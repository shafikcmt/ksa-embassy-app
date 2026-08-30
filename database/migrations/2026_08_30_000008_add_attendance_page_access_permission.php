<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Introduce the access_attendance page-access permission for the Attendance
 * module (see App\Support\PagePermissions).
 *
 * Behaviour is grandfathered exactly like the original page-access migration
 * (2026_08_30_000003): every EXISTING agency_staff account is backfilled so
 * nothing they can do today changes. Only staff created AFTER this migration
 * start without it unless the admin enables it on the Staff Accounts screen.
 *
 * The permission name is a string literal here (not PagePermissions::permissions())
 * so this migration grants exactly this one new permission, independent of any
 * future changes to the module map.
 */
return new class extends Migration
{
    private const PERMISSION = 'access_attendance';

    public function up(): void
    {
        $this->forgetCache();

        // 1. Create the permission (idempotent).
        Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

        // 2. Agency admins must never be restricted — grant it to the role.
        $admin = Role::where('name', 'agency_admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo(self::PERMISSION);
        }

        // 3. Backfill existing staff with the new access (zero behaviour change).
        $staffRole = Role::where('name', 'agency_staff')->where('guard_name', 'web')->first();
        if ($staffRole) {
            foreach ($staffRole->users as $user) {
                $user->givePermissionTo(self::PERMISSION);
            }
        }

        $this->forgetCache();
    }

    public function down(): void
    {
        $this->forgetCache();

        Permission::where('name', self::PERMISSION)->get()->each->delete();

        $this->forgetCache();
    }

    private function forgetCache(): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Cache store not ready during a fresh deploy — safe to ignore.
        }
    }
};
