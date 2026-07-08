<?php

namespace App\Repositories;

use App\Models\Job;
use App\Models\Folder;
use App\Models\Subject;
use App\Models\ProofingChangelog;
use App\Models\GroupPosition;
use App\Models\Franchise;
use App\Models\User;
use App\Services\Proofing\StatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ReportRepository
 * 
 * Handles all complex SQL queries for reporting functionality.
 * This repository centralizes data retrieval logic that was previously
 * scattered across the Report model.
 */
class ReportRepository
{
    /**
     * Get the currently authenticated user's ID
     */
    protected function getUserId(): int
    {
        return Auth::user()->id;
    }

    /**
     * Fetch Jobs (Schools) IDs by user access
     * 
     * @param int|null $tsJobId Optional job ID filter
     * @return Collection
     */
    public function getSchoolsIds(?int $tsJobId = null): Collection
    {
        return $this->syncedJobsForUserQuery()
            ->select(
                'jobs.id',
                'jobs.ts_job_id',
                'jobs.ts_jobname',
                'jobs.ts_season_id',
                'status.status_external_name as sync_status'
            )
            ->join('status', 'status.id', '=', 'jobs.jobsync_status_id')
            ->orderBy('jobs.ts_jobname', 'asc')
            ->get();
    }

    /**
     * Fetch Folders IDs by user access
     * 
     * @param int|null $tsJobId Optional job ID filter
     * @return Collection
     */
    public function getFoldersIds(?int $tsJobId = null): Collection
    {
        return $this->foldersQuery()
            ->select(
                'folders.id',
                'folders.ts_foldername',
                'folders.ts_folder_id'
            )
            ->orderBy('folders.ts_foldername', 'asc')
            ->get();
    }

    /**
     * Fetch Folders IDs by school (job)
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getFoldersIdsBySchool(?int $tsJobId = null): Collection
    {
        return Folder::select('ts_folder_id', 'ts_foldername')
            ->where('ts_job_id', $tsJobId)
            ->orderBy('ts_foldername', 'asc')
            ->get();
    }

    /**
     * Get franchises accessible by the current user
     *
     * @return Collection
     */
    public function getFranchises(): Collection
    {
        return $this->franchisesQuery()
            ->select(
                'franchises.id',
                'franchises.alphacode',
                'franchises.name',
                'users.id as user_id'
            )
            ->orderBy('franchises.alphacode', 'asc')
            ->get();
    }

    public function countFranchises(): int
    {
        return $this->franchisesQuery()->count();
    }

    protected function franchisesQuery(): Builder
    {
        $userId = $this->getUserId();

        return Franchise::query()
            ->join('school_franchises', 'school_franchises.franchise_id', '=', 'franchises.id')
            ->join('schools', 'schools.id', '=', 'school_franchises.school_id')
            ->join('school_users', 'school_users.school_id', '=', 'schools.id')
            ->join('users', 'users.id', '=', 'school_users.user_id')
            ->where('users.id', $userId);
    }

    /**
     * Get schools (jobs) accessible by the current user
     *
     * @return Collection
     */
    public function getSchools(): Collection
    {
        return $this->schoolsQuery()
            ->select(
                'jobs.id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.ts_season_id',
                'status.status_external_name as sync_status'
            )
            ->orderBy('jobs.ts_jobname', 'asc')
            ->get();
    }

    public function countSchools(): int
    {
        return $this->schoolsQuery()->count();
    }

    protected function schoolsQuery(): Builder
    {
        return $this->syncedJobsForUserQuery()
            ->join('status', 'status.id', '=', 'jobs.jobsync_status_id');
    }

    /**
     * Jobs synced to proofing and assigned to the current user.
     */
    protected function syncedJobsForUserQuery(): Builder
    {
        $statusService = app(StatusService::class);

        return Job::query()
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $this->getUserId())
            ->where('jobs.jobsync_status_id', $statusService->sync)
            ->whereNotIn('jobs.job_status_id', [
                $statusService->archived,
                $statusService->tnjNotFound,
                $statusService->deleted,
            ]);
    }

    /**
     * Get folders accessible by the current user
     *
     * @return Collection
     */
    public function getFolders(): Collection
    {
        return $this->foldersQuery()
            ->select(
                'folders.id',
                'folders.ts_folderkey',
                'folders.ts_foldername'
            )
            ->orderBy('folders.ts_foldername', 'asc')
            ->get();
    }

    public function countFolders(): int
    {
        return $this->foldersQuery()->count();
    }

    protected function foldersQuery(): Builder
    {
        return Folder::query()
            ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->whereIn('jobs.ts_job_id', $this->syncedJobsForUserQuery()->select('jobs.ts_job_id'));
    }

    /**
     * Get folders by school
     *
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getFoldersBySchool(?int $tsJobId = null): Collection
    {
        return $this->foldersBySchoolQuery($tsJobId)
            ->select(
                'folders.id',
                'folders.ts_folderkey',
                'folders.ts_foldername'
            )
            ->orderBy('folders.ts_foldername', 'asc')
            ->get();
    }

    public function countFoldersBySchool(?int $tsJobId = null): int
    {
        return $this->foldersBySchoolQuery($tsJobId)->count();
    }

    protected function foldersBySchoolQuery(?int $tsJobId): Builder
    {
        return Folder::query()
            ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_job_id', $tsJobId);
    }

    /**
     * Get subjects accessible by the current user
     *
     * @return Collection
     */
    public function getSubjects(): Collection
    {
        return $this->subjectsQuery()
            ->select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->get();
    }

    public function countSubjects(): int
    {
        return $this->subjectsQuery()->count();
    }

    protected function subjectsQuery(): Builder
    {
        $userId = $this->getUserId();

        return Subject::query()
            ->join('jobs', 'subjects.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $userId);
    }

    /**
     * Get subjects by school
     *
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getSubjectsBySchool(?int $tsJobId = null): Collection
    {
        return $this->subjectsBySchoolQuery($tsJobId)
            ->select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->get();
    }

    public function countSubjectsBySchool(?int $tsJobId = null): int
    {
        return $this->subjectsBySchoolQuery($tsJobId)->count();
    }

    protected function subjectsBySchoolQuery(?int $tsJobId): Builder
    {
        return Subject::query()
            ->join('jobs', 'subjects.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_job_id', $tsJobId);
    }

    /**
     * Get subjects by folder
     *
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getSubjectsByFolder(?int $tsFolderId = null): Collection
    {
        return $this->subjectsByFolderQuery($tsFolderId)
            ->select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->get();
    }

    public function countSubjectsByFolder(?int $tsFolderId = null): int
    {
        return $this->subjectsByFolderQuery($tsFolderId)->count();
    }

    protected function subjectsByFolderQuery(?int $tsFolderId): Builder
    {
        return Subject::query()
            ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
            ->where('folders.ts_folder_id', $tsFolderId)
            ->whereNotNull('subjects.ts_subjectkey')
            ->whereNotNull('folders.ts_folderkey');
    }

    /**
     * Get subjects by school and folder
     *
     * @param int|null $tsJobId Job ID
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getSubjectsBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): Collection
    {
        return $this->subjectsBySchoolAndFolderQuery($tsJobId, $tsFolderId)
            ->select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->get();
    }

    public function countSubjectsBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): int
    {
        return $this->subjectsBySchoolAndFolderQuery($tsJobId, $tsFolderId)->count();
    }

    protected function subjectsBySchoolAndFolderQuery(?int $tsJobId, ?int $tsFolderId): Builder
    {
        return Subject::query()
            ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
            ->join('jobs', 'jobs.ts_job_id', '=', 'folders.ts_job_id')
            ->where([
                ['jobs.ts_job_id', $tsJobId],
                ['folders.ts_folder_id', $tsFolderId],
            ])
            ->whereNotNull('subjects.ts_subjectkey')
            ->whereNotNull('folders.ts_folderkey');
    }

    /**
     * Get photo coordinators accessible by the current user
     *
     * @return Collection
     */
    public function getPhotoCoordinators(): Collection
    {
        return $this->photoCoordinatorsQuery()
            ->select('users.id', 'users.firstname', 'users.lastname')
            ->get();
    }

    public function countPhotoCoordinators(): int
    {
        return $this->photoCoordinatorsQuery()->count();
    }

    protected function photoCoordinatorsQuery(): Builder
    {
        $userId = $this->getUserId();

        return User::query()
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('job_users as ju_current', 'ju_current.user_id', '=', 'users.id')
            ->join('job_users as ju_logged', 'ju_logged.ts_job_id', '=', 'ju_current.ts_job_id')
            ->where('ju_logged.user_id', $userId)
            ->where('roles.name', 'Photo Coordinator');
    }

    /**
     * Get teachers accessible by the current user
     *
     * @return Collection
     */
    public function getTeachers(): Collection
    {
        return $this->teachersQuery()
            ->select('users.id', 'users.firstname', 'users.lastname')
            ->get();
    }

    public function countTeachers(): int
    {
        return $this->teachersQuery()->count();
    }

    protected function teachersQuery(): Builder
    {
        $userId = $this->getUserId();

        return User::query()
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('job_users as ju_current', 'ju_current.user_id', '=', 'users.id')
            ->join('job_users as ju_logged', 'ju_logged.ts_job_id', '=', 'ju_current.ts_job_id')
            ->where('ju_logged.user_id', $userId)
            ->where('roles.name', 'Teacher');
    }

    /**
     * Get folder changes by school
     *
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getFolderChangesBySchool(?int $tsJobId = null): Collection
    {
        return $this->folderChangesBySchoolQuery($tsJobId)
            ->select(
                'changelogs.id',
                'changelogs.change_datetime',
                'changelogs.change_from',
                'changelogs.change_to',
                'issues.issue_description',
                'folders.ts_foldername',
                'folders.ts_folderkey'
            )
            ->get();
    }

    public function countFolderChangesBySchool(?int $tsJobId = null): int
    {
        return $this->folderChangesBySchoolQuery($tsJobId)->count('changelogs.id');
    }

    /**
     * Get folder changes by school and folder
     *
     * @param int|null $tsJobId Job ID
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getFolderChangesBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): Collection
    {
        return $this->folderChangesBySchoolAndFolderQuery($tsJobId, $tsFolderId)
            ->select(
                'changelogs.id',
                'changelogs.change_datetime',
                'changelogs.change_from',
                'changelogs.change_to',
                'issues.issue_description',
                'folders.ts_foldername',
                'folders.ts_folderkey'
            )
            ->get();
    }

    public function countFolderChangesBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): int
    {
        return $this->folderChangesBySchoolAndFolderQuery($tsJobId, $tsFolderId)->count('changelogs.id');
    }

    /**
     * Get subject changes by school
     *
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getSubjectChangesBySchool(?int $tsJobId = null): Collection
    {
        return $this->subjectChangesBySchoolQuery($tsJobId)
            ->select(
                'changelogs.id',
                'changelogs.change_datetime',
                'changelogs.change_from',
                'changelogs.change_to',
                'issues.issue_description',
                'changelogs.notes',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->get();
    }

    public function countSubjectChangesBySchool(?int $tsJobId = null): int
    {
        return $this->subjectChangesBySchoolQuery($tsJobId)->count('changelogs.id');
    }

    /**
     * Get subject changes by school and folder
     *
     * @param int|null $tsJobId Job ID
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getSubjectChangesBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): Collection
    {
        return $this->subjectChangesBySchoolAndFolderQuery($tsJobId, $tsFolderId)
            ->select(
                'changelogs.id',
                'changelogs.change_datetime',
                'changelogs.change_from',
                'changelogs.change_to',
                'issues.issue_description',
                'changelogs.notes',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->distinct()
            ->get();
    }

    public function countSubjectChangesBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): int
    {
        return $this->subjectChangesBySchoolAndFolderQuery($tsJobId, $tsFolderId)
            ->distinct()
            ->count('changelogs.id');
    }

    /**
     * Get subject changes by school for Timestone import
     *
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getSubjectChangesBySchoolForTimestoneImport(?int $tsJobId = null): Collection
    {
        return $this->subjectChangesBySchoolForTimestoneImportQuery($tsJobId)
            ->orderBy('keyvalue', 'asc')
            ->get();
    }

    public function countSubjectChangesBySchoolForTimestoneImport(?int $tsJobId = null): int
    {
        return $this->subjectChangesBySchoolForTimestoneImportQuery($tsJobId)->count();
    }

    /**
     * Get group photo positions by school for TNJ importing
     *
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getGroupPhotoPositionsBySchoolForTnjImporting(?int $tsJobId = null): Collection
    {
        return $this->groupPhotoPositionsBySchoolForTnjImportingQuery($tsJobId)->get();
    }

    public function countGroupPhotoPositionsBySchoolForTnjImporting(?int $tsJobId = null): int
    {
        return $this->groupPhotoPositionsBySchoolForTnjImportingQuery($tsJobId)->count();
    }

    /**
     * Get group photo positions by folder for TNJ importing
     *
     * @param int|null $tsFolderId Folder ID (passed as $tsJobId for backward compatibility)
     * @return Collection
     */
    public function getGroupPhotoPositionsByFolderForTnjImporting(?int $tsFolderId = null): Collection
    {
        return $this->groupPhotoPositionsByFolderForTnjImportingQuery($tsFolderId)
            ->select(
                'group_positions.ts_subjectkey',
                'group_positions.row_number',
                'group_positions.row_position',
                'folders.ts_foldername',
                'group_positions.row_description'
            )
            ->orderBy('group_positions.row_number', 'asc')
            ->orderBy('group_positions.row_position', 'asc')
            ->get();
    }

    public function countGroupPhotoPositionsByFolderForTnjImporting(?int $tsFolderId = null): int
    {
        return $this->groupPhotoPositionsByFolderForTnjImportingQuery($tsFolderId)->count('group_positions.id');
    }

    /**
     * Get group photo positions by school and folder for TNJ importing
     *
     * @param int|null $tsJobId Job ID
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getGroupPhotoPositionsBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): Collection
    {
        return $this->groupPhotoPositionsBySchoolAndFolderQuery($tsJobId, $tsFolderId)->get();
    }

    public function countGroupPhotoPositionsBySchoolAndFolder(?int $tsJobId = null, ?int $tsFolderId = null): int
    {
        return $this->groupPhotoPositionsBySchoolAndFolderQuery($tsJobId, $tsFolderId)->count();
    }

    public function getBlueprintFullChangeList($tsJobId = null)
    {
        return $this->blueprintFullChangeListQuery($tsJobId)
            ->orderBy('change_datetime', 'desc')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            });
    }

    public function countBlueprintFullChangeList($tsJobId = null): int
    {
        return DB::query()
            ->fromSub($this->blueprintFullChangeListUnionQuery($tsJobId), 'combined')
            ->count();
    }

    /**
     * Count preview results without loading row data into PHP memory.
     */
    public function countForReport(string $reportMethod, array $parameters = []): int
    {
        $map = [
            'myFranchises' => 'countFranchises',
            'mySchools' => 'countSchools',
            'myFolders' => 'countFolders',
            'myFoldersBySchool' => 'countFoldersBySchool',
            'mySubjects' => 'countSubjects',
            'mySubjectsBySchool' => 'countSubjectsBySchool',
            'mySubjectsByFolder' => 'countSubjectsByFolder',
            'mySubjectsBySchoolAndFolder' => 'countSubjectsBySchoolAndFolder',
            'myPhotocoordinators' => 'countPhotoCoordinators',
            'myTeachers' => 'countTeachers',
            'myFolderChangesBySchool' => 'countFolderChangesBySchool',
            'myFolderChangesBySchoolAndFolder' => 'countFolderChangesBySchoolAndFolder',
            'mySubjectChangesBySchool' => 'countSubjectChangesBySchool',
            'mySubjectChangesBySchoolAndFolder' => 'countSubjectChangesBySchoolAndFolder',
            'mySubjectChangesBySchoolForTimestoneImport' => 'countSubjectChangesBySchoolForTimestoneImport',
            'myGroupPhotoPositionsBySchoolForTnjImporting' => 'countGroupPhotoPositionsBySchoolForTnjImporting',
            'myGroupPhotoPositionsByFolderForTnjImporting' => 'countGroupPhotoPositionsByFolderForTnjImporting',
            'myGroupPhotoPositionsBySchoolAndFolder' => 'countGroupPhotoPositionsBySchoolAndFolder',
            'blueprintFullChangeList' => 'countBlueprintFullChangeList',
        ];

        if (! isset($map[$reportMethod])) {
            return 0;
        }

        return (int) call_user_func_array([$this, $map[$reportMethod]], $parameters);
    }

    protected function folderChangeIssues(): array
    {
        return [
            'FOLDER_NAME_CHANGE',
        ];
    }

    protected function folderChangesBySchoolQuery(?int $tsJobId): Builder
    {
        return ProofingChangelog::query()
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('folders', 'folders.ts_folderkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->whereNotNull('folders.ts_folderkey')
            ->whereIn('issues.issue_name', $this->folderChangeIssues());
    }

    protected function folderChangesBySchoolAndFolderQuery(?int $tsJobId, ?int $tsFolderId): Builder
    {
        return ProofingChangelog::query()
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('folders', 'folders.ts_folderkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->where('folders.ts_folder_id', $tsFolderId)
            ->whereNotNull('folders.ts_folderkey')
            ->whereIn('issues.issue_name', $this->folderChangeIssues());
    }

    protected function subjectChangesBySchoolQuery(?int $tsJobId): Builder
    {
        return ProofingChangelog::query()
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where([
                ['jobs.ts_job_id', $tsJobId],
                ['changelogs.keyorigin', 'Subject'],
            ])
            ->whereNotNull('subjects.ts_subjectkey');
    }

    protected function subjectChangesBySchoolAndFolderQuery(?int $tsJobId, ?int $tsFolderId): Builder
    {
        return ProofingChangelog::query()
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where([
                ['jobs.ts_job_id', $tsJobId],
                ['changelogs.keyorigin', 'Subject'],
            ])
            ->whereNotNull('subjects.ts_subjectkey')
            ->where(function ($query) use ($tsFolderId) {
                $query->where('subjects.ts_folder_id', $tsFolderId)
                    ->orWhereExists(function ($sub) use ($tsFolderId) {
                        $sub->select(DB::raw(1))
                            ->from('folder_subjects')
                            ->whereColumn('folder_subjects.ts_subject_id', 'subjects.ts_subject_id')
                            ->where('folder_subjects.ts_folder_id', $tsFolderId)
                            ->where('folder_subjects.is_deleted', 0);
                    });
            });
    }

    protected function subjectChangesBySchoolForTimestoneImportQuery(?int $tsJobId): Builder
    {
        $tsJobKey = $this->resolveTsJobKey($tsJobId);
        return ProofingChangelog::query()
            ->where([
                ['ts_jobkey', $tsJobKey],
                ['issue_id', 15],
            ]);
    }

    protected function groupPhotoPositionsBySchoolForTnjImportingQuery(?int $tsJobId): Builder
    {
        $tsJobKey = $this->resolveTsJobKey($tsJobId);

        return GroupPosition::query()->where('ts_jobkey', $tsJobKey);
    }

    protected function groupPhotoPositionsByFolderForTnjImportingQuery(?int $tsFolderId): Builder
    {
        return GroupPosition::query()
            ->join('jobs', 'jobs.ts_jobkey', '=', 'group_positions.ts_jobkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('folders.ts_folder_id', $tsFolderId)
            ->whereNotNull('folders.ts_folderkey');
    }

    protected function groupPhotoPositionsBySchoolAndFolderQuery(?int $tsJobId, ?int $tsFolderId): Builder
    {
        $tsJobKey = $this->resolveTsJobKey($tsJobId);
        $tsFolderKey = $this->resolveTsFolderKey($tsFolderId);

        return ProofingChangelog::query()
            ->where([
                ['ts_jobkey', $tsJobKey],
                ['keyvalue', $tsFolderKey],
            ])
            ->whereNotNull('user_id')
            ->where('issue_id', 12);
    }

    protected function resolveTsJobKey(?int $tsJobId): string
    {
        $job = Job::withoutGlobalScopes()->where('ts_job_id', $tsJobId)->first()
            ?? Job::withoutGlobalScopes()->where('ts_jobkey', $tsJobId)->first();

        return $job ? $job->ts_jobkey : (string) $tsJobId;
    }

    protected function resolveTsFolderKey(?int $tsFolderId): string
    {
        $folder = Folder::withoutGlobalScopes()->where('ts_folder_id', $tsFolderId)->first()
            ?? Folder::withoutGlobalScopes()->where('ts_folderkey', $tsFolderId)->first();

        return $folder ? $folder->ts_folderkey : (string) $tsFolderId;
    }

    protected function blueprintFullChangeListQuery($tsJobId)
    {
        return $this->blueprintFullChangeListUnionQuery($tsJobId);
    }

    protected function blueprintFullChangeListUnionQuery($tsJobId)
    {
        $tsJobKey = $this->resolveTsJobKey($tsJobId);

        $allChangesSub = DB::table('changelogs')
            ->select('*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY issue_id, ts_jobkey, keyvalue ORDER BY change_datetime DESC, id DESC) as row_num')
            ->where('ts_jobkey', $tsJobKey)
            ->whereNotIn('issue_id', [1, 2, 3, 4, 14])
            ->where('change_to', '<>', '{"Absent":[]}')
            ->where('notes', '<>', 'Marked All Issue Recorded as "Yes"')
            ->whereNotNull('user_id')
            ->where(function ($query) use ($tsJobKey) {
                $query->where('issue_id', '<>', 5)
                    ->orWhereExists(function ($sub) use ($tsJobKey) {
                        $sub->select(DB::raw(1))
                            ->from('changelogs as c96')
                            ->whereColumn('c96.keyvalue', 'changelogs.keyvalue')
                            ->where('c96.ts_jobkey', $tsJobKey)
                            ->where('c96.issue_id', 8)
                            ->where('c96.change_to', '<>', '{"Absent":[]}')
                            ->where('c96.notes', '<>', 'Marked All Issue Recorded as "Yes"')
                            ->whereNotNull('c96.user_id');
                    });
            });

        $query1 = DB::table(DB::raw("({$allChangesSub->toSql()}) as ranked"))
            ->mergeBindings($allChangesSub)
            ->select(
                'ranked.id',
                'ranked.change_datetime',
                'ranked.change_from',
                'ranked.change_to',
                'issues.issue_description',
                'ranked.notes',
                'ranked.keyvalue as ts_subjectkey',
                DB::raw("COALESCE(subjects.firstname, folders.ts_foldername) as firstname"),
                DB::raw("COALESCE(subjects.lastname, ' (Folder)') as lastname"),
                'ranked.keyorigin as type'
            )
            ->leftJoin('issues', 'issues.id', '=', 'ranked.issue_id')
            ->leftJoin('subjects', function ($join) {
                $join->on('subjects.ts_subjectkey', '=', 'ranked.keyvalue')
                    ->whereRaw("ranked.keyorigin = 'Subject'");
            })
            ->leftJoin('folders', function ($join) {
                $join->on('folders.ts_folderkey', '=', 'ranked.keyvalue')
                    ->whereRaw("ranked.keyorigin IN ('Folder', 'Group')");
            })
            ->where('ranked.row_num', 1);

        $query2 = DB::table('group_positions')
            ->select(
                'group_positions.id',
                'group_positions.created_at as change_datetime',
                DB::raw("NULL as change_from"),
                DB::raw("CONCAT('Row: ', group_positions.row_number, ', Pos: ', group_positions.row_position) as change_to"),
                DB::raw("'Group Position' as issue_description"),
                'group_positions.row_description as notes',
                'group_positions.ts_subjectkey',
                'group_positions.subject_full_name as firstname',
                DB::raw("'' as lastname"),
                DB::raw("'Position' as type")
            )
            ->where('group_positions.ts_jobkey', $tsJobKey);

        return $query1->union($query2);
    }
}
