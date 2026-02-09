<?php

namespace Database\Seeders;

use App\Helpers\PermissionHelper as PH;
use App\Helpers\RoleHelper;
use App\Helpers\UserAbilitiesHelper;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the default permissions
     */
    public function run(): void
    {
        $defaultPermissions = array_merge(
            (array) PH::toPermission(PH::ACT_CREATE, PH::SUB_USER),
            UserAbilitiesHelper::getDefaultPageAccessPermissions(),
            UserAbilitiesHelper::getDefaultUserInvitePermissions(),
            UserAbilitiesHelper::getDefaultUserDisablePermissions(),
            UserAbilitiesHelper::getDefaultUserRevokePermissions(),
            UserAbilitiesHelper::getDefaultUserImpersonationPermissions(),
            UserAbilitiesHelper::getDefaultEditPermissions(),
        );

        // Create Permissions
        foreach ($defaultPermissions as $permission) {
            Permission::createOrFirst(['name' => $permission]);
        }

        // Setup default permissions based on documentation
        $adminAndFranchise = [
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_SCHOOL_ADMIN),
        ];
        $coordinatorAndTeacher = [
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_TEACHER),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_TEACHER),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_TEACHER),
        ];
        $adminToolsAccess = [
            PH::getAccessToPage(PH::SUB_ADMIN_TOOLS),
            PH::getAccessToPage(PH::SUB_REPORTS),
        ];

        // Prepare permissions per role:
        $rolePermissions = [
            RoleHelper::ROLE_SUPER_ADMIN => array_merge(
                [
                    PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_SUPER_ADMIN),
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_SUPER_ADMIN),
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_SCHOOL_ADMIN),
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_PHOTO_COORDINATOR),
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_TEACHER),
                ],
                $adminToolsAccess,
                $adminAndFranchise,
            ),
            RoleHelper::ROLE_ADMIN => array_merge(
                [
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_SCHOOL_ADMIN),
                    PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_PHOTO_COORDINATOR),
                    PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_PHOTO_COORDINATOR),
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_TEACHER),
                ],
                $adminToolsAccess,
                $adminAndFranchise,
            ),
            RoleHelper::ROLE_FRANCHISE => array_merge(
                [
                    PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                    PH::getAccessToPage(PH::SUB_PROOFING),
                    PH::getAccessToPage(PH::SUB_CONFIG_PROOFING),
                    PH::getAccessToPage(PH::SUB_MANGE_INVITATION),
                    PH::getAccessToPage(PH::SUB_PROOF_CHANGE),
                    PH::getAccessToPage(PH::SUB_BULK_UPLOAD),
                    PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_SCHOOL_ADMIN),
                    PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_SCHOOL_ADMIN),
                    PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_SCHOOL_ADMIN),
                ],
                $adminToolsAccess,
                $coordinatorAndTeacher,
            ),
            RoleHelper::ROLE_SCHOOL_ADMIN => array_merge(
                [
                    PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                    PH::getAccessToPage(PH::SUB_ORDERING),
                    PH::getAccessToPage(PH::SUB_ADMIN_TOOLS),
                ],
                $coordinatorAndTeacher,
            ),
            RoleHelper::ROLE_PHOTO_COORDINATOR => array_merge(
                [
                    PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                    PH::getAccessToPage(PH::SUB_PROOFING),
                    PH::getAccessToPage(PH::SUB_MANGE_INVITATION),
                    PH::getAccessToPage(PH::SUB_PROOF_CHANGE),
                    PH::getAccessToPage(PH::SUB_ADMIN_TOOLS),
                ],
                $coordinatorAndTeacher,
            ),
            RoleHelper::ROLE_TEACHER => [
                PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                PH::getAccessToPage(PH::SUB_PROOFING),
            ],
        ];

        // Apply permissions per role
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::createOrFirst(['name' => $roleName]);
            foreach($permissions as $permission) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
