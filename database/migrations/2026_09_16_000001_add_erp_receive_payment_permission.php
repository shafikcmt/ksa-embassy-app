<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Introduce the erp_receive_payment ACTION permission.
 *
 * Unlike the access_* page-access permissions, this is a fine-grained action
 * grant (see App\Support\ActionPermissions). It lets an agency admin authorise
 * specific staff to record payments on Delivery / Double MOFA records.
 *
 * DELIBERATELY NOT backfilled to existing agency_staff (unlike the page-access
 * migrations 2026_08_30_* / 2026_08_31_000001): staff must never receive this by
 * default — only when an admin explicitly grants it on the Staff Accounts screen.
 * Agency admins ARE granted it here so they keep full access on fresh installs
 * and on existing production databases.
 */
return new class extends Migration
{
    private const PERMISSION = 'erp_receive_payment';

    public function up(): void
    {
        $this->forgetCache();

        // 1. Create the permission (idempotent — safe to re-run on existing DBs).
        Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

        // 2. Agency admins must never be restricted — grant it to the role.
        $admin = Role::where('name', 'agency_admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo(self::PERMISSION);
        }

        // NOTE: intentionally NO agency_staff backfill — staff get this only when
        // an admin enables it per-user via the Staff Accounts screen.

        $this->forgetCache();
    }

    public function down(): void
    {
        $this->forgetCache();

        // Removes ONLY this permission (and its own role/model pivot rows via
        // Spatie's delete). No other permission, role, or user grant is touched.
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
