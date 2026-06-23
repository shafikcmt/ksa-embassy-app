<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder used by the live deploy (deploy.sh).
 *
 * Only the essential, fully idempotent seeders run here so it is safe to call
 * on EVERY deploy. Demo/sample data (agencies, agents, HR, embassy lists) is
 * intentionally excluded — that belongs to local development via DatabaseSeeder.
 */
class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            PlansSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
