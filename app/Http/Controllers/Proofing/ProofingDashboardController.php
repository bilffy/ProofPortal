<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\SchoolService;
use Illuminate\Support\Facades\Session;
use App\Services\Proofing\JobService;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\TimestoneTableService;
use App\Helpers\SchoolContextHelper;
use App\Http\Resources\UserResource;
use App\Helpers\RoleHelper;
use Auth;

class ProofingDashboardController extends Controller
{
    protected $jobService;
    protected $encryptDecryptService;
    protected $schoolService;
    protected $seasonService;
    protected $proofingChangelogService;
    protected $timestoneTableService;
    protected $statusService;

    public function __construct(JobService $jobService, SchoolService $schoolService, EncryptDecryptService $encryptDecryptService, SeasonService $seasonService, ProofingChangelogService $proofingChangelogService, TimestoneTableService $timestoneTableService, StatusService $statusService)
    {

        $this->jobService = $jobService;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->schoolService = $schoolService;
        $this->seasonService = $seasonService;
        $this->proofingChangelogService = $proofingChangelogService;
        $this->timestoneTableService = $timestoneTableService;
        $this->statusService = $statusService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function index(Request $request, $hash = null)
    {
        // Get the authenticated user
        $user = Auth::user();
        $selectedJob = null;

        if ($hash) {
            $decryptedJobKey = $this->getDecryptData($hash);
            $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first();
            
            if (!$selectedJob) {
                abort(404); 
            }

            if ($selectedJob->job_status_id == $this->statusService->archived) {
                abort(403, 'This job has been archived and is no longer accessible.');
            }
            
            // Maintain session compatibility for now, but prioritize URL param
            $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first();
             session([
                'selectedJob' => $selectedJob,
                'selectedSeason' => $selectedSeason,
                'openJob' => true
            ]);
            session()->save();

        } elseif(Session::has('selectedJob') && Session::has('selectedSeason') && Session::get('openJob') === true) {
            $sessionJob = session('selectedJob');
            $selectedJob = $this->jobService->getJobByJobKey($sessionJob->ts_jobkey)->first();
            
            // Check if the job was archived in another session
            if (!$selectedJob || $selectedJob->job_status_id == $this->statusService->archived) {
                Session::forget(['selectedJob', 'selectedSeason', 'openJob']);
                session()->save();
                $selectedJob = null;
            }
        }

        if ($selectedJob) {
            // Logic for Task Items (Job Dashboard)
            $approvedSubjectChanges = $this->proofingChangelogService->getAllApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
            $approvedFolderGroupChangesCount = $this->proofingChangelogService->getAllApprovedFolderGroupChangeByJobKey($selectedJob->ts_jobkey);
            $awaitApprovalSubjectChanges = $this->proofingChangelogService->getAllAwaitApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
            $approvedSubjectChangesCount = isset($approvedSubjectChanges['subjectChanges']) ? $approvedSubjectChanges['subjectChanges']->count() : 0;
            $awaitApprovalSubjectChangesCount = isset($awaitApprovalSubjectChanges['subjectChanges']) ? $awaitApprovalSubjectChanges['subjectChanges']->count() : 0;

            // Store session data
            session([
                'approvedSubjectChangesCount' => $approvedSubjectChangesCount + $approvedFolderGroupChangesCount,
                'awaitApprovalSubjectChangesCount' => $awaitApprovalSubjectChangesCount
            ]);
        
            // Save session
            session()->save(); 
            
            return view('proofing.franchise.task-items', [
                'user' => new UserResource($user)
            ]);
        } elseif($user->hasRole('Franchise')) {
            // Get the dashboard data
            $franchiseCode = $user->getSchoolOrFranchiseDetail()->alphacode;
            $data = $this->jobService->getDashboardData($franchiseCode, Session::get('school_context-sid'));
            $user = Auth::user();
            $getSeason = $this->seasonService
                        ->getAllSeasonData('ts_season_id')
                        ->pluck('ts_season_id')
                        ->toArray();
            $tsJobs = $this->timestoneTableService->getAllTimestoneJobsBySeasonID($getSeason, $user->getFranchise()->ts_account_id, SchoolContextHelper::getCurrentSchoolContext()->schoolkey)->get();
            $bpJobs = $this->jobService->getJobsBySeason(SchoolContextHelper::getCurrentSchoolContext()->schoolkey, $getSeason)
                ->where('job_users.user_id', $user->id)
                ->where('show_proofing', 1)
                ->pluck('ts_jobkey');
                
            $activeJobKeys = isset($data['activeSyncJobs']) ? $data['activeSyncJobs']->pluck('ts_jobkey') : collect([]);
            $allSyncedJobKeys = $bpJobs->merge($activeJobKeys)->unique()->flip();
    
            $filteredTsJobs = $tsJobs->reject(function ($tsJob) use ($allSyncedJobKeys) {
                return $allSyncedJobKeys->has($tsJob->JobKey);
            });
        
            // Pass both $user and $data to the view
            return view('proofing.proofing-home', [
                'user' => new UserResource($user), // Passing the authenticated user
                'data' => $data, // Passing the dashboard data
                'tsJobs' => $filteredTsJobs
            ]);
        } elseif($user->hasRole('Photo Coordinator') || $user->hasRole('Teacher')) {
            if(Session::has('school_context-sid')) {
                $currentSchool = $this->schoolService
                ->getSchoolById(Session::get('school_context-sid'))
                ->first(['schoolkey']);
            }else{
                $currentSchool = $user->getSchool();
            }
            if ($currentSchool) {
                $jobs = $user->jobs()
                    ->select('jobs.ts_job_id', 'ts_jobname', 'ts_season_id', 'ts_schoolkey', 'ts_jobkey')
                    ->where('ts_schoolkey', $currentSchool->schoolkey)
                    ->whereNotIn('jobs.job_status_id', [$this->statusService->archived, $this->statusService->deleted, $this->statusService->tnjNotFound])
                    ->get();
            } else {
                $jobs = collect(); // empty collection
            }
            if($jobs->count() === 1 && ($user->hasRole(RoleHelper::ROLE_PHOTO_COORDINATOR) || $user->hasRole(RoleHelper::ROLE_TEACHER))) {
                $selectedJob = $jobs->first();
                $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first();
                session([
                    'selectedJob' => $selectedJob,
                    'selectedSeason' => $selectedSeason,
                    'openJob' => true
                ]);
                session()->save();
                // Logic for Task Items (Job Dashboard)
                $approvedSubjectChanges = $this->proofingChangelogService->getAllApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
                $approvedFolderGroupChangesCount = $this->proofingChangelogService->getAllApprovedFolderGroupChangeByJobKey($selectedJob->ts_jobkey);
                $awaitApprovalSubjectChanges = $this->proofingChangelogService->getAllAwaitApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
                $approvedSubjectChangesCount = isset($approvedSubjectChanges['subjectChanges']) ? $approvedSubjectChanges['subjectChanges']->count() : 0;
                $awaitApprovalSubjectChangesCount = isset($awaitApprovalSubjectChanges['subjectChanges']) ? $awaitApprovalSubjectChanges['subjectChanges']->count() : 0;

                // Store session data
                session([
                    'approvedSubjectChangesCount' => $approvedSubjectChangesCount + $approvedFolderGroupChangesCount,
                    'awaitApprovalSubjectChangesCount' => $awaitApprovalSubjectChangesCount
                ]);
            
                // Save session
                session()->save(); 
                
                return view('proofing.franchise.task-items', [
                    'user' => new UserResource($user)
                ]);
            } else {
                return view('proofing.proofing-switch-job', [
                    'user' => new UserResource($user), // Passing the authenticated user
                    'jobs' => $jobs, // Passing the dashboard data
                ]);
            }  
        }  
    }
}
