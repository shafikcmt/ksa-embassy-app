<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed if at least one agency exists
        $agency = \App\Models\Agency::first();
        if (! $agency) return;

        $adminUser = \App\Models\User::where('agency_id', $agency->id)->first();

        $demoAgents = [
            [
                'name'    => 'Mohammed Al-Rashid',
                'email'   => 'mohammed.rashid@demo.sa',
                'phone'   => '+966501234567',
                'address' => 'Riyadh, Al-Olaya District, King Fahd Road',
                'status'  => 'active',
                'notes'   => 'Senior recruitment agent',
            ],
            [
                'name'    => 'Abdullah Hassan',
                'email'   => 'abdullah.hassan@demo.sa',
                'phone'   => '+966512345678',
                'address' => 'Jeddah, Al-Hamra District',
                'status'  => 'active',
                'notes'   => null,
            ],
            [
                'name'    => 'Fatima Al-Zahra',
                'email'   => null,
                'phone'   => '+966523456789',
                'address' => 'Dammam, Al-Faisaliyah',
                'status'  => 'inactive',
                'notes'   => 'On leave',
            ],
        ];

        foreach ($demoAgents as $data) {
            \App\Models\Agent::firstOrCreate(
                ['agency_id' => $agency->id, 'phone' => $data['phone']],
                array_merge($data, [
                    'agency_id'  => $agency->id,
                    'created_by' => $adminUser?->id,
                    'updated_by' => $adminUser?->id,
                ])
            );
        }
    }
}
