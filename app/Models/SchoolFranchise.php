<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolFranchise extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'school_id',
    ];

    public function franchise()
    {
        return $this->belongsTo(Franchise::class, 'franchise_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}