<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clearing the permission cache must not abort seeding if the configured
        // cache store (e.g. database) is not yet available during a fresh deploy.
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Cache not ready yet — safe to ignore on first deploy.
        }

        $permissions = [
            // Agency management
            'view_agency', 'edit_agency',
            // Users
            'view_users', 'create_users', 'edit_users', 'delete_users',
            // Agents
            'view_agents', 'create_agents', 'edit_agents', 'delete_agents',
            // HR
            'view_hr', 'create_hr', 'edit_hr', 'delete_hr',
            // Embassy list
            'view_embassy_list', 'create_embassy_list', 'edit_embassy_list', 'delete_embassy_list',
            // PDF
            'print_pdf',
            // Notices
            'view_notices', 'manage_notices',
            // Page access — per-staff module gating (see App\Support\PagePermissions).
            // Admins get all of these; staff get only what an admin grants them
            // directly, so these are intentionally NOT added to the staff role below.
            'access_hr', 'access_embassy_list', 'access_agents', 'access_license', 'access_notes',
            'access_attendance', 'access_erp',
            // Action permissions (fine-grained; see App\Support\ActionPermissions).
            // Admins get these via the syncPermissions($permissions) call below.
            // agency_staff do NOT — omitted from the staff role list on purpose.
            'erp_receive_payment',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        $agencyAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agency_admin']);
        $agencyAdmin->syncPermissions($permissions);

        $agencyStaff = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'agency_staff']);
        $agencyStaff->syncPermissions([
            'view_agency',
            'view_agents', 'create_agents', 'edit_agents',
            'view_hr', 'create_hr', 'edit_hr',
            'view_embassy_list', 'create_embassy_list', 'edit_embassy_list',
            'print_pdf',
            'view_notices',
        ]);
    }
}
