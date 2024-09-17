<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Franchise extends Model
{
    use HasFactory;

    protected $table = 'franchises';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ts_account_id',
        'alphacode',
        'name',
        'address',
        'postcode',
        'suburb',
        'state',
        'country',
        'status_id',
    ];
    
    /**
     * Get the users associated with the franchise.
     */
    public function users()
    {
        return $this->hasMany(FranchiseUser::class, 'franchise_id');
    }

    /**
     * Get the schools associated with the franchise.
     */
    public function schools()
    {
        return $this->hasMany(SchoolFranchise::class, 'franchise_id');
    }
}
