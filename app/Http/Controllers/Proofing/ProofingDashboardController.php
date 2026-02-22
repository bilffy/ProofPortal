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
use App\Http\Resources\UserResource;
use Auth;

class ProofingDashboardController extends Controller
{
    protected $jobService;
    protected $encryptDecryptService;
    protected $schoolService;
    protected $seasonService;
    protected $proofingChangelogService;

    public function __construct(JobService $jobService, SchoolService $schoolService, EncryptDecryptService $encryptDecryptService, SeasonService $seasonService, ProofingChangelogService $proofingChangelogService)
    {

        $this->jobService = $jobService;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->schoolService = $schoolService;
        $this->seasonService = $seasonService;
        $this->proofingChangelogService = $proofingChangelogService;
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
            
            // Maintain session compatibility for now, but prioritize URL param
            $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first();
             session([
                'selectedJob' => $selectedJob,
                'selectedSeason' => $selectedSeason,
                'openJob' => true
            ]);
            session()->save();

        } elseif(Session::has('selectedJob') && Session::has('selectedSeason') && Session::get('openJob') === true) {
            $selectedJob = session('selectedJob');
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
        
            // Pass both $user and $data to the view
            return view('proofing.proofing-home', [
                'user' => new UserResource($user), // Passing the authenticated user
                'data' => $data, // Passing the dashboard data
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
                    ->get();
            } else {
                $jobs = collect(); // empty collection
            }
                    
            return view('proofing.proofing-switch-job', [
                'user' => new UserResource($user), // Passing the authenticated user
                'jobs' => $jobs, // Passing the dashboard data
            ]);
        }  
    }
}
