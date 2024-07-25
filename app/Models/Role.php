<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    
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