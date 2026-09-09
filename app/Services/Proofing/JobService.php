<?php

namespace App\Services\Proofing;

use App\Models\Folder;
use App\Models\Job;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SchoolService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\EmailService;
use App\Helpers\ActivityLogHelper;
use App\Helpers\SchoolContextHelper;
use App\Helpers\Constants\LogConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * Lean job list for Photography Configure season dropdown (no franchise/season/job_users joins).
     */
    public function getPortalConfigureJobsList(int $schoolId, $seasonId)
    {
        $query = $this->portalConfigureJobsBaseQuery($schoolId);
        $this->constrainJobsToSeasons($query, $seasonId);

        return $query
            ->select([
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
            ])
            ->selectRaw($this->portalConfigureJobsPortraitExistsSql())
            ->orderBy('jobs.ts_jobname')
            ->get();
    }

    /**
     * All portal configure jobs for a school, grouped by ts_season_id (single query).
     *
     * @return array<int|string, list<array{ts_jobkey: string, ts_jobname: string, has_visible_portrait: bool}>>
     */
    public function getPortalConfigureJobsGroupedBySeason(int $schoolId): array
    {
        $jobs = $this->portalConfigureJobsBaseQuery($schoolId)
            ->select([
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.ts_season_id',
            ])
            ->selectRaw($this->portalConfigureJobsPortraitExistsSql())
            ->orderBy('jobs.ts_jobname')
            ->get();

        $grouped = [];
        foreach ($jobs as $job) {
            $grouped[$job->ts_season_id][] = [
                'ts_jobkey' => $job->ts_jobkey,
                'ts_jobname' => $job->ts_jobname,
                'has_visible_portrait' => (bool) $job->has_visible_portrait,
            ];
        }

        return $grouped;
    }

    protected function portalConfigureJobsBaseQuery(int $schoolId)
    {
        $school = \App\Models\School::find($schoolId, ['id', 'schoolkey']);
        $schoolKey = $school?->schoolkey ?? '';

        $query = Job::query()
            ->where('jobs.show_portal', 1)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('jobs.foldersync_status_id', $this->statusService->completed)
            ->where(function ($q) use ($schoolId, $schoolKey) {
                $q->where('jobs.school_id', $schoolId);
                if ($schoolKey !== '') {
                    $q->orWhere(function ($unassigned) use ($schoolKey) {
                        $unassigned->whereNull('jobs.school_id')
                            ->where('jobs.ts_schoolkey', $schoolKey);
                    });
                }
            });

        \App\Helpers\PhotographyJobQueryHelper::applyDownloadAvailableJobFilter($query);

        return $query;
    }

    protected function portalConfigureJobsPortraitExistsSql(): string
    {
        return 'EXISTS (
                SELECT 1 FROM folders
                WHERE folders.ts_job_id = jobs.ts_job_id
                  AND folders.ts_folderkey IS NOT NULL
                  AND (folders.is_deleted = 0 OR folders.is_deleted IS NULL)
                  AND folders.is_visible_for_portrait = 1
            ) as has_visible_portrait';
    }

    /**
     * Proofing jobs that must be archived before they appear in Photography configure/portraits.
     */
    public function getProofingJobsNeedingArchive(int $schoolId): \Illuminate\Support\Collection
    {
        $school = \App\Models\School::find($schoolId, ['id', 'schoolkey']);
        $schoolKey = $school?->schoolkey ?? '';
        $archivedId = $this->statusService->archived;
        $completedId = $this->statusService->completed;

        return Job::query()
            ->join('seasons', 'seasons.ts_season_id', '=', 'jobs.ts_season_id')
            ->leftJoin('status', 'status.id', '=', 'jobs.job_status_id')
            ->where('jobs.show_proofing', 1)
            ->where('jobs.job_status_id', '!=', $archivedId)
            ->where(function ($q) use ($completedId) {
                $q->where('jobs.job_status_id', $completedId)
                    ->orWhere(function ($dueQuery) {
                        $dueQuery->whereNotNull('jobs.proof_due')
                            ->where('jobs.proof_due', '<', now());
                    });
            })
            ->where(function ($q) use ($schoolId, $schoolKey) {
                $q->where('jobs.school_id', $schoolId);
                if ($schoolKey !== '') {
                    $q->orWhere(function ($unassigned) use ($schoolKey) {
                        $unassigned->whereNull('jobs.school_id')
                            ->where('jobs.ts_schoolkey', $schoolKey);
                    });
                }
            })
            ->select([
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.proof_due',
                'jobs.job_status_id',
                'seasons.code as season_code',
                'status.status_external_name as job_status_name',
                'status.status_internal_name as job_status_internal_name',
            ])
            ->orderBy('jobs.ts_jobname')
            ->get();
    }

    /**
     * @param  list<int>  $tsJobIds
     * @return array{archived: list<int>, failed: list<int>}
     */
    public function archiveProofingJobsForSchool(array $tsJobIds, int $schoolId): array
    {
        $eligibleIds = $this->getProofingJobsNeedingArchive($schoolId)
            ->pluck('ts_job_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $archived = [];
        $failed = [];

        foreach ($tsJobIds as $tsJobId) {
            $tsJobId = (int) $tsJobId;
            if ($tsJobId <= 0 || ! in_array($tsJobId, $eligibleIds, true)) {
                $failed[] = $tsJobId;
                continue;
            }

            try {
                $this->updateJobStatus($tsJobId, $this->statusService->archived);
                $archived[] = $tsJobId;
            } catch (\Throwable) {
                $failed[] = $tsJobId;
            }
        }

        return ['archived' => $archived, 'failed' => $failed];
    }

    /**
     * Folder rows for portal digital-image configure (counts via SQL, no image eager load).
     */
    public function getPortalJobFolderConfig(int $tsJobId): array
    {
        $folders = Folder::query()
            ->where('ts_job_id', $tsJobId)
            ->whereNotNull('ts_folderkey')
            ->where(function ($query) {
                $query->where('is_deleted', 0)->orWhereNull('is_deleted');
            })
            ->with(['folderTags'])
            ->select([
                'folders.ts_folder_id',
                'folders.portal_ts_foldername',
                'folders.folder_tag',
                'folders.is_visible_for_portrait',
                'folders.is_visible_for_group',
            ])
            ->orderBy('portal_ts_foldername')
            ->get();

        if ($folders->isEmpty()) {
            return [];
        }

        $counts = $this->getPortalFolderImageCounts($folders->pluck('ts_folder_id')->all());

        return $folders
            ->map(fn ($folder) => [
                'ts_foldername' => $folder->portal_ts_foldername,
                'ts_folder_id' => $folder->ts_folder_id,
                'tag' => $folder->folderTags->external_name ?? null,
                'is_visible_for_portrait' => $folder->is_visible_for_portrait,
                'is_visible_for_group' => $folder->is_visible_for_group,
                'groupCount' => $counts['group'][$folder->ts_folder_id] ?? 0,
                'students' => $counts['students'][$folder->ts_folder_id] ?? 0,
                'attached' => $counts['attached'][$folder->ts_folder_id] ?? 0,
            ])
            ->values()
            ->all();
    }

    /**
     * Batch portrait/group counts for configure folders (3 queries instead of per-folder subqueries).
     *
     * @return array{group: array<int, int>, students: array<int, int>, attached: array<int, int>}
     */
    protected function getPortalFolderImageCounts(array $folderIds): array
    {
        if ($folderIds === []) {
            return ['group' => [], 'students' => [], 'attached' => []];
        }

        $students = DB::table('subjects')
            ->join('images', 'images.keyvalue', '=', 'subjects.ts_subjectkey')
            ->whereIn('subjects.ts_folder_id', $folderIds)
            ->where(function ($query) {
                $query->where('subjects.is_deleted', 0)
                    ->orWhereNull('subjects.is_deleted');
            })
            ->where(function ($query) {
                $query->where('images.is_deleted', 0)
                    ->orWhereNull('images.is_deleted');
            })
            ->select('subjects.ts_folder_id', DB::raw('COUNT(DISTINCT subjects.ts_subject_id) as total'))
            ->groupBy('subjects.ts_folder_id')
            ->pluck('total', 'ts_folder_id');

        $attached = DB::table('folder_subjects')
            ->join('subjects', 'subjects.ts_subject_id', '=', 'folder_subjects.ts_subject_id')
            ->join('images', 'images.keyvalue', '=', 'subjects.ts_subjectkey')
            ->whereIn('folder_subjects.ts_folder_id', $folderIds)
            ->where(function ($query) {
                $query->where('folder_subjects.is_deleted', 0)
                    ->orWhereNull('folder_subjects.is_deleted');
            })
            ->where(function ($query) {
                $query->where('subjects.is_deleted', 0)
                    ->orWhereNull('subjects.is_deleted');
            })
            ->where(function ($query) {
                $query->where('images.is_deleted', 0)
                    ->orWhereNull('images.is_deleted');
            })
            ->select('folder_subjects.ts_folder_id', DB::raw('COUNT(DISTINCT folder_subjects.ts_subject_id) as total'))
            ->groupBy('folder_subjects.ts_folder_id')
            ->pluck('total', 'ts_folder_id');

        $group = DB::table('folders')
            ->join('images', 'images.keyvalue', '=', 'folders.ts_folderkey')
            ->whereIn('folders.ts_folder_id', $folderIds)
            ->where(function ($query) {
                $query->where('images.is_deleted', 0)
                    ->orWhereNull('images.is_deleted');
            })
            ->select('folders.ts_folder_id', DB::raw('COUNT(*) as total'))
            ->groupBy('folders.ts_folder_id')
            ->pluck('total', 'ts_folder_id');

        return [
            'group' => $group->all(),
            'students' => $students->all(),
            'attached' => $attached->all(),
        ];
    }

    public function findPortalJobForSchool(string $jobKey, int $schoolId): ?Job
    {
        if ($jobKey === '' || $schoolId <= 0) {
            return null;
        }

        $school = \App\Models\School::find($schoolId, ['id', 'schoolkey']);
        $schoolKey = $school?->schoolkey ?? '';

        $query = Job::query()
            ->where('jobs.ts_jobkey', $jobKey)
            ->where('jobs.show_portal', 1)
            ->where('jobs.jobsync_status_id', $this->statusService->sync)
            ->where('jobs.foldersync_status_id', $this->statusService->completed)
            ->where(function ($q) use ($schoolId, $schoolKey) {
                $q->where('jobs.school_id', $schoolId);
                if ($schoolKey !== '') {
                    $q->orWhere(function ($unassigned) use ($schoolKey) {
                        $unassigned->whereNull('jobs.school_id')
                            ->where('jobs.ts_schoolkey', $schoolKey);
                    });
                }
            });

        \App\Helpers\PhotographyJobQueryHelper::applyDownloadAvailableJobFilter($query);

        return $query->select(
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.download_available_date',
                'jobs.portrait_download_date',
                'jobs.group_download_date'
            )
            ->first();
    }

    public function toggleArchivedJobs($franchiseCode, $schoolId, $includeArchived)
    {
        $archiveStatus = $this->statusService->archived;
        $tnjNotFound = $this->statusService->tnjNotFound;
        $deleted = $this->statusService->deleted;

        $query = $this->queryJobs($franchiseCode, $schoolId)
            ->where('job_users.user_id', Auth::user()->id);

        if ($includeArchived) {
            $jobs = $query->where('jobs.job_status_id', $this->statusService->archived)->with('folders')->get();
        } else {
            $jobs = $query->whereNotIn('jobs.job_status_id', [$archiveStatus, $tnjNotFound, $deleted])
                          ->where('jobs.jobsync_status_id', $this->statusService->sync)
                          ->with('folders')
                          ->get();
        }

        $allStatusIds = $jobs->flatMap(fn ($job) => $job->folders->pluck('status_id'))->unique()->filter()->values();
        $statusNamesById = $this->statusService->getDataById($allStatusIds)->pluck('status_external_name', 'id');

        return $jobs->map(function ($job) use ($statusNamesById) {
            $job->hash = Crypt::encryptString($job->ts_job_id);
            $job->jobKeyHash = Crypt::encryptString($job->ts_jobkey);
            $job->config_url = \URL::signedRoute('config-job', ['hash' => $job->jobKeyHash]);
            $job->folderCounts = $job->folders->groupBy('status_id')->map->count();
            $job->folderCounts = $job->folderCounts->mapWithKeys(function ($count, $statusId) use ($statusNamesById) {
                return [$statusNamesById[$statusId] ?? 'Unknown Status' => $count];
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

        $jobUpdates = ['job_status_id' => $newStatusId];
        // Archived jobs must appear in Photography configure / portraits.
        if ((int) $newStatusId === (int) $this->statusService->archived) {
            $jobUpdates['show_portal'] = 1;
        }
        $job->update($jobUpdates);

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

