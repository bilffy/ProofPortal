<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\SchoolService;
use App\Services\Proofing\SeasonService;
use App\Models\School;
use App\Models\Folder;
use App\Helpers\SchoolContextHelper;
use App\Helpers\SchoolLogoHelper;
use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Crypt;
use App\Http\Resources\UserResource;
use Auth;
use Carbon\Carbon;

class SchoolConfigureController extends Controller
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $folderService;
    protected $seasonService;
    protected $schoolService;

    public function __construct(
        EncryptDecryptService $encryptDecryptService, 
        JobService $jobService, 
        FolderService $folderService, 
        SeasonService $seasonService, 
        SchoolService $schoolService
    )
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

    public function showSchoolLogo($encryptedPath)
    {
        try {
            $filePath = Crypt::decryptString($encryptedPath);
            $legacyPath = storage_path('app/public/' . ltrim($filePath, '/'));

            if (is_file($legacyPath)) {
                return response()->file($legacyPath, [
                    'Content-Type' => mime_content_type($legacyPath),
                ]);
            }

            $school = $this->resolveSchoolFromLogoPath($filePath);

            if ($school !== null) {
                $filename = basename($filePath);
                $localPath = SchoolLogoHelper::resolveLocalPath($school, $filename);

                if ($localPath !== null) {
                    return response()->file($localPath, [
                        'Content-Type' => mime_content_type($localPath),
                    ]);
                }

                $remoteContents = SchoolLogoHelper::fetchRemoteContents($school, $filename);

                if ($remoteContents !== null) {
                    return response($remoteContents)->header(
                        'Content-Type',
                        $this->mimeTypeFromFilename($filename)
                    );
                }
            }

            abort(404, 'File not found');
        } catch (\Exception $e) {
            abort(404, 'Invalid or expired URL');
        }
    }

    public function configSchoolFetchJobs(Request $request)
    {
        $decryptedSeasonID = $this->getDecryptData($request->ts_season_id);
        $decryptedSchoolkey = $this->getDecryptData($request->schoolkey);

        // Fetch all jobs by season and schoolkey
        $jobs = $this->jobService->getJobsBySeason($decryptedSchoolkey, $decryptedSeasonID)->where('show_portal',1)
        ->get();

        // Map through all jobs to get the job details along with folders and associated data
        $jobsWithDetails = $jobs->map(function ($job) {
            // Initialize $selectedFolders as an empty array
            $selectedFolders = [];

            // Transform folders for each job
            $jobWithRelations = $this->jobService->getJobsByTSJobID($job->ts_job_id); 

            if ($jobWithRelations->folders->isNotEmpty()) {
                $selectedFolders =$jobWithRelations->folders
                ->filter(function ($folder) {
                    return !is_null($folder->ts_folderkey);
                })
                ->map(function ($folder) {
                    $folderWithImage = $folder->images ?? collect();

                    $subjectsWithImages = $folder->subjects->filter(function ($subject) {
                        return $subject->images !== null;
                    });

                    $attachedSubjectsWithImages = $folder->attachedsubjects->filter(function ($attachedSubject) {
                        return $attachedSubject->images !== null;
                    });
    
                    return [
                        'ts_foldername' => $folder->portal_ts_foldername,
                        'ts_folder_id' => $folder->ts_folder_id,
                        'tag' => $folder->folderTags->external_name ?? null, // Handle null if folderTags is empty
                        'is_visible_for_portrait' => $folder->is_visible_for_portrait,
                        'is_visible_for_group' => $folder->is_visible_for_group,
                        'groupCount' => is_countable($folderWithImage) ? $folderWithImage->count() : 0,
                        'students' => $subjectsWithImages->count(),
                        'attached' => $attachedSubjectsWithImages->count(),
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
                'has_visible_portrait' => !empty($selectedFolders) && collect($selectedFolders)->contains('is_visible_for_portrait', 1),
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
        return response()->json(['success' => true]);
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
        $school = School::with('franchises')->where('schoolkey', $decryptedSchoolKey)->firstOrFail();

        $this->deleteExistingLogo($decryptedSchoolKey);

        if ($request->hasFile('schoolLogo')) {
            $uploadedFile = $request->file('schoolLogo');
            $fileSize = $uploadedFile->getSize();
            $fileType = $uploadedFile->getClientMimeType();

            try {
                $filename = $this->storeLogoFile($uploadedFile, $school);
            } catch (\Throwable $e) {
                Log::error('School logo upload failed', [
                    'school_id' => $school->id,
                    'schoolkey' => $school->schoolkey,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'School logo upload failed. Please contact support if this continues.',
                ], 500);
            }

            $this->schoolService->saveSchoolData($decryptedSchoolKey, 'school_logo', $filename);

            ActivityLogHelper::log(LogConstants::UPLOAD_SCHOOL_LOGO, [
                'file_name' => $filename,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'remote_path' => SchoolLogoHelper::relativePath($school, $filename),
            ], auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'School logo uploaded successfully.',
                'path' => SchoolLogoHelper::publicUrl($school, $filename)
                    ?? SchoolLogoHelper::relativePath($school, $filename),
            ]);
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
        $selectedSchool = School::with('franchises')
            ->where('schoolkey', $schoolKey)
            ->first();

        if ($selectedSchool && $selectedSchool->school_logo) {
            SchoolLogoHelper::delete($selectedSchool, $selectedSchool->school_logo);
            $this->schoolService->saveSchoolData($schoolKey, 'school_logo', null);

            return true;
        }

        return false;
    }

    private function storeLogoFile($file, School $school)
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $filename = 'school_logo_' . time() . '_' . uniqid() . '.' . $extension;

        SchoolLogoHelper::store($file, $school, $filename);

        return $filename;
    }

    private function mimeTypeFromFilename(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => 'image/jpeg',
        };
    }

    private function resolveSchoolFromLogoPath(string $filePath): ?School
    {
        $segments = explode('/', trim($filePath, '/'));

        if (count($segments) < 5 || $segments[0] !== 'school_logos') {
            return null;
        }

        $schoolKey = $segments[2] ?? null;
        $schoolId = $segments[3] ?? null;

        if ($schoolKey === null || $schoolId === null) {
            return null;
        }

        return School::with('franchises')
            ->where('schoolkey', $schoolKey)
            ->where('id', $schoolId)
            ->first();
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
        
        return response()->json(['success' => true]);
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

        $thisJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first();
        
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
        $folderIds = array_filter(array_map('trim', explode(',', (string) $request->folderId)));
        $decryptedFolderId = [];

        foreach ($folderIds as $folderId) {
            $decryptedFolderId[] = $this->getDecryptData($folderId);
        }

        if ($decryptedFolderId !== []) {
            $this->folderService->updateFolderData($decryptedFolderId, $request->field, $request->newValue);

            $folder = Folder::where('ts_folder_id', $decryptedFolderId[0])->first();
            if ($folder) {
                ActivityLogHelper::log(LogConstants::UPDATE_SCHOOL_FOLDER_CONFIG, [
                    'school' => $school->id,
                    'school_key' => $school->schoolkey,
                    'job_id' => $folder->ts_job_id,
                    'folder_key' => $folder->ts_folderkey,
                    $request->field => $request->newValue,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Folder updated successfully.']);
    }

}
