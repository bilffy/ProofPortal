<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToFranchise;

class Image extends Model
{
    use HasFactory, BelongsToFranchise;

    protected $table = 'images';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'is_primary',
        'ts_image_id',
        'ts_imagekey',
        'ts_job_id',
        'keyvalue',
        'keyorigin',
        'protected',
        'portal_subject_id'
    ];
    
    //Subjects Table
    public function subjects(){
        return $this->belongsTo('App\Models\Subject', 'keyvalue', 'ts_subjectkey');
    }
    //Folders Table
    public function folders(){
        return $this->belongsTo('App\Models\Folder', 'keyvalue', 'ts_folderkey');
    }
    //Jobs Table
    public function jobs(){
        return $this->belongsTo('App\Models\Job', 'ts_job_id', 'ts_job_id');
    }
}
