<?php

namespace App\Models;

use App\Helpers\FilenameFormatHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'title',
        'salutation',
        'ts_subjectkey',
        'ts_job_id',
        'ts_folder_id',
        'ts_subject_id',
        'is_locked',
    ];

    public function folder(){
        return $this->belongsTo('App\Models\Folder', 'ts_folder_id', 'ts_folder_id');
    }

    public function job(){
        return $this->belongsTo('App\Models\Job', 'ts_job_id', 'ts_job_id');
    }

    public function images(){
        return $this->hasOne('App\Models\Image', 'keyvalue', 'ts_subjectkey');
    }

    public function attachedsubjects(){
        return $this->hasMany('App\Models\FolderSubject', 'ts_subject_id', 'ts_subject_id');
    }

    public function getFilename(string $format): string
    {
        $options = [
            'subjects' => $this->id,
            'folders' => $this->folder->id,
        ];
        return FilenameFormatHelper::applyFormat($format, $options);
    }
}
