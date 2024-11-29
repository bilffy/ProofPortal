<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $table = 'images';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ts_image_id',
        'ts_imagekey',
        'ts_job_id',
        'keyvalue',
        'keyorigin',
        'image_type_id',
        'protected',
    ];
}
