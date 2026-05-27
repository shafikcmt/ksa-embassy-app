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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
