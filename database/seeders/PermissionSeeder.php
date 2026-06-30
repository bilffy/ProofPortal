<?php

namespace Database\Seeders;

use App\Helpers\UserAbilitiesHelper;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Seed default permissions and role_has_permissions associations.
     */
    public function run(): void
    {
        foreach (UserAbilitiesHelper::getAllDefaultPermissionNames() as $permission) {
            Permission::createOrFirst(['name' => $permission]);
        }

        foreach (UserAbilitiesHelper::getDefaultRolePermissions() as $roleName => $permissions) {
            $role = Role::createOrFirst(['name' => $roleName]);
            foreach ($permissions as $permission) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
