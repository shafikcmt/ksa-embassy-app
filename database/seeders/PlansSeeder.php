<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'                      => 'Trial',
                'slug'                      => 'trial',
                'price'                     => 0,
                'max_hr'                    => 20,
                'max_users'                 => 2,
                'max_agents'                => 5,
                'max_embassy_lists_monthly' => 5,
                'max_pdf_monthly'           => 30,
                'storage_limit_mb'          => 256,
                'duration_days'             => 14,
                'is_active'                 => true,
                'description'               => '14-day free trial',
            ],
            [
                'name'                      => 'Basic',
                'slug'                      => 'basic',
                'price'                     => 29.00,
                'max_hr'                    => 100,
                'max_users'                 => 3,
                'max_agents'                => 15,
                'max_embassy_lists_monthly' => 20,
                'max_pdf_monthly'           => 150,
                'storage_limit_mb'          => 512,
                'duration_days'             => 30,
                'is_active'                 => true,
                'description'               => 'For small agencies',
            ],
            [
                'name'                      => 'Standard',
                'slug'                      => 'standard',
                'price'                     => 59.00,
                'max_hr'                    => 500,
                'max_users'                 => 8,
                'max_agents'                => 50,
                'max_embassy_lists_monthly' => 60,
                'max_pdf_monthly'           => 500,
                'storage_limit_mb'          => 2048,
                'duration_days'             => 30,
                'is_active'                 => true,
                'description'               => 'For growing agencies',
            ],
            [
                'name'                      => 'Premium',
                'slug'                      => 'premium',
                'price'                     => 99.00,
                'max_hr'                    => 9999,
                'max_users'                 => 25,
                'max_agents'                => 999,
                'max_embassy_lists_monthly' => 999,
                'max_pdf_monthly'           => 9999,
                'storage_limit_mb'          => 10240,
                'duration_days'             => 30,
                'is_active'                 => true,
                'description'               => 'Unlimited — for large agencies',
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
