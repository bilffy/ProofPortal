<?php

namespace App\Services\Proofing;

use App\Models\Job;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FranchiseDashboardService
{
    public function __construct(
        protected StatusService $statusService,
        protected SeasonService $seasonService,
    ) {
    }

    /**
     * Schools belonging to the user's franchise.
     *
     * @return Collection<int, School>
     */
    public function getFranchiseSchools(User $user): Collection
    {
        $query = School::query()
            ->join('school_franchises', 'schools.id', '=', 'school_franchises.school_id')
            ->select(
                'schools.id',
                'schools.schoolkey',
                'schools.name',
                'schools.suburb',
                'schools.postcode',
                'schools.state',
                'schools.school_logo'
            );

        if ($user->isFranchiseLevel()) {
            $franchise = $user->getFranchise();
            if (!$franchise) {
                return collect();
            }
            $query->where('school_franchises.franchise_id', $franchise->id);
        }

        return $query->orderBy('schools.name')->get();
    }

    /**
     * @param  array<int>  $schoolIds
     * @param  array<string>  $schoolKeys
     * @param  array<int>|null  $seasonIds
     */
    protected function deletedJobsQuery(array $schoolIds, array $schoolKeys, ?array $seasonIds): Builder
    {
        $query = Job::query()
            ->where('jobs.job_status_id', $this->statusService->deleted)
            ->whereNull('jobs.show_proofing');

        $this->constrainJobsToSchools($query, $schoolIds, $schoolKeys);

        return $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));
    }

    /**
     * Proofing jobs for franchise schools (not limited to a single user assignment).
     *
     * @param  array<int>  $schoolIds
     * @param  array<string>  $schoolKeys
     * @param  array<int>|null  $seasonIds
     */
    protected function proofingJobsQuery(array $schoolIds, array $schoolKeys, ?array $seasonIds): Builder
    {
        $query = Job::query()
            ->where('jobs.show_proofing', 1);

        $this->constrainJobsToSchools($query, $schoolIds, $schoolKeys);

        return $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));
    }

    /**
     * @param  array<int>  $schoolIds
     * @param  array<string>  $schoolKeys
     */
    protected function constrainJobsToSchools(Builder $query, array $schoolIds, array $schoolKeys): void
    {
        $query->where(function (Builder $q) use ($schoolIds, $schoolKeys) {
            $q->whereIn('jobs.school_id', $schoolIds);

            if (!empty($schoolKeys)) {
                $q->orWhere(function (Builder $unassigned) use ($schoolKeys) {
                    $unassigned->whereNull('jobs.school_id')
                        ->whereIn('jobs.ts_schoolkey', $schoolKeys);
                });
            }
        });
    }

    /**
     * Headline counts for the franchise dashboard tiles and charts.
     *
     * @param  int|string|null  $seasonId
     * @return array<string, int>
     */
    public function getDashboardMetrics(User $user, $seasonId = null): array
    {
        $schools = $this->getFranchiseSchools($user);
        $schoolIds = $schools->pluck('id')->map(fn ($id) => (int) $id)->all();
        $keyToIds = [];
        foreach ($schools as $school) {
            $key = trim((string) ($school->schoolkey ?? ''));
            if ($key !== '') {
                $keyToIds[$key][] = (int) $school->id;
            }
        }
        $schoolKeys = array_keys($keyToIds);

        $metrics = [
            'photography_total' => 0,
            'photography_configured' => 0,
            'photography_not_configured' => 0,
            'active_proofing' => 0,
            'schools_count' => count($schoolIds),
            'synced' => 0,
            'status_opened' => 0,
            'status_not_opened' => 0,
            'status_completed' => 0,
            'status_archived' => 0,
            'status_deleted' => 0,
            'status_incomplete' => 0,
            'status_modified' => 0,
            'status_unlocked' => 0,
        ];

        if (empty($schoolIds)) {
            return $metrics;
        }

        $seasonIds = $this->resolveSeasonIds($seasonId);
        $selectedSeasonOnly = $seasonId !== null && $seasonId !== '';
        $photographySeasonIds = $selectedSeasonOnly ? $seasonIds : null;

        $modifiedId = (int) $this->statusService->modified;
        $unlockedId = (int) $this->statusService->unlocked;
        $incompleteId = (int) $this->statusService->incomplete;
        $activeId = (int) $this->statusService->active;
        $noneId = (int) $this->statusService->none;
        $completedId = (int) $this->statusService->completed;
        $archivedId = (int) $this->statusService->archived;
        $syncId = (int) $this->statusService->sync;

        // Active proofing = jobs whose folders are still being worked on (the
        // "Your Proofing Jobs" tile), separate from the Job Proofing Status chart.
        $activeProofingStatusIds = array_values(array_filter([$modifiedId, $unlockedId, $incompleteId]));

        $validSchoolIds = array_flip($schoolIds);

        // A job counts once for every franchise school it resolves to (by school id,
        // or by school key when the job has no school id).
        $schoolCountFor = function ($job) use ($keyToIds, $validSchoolIds): int {
            if ($job->school_id !== null && (int) $job->school_id > 0) {
                $ids = [(int) $job->school_id];
            } else {
                $ids = $keyToIds[trim((string) $job->ts_schoolkey)] ?? [];
            }

            return count(array_filter($ids, fn ($id) => isset($validSchoolIds[$id])));
        };

        // Photography jobs (show_portal = 1):
        // Configured = download_available_date is not null AND at least one folder has is_visible_for_portrait = 1.
        // Not configured = download_available_date is not null AND no folder has is_visible_for_portrait = 1.
        $photoQuery = Job::query()
            ->where('jobs.show_portal', 1)
            ->whereNotNull('jobs.download_available_date')
            ->select(['jobs.ts_job_id', 'jobs.school_id', 'jobs.ts_schoolkey']);
        $this->constrainJobsToSchools($photoQuery, $schoolIds, $schoolKeys);
        $photoQuery->when(!empty($photographySeasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $photographySeasonIds));
        $photoJobs = $photoQuery->get();
        $photoJobIds = $photoJobs->pluck('ts_job_id')->map(fn ($id) => (int) $id)->all();

        $portraitVisibleJobIds = [];
        if (!empty($photoJobIds)) {
            $portraitVisibleJobIds = \App\Models\Folder::query()
                ->withoutGlobalScopes()
                ->whereIn('ts_job_id', $photoJobIds)
                ->where('is_visible_for_portrait', 1)
                ->distinct()
                ->pluck('ts_job_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->all();
        }

        foreach ($photoJobs as $job) {
            $configured = isset($portraitVisibleJobIds[(int) $job->ts_job_id]);
            $count = $schoolCountFor($job);
            $metrics['photography_total'] += $count;
            $metrics[$configured ? 'photography_configured' : 'photography_not_configured'] += $count;
        }

        // Proofing jobs. Only synced jobs are bucketed by status for the chart.
        $statusBuckets = [
            $activeId => 'status_opened',
            $incompleteId => 'status_incomplete',
            $modifiedId => 'status_modified',
            $unlockedId => 'status_unlocked',
            $noneId => 'status_not_opened',
            $completedId => 'status_completed',
            $archivedId => 'status_archived',
        ];

        $proofingJobs = $this->proofingJobsQuery($schoolIds, $schoolKeys, $seasonIds)
            ->get(['jobs.ts_job_id', 'jobs.school_id', 'jobs.ts_schoolkey', 'jobs.jobsync_status_id', 'jobs.job_status_id']);

        foreach ($proofingJobs as $job) {
            $statusId = (int) ($job->job_status_id ?? 0);
            $count = $schoolCountFor($job);

            if (in_array($statusId, $activeProofingStatusIds, true)) {
                $metrics['active_proofing'] += $count;
            }

            if ((int) $job->jobsync_status_id === $syncId) {
                $metrics['synced'] += $count;
                if (isset($statusBuckets[$statusId])) {
                    $metrics[$statusBuckets[$statusId]] += $count;
                }
            }
        }

        // Deleted jobs (show_proofing null)
        $deletedJobs = $this->deletedJobsQuery($schoolIds, $schoolKeys, $seasonIds)
            ->get(['jobs.ts_job_id', 'jobs.school_id', 'jobs.ts_schoolkey']);
        foreach ($deletedJobs as $job) {
            $metrics['status_deleted'] += $schoolCountFor($job);
        }

        return $metrics;
    }

    /**
     * @param  int|string|null  $seasonId
     * @return array<int>
     */
    /**
     * Synced proofing jobs table for the franchise dashboard (mirrors /proofing home columns,
     * plus school name). Includes archived and deleted-status jobs. Respects the dashboard season filter.
     *
     * @param  int|string|null  $seasonId
     * @return array<int, array<string, mixed>>
     */
    public function getProofingJobsTable(User $user, $seasonId = null): array
    {
        $schools = $this->getFranchiseSchools($user);
        $schoolIds = $schools->pluck('id')->map(fn ($id) => (int) $id)->all();
        $keyToName = [];
        foreach ($schools as $school) {
            $key = trim((string) ($school->schoolkey ?? ''));
            if ($key !== '') {
                $keyToName[$key] = (string) $school->name;
            }
        }
        $schoolKeys = array_keys($keyToName);

        if (empty($schoolIds)) {
            return [];
        }

        $seasonIds = $this->resolveSeasonIds($seasonId);
        $syncId = (int) $this->statusService->sync;
        $archivedId = (int) $this->statusService->archived;
        $deletedId = (int) $this->statusService->deleted;
        $tnjNotFoundId = (int) $this->statusService->tnjNotFound;
        $completedId = (int) $this->statusService->completed;
        $excludeStatusIds = array_values(array_filter([
            $tnjNotFoundId,
        ]));

        $query = Job::query()
            ->leftJoin('schools', 'schools.id', '=', 'jobs.school_id')
            ->leftJoin('seasons', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
            ->where(function (Builder $q) use ($syncId, $deletedId, $archivedId) {
                $q->where(function (Builder $synced) use ($syncId) {
                    $synced->where('jobs.show_proofing', 1)
                        ->where('jobs.jobsync_status_id', $syncId);
                })
                    ->orWhereIn('jobs.job_status_id', array_values(array_filter([$deletedId, $archivedId])));
            })
            ->when(!empty($excludeStatusIds), fn (Builder $q) => $q->whereNotIn('jobs.job_status_id', $excludeStatusIds))
            ->with(['reviewStatuses'])
            ->select([
                'jobs.id',
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.job_status_id',
                'jobs.school_id',
                'jobs.ts_schoolkey',
                'jobs.proof_start',
                'jobs.proof_warning',
                'jobs.proof_due',
                'schools.name as school_name',
                'seasons.code as season_code',
            ])
            ->orderBy('schools.name')
            ->orderBy('jobs.ts_jobname');

        $this->constrainJobsToSchools($query, $schoolIds, $schoolKeys);
        $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));

        $jobs = $query->get();

        if ($jobs->isEmpty()) {
            return [];
        }

        $statuses = $this->statusService
            ->getAllStatusData('id', 'status_internal_name', 'status_external_name')
            ->get();

        $folderStatusCounts = \App\Models\Folder::query()
            ->whereIn('ts_job_id', $jobs->pluck('ts_job_id'))
            ->select('ts_job_id', 'status_id', DB::raw('count(*) as count'))
            ->groupBy('ts_job_id', 'status_id')
            ->get()
            ->groupBy('ts_job_id');

        $configuredJobIds = $this->getPhotographyConfiguredJobIds(
            $jobs->pluck('ts_job_id')->map(fn ($id) => (int) $id)->all()
        );

        $rows = [];
        foreach ($jobs as $job) {
            $statusExternal = optional($job->reviewStatuses)->status_external_name ?? '';

            $schoolName = trim((string) ($job->school_name ?? ''));
            if ($schoolName === '') {
                $key = trim((string) ($job->ts_schoolkey ?? ''));
                $schoolName = $key !== '' ? ($keyToName[$key] ?? $key) : '—';
            }

            $folderCounts = $folderStatusCounts->get((int) $job->ts_job_id, collect());
            $folderStatusParts = [];
            foreach ($statuses as $status) {
                $statusData = $folderCounts->firstWhere('status_id', $status->id);
                $count = $statusData ? (int) $statusData->count : 0;
                if ($count > 0) {
                    $folderStatusParts[] = $status->status_external_name . ': ' . $count;
                }
            }

            $jobId = (int) $job->ts_job_id;
            $rows[] = [
                'id' => (int) $job->id,
                'ts_job_id' => $jobId,
                'ts_jobkey' => (string) $job->ts_jobkey,
                'ts_jobname' => (string) $job->ts_jobname,
                'school_name' => $schoolName,
                'season_code' => (string) ($job->season_code ?? ''),
                'photography_configured' => isset($configuredJobIds[$jobId]),
                'job_status' => (string) $statusExternal,
                'job_status_internal' => (string) (optional($job->reviewStatuses)->status_internal_name ?? ''),
                'is_completed' => (int) ($job->job_status_id ?? 0) === $completedId,
                'folder_statuses' => $folderStatusParts,
                'proof_start' => $job->proof_start,
                'proof_warning' => $job->proof_warning,
                'proof_due' => $job->proof_due,
            ];
        }

        return $rows;
    }

    /**
     * Job IDs that are photography-configured:
     * show_portal = 1, download_available_date not null,
     * and at least one folder with is_visible_for_portrait = 1.
     *
     * @param  array<int>  $jobIds
     * @return array<int, int> keyed by ts_job_id
    **/
    protected function getPhotographyConfiguredJobIds(array $jobIds): array
    {
        $jobIds = array_values(array_filter(array_map('intval', $jobIds)));
        if (empty($jobIds)) {
            return [];
        }

        return \App\Models\Folder::query()
            ->withoutGlobalScopes()
            ->join('jobs', 'jobs.ts_job_id', '=', 'folders.ts_job_id')
            ->whereIn('folders.ts_job_id', $jobIds)
            ->where('jobs.show_portal', 1)
            ->whereNotNull('jobs.download_available_date')
            ->where('folders.is_visible_for_portrait', 1)
            ->distinct()
            ->pluck('folders.ts_job_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();
    }

    public function resolveSeasonIds($seasonId = null): array
    {
        if ($seasonId !== null && $seasonId !== '') {
            return [(int) $seasonId];
        }

        return $this->seasonService
            ->getAllSeasonDataForPortalAndProofing('ts_season_id')
            ->pluck('ts_season_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
