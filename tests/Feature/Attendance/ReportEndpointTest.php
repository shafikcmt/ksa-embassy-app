<?php

namespace Tests\Feature\Attendance;

use App\Models\Agency;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * H3d check (D) — tenant + role on the Dashboard/Reports endpoints.
 *   - Dashboard/Reports viewing: any access_attendance user (staff scoped to own).
 *   - CSV/PDF export: admin-only (403 for staff).
 *   - Tenant isolation: an agency's report/export never contains another tenant.
 *   - Employee filter (admin) narrows to one employee.
 */
class ReportEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $admin;
    private User $staffUser;
    private Employee $staffEmployee;
    private Employee $peerEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $this->agency = Agency::create([
            'name' => 'Report Co', 'slug' => 'report-co', 'status' => 'active', 'license_number' => 'LIC-REP-0001',
        ]);
        $this->admin = $this->makeUser($this->agency, 'admin@report.co', 'agency_admin');
        $this->staffUser = $this->makeUser($this->agency, 'staff@report.co', 'agency_staff');
        $this->staffUser->givePermissionTo('access_attendance');

        $this->staffEmployee = Employee::create([
            'agency_id' => $this->agency->id, 'user_id' => $this->staffUser->id, 'name' => 'Sara Staff', 'status' => 'active',
        ]);
        $this->peerEmployee = Employee::create([
            'agency_id' => $this->agency->id, 'name' => 'Peter Peer', 'status' => 'active',
        ]);

        AttendanceSetting::create([
            'agency_id' => $this->agency->id, 'office_start' => '09:00', 'office_end' => '17:00',
            'grace_minutes' => 0, 'timezone' => 'UTC', 'weekend_days' => [5, 6],
        ]);

        // A present row so reports have content.
        AttendanceRecord::create([
            'agency_id' => $this->agency->id, 'employee_id' => $this->staffEmployee->id,
            'work_date' => '2026-09-07', 'status' => 'present', 'source' => 'admin',
        ]);
    }

    private function makeUser(Agency $agency, string $email, string $role): User
    {
        $u = User::create([
            'name' => $email, 'email' => $email, 'password' => Hash::make('secret1234'),
            'agency_id' => $agency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    // ── Viewing ───────────────────────────────────────────────────────────────

    public function test_admin_can_open_dashboard_and_reports_tabs(): void
    {
        $this->actingAs($this->admin)->get(route('attendance.index', ['tab' => 'dashboard']))
            ->assertOk()->assertSee('On-time');
        $this->actingAs($this->admin)->get(route('attendance.index', ['tab' => 'reports', 'from' => '2026-09-01', 'to' => '2026-09-30']))
            ->assertOk()->assertSee('Sara Staff')->assertSee('Peter Peer');
    }

    public function test_staff_report_is_scoped_to_own_employee(): void
    {
        // NB: the peer's name legitimately appears in the read-only Employees tab, so
        // whole-page assertDontSee is wrong — assert the REPORT summary scope directly.
        $res = $this->actingAs($this->staffUser)->get(route('attendance.index', ['tab' => 'reports', 'from' => '2026-09-01', 'to' => '2026-09-30']));
        $res->assertOk();

        $summary = $res->viewData('report')['summary'];
        $this->assertSame([$this->staffEmployee->id], array_keys($summary)); // own employee only

        // And an admin's summary spans the whole active workforce.
        $adminSummary = $this->actingAs($this->admin)
            ->get(route('attendance.index', ['tab' => 'reports', 'from' => '2026-09-01', 'to' => '2026-09-30']))
            ->viewData('report')['summary'];
        $this->assertEqualsCanonicalizing(
            [$this->staffEmployee->id, $this->peerEmployee->id],
            array_keys($adminSummary),
        );
    }

    // ── Export role gate ────────────────────────────────────────────────────────

    public function test_csv_export_is_admin_only(): void
    {
        $this->actingAs($this->staffUser)->get(route('attendance.reports.export-csv'))->assertForbidden();

        $res = $this->actingAs($this->admin)->get(route('attendance.reports.export-csv', ['from' => '2026-09-01', 'to' => '2026-09-30']));
        $res->assertOk();
        $csv = $res->streamedContent();
        $this->assertStringContainsString('Attendance Report', $csv);
        $this->assertStringContainsString('Sara Staff', $csv);
        $this->assertStringContainsString('On-time %', $csv);
    }

    public function test_pdf_export_is_admin_only(): void
    {
        $this->actingAs($this->staffUser)->get(route('attendance.reports.export-pdf'))->assertForbidden();

        $res = $this->actingAs($this->admin)->get(route('attendance.reports.export-pdf', ['from' => '2026-09-01', 'to' => '2026-09-30']));
        $res->assertOk();
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    // ── Tenant isolation ────────────────────────────────────────────────────────

    public function test_export_never_contains_another_tenant(): void
    {
        $other = Agency::create(['name' => 'Rival Co', 'slug' => 'rival-co', 'status' => 'active', 'license_number' => 'LIC-REP-0002']);
        Employee::create(['agency_id' => $other->id, 'name' => 'Rival Worker', 'status' => 'active']);

        $csv = $this->actingAs($this->admin)
            ->get(route('attendance.reports.export-csv', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->streamedContent();

        $this->assertStringContainsString('Sara Staff', $csv);
        $this->assertStringNotContainsString('Rival Worker', $csv);
    }

    // ── Employee filter ─────────────────────────────────────────────────────────

    public function test_admin_employee_filter_narrows_to_one(): void
    {
        $csv = $this->actingAs($this->admin)->get(route('attendance.reports.export-csv', [
            'from' => '2026-09-01', 'to' => '2026-09-30', 'employee_id' => $this->staffEmployee->id,
        ]))->streamedContent();

        $this->assertStringContainsString('Sara Staff', $csv);
        $this->assertStringNotContainsString('Peter Peer', $csv);
        // Single-employee focus adds the day-by-day block.
        $this->assertStringContainsString('Day-by-day', $csv);
    }
}
