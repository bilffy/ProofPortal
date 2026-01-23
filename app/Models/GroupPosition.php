<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToFranchise;

class GroupPosition extends Model
{
    use HasFactory, BelongsToFranchise;
    protected $table = "group_positions";
    protected $fillable = ['ts_jobkey', 'ts_folderkey', 'ts_subjectkey', 'subject_full_name', 'row_description', 'row_number', 'row_position'];
    public $timestamps = false;

    public function subject(){
        return $this->belongsTo('App\Models\Subject', 'ts_subjectkey', 'ts_subjectkey');
    }

    public function folder(){
        return $this->belongsTo('App\Models\Folder', 'ts_folderkey', 'ts_folderkey');
    }

    public function job(){
        return $this->belongsTo('App\Models\Job', 'ts_jobkey', 'ts_jobkey');
    }
}
