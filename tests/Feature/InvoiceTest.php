<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\HrProfile;
use App\Models\Invoice;
use App\Models\MofaEntry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ERP Invoices — tenancy, numbering, server-side totals, lifecycle locks,
 * permissions, soft delete, audit log, PDF.
 *
 * MySQL only (see project test-DB note):
 *   DB_CONNECTION=mysql DB_DATABASE=ksa_embassy_test php artisan test tests/Feature/InvoiceTest.php
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private Agency $other;
    private User $admin;
    private User $staff;
    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);

        $this->agency = $this->makeAgency('alpha', $plan);
        $this->other  = $this->makeAgency('beta', $plan);

        $this->admin      = $this->makeUser($this->agency, 'admin@alpha.test', 'agency_admin');
        $this->staff      = $this->makeUser($this->agency, 'staff@alpha.test', 'agency_staff');
        $this->staff->givePermissionTo('access_erp');
        $this->otherAdmin = $this->makeUser($this->other, 'admin@beta.test', 'agency_admin');
    }

    private function makeAgency(string $slug, Plan $plan): Agency
    {
        $agency = Agency::create(['name' => ucfirst($slug) . ' Agency', 'slug' => $slug, 'status' => 'active']);
        Subscription::create([
            'agency_id' => $agency->id, 'plan_id' => $plan->id,
            'start_date' => now()->subDay(), 'end_date' => now()->addYear(),
            'status' => 'active', 'payment_status' => 'paid', 'amount' => 0,
        ]);

        return $agency;
    }

    private function makeUser(Agency $agency, string $email, string $role): User
    {
        $user = User::create([
            'name' => $email, 'email' => $email, 'password' => Hash::make('secret1234'),
            'agency_id' => $agency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'invoice_date'   => '2026-09-24',
            'bill_to_name'   => 'Walk-in Customer',
            'status'         => 'pending',
            'currency'       => 'BDT',
            'tax_type'       => 'percent',
            'tax_value'      => '15',
            'discount_type'  => 'amount',
            'discount_value' => '100.30',
            'items'          => [
                ['processing_fee' => '0.30', 'mofa_fee' => '0'],   // subtotal contribution: 0.30
                ['processing_fee' => '3000.00', 'mofa_fee' => '0'], // subtotal contribution: 3000.00
            ],
        ], $overrides);
    }

    private function createAs(User $user, array $overrides = []): Invoice
    {
        $this->actingAs($user)->post(route('erp.invoices.store'), $this->payload($overrides))->assertRedirect();

        return Invoice::latest('id')->firstOrFail();
    }

    private function deleteAs(User $user, Invoice $invoice, ?string $reason = 'Entered by mistake', ?string $number = null)
    {
        return $this->actingAs($user)->delete(route('erp.invoices.destroy', $invoice), [
            'delete_reason'  => $reason,
            'confirm_number' => $number ?? $invoice->invoice_number,
        ]);
    }

    public function test_store_computes_totals_on_server_and_numbers_per_agency(): void
    {
        $inv = $this->createAs($this->staff, ['total_amount' => '1.00', 'subtotal' => '1.00']); // hostile fields ignored

        $this->assertSame("INV-{$this->agency->id}-2026-0001", $inv->invoice_number);
        $this->assertSame('3000.30', (string) $inv->subtotal);
        $this->assertSame('450.05', (string) $inv->tax_amount);
        $this->assertSame('100.30', (string) $inv->discount_amount);
        $this->assertSame('3350.05', (string) $inv->total_amount);
        $this->assertSame(['0.30', '3000.00'], $inv->items->pluck('total_amount')->map(fn ($a) => (string) $a)->all());
        $this->assertSame($this->agency->id, $inv->agency_id);

        $second = $this->createAs($this->admin);
        $this->assertSame("INV-{$this->agency->id}-2026-0002", $second->invoice_number);

        $otherInv = $this->createAs($this->otherAdmin);
        $this->assertSame("INV-{$this->other->id}-2026-0001", $otherInv->invoice_number);

        $this->assertTrue(AuditLog::where('action', 'invoice_created')->where('auditable_id', $inv->id)->exists());
    }

    public function test_deleted_draft_number_is_never_reissued(): void
    {
        $draft = $this->createAs($this->admin, ['status' => 'draft']);
        $this->deleteAs($this->admin, $draft)->assertRedirect(route('erp.invoices.index'));

        $this->assertSoftDeleted('invoices', ['id' => $draft->id]);
        $next = $this->createAs($this->admin);
        $this->assertStringEndsWith('-0002', $next->invoice_number);

        // A deleted PAID invoice keeps its number reserved too.
        $this->actingAs($this->admin)->patch(route('erp.invoices.mark-paid', $next), ['payment_method' => 'cash', 'paid_at' => today()->format('Y-m-d')]);
        $this->deleteAs($this->admin, $next->fresh())->assertRedirect(route('erp.invoices.index'));
        $this->assertStringEndsWith('-0003', $this->createAs($this->admin)->invoice_number);
    }

    public function test_other_agency_cannot_view_edit_pay_or_print(): void
    {
        $inv = $this->createAs($this->admin);

        $this->actingAs($this->otherAdmin)->get(route('erp.invoices.show', $inv))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.invoices.edit', $inv))->assertForbidden();
        $this->actingAs($this->otherAdmin)->put(route('erp.invoices.update', $inv), $this->payload())->assertForbidden();
        $this->actingAs($this->otherAdmin)->patch(route('erp.invoices.mark-paid', $inv), ['payment_method' => 'cash', 'paid_at' => '2026-09-24'])->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.invoices.download-pdf', $inv))->assertForbidden();

        $this->actingAs($this->otherAdmin)->get(route('erp.invoices.index'))->assertOk()->assertDontSee($inv->invoice_number);
    }

    public function test_cannot_attach_another_agencys_passenger(): void
    {
        $foreign = HrProfile::create([
            'agency_id' => $this->other->id, 'full_name_en' => 'Foreign Pax', 'status' => 'active',
            'nationality' => 'Bangladeshi', 'date_of_birth' => '1990-01-01', 'gender' => 'male',
        ]);

        $this->actingAs($this->admin)->post(route('erp.invoices.store'), $this->payload([
            'items' => [['hr_profile_id' => $foreign->id, 'processing_fee' => '10', 'mofa_fee' => '0']],
        ]))->assertSessionHasErrors('items.0.hr_profile_id');

        $this->assertSame(0, Invoice::count());
    }

    public function test_rejects_bad_money_and_over_100_percent_and_negative_total(): void
    {
        $this->actingAs($this->admin)->post(route('erp.invoices.store'), $this->payload([
            'items' => [['processing_fee' => '10.005', 'mofa_fee' => '0']],
        ]))->assertSessionHasErrors('items.0.processing_fee');

        $this->actingAs($this->admin)->post(route('erp.invoices.store'), $this->payload(['tax_value' => '100.01']))
            ->assertSessionHasErrors('tax_value');

        $this->actingAs($this->admin)->post(route('erp.invoices.store'), $this->payload([
            'tax_type' => 'none', 'discount_value' => '999999',
        ]))->assertSessionHasErrors('discount_value');

        $this->assertSame(0, Invoice::count());
    }

    public function test_mark_paid_locks_invoice(): void
    {
        $inv = $this->createAs($this->admin);

        $this->actingAs($this->staff)->patch(route('erp.invoices.mark-paid', $inv), ['payment_method' => 'cash', 'paid_at' => '2026-09-24'])
            ->assertForbidden(); // staff without erp_receive_payment

        $this->actingAs($this->admin)->patch(route('erp.invoices.mark-paid', $inv), [
            'payment_method' => 'bank_transfer', 'paid_at' => today()->format('Y-m-d'), 'payment_reference' => 'TRX-1',
        ])->assertRedirect();

        $inv->refresh();
        $this->assertSame('paid', $inv->status);
        $this->assertSame('bank_transfer', $inv->payment_method);
        $this->assertSame($this->admin->id, $inv->paid_by);

        // Locked: no edit, no update, no cancel, no second payment (admin-only delete is covered separately).
        $this->actingAs($this->admin)->get(route('erp.invoices.edit', $inv))->assertRedirect(route('erp.invoices.show', $inv));
        $this->actingAs($this->admin)->put(route('erp.invoices.update', $inv), $this->payload())->assertForbidden();
        $this->actingAs($this->admin)->patch(route('erp.invoices.cancel', $inv))->assertSessionHasErrors('status');
        $this->actingAs($this->admin)->patch(route('erp.invoices.mark-paid', $inv), ['payment_method' => 'cash', 'paid_at' => '2026-09-24'])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame('3350.05', (string) $inv->fresh()->total_amount);
    }

    public function test_granted_staff_can_mark_paid(): void
    {
        $this->staff->givePermissionTo('erp_receive_payment');
        $inv = $this->createAs($this->admin);

        $this->actingAs($this->staff)->patch(route('erp.invoices.mark-paid', $inv), ['payment_method' => 'cash', 'paid_at' => '2026-09-24'])
            ->assertRedirect();
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_update_recalculates_and_replaces_lines(): void
    {
        $inv = $this->createAs($this->staff, ['status' => 'draft']);

        $this->actingAs($this->staff)->put(route('erp.invoices.update', $inv), $this->payload([
            'status' => 'pending', 'tax_type' => 'none', 'discount_type' => 'none',
            'items' => [['processing_fee' => '1001.00', 'mofa_fee' => '0']],
        ]))->assertRedirect(route('erp.invoices.show', $inv));

        $inv->refresh()->load('items');
        $this->assertSame('pending', $inv->status);
        $this->assertCount(1, $inv->items);
        $this->assertSame('1001.00', (string) $inv->total_amount);
        $this->assertSame('0.00', (string) $inv->tax_value);
        $this->assertTrue(AuditLog::where('action', 'invoice_updated')->where('auditable_id', $inv->id)->exists());
    }

    public function test_delete_permissions_by_status_and_creator(): void
    {
        $adminDraft = $this->createAs($this->admin, ['status' => 'draft']);
        $staffDraft = $this->createAs($this->staff, ['status' => 'draft']);
        $pending    = $this->createAs($this->staff);
        $paid       = $this->createAs($this->admin);
        $this->actingAs($this->admin)->patch(route('erp.invoices.mark-paid', $paid), ['payment_method' => 'cash', 'paid_at' => today()->format('Y-m-d')]);
        $cancelled  = $this->createAs($this->admin);
        $this->actingAs($this->admin)->patch(route('erp.invoices.cancel', $cancelled));

        // Staff: only drafts they created themselves.
        $this->deleteAs($this->staff, $adminDraft)->assertForbidden();
        $this->deleteAs($this->staff, $pending)->assertForbidden();   // own, but not a draft
        $this->deleteAs($this->staff, $paid->fresh())->assertForbidden();
        $this->deleteAs($this->staff, $cancelled->fresh())->assertForbidden();
        $this->deleteAs($this->staff, $staffDraft)->assertRedirect(route('erp.invoices.index'));
        $this->assertSoftDeleted('invoices', ['id' => $staffDraft->id]);

        // Admin: every status.
        foreach ([$adminDraft, $pending, $paid, $cancelled] as $inv) {
            $this->deleteAs($this->admin, $inv->fresh())->assertRedirect(route('erp.invoices.index'))->assertSessionHas('success');
            $this->assertSoftDeleted('invoices', ['id' => $inv->id]);
        }

        $row = Invoice::withTrashed()->find($paid->id);
        $this->assertSame($this->admin->id, (int) $row->deleted_by);
        $this->assertSame('Entered by mistake', $row->delete_reason);
        $this->assertSame(2, $row->items()->count()); // items stay linked, not deleted

        $log = AuditLog::where('action', 'invoice_deleted')->where('auditable_id', $paid->id)->firstOrFail();
        $this->assertSame($paid->invoice_number, $log->new_values['invoice_number']);
        $this->assertSame('Entered by mistake', $log->new_values['delete_reason']);
        $this->assertSame('paid', $log->new_values['status']);
    }

    public function test_delete_requires_reason_and_exact_typed_number(): void
    {
        $inv = $this->createAs($this->admin);

        $this->deleteAs($this->admin, $inv, '', null)->assertSessionHasErrors('delete_reason');
        $this->deleteAs($this->admin, $inv, 'oops', null)->assertSessionHasErrors('delete_reason');       // < 5 chars
        $this->deleteAs($this->admin, $inv, '    abc    ', null)->assertSessionHasErrors('delete_reason'); // trimmed first
        $this->deleteAs($this->admin, $inv, 'Wrong invoice', '')->assertSessionHasErrors('confirm_number');
        $this->deleteAs($this->admin, $inv, 'Wrong invoice', strtolower($inv->invoice_number))->assertSessionHasErrors('confirm_number');
        $this->assertNotSoftDeleted('invoices', ['id' => $inv->id]);

        $this->deleteAs($this->admin, $inv, 'Wrong invoice', '  ' . $inv->invoice_number . ' ')->assertRedirect(route('erp.invoices.index'));
        $this->assertSoftDeleted('invoices', ['id' => $inv->id]);
    }

    public function test_other_agency_delete_is_404_and_deleted_invoices_leave_list_and_totals(): void
    {
        $inv  = $this->createAs($this->admin);
        $keep = $this->createAs($this->admin, ['bill_to_name' => 'Kept Customer']);

        $this->deleteAs($this->otherAdmin, $inv)->assertNotFound();
        $this->assertNotSoftDeleted('invoices', ['id' => $inv->id]);

        $this->deleteAs($this->admin, $inv)->assertRedirect(route('erp.invoices.index'));

        $this->actingAs($this->admin)->get(route('erp.invoices.index'))
            // (the success flash still names the number, so check the row link instead)
            ->assertOk()->assertDontSee('href="' . route('erp.invoices.show', $inv) . '"', false)->assertSee($keep->invoice_number)
            ->assertSee('1 pending invoice');
        $this->actingAs($this->admin)->get(route('erp.invoices.index', ['q' => $inv->invoice_number]))
            ->assertOk()->assertSee('No invoices match these filters.'); // search box echoes q, so check the empty state
        $this->actingAs($this->admin)->get(route('erp.invoices.show', $inv))->assertNotFound();
    }

    public function test_pages_and_pdf_render(): void
    {
        $inv = $this->createAs($this->admin);

        $this->actingAs($this->staff)->get(route('erp.invoices.index'))->assertOk()->assertSee($inv->invoice_number);
        $this->actingAs($this->staff)->get(route('erp.invoices.index', ['status' => 'pending', 'q' => 'Walk-in Customer', 'sort' => 'amount_desc']))
            ->assertOk()->assertSee($inv->invoice_number);
        $this->actingAs($this->staff)->get(route('erp.invoices.create'))->assertOk()->assertDontSee('name="due_date"', false);
        $this->actingAs($this->staff)->get(route('erp.invoices.index', ['status' => 'overdue']))->assertOk(); // old URLs still work
        $this->actingAs($this->staff)->get(route('erp.invoices.show', $inv))->assertOk()->assertSee('3,350.05');
        $this->actingAs($this->staff)->get(route('erp.invoices.edit', $inv))->assertOk();

        $this->actingAs($this->staff)->get(route('erp.invoices.preview-pdf', $inv))
            ->assertOk()->assertSee('frame.contentWindow.print()', false);
        $this->actingAs($this->staff)->get(route('erp.invoices.preview-pdf', [$inv, 'raw' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $res = $this->actingAs($this->staff)->get(route('erp.invoices.download-pdf', $inv))->assertOk();
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }

    public function test_erp_only_passenger_is_searchable_and_shown_on_invoice(): void
    {
        MofaEntry::create(['agency_id' => $this->agency->id, 'full_name' => 'Rahim Uddin', 'passport_number' => 'ek0753712', 'mofa_date' => '2026-09-25']);
        MofaEntry::create(['agency_id' => $this->other->id, 'full_name' => 'Foreign Person', 'passport_number' => 'ZZ0000001', 'mofa_date' => '2026-09-25']);

        // Picker data includes the MOFA-only passenger, never another agency's.
        $this->actingAs($this->staff)->get(route('erp.invoices.create'))->assertOk()
            ->assertSee('EK0753712')->assertSee('Rahim Uddin')->assertDontSee('ZZ0000001');

        $inv = $this->createAs($this->admin, ['items' => [
            ['passenger_name' => 'Rahim Uddin', 'passport_no' => 'ek0753712', 'processing_fee' => '0', 'mofa_fee' => '3000', 'paid_amount' => '3000'],
        ]]);
        $item = $inv->items()->sole();
        $this->assertSame(['Rahim Uddin', 'EK0753712'], [$item->displayName(), $item->displayPassport()]);

        $this->actingAs($this->staff)->get(route('erp.invoices.show', $inv))->assertOk()->assertSee('Rahim Uddin')->assertSee('EK0753712');
        $this->actingAs($this->staff)->get(route('erp.invoices.index', ['q' => 'EK0753712']))->assertOk()->assertSee($inv->invoice_number);
        $this->actingAs($this->staff)->get(route('erp.invoices.edit', $inv))->assertOk()->assertSee('EK0753712');
    }

    public function test_hr_linked_line_snapshots_name_and_passport(): void
    {
        $hr = HrProfile::create([
            'agency_id' => $this->agency->id, 'full_name_en' => 'Hr Pax', 'status' => 'active',
            'nationality' => 'Bangladeshi', 'date_of_birth' => '1990-01-01', 'gender' => 'male',
        ]);
        $hr->passport()->create(['passport_number' => 'HR1234567']);

        $inv = $this->createAs($this->admin, ['items' => [['hr_profile_id' => $hr->id, 'processing_fee' => '1000', 'mofa_fee' => '0']]]);
        $item = $inv->items()->sole();
        $this->assertSame(['Hr Pax', 'HR1234567'], [$item->passenger_name, $item->passport_no]);
    }
}
