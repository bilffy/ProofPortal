<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInviteToken extends Model
{
    use HasFactory;
    
    protected $table = 'user_invite_tokens';

    protected $fillable = [
        'user_id',
        'token',
    ];
    
    // Define the relationship with users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}