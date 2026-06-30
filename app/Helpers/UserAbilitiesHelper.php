<?php

namespace App\Helpers;

use App\Helpers\PermissionHelper as PH;
use Spatie\Permission\Models\Role;

class UserAbilitiesHelper
{
        
    public static function getDefaultPageAccessPermissions()
    {
        return [
            PH::getAccessToPage(PH::SUB_ADMIN_TOOLS),
            PH::getAccessToPage(PH::SUB_ORDERING),
            PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
            PH::getAccessToPage(PH::SUB_PROOFING),
            PH::getAccessToPage(PH::SUB_CONFIG_PROOFING),
            PH::getAccessToPage(PH::SUB_MANGE_INVITATION),
            PH::getAccessToPage(PH::SUB_PROOF_CHANGE),
            PH::getAccessToPage(PH::SUB_BULK_UPLOAD),
            PH::getAccessToPage(PH::SUB_REPORTS),
        ];
    }
    
    public static function getDefaultUserInvitePermissions()
    {
        return [
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_SUPER_ADMIN),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_SCHOOL_ADMIN),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_INVITE, RoleHelper::ROLE_TEACHER),
        ];
    }

    public static function getDefaultUserDisablePermissions()
    {
        return [
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_SUPER_ADMIN),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_SCHOOL_ADMIN),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_DISABLE, RoleHelper::ROLE_TEACHER),
        ];
    }

    public static function getDefaultUserRevokePermissions()
    {
        return [
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_SCHOOL_ADMIN),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_REVOKE, RoleHelper::ROLE_TEACHER),
        ];
    }

    public static function getDefaultUserImpersonationPermissions()
    {
        return [
            PH::toPermission(PH::ACT_IMPERSONATE, RoleHelper::ROLE_SUPER_ADMIN),
            PH::toPermission(PH::ACT_IMPERSONATE, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_IMPERSONATE, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_IMPERSONATE, RoleHelper::ROLE_SCHOOL_ADMIN),
            PH::toPermission(PH::ACT_IMPERSONATE, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_IMPERSONATE, RoleHelper::ROLE_TEACHER),
        ];
    }

    public static function removeRolePermission(string $roleName, string $permissionString)
    {
        $role = Role::findByName($roleName);
        $role->revokePermissionTo($permissionString);
    }

    public static function getDefaultEditPermissions()
    {
        return [
            PH::toPermission(PH::ACT_EDIT, RoleHelper::ROLE_SUPER_ADMIN),
            PH::toPermission(PH::ACT_EDIT, RoleHelper::ROLE_ADMIN),
            PH::toPermission(PH::ACT_EDIT, RoleHelper::ROLE_FRANCHISE),
            PH::toPermission(PH::ACT_EDIT, RoleHelper::ROLE_SCHOOL_ADMIN),
            PH::toPermission(PH::ACT_EDIT, RoleHelper::ROLE_PHOTO_COORDINATOR),
            PH::toPermission(PH::ACT_EDIT, RoleHelper::ROLE_TEACHER),
        ];
    }

    /**
     * Default role_has_permissions matrix (permissions each role is granted).
     * Mirrors Settings → Role Permissions downstream rows and production defaults.
     */
    public static function getDefaultRolePermissions(): array
    {
        $invite = fn (array $targets) => self::actionPermissions(PH::ACT_INVITE, $targets);
        $disable = fn (array $targets) => self::actionPermissions(PH::ACT_DISABLE, $targets);
        $revoke = fn (array $targets) => self::actionPermissions(PH::ACT_REVOKE, $targets);
        $impersonate = fn (array $targets) => self::actionPermissions(PH::ACT_IMPERSONATE, $targets);
        $edit = fn (array $targets) => self::actionPermissions(PH::ACT_EDIT, $targets);

        $allRoles = [
            RoleHelper::ROLE_SUPER_ADMIN,
            RoleHelper::ROLE_ADMIN,
            RoleHelper::ROLE_FRANCHISE,
            RoleHelper::ROLE_SCHOOL_ADMIN,
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_TEACHER,
        ];

        // Super Admin UI downstream (Admin role hidden): Super Admin + Franchise + School + PC + Teacher
        $superAdminDownstream = [
            RoleHelper::ROLE_SUPER_ADMIN,
            RoleHelper::ROLE_FRANCHISE,
            RoleHelper::ROLE_SCHOOL_ADMIN,
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_TEACHER,
        ];

        $franchiseDownstream = [
            RoleHelper::ROLE_SCHOOL_ADMIN,
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_TEACHER,
        ];

        $schoolDownstream = [
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_TEACHER,
        ];

        $createUser = PH::toPermission(PH::ACT_CREATE, PH::SUB_USER);
        $adminTools = PH::getAccessToPage(PH::SUB_ADMIN_TOOLS);
        $reports = PH::getAccessToPage(PH::SUB_REPORTS);

        return [
            RoleHelper::ROLE_SUPER_ADMIN => array_merge(
                [$createUser, $adminTools, $reports],
                $invite(array_merge([RoleHelper::ROLE_ADMIN], $allRoles)),
                $disable(array_merge([RoleHelper::ROLE_ADMIN], $allRoles)),
                $revoke([RoleHelper::ROLE_FRANCHISE, RoleHelper::ROLE_SCHOOL_ADMIN]),
                $impersonate($superAdminDownstream),
                $edit($superAdminDownstream),
            ),
            RoleHelper::ROLE_ADMIN => array_merge(
                [$createUser, $adminTools, $reports],
                $invite([RoleHelper::ROLE_ADMIN, RoleHelper::ROLE_FRANCHISE, RoleHelper::ROLE_PHOTO_COORDINATOR]),
                $disable([RoleHelper::ROLE_ADMIN, RoleHelper::ROLE_FRANCHISE, RoleHelper::ROLE_SCHOOL_ADMIN, RoleHelper::ROLE_TEACHER]),
                $revoke([RoleHelper::ROLE_ADMIN, RoleHelper::ROLE_FRANCHISE, RoleHelper::ROLE_SCHOOL_ADMIN, RoleHelper::ROLE_PHOTO_COORDINATOR]),
                $impersonate([RoleHelper::ROLE_ADMIN]),
                $edit([RoleHelper::ROLE_ADMIN]),
            ),
            RoleHelper::ROLE_FRANCHISE => array_merge(
                [$createUser, $adminTools, $reports],
                [
                    PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                    PH::getAccessToPage(PH::SUB_PROOFING),
                    PH::getAccessToPage(PH::SUB_CONFIG_PROOFING),
                    PH::getAccessToPage(PH::SUB_MANGE_INVITATION),
                    PH::getAccessToPage(PH::SUB_PROOF_CHANGE),
                    PH::getAccessToPage(PH::SUB_BULK_UPLOAD),
                ],
                $invite($franchiseDownstream),
                $disable($franchiseDownstream),
                $revoke($franchiseDownstream),
                $impersonate($franchiseDownstream),
                $edit($franchiseDownstream),
            ),
            RoleHelper::ROLE_SCHOOL_ADMIN => array_merge(
                [$createUser, $adminTools],
                [
                    PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                    PH::getAccessToPage(PH::SUB_ORDERING),
                ],
                $invite($schoolDownstream),
                $disable($schoolDownstream),
                $revoke(array_merge([RoleHelper::ROLE_SCHOOL_ADMIN], $schoolDownstream)),
                $impersonate($schoolDownstream),
                $edit($schoolDownstream),
            ),
            RoleHelper::ROLE_PHOTO_COORDINATOR => array_merge(
                [$createUser, $adminTools],
                [
                    PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                    PH::getAccessToPage(PH::SUB_PROOFING),
                    PH::getAccessToPage(PH::SUB_MANGE_INVITATION),
                    PH::getAccessToPage(PH::SUB_PROOF_CHANGE),
                ],
                $invite($schoolDownstream),
                $disable($schoolDownstream),
                $revoke($schoolDownstream),
            ),
            RoleHelper::ROLE_TEACHER => [
                PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
                PH::getAccessToPage(PH::SUB_PROOFING),
            ],
        ];
    }

    /**
     * All permission names that should exist in the permissions table.
     */
    public static function getAllDefaultPermissionNames(): array
    {
        return array_values(array_unique(array_merge(
            (array) PH::toPermission(PH::ACT_CREATE, PH::SUB_USER),
            self::getDefaultPageAccessPermissions(),
            self::getDefaultUserInvitePermissions(),
            self::getDefaultUserDisablePermissions(),
            self::getDefaultUserRevokePermissions(),
            self::getDefaultUserImpersonationPermissions(),
            self::getDefaultEditPermissions(),
        )));
    }

    private static function actionPermissions(string $action, array $targetRoles): array
    {
        return array_map(
            fn (string $targetRole) => PH::toPermission($action, $targetRole),
            $targetRoles
        );
    }
}
