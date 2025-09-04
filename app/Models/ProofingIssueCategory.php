<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProofingIssueCategory extends Model
{
    use HasFactory;
    
    protected $table = "issue_categories";
    
    protected $fillable = ['issue_category_id', 'category_name'];

    public function issue(){
        return $this->hasMany('App\Models\ProofingIssue', 'issue_category_id', 'id');
    }
}
