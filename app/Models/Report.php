<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Job;
use App\Models\Folder;
use App\Models\Subject;
use App\Models\ProofingChangelog;
use App\Models\GroupPosition;
use App\Models\Franchise;
use App\Models\User;
use App\Models\JobUsers;
use Auth;

class Report extends Model
{
    use HasFactory;
    protected $table = "reports";
    protected $fillable = ['id', 'name', 'description', 'query', 'params'];

    // Fetch Jobs (Schools) by franchiseAccountID and syncStatus
    public static function mySchoolsIds($tsJobId = null)
    {
        // Get the logged-in user's ID
        $userId = Auth::user()->id; 
        
        // Base query
        $query = Job::select(
                    'jobs.id',
                    'jobs.ts_job_id',
                    'jobs.ts_jobname',
                    'jobs.ts_season_id'
                )
                ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
                ->join('users', 'users.id', '=', 'job_users.user_id')
                ->where('users.id', $userId)
                ->orderBy('jobs.ts_jobname', 'asc')->get();

        // Return results
        return $query;  // Execute the query and return the results
    }

    // Fetch Folders by franchiseAccountID and syncStatus
    public static function myFoldersIds($tsJobId = null)
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 
        
        // Base query
        $query = Folder::select(
            'folders.id',
            'folders.ts_foldername',
            'folders.ts_folder_id'
        )
        ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('users', 'users.id', '=', 'job_users.user_id')
        ->where('users.id', $userId)
        ->orderBy('folders.ts_foldername', 'asc')->get();

        return $query;
    }


    // Fetch Folders by school (job) with franchiseAccountID, syncStatus, and tsJobId
    public static function myFoldersIdsBySchool($tsJobId = null)
    {
        $query = Folder::select('ts_folder_id', 'ts_foldername')->where('ts_job_id',$tsJobId)->get();

        return $query;
    }

    public static function myFranchises()
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 
        
        // Base query
        $query = Franchise::select(
                    'franchises.id',
                    'franchises.alphacode',
                    'franchises.name',
                    'users.id as user_id' // Alias to avoid ambiguity
                )
                ->join('school_franchises', 'school_franchises.franchise_id', '=', 'franchises.id')
                ->join('schools', 'schools.id', '=', 'school_franchises.school_id')
                ->join('school_users', 'school_users.school_id', '=', 'schools.id') // Assuming the correct join on school_id
                ->join('users', 'users.id', '=', 'school_users.user_id')
                ->where('users.id', $userId)
                ->orderBy('franchises.alphacode', 'asc')->get();

        // Return results, either as a collection or query object
        return $query;  // Call ->get() to execute and return results as a collection
    }

    public static function mySchools()
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 
        
        // Base query
        $query = Job::select(
                    'jobs.id',
                    'jobs.ts_jobkey',
                    'jobs.ts_jobname',
                    'jobs.ts_season_id'
                )
                ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
                ->join('users', 'users.id', '=', 'job_users.user_id')
                ->where('users.id', $userId)
                ->orderBy('jobs.ts_jobname', 'asc')->get();

        // Return results
        return $query;  // Execute the query and return the results
    }

    public static function myFolders()
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 
        
        // Base query
        $query = Folder::select(
            'folders.id',
            'folders.ts_folderkey',
            'folders.ts_foldername'
        )
        ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('users', 'users.id', '=', 'job_users.user_id')
        ->where('users.id', $userId)
        ->orderBy('folders.ts_foldername', 'asc')->get();

        return $query;
    }

    public static function myFoldersBySchool($tsJobId = null)
    {
        // Base query
        $query = Folder::select(
            'folders.id',
            'folders.ts_folderkey',
            'folders.ts_foldername'
        )
        ->join('jobs', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
        ->where('jobs.ts_job_id', $tsJobId)
        ->orderBy('folders.ts_foldername', 'asc')->get();

        return $query;
    }

    public static function mySubjects()
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 

        // Base query
        $query = Subject::select(
            'subjects.id',
            'subjects.ts_subjectkey',
            'subjects.firstname',
            'subjects.lastname'
        )
        ->join('jobs', 'subjects.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('job_users', 'job_users.ts_job_id', '=', 'jobs.ts_job_id')
        ->join('users', 'users.id', '=', 'job_users.user_id')
        ->where('users.id', $userId)->get();

        return $query;
    }

    public static function mySubjectsBySchool($tsJobId = null)
    {
        // Base query
        $query = Subject::select(
            'subjects.id',
            'subjects.ts_subjectkey',
            'subjects.firstname',
            'subjects.lastname'
        )
        ->join('jobs', 'subjects.ts_job_id', '=', 'jobs.ts_job_id')
        ->where('jobs.ts_job_id', $tsJobId)->get();

        return $query;
    }

    public static function mySubjectsByFolder($tsJobId = null)
    {
        $folderId = $tsJobId;
        // Base query
        $query = Subject::select(
            'subjects.id',
            'subjects.ts_subjectkey',
            'subjects.firstname',
            'subjects.lastname'
        )
        ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
        ->where('folders.ts_folder_id', $folderId)->get();

        return $query;
    }

    public static function mySubjectsBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        // Base query
        $query = Subject::select(
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
        ])->get();

        return $query;
    }

    public static function myPhotocoordinators()
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 
    
        // Fetch users with the role 'PhotoCoordinator' who are associated with the jobs of the current user
        $query = User::select('users.id', 'users.firstname', 'users.lastname')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.role_id', '=', 'model_has_roles.role_id')
            ->join('job_users as ju_current', 'ju_current.user_id', '=', 'users.id')
            ->join('job_users as ju_logged', 'ju_logged.ts_job_id', '=', 'ju_current.ts_job_id')
            ->where('ju_logged.user_id', $userId)
            ->where('roles.name', 'Photo Coordinator')
            ->get();
    
        return $query;
    }
    

    public static function myTeachers()
    {
        // Get the logged-in user's ID

        $userId = Auth::user()->id; 
    
        // Fetch users with the role 'Teacher' who are associated with the jobs of the current user
        $query = User::select('users.id', 'users.firstname', 'users.lastname')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.role_id', '=', 'model_has_roles.role_id')
            ->join('job_users as ju_current', 'ju_current.user_id', '=', 'users.id')
            ->join('job_users as ju_logged', 'ju_logged.ts_job_id', '=', 'ju_current.ts_job_id')
            ->where('ju_logged.user_id', $userId)
            ->where('roles.name', 'Teacher')
            ->get();
    
        return $query;
    }

    public static function myFolderChangesBySchool($tsJobId = null)
    {
        $issues = [
            'FOLDER_NAME_CHANGE',
            'TEACHER',
            'PRINCIPAL',
            'DEPUTY'
        ];
    
        // Query definition with correct table aliasing
        $query = ProofingChangelog::
            select(
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
    
        return $query;
    }

    public static function myFolderChangesBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        $issues = [
            'FOLDER_NAME_CHANGE',
            'TEACHER',
            'PRINCIPAL',
            'DEPUTY'
        ];
    
        // Query definition with correct table aliasing
        $query = ProofingChangelog::
            select(
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
    
        return $query;
    }

    public static function mySubjectChangesBySchool($tsJobId = null)
    {
        // Query definition with correct table aliasing
        $query = ProofingChangelog::
            select(
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
    
        return $query;
    }

    public static function mySubjectChangesBySchoolAndFolder($tsJobId = null, $tsFolderId = null)
    {
        // Query definition with correct table aliasing
        $query = ProofingChangelog::
            select(
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
    
        return $query;
    }

    public static function mySubjectChangesBySchoolForTimestoneImport($tsJobId = null)
    {
        // Query definition with correct table aliasing
        $query = ProofingChangelog::
            select(
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
    
        return $query;
    }

    public static function myGroupPhotoPositionsBySchoolForTnjImporting($tsJobId = null)
    {
        // Query definition with correct table aliasing
        $query = GroupPosition::
            select(
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
    
        return $query;
    }

    public static function myGroupPhotoPositionsByFolderForTnjImporting($tsJobId = null)
    {
        $tsFolderId = $tsJobId;
        // Query definition with correct table aliasing
        $query = GroupPosition::
            select(
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
    
        return $query;
    }

    public static function myGroupPhotoPositionsBySchoolAndFolderForTnjImporting($tsJobId = null, $tsFolderId = null)
    {
        // Query definition with correct table aliasing
        $query = GroupPosition::
            select(
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
    
        return $query;
    }

    public static function blueprintFullChangeList($tsJobId = null)
    {
        return self::mySchools();
    }

    //ReportRole Table
    public function report_roles()
    {
        return $this->hasMany('App\Models\ReportRole', 'report_id', 'id');
    }
}

