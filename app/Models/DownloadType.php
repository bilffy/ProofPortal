<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadType extends Model
{
    use HasFactory;
    protected $table = "download_type";

    protected $fillable = [
        'download_type',
    ];
}
