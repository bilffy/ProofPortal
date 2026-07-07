<?php

namespace App\Services\Proofing;

use App\Models\Job;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SchoolService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\EmailService;
use App\Helpers\ActivityLogHelper;
use App\Helpers\SchoolContextHelper;
use App\Helpers\Constants\LogConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JobService
{
    protected $statusService;
    protected $seasonService;
    protected $schoolService;
    protected $emailService;

    protected function getFolderService()
    {
        return app(FolderService::class);
    }

    public function __construct(StatusService $statusService, SchoolService $schoolService, SeasonService $seasonService, EmailService $emailService)
    {
        $this->statusService = $statusService;
        $this->schoolService = $schoolService;
        $this->seasonService = $seasonService;
        $this->emailService = $emailService;
    }

    public function getDashboardData($franchiseCode, $schoolKey = null)
    {
        $school = SchoolContextHelper::getSchool();
        $selectedSchoolkey = $school ? $school->schoolkey : null;
        
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        $activeSyncJobs = $this->getActiveSyncJobs($franchiseCode, $selectedSchoolkey);
        $statuses = $this->statusService->getAllStatusData('id', 'status_internal_name', 'status_external_name')->get();
        $completedStatus = $this->statusService->completed;
        $totalSchoolCount = $this->queryJobs($franchiseCode, $selectedSchoolkey)->whereNotIn('jobs.job_status_id', [$this->statusService->archived, $tnjNotFound, $deleted])
            ->where('job_users.user_id', Auth::user()->id)->count();
        $seasons = $this->seasonService->getAllSeasonData('code', 'show_in_portal', 'is_default', 'ts_season_id')->get();
        $schools = $this->schoolService->franchiseSchools($franchiseCode)->get();
        
        // Check if there is a default season
        $defaultSeason = $seasons->where('show_in_portal', 1)->first();
        $defaultSeasonJobs = [];
        if ($defaultSeason) {
            $defaultSeasonJobs = $this->getDefaultSeasonJobs($defaultSeason->ts_season_id, $franchiseCode);
        }

        $folderStatusCounts = \App\Models\Folder::whereIn('ts_job_id', $activeSyncJobs->pluck('ts_job_id'))
            ->select('ts_job_id', 'status_id', \DB::raw('count(*) as count'))
            ->groupBy('ts_job_id', 'status_id')
            ->get()
            ->groupBy('ts_job_id');

        return compact(
            'activeSyncJobs', 
            'totalSchoolCount', 
            'completedStatus', 
            'statuses', 
            'seasons', 
            'defaultSeasonJobs',
            'schools',
            'folderStatusCounts'
        );
    }
    
    public function getActiveSyncJobs($franchiseCode, $schoolKey = null)
    {
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;

        return $this->queryJobs($franchiseCode, $schoolKey)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('job_users.user_id', Auth::user()->id)
            ->whereNotIn('jobs.job_status_id', [$this->statusService->archived, $tnjNotFound, $deleted])
            ->orderBy('jobs.id', 'asc')
            ->get();
    }

    public function getActiveSyncJobsBySchoolkey($schoolkey)
    {
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        return $this->queryJobs(null,$schoolkey)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->whereNotIn('jobs.job_status_id', [$this->statusService->archived, $tnjNotFound, $deleted])
            ->orderBy('jobs.id', 'asc')
            ->get();
    }

    public function getDefaultSeasonJobs($seasonID, $franchiseCode)
    {
        return $this->queryJobs($franchiseCode,null)
            ->where('jobs.ts_season_id', $seasonID)
            ->orderBy('jobs.id', 'asc')
            ->get();
    }  
    
    public function getJobsByTSJobID($TSJobID)
    {
        return Job::with([
            'folders.subjects.images',
            'folders.attachedsubjects.images',
            'folders.folderTag',
            'folders.images',
        ])->where('ts_job_id', $TSJobID)->first();
    }
    
    public function toggleArchivedJobs($franchiseCode, $schoolKey, $includeArchived)
    {   
        $archiveStatus = $this->statusService->archived;
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        
        $query = $this->queryJobs($franchiseCode,null)
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where('job_users.user_id', Auth::user()->id);

        if ($includeArchived) {
            $jobs = $query->where('jobs.job_status_id', $this->statusService->archived)->get();
        } else {
            $jobs = $query->whereNotIn('jobs.job_status_id', [$archiveStatus, $tnjNotFound, $deleted])
                          ->where('jobs.jobsync_status_id', $this->statusService->sync)
                          ->get();
        }

        return $jobs->map(function ($job) {
            $job->hash = Crypt::encryptString($job->ts_job_id);
            $job->jobKeyHash = Crypt::encryptString($job->ts_jobkey);
            $job->config_url = \URL::signedRoute('config-job', ['hash' => $job->jobKeyHash]);
            $job->folderCounts = $job->folders->groupBy('status_id')->map->count();
            $statusNames = $this->statusService->getDataById($job->folderCounts->keys())->pluck('status_external_name', 'id');
            $job->folderCounts = $job->folderCounts->mapWithKeys(function ($count, $statusId) use ($statusNames) {
                return [$statusNames[$statusId] ?? 'Unknown Status' => $count];
            })->toArray();
            return $job;
        });
    }

    public function updateJobStatus($tsJobId, $newStatusId)
    {
        $job = $this->getJobById($tsJobId);
        if (!$job) {
            throw new \Exception("Job not found for ID: " . $tsJobId);
        }

        $oldStatusId = $job->job_status_id;

        $rootUserId = Auth::id();
        ActivityLogHelper::log(LogConstants::JOB_STATUS_CHANGED, [
            'jobkey' => $job->ts_jobkey,
            'status' => $newStatusId
        ], $rootUserId);
        
        $job->update(['job_status_id' => $newStatusId]);
    
        $statusFields = [
            $this->statusService->modified => 'job_status_modified',
            $this->statusService->completed => 'job_status_completed',
            $this->statusService->unlocked => 'job_status_unlocked'
        ];
    
        if (isset($statusFields[$newStatusId])) {
            // Only send 'modified' email if it wasn't already modified
            if ($newStatusId != $this->statusService->modified || $oldStatusId != $this->statusService->modified) {
                $this->emailService->saveEmailContent($job->ts_jobkey, $statusFields[$newStatusId], Carbon::now(), $newStatusId);
            }
        }
    
        if ($newStatusId == $this->statusService->completed) {
            $this->getFolderService()->updateFolderStatus($job->folders->pluck('ts_folder_id')->toArray(), $newStatusId);
        }
    }

    // public function getJobsBySeason($seasonId, $franchiseCode)
    // {
    //     return $this->getDefaultSeasonJobs($seasonId, $franchiseCode);
    // }

    public function getJobsBySeason($schoolkey, $seasonId)
    {
        return $this->queryJobs(null,$schoolkey)
        ->where([
            ['jobs.ts_season_id', $seasonId],
            ['jobs.jobsync_status_id', $this->statusService->sync],
            ['jobs.foldersync_status_id', $this->statusService->completed]
        ])
        ->distinct()
        ->orderBy('ts_jobname', 'asc');
    }

    public function getJobById($id)
    {
        return Job::where('ts_job_id',$id)->first();
    }

    public function getJobByJobKey($jobkey)
    {
        return Job::with(['folders', 'subjects'])->where('ts_jobkey',$jobkey);
    }

    public function updateJobData($jobkey, $column, $value){
        return Job::where('ts_jobkey',$jobkey)->update([$column => $value]);
    }

    public function deleteJob($tsJobKey)
    {                    
        $job = Job::with('seasons')->where('ts_jobkey', $tsJobKey)->firstOrFail();
                                                    
        $tsFolderIds = $job->folders()->pluck('ts_folder_id')->toArray();                                                                
        
        \DB::beginTransaction();

        try {
            if (!empty($tsFolderIds)) {
                \DB::table('folder_users')->whereIn('ts_folder_id', $tsFolderIds)->delete();
            }
            $job->jobUsers()->delete();

            $rootUserId = Auth::id();
            ActivityLogHelper::log(LogConstants::JOB_STATUS_CHANGED, [
                'jobkey' => $job->ts_jobkey,
                'status' => $this->statusService->deleted
            ], $rootUserId);

            $job->update([
                'job_status_id' => $this->statusService->deleted,
                'imagesync_status_id' => $this->statusService->unsync,
                'show_proofing' => null,
                'proof_start' => null,
                'proof_warning' => null,
                'proof_due' => null,
                'proof_catchup' => null,
                'force_sync' => null,
                'notifications_enabled' => null,
                'notifications_matrix' => null
            ]);

            \DB::commit();
            
            // Delete Group Image

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error("Delete Job Error: " . $e->getMessage());
            throw $e;
        }
    }

    protected function queryJobs($franchiseCode = null, $schoolkey = null)
    {
        return Job::join('schools', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
        ->join('seasons', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
        ->join('franchises', 'franchises.ts_account_id', '=', 'jobs.ts_account_id')
        ->leftjoin('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
        ->when($franchiseCode, fn($query) => $query->where('franchises.alphacode', $franchiseCode))
        ->when($schoolkey, fn($query) => $query->where('jobs.ts_schoolkey', $schoolkey))
        ->with(['reviewStatuses'])
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
            'seasons.code as season_code',
            'show_proofing'
        );
    }
}
