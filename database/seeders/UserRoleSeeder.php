<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allUsers = User::all();

        // Set first 5 users as Super Admin, the rest is Admin level only.
        foreach ($allUsers as $user) {
            $role = $user->id > 5 ? Role::ROLE_ADMIN : Role::ROLE_SUPER_ADMIN;
            $user->assignRole($role);
        }
    }
}
