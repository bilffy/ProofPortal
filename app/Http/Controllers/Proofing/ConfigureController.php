<?php

namespace App\Http\Controllers\Proofing;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Job;
use App\Models\School;
use App\Models\Template;
use App\Models\FranchiseUser;
use App\Models\FolderUser;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\SchoolService;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\ConfigureService;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SchoolContextHelper;
use Illuminate\Support\Facades\Http;


class ConfigureController extends Controller
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $folderService;
    protected $configureService;
    protected $seasonService;
    protected $schoolService;
    protected $emailService;
    protected $statusService;

    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, FolderService $folderService, ConfigureService $configureService, SeasonService $seasonService, SchoolService $schoolService, EmailService $emailService, StatusService $statusService)
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->folderService = $folderService;
        $this->configureService = $configureService;
        $this->seasonService = $seasonService;
        $this->schoolService = $schoolService;
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

        if(!Session::has('selectedJob') && !Session::has('selectedSeason')){
            $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first(); // Store session data
            session([
                'selectedJob' => $selectedJob,
                'selectedSeason' => $selectedSeason,
                'openJob' => false
            ]);
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
    }

    public function proofingTimelineEmailSend(Request $request){
        $this->configureService->sendEmailDates($request->all());
    }

    public function notificationEnable(Request $request)
    {
        $emailNotificationEnable = $request->input('isReviewDateEnabled') === 'true' ? 1 : 0;
        $this->updateJobData($request->input('jobHash'), 'notifications_enabled', $emailNotificationEnable);
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
                $client = Http::withoutVerifying()->timeout(30);
                $baseUrl = 'http://bpsync.msp.local/index.php';
                $jobKey = $selectedJob->ts_jobkey;
                $this->jobService->updateJobData($jobKey, 'force_sync', 1);
                $folderResponse = $client->get("{$baseUrl}/folders/sync/{$jobKey}");
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

    //////////////////////////////////-------------------------------Config School------------------------------------///////////////////////////////////////////
    
    // Removed, logic is moved to configure-new blade file
    // public function configSchool()
    // {
    //     $decryptedSchoolKey = SchoolContextHelper::getCurrentSchoolContext()->schoolkey;
    //     $selectedSchool = $this->schoolService->getSchoolBySchoolKey($decryptedSchoolKey)->first();
    //     $filePath = '';
    //     if ($selectedSchool && $selectedSchool->school_logo) {
    //         $filePath = 'school_logos/' . $selectedSchool->school_logo;
    //     }
    //     $hash = Crypt::encryptString(SchoolContextHelper::getCurrentSchoolContext()->schoolkey);
    //     $encryptedPath = $selectedSchool->school_logo ? Crypt::encryptString($filePath) : '';
    //     $seasons = $this->seasonService->getAllSeasonData('code', 'is_default', 'ts_season_id')->orderby('code','desc')->get();
    //     $defaultSeasonCode = $seasons->where('is_default', 1)->select('code', 'ts_season_id')->first();
    //     $syncJobsbySchoolkey =  $this->jobService->getActiveSyncJobsBySchoolkey($decryptedSchoolKey);
    //     $selectedFolders = [];

    //     return view('proofing.franchise.school.configure-school', [
    //         'selectedSchool' => $selectedSchool, 
    //         'encryptedPath' => $encryptedPath, 
    //         'hash' => $hash, 
    //         'seasons' => $seasons, 
    //         'syncJobsbySchoolkey' => $syncJobsbySchoolkey, 
    //         'defaultSeasonCode' => $defaultSeasonCode, 
    //         'selectedFolders' => $selectedFolders,
    //         'user' => new UserResource(Auth::user()),
    //     ]);
    // }

    public function showSchoolLogo($encryptedPath)
    {
        try {
            // Assuming you're decrypting the path or using it to find the file
            $filePath = Crypt::decryptString($encryptedPath);
            $path = storage_path('app/public/' . $filePath);  // Modify based on the correct location of the file

            // Check if the file exists
            if (!file_exists($path)) {
                abort(404, 'File not found');
            }

            // Get the MIME type of the file
            $mimeType = mime_content_type($path);

            // Return the file as a response
            return response()->file($path, ['Content-Type' => $mimeType]);

        } catch (\Exception $e) {
            abort(404, 'Invalid or expired URL');
        }
    }

    public function configSchoolFetchJobs(Request $request)
    {
        $decryptedSeasonID = $this->getDecryptData($request->ts_season_id);
        $decryptedSchoolkey = $this->getDecryptData($request->schoolkey);

        // Fetch all jobs by season and schoolkey
        $jobs = $this->jobService->getJobsBySeason($decryptedSchoolkey, $decryptedSeasonID);

        // Map through all jobs to get the job details along with folders and associated data
        $jobsWithDetails = $jobs->map(function ($job) {
            // Initialize $selectedFolders as an empty array
            $selectedFolders = [];

            // Transform folders for each job
            $jobWithRelations = $this->jobService->getJobsByTSJobID($job->ts_job_id); 

            if ($jobWithRelations->folders->isNotEmpty()) {
                $selectedFolders = $jobWithRelations->folders->map(function ($folder) {
                    $folderWithImage = $folder->images ?? collect();

                    $subjectsWithImages = $folder->subjects->filter(function ($subject) {
                        return $subject->images !== null; // Check if the subject has an image
                    });
    
                    // $attachedSubjectsWithImages = $folder->attachedsubjects->filter(function ($attachedSubject) {
                    //     return $attachedSubject->images !== null; // Attached subjects with images
                    // });
    
                    return [
                        'ts_foldername' => $folder->ts_foldername,
                        'ts_folder_id' => $folder->ts_folder_id,
                        'tag' => $folder->folderTags->external_name ?? null, // Handle null if folderTags is empty
                        'is_visible_for_portrait' => $folder->is_visible_for_portrait,
                        'is_visible_for_group' => $folder->is_visible_for_group,
                        'groupCount' => is_countable($folderWithImage) ? $folderWithImage->count() : 0,
                        'students' => $subjectsWithImages->count(), // Count of homed subjects with images
                        'attached' => $folder->attachedsubjects->count(), // Count of attached subjects with images
                    ];
                })->toArray();
            }
        
            // Prepare job details for each job
            return [
                'ts_jobkey' => Crypt::encryptString($jobWithRelations->ts_jobkey),
                'ts_jobname' => $jobWithRelations->ts_jobname,
                'download_available_date' => $jobWithRelations->download_available_date,
                'portrait_download_date' => $jobWithRelations->portrait_download_date,
                'group_download_date' => $jobWithRelations->group_download_date,
                'Folders' => $selectedFolders
            ];
        });

        return response()->json($jobsWithDetails);
    }

    public function configSchoolFolderConfig(Request $request)
    {
        // Get the selected folders from the request
        $selectedFolders = $request->folders;

        // Render the folder configuration view with the selected folders
        $foldersHtml = view('partials.photography.configure.folders', compact('selectedFolders'))->render();

        // Return the rendered HTML in the JSON response
        return response()->json([
            'html' => $foldersHtml
        ]);
    }

    public function configSchoolChangeUpdate(Request $request)
    {
        $decryptedSchoolKey = $this->getDecryptData($request->schoolKey);
        $this->schoolService->saveSchoolData($decryptedSchoolKey, $request->field, $request->newData);
    }

    public function uploadSchoolLogo(Request $request)
    {
        $imgExtensions = ImageHelper::getExtensionsAsString();
        $imageValidator = 'required|image|mimes:' . $imgExtensions . '|max:2048';
        // Validate the file first, including file type and size
        $request->validate([
            'schoolLogo' => $imageValidator, // Require an image of specific types and size limit
        ]);
    
        $decryptedSchoolKey = $this->getDecryptData($request->schoolKey);
    
        // Delete existing logo if it exists
        $this->deleteExistingLogo($decryptedSchoolKey);
    
        // Handle file upload
        if ($request->hasFile('schoolLogo')) {
            $filename = $this->storeLogoFile($request->file('schoolLogo'));
            
            // Save the new logo filename in the database
            $this->schoolService->saveSchoolData($decryptedSchoolKey, 'school_logo', $filename);

            // Log UPLOAD_SCHOOL_LOGO activity
            ActivityLogHelper::log(LogConstants::UPLOAD_SCHOOL_LOGO, [
                'file_name' => $filename,
                'file_size' => $request->file('schoolLogo')->getSize(),
                'file_type' => $request->file('schoolLogo')->getClientMimeType(),
            ]);
    
            // Return a successful response with the new path
            return response()->json(['success' => true, 'message' => 'School logo uploaded successfully.', 'path' => 'storage/school_logos/' . $filename]);
        }
    
        // If no file was found, return an error response
        return response()->json(['success' => false, 'message' => 'No file uploaded or file is invalid.'], 400);
    }
    
    public function deleteSchoolLogo(Request $request)
    {
        $decryptedSchoolKey = $this->getDecryptData($request->schoolKey);
        if ($this->deleteExistingLogo($decryptedSchoolKey)) {
            return response()->json(['success' => true, 'message' => 'School logo deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No logo found to delete.'], 404);
    }
    
    // Helper method to delete an existing logo
    private function deleteExistingLogo($schoolKey)
    {
        $selectedSchool = $this->schoolService->getSchoolBySchoolKey($schoolKey)->first();
        if ($selectedSchool && $selectedSchool->school_logo) {
            if (Storage::disk('public')->exists('school_logos/' . $selectedSchool->school_logo)) {
                // Delete the file from the storage
                Storage::disk('public')->delete('school_logos/' . $selectedSchool->school_logo);
                // Update the database to remove the logo reference
                $this->schoolService->saveSchoolData($schoolKey, 'school_logo', null);
                return true;
            }
        }
        return false;
    }
    
    // Helper method to store a new logo file
    private function storeLogoFile($file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $uniqueSuffix = time() . '_' . uniqid(); // Adds a unique suffix based on time and a unique ID
        $filename = $originalFilename . '_' . $uniqueSuffix . '.' . $file->getClientOriginalExtension();
        $file->storeAs('school_logos', $filename, 'public'); // Store the file in 'public/school_logos'
        return $filename;
    }    

    public function configSchoolDigitalDownload(Request $request)
    {
        $digital_download_permission = $request->input('digital_download_permission', []);
        $digital_download_notification = $request->input('digital_download_notification', []);
        $decryptedSchoolKey = $this->getDecryptData($request->input('schoolKey'));
        $school = School::where('schoolkey',$decryptedSchoolKey)->first();
        $schoolData = json_decode($school->digital_download_permission_notification, true);
        // Prepare the matrix for digital_download_notification and digital_download_permission
        $notificationsMatrix = [
            'digital_download_permission' => array_replace_recursive($schoolData['digital_download_permission'] ?? [], $this->convertStringToBoolean($digital_download_permission)),
            'digital_download_notification' => array_replace_recursive($schoolData['digital_download_notification'] ?? [], $this->convertStringToBoolean($digital_download_notification))
            // 'digital_download_permission' => $this->processDigitalDownload($digital_download_permission),
            // 'digital_download_notification' => $this->processDigitalDownload($digital_download_notification)
        ];
        
        $this->schoolService->saveSchoolData($decryptedSchoolKey, 'digital_download_permission_notification', json_encode($notificationsMatrix));
        $school = School::where('schoolkey',$decryptedSchoolKey)->first();
        // Log UPDATE_SCHOOL_DOWNLOAD_PERMISSIONS activity
        ActivityLogHelper::log(LogConstants::UPDATE_SCHOOL_DOWNLOAD_PERMISSIONS, [
            'school' => $school->id,
            'school_key' => $decryptedSchoolKey,
            'digital_download_permission_notification' => $notificationsMatrix,
        ]);
    }

    protected function processDigitalDownload($input)
    {
        $result = [];
        foreach ($input as $field => $values) {
            $result[$field] = [
                'photocoordinator' => in_array('photocoordinator', $values),
                'schooladmin' => in_array('schooladmin', $values),
                'teacher' => in_array('teacher', $values),
            ];
        }
        return $result;
    }

    protected function convertStringToBoolean(array $array): array {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Recursively process nested arrays
                $array[$key] = $this->convertStringToBoolean($value);
            } elseif ($value === 'true') {
                // Convert "true" string to boolean true
                $array[$key] = true;
            } elseif ($value === 'false') {
                // Convert "false" string to boolean false
                $array[$key] = false;
            }
        }
        return $array;
    }

    public function configSchoolJobChangeUpdate(Request $request)
    {
        $decryptedJobKey = $this->getDecryptData($request->jobKey);
        $cleanedDate = preg_replace('/\s?(AM|PM)$/i', '', $request->newData);
        $parsedDate = Carbon::createFromFormat('d/m/Y H:i', $cleanedDate);
        $job = $this->jobService->updateJobData($decryptedJobKey, $request->field, $parsedDate->format('Y-m-d H:i:s'));

        $thisJob = Job::where('ts_jobkey', $decryptedJobKey)->first();
        $school = School::where('schoolkey', $thisJob->ts_schoolkey)->first();
        // Log UPDATE_SCHOOL_DOWNLOAD_TIMELINE_CONFIG activity
        ActivityLogHelper::log(LogConstants::UPDATE_SCHOOL_DOWNLOAD_TIMELINE_CONFIG, [
            'school' => $school->id,
            'school_key' => $thisJob->ts_schoolkey,
            'job_id' => $thisJob->ts_job_id,
            $request->field => $parsedDate->format('Y-m-d H:i:s'),
        ]);
        
        return response()->json(['success' => true, 'message' => 'Job updated successfully.']);
    }

    public function configSchoolFolderChangeUpdate(Request $request)
    {
        $school = SchoolContextHelper::getCurrentSchoolContext();
        $folderIds = explode(',', $request->folderId);
        if (is_array($folderIds)) {
            foreach ($folderIds as $folderId) {
                $decryptedFolderId[] = $this->getDecryptData($folderId);
            }
            $decryptedFolderIds = is_array($decryptedFolderId) ? $decryptedFolderId : [$decryptedFolderId];
            $this->folderService->updateFolderData($decryptedFolderIds, $request->field, $request->newValue);

            $folder = Folder::where('ts_folder_id', $decryptedFolderIds[0])->first();
            // Log UPDATE_SCHOOL_FOLDER_CONFIG activity
            ActivityLogHelper::log(LogConstants::UPDATE_SCHOOL_FOLDER_CONFIG, [
                'school' => $school->id,
                'school_key' => $school->schoolkey,
                'job_id' => $folder->ts_job_id,
                'folder_key' => $folder->ts_folderkey,
                $request->field => $request->newValue,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Folder updated successfully.']);
    }

}
