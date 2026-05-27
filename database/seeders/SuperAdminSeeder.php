<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'superadmin@ksa.local'],
            [
                'name'           => 'Super Admin',
                'password'       => \Illuminate\Support\Facades\Hash::make('SuperAdmin@123'),
                'agency_id'      => null,
                'is_super_admin' => true,
                'is_active'      => true,
            ]
        );
    }
}
