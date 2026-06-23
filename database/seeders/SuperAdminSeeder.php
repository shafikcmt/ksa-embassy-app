<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotent: creates the super admin once. On future deploys the existing
     * account is left untouched so a manually changed password is never reset.
     */
    public function run(): void
    {
        $user = User::firstOrNew(['email' => 'superadmin@ksa.local']);

        // Only set the default password when the account is first created.
        if (! $user->exists) {
            $user->password = Hash::make('SuperAdmin@123');
        }

        $user->name           = $user->name ?: 'Super Admin';
        $user->agency_id      = null;
        $user->is_super_admin = true;
        $user->is_active      = true;
        $user->save();
    }
}
