<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToFranchise;

class ProofingChangelog extends Model
{
    use HasFactory, BelongsToFranchise;

    protected $table = "changelogs";
    
    protected $fillable = ['ts_jobkey', 'keyvalue', 'keyorigin', 'change_from', 'change_to', 'notes', 'resolved_status_id', 'issue_id', 'change_datetime', 'decision_datetime', 'approvalStatus', 'user_id'];

    public function issue(){
        return $this->belongsTo('App\Models\ProofingIssue', 'issue_id', 'id');
    }

    public function statuses(){
        return $this->belongsTo('App\Models\Status', 'approvalStatus', 'id');
    }

    public function job(){
        return $this->belongsTo('App\Models\Job', 'ts_jobkey', 'ts_jobkey');
    }

    public function folder(){
        return $this->belongsTo('App\Models\Folder', 'keyvalue', 'ts_folderkey'); 
    }

    public function subject(){
        return $this->belongsTo('App\Models\Subject', 'keyvalue', 'ts_subjectkey'); 
    }

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id', 'id'); 
    }
    
}
