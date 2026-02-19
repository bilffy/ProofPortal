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
use Illuminate\Http\Request;
use App\Models\FranchiseUser;
use App\Models\JobUser;
use App\Models\FolderUser;
use Illuminate\Support\Facades\Http;
use App\Helpers\SchoolContextHelper;
use Illuminate\Support\Facades\URL;
use Auth;

class ProofingJobController extends Controller
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

    public function openJob(Request $request)
    {
        if(empty($request->query('jobKey')) && Session::has('selectedJob')){
                $selectedJob = session('selectedJob');
        }else{
            $jobKey = $request->query('jobKey');
            if(isset($jobKey)){
                $selectedJob = $this->jobService->getJobByJobKey($this->getDecryptData($jobKey))->first(); 
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
   
        if(empty($request->query('jobKey')) && Session::has('selectedJob')){
            return redirect()->route('proofing');
        }else{
            // Return JSON response instead of redirecting
            return response()->json([
                'success' => true,
                'message' => 'Job opened successfully.',
                'redirectUrl' => URL::signedRoute('proofing.dashboard', ['hash' => $this->encryptDecryptService->encryptStringMethod($selectedJob->ts_jobkey)]),
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
        $franchiseCode = Auth::user()->getSchoolOrFranchiseDetail()->alphacode;
        $activeSyncJobs = $this->jobService->toggleArchivedJobs($franchiseCode, $schoolKey, $includeArchived);
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

    public function proxySyncJob(Request $request)
    {
        $jobKey = $this->getDecryptData($request->input('jobKey'));
        $selectedJob = $this->jobService->getJobByJobKey($jobKey)->first();
        // $baseUrl = config('services.bpsync.url');
        $baseUrl = 'http://bpsync.msp.local/index.php/';
    
        try {
            $client = Http::withOptions(['verify' => config('services.bpsync.verify_ssl', true)])->timeout(30);
    
            $jobResponse = null;
            $folderResponse = null;
    
            // Case 1: Job does NOT exist → Job + Folder sync
            if (!$selectedJob) {
                $jobResponse = $client->get("{$baseUrl}/jobs/sync/{$jobKey}");
                $folderResponse = $client->get("{$baseUrl}/folders/sync/{$jobKey}");
                \Log::info($jobResponse);
                \Log::info($folderResponse);
                $selectedJob = $this->jobService->getJobByJobKey($jobKey)->first();
            } 
            // Case 2: Job exists → Folder sync only (This logs the number of associations to sync)
            else {
                if ($selectedJob->job_status_id == $this->statusService->deleted) {
                    $this->jobService->updateJobData($jobKey, 'job_status_id', $this->statusService->none);
                }
                $folderResponse = $client->get("{$baseUrl}/folders/sync/{$jobKey}");
                \Log::info($folderResponse);
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
}
