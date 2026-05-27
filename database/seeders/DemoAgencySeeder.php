<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoAgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = \App\Models\Plan::where('slug', 'standard')->first();
        if (! $plan) return;

        // Create demo agency
        $agency = \App\Models\Agency::firstOrCreate(
            ['slug' => 'al-noor-recruitment-demo'],
            [
                'name'                => 'Al-Noor Recruitment Agency (Demo)',
                'slug'                => 'al-noor-recruitment-demo',
                'license_number'      => 'LIC-2024-00123',
                'rl_number'           => 'RL-2024-456',
                'address'             => 'Riyadh, Al-Olaya District, King Fahd Road, Building 12',
                'phone'               => '+966112345678',
                'email'               => 'info@alnoor-demo.sa',
                'license_expiry_date' => now()->addYear(),
                'status'              => 'active',
            ]
        );

        // Create agency admin
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@alnoor-demo.sa'],
            [
                'name'           => 'Agency Admin',
                'email'          => 'admin@alnoor-demo.sa',
                'password'       => \Illuminate\Support\Facades\Hash::make('Admin@1234'),
                'agency_id'      => $agency->id,
                'is_super_admin' => false,
                'is_active'      => true,
            ]
        );
        if (! $admin->hasRole('agency_admin')) {
            $admin->assignRole('agency_admin');
        }

        // Create agency staff
        $staff = \App\Models\User::firstOrCreate(
            ['email' => 'staff@alnoor-demo.sa'],
            [
                'name'           => 'Agency Staff',
                'email'          => 'staff@alnoor-demo.sa',
                'password'       => \Illuminate\Support\Facades\Hash::make('Staff@1234'),
                'agency_id'      => $agency->id,
                'is_super_admin' => false,
                'is_active'      => true,
            ]
        );
        if (! $staff->hasRole('agency_staff')) {
            $staff->assignRole('agency_staff');
        }

        // Active subscription
        \App\Models\Subscription::firstOrCreate(
            ['agency_id' => $agency->id, 'plan_id' => $plan->id],
            [
                'start_date'     => now(),
                'end_date'       => now()->addDays(30),
                'status'         => 'active',
                'payment_status' => 'paid',
                'amount'         => $plan->price,
            ]
        );
    }
}
