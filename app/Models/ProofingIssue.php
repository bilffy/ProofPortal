<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProofingIssue extends Model
{
    use HasFactory;

    protected $table = "issues";
    
    protected $fillable = ['issue_description', 'issue_category_id'];

    public function issuecategory(){
        return $this->belongsTo('App\Models\ProofingIssueCategory', 'issue_category_id', 'id');
    }

    public function changes(){
        return $this->hasMany('App\Models\ProofingChangelog', 'issue_id', 'id');
    }
}
