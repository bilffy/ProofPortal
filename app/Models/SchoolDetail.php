<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'photoday',
        'catchup_date',
        'digitalDownload_date',
        'principal',
        'ts_season_id',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}