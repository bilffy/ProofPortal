<?php

namespace App\Services\Proofing;

use App\Models\Job;
use App\Models\School;
use App\Models\User;
use App\Helpers\RoleHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SchoolDashboardService
{
    public function __construct(
        protected StatusService $statusService,
        protected SeasonService $seasonService,
    ) {
    }

    /**
     * Metric counts for school dashboard (photography + proofing).
     * Proofing metrics include archived and deleted jobs.
     *
     * @return array{
     *     photography: int,
     *     photography_ready: int,
     *     photography_waiting: int,
     *     synced: int,
     *     opened: int,
     *     not_opened: int,
     *     before_proofing: int,
     *     after_starting: int,
     *     after_finished: int,
     *     in_catchup: int,
     *     archived: int,
     *     deleted: int,
     *     total_proofing: int
     * }
     */
    public function getMetrics(School $school, User $user, $seasonId = null): array
    {
        $now = Carbon::now();
        $seasonIds = $this->resolveSeasonIds($seasonId);
        $selectedSeasonOnly = $seasonId !== null && $seasonId !== '';
        $photographySeasonIds = $selectedSeasonOnly ? $seasonIds : null;

        $base = $this->proofingJobsQuery($school, $user->id, $seasonIds);

        $activeId = $this->statusService->active;
        $incompleteId = $this->statusService->incomplete;
        $modifiedId = $this->statusService->modified;
        $noneId = $this->statusService->none;
        $syncId = $this->statusService->sync;
        $archivedId = $this->statusService->archived;

        $metricsQuery = clone $base;
        $metricsQuery->getQuery()->columns = null;

        $row = $metricsQuery
            ->selectRaw(
                'COUNT(*) as total_proofing,
                SUM(CASE WHEN jobs.jobsync_status_id = ? THEN 1 ELSE 0 END) as synced,
                SUM(CASE WHEN jobs.job_status_id IN (?, ?, ?) THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN jobs.job_status_id = ? THEN 1 ELSE 0 END) as not_opened,
                SUM(CASE WHEN jobs.proof_start IS NULL OR jobs.proof_start > ? THEN 1 ELSE 0 END) as before_proofing,
                SUM(CASE WHEN jobs.proof_start IS NOT NULL AND jobs.proof_due IS NOT NULL AND jobs.proof_start <= ? AND jobs.proof_due >= ? THEN 1 ELSE 0 END) as after_starting,
                SUM(CASE WHEN jobs.proof_due IS NOT NULL AND jobs.proof_due < ? AND jobs.proof_catchup IS NULL THEN 1 ELSE 0 END) as after_finished,
                SUM(CASE WHEN jobs.proof_catchup IS NOT NULL AND jobs.is_in_catchup = 1 THEN 1 ELSE 0 END) as in_catchup,
                SUM(CASE WHEN jobs.job_status_id = ? THEN 1 ELSE 0 END) as archived',
                [
                    $syncId,
                    $modifiedId,
                    $incompleteId,
                    $activeId,
                    $noneId,
                    $now,
                    $now,
                    $now,
                    $now,
                    $archivedId,
                ]
            )
            ->first();

        $photography = $this->getPhotographyBreakdown($school, $photographySeasonIds);

        return [
            'photography' => $photography['total'],
            'photography_ready' => $photography['ready'],
            'photography_waiting' => $photography['waiting'],
            'synced' => (int) ($row->synced ?? 0),
            'opened' => (int) ($row->opened ?? 0),
            'not_opened' => (int) ($row->not_opened ?? 0),
            'before_proofing' => (int) ($row->before_proofing ?? 0),
            'after_starting' => (int) ($row->after_starting ?? 0),
            'after_finished' => (int) ($row->after_finished ?? 0),
            'in_catchup' => (int) ($row->in_catchup ?? 0),
            'archived' => (int) ($row->archived ?? 0),
            // School-wide deleted count (not limited to jobs assigned to the current user)
            'deleted' => $this->getDeletedJobsCount($school, $seasonIds),
            'total_proofing' => (int) ($row->total_proofing ?? 0),
        ];
    }

    /**
     * Deleted jobs for the school/season(s).
     * Deleted jobs are stored with show_proofing = null.
     *
     * @param  array<int>|null  $seasonIds
     */
    public function getDeletedJobsCount(School $school, ?array $seasonIds): int
    {
        $query = Job::query()
            ->where('jobs.job_status_id', $this->statusService->deleted)
            ->whereNull('jobs.show_proofing');

        $this->constrainJobsToSchool($query, $school);

        return $query
            ->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds))
            ->count();
    }

    /**
     * Photography jobs (show_portal = 1):
     * - ready: download_available_date is set (images can be viewed)
     * - waiting: download_available_date is null (still in lab / not ready yet)
     *
     * @param  array<int>|null  $seasonIds
     * @return array{total: int, ready: int, waiting: int}
     */
    public function getPhotographyBreakdown(School $school, ?array $seasonIds = null): array
    {
        $query = $this->photographyJobsQuery($school, $seasonIds);
        $query->getQuery()->columns = null;

        $row = $query
            ->selectRaw(
                'COUNT(*) as total,
                SUM(CASE WHEN jobs.download_available_date IS NOT NULL THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN jobs.download_available_date IS NULL THEN 1 ELSE 0 END) as waiting'
            )
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'ready' => (int) ($row->ready ?? 0),
            'waiting' => (int) ($row->waiting ?? 0),
        ];
    }

    /**
     * Jobs available in photography to view images: show_portal = 1 and download_available_date is set.
     *
     * @param  array<int>|null  $seasonIds
     */
    public function getPhotographyJobsCount(School $school, ?array $seasonIds = null): int
    {
        return $this->getPhotographyBreakdown($school, $seasonIds)['ready'];
    }

    /**
     * @param  array<int>|null  $seasonIds
     */
    protected function photographyJobsQuery(School $school, ?array $seasonIds = null): Builder
    {
        $query = Job::query()->where('jobs.show_portal', 1);
        $this->constrainJobsToSchool($query, $school);

        return $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));
    }

    /**
     * @param  array<int>|null  $seasonIds
     */
    protected function proofingJobsQuery(School $school, int $userId, ?array $seasonIds): Builder
    {
        $query = Job::query()
            ->where('jobs.show_proofing', 1)
            ->whereHas('users', fn (Builder $q) => $q->where('users.id', $userId));

        $this->constrainJobsToSchool($query, $school);

        return $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));
    }

    /**
     * Prefer jobs.school_id; fall back to matching ts_schoolkey when school_id is unset.
     */
    protected function constrainJobsToSchool(Builder $query, School $school): void
    {
        $schoolKey = $school->schoolkey;

        $query->where(function (Builder $q) use ($school, $schoolKey) {
            $q->where('jobs.school_id', $school->id);

            if ($schoolKey !== null && $schoolKey !== '') {
                $q->orWhere(function (Builder $unassigned) use ($schoolKey) {
                    $unassigned->whereNull('jobs.school_id')
                        ->where('jobs.ts_schoolkey', $schoolKey);
                });
            }
        });
    }

    /**
     * Users linked to the school by role (School Admin, Photo Coordinator, Teacher).
     *
     * @return array{
     *     school_admin: int,
     *     photo_coordinator: int,
     *     teacher: int,
     *     total: int
     * }
     */
    public function getSchoolRoleUserCounts(int $schoolId): array
    {
        $roleMap = [
            RoleHelper::ROLE_SCHOOL_ADMIN => 'school_admin',
            RoleHelper::ROLE_PHOTO_COORDINATOR => 'photo_coordinator',
            RoleHelper::ROLE_TEACHER => 'teacher',
        ];

        $counts = array_fill_keys(array_values($roleMap), 0);

        $rows = User::query()
            ->role(array_keys($roleMap))
            ->whereIn('status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
            ->whereHas('schools', fn (Builder $q) => $q->where('schools.id', $schoolId))
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', array_keys($roleMap))
            ->groupBy('roles.name')
            ->selectRaw('roles.name as role_name, COUNT(DISTINCT users.id) as total')
            ->pluck('total', 'role_name');

        foreach ($roleMap as $roleName => $key) {
            $counts[$key] = (int) ($rows[$roleName] ?? 0);
        }

        return [
            'school_admin' => $counts['school_admin'],
            'photo_coordinator' => $counts['photo_coordinator'],
            'teacher' => $counts['teacher'],
            'total' => array_sum($counts),
        ];
    }

    /**
     * Recent activity feed for the school dashboard:
     * - Jobs that completed proofing in the last 7 days (proof_due)
     * - Jobs due to proof in the next 7 days (proof_due)
     * - Jobs synced by the current user for this school
     * - New users created for this school
     *
     * @return array<int, array{title: string, description: string, time: string, icon: string, color: string}>
     */
    public function getRecentActivity(School $school, User $user, int $limit = 12): array
    {
        $items = collect();
        $now = Carbon::now();
        $last7Days = $now->copy()->subDays(7);
        $next7Days = $now->copy()->addDays(7);
        $deletedId = $this->statusService->deleted;
        $syncId = $this->statusService->sync;

        $schoolProofingJobs = function () use ($school, $deletedId): Builder {
            $query = Job::query()
                ->where('jobs.show_proofing', 1)
                ->where(function (Builder $statusQuery) use ($deletedId) {
                    $statusQuery->whereNull('jobs.job_status_id')
                        ->orWhere('jobs.job_status_id', '!=', $deletedId);
                });

            $this->constrainJobsToSchool($query, $school);

            return $query;
        };

        $jobLabel = static function (Job $job): string {
            return (string) ($job->ts_jobname ?: $job->ts_jobkey ?: 'Untitled job');
        };

        // 1) Completed proofing in the last 7 days
        $completedJobs = $schoolProofingJobs()
            ->whereNotNull('jobs.proof_due')
            ->where('jobs.proof_due', '>=', $last7Days)
            ->where('jobs.proof_due', '<=', $now)
            ->orderByDesc('jobs.proof_due')
            ->limit(10)
            ->get(['jobs.ts_jobname', 'jobs.ts_jobkey', 'jobs.proof_due']);

        foreach ($completedJobs as $job) {
            $items->push([
                'title' => 'Job completed proofing',
                'type' => 'Job completed proofing',
                'description' => $jobLabel($job),
                'at' => $job->proof_due,
                'icon' => 'check',
                'color' => '#4cc0ad',
            ]);
        }

        // 2) Jobs to proof in the next 7 days
        $upcomingJobs = $schoolProofingJobs()
            ->whereNotNull('jobs.proof_start')
            ->where('jobs.proof_due', '>', $now)
            ->where('jobs.proof_start', '<=', $next7Days)
            ->orderBy('jobs.proof_start')
            ->limit(10)
            ->get(['jobs.ts_jobname', 'jobs.ts_jobkey', 'jobs.proof_start']);

        foreach ($upcomingJobs as $job) {
            $items->push([
                'title' => 'Job to proof',
                'type' => 'Job to proof',
                'description' => $jobLabel($job),
                'at' => $job->proof_start,
                'icon' => 'calendar',
                'color' => '#4cc0ad',
            ]);
        }


        // 4) New users created for this school
        $schoolUsers = User::query()
            ->with('roles:id,name')
            ->whereIn('status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
            ->whereHas('schools', fn (Builder $q) => $q->where('schools.id', $school->id))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'firstname', 'lastname', 'email', 'created_at']);

        foreach ($schoolUsers as $schoolUser) {
            $name = trim(($schoolUser->firstname ?? '') . ' ' . ($schoolUser->lastname ?? ''));
            if ($name === '') {
                $name = $schoolUser->email ?? 'User account created';
            }

            $roleName = $schoolUser->getRoleNames()->first();
            $title = $roleName
                ? 'New ' . $roleName . ' created'
                : 'New user created';

            $items->push([
                'title' => $title,
                'type' => 'New user created',
                'description' => $name,
                'at' => $schoolUser->created_at,
                'icon' => 'user',
                'color' => '#4cc0ad',
            ]);
        }

        $byType = [
            'Job to proof' => $items
                ->where('type', 'Job to proof')
                ->sortBy(fn ($item) => Carbon::parse($item['at'])->timestamp)
                ->values(),
            'Job completed proofing' => $items
                ->where('type', 'Job completed proofing')
                ->sortByDesc(fn ($item) => Carbon::parse($item['at'])->timestamp)
                ->values(),
            'New user created' => $items
                ->where('type', 'New user created')
                ->sortByDesc(fn ($item) => Carbon::parse($item['at'])->timestamp)
                ->values(),
        ];

        return collect($byType)
            ->flatten(1)
            ->take($limit)
            ->values()
            ->map(function (array $item) {
                $at = Carbon::parse($item['at']);

                return [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'time' => $at->diffForHumans(),
                    'icon' => $item['icon'],
                    'color' => '#4cc0ad',
                ];
            })
            ->all();
    }

    /**
     * @param  int|string|null  $seasonId
     * @return array<int>
     */
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
