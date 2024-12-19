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
        'name',
        'ts_image_id',
        'ts_imagekey',
        'ts_job_id',
        'keyvalue',
        'keyorigin',
        'protected',
    ];
    
    //Subjects Table
    public function subjects(){
        return $this->belongsTo('App\Models\Subject', 'keyvalue', 'ts_subject_id');
    }
    //Folders Table
    public function folders(){
        return $this->belongsTo('App\Models\Folder', 'keyvalue', 'ts_folder_id');
    }
    //Jobs Table
    public function jobs(){
        return $this->belongsTo('App\Models\Job', 'ts_job_id', 'ts_job_id');
    }
}
