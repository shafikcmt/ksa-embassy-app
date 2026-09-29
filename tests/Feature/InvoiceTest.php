<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\HrProfile;
use App\Models\Invoice;
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
            'due_date'       => '2026-10-01',
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
        $this->actingAs($this->admin)->delete(route('erp.invoices.destroy', $draft))->assertRedirect();

        $this->assertSoftDeleted('invoices', ['id' => $draft->id]);
        $next = $this->createAs($this->admin);
        $this->assertStringEndsWith('-0002', $next->invoice_number);
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

        // Locked: no edit, no update, no delete, no cancel, no second payment.
        $this->actingAs($this->admin)->get(route('erp.invoices.edit', $inv))->assertRedirect(route('erp.invoices.show', $inv));
        $this->actingAs($this->admin)->put(route('erp.invoices.update', $inv), $this->payload())->assertForbidden();
        $this->actingAs($this->admin)->delete(route('erp.invoices.destroy', $inv));
        $this->assertNotSoftDeleted('invoices', ['id' => $inv->id]);
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

    public function test_only_admin_deletes_and_only_drafts(): void
    {
        $draft = $this->createAs($this->admin, ['status' => 'draft']);
        $pending = $this->createAs($this->admin);

        $this->actingAs($this->staff)->delete(route('erp.invoices.destroy', $draft))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('erp.invoices.destroy', $pending))->assertSessionHas('error');
        $this->assertNotSoftDeleted('invoices', ['id' => $pending->id]);

        $this->actingAs($this->admin)->delete(route('erp.invoices.destroy', $draft));
        $this->assertSoftDeleted('invoices', ['id' => $draft->id]);
    }

    public function test_pages_and_pdf_render(): void
    {
        $inv = $this->createAs($this->admin);

        $this->actingAs($this->staff)->get(route('erp.invoices.index'))->assertOk()->assertSee($inv->invoice_number);
        $this->actingAs($this->staff)->get(route('erp.invoices.index', ['status' => 'pending', 'q' => 'Walk-in Customer', 'sort' => 'amount_desc']))
            ->assertOk()->assertSee($inv->invoice_number);
        $this->actingAs($this->staff)->get(route('erp.invoices.create'))->assertOk();
        $this->actingAs($this->staff)->get(route('erp.invoices.show', $inv))->assertOk()->assertSee('3,350.05');
        $this->actingAs($this->staff)->get(route('erp.invoices.edit', $inv))->assertOk();

        $this->actingAs($this->staff)->get(route('erp.invoices.preview-pdf', $inv))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $res = $this->actingAs($this->staff)->get(route('erp.invoices.download-pdf', $inv))->assertOk();
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }
}
