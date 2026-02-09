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

    // public function images(){
    //     return $this->hasOne('App\Models\Image', 'keyvalue', 'ts_subjectkey')->select('ts_imagekey');
    // }

    public function images()
    {
        return $this->hasOneThrough(
            'App\Models\Image',   // Final related model
            'App\Models\Subject', // Intermediate model
            'ts_subject_id',      // Foreign key on the Subject table (to FolderSubject)
            'keyvalue',           // Foreign key on the Image table (to Subject)
            'ts_subject_id',      // Local key on the FolderSubject table
            'ts_subjectkey'      // Local key on the Subject table
        )
        ->select('ts_imagekey', 'keyvalue', 'is_primary') // Added is_primary for the order to work
        ->orderBy('is_primary', 'desc'); 
    }
}
