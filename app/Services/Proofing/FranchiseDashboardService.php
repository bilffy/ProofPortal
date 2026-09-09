<?php

namespace App\Services\Proofing;

use App\Helpers\RoleHelper;
use App\Helpers\SchoolLogoHelper;
use App\Models\Job;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
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
     * Franchise-wide metrics (same shape as school dashboard).
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
     *     total_proofing: int,
     *     schools_count: int
     * }
     */
    public function getMetrics(User $user, $seasonId = null): array
    {
        $schools = $this->getFranchiseSchools($user);
        $schoolIds = $schools->pluck('id')->map(fn ($id) => (int) $id)->all();
        $schoolKeys = $schools->pluck('schoolkey')
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $empty = [
            'photography' => 0,
            'photography_ready' => 0,
            'photography_waiting' => 0,
            'synced' => 0,
            'opened' => 0,
            'not_opened' => 0,
            'before_proofing' => 0,
            'after_starting' => 0,
            'after_finished' => 0,
            'in_catchup' => 0,
            'archived' => 0,
            'deleted' => 0,
            'total_proofing' => 0,
            'schools_count' => count($schoolIds),
        ];

        if (empty($schoolIds)) {
            return $empty;
        }

        $now = Carbon::now();
        $seasonIds = $this->resolveSeasonIds($seasonId);
        $selectedSeasonOnly = $seasonId !== null && $seasonId !== '';
        $photographySeasonIds = $selectedSeasonOnly ? $seasonIds : null;

        $activeId = $this->statusService->active;
        $incompleteId = $this->statusService->incomplete;
        $modifiedId = $this->statusService->modified;
        $noneId = $this->statusService->none;
        $syncId = $this->statusService->sync;
        $archivedId = $this->statusService->archived;

        $base = $this->proofingJobsQuery($schoolIds, $schoolKeys, $seasonIds);
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

        $photography = $this->getPhotographyBreakdown($schoolIds, $schoolKeys, $photographySeasonIds);

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
            // Deleted jobs have show_proofing = null (excluded from proofingJobsQuery)
            'deleted' => $this->getDeletedJobsCount($schoolIds, $schoolKeys, $seasonIds),
            'total_proofing' => (int) ($row->total_proofing ?? 0),
            'schools_count' => count($schoolIds),
        ];
    }

    /**
     * Deleted jobs for franchise schools/season(s).
     * Deleted jobs are stored with show_proofing = null.
     *
     * @param  array<int>  $schoolIds
     * @param  array<string>  $schoolKeys
     * @param  array<int>|null  $seasonIds
     */
    public function getDeletedJobsCount(array $schoolIds, array $schoolKeys, ?array $seasonIds): int
    {
        if (empty($schoolIds)) {
            return 0;
        }

        return $this->deletedJobsQuery($schoolIds, $schoolKeys, $seasonIds)->count();
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
     * @param  array<int>  $schoolIds
     * @param  array<string>  $schoolKeys
     * @param  array<int>|null  $seasonIds
     * @return array{total: int, ready: int, waiting: int}
     */
    public function getPhotographyBreakdown(array $schoolIds, array $schoolKeys, ?array $seasonIds = null): array
    {
        $query = Job::query()->where('jobs.show_portal', 1);
        $this->constrainJobsToSchools($query, $schoolIds, $schoolKeys);
        $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));
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
     * Role user counts across all franchise schools.
     *
     * @return array{school_admin: int, photo_coordinator: int, teacher: int, total: int}
     */
    public function getRoleUserCounts(User $user): array
    {
        $schoolIds = $this->getFranchiseSchools($user)->pluck('id')->all();
        if (empty($schoolIds)) {
            return [
                'school_admin' => 0,
                'photo_coordinator' => 0,
                'teacher' => 0,
                'total' => 0,
            ];
        }

        $countForRole = function (string $roleName) use ($schoolIds): int {
            return User::query()
                ->role($roleName)
                ->whereIn('status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
                ->whereHas('schools', fn (Builder $q) => $q->whereIn('schools.id', $schoolIds))
                ->count();
        };

        $schoolAdmin = $countForRole(RoleHelper::ROLE_SCHOOL_ADMIN);
        $photoCoordinator = $countForRole(RoleHelper::ROLE_PHOTO_COORDINATOR);
        $teacher = $countForRole(RoleHelper::ROLE_TEACHER);

        return [
            'school_admin' => $schoolAdmin,
            'photo_coordinator' => $photoCoordinator,
            'teacher' => $teacher,
            'total' => $schoolAdmin + $photoCoordinator + $teacher,
        ];
    }

    /**
     * Franchise-wide recent activity (same event types as school dashboard).
     *
     * @return array<int, array{title: string, description: string, time: string, icon: string, color: string}>
     */
    public function getRecentActivity(User $user, int $limit = 12): array
    {
        $schools = $this->getFranchiseSchools($user);
        $schoolIds = $schools->pluck('id')->map(fn ($id) => (int) $id)->all();
        $schoolKeys = $schools->pluck('schoolkey')
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $schoolNames = $schools->pluck('name', 'id');

        if (empty($schoolIds)) {
            return [];
        }

        $items = collect();
        $now = Carbon::now();
        $last7Days = $now->copy()->subDays(7);
        $next7Days = $now->copy()->addDays(7);
        $deletedId = $this->statusService->deleted;
        $syncId = $this->statusService->sync;
        $activityColor = '#00b4df';

        $franchiseProofingJobs = function () use ($schoolIds, $schoolKeys, $deletedId): Builder {
            $query = Job::query()
                ->where('jobs.show_proofing', 1)
                ->where(function (Builder $statusQuery) use ($deletedId) {
                    $statusQuery->whereNull('jobs.job_status_id')
                        ->orWhere('jobs.job_status_id', '!=', $deletedId);
                });

            $this->constrainJobsToSchools($query, $schoolIds, $schoolKeys);

            return $query;
        };

        $jobLabel = static function (Job $job) use ($schoolNames): string {
            $name = (string) ($job->ts_jobname ?: $job->ts_jobkey ?: 'Untitled job');
            $schoolName = $job->school_id ? ($schoolNames[$job->school_id] ?? null) : null;
            return $schoolName ? $schoolName . ' — ' . $name : $name;
        };

        $completedJobs = $franchiseProofingJobs()
            ->whereNotNull('jobs.proof_due')
            ->where('jobs.proof_due', '>=', $last7Days)
            ->where('jobs.proof_due', '<=', $now)
            ->orderByDesc('jobs.proof_due')
            ->limit(10)
            ->get(['jobs.ts_jobname', 'jobs.ts_jobkey', 'jobs.proof_due', 'jobs.school_id']);

        foreach ($completedJobs as $job) {
            $items->push([
                'title' => 'Job completed proofing',
                'type' => 'Job completed proofing',
                'description' => $jobLabel($job),
                'at' => $job->proof_due,
                'icon' => 'check',
                'color' => $activityColor,
            ]);
        }

        $upcomingJobs = $franchiseProofingJobs()
            ->whereNotNull('jobs.proof_start')
            ->where('jobs.proof_start', '>', $now)
            ->where('jobs.proof_start', '<=', $next7Days)
            ->orderBy('jobs.proof_start')
            ->limit(10)
            ->get(['jobs.ts_jobname', 'jobs.ts_jobkey', 'jobs.proof_start', 'jobs.school_id']);

        foreach ($upcomingJobs as $job) {
            $items->push([
                'title' => 'Job to proof',
                'type' => 'Job to proof',
                'description' => $jobLabel($job),
                'at' => $job->proof_start,
                'icon' => 'calendar',
                'color' => $activityColor,
            ]);
        }

        $syncedJobs = $franchiseProofingJobs()
            ->where('jobs.jobsync_status_id', $syncId)
            ->orderByDesc('jobs.updated_at')
            ->limit(10)
            ->get(['jobs.ts_jobname', 'jobs.ts_jobkey', 'jobs.updated_at', 'jobs.created_at', 'jobs.school_id']);

        foreach ($syncedJobs as $job) {
            $items->push([
                'title' => 'Job synced',
                'type' => 'Job synced',
                'description' => $jobLabel($job),
                'at' => $job->updated_at ?? $job->created_at,
                'icon' => 'refresh',
                'color' => $activityColor,
            ]);
        }

        $schoolUsers = User::query()
            ->with('roles:id,name')
            ->whereIn('status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
            ->whereHas('schools', fn (Builder $q) => $q->whereIn('schools.id', $schoolIds))
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
                'color' => $activityColor,
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
            'Job synced' => $items
                ->where('type', 'Job synced')
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
            ->map(function (array $item) use ($activityColor) {
                $at = Carbon::parse($item['at']);

                return [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'time' => $at->diffForHumans(),
                    'icon' => $item['icon'],
                    'color' => $activityColor,
                ];
            })
            ->all();
    }

    /**
     * Per-school performance rows for the franchise overview.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     schoolkey: string,
     *     suburb: string,
     *     url: string,
     *     photography: int,
     *     photography_ready: int,
     *     photography_waiting: int,
     *     synced: int,
     *     before_proofing: int,
     *     in_proofing: int,
     *     finished: int,
     *     catchup: int,
     *     total_proofing: int,
     *     users_added: int,
     *     stage_label: string
     * }>
     */
    public function getSchoolsPerformance(User $user, $seasonId = null): array
    {
        $schools = $this->getFranchiseSchools($user);
        if ($schools->isEmpty()) {
            return [];
        }

        $schoolIds = $schools->pluck('id')->map(fn ($id) => (int) $id)->all();
        $keyToIds = [];
        foreach ($schools as $school) {
            $key = trim((string) ($school->schoolkey ?? ''));
            if ($key !== '') {
                $keyToIds[$key][] = (int) $school->id;
            }
        }
        $schoolKeys = array_keys($keyToIds);

        $seasonIds = $this->resolveSeasonIds($seasonId);
        $selectedSeasonOnly = $seasonId !== null && $seasonId !== '';
        $photographySeasonIds = $selectedSeasonOnly ? $seasonIds : null;
        $now = Carbon::now();
        $syncId = $this->statusService->sync;

        $rows = [];
        foreach ($schools as $school) {
            $id = (int) $school->id;
            $rows[$id] = [
                'id' => $id,
                'name' => (string) $school->name,
                'schoolkey' => (string) ($school->schoolkey ?? ''),
                'suburb' => (string) ($school->suburb ?? ''),
                'url' => route('franchise.school-dashboard', ['hashedId' => $school->getCryptedIdAttribute()]),
                'photography' => 0,
                'photography_ready' => 0,
                'photography_waiting' => 0,
                'synced' => 0,
                'opened' => 0,
                'not_opened' => 0,
                'archived' => 0,
                'deleted' => 0,
                'before_proofing' => 0,
                'in_proofing' => 0,
                'finished' => 0,
                'catchup' => 0,
                'total_proofing' => 0,
                'users_added' => 0,
                'school_admin' => 0,
                'photo_coordinator' => 0,
                'teacher' => 0,
                'stage_label' => '—',
            ];
        }

        $resolveIds = function (?int $schoolId, ?string $schoolKey) use ($keyToIds): array {
            if ($schoolId !== null && $schoolId > 0) {
                return [(int) $schoolId];
            }
            $key = trim((string) $schoolKey);
            return $keyToIds[$key] ?? [];
        };

        $openedStatusIds = array_filter([
            (int) $this->statusService->modified,
            (int) $this->statusService->incomplete,
            (int) $this->statusService->active,
        ]);
        $noneId = (int) $this->statusService->none;
        $archivedId = (int) $this->statusService->archived;

        // Photography jobs
        $photoQuery = Job::query()->where('jobs.show_portal', 1);
        $this->constrainJobsToSchools($photoQuery, $schoolIds, $schoolKeys);
        $photoQuery->when(!empty($photographySeasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $photographySeasonIds));
        foreach ($photoQuery->get(['jobs.school_id', 'jobs.ts_schoolkey', 'jobs.download_available_date']) as $job) {
            foreach ($resolveIds($job->school_id !== null ? (int) $job->school_id : null, $job->ts_schoolkey) as $sid) {
                if (!isset($rows[$sid])) {
                    continue;
                }
                $rows[$sid]['photography']++;
                if ($job->download_available_date !== null) {
                    $rows[$sid]['photography_ready']++;
                } else {
                    $rows[$sid]['photography_waiting']++;
                }
            }
        }

        // Proofing jobs for franchise schools (all users)
        $proofingQuery = $this->proofingJobsQuery($schoolIds, $schoolKeys, $seasonIds);

        foreach ($proofingQuery->get([
            'jobs.school_id',
            'jobs.ts_schoolkey',
            'jobs.jobsync_status_id',
            'jobs.job_status_id',
            'jobs.proof_start',
            'jobs.proof_due',
            'jobs.proof_catchup',
            'jobs.is_in_catchup',
        ]) as $job) {
            foreach ($resolveIds($job->school_id !== null ? (int) $job->school_id : null, $job->ts_schoolkey) as $sid) {
                if (!isset($rows[$sid])) {
                    continue;
                }
                $rows[$sid]['total_proofing']++;
                $statusId = (int) ($job->job_status_id ?? 0);

                if ((int) $job->jobsync_status_id === (int) $syncId) {
                    $rows[$sid]['synced']++;
                }

                if (in_array($statusId, $openedStatusIds, true)) {
                    $rows[$sid]['opened']++;
                }
                if ($statusId === $noneId) {
                    $rows[$sid]['not_opened']++;
                }
                if ($statusId === $archivedId) {
                    $rows[$sid]['archived']++;
                }

                $bucket = $this->resolveStageBucket($job, $now);
                if ($bucket === 'before_proofing') {
                    $rows[$sid]['before_proofing']++;
                } elseif ($bucket === 'in_proofing') {
                    $rows[$sid]['in_proofing']++;
                } elseif ($bucket === 'finished') {
                    $rows[$sid]['finished']++;
                } elseif ($bucket === 'catchup') {
                    $rows[$sid]['catchup']++;
                }
            }
        }

        // Deleted jobs have show_proofing = null (not in proofingJobsQuery)
        foreach ($this->deletedJobsQuery($schoolIds, $schoolKeys, $seasonIds)->get(['jobs.school_id', 'jobs.ts_schoolkey']) as $job) {
            foreach ($resolveIds($job->school_id !== null ? (int) $job->school_id : null, $job->ts_schoolkey) as $sid) {
                if (!isset($rows[$sid])) {
                    continue;
                }
                $rows[$sid]['deleted']++;
            }
        }

        $usersBySchool = $this->getUsersByRolePerSchoolIds($schoolIds);
        foreach ($rows as $id => &$row) {
            $roleCounts = $usersBySchool[$id] ?? [
                'school_admin' => 0,
                'photo_coordinator' => 0,
                'teacher' => 0,
                'total' => 0,
            ];
            $row['users_added'] = (int) $roleCounts['total'];
            $row['school_admin'] = (int) $roleCounts['school_admin'];
            $row['photo_coordinator'] = (int) $roleCounts['photo_coordinator'];
            $row['teacher'] = (int) $roleCounts['teacher'];
            $stageCounts = [
                'before_proofing' => $row['before_proofing'],
                'in_proofing' => $row['in_proofing'],
                'finished' => $row['finished'],
                'catchup' => $row['catchup'],
            ];
            $row['stage_label'] = $this->formatStageLabel($stageCounts, array_sum($stageCounts));
        }
        unset($row);

        return collect($rows)
            ->sortByDesc(fn ($row) => ($row['in_proofing'] * 1000) + $row['synced'] + $row['photography_ready'])
            ->values()
            ->all();
    }

    /**
     * @param  array<int>  $schoolIds
     * @return array<int, array{school_admin: int, photo_coordinator: int, teacher: int, total: int}>
     */
    protected function getUsersByRolePerSchoolIds(array $schoolIds): array
    {
        $empty = [
            'school_admin' => 0,
            'photo_coordinator' => 0,
            'teacher' => 0,
            'total' => 0,
        ];

        $result = [];
        foreach ($schoolIds as $schoolId) {
            $result[(int) $schoolId] = $empty;
        }

        if (empty($schoolIds)) {
            return $result;
        }

        $roleMap = [
            RoleHelper::ROLE_SCHOOL_ADMIN => 'school_admin',
            RoleHelper::ROLE_PHOTO_COORDINATOR => 'photo_coordinator',
            RoleHelper::ROLE_TEACHER => 'teacher',
        ];

        $rows = DB::table('school_users')
            ->join('users', 'users.id', '=', 'school_users.user_id')
            ->join('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('school_users.school_id', $schoolIds)
            ->whereIn('roles.name', array_keys($roleMap))
            ->whereIn('users.status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
            ->groupBy('school_users.school_id', 'roles.name')
            ->select(
                'school_users.school_id',
                'roles.name as role_name',
                DB::raw('COUNT(DISTINCT users.id) as users_count')
            )
            ->get();

        foreach ($rows as $row) {
            $schoolId = (int) $row->school_id;
            $key = $roleMap[$row->role_name] ?? null;
            if ($key === null || !isset($result[$schoolId])) {
                continue;
            }
            $result[$schoolId][$key] = (int) $row->users_count;
        }

        foreach ($result as $schoolId => $counts) {
            $result[$schoolId]['total'] = $counts['school_admin'] + $counts['photo_coordinator'] + $counts['teacher'];
        }

        return $result;
    }

    /**
     * @param  array<int>  $schoolIds
     * @return array<int, int>
     */
    protected function getUsersAddedBySchoolIds(array $schoolIds): array
    {
        $byRole = $this->getUsersByRolePerSchoolIds($schoolIds);
        $result = [];
        foreach ($schoolIds as $schoolId) {
            $result[(int) $schoolId] = (int) ($byRole[(int) $schoolId]['total'] ?? 0);
        }

        return $result;
    }

    protected function resolveStageBucket(object $job, Carbon $now): ?string
    {
        if ($job->proof_catchup !== null && (int) $job->is_in_catchup === 1) {
            return 'catchup';
        }

        if ($job->proof_start !== null && $job->proof_due !== null) {
            $start = Carbon::parse($job->proof_start);
            $due = Carbon::parse($job->proof_due);
            if ($start->lte($now) && $due->gte($now)) {
                return 'in_proofing';
            }
        }

        if ($job->proof_due !== null && Carbon::parse($job->proof_due)->lt($now) && $job->proof_catchup === null) {
            return 'finished';
        }

        if ($job->proof_start === null || Carbon::parse($job->proof_start)->gt($now)) {
            return 'before_proofing';
        }

        return null;
    }

    /**
     * @param  array{before_proofing: int, in_proofing: int, finished: int, catchup: int}  $counts
     */
    protected function formatStageLabel(array $counts, int $total): string
    {
        if ($total === 0) {
            return 'No proofing jobs';
        }

        $priority = [
            'in_proofing' => 'In Proofing',
            'catchup' => 'Catchup',
            'before_proofing' => 'Before Proofing',
            'finished' => 'Finished',
        ];

        $parts = [];
        foreach ($priority as $key => $label) {
            if (($counts[$key] ?? 0) > 0) {
                $parts[] = $label . ' (' . $counts[$key] . ')';
            }
        }

        return implode(' · ', $parts);
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
     * Unified franchise dashboard: aggregates school-dashboard-style metrics
     * across all franchise schools, with school-wise modal breakdowns.
     *
     * @param  int|string|null  $seasonId
     * @return array{
     *     metrics: array<string, int>,
     *     roleCounts: array{school_admin: int, photo_coordinator: int, teacher: int, total: int},
     *     breakdowns: array<string, mixed>,
     *     recentActivity: array<int, array{title: string, description: string, time: string, icon: string, color: string}>
     * }
     */
    public function getUnifiedDashboard(User $user, $seasonId = null): array
    {
        $schools = $this->getFranchiseSchools($user);
        if ($schools->isNotEmpty()) {
            $schools->load('franchises');
        }
        $schoolIds = $schools->pluck('id')->map(fn ($id) => (int) $id)->all();
        $keyToIds = [];
        foreach ($schools as $school) {
            $key = trim((string) ($school->schoolkey ?? ''));
            if ($key !== '') {
                $keyToIds[$key][] = (int) $school->id;
            }
        }
        $schoolKeys = array_keys($keyToIds);

        $emptyMetrics = [
            'photography_total' => 0,
            'photography_configured' => 0,
            'photography_not_configured' => 0,
            'active_proofing' => 0,
            'total_users' => 0,
            'schools_count' => count($schoolIds),
            'synced' => 0,
            'status_opened' => 0,
            'status_not_opened' => 0,
            'status_completed' => 0,
            'status_archived' => 0,
            'status_deleted' => 0,
            'stage_not_started' => 0,
            'stage_active' => 0,
            'stage_completed' => 0,
            'stage_catchup' => 0,
            'folders_total' => 0,
            'folders_completed' => 0,
            'folders_unlocked_modified' => 0,
        ];

        $emptyRoles = [
            'school_admin' => 0,
            'photo_coordinator' => 0,
            'teacher' => 0,
            'total' => 0,
        ];

        if (empty($schoolIds)) {
            return [
                'metrics' => $emptyMetrics,
                'roleCounts' => $emptyRoles,
                'breakdowns' => [
                    'schools' => [],
                    'photography' => [],
                    'active_proofing' => [],
                    'users' => [],
                    'proofing_status' => [],
                    'stages' => [],
                    'folders' => [],
                    'recent_activity' => [],
                ],
                'recentActivity' => [],
            ];
        }

        $seasonIds = $this->resolveSeasonIds($seasonId);
        $selectedSeasonOnly = $seasonId !== null && $seasonId !== '';
        $photographySeasonIds = $selectedSeasonOnly ? $seasonIds : null;
        $now = Carbon::now();

        $modifiedId = (int) $this->statusService->modified;
        $unlockedId = (int) $this->statusService->unlocked;
        $incompleteId = (int) $this->statusService->incomplete;
        $activeId = (int) $this->statusService->active;
        $noneId = (int) $this->statusService->none;
        $completedId = (int) $this->statusService->completed;
        $archivedId = (int) $this->statusService->archived;
        $deletedId = (int) $this->statusService->deleted;
        $syncId = (int) $this->statusService->sync;

        $activeProofingStatusIds = array_values(array_filter([$modifiedId, $unlockedId, $incompleteId]));
        $openedStatusIds = array_values(array_filter(array_merge($activeProofingStatusIds, [$activeId])));

        $resolveIds = function (?int $schoolId, ?string $schoolKey) use ($keyToIds): array {
            if ($schoolId !== null && $schoolId > 0) {
                return [(int) $schoolId];
            }
            $key = trim((string) $schoolKey);

            return $keyToIds[$key] ?? [];
        };

        $rows = [];
        foreach ($schools as $school) {
            $id = (int) $school->id;
            $rows[$id] = [
                'id' => $id,
                'name' => (string) $school->name,
                'schoolkey' => (string) ($school->schoolkey ?? ''),
                'suburb' => (string) ($school->suburb ?? ''),
                'postcode' => (string) ($school->postcode ?? ''),
                'state' => (string) ($school->state ?? ''),
                'school_logo' => (string) ($school->school_logo ?? ''),
                'url' => route('franchise.school-dashboard', ['hashedId' => $school->getCryptedIdAttribute()]),
                'photography_configured' => 0,
                'photography_not_configured' => 0,
                'photography_total' => 0,
                'photography_job_details' => [],
                'active_proofing' => 0,
                'active_proofing_job_details' => [],
                'synced' => 0,
                'status_opened' => 0,
                'status_not_opened' => 0,
                'status_completed' => 0,
                'status_archived' => 0,
                'status_deleted' => 0,
                'stage_not_started' => 0,
                'stage_active' => 0,
                'stage_completed' => 0,
                'stage_catchup' => 0,
                'folders_total' => 0,
                'folders_completed' => 0,
                'folders_unlocked_modified' => 0,
                'school_admin' => 0,
                'photo_coordinator' => 0,
                'teacher' => 0,
                'users_total' => 0,
                'completed_last_7_jobs' => [],
                'starting_next_7_jobs' => [],
                'status_jobs' => [
                    'synced' => [],
                    'opened' => [],
                    'not_opened' => [],
                    'completed' => [],
                    'archived' => [],
                    'deleted' => [],
                ],
                'status_job_details' => [],
                'stage_jobs' => [
                    'not_started' => [],
                    'active' => [],
                    'completed' => [],
                    'catchup' => [],
                ],
                'stage_job_details' => [],
                'folder_jobs' => [
                    'total' => [],
                    'unlocked_modified' => [],
                    'completed' => [],
                ],
                'folder_job_details' => [],
            ];
        }

        // Photography jobs (show_portal = 1):
        // Configured = download_available_date is not null AND at least one folder has is_visible_for_portrait = 1.
        // Not configured = download_available_date is not null AND no folder has is_visible_for_portrait = 1.
        $photoQuery = Job::query()
            ->where('jobs.show_portal', 1)
            ->whereNotNull('jobs.download_available_date')
            ->select(['jobs.ts_job_id', 'jobs.ts_jobkey', 'jobs.ts_jobname', 'jobs.school_id', 'jobs.ts_schoolkey']);
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
            $jobId = (int) $job->ts_job_id;
            $jobLabel = $this->formatJobLabel($job);
            foreach ($resolveIds($job->school_id !== null ? (int) $job->school_id : null, $job->ts_schoolkey) as $sid) {
                if (!isset($rows[$sid])) {
                    continue;
                }
                $configured = isset($portraitVisibleJobIds[$jobId]);
                $rows[$sid]['photography_total']++;
                if (!isset($rows[$sid]['photography_job_details'][$jobId])) {
                    $rows[$sid]['photography_job_details'][$jobId] = [
                        'label' => $jobLabel,
                        'configured' => 0,
                        'not_configured' => 0,
                    ];
                }
                if ($configured) {
                    $rows[$sid]['photography_configured']++;
                    $rows[$sid]['photography_job_details'][$jobId]['configured'] = 1;
                } else {
                    $rows[$sid]['photography_not_configured']++;
                    $rows[$sid]['photography_job_details'][$jobId]['not_configured'] = 1;
                }
            }
        }

        // Proofing jobs
        $proofingJobs = $this->proofingJobsQuery($schoolIds, $schoolKeys, $seasonIds)
            ->get([
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.school_id',
                'jobs.ts_schoolkey',
                'jobs.jobsync_status_id',
                'jobs.job_status_id',
                'jobs.proof_start',
                'jobs.proof_due',
                'jobs.proof_catchup',
                'jobs.is_in_catchup',
                'jobs.updated_at',
            ]);

        $activeProofingJobMeta = [];

        foreach ($proofingJobs as $job) {
            $jobId = (int) $job->ts_job_id;
            $jobLabel = $this->formatJobLabel($job);
            $statusId = (int) ($job->job_status_id ?? 0);
            $isSynced = (int) $job->jobsync_status_id === $syncId;
            $isActiveProofing = in_array($statusId, $activeProofingStatusIds, true);

            $stage = $this->resolveUnifiedStageBucket($job, $now, $activeProofingStatusIds, $completedId);

            foreach ($resolveIds($job->school_id !== null ? (int) $job->school_id : null, $job->ts_schoolkey) as $sid) {
                if (!isset($rows[$sid])) {
                    continue;
                }

                if ($isActiveProofing) {
                    $rows[$sid]['active_proofing']++;
                    $activeProofingJobMeta[$jobId] = [
                        'sid' => $sid,
                        'label' => $jobLabel,
                    ];
                    $rows[$sid]['active_proofing_job_details'][$jobId] = [
                        'label' => $jobLabel,
                        'active' => 1,
                    ];
                }

                if ($isSynced) {
                    $rows[$sid]['synced']++;
                    $rows[$sid]['status_jobs']['synced'][$jobId] = $jobLabel;
                    if (!isset($rows[$sid]['status_job_details'][$jobId])) {
                        $rows[$sid]['status_job_details'][$jobId] = [
                            'label' => $jobLabel,
                            'synced' => 0,
                            'opened' => 0,
                            'not_opened' => 0,
                            'completed' => 0,
                            'archived' => 0,
                            'deleted' => 0,
                        ];
                    }
                    $rows[$sid]['status_job_details'][$jobId]['synced'] = 1;
                    if (in_array($statusId, $openedStatusIds, true)) {
                        $rows[$sid]['status_opened']++;
                        $rows[$sid]['status_jobs']['opened'][$jobId] = $jobLabel;
                        $rows[$sid]['status_job_details'][$jobId]['opened'] = 1;
                    } elseif ($statusId === $noneId) {
                        $rows[$sid]['status_not_opened']++;
                        $rows[$sid]['status_jobs']['not_opened'][$jobId] = $jobLabel;
                        $rows[$sid]['status_job_details'][$jobId]['not_opened'] = 1;
                    } elseif ($statusId === $completedId) {
                        $rows[$sid]['status_completed']++;
                        $rows[$sid]['status_jobs']['completed'][$jobId] = $jobLabel;
                        $rows[$sid]['status_job_details'][$jobId]['completed'] = 1;
                    } elseif ($statusId === $archivedId) {
                        $rows[$sid]['status_archived']++;
                        $rows[$sid]['status_jobs']['archived'][$jobId] = $jobLabel;
                        $rows[$sid]['status_job_details'][$jobId]['archived'] = 1;
                    }
                }

                if ($stage !== null) {
                    $rows[$sid][$stage]++;
                    $stageKey = str_replace('stage_', '', $stage);
                    $rows[$sid]['stage_jobs'][$stageKey][$jobId] = [
                        'label' => $jobLabel,
                        'proof_start' => $job->proof_start,
                        'proof_due' => $job->proof_due,
                        'proof_catchup' => $job->proof_catchup,
                    ];
                    if (!isset($rows[$sid]['stage_job_details'][$jobId])) {
                        $rows[$sid]['stage_job_details'][$jobId] = [
                            'label' => $jobLabel,
                            'not_started' => 0,
                            'active' => 0,
                            'completed' => 0,
                            'catchup' => 0,
                        ];
                    }
                    $rows[$sid]['stage_job_details'][$jobId][$stageKey] = 1;
                }

                // Recent activity grouping
                if ($statusId === $completedId && $job->updated_at) {
                    $updated = Carbon::parse($job->updated_at);
                    if ($updated->gte($now->copy()->subDays(7))) {
                        $rows[$sid]['completed_last_7_jobs'][$jobId] = [
                            'label' => $jobLabel,
                            'proof_due' => $job->proof_due,
                        ];
                    }
                }
                if ($job->proof_start !== null) {
                    $start = Carbon::parse($job->proof_start);
                    if ($start->gt($now) && $start->lte($now->copy()->addDays(7))) {
                        $rows[$sid]['starting_next_7_jobs'][$jobId] = [
                            'label' => $jobLabel,
                            'proof_start' => $job->proof_start,
                        ];
                    }
                }
            }
        }

        // Deleted jobs (show_proofing null)
        foreach ($this->deletedJobsQuery($schoolIds, $schoolKeys, $seasonIds)
            ->get(['jobs.ts_job_id', 'jobs.ts_jobkey', 'jobs.ts_jobname', 'jobs.school_id', 'jobs.ts_schoolkey']) as $job) {
            $jobId = (int) $job->ts_job_id;
            $jobLabel = $this->formatJobLabel($job);
            foreach ($resolveIds($job->school_id !== null ? (int) $job->school_id : null, $job->ts_schoolkey) as $sid) {
                if (!isset($rows[$sid])) {
                    continue;
                }
                $rows[$sid]['status_deleted']++;
                $rows[$sid]['status_jobs']['deleted'][$jobId] = $jobLabel;
                if (!isset($rows[$sid]['status_job_details'][$jobId])) {
                    $rows[$sid]['status_job_details'][$jobId] = [
                        'label' => $jobLabel,
                        'synced' => 0,
                        'opened' => 0,
                        'not_opened' => 0,
                        'completed' => 0,
                        'archived' => 0,
                        'deleted' => 0,
                    ];
                }
                $rows[$sid]['status_job_details'][$jobId]['deleted'] = 1;
            }
        }

        // Folder status for active proofing jobs
        if (!empty($activeProofingJobMeta)) {
            $folders = \App\Models\Folder::query()
                ->withoutGlobalScopes()
                ->whereIn('ts_job_id', array_keys($activeProofingJobMeta))
                ->where('is_visible_for_proofing', 1)
                ->get(['ts_job_id', 'status_id']);

            foreach ($folders as $folder) {
                $jobId = (int) $folder->ts_job_id;
                $meta = $activeProofingJobMeta[$jobId] ?? null;
                if ($meta === null) {
                    continue;
                }
                $sid = $meta['sid'];
                if (!isset($rows[$sid])) {
                    continue;
                }
                if (!isset($rows[$sid]['folder_job_details'][$jobId])) {
                    $rows[$sid]['folder_job_details'][$jobId] = [
                        'label' => $meta['label'],
                        'total' => 0,
                        'unlocked_modified' => 0,
                        'completed' => 0,
                    ];
                }
                $rows[$sid]['folders_total']++;
                $rows[$sid]['folder_job_details'][$jobId]['total']++;
                $rows[$sid]['folder_jobs']['total'][$jobId] = $meta['label'];
                $folderStatus = (int) ($folder->status_id ?? 0);
                if ($folderStatus === $completedId) {
                    $rows[$sid]['folders_completed']++;
                    $rows[$sid]['folder_job_details'][$jobId]['completed']++;
                    $rows[$sid]['folder_jobs']['completed'][$jobId] = $meta['label'];
                }
                if (in_array($folderStatus, [$unlockedId, $modifiedId], true)) {
                    $rows[$sid]['folders_unlocked_modified']++;
                    $rows[$sid]['folder_job_details'][$jobId]['unlocked_modified']++;
                    $rows[$sid]['folder_jobs']['unlocked_modified'][$jobId] = $meta['label'];
                }
            }
        }

        // Users by role per school
        $usersBySchool = $this->getUsersByRolePerSchoolIds($schoolIds);
        foreach ($usersBySchool as $sid => $counts) {
            if (!isset($rows[$sid])) {
                continue;
            }
            $rows[$sid]['school_admin'] = (int) $counts['school_admin'];
            $rows[$sid]['photo_coordinator'] = (int) $counts['photo_coordinator'];
            $rows[$sid]['teacher'] = (int) $counts['teacher'];
            $rows[$sid]['users_total'] = (int) $counts['total'];
        }

        $metrics = $emptyMetrics;
        $metrics['schools_count'] = count($schoolIds);
        foreach ($rows as $row) {
            $metrics['photography_total'] += $row['photography_total'];
            $metrics['photography_configured'] += $row['photography_configured'];
            $metrics['photography_not_configured'] += $row['photography_not_configured'];
            $metrics['active_proofing'] += $row['active_proofing'];
            $metrics['synced'] += $row['synced'];
            $metrics['status_opened'] += $row['status_opened'];
            $metrics['status_not_opened'] += $row['status_not_opened'];
            $metrics['status_completed'] += $row['status_completed'];
            $metrics['status_archived'] += $row['status_archived'];
            $metrics['status_deleted'] += $row['status_deleted'];
            $metrics['stage_not_started'] += $row['stage_not_started'];
            $metrics['stage_active'] += $row['stage_active'];
            $metrics['stage_completed'] += $row['stage_completed'];
            $metrics['stage_catchup'] += $row['stage_catchup'];
            $metrics['folders_total'] += $row['folders_total'];
            $metrics['folders_completed'] += $row['folders_completed'];
            $metrics['folders_unlocked_modified'] += $row['folders_unlocked_modified'];
            $metrics['total_users'] += $row['users_total'];
        }

        $roleCounts = [
            'school_admin' => (int) collect($rows)->sum('school_admin'),
            'photo_coordinator' => (int) collect($rows)->sum('photo_coordinator'),
            'teacher' => (int) collect($rows)->sum('teacher'),
            'total' => $metrics['total_users'],
        ];

        $sorted = collect($rows)->sortBy('name')->values();
        $jobValues = static fn (array $jobs): array => array_values($jobs);

        $logoUrlCache = [];

        $breakdowns = [
            'schools' => $sorted
                ->map(function ($r) use ($schools, &$logoUrlCache) {
                    /** @var School|null $school */
                    $school = $schools->firstWhere('id', $r['id']);
                    $logoFilename = trim((string) ($r['school_logo'] ?? ''));
                    $logoUrl = null;

                    if ($school && $logoFilename !== '') {
                        $cacheKey = $school->id . '|' . $logoFilename;
                        if (!array_key_exists($cacheKey, $logoUrlCache)) {
                            try {
                                $logoUrlCache[$cacheKey] = SchoolLogoHelper::publicUrl($school, $logoFilename);
                            } catch (\Throwable) {
                                $logoUrlCache[$cacheKey] = null;
                            }
                        }
                        $logoUrl = $logoUrlCache[$cacheKey];
                    }

                    return [
                        'school' => $r['name'],
                        'schoolkey' => $r['schoolkey'],
                        'suburb' => $r['suburb'],
                        'postcode' => $r['postcode'],
                        'state' => $r['state'],
                        'school_logo' => $logoUrl,
                        'url' => $r['url'],
                    ];
                })
                ->values()
                ->all(),
            'photography' => $sorted
                ->filter(fn ($r) => count($r['photography_job_details']) > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'configured' => $r['photography_configured'],
                    'not_configured' => $r['photography_not_configured'],
                    'total' => $r['photography_total'],
                    'jobs' => [
                        'configured' => collect($r['photography_job_details'])
                            ->filter(fn ($job) => (int) $job['configured'] === 1)
                            ->pluck('label')
                            ->sort()
                            ->values()
                            ->all(),
                        'not_configured' => collect($r['photography_job_details'])
                            ->filter(fn ($job) => (int) $job['not_configured'] === 1)
                            ->pluck('label')
                            ->sort()
                            ->values()
                            ->all(),
                    ],
                ])
                ->values()
                ->all(),
            'active_proofing' => $sorted
                ->filter(fn ($r) => count($r['active_proofing_job_details']) > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'count' => $r['active_proofing'],
                    'jobs' => collect($r['active_proofing_job_details'])
                        ->pluck('label')
                        ->sort()
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'users' => $sorted
                ->filter(fn ($r) => (int) $r['users_total'] > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'school_admin' => (int) $r['school_admin'],
                    'photo_coordinator' => (int) $r['photo_coordinator'],
                    'teacher' => (int) $r['teacher'],
                    'total' => (int) $r['users_total'],
                ])
                ->values()
                ->all(),
            'proofing_status' => $sorted
                ->filter(fn ($r) => count($r['status_job_details']) > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'synced' => $r['synced'],
                    'opened' => $r['status_opened'],
                    'not_opened' => $r['status_not_opened'],
                    'completed' => $r['status_completed'],
                    'archived' => $r['status_archived'],
                    'deleted' => $r['status_deleted'],
                    'jobs' => [
                        'opened' => collect($jobValues($r['status_jobs']['opened']))->sort()->values()->all(),
                        'not_opened' => collect($jobValues($r['status_jobs']['not_opened']))->sort()->values()->all(),
                        'completed' => collect($jobValues($r['status_jobs']['completed']))->sort()->values()->all(),
                        'archived' => collect($jobValues($r['status_jobs']['archived']))->sort()->values()->all(),
                        'deleted' => collect($jobValues($r['status_jobs']['deleted']))->sort()->values()->all(),
                    ],
                ])
                ->values()
                ->all(),
            'stages' => $sorted
                ->filter(fn ($r) => count($r['stage_job_details']) > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'not_started' => $r['stage_not_started'],
                    'active' => $r['stage_active'],
                    'completed' => $r['stage_completed'],
                    'catchup' => $r['stage_catchup'],
                    'jobs' => [
                        'not_started' => $this->mapStageJobBreakdown($r['stage_jobs']['not_started'], 'not_started'),
                        'active' => $this->mapStageJobBreakdown($r['stage_jobs']['active'], 'active'),
                        'completed' => $this->mapStageJobBreakdown($r['stage_jobs']['completed'], 'completed'),
                        'catchup' => $this->mapStageJobBreakdown($r['stage_jobs']['catchup'], 'catchup'),
                    ],
                ])
                ->values()
                ->all(),
            'folders' => $sorted
                ->filter(fn ($r) => count($r['folder_job_details']) > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'total' => $r['folders_total'],
                    'completed' => $r['folders_completed'],
                    'unlocked_modified' => $r['folders_unlocked_modified'],
                    'jobs' => collect($r['folder_job_details'])
                        ->sortBy('label')
                        ->values()
                        ->map(fn ($job) => [
                            'label' => $job['label'],
                            'total' => (int) $job['total'],
                            'unlocked_modified' => (int) $job['unlocked_modified'],
                            'completed' => (int) $job['completed'],
                        ])
                        ->all(),
                ])
                ->values()
                ->all(),
            'recent_activity' => $sorted
                ->filter(fn ($r) => count($r['completed_last_7_jobs']) > 0 || count($r['starting_next_7_jobs']) > 0)
                ->map(fn ($r) => [
                    'school' => $r['name'],
                    'schoolkey' => $r['schoolkey'],
                    'url' => $r['url'],
                    'jobs' => [
                        'completed_last_7' => $this->mapRecentActivityJobs($r['completed_last_7_jobs'], 'completed_last_7'),
                        'starting_next_7' => $this->mapRecentActivityJobs($r['starting_next_7_jobs'], 'starting_next_7'),
                    ],
                ])
                ->values()
                ->all(),
        ];

        $recentActivity = [];
        foreach ($breakdowns['recent_activity'] as $group) {
            $completedJobs = $group['jobs']['completed_last_7'] ?? [];
            $startingJobs = $group['jobs']['starting_next_7'] ?? [];

            if (count($completedJobs) > 0) {
                $labels = array_map(fn ($job) => $job['label'] ?? '', $completedJobs);
                $recentActivity[] = [
                    'title' => 'Jobs completed (last 7 days)',
                    'description' => $group['school'] . ' — ' . count($completedJobs) . ' job(s): '
                        . implode(', ', array_slice($labels, 0, 3))
                        . (count($labels) > 3 ? '…' : ''),
                    'time' => 'Last 7 days',
                    'icon' => 'check',
                    'color' => '#43A047',
                ];
            }

            if (count($startingJobs) > 0) {
                $labels = array_map(fn ($job) => $job['label'] ?? '', $startingJobs);
                $recentActivity[] = [
                    'title' => 'Jobs to proof (next 7 days)',
                    'description' => $group['school'] . ' — ' . count($startingJobs) . ' job(s): '
                        . implode(', ', array_slice($labels, 0, 3))
                        . (count($labels) > 3 ? '…' : ''),
                    'time' => 'Next 7 days',
                    'icon' => 'calendar',
                    'color' => '#1E88E5',
                ];
            }
        }

        return [
            'metrics' => $metrics,
            'roleCounts' => $roleCounts,
            'breakdowns' => $breakdowns,
            'recentActivity' => $recentActivity,
        ];
    }

    /**
     * Format TNJ job display as "Job Name (jobkey)".
     */
    protected function formatJobLabel(object $job): string
    {
        $name = trim((string) ($job->ts_jobname ?? ''));
        $key = trim((string) ($job->ts_jobkey ?? ''));

        if ($name === '') {
            $name = $key !== '' ? $key : 'Untitled job';
        }

        return $key !== '' ? "{$name} ({$key})" : $name;
    }

    /**
     * @param  array<int, array{label: string, proof_start?: mixed, proof_due?: mixed}>  $jobs
     * @return array<int, array{label: string, meta: string}>
     */
    protected function mapRecentActivityJobs(array $jobs, string $activityKey): array
    {
        return collect($jobs)
            ->sortBy('label')
            ->values()
            ->map(function (array $job) use ($activityKey) {
                $proofDue = $this->formatDashboardDate($job['proof_due'] ?? null);
                $proofStart = $this->formatDashboardDate($job['proof_start'] ?? null);

                $meta = match ($activityKey) {
                    'completed_last_7' => $proofDue ? "Proof due: {$proofDue}" : '',
                    'starting_next_7' => $proofStart ? "Proof start: {$proofStart}" : '',
                    default => '',
                };

                return [
                    'label' => (string) ($job['label'] ?? ''),
                    'meta' => $meta,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array{label: string, proof_start: mixed, proof_due: mixed, proof_catchup: mixed}>  $jobs
     * @return array<int, array{label: string, meta: string}>
     */
    protected function mapStageJobBreakdown(array $jobs, string $stageKey): array
    {
        return collect($jobs)
            ->sortBy('label')
            ->values()
            ->map(fn (array $job) => [
                'label' => (string) ($job['label'] ?? ''),
                'meta' => $this->buildStageJobMeta($stageKey, $job),
            ])
            ->all();
    }

    /**
     * @param  array{label?: string, proof_start?: mixed, proof_due?: mixed, proof_catchup?: mixed}  $job
     */
    protected function buildStageJobMeta(string $stageKey, array $job): string
    {
        $proofStart = $this->formatDashboardDate($job['proof_start'] ?? null);
        $proofDue = $this->formatDashboardDate($job['proof_due'] ?? null);
        $proofCatchup = $this->formatDashboardDate($job['proof_catchup'] ?? null);

        $parts = match ($stageKey) {
            'not_started' => array_filter([
                $proofStart ? "Proof start: {$proofStart}" : null,
            ]),
            'active' => array_filter([
                $proofStart ? "Proof start: {$proofStart}" : null,
                $proofDue ? "Proof due: {$proofDue}" : null,
            ]),
            'completed' => array_filter([
                $proofDue ? "Proof due: {$proofDue}" : null,
            ]),
            'catchup' => array_filter([
                $proofCatchup ? "Proof catchup: {$proofCatchup}" : null,
                $proofDue ? "Proof due: {$proofDue}" : null,
            ]),
            default => [],
        };

        return implode(' • ', $parts);
    }

    protected function formatDashboardDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('d M Y');
    }

    /**
     * Exclusive stage bucket for unified franchise dashboard charts.
     * Priority: catchup → completed → active → not started.
     */
    protected function resolveUnifiedStageBucket(
        object $job,
        Carbon $now,
        array $activeProofingStatusIds,
        int $completedId
    ): ?string {
        if ($job->proof_catchup !== null && (int) $job->is_in_catchup === 1) {
            return 'stage_catchup';
        }

        $statusId = (int) ($job->job_status_id ?? 0);
        $duePassed = $job->proof_due !== null && Carbon::parse($job->proof_due)->lt($now);
        if ($statusId === $completedId || $duePassed) {
            return 'stage_completed';
        }

        $betweenDates = $job->proof_start !== null
            && $job->proof_due !== null
            && Carbon::parse($job->proof_start)->lte($now)
            && Carbon::parse($job->proof_due)->gte($now);
        $activeByStatus = in_array($statusId, $activeProofingStatusIds, true);
        if ($activeByStatus || $betweenDates) {
            return 'stage_active';
        }

        if ($job->proof_start !== null && Carbon::parse($job->proof_start)->gt($now)) {
            return 'stage_not_started';
        }

        return null;
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
     * Unsynced proofing jobs: show_proofing is 0 or null, and job_status_id is NONE (or null).
     * Columns match the franchise dashboard list (ID, School Name, Job Key, Job, Season).
     *
     * @param  int|string|null  $seasonId
     * @return array<int, array<string, mixed>>
     */
    public function getUnsyncedProofingJobsTable(User $user, $seasonId = null): array
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
        $noneId = (int) $this->statusService->none;
        $deletedId = (int) $this->statusService->deleted;

        $query = Job::query()
            ->leftJoin('schools', 'schools.id', '=', 'jobs.school_id')
            ->leftJoin('seasons', 'jobs.ts_season_id', '=', 'seasons.ts_season_id')
            ->where(function (Builder $q) {
                $q->where('jobs.show_proofing', 0)
                    ->orWhereNull('jobs.show_proofing');
            })
            ->where(function (Builder $q) use ($noneId) {
                $q->where('jobs.job_status_id', $noneId)
                    ->orWhereNull('jobs.job_status_id');
            })
            ->when($deletedId > 0, fn (Builder $q) => $q->where(function (Builder $statusQ) use ($deletedId) {
                $statusQ->whereNull('jobs.job_status_id')
                    ->orWhere('jobs.job_status_id', '!=', $deletedId);
            }));

        $this->constrainJobsToSchools($query, $schoolIds, $schoolKeys);
        $query->when(!empty($seasonIds), fn (Builder $q) => $q->whereIn('jobs.ts_season_id', $seasonIds));

        $jobs = $query
            ->select([
                'jobs.id',
                'jobs.ts_job_id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.school_id',
                'jobs.ts_schoolkey',
                'schools.name as school_name',
                'seasons.code as season_code',
            ])
            ->orderBy('schools.name')
            ->orderBy('jobs.ts_jobname')
            ->get();

        $configuredJobIds = $this->getPhotographyConfiguredJobIds(
            $jobs->pluck('ts_job_id')->map(fn ($id) => (int) $id)->all()
        );

        $rows = [];
        foreach ($jobs as $job) {
            $schoolName = trim((string) ($job->school_name ?? ''));
            if ($schoolName === '') {
                $key = trim((string) ($job->ts_schoolkey ?? ''));
                $schoolName = $key !== '' ? ($keyToName[$key] ?? $key) : '—';
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
     */
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
