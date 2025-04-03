<?php

namespace App\Models;

use App\Helpers\FilenameFormatHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $table = 'folders';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ts_folder_id',
        'ts_folderkey',
        'ts_foldername',
        'ts_job_id',
        'folder_tag',
        'status_id',
        'teacher',
        'principal',
        'deputy',
        'is_edit_portraits',
        'is_edit_groups',
        'is_edit_job_title',
        'is_edit_salutation',
        'is_locked',
        'is_visible_for_proofing',
        'is_visible_for_portrait',
        'is_visible_for_group',
        'is_subject_list_allowed',
        'is_edit_principal',
        'is_edit_deputy',
        'is_edit_teacher',
    ];

    public function folderTag()
    {
        return $this->belongsTo(FolderTag::class, 'folder_tag');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function subjects(){
        return $this->hasMany('App\Models\Subject', 'ts_folder_id', 'ts_folder_id');
    }

    public function attachedsubjects(){
        return $this->hasMany('App\Models\FolderSubject', 'ts_folder_id', 'ts_folder_id');
    }

    public function job(){
        return $this->belongsTo('App\Models\Job', 'ts_job_id', 'ts_job_id');
    }

    public function images(){
        return $this->hasMany('App\Models\Image', 'keyvalue', 'ts_folderkey');
    }

    public function folderTags(){
        return $this->belongsTo('App\Models\FolderTag', 'folder_tag', 'tag');
    }

    public function getFilename(string $format): string
    {
        $options = [
            'folders' => $this->id,
            'seasons' => $this->job()->first()->seasons()->first()->id,
        ];
        return FilenameFormatHelper::applyFormat($format, $options);
    }
}
