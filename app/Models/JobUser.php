<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
									   

class JobUser extends Pivot
{
	
    use HasFactory;

    protected $table = 'job_users';

    protected $fillable = [
        'user_id',
        'ts_job_id',
    ];

    // If your table has timestamps, keep this true
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'ts_job_id', 'ts_job_id');
    }
}




