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
            // PH::getAccessToPage(PH::SUB_ORDERING),
            PH::getAccessToPage(PH::SUB_PHOTOGRAPHY),
            PH::getAccessToPage(PH::SUB_PROOFING),
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
}
