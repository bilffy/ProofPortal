<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolderSubject extends Model
{
    use HasFactory;
    protected $table = "folder_subjects";

    protected $fillable = ['ts_folder_id','ts_subject_id'];

    public function subject(){
        return $this->belongsTo('App\Models\Subject', 'ts_subject_id', 'ts_subject_id');
    }

    public function folder(){
        return $this->belongsTo('App\Models\Folder', 'ts_folder_id', 'ts_folder_id');
    }

    public function images(){
        return $this->hasOne('App\Models\Image', 'keyvalue', 'ts_subject_id')->select('ts_imagekey');
    }
}
