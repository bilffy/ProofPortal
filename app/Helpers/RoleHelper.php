<?php

namespace App\Helpers;

use Spatie\Permission\Models\Role;

class RoleHelper
{
    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_FRANCHISE = 'Franchise';
    public const ROLE_PHOTO_COORDINATOR = 'Photo Coordinator';
    public const ROLE_SCHOOL_ADMIN = 'School Admin';
    public const ROLE_TEACHER = 'Teacher';

    /**
     * Get allowed role names based on the given role.
     *
     * @param string $role
     * @return array
     */
    public static function getAllowedRoleNames(string $role): array
    {
        $allowedRoles = [];
        switch ($role) {
            case self::ROLE_SUPER_ADMIN:
                $allowedRoles = [
                    self::ROLE_SUPER_ADMIN,
                    self::ROLE_ADMIN,
                    self::ROLE_FRANCHISE,
                ];
                break;
            case self::ROLE_ADMIN:
                $allowedRoles = [
                    self::ROLE_ADMIN,
                    self::ROLE_FRANCHISE,
                ];
                break;
            case self::ROLE_FRANCHISE:
                $allowedRoles = [
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                ];
                break;
            case self::ROLE_SCHOOL_ADMIN:
                $allowedRoles = [
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            case self::ROLE_PHOTO_COORDINATOR:
                $allowedRoles = [
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            default:
                $allowedRoles = [];
        }

        return $allowedRoles;
    }

    public static function getRoleNamesForFilter(string $role): array
    {
        $allowedRoles = [];
        switch ($role) {
            case self::ROLE_FRANCHISE:
                $allowedRoles = [
                    self::ROLE_FRANCHISE,
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            case self::ROLE_SCHOOL_ADMIN:
            case self::ROLE_PHOTO_COORDINATOR:
                $allowedRoles = [
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            default:
                $allowedRoles = [
                    self::ROLE_SUPER_ADMIN,
                    self::ROLE_ADMIN,
                    self::ROLE_FRANCHISE,
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
        }

        return $allowedRoles;
    }

    /**
     * Get allowed roles based on the given role.
     *
     * @param string $role
     * @return array
     */
    public static function getAllowedRoles(string $role): array
    {
        $roleNames = self::getAllowedRoleNames($role);
        return self::getRolesByNames($roleNames);
    }

    public static function getAllRoles(): array
    {
        return Role::orderBy('id')->get()->all();
    }

    public static function getRolesByNames(array $roleNames): array
    {
        return Role::whereIn('name', $roleNames)->orderBy('id')->get()->all();
    }
}