<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\RoleHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;
use Auth;
use Vinkla\Hashids\Facades\Hashids;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Impersonate, HasApiTokens;

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

    public function downloadRequested()
    {
        return $this->belongsToMany(DownloadRequested::class, 'download_requested');
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

    public function isSuperAdmin()
    {
        return $this->hasRole([RoleHelper::ROLE_SUPER_ADMIN]);
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

    public function isSchoolAdmin()
    {
        return $this->hasRole([RoleHelper::ROLE_SCHOOL_ADMIN]);
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
        // Redundancy: Added get Franchise from School process to avoid null from School level users
        if ($this->isSchoolLevel()) {
            /** @var School $school */
            $school = $this->getSchool();
            return $school->franchises()->first();
        }
        return $this->franchises()->first();
    }

    public function getSchool()
    {
        return $this->schools()->first();
    }

    public function getSchoolOrFranchise($withAdmin = false)
    {
        return $this->isAdmin()
            ? ( $withAdmin ? Franchise::getMSP()?->name : "" )
            : ( $this->isFranchiseLevel() ? $this->getFranchise()?->name : $this->getSchool()?->name );
    }

    public function getOrganization()
    {
        if ($this->isAdmin()) {
            return Franchise::getMSP();
        }
        return $this->getFranchise();
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

    /**
     * @return bool
     */
    public function canImpersonate(): bool
    {   
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isRcUser()) {
            return true;
        }
        
        if ($this->isFranchiseLevel()) {
            return true;
        }

        if ($this->isSchoolAdmin()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the Hashed ID attribute.
     */
    public function getHashedIdAttribute()
    {
        // return Hashids::encodeHex("$this->id");
        return $this->id;
    }
    
    /**
     * @return bool
     */
//    public function canBeImpersonated(): bool
//    {
//        return true;
//
//        if (Auth::user()->id == $this->id) {
//            return false;
//        }
//        
//        return true;
//    }

}
