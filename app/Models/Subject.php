<?php

namespace App\Models;

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
    
}
