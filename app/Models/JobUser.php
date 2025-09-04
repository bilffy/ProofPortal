<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobUser extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ts_job_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'ts_job_id', 'ts_job_id');
    }
}




