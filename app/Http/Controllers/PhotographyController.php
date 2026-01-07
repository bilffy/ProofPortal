<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\EncryptionHelper;
use App\Helpers\ImageHelper;
use App\Helpers\PhotographyHelper;
use App\Helpers\SchoolContextHelper;
use App\Models\DownloadDetail;
use App\Models\DownloadRequested;
use App\Models\DownloadType;
use App\Models\FilenameFormat;
use App\Models\Folder;
use App\Models\Image;
use App\Models\Job;
use App\Models\SchoolPhotoUpload;
use App\Models\Season;
use App\Models\Status;
use App\Models\Subject;
use App\Models\ImageOptions;
use App\Services\ImageService;
use App\Services\Proofing\SchoolService;
use App\Services\Storage\StorageServiceInterface;
use App\Services\UserService;
use Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;

class PhotographyController extends Controller
{
    protected $schoolService;
    protected StorageServiceInterface $storageService;
    
    /**
     * @var ImageService $imageService
     * @var SchoolService $schoolService
     * @var StorageServiceInterface $storageService
     */
    private ImageService $imageService;
    
    public function __construct(
        ImageService $imageService, 
        SchoolService $schoolService,
        StorageServiceInterface $storageService
    ) {
        $this->imageService = $imageService;
        $this->schoolService = $schoolService;
        $this->storageService = $storageService;
    }

    public function index()
    {
        $user = Auth::user();
        if ($user->isFranchiseLevel() && SchoolContextHelper::isSchoolContext()) {
            return redirect()->route('photography.configure-new');
        } else {
            return redirect()->route('photography.portraits');
        }
    }

    public function showConfiguration()
    {
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()),
                'imageOptions' => $this->imageService->getImageOptions(),
                'currentTab' => 'configure',
                'configMessages' => config('app.dialog_config.download'),
                'photographyMessages' => config('app.dialog_config.photography')
            ]);
    }

    public function showPortraits()
    {   
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()), 
                'imageOptions' => $this->imageService->getImageOptions(),
                'currentTab' => 'portraits',
                'configMessages' => config('app.dialog_config.download'),
                'photographyMessages' => config('app.dialog_config.photography')
            ]
        );
    }

    public function showGroups()
    {
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()),
                'imageOptions' => $this->imageService->getImageOptions(),
                'currentTab' => 'groups',
                'configMessages' => config('app.dialog_config.download'),
                'photographyMessages' => config('app.dialog_config.photography')
            ]
        );
    }

    public function showOthers()
    {
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()),
                'imageOptions' => $this->imageService->getImageOptions(),
                'currentTab' => 'others',
                'configMessages' => config('app.dialog_config.download'),
                'photographyMessages' => config('app.dialog_config.photography')
            ]
        );
    }
    
    public function execNonce()
    {
        $nonce = Str::random(40);
        session(['download-request-nonce' => $nonce]);
        return response()->json(['success' => true, 'data' => ['nonce' => $nonce]]);
    }
    
    public function requestDownloadDetails(Request $request)
    {   
        $school = SchoolContextHelper::getSchool();

        $user = Auth::user();
        
        if (UserService::isCanAccessImage($user, $school) === false) {
            abort(403, 'Access denied.');                    
        }      

        // get the nonce from the request header
        if ($request->header('MSP-Nonce') !== session('download-request-nonce')) {
            return response()->json('Invalid Request', 422);
        }
        
        $validator = Validator::make($request->all(), [
            'images' => 'array',
            'images.*' => ['string', 'not_regex:/\\$/'], // reject any item containing a `$`
            'category' => 'required|integer|in:1,2',
            'filters' => 'required|array',
            'filters.year' => 'required|string',
            'filters.view' => 'required|string',
            'filters.class' => 'required|string',
            //'filters.resolution' => 'required|string|in:high,low',
            'filters.folder_format' => 'required|string|in:all,organize',
            'tab' => 'required|string|in:PORTRAITS,GROUPS,OTHERS',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $category = $request->input('category');
        $selectedFilters = $request->input('filters');
        $schoolKey = $school->schoolkey ?? '';
        $view = $selectedFilters['view'];
        $class = json_decode($selectedFilters['class']);
        $images = $request->input('images');
        $selectedFilters['jobkey'] = [];
        $selectedFilters['class'] = [];
        $selectedFilters['details'] = empty($images) ? false : true;
        $logImgKeys = [];
        $tab = $request->input('tab');

        if (empty($class)) {
            // Extract the records from the folder_tags table and return an array of tag values
            // based on the selected year, school key, operator, and view
            $tags = $this->imageService->getFolderForView2(
                $selectedFilters['year'],
                $schoolKey,
                $tab,
            )->pluck('external_name')->toArray();

            $folders = $this->imageService->getFoldersByTag(
                $selectedFilters['year'],
                $schoolKey,
                $tags,
                $tab
            )->toArray();
        } else {
            $folders = Folder::whereIn('ts_folderkey', $class)->get();
        }
        
        foreach ($folders as $folder) {
            $selectedFilters['class']['folderkey'][] = $folder->ts_folderkey;
            // query the jobs table to get the jobkey based on the ts_job_id
            $job = Job::where('ts_job_id', $folder->ts_job_id)->first();
            
            if ($job) {
                // add the jobkey to the selectedFilters array if does not exist
                if (!in_array($job->ts_jobkey, $selectedFilters['jobkey'])) {
                    $selectedFilters['jobkey'][] = $job->ts_jobkey;
                }
            }
        }
        
        $downloadType = DownloadType::where('download_type', 'Portrait')->first();
        
        $season = Season::where('ts_season_id', $selectedFilters['year'])->first();
        // get the season code as the year
        $selectedFilters['year'] = $season->code;
        
//        // check if the image belongs to the school, basically schoolKey should match
//        foreach ($images as $image) {
//            $key = base64_decode(base64_decode(preg_replace('/^img_/', '', $image)));
//            $imgRecord = Image::where('keyvalue', $key)->first();
//            if ($imgRecord) {
//                $job = Job::where('ts_job_id', $imgRecord->ts_job_id)->first();
//                if ($job && $job->ts_schoolkey !== $schoolKey) {
//                    return response()->json('Invalid Request', 422);
//                }
//            }
//        }

        // check if the image belongs to the school, basically schoolKey should match
        $keys = array_filter(array_map(function($img) {
            $decoded = preg_replace('/^img_/', '', $img);
            $firstPass = base64_decode($decoded);
            return $firstPass === false ? '_notfound_' : base64_decode($firstPass);
        }, $images ?? []));
        
        // if any decoded keys exist, check in a single query joining jobs
        if (!empty($keys)) {
            /*$mismatchExists = Image::select('images.keyvalue')
                ->join('jobs', 'images.ts_job_id', '=', 'jobs.ts_job_id')
                ->whereIn('images.keyvalue', $keys)
                ->where('jobs.ts_schoolkey', '!=', $schoolKey)
                ->exists();*/
            
            // Check if all keys exist and belong to the school
            $imageCount = Image::join('jobs', 'images.ts_job_id', '=', 'jobs.ts_job_id')
                ->whereIn('images.keyvalue', $keys)
                ->where('jobs.ts_schoolkey', '=', $schoolKey)
                ->count();
            
            if ($imageCount != count($keys)) {
                return response()->json('Invalid Request', 403);
            }
            
            // Check if any keys belong to other schools, to make sure all keys are valid and belongs to the school
            $foundCount = Image::join('jobs', 'images.ts_job_id', '=', 'jobs.ts_job_id')
                ->whereIn('images.keyvalue', $keys)
                ->where('jobs.ts_schoolkey', '!=', $schoolKey)
                ->count();
        
            if ($foundCount) {
                return response()->json('Invalid Request', 403);
            } 
        }
        
        $downloadRequest = DownloadRequested::create([
            'user_id' => auth()->id(),
            'requested_date' => now(),
            'download_category_id' => $category,
            'download_type_id' => $downloadType->id,
            'filters' => json_encode($selectedFilters),
            'status_id' => Status::where('status_internal_name', 'PENDING')->first()->id,
            'filename_format' => $request->input('filenameFormat'),
        ]);

        foreach ($images as $image) {

            // remove the img_ prefix, then decode the base64 encoded image
            $key = base64_decode(base64_decode(preg_replace('/^img_/', '', $image)));
            
            // Add to logged image keys
            $logImgKeys[] = $key;
            // Query the Image model to get the image data
            $image = Image::where('keyvalue', $key)->first();
            
            if ($image) {
                $job = Job::where('ts_job_id', $image->ts_job_id)->first();
                if ($job) {
                    DownloadDetail::create([
                        'download_id' => $downloadRequest->id,
                        'ts_jobkey' => $job->ts_jobkey,
                        'keyorigin' => $image->keyorigin,
                        'keyvalue' => $image->keyvalue,
                    ]);
                }
            }
        }
        // If there are multiple images, return images list
        $data = [$images];
        // If there is only one image, return the image content
        if (count($images) === 1) {
            $key = base64_decode(base64_decode(preg_replace('/^img_/', '', $images[0])));

            switch ($request->input('tab')) {
                case PhotographyHelper::TAB_GROUPS:
                case PhotographyHelper::TAB_OTHERS:
                    $object = Folder::where('ts_folderkey', $key)->first();
                    break;
                case PhotographyHelper::TAB_PORTRAITS:
                    $object = Subject::where('ts_subjectkey', $key)->first();
                    break;
            }
            
            if (!$object) {
                return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
            }
            
            $fileFormat = FilenameFormat::where('format_key', $request->input('filenameFormat'))->first();
            $filename = null == $fileFormat ? $key : $object->getFilename($fileFormat->format);
            
            // get the path of the image in .env IMAGE_REPOSITORY/print_quality
            // UPDATED: Refer to filesystems' disks configuration for the path
            // $path = base_path(config('app.image_repository') . '/print_quality/' . $key . ".jpg");
            $imageOption = ImageOptions::where('id', $selectedFilters['resolution'])->first();
            $files = ImageHelper::findImageFiles($key);
            // check if the file exists in the print_quality
            if (!empty($files)) {
                $printQualityFiles = array_filter($files, function($file) {
                    return str_contains($file, 'print_quality');
                });
                if (!empty($printQualityFiles)) {
                    $path = array_values($printQualityFiles)[0];
                } else {
                    $path = $files[0];
                }
                $basePath = ImageHelper::getStorageBasePath();
                $path = str_replace($basePath, "", $path);
            } else {
                $path = '';
            }
            $fileExtension = File::extension($path);
            // and make sure to resizeImage if the long_edge option is set
            if ($imageOption->long_edge) {
                return $this->resizeImage($path, $imageOption->long_edge, $filename, $fileExtension);
            }
            // retrieve the image content using the ImageService - as is?
            $imageContent = base64_encode($this->imageService->getImageContent($key));
            return response()->json([
                'success' => true,
                'data' => $imageContent,
                'filename' => $filename,
                'extension' => $fileExtension,
            ]);
            // return response()->json(['success' => true, 'data' => $data, 'filename' => EncryptionHelper::simpleEncrypt($filename)]);
        }

        // Log DOWNLOAD_PHOTOS activity
        ActivityLogHelper::log(LogConstants::DOWNLOAD_PHOTOS, [
            'school' => $school->id,
            'school_key' => $schoolKey,
            'download_requested' => $downloadRequest->id,
        ]);
        
        // remove the nonce from the session
        session()->forget('download-request-nonce');
        
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function uploadImage(Request $request)
    {
        $imgExtensions = ImageHelper::getExtensionsAsString();
        $imageValidator = 'required|image|mimes:' . $imgExtensions . '|max:5120';
        $validator = Validator::make($request->all(), [
            'image' => $imageValidator,
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $imgKey = $request->input('image_key');
            $key = base64_decode(base64_decode($imgKey));
            $subject = Subject::where('ts_subjectkey', $key)->first();
            if ($subject) {
                $folder = Folder::where('ts_folder_id', $subject->ts_folder_id)->first();
                $existingImages = SchoolPhotoUpload::where('subject_id', $subject->id)->whereNull('deleted_at')->get();
                $origin = 'subject';
            } else {
                $folder = Folder::where('ts_folderkey', $key)->first();
                if (!$folder) {
                    return response()->json(['success' => false, 'message' => 'Origin not found.'], 404);
                }
                $existingImages = SchoolPhotoUpload::where('folder_id', $folder->id)->whereNull('deleted_at')->get();
                $origin = 'folder';
            }
            $image = Image::where('keyvalue', $key)
                ->where('keyorigin', $origin)->first();
            // If there is an existing uploaded image, delete previous image
            if ($existingImages->count() > 0) {
                foreach ($existingImages as $existingImage) {
                    $metadata = $existingImage->metadata;
                    $existingPath = $metadata['path'] ?? null;
                    // Remove the existing image file from storage
                    if (!empty($existingPath)) {
                        $this->storageService->delete($existingPath);
                        // Update $existingImage record remove path in metadata
                        $metadata['path'] = ''; // No path means file is deleted
                        $existingImage->update([
                            'metadata' => $metadata,
                        ]);
                    };
                }
            }
            $filename = $key . '.' . $img->getClientOriginalExtension();
            
            $path = $this->storageService->store(env('FILE_IMAGE_UPLOAD_PATH', ''), $img, $filename);
            $newImage = SchoolPhotoUpload::create([
                'subject_id' => $subject ? $subject->id : null,
                'folder_id' => $folder ? $folder->id : null,
                'image_id' => $image ? $image->id : null,
                'metadata' => [
                    'original_filename' => $img->getClientOriginalName(),
                    'key' => $key,
                    'origin' => $origin,
                    'image_key' => $image->ts_imagekey ?? '',
                    'path' => $path,
                ],
            ]);
            // Log upload activity
            ActivityLogHelper::log(LogConstants::UPLOAD_PHOTO, [
                'school_key' => SchoolContextHelper::getSchool()->schoolkey ?? '',
                'imagekey' => $image->ts_imagekey ?? '',
                'key' => $key,
                'photo_upload_id' => $newImage->id,
                'path' => $path,
            ]);

            $originKey = base64_encode(base64_encode($key));

            return response()->json(['success' => true, 'path' => $path, 'key' => $originKey, 'uploadId' => $newImage ? $newImage->id : 0]);
        }
        
        return response()->json(['success' => false, 'message' => 'Image file is required.'], 422);
    }

    public function removeImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], status: 422);
        }
        
        $imgKey = $request->input('image_key');
        $key = base64_decode(base64_decode($imgKey));

        $subject = Subject::where('ts_subjectkey', $key)->first();
        if ($subject) {
            $folder = Folder::where('ts_folder_id', $subject->ts_folder_id)->first();
            $image = SchoolPhotoUpload::where('subject_id', $subject->id)
                ->where('folder_id', $folder->id)->whereNull('deleted_at');
            $origin = 'subject';
        } else {
            $folder = Folder::where('ts_folderkey', $key)->first();
            if (!$folder) {
                return response()->json(['success' => false, 'message' => 'Origin not found.', 'key' => $key], 404);
            }
            $image = SchoolPhotoUpload::where('folder_id', $folder->id)
                ->where('subject_id', null)->whereNull('deleted_at');
            $origin = 'folder';
        }

        $img = Image::where('keyvalue', $key)
            ->where('keyorigin', $origin)->first();
        // Find the latest uploaded image
        $image = $image->latest()->first();
        
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Uploaded Image not found.'], 404);
        }

        // Remove the image file from storage
        $deleted = false;
        $metadata = $image->metadata;
        if (isset($metadata['path'])) {
            $deleted = $this->storageService->delete($metadata['path']);
            if ($deleted) {
                $metadata['path'] = ''; // No path means file is deleted
                $image->update([
                    'metadata' => $metadata,
                    'deleted_at' => now(),
                ]);
            }
        }

        if ($deleted) {
            // Log remove activity
            ActivityLogHelper::log(LogConstants::REMOVE_PHOTO, [
                'school_key' => SchoolContextHelper::getSchool()->schoolkey ?? '',
                'imagekey' => $img->ts_imagekey ?? '',
                'key' => $key,
                'photo_upload_id' => $image->id,
                'path' => $image->path,
            ]);
        }

        $originKey = base64_encode(base64_encode($key));

        return response()->json(['success' => $deleted, 'key' => $originKey]);
    }
    
    private function resizeImage($path, $long_edge, $filename, $fileExtension)
    {
        $scriptPath = base_path('image_resizer/resizer.py'); // adjust path to your script

        // Run Python script
        $process = new Process([
            'python3',
            $scriptPath,
            $path,
            $long_edge
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'error' => $process->getErrorOutput()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'data' => trim($process->getOutput()),
            'filename' => $filename,
            'extension' => $fileExtension
        ]);
    }
}
