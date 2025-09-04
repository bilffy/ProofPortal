<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;
    
    protected $table = 'user_otps';

    protected $fillable = [
        'user_id',
        'otp',
        'expire_on',
        'otp_attempts',
        'resend_attempts',
        'last_resend_at',
    ];
    
    // Define the relationship with users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}