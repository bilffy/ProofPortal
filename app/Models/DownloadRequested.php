<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadRequested extends Model
{
    use HasFactory;

    protected $table = 'download_requested';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'download_type_id',
        'download_category_id',
        'requested_date',
        'completed_date',
    ];

    public function downloadDetails()
    {
        return $this->belongsToMany(DownloadDetail::class, 'download_details');
    }

    public function downloadType(){
        return $this->belongsTo(DownloadType::class, 'download_type_id');
    }

    public function downloadCategory(){
        return $this->belongsTo(DownloadCategory::class, 'download_category_id');
    }
}
