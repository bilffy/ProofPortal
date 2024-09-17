<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            Role::ROLE_SUPER_ADMIN,
            Role::ROLE_ADMIN,
            Role::ROLE_FRANCHISE,
            Role::ROLE_SCHOOL_ADMIN,
            Role::ROLE_PHOTO_COORDINATOR,
            Role::ROLE_TEACHER,
        ];

        // Create or get 'create user' Permission
        $permission = Permission::createOrFirst(['name' => 'create user']);
        foreach ($roles as $roleName) {
            $role = \Spatie\Permission\Models\Role::createOrFirst(['name' => $roleName]);
            // Add 'create user' permission to roles except Photo Coordinator and Teacher
            if (!in_array($roleName, [Role::ROLE_PHOTO_COORDINATOR, Role::ROLE_TEACHER])) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
