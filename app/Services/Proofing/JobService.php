<?php

namespace App\Services\Proofing;

use App\Models\Job;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SchoolService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\EmailService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
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

    public function getDashboardData($franchiseCode)
    {
        $selectedSchoolkey = $this->schoolService->getSchoolById(Session::get('school_context-sid'))->select('schoolkey')->first() ?? Auth::user()->getSchool();
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        $activeSyncJobs = $this->getActiveSyncJobs($franchiseCode);
        $statuses = $this->statusService->getAllStatusData('id', 'status_internal_name', 'status_external_name')->get();
        $completedStatus = $this->statusService->completed;
        $totalSchoolCount = $this->queryJobs($franchiseCode,$selectedSchoolkey->schoolkey)->whereNotIn('jobs.job_status_id', [$tnjNotFound, $deleted])
            ->where('job_users.user_id', Auth::user()->id)->count();
        $seasons = $this->seasonService->getAllSeasonData('code', 'is_default', 'ts_season_id')->get();
        $schools = $this->schoolService->franchiseSchools($franchiseCode)->get();
        
        // Check if there is a default season
        $defaultSeason = $seasons->where('is_default', 1)->first();
        $defaultSeasonJobs = [];
        if ($defaultSeason) {
            $defaultSeasonJobs = $this->getDefaultSeasonJobs($defaultSeason->ts_season_id, $franchiseCode);
        }

        return compact(
            'activeSyncJobs', 
            'totalSchoolCount', 
            'completedStatus', 
            'statuses', 
            'seasons', 
            'defaultSeasonJobs',
            'schools'
        );
    }

    public function getActiveSyncJobs($franchiseCode)
    {
        $selectedSchoolkey = $this->schoolService->getSchoolById(Session::get('school_context-sid'))->select('schoolkey')->first() ?? Auth::user()->getSchool();
        
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        return $this->queryJobs($franchiseCode,$selectedSchoolkey->schoolkey)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('job_users.user_id', Auth::user()->id)
            ->whereNotIn('jobs.job_status_id', [$tnjNotFound, $deleted])
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getActiveSyncJobsBySchoolkey($schoolkey)
    {
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        return $this->queryJobs(null,$schoolkey)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->whereNotIn('jobs.job_status_id', [$tnjNotFound, $deleted])
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
    
    public function getJobsByTSJobID($TSJobID)
    {
        return Job::with(['folders.subjects.images','folders.folderTag','folders.images'])->where('ts_job_id', $TSJobID)->first();
    }
    
    public function toggleArchivedJobs($franchiseCode, $schoolKey, $includeArchived)
    {   
        $archiveStatus = $this->statusService->archived;
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        if ($includeArchived) {
            return $this->queryJobs($franchiseCode,null)
                ->where('jobs.job_status_id', $this->statusService->archived)
                ->where('jobs.ts_schoolkey', $schoolKey)
                ->where('job_users.user_id', Auth::user()->id)
                ->get()
                ->map(function ($job) {
                    $job->hash = Crypt::encryptString($job->ts_job_id);
                    $job->folderCounts = $job->folders->groupBy('status_id')->map->count();
                    $statusNames = $this->statusService->getDataById($job->folderCounts->keys())->pluck('status_external_name', 'id');
                    $job->folderCounts = $job->folderCounts->mapWithKeys(function ($count, $statusId) use ($statusNames) {
                        return [$statusNames[$statusId] ?? 'Unknown Status' => $count];
                    })->toArray();
                    return $job;
                });
        }else{
            return $this->queryJobs($franchiseCode,null)
            ->whereNotIn('jobs.job_status_id', [$archiveStatus, $tnjNotFound, $deleted])
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('jobs.ts_schoolkey', $schoolKey)
            ->where('job_users.user_id', Auth::user()->id)
            ->get()
            ->map(function ($job) {
                $job->hash = Crypt::encryptString($job->ts_job_id);
                $job->folderCounts = $job->folders->groupBy('status_id')->map->count();
                $statusNames = $this->statusService->getDataById($job->folderCounts->keys())->pluck('status_external_name', 'id');
                $job->folderCounts = $job->folderCounts->mapWithKeys(function ($count, $statusId) use ($statusNames) {
                    return [$statusNames[$statusId] ?? 'Unknown Status' => $count];
                })->toArray();
                return $job;
            });
        }
        return [];
    }

    public function updateJobStatus($tsJobId, $newStatusId)
    {
        $job = $this->getJobById($tsJobId);
        if (!$job) {
            throw new \Exception("Job not found for ID: " . $tsJobId);
        }
    
        $job->update(['job_status_id' => $newStatusId]);
    
        $statusFields = [
            $this->statusService->modified => 'job_status_modified',
            $this->statusService->completed => 'job_status_completed',
            $this->statusService->unlocked => 'job_status_unlocked'
        ];
    
        if (isset($statusFields[$newStatusId])) {
            $this->emailService->saveEmailContent($job->ts_jobkey, $statusFields[$newStatusId], Carbon::now(), $newStatusId);
        }
    
        if ($newStatusId === $this->statusService->completed) {
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
        ->orderBy('id', 'asc')
        ->get();
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

    // public function deleteJob($tsJobId){
    //     $job = Job::where('ts_job_id', $tsJobId)->firstOrFail();

    //     $job->update([
    //         'jobsync_status_id' => $this->statusService->sync,
    //         'foldersync_status_id' => $this->statusService->pending,
    //         'job_status_id' => $this->statusService->deleted,
    //         'imagesync_status_id' => $this->statusService->unsync,
    //         'proof_start' => null,
    //         'proof_warning' => null,
    //         'proof_due' => null,
    //         'proof_catchup' => null,
    //         'force_sync' => null,
    //         'notifications_enabled' => null,
    //         'notifications_matrix' => null
    //     ]);

    //     $job->folders()->each(function ($folder) {
    //         // Delete associated subjects, images in folders
    //         $folder->subjects()->each(function ($subject) {
    //             $subject->images()->delete(); // Delete images associated with the subject
    //         });
    //         $folder->folderUsers()->delete(); // Delete users associated with the folder
    //         $folder->images()->delete(); // Delete images associated with the folder
    //         $folder->subjects()->delete(); // Delete subjects associated with the folder
    //         $folder->attachedsubjects()->delete(); // Delete attached subjects from folder_subject table
    //     });

    //     $job->folders()->delete(); // Delete folders associated with the job
    //     $job->jobUsers()->delete(); // Delete job users associated with the job
    
    //     // Delete group positions, changelogs, emails associated with the job via ts_jobkey
    //     $job->groupPositions()->delete(); // Delete group positions associated with the job
    //     $job->proofingChangelogs()->delete(); // Delete changelogs associated with the job
    //     // $job->email()->delete(); // Delete emails associated with the job  - (2026 Dec Enhancement)
    // }

    public function deleteJob($tsJobKey)
    {                    
        $job = Job::where('ts_jobkey', $tsJobKey)->firstOrFail();
        $tsJobId = $job->ts_job_id;
                                                    
        $tsFolderIds = $job->folders()->pluck('ts_folder_id')->toArray();                                                                
                                                                    
        if (!empty($tsFolderIds)) {
                            
            \DB::table('subjects')
                ->whereIn('ts_folder_id', $tsFolderIds)
                ->delete();
                                                        
            \DB::table('folder_users')
                ->whereIn('ts_folder_id', $tsFolderIds)
                ->delete();

            \DB::table('folder_subjects')
                ->whereIn('ts_folder_id', $tsFolderIds)
                ->delete();
                
            \DB::table('images')
                ->where('ts_job_id', $tsJobId) 
                ->delete();

            \DB::table('changelogs')
                ->where('ts_jobkey', $tsJobKey)
                ->delete();
        }

        $job->folders()->delete();
        $job->jobUsers()->delete();
        $job->groupPositions()->delete(); 
        // \DB::table('emails')->where('ts_jobkey', $tsJobKey)->delete(); //2026 Dec Enhancement

        $job->update([
            'jobsync_status_id' => $this->statusService->sync,
            'foldersync_status_id' => $this->statusService->pending,
            'job_status_id' => $this->statusService->deleted,
            'imagesync_status_id' => $this->statusService->unsync,
            'proof_start' => null,
            'proof_warning' => null,
            'proof_due' => null,
            'proof_catchup' => null,
            'force_sync' => null,
            'notifications_enabled' => null,
            'notifications_matrix' => null
        ]);
    }

    protected function queryJobs($franchiseCode = null, $schoolkey = null)
    {
        return Job::join('schools', 'jobs.ts_schoolkey', '=', 'schools.schoolkey')
        ->join('seasons', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
        ->join('franchises', 'franchises.ts_account_id', '=', 'jobs.ts_account_id')
        ->leftjoin('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
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
