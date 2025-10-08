<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageOptions extends Model
{
    use HasFactory;
    protected $table = "image_options";

    protected $fillable = [
        'display_name',
        'file_format',
        'long_edge',
        'image_use',
    ];
    
}
