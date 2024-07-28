<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Define the relationship with franchise_users
    public function franchiseUsers()
    {
        return $this->hasMany(FranchiseUser::class, 'user_id');
    }

    // Define the relationship with user_invite_tokens
    public function userInviteTokens()
    {
        return $this->hasMany(UserInviteToken::class, 'user_id');
    }
    
    // Define the relationship with the status table
    public function status()
    {
        return $this->belongsTo(Status::class, 'active_status_id');
    }
    
    // Define the relationship with the user_roles table
    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'firstname',
        'lastname',
        'username',
        'address',
        'suburb',
        'postcode',
        'state',
        'country',
        'contact',
        'active_status_id',
        'activation_date',
        'expiry_date',
        'password_expiry',
        'password_expiry_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activation_date' => 'datetime',
            'expiry_date' => 'datetime',
            'password_expiry_date' => 'datetime',
        ];
    }
}
