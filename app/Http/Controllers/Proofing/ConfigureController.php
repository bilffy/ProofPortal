<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\EncryptDecryptService;
use App\Services\JobService;
use App\Services\FolderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\SeasonService;
use App\Services\SchoolService;
use App\Http\Resources\UserResource;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SchoolContextHelper;


class ConfigureController extends Controller
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $folderService;

    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, FolderService $folderService, SeasonService $seasonService, SchoolService $schoolService)
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->folderService = $folderService;
        $this->seasonService = $seasonService;
        $this->schoolService = $schoolService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    //////////////////////////////////-------------------------------Config School------------------------------------///////////////////////////////////////////
    
    public function configSchool()
    {
        $decryptedSchoolKey = SchoolContextHelper::getCurrentSchoolContext()->schoolkey;
        $selectedSchool = $this->schoolService->getSchoolBySchoolKey($decryptedSchoolKey)->first();
        $filePath = '';
        if ($selectedSchool && $selectedSchool->school_logo) {
            $filePath = 'school_logos/' . $selectedSchool->school_logo;
        }
        $hash = Crypt::encryptString(SchoolContextHelper::getCurrentSchoolContext()->schoolkey);
        $encryptedPath = $selectedSchool->school_logo ? Crypt::encryptString($filePath) : '';
        $seasons = $this->seasonService->getAllSeasonData('code', 'is_default', 'ts_season_id')->orderby('code','desc')->get();
        $defaultSeasonCode = $seasons->where('is_default', 1)->select('code', 'ts_season_id')->first();
        $syncJobsbySchoolkey =  $this->jobService->getActiveSyncJobsBySchoolkey($decryptedSchoolKey);
        $selectedFolders = [];
        $user = Auth::user();
        return view('proofing.franchise.school.configure-school',[
            'selectedSchool' => $selectedSchool, 
            'encryptedPath' => $encryptedPath, 
            'hash' => $hash, 
            'seasons' => $seasons, 
            'syncJobsbySchoolkey' => $syncJobsbySchoolkey, 
            'defaultSeasonCode' => $defaultSeasonCode, 
            'selectedFolders' => $selectedFolders,
            'user' => new UserResource($user)
        ]);
    }

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
            $jobWithRelations = $this->jobService->geJobsByTSJobID($job->ts_job_id); 

            if ($jobWithRelations->folders->isNotEmpty()) {
                $selectedFolders = $jobWithRelations->folders->map(function ($folder) {
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
                        'students' => $subjectsWithImages->count() // Count of attached subjects with images
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
        $foldersHtml = view('proofing.franchise.school.configure-school-folderconfig', compact('selectedFolders'))->render();

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
        // Validate the file first, including file type and size
        $request->validate([
            'schoolLogo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Require an image of specific types and size limit
        ]);
    
        $decryptedSchoolKey = $this->getDecryptData($request->schoolKey);
    
        // Delete existing logo if it exists
        $this->deleteExistingLogo($decryptedSchoolKey);
    
        // Handle file upload
        if ($request->hasFile('schoolLogo')) {
            $filename = $this->storeLogoFile($request->file('schoolLogo'));
            
            // Save the new logo filename in the database
            $this->schoolService->saveSchoolData($decryptedSchoolKey, 'school_logo', $filename);
    
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
        
        // Prepare the matrix for digital_download_notification and digital_download_permission
        $notificationsMatrix = [
            'digital_download_permission' => $this->processDigitalDownload($digital_download_permission),
            'digital_download_notification' => $this->processDigitalDownload($digital_download_notification)
        ];
       
        $decryptedSchoolKey = $this->getDecryptData($request->input('schoolKey'));
        $this->schoolService->saveSchoolData($decryptedSchoolKey, 'digital_download_permission_notification', json_encode($notificationsMatrix));
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

    public function configSchoolJobChangeUpdate(Request $request)
    {
        $decryptedJobKey = $this->getDecryptData($request->jobKey);
        $cleanedDate = preg_replace('/\s?(AM|PM)$/i', '', $request->newData);
        $parsedDate = Carbon::createFromFormat('d/m/Y H:i', $cleanedDate);
        $job = $this->jobService->updateJobData($decryptedJobKey, $request->field, $parsedDate->format('Y-m-d H:i:s'));
        return response()->json(['success' => true, 'message' => 'Job updated successfully.']);
    }

    public function configSchoolFolderChangeUpdate(Request $request)
    {
        $folderIds = explode(',', $request->folderId);
        if (is_array($folderIds)) {
            foreach ($folderIds as $folderId) {
                $decryptedFolderId[] = $this->getDecryptData($folderId);
            }
            $decryptedFolderIds = is_array($decryptedFolderId) ? $decryptedFolderId : [$decryptedFolderId];
            $this->folderService->updateFolderData($decryptedFolderIds, $request->field, $request->newValue);
        }

        return response()->json(['success' => true, 'message' => 'Folder updated successfully.']);
    }

}
