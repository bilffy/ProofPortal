<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\SchoolService;
use Illuminate\Support\Facades\Session;
use App\Services\Proofing\JobService;
use App\Services\Proofing\TimestoneTableService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\FranchiseUser;
use App\Models\JobUser;
use App\Models\FolderUser;
use Illuminate\Support\Facades\Http;
use App\Helpers\SchoolContextHelper;
use Auth;

class ProofHomeController extends Controller
{
    protected $jobService;
    protected $franchiseCode;
    protected $encryptDecryptService;
    protected $schoolService;
    protected $statusService;
    protected $seasonService;
    protected $proofingChangelogService;
    protected $timestoneTableService;

    public function __construct(JobService $jobService, SchoolService $schoolService, EncryptDecryptService $encryptDecryptService, StatusService $statusService, SeasonService $seasonService, ProofingChangelogService $proofingChangelogService, TimestoneTableService $timestoneTableService)
    {

        $this->jobService = $jobService;
        $this->franchiseCode = Auth::user()->getSchoolOrFranchiseDetail()->alphacode;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->schoolService = $schoolService;
        $this->statusService = $statusService;
        $this->seasonService = $seasonService;
        $this->proofingChangelogService = $proofingChangelogService;
        $this->timestoneTableService = $timestoneTableService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function index(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        if(Session::has('selectedJob') && Session::has('selectedSeason') && Session::get('openJob') === true) {
            $selectedJob = session('selectedJob') ?? '[]';
            
            if (!$selectedJob) {
                abort(404); 
            }

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
            $data = $this->jobService->getDashboardData($this->franchiseCode);
        
            // Pass both $user and $data to the view
            return view('proofing.proofing-home', [
                'user' => new UserResource($user), // Passing the authenticated user
                'data' => $data, // Passing the dashboard data
            ]);
        } elseif($user->hasRole('Photo Coordinator') || $user->hasRole('Teacher')) {
            $currentSchool = $this->schoolService
            ->getSchoolById(Session::get('school_context-sid'))
            ->first(['schoolkey']);
        
            if ($currentSchool) {
                $jobs = $user->jobs()
                    ->select('jobs.ts_job_id', 'ts_jobname', 'ts_season_id', 'ts_schoolkey')
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

    public function viewSeason()
    {
        $user = Auth::user();
        $allSeasons = $this->seasonService->getAllSeasonData('ts_season_id', 'code', 'ts_season_key', 'is_default')->orderBy('id', 'desc')->get();
        return view('proofing.open-season', [
            'user' => new UserResource($user), // Passing the authenticated user
            'allSeasons' => $allSeasons
        ]);
    }

    public function passSeason(Request $request)
    {   
        // Store session data
        session([
            'openSeason' => true
        ]);
        return redirect()->route('dashboard.openSeason',['selectedSeasonId' => $request->season_key_hash]);
    }

    public function openSeason(Request $request, $selectedSeason)
    {
        $getSeason = $this->getDecryptData($selectedSeason);
        $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($getSeason)->first();
    
        if ($selectedSeason) {
            session([
                'selectedSeasonDashboard' => [
                    'ts_season_id' => $selectedSeason->ts_season_id,
                    'code' => $selectedSeason->code,
                ],
                'job-season-flag' => true
            ]);
        }

        $user = Auth::user();

        $tsJobs = $this->timestoneTableService->getAllTimestoneJobsBySeasonID($getSeason, $user->getFranchise()->ts_account_id, SchoolContextHelper::getCurrentSchoolContext()->schoolkey)->get();
    
        return view('proofing.open-season-job', [
            'user' => new UserResource($user),
            'selectedSeason' => $selectedSeason,
            'tsJobs' => $tsJobs
        ]);
    }   
    
    public function closeSeason()
    {
        // Forget only the keys we intend to remove
        session()->forget([
            'job-season-flag',
            'selectedJob',
            'openJob',
            'selectedSeason',
            'selectedSeasonDashboard',
            'openSeason'
        ]);
    
        // Force save then regenerate ID so the browser receives a fresh session cookie
        session()->save();
        session()->regenerate();
    
        return redirect()->route('proofing');
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

        if (!$selectedJob) {
            abort(404); 
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
        $message = session()->pull('message');

        Session::forget([
            'selectedJob',
            'selectedSeason',
            'openJob',
            'approvedSubjectChangesCount',
            'awaitApprovalSubjectChangesCount'
        ]);
        Session::save();

        // \Log::info('Session cleared for Job closure:', [
        //     'selectedJob' => session('selectedJob'),
        //     'selectedSeason' => session('selectedSeason'),
        //     'openJob' => session('openJob'),
        //     'approvedCount' => session('approvedSubjectChangesCount'),
        //     'pendingCount' => session('awaitApprovalSubjectChangesCount'),
        // ]);

        if ($message) {
            session()->flash('message', $message);
        }

        return redirect()->route('proofing');
    }
    
    // public function proxySyncJob(Request $request)
    // {
    //     $jobKey = $this->getDecryptData($request->input('jobKey'));
    //     $selectedJob = $this->jobService->getJobByJobKey($jobKey)->first();
    
    //     try {
    //         // === Case 1: Job not found locally — sync both job and folder ===
    //         if (!$selectedJob) {
    //             $jobResponse = Http::withoutVerifying()->get("http://bpsync.msp.local/index.php/jobs/sync/{$jobKey}");
    //             $folderResponse = Http::withoutVerifying()->get("http://bpsync.msp.local/index.php/folders/sync/{$jobKey}");
    //         }
    
    //         // === Case 2: Job exists but unsynced/pending — update job + sync only folders ===
    //         elseif (
    //             $selectedJob->jobsync_status_id === $this->statusService->unsync &&
    //             $selectedJob->foldersync_status_id === $this->statusService->pending
    //         ) {
    //             $this->jobService->updateJobData($jobKey, 'jobsync_status_id', $this->statusService->sync);
    
    //             $folderResponse = Http::withoutVerifying()->get("http://bpsync.msp.local/index.php/folders/sync/{$jobKey}");
    //             $jobResponse = null; // Not needed in this case
    //         }
    
    //         // === Case 3: Already synced — no need to do anything ===
    //         else {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Job already synced.'
    //             ]);
    //         }
    
    //         // === Evaluate Sync Results ===
    //         if (
    //             (isset($jobResponse) ? $jobResponse->successful() : true) && 
    //             isset($folderResponse) && $folderResponse->successful()
    //         ) {
    //             return response()->json([
    //                 'success' => true,
    //                 'jobs' => $jobResponse?->json(),
    //                 'folders' => $folderResponse->json(),
    //             ]);
    //         }
    
    //         // === Handle Sync Failures ===
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'One or more sync calls failed.',
    //             'job_status' => $jobResponse?->status(),
    //             'folder_status' => $folderResponse?->status(),
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }    

    public function proxySyncJob(Request $request)
    {
        $jobKey = $this->getDecryptData($request->input('jobKey'));
        $selectedJob = $this->jobService->getJobByJobKey($jobKey)->first();
        $baseUrl = 'http://bpsync.msp.local/index.php';
    
        try {
            $client = Http::withoutVerifying()->timeout(30);
    
            $jobResponse = null;
            $folderResponse = null;
    
            // Case 1: Job does NOT exist → Job + Folder sync
            if (!$selectedJob) {
                $jobResponse = $client->get("{$baseUrl}/jobs/sync/{$jobKey}");
                $folderResponse = $client->get("{$baseUrl}/folders/sync/{$jobKey}");
                $selectedJob = $this->jobService->getJobByJobKey($jobKey)->first();
            } 
            // Case 2: Job exists → Folder sync only (Force Sync)
            else {
                if ($selectedJob->job_status_id == $this->statusService->deleted) {
                    $this->jobService->updateJobData($jobKey, 'job_status_id', $this->statusService->none);
                }
                $folderResponse = $client->get("{$baseUrl}/folders/sync/{$jobKey}");
            }
    
            // Validate responses
            $jobSuccess = is_null($jobResponse) || $jobResponse->successful();
            $folderSuccess = $folderResponse && $folderResponse->successful();
    
            if ($jobSuccess && $folderSuccess && $selectedJob) {
                $franchise = $selectedJob->franchises;
    
                if ($franchise) {
                    $franchiseUserIds = FranchiseUser::where('franchise_id', $franchise->id)->pluck('user_id');
                    $folderIds = $selectedJob->folders()->pluck('ts_folder_id');
    
                    foreach ($franchiseUserIds as $userId) {
                        JobUser::firstOrCreate([
                            'user_id'   => $userId,
                            'ts_job_id' => $selectedJob->ts_job_id
                        ]);
    
                        foreach ($folderIds as $folderId) {
                            FolderUser::firstOrCreate([
                                'user_id'      => $userId,
                                'ts_folder_id' => $folderId
                            ]);
                        }
                    }
                }
    
                return response()->json([
                    'success' => true,
                    'jobs'    => $jobResponse?->json(),
                    'folders' => $folderResponse->json(),
                ]);
            }
    
            return response()->json([
                'success'        => false,
                'message'        => 'Sync failed.',
                'job_status'     => $jobResponse?->status(),
                'folder_status'  => $folderResponse?->status(),
            ], 502);
    
        } catch (\Throwable $e) {
            \Log::error('Proxy sync failed', [
                'jobKey' => $jobKey,
                'error'  => $e->getMessage()
            ]);
    
            return response()->json([
                'success' => false,
                'error'   => 'Internal server error'
            ], 500);
        }
    }

    public function archive(Request $request)
    {
        $selectedJob = $this->jobService->getJobById($this->getDecryptData($request->input('job'))); 
        
        if (!$selectedJob) {
            abort(404);  
        }

        $result = $this->jobService->updateJobStatus($selectedJob->ts_job_id, $this->statusService->archived);

        if(Session::has('selectedJob') && Session::has('selectedSeason')){
            return redirect()->route('dashboard.closeJob')
            ->with('message', 'The Job "' . $selectedJob->ts_jobname . '" has been archived.');
        }
    
        return response()->json([
            'message' => 'The Job "' . $selectedJob->ts_jobname . '" has been archived.'
        ]);
    }

    public function restore(Request $request)
    {
        $selectedJob = $this->jobService->getJobById($this->getDecryptData($request->input('job'))); 

        if (!$selectedJob) {
            abort(404); 
        }

        $result = $this->jobService->updateJobStatus($selectedJob->ts_job_id, $this->statusService->active);
        return response()->json([
            'message' => 'The Job "' . $selectedJob->ts_jobname . '" has been restored.'
        ]);
    }

    public function toggleArchived(Request $request)
    {
        $includeArchived = filter_var($request->get('includeArchived'), FILTER_VALIDATE_BOOLEAN);
        $schoolKey = SchoolContextHelper::getSchool()->schoolkey;
        $activeSyncJobs = $this->jobService->toggleArchivedJobs($this->franchiseCode, $schoolKey, $includeArchived);
        return response()->json(['data' => $activeSyncJobs]);
    }
    
    public function deleteJob(Request $request)
    {
        $selectedJob = $this->jobService->getJobById($this->getDecryptData($request->input('job'))); 

        if (!$selectedJob) {
            abort(404); 
        }
        
        $this->jobService->deleteJob($selectedJob->ts_jobkey);
        if(Session::has('selectedJob') && Session::has('selectedSeason')){
            return redirect()->route('dashboard.closeJob')
                         ->with('message', 'The Job "' . $selectedJob->ts_jobname . '" has been deleted.');
        }
        return response()->json('Job Deleted');
    }
}

