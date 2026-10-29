<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\RBAC\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@lcms.local'],
            [
                'name' => 'SuperAdmin',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if ($superAdminRole !== null) {
            $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
