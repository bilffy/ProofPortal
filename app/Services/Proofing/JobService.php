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
        $selectedSchoolId = $school ? $school->id : null;

        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;
        $activeSyncJobs = $this->getActiveSyncJobs($franchiseCode, $selectedSchoolId);
        $statuses = $this->statusService->getAllStatusData('id', 'status_internal_name', 'status_external_name')->get();
        $completedStatus = $this->statusService->completed;
        $totalSchoolCount = $this->queryJobs($franchiseCode, $selectedSchoolId)->whereNotIn('jobs.job_status_id', [$this->statusService->archived, $tnjNotFound, $deleted])
            ->where('job_users.user_id', Auth::user()->id)->count();
        $seasons = $this->seasonService->getAllSeasonDataForProofing('code', 'show_in_proofing', 'is_default', 'ts_season_id')->get();
        $schools = $this->schoolService->franchiseSchools($franchiseCode)->get();

        // Check if there is a default season
        $defaultSeason = $seasons->where('show_in_proofing', 1)->first();
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

    public function getActiveSyncJobs($franchiseCode, $schoolId = null)
    {
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;

        return $this->queryJobs($franchiseCode, $schoolId)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('job_users.user_id', Auth::user()->id)
            ->whereNotIn('jobs.job_status_id', [$this->statusService->archived, $tnjNotFound, $deleted])
            ->orderBy('jobs.id', 'asc')
            ->get();
    }

    public function getActiveSyncJobsBySchoolkey($schoolkey)
    {
        $school = \App\Models\School::where('schoolkey', $schoolkey)->first();

        return $this->getActiveSyncJobsBySchoolId($school?->id);
    }

    public function getActiveSyncJobsBySchoolId(?int $schoolId)
    {
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;

        return $this->queryJobs(null, $schoolId)
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
            'folders.folderTags',
            'folders.images',
        ])->where('ts_job_id', $TSJobID)->first();
    }

    public function getJobsByTSJobIDs(array $TSJobIDs)
    {
        return Job::with([
            'folders.subjects.images',
            'folders.attachedsubjects.images',
            'folders.folderTags',
            'folders.images',
        ])->whereIn('ts_job_id', $TSJobIDs)->get()->keyBy('ts_job_id');
    }

    public function toggleArchivedJobs($franchiseCode, $schoolId, $includeArchived)
    {
        $archiveStatus = $this->statusService->archived;
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;

        $query = $this->queryJobs($franchiseCode, $schoolId)
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

        // Clear outstanding pending emails before creating the completion notification
        if ($newStatusId == $this->statusService->completed) {
            $this->emailService->expirePendingEmailsForJob($job->ts_jobkey);
        }

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
        $school = \App\Models\School::where('schoolkey', $schoolkey)->first();

        return $this->getJobsBySeasonAndSchoolId($school?->id, $seasonId);
    }

    public function getJobsBySeasonAndSchoolId(?int $schoolId, $seasonId)
    {
        $query = $this->queryJobs(null, $schoolId)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('jobs.foldersync_status_id', $this->statusService->completed)
            ->distinct()
            ->orderBy('ts_jobname', 'asc');

        $this->constrainJobsToSeasons($query, $seasonId);

        return $query;
    }

    /**
     * Portal jobs that should not appear on Unsynced Jobs.
     * Match by ts_schoolkey (same as Timestone). school_id may be null.
     */
    public function getJobsShownInProofing(?string $schoolKey, $seasonId)
    {
        $query = $this->queryJobs(null, null)
            ->where('jobs.show_proofing', 1)
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('jobs.job_status_id')
                    ->orWhere('jobs.job_status_id', '!=', $this->statusService->deleted);
            })
            ->when($schoolKey, fn ($query) => $query->where('jobs.ts_schoolkey', $schoolKey))
            ->distinct()
            ->orderBy('ts_jobname', 'asc');

        $this->constrainJobsToSeasons($query, $seasonId);

        return $query;
    }

    protected function constrainJobsToSeasons($query, $seasonId): void
    {
        if (is_array($seasonId)) {
            if (!empty($seasonId)) {
                $query->whereIn('jobs.ts_season_id', $seasonId);
            }

            return;
        }

        if ($seasonId !== null && $seasonId !== '') {
            $query->where('jobs.ts_season_id', $seasonId);
        }
    }

    /**
     * Stamp portal school ownership when a job is selected on Configure.
     */
    public function assignSchoolToJob(string $jobKey, int $schoolId): bool
    {
        $job = Job::withoutGlobalScopes()
            ->where('ts_jobkey', $jobKey)
            ->first();

        if (!$job) {
            return false;
        }

        // Already owned by this school — treat as success (MySQL would report 0 affected rows)
        if ((int) $job->school_id === $schoolId) {
            return true;
        }

        $updated = Job::withoutGlobalScopes()
            ->where('ts_jobkey', $jobKey)
            ->update(['school_id' => $schoolId]);

        return $updated > 0;
    }

    public function getJobById($id)
    {
        return Job::where('ts_job_id',$id)->first();
    }

    public function getJobByJobKey($jobkey)
    {
        // Do not eager-load folders/subjects here — config-job (and Redis session)
        // previously OOM'd serialising entire school graphs into session.
        return Job::where('ts_jobkey', $jobkey);
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

    protected function queryJobs($franchiseCode = null, $schoolId = null)
    {
        // Prefer jobs.school_id (unique portal ownership). schoolkey alone is not unique (e.g. DEMO).
        return Job::join('franchises', 'franchises.ts_account_id', '=', 'jobs.ts_account_id')
            ->leftJoin('schools', 'schools.id', '=', 'jobs.school_id')
            ->join('seasons', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
            ->leftJoin('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->when($franchiseCode, fn ($query) => $query->where('franchises.alphacode', $franchiseCode))
            ->when($schoolId, function ($query) use ($schoolId) {
                $school = \App\Models\School::find($schoolId);
                $schoolKey = $school?->schoolkey ?? '';

                // Configured for this school, or unassigned Timestone rows matching schoolkey
                $query->where(function ($q) use ($schoolId, $schoolKey) {
                    $q->where('jobs.school_id', $schoolId);
                    if ($schoolKey !== '') {
                        $q->orWhere(function ($unassigned) use ($schoolKey) {
                            $unassigned->whereNull('jobs.school_id')
                                ->where('jobs.ts_schoolkey', $schoolKey);
                        });
                    }
                });
            })
            ->with(['reviewStatuses'])
            ->select(
                'jobs.id',
                'jobs.ts_job_id',
                'jobs.ts_season_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.job_status_id',
                'jobs.school_id',
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

