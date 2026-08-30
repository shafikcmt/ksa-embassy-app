<?php

use App\Support\PagePermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Introduce the five agency "page access" permissions (access_hr, ...).
 *
 * Behaviour is grandfathered: every EXISTING agency_staff account is backfilled
 * with all five so nothing they can do today changes. Only staff created AFTER
 * this migration (via the new Staff Accounts screen) start restricted, with the
 * admin explicitly choosing which modules to enable.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = PagePermissions::permissions();

        $this->forgetCache();

        // 1. Create the five page-access permissions (idempotent).
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 2. Agency admins must never be restricted — grant all five to the role.
        $admin = Role::where('name', 'agency_admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // 3. Backfill existing staff with full access (zero behaviour change).
        $staffRole = Role::where('name', 'agency_staff')->where('guard_name', 'web')->first();
        if ($staffRole) {
            foreach ($staffRole->users as $user) {
                $user->givePermissionTo($permissions);
            }
        }

        $this->forgetCache();
    }

    public function down(): void
    {
        $this->forgetCache();

        Permission::whereIn('name', PagePermissions::permissions())
            ->get()
            ->each->delete();

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
