<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\RoleHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Impersonate;

    public const STATUS_NEW = 'new';
    public const STATUS_INVITED = 'invited';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';
    
    // Define the relationship with franchise using franchise_users
    public function franchises()
    {
        return $this->belongsToMany(Franchise::class, 'franchise_users');
    }

    // Define the relationship with school using school_users
    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_users');
    }

    // Define the relationship with user_invite_tokens
    public function userInviteTokens()
    {
        return $this->hasMany(UserInviteToken::class, 'user_id');
    }

    // Define the relationship with user_otps
    public function userOtps()
    {
        return $this->hasMany(UserOtp::class, 'user_id');
    }
    
    // Define the relationship with the status table
    public function status()
    {
        return $this->belongsTo(Status::class, 'active_status_id');
    }

    public function uiSettings()
    {
        return $this->hasMany(UiSetting::class, 'user_id');
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
        'is_setup_complete',
        'status',
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

    public function isAdmin()
    {
        return $this->hasRole([RoleHelper::ROLE_SUPER_ADMIN, RoleHelper::ROLE_ADMIN]);
    }

    public function isRcUser()
    {
        return $this->hasRole([RoleHelper::ROLE_ADMIN]);
    }
    
    public function isFranchiseLevel()
    {
        return $this->hasRole([RoleHelper::ROLE_FRANCHISE]);
    }

    public function isSchoolLevel()
    {
        return $this->hasRole([RoleHelper::ROLE_SCHOOL_ADMIN, RoleHelper::ROLE_PHOTO_COORDINATOR, RoleHelper::ROLE_TEACHER]);
    }

    // Get the latest otp
    public function getLatestOtp()
    {
        return $this->userOtps()->latest()->first();
    }

    public function getRole()
    {
        return $this->getRoleNames()->first();
    }
    
    public function getFranchise()
    {
        return $this->franchises()->first();
    }

    public function getSchool()
    {
        return $this->schools()->first();
    }

    public function getSchoolOrFranchise()
    {
        return $this->isAdmin() ? "" : ( $this->isFranchiseLevel() ? $this->getFranchise()?->name : $this->getSchool()?->name );
    }

    public function getInvitableRoles()
    {
        // Todo: Update logic using permissions
        return RoleHelper::getAllowedRoleNames($this->getRole());
    }

    public function getUiSetting()
    {
        return $this->uiSettings()->first();
    }
}
