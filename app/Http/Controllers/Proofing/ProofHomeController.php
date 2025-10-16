<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SeasonService;
use Illuminate\Support\Facades\Session;
use App\Services\Proofing\JobService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Auth;

class ProofHomeController extends Controller
{
    public function __construct(JobService $jobService, EncryptDecryptService $encryptDecryptService, StatusService $statusService, SeasonService $seasonService, ProofingChangelogService $proofingChangelogService)
    {

        $this->jobService = $jobService;
        $this->franchiseCode = Auth::user()->getSchoolOrFranchiseDetail()->alphacode;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->statusService = $statusService;
        $this->seasonService = $seasonService;
        $this->proofingChangelogService = $proofingChangelogService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function index()
    {
        // Get the authenticated user
        $user = Auth::user();
        if(Session::has('selectedJob') && Session::has('selectedSeason') && Session::get('openJob') === true)
        {
            $selectedJob = session('selectedJob') ?? '[]';
            $approvedSubjectChanges = $this->proofingChangelogService->getAllApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
            $awaitApprovalSubjectChanges = $this->proofingChangelogService->getAllAwaitApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
            $approvedSubjectChangesCount = isset($approvedSubjectChanges['subjectChanges']) ? $approvedSubjectChanges['subjectChanges']->count() : 0;
            $awaitApprovalSubjectChangesCount = isset($awaitApprovalSubjectChanges['subjectChanges']) ? $awaitApprovalSubjectChanges['subjectChanges']->count() : 0;

            // Store session data
            session([
                'approvedSubjectChangesCount' => $approvedSubjectChangesCount,
                'awaitApprovalSubjectChangesCount' => $awaitApprovalSubjectChangesCount
            ]);
        
            // Save session
            session()->save(); 
            
            return view('proofing.franchise.task-items', [
                'user' => new UserResource($user)
            ]);
        }elseif($user->hasRole('Franchise')){
            // Get the dashboard data
            $data = $this->jobService->getDashboardData($this->franchiseCode);
        
            // Pass both $user and $data to the view
            return view('proofing.proofing-home', [
                'user' => new UserResource($user), // Passing the authenticated user
                'data' => $data, // Passing the dashboard data
            ]);
        }elseif($user->hasRole('Photo Coordinator') || $user->hasRole('Teacher')){
            $jobs = $user->jobs()->select('jobs.ts_job_id', 'ts_jobname', 'ts_season_id', 'ts_schoolkey')
                    // ->where('ts_schoolkey', $user->getSchool()->schoolkey)
                    ->get();

            return view('proofing.proofing-switch-job', [
                'user' => new UserResource($user), // Passing the authenticated user
                'jobs' => $jobs, // Passing the dashboard data
            ]);
        }  
    }
    
    public function openJob(Request $request)
    {
        if(empty($request->query('jobId')) && Session::has('selectedJob')){
                $selectedJob = session('selectedJob');
        }else{
            $jobID = $request->query('jobId');
            if(isset($jobID)){
                $selectedJob = $this->jobService->getJobById($this->getDecryptData($jobID)); 
            }else{
                return redirect()->route('proofing');
            }
        }

        $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first();
    
        // Store session data
        session([
            'selectedJob' => $selectedJob,
            'selectedSeason' => $selectedSeason,
            'openJob' => true
        ]);
    
        // Save session
        session()->save(); 
   
        if(empty($request->query('jobId')) && Session::has('selectedJob')){
            return redirect()->route('proofing');
        }else{
            // Return JSON response instead of redirecting
            return response()->json([
                'success' => true,
                'message' => 'Job opened successfully.',
                'approvedSubjectChangesCount' => Session::get('approvedSubjectChangesCount'),
                'awaitApprovalSubjectChangesCount' => Session::get('awaitApprovalSubjectChangesCount')
            ]);
        }
    }
    
    public function closeJob()
    {
        Session::pull('selectedJob');
        Session::pull('selectedSeason');
        Session::pull('openJob');
        Session::pull('approvedSubjectChangesCount');
        Session::pull('awaitApprovalSubjectChangesCount');
        return redirect()->route('proofing');
    }

    public function archive(Request $request)
    {
        $selectedJob = $this->jobService->getJobById($this->getDecryptData($request->input('job'))); 
        $result = $this->jobService->updateJobStatus($selectedJob->ts_job_id, $this->statusService->archived);

        if(Session::has('selectedJob') && Session::has('selectedSeason')){
            return redirect()->route('dashboard.closeJob');
        }
    
        return response()->json([
            'message' => 'The Job "' . $selectedJob->ts_jobname . '" has been archived.'
        ]);
    }

    public function restore(Request $request)
    {
        $selectedJob = $this->jobService->getJobById($this->getDecryptData($request->input('job'))); 
        $result = $this->jobService->updateJobStatus($selectedJob->ts_job_id, $this->statusService->none);
        return response()->json([
            'message' => 'The Job "' . $selectedJob->ts_jobname . '" has been restored.'
        ]);
    }

    public function toggleArchived(Request $request)
    {
        $includeArchived = filter_var($request->get('includeArchived'), FILTER_VALIDATE_BOOLEAN);
        $activeSyncJobs = $this->jobService->toggleArchivedJobs($this->franchiseCode, $includeArchived);
        return response()->json(['data' => $activeSyncJobs]);
    }
    
    public function deleteJob(Request $request)
    {
        $selectedJob = $this->jobService->getJobById($this->getDecryptData($request->input('job'))); 
        $this->jobService->deleteJob($selectedJob->ts_job_id);
        if(Session::has('selectedJob') && Session::has('selectedSeason')){
            return redirect()->route('dashboard.closeJob');
        }
        return response()->json('Job Deleted');
    }
}

