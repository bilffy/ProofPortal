<?php

namespace Database\Seeders;

use App\Helpers\RoleHelper;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            RoleHelper::ROLE_SUPER_ADMIN,
            RoleHelper::ROLE_ADMIN,
            RoleHelper::ROLE_FRANCHISE,
            RoleHelper::ROLE_SCHOOL_ADMIN,
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_TEACHER,
        ];

        // Create or get 'create user' Permission
        $permission = Permission::createOrFirst(['name' => 'create user']);
        foreach ($roles as $roleName) {
            $role = Role::createOrFirst(['name' => $roleName]);
            // Add 'create user' permission to roles except Photo Coordinator and Teacher
            if (!in_array($roleName, [RoleHelper::ROLE_TEACHER]) && !$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
