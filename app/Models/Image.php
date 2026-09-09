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
        'image_path',
        'is_primary',
        'ts_image_id',
        'ts_imagekey',
        'ts_job_id',
        'keyvalue',
        'keyorigin',
        'protected',
        'portal_subject_id',
        'is_deleted',
    ];

    /**
     * Active (non-deleted) image rows. Treats NULL as not deleted.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0)->orWhereNull('is_deleted');
    }
    
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
