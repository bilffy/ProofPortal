<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_FRANCHISE = 'Franchise';
    public const ROLE_PHOTO_COORDINATOR = 'Photo Coordinator';
    public const ROLE_SCHOOL_ADMIN = 'School Admin';
    public const ROLE_TEACHER = 'Teacher';
    
    protected $table = 'roles';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'notes',
    ];

    public static function getAllowedRoles($role)
    {
        $allowedRoles = [];
        switch ($role) {
            case self::ROLE_SUPER_ADMIN:
                $allowedRoles = [
                    self::ROLE_SUPER_ADMIN,
                    self::ROLE_ADMIN,
                    self::ROLE_FRANCHISE,
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            case self::ROLE_ADMIN:
                $allowedRoles = [
                    self::ROLE_ADMIN,
                    self::ROLE_FRANCHISE,
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            case self::ROLE_FRANCHISE:
                $allowedRoles = [
                    self::ROLE_FRANCHISE,
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            case self::ROLE_SCHOOL_ADMIN:
                $allowedRoles = [
                    self::ROLE_SCHOOL_ADMIN,
                    self::ROLE_PHOTO_COORDINATOR,
                    self::ROLE_TEACHER,
                ];
                break;
            default:
                $allowedRoles = [];
        }

        return Role::whereIn('name', $allowedRoles)->get()->all();
    }
    
    /**
     * Get the users associated with the role.
     */
    public function users()
    {
        return $this->hasMany(UserRole::class, 'role_id');
    }
    
    /**
     * Get the permissions associated with the role.
     */
    public function permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }
}