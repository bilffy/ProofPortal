<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class SchoolPhotoUpload extends Model
{
    protected $table = 'school_photo_uploads';

    protected $fillable = [
        'subject_id',
        'folder_id',
        'image_id',
        'metadata',
        'deleted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];
}