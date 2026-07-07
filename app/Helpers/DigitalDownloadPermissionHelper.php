<?php

namespace App\Helpers;

use App\Models\School;
use App\Models\User;

class DigitalDownloadPermissionHelper
{
    public const FIELD_PORTRAIT = 'download_portrait';
    public const FIELD_GROUP = 'download_group';
    public const FIELD_OTHER = 'download_schoolPhoto';

    private const ROLE_VALUE_MAP = [
        RoleHelper::ROLE_PHOTO_COORDINATOR => 'photocoordinator',
        RoleHelper::ROLE_SCHOOL_ADMIN => 'schooladmin',
        RoleHelper::ROLE_TEACHER => 'teacher',
    ];

    public static function canViewAndDownload(?User $user, ?School $school, string $field): bool
    {
        if (!$user || !$school) {
            return false;
        }

        if ($user->isFranchiseLevel() || $user->isRcUser() || $user->isAdmin()) {
            return true;
        }

        $roleKey = self::getRoleKey($user);
        if ($roleKey === null) {
            return false;
        }

        $permissions = self::getPermissionsMatrix($school);
        if ($permissions === null) {
            return false;
        }

        return ($permissions['digital_download_permission'][$field][$roleKey] ?? false) === true;
    }

    public static function canViewAndDownloadTab(?User $user, ?School $school, string $tab): bool
    {
        $field = match (strtoupper($tab)) {
            PhotographyHelper::TAB_PORTRAITS => self::FIELD_PORTRAIT,
            PhotographyHelper::TAB_GROUPS => self::FIELD_GROUP,
            PhotographyHelper::TAB_OTHERS => self::FIELD_OTHER,
            default => null,
        };

        if ($field === null) {
            return false;
        }

        return self::canViewAndDownload($user, $school, $field);
    }

    private static function getRoleKey(User $user): ?string
    {
        $roleName = $user->getRole();

        return self::ROLE_VALUE_MAP[$roleName] ?? null;
    }

    private static function getPermissionsMatrix(School $school): ?array
    {
        if (!$school->digital_download_permission_notification) {
            return null;
        }

        $decoded = json_decode($school->digital_download_permission_notification, true);
        if (!is_array($decoded) || !isset($decoded['digital_download_permission'])) {
            return null;
        }

        return $decoded;
    }
}
