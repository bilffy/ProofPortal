<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'school_logo',
        'description',
        'schoolkey',
        'address',
        'postcode',
        'suburb',
        'state',
        'country',
        'status_id',
    ];

    /**
     * Get the school details associated with the school.
     */
    public function details()
    {
        return $this->hasOne(SchoolDetail::class, 'school_id');
    }

    /**
     * Get the users associated with the school.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'school_users');
        // return $this->hasMany(SchoolUser::class, 'school_id');
    }

    /**
     * Get the franchises associated with the school.
     */
    public function franchises()
    {
        return $this->belongsToMany(Franchise::class, 'school_franchises');
        // return $this->hasMany(SchoolFranchise::class, 'school_id');
    }
}