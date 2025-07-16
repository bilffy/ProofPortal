<?php

namespace App\Helpers;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Auth;
use Vinkla\Hashids\Facades\Hashids;

class PermissionHelper
{
    // Abilities
    public const ACT_ACCESS= 'access';
    public const ACT_CREATE = 'create';
    public const ACT_DISABLE = 'disable';
    public const ACT_IMPERSONATE = 'impersonate';
    public const ACT_INVITE = 'invite';
    public const ACT_REVOKE = 'revoke';

    // Roles
    public const SUB_USER = 'user';
    // Pages
    public const SUB_ADMIN_TOOLS = 'admin_tools';
    public const SUB_ORDERING = 'ordering';
    public const SUB_PHOTOGRAPHY = 'photography';
    public const SUB_PROOFING = 'proofing';
    public const SUB_REPORTS = 'reports';

    private static function normalizeSubject(string $subject): string
    {
        return strtolower(str_replace(' ', '_', $subject));
    }

    /**
     * Build Permission name by action + subject
     *
     * @param string $action
     * @param string $subject
     * @return string
     */
    public static function toPermission(string $action, string $subject): string
    {
        return $action . " " . self::normalizeSubject($subject);
    }

    /**
     * get Permission object from action and subject
     *
     * @param string $action
     * @param string $subject
     * @return Permission
     */
    public static function getPermission(string $action, string $subject): Permission
    {
        return Permission::createOrFirst(['name' => self::toPermission($action, $subject)]);
    }

    /**
     * get Permission object from name
     *
     * @param string $permissionName
     * @return Permission
     */
    public static function getPermissionByName(string $permissionName): Permission
    {
        return Permission::createOrFirst(['name' => $permissionName]);
    }

    /**
     * Shortcut method to get permissions for 'access' actions
     *
     * @param string $page
     * 
     * @return string
     */
    public static function getAccessToPage($page)
    {
        return self::toPermission(self::ACT_ACCESS, $page);
    }

    /**
     * @return bool
     */
    public static function canImpersonate(string $userId): bool
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // $userId = Hashids::decodeHex($userId);
        
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }
        
        if ($user->status != User::STATUS_ACTIVE) {
            return false;
        }
        
        if ($currentUser->id == $user->id) {
            return false;
        }
        
        if ($currentUser->isSuperAdmin()) {
            if ($user->isSuperAdmin()) {
                return false;
            }
            return true;
        }
    
        if ($currentUser->isRcUser()) {
            if ($user->isSuperAdmin() || $user->isRcUser()) {
                return false;
            }
            return true;
        }

        if ($currentUser->isFranchiseLevel()) {
            if ($user->isFranchiseLevel()) {
                return false;
            }
            return true;
        }

        if ($currentUser->isSchoolAdmin()) {
            if ($user->isSchoolAdmin()) {
                return false;
            }
            return true;
        }

        return false;
    }
}
