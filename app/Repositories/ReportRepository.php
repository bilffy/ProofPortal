<?php

namespace App\Repositories;

use App\Models\Job;
use App\Models\Folder;
use App\Models\Subject;
use App\Models\ProofingChangelog;
use App\Models\GroupPosition;
use App\Models\Franchise;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        $userId = $this->getUserId();
        
        return Job::select(
                'jobs.id',
                'jobs.ts_job_id',
                'jobs.ts_jobname',
                'jobs.ts_season_id'
            )
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $userId)
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
        $userId = $this->getUserId();
        
        return Folder::select(
                'folders.id',
                'folders.ts_foldername',
                'folders.ts_folder_id'
            )
            ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $userId)
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
            ->get();
    }

    /**
     * Get franchises accessible by the current user
     * 
     * @return Collection
     */
    public function getFranchises(): Collection
    {
        $userId = $this->getUserId();
        
        return Franchise::select(
                'franchises.id',
                'franchises.alphacode',
                'franchises.name',
                'users.id as user_id'
            )
            ->join('school_franchises', 'school_franchises.franchise_id', '=', 'franchises.id')
            ->join('schools', 'schools.id', '=', 'school_franchises.school_id')
            ->join('school_users', 'school_users.school_id', '=', 'schools.id')
            ->join('users', 'users.id', '=', 'school_users.user_id')
            ->where('users.id', $userId)
            ->orderBy('franchises.alphacode', 'asc')
            ->get();
    }

    /**
     * Get schools (jobs) accessible by the current user
     * 
     * @return Collection
     */
    public function getSchools(): Collection
    {
        $userId = $this->getUserId();
        
        return Job::select(
                'jobs.id',
                'jobs.ts_jobkey',
                'jobs.ts_jobname',
                'jobs.ts_season_id'
            )
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $userId)
            ->orderBy('jobs.ts_jobname', 'asc')
            ->get();
    }

    /**
     * Get folders accessible by the current user
     * 
     * @return Collection
     */
    public function getFolders(): Collection
    {
        $userId = $this->getUserId();
        
        return Folder::select(
                'folders.id',
                'folders.ts_folderkey',
                'folders.ts_foldername'
            )
            ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $userId)
            ->orderBy('folders.ts_foldername', 'asc')
            ->get();
    }

    /**
     * Get folders by school
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getFoldersBySchool(?int $tsJobId = null): Collection
    {
        return Folder::select(
                'folders.id',
                'folders.ts_folderkey',
                'folders.ts_foldername'
            )
            ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->orderBy('folders.ts_foldername', 'asc')
            ->get();
    }

    /**
     * Get subjects accessible by the current user
     * 
     * @return Collection
     */
    public function getSubjects(): Collection
    {
        $userId = $this->getUserId();

        return Subject::select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->join('jobs', 'subjects.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('users', 'users.id', '=', 'job_users.user_id')
            ->where('users.id', $userId)
            ->get();
    }

    /**
     * Get subjects by school
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getSubjectsBySchool(?int $tsJobId = null): Collection
    {
        return Subject::select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->join('jobs', 'subjects.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->get();
    }

    /**
     * Get subjects by folder
     * 
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getSubjectsByFolder(?int $tsFolderId = null): Collection
    {
        return Subject::select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
            ->where('folders.ts_folder_id', $tsFolderId)
            ->get();
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
        return Subject::select(
                'subjects.id',
                'subjects.ts_subjectkey',
                'subjects.firstname',
                'subjects.lastname'
            )
            ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
            ->join('jobs', 'jobs.ts_job_id', '=', 'folders.ts_job_id')
            ->where([
                ['jobs.ts_job_id', $tsJobId],
                ['folders.ts_folder_id', $tsFolderId]
            ])
            ->get();
    }

    /**
     * Get photo coordinators accessible by the current user
     * 
     * @return Collection
     */
    public function getPhotoCoordinators(): Collection
    {
        $userId = $this->getUserId();
    
        return User::select('users.id', 'users.firstname', 'users.lastname')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.role_id', '=', 'model_has_roles.role_id')
            ->join('job_users as ju_current', 'ju_current.user_id', '=', 'users.id')
            ->join('job_users as ju_logged', 'ju_logged.ts_job_id', '=', 'ju_current.ts_job_id')
            ->where('ju_logged.user_id', $userId)
            ->where('roles.name', 'Photo Coordinator')
            ->get();
    }

    /**
     * Get teachers accessible by the current user
     * 
     * @return Collection
     */
    public function getTeachers(): Collection
    {
        $userId = $this->getUserId();
    
        return User::select('users.id', 'users.firstname', 'users.lastname')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.role_id', '=', 'model_has_roles.role_id')
            ->join('job_users as ju_current', 'ju_current.user_id', '=', 'users.id')
            ->join('job_users as ju_logged', 'ju_logged.ts_job_id', '=', 'ju_current.ts_job_id')
            ->where('ju_logged.user_id', $userId)
            ->where('roles.name', 'Teacher')
            ->get();
    }

    /**
     * Get folder changes by school
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getFolderChangesBySchool(?int $tsJobId = null): Collection
    {
        $issues = [
            'FOLDER_NAME_CHANGE',
            'TEACHER',
            'PRINCIPAL',
            'DEPUTY'
        ];
    
        return ProofingChangelog::select(
                'changelogs.id', 
                'changelogs.change_datetime', 
                'changelogs.change_from', 
                'changelogs.change_to', 
                'issues.issue_description',  
                'folders.ts_foldername', 
                'folders.ts_folderkey'
            )
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('folders', 'folders.ts_folderkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->whereIn('issues.issue_name', $issues)
            ->get();
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
        $issues = [
            'FOLDER_NAME_CHANGE',
            'TEACHER',
            'PRINCIPAL',
            'DEPUTY'
        ];
    
        return ProofingChangelog::select(
                'changelogs.id', 
                'changelogs.change_datetime', 
                'changelogs.change_from', 
                'changelogs.change_to', 
                'issues.issue_description',  
                'folders.ts_foldername', 
                'folders.ts_folderkey'
            )
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('folders', 'folders.ts_folderkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->where('folders.ts_folder_id', $tsFolderId)
            ->whereIn('issues.issue_name', $issues)
            ->get();
    }

    /**
     * Get subject changes by school
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getSubjectChangesBySchool(?int $tsJobId = null): Collection
    {
        return ProofingChangelog::select(
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
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->where('changelogs.keyorigin', 'Subject')
            ->get();
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
        return ProofingChangelog::select(
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
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where([
                ['jobs.ts_job_id', $tsJobId],
                ['folders.ts_folder_id', $tsFolderId],
                ['changelogs.keyorigin', 'Subject']
            ])
            ->get();
    }

    /**
     * Get subject changes by school for Timestone import
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getSubjectChangesBySchoolForTimestoneImport(?int $tsJobId = null): Collection
    {
        return ProofingChangelog::select(
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
            ->join('jobs', 'jobs.ts_jobkey', '=', 'changelogs.ts_jobkey')
            ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
            ->join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->where('changelogs.keyorigin', 'Subject')
            ->get();
    }

    /**
     * Get group photo positions by school for TNJ importing
     * 
     * @param int|null $tsJobId Job ID
     * @return Collection
     */
    public function getGroupPhotoPositionsBySchoolForTnjImporting(?int $tsJobId = null): Collection
    {
        return GroupPosition::select(
                'group_positions.ts_subjectkey', 
                'group_positions.row_number', 
                'group_positions.row_position', 
                'folders.ts_foldername', 
                'group_positions.row_description'
            )
            ->join('jobs', 'jobs.ts_jobkey', '=', 'group_positions.ts_jobkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('jobs.ts_job_id', $tsJobId)
            ->orderBy('group_positions.row_number', 'asc')
            ->orderBy('group_positions.row_position', 'asc')
            ->get();
    }

    /**
     * Get group photo positions by folder for TNJ importing
     * 
     * @param int|null $tsFolderId Folder ID (passed as $tsJobId for backward compatibility)
     * @return Collection
     */
    public function getGroupPhotoPositionsByFolderForTnjImporting(?int $tsFolderId = null): Collection
    {
        return GroupPosition::select(
                'group_positions.ts_subjectkey', 
                'group_positions.row_number', 
                'group_positions.row_position', 
                'folders.ts_foldername', 
                'group_positions.row_description'
            )
            ->join('jobs', 'jobs.ts_jobkey', '=', 'group_positions.ts_jobkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('folders.ts_folder_id', $tsFolderId)
            ->orderBy('group_positions.row_number', 'asc')
            ->orderBy('group_positions.row_position', 'asc')
            ->get();
    }

    /**
     * Get group photo positions by school and folder for TNJ importing
     * 
     * @param int|null $tsJobId Job ID
     * @param int|null $tsFolderId Folder ID
     * @return Collection
     */
    public function getGroupPhotoPositionsBySchoolAndFolderForTnjImporting(?int $tsJobId = null, ?int $tsFolderId = null): Collection
    {
        return GroupPosition::select(
                'group_positions.ts_subjectkey', 
                'group_positions.row_number', 
                'group_positions.row_position', 
                'folders.ts_foldername', 
                'group_positions.row_description'
            )
            ->join('jobs', 'jobs.ts_jobkey', '=', 'group_positions.ts_jobkey')
            ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
            ->where([
                ['jobs.ts_job_id', $tsJobId],
                ['folders.ts_folder_id', $tsFolderId]
            ])
            ->orderBy('group_positions.row_number', 'asc')
            ->orderBy('group_positions.row_position', 'asc')
            ->get();
    }
}
