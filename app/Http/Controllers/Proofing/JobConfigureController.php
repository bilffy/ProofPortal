<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\ConfigureService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\UserResource;
use Auth;
use Carbon\Carbon;
use App\Models\Template;
use App\Models\FranchiseUser;
use App\Models\FolderUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JobConfigureController extends Controller
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $folderService;
    protected $configureService;
    protected $seasonService;
    protected $emailService;
    protected $statusService;

    public function __construct(
        EncryptDecryptService $encryptDecryptService, 
        JobService $jobService, 
        FolderService $folderService, 
        ConfigureService $configureService, 
        SeasonService $seasonService, 
        EmailService $emailService, 
        StatusService $statusService
    )
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->folderService = $folderService;
        $this->configureService = $configureService;
        $this->seasonService = $seasonService;
        $this->emailService = $emailService;
        $this->statusService = $statusService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function index($hash){
        $decryptedJobKey = $this->getDecryptData($hash);
        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first();

        if (!$selectedJob) {
            abort(404); 
        }

        // $subjectKeys = $selectedJob->folders->flatMap(function ($folder) {
        //     return $folder->subjects->pluck('ts_subjectkey');
        // });

        $compiledFolderDuplicates = $this->getDuplicateFolder($selectedJob);
        $compiledSubjectDuplicates = $this->getDuplicateSubject($selectedJob);

        $imageCount = $this->configureService->peopleImageCount($selectedJob->ts_job_id);

        if(!Session::has('selectedJob') || session('selectedJob')->ts_jobkey != $selectedJob->ts_jobkey){
            $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first(); // Store session data
            session([
                'selectedJob' => $selectedJob,
                'selectedSeason' => $selectedSeason,
                'openJob' => false
            ]);
            session()->save();
        }

        $selectedFolders = $this->folderService->getFolderByJobId($selectedJob->ts_job_id)->with('images')->orderBy('ts_foldername', 'asc')->get();
        $user = Auth::user();
        
        return view('proofing.franchise.configure.configure-job',[
            'selectedJob' => $selectedJob,
            'hash' =>$hash, 
            'selectedFolders' => $selectedFolders, 
            'compiledFolderDuplicates' => $compiledFolderDuplicates, 
            'compiledSubjectDuplicates' => $compiledSubjectDuplicates ,
            'imageCount' => $imageCount,
            'user' => new UserResource($user)
        ]);
    }

    private function getDuplicateFolder($selectedJob)
    {
        $folderKeys = $selectedJob->folders->pluck('ts_folderkey');
        return $folderKeys->duplicates();
    }

    private function getDuplicateSubject($selectedJob)
    {
        $subjectKeys = $selectedJob->subjects->pluck('ts_subjectkey');
        return $subjectKeys->duplicates();
    }

    public function proofingTimelineInsert(Request $request){
        $proofingTimeline = $this->configureService->insertProofingTimeline($request->all());
        return response()->json(['success' => true]);
    }

    public function proofingTimelineEmailSend(Request $request){
        $this->configureService->sendEmailDates($request->all());
        return response()->json(['success' => true]);
    }

    public function notificationEnable(Request $request)
    {
        $emailNotificationEnable = $request->input('isReviewDateEnabled') === 'true' ? 1 : 0;
        $this->updateJobData($request->input('jobHash'), 'notifications_enabled', $emailNotificationEnable);
        return response()->json(['success' => true]);
    }

    public function notificationMatrixInsert(Request $request)
    {
        // Process the schools array
        $schools = $request->input('schools', []);
        $folders = $request->input('folders', []);

        // Prepare the matrix for schools and folders
        $notificationsMatrix = [
            'schools' => $this->processNotifications($schools),
            'folders' => $this->processNotifications($folders)
        ];
        $decryptedJobKey = $this->getDecryptData($request->input('jobHash'));

        $this->updateJobData($request->input('jobHash'), 'notifications_matrix', json_encode($notificationsMatrix));

        $template = Template::with('emailCategory')
            ->where('template_name', $request->input('fieldTag'))
            ->first();
        
        if ($template && $template->emailCategory && $template->emailCategory->email_category_name === 'Proofing') {
            $this->emailService->updateEmailSend($request->input('fieldTag'), $decryptedJobKey);
        }
        return response()->json(['success' => true]); 
    }

    protected function processNotifications($input)
    {
        $result = [];
        foreach ($input as $field => $values) {
            $result[$field] = [
                'franchise' => in_array('franchise', $values),
                'photocoordinator' => in_array('photocoordinator', $values),
                'teacher' => in_array('teacher', $values),
            ];
        }
        return $result;
    }

    public function updateJobData($hashedJob, $column, $value)
    {
        $decryptedJobKey = $this->getDecryptData($hashedJob);
        $this->jobService->updateJobData($decryptedJobKey, $column, $value);
    }

    public function folderConfigAll(Request $request){
        if ($request->has(['field', 'active_ids', 'inactive_ids'])) {
            $field = $request->input('field');
            $activeIds = json_decode($request->input('active_ids'), true);
            $inactiveIds = json_decode($request->input('inactive_ids'), true);

            $activeIds = empty($activeIds) ? [0] : $activeIds;
            $inactiveIds = empty($inactiveIds) ? [0] : $inactiveIds;

            $isActiveCount = $this->folderService->updateFolderData($activeIds, $field, true);
            $isInactiveCount = $this->folderService->updateFolderData($inactiveIds, $field, false);

            return response()->json([$isActiveCount, $isInactiveCount]);
        }else{
            return response()->json(false);
        }
    }


    // tnj Refresh Config - Merge Duplicate Folders, Subjects, Update Associations, and People Images
    public function handleJobAction($action, $hashedJob)
    {
        $decryptedJobKey = $this->getDecryptData($hashedJob);
        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first();
        
        if (!$selectedJob) {
            abort(404); 
        }

        // Check if proofing has started
        // if ($this->hasProofingStarted($selectedJob)) {
        //     return redirect()->back()->with('error', 'Unable to update for "'.$selectedJob->ts_jobname.'"! Proofing already started.');
        // }

        switch ($action) {
            case 'merge-duplicate-folders':
                $count = $this->getDuplicateFolder($selectedJob)->count();
                $this->configureService->mergeDuplicateFolders($selectedJob->ts_job_id);
                $message = "Merged $count duplicate Folders in \"$selectedJob->ts_jobname\".";
                break;

            case 'merge-duplicate-subjects':
                $count = $this->getDuplicateSubject($selectedJob)->count();
                $this->configureService->mergeDuplicateSubjects($selectedJob->ts_job_id);
                $message = "Merged $count duplicate People in \"$selectedJob->ts_jobname\".";
                break;

            case 'update-subject-associations':
                // $this->configureService->updateSubjectAssociations($selectedJob->ts_job_id);
                $this->configureService->updatePeopleImage($selectedJob->ts_job_id);
                $client = Http::withOptions(['verify' => config('services.bpsync.verify_ssl', true)])->timeout(30);
                // $baseUrl = config('services.bpsync.url');
                $baseUrl = 'http://bpsync.msp.local';
                $jobKey = $selectedJob->ts_jobkey;
                $this->jobService->updateJobData($jobKey, 'force_sync', 1);
                $folderResponse = $client->get("{$baseUrl}/folders/sync/{$jobKey}");
                // Log::info($folderResponse);
                $message = "Linked Folders will be updated for \"$selectedJob->ts_jobname\".";
                
                $folderSuccess = $folderResponse && $folderResponse->successful();
                if ($folderSuccess && $selectedJob) {
                    $franchise = $selectedJob->franchises;
        
                    if ($franchise) {
                        $franchiseUserIds = FranchiseUser::where('franchise_id', $franchise->id)->pluck('user_id');
                        $folderIds = $selectedJob->folders()->pluck('ts_folder_id');
        
                        foreach ($franchiseUserIds as $userId) {
                            foreach ($folderIds as $folderId) {
                                FolderUser::firstOrCreate([
                                    'user_id'      => $userId,
                                    'ts_folder_id' => $folderId
                                ]);
                            }
                        }
                    }
                }
                break;

            case 'update-people-images':
                $this->configureService->updatePeopleImage($selectedJob->ts_job_id);
                $message = "People Images will be updated for \"$selectedJob->ts_jobname\".";
                break;

            default:
                return redirect()->back()->with('error', 'Invalid action.');
        }

        return redirect()->back()->with('message', 'Success! ' . $message);
    }

    // Check if proofing has started
    private function hasProofingStarted($selectedJob)
    {
        return Carbon::today()->gt(Carbon::parse($selectedJob->proof_start));
    }
}
