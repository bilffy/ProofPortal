<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Crypt;

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
    
    /**
     * Get the Hashed ID attribute.
     */
    public function getHashedIdAttribute()
    {   
        // return Hashids::encodeHex("$this->id");
        return $this->id;
    }   
    //code by IT
    public function getCryptedIdAttribute()
    {    
        return Crypt::encryptString($this->id);
    }
    //code by IT
    //Proofing    
    public function scopeWithFranchise($query, $franchiseCode){
        return $query->join('school_franchises', 'school_franchises.school_id', '=', 'schools.id')
        ->join('franchises', 'franchises.id', '=', 'school_franchises.franchise_id')
        ->select('schools.name', 'schools.schoolkey', 'schools.id', 'schools.address', 'schools.postcode', 'schools.suburb', 'schools.country')
        ->where('franchises.alphacode', $franchiseCode);
    }
    
}