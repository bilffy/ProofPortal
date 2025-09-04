<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseUser extends Model
{
    use HasFactory;
    
    protected $table = 'franchise_users';

    protected $fillable = [
        'user_id',
        'franchise_id',
    ];
    
    // Define the relationship with users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship with franchises
    public function franchise()
    {
        return $this->belongsTo(Franchise::class, 'franchise_id');
    }
}