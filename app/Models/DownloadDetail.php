<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadDetail extends Model
{
    use HasFactory;
    protected $table = "download_details";

    protected $fillable = [
        'download_id',
        'ts_jobkey',
        'keyorigin', 
        'keyvalue',
    ];

    public function downloadRequested(){
        return $this->belongsTo(DownloadRequested::class, 'download_id');
    }
}
