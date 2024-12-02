<?php

namespace App\Services;

use App\Models\Job;
use App\Services\StatusService;

class JobService
{
    protected $statusService;

    protected function getFolderService()
    {
        return app(FolderService::class);
    }

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function getActiveSyncJobsBySchoolkey($schoolkey)
    {
        $tnjNotFound = $this->statusService->tnjNotFound;
        return $this->queryJobs(null,$schoolkey)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->whereNotIn('jobs.job_status_id', [$tnjNotFound])
            ->orderBy('id', 'asc')
            ->get();
    } 

    public function getDefaultSeasonJobs($seasonID, $franchiseCode)
    {
        return $this->queryJobs($franchiseCode,null)
            ->where('jobs.ts_season_id', $seasonID)
            ->orderBy('id', 'asc')
            ->get();
    }
    
    public function geJobsByTSJobID($TSJobID)
    {
        return Job::with(['folders.subjects.images','folders.folderTags'])->where('ts_job_id', $TSJobID)->first();
    }

    public function getJobsBySeason($schoolkey, $seasonId)
    {
        return $this->queryJobs(null,$schoolkey)
        ->where('jobs.ts_season_id', $seasonId)
        ->orderBy('id', 'asc')
        ->get();
    }

    public function updateJobData($jobkey, $column, $value){
        return Job::where('ts_jobkey',$jobkey)->update([$column => $value]);
    }

    protected function queryJobs($franchiseCode = null, $schoolkey = null)
    {
        return Job::join('schools', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
        ->join('seasons', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
        ->join('franchises', 'franchises.ts_account_id', '=', 'jobs.ts_account_id')
        ->when($franchiseCode, fn($query) => $query->where('franchises.alphacode', $franchiseCode))
        ->when($schoolkey, fn($query) => $query->where('jobs.ts_schoolkey', $schoolkey))
        ->with(['reviewStatuses', 'folders' => function ($query) {
            $query->select('folders.ts_job_id', 'folders.id', 'folders.is_locked', 'folders.status_id');
        }])     
        ->select(
            'jobs.id',
            'jobs.ts_job_id',
            'jobs.ts_season_id',
            'jobs.ts_jobkey',
            'jobs.ts_jobname',
            'jobs.job_status_id',
            'jobs.proof_start',
            'jobs.proof_warning',
            'jobs.proof_due',
            'jobs.download_available_date',
            'schools.name as school_name',
            'seasons.ts_season_id as season_id',
            'seasons.code as season_code'
        );
    }
}
