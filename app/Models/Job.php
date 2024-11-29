<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $table = 'jobs';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ts_season_id',
        'ts_account_id',
        'ts_job_id',
        'ts_jobkey',
        'ts_jobname',
        'ts_schoolkey',
        'jobsync_status_id',
        'foldersync_status_id',
        'job_status_id',
        'proof_start',
        'proof_warning',
        'proof_due',
        'force_catchup',
        'download_available_date',
        'download_available_date',
        'notifications_enabled',
        'notifications_matrix',
    ];

    public function jobSyncStatus()
    {
        return $this->belongsTo(Status::class, 'jobsync_status_id');
    }

    public function folderSyncStatus()
    {
        return $this->belongsTo(Status::class, 'foldersync_status_id');
    }

    public function jobStatus()
    {
        return $this->belongsTo(Status::class, 'job_status_id');
    }
}
