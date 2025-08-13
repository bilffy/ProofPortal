<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\EncryptionHelper;
use App\Helpers\PhotographyHelper;
use App\Helpers\SchoolContextHelper;
use App\Models\DownloadDetail;
use App\Models\DownloadRequested;
use App\Models\DownloadType;
use App\Models\FilenameFormat;
use App\Models\Folder;
use App\Models\Image;
use App\Models\Job;
use App\Models\Season;
use App\Models\Status;
use App\Models\Subject;
use App\Services\ImageService;
use App\Services\SchoolService;
use Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PhotographyController extends Controller
{
    protected $schoolService;
    
    /**
     * @var ImageService $imageService
     * @var SchoolService $schoolService
     */
    private ImageService $imageService;
    
    public function __construct(ImageService $imageService, SchoolService $schoolService)
    {
        $this->imageService = $imageService;
        $this->schoolService = $schoolService;
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
        // get the nonce from the request header
        if ($request->header('MSP-Nonce') !== session('download-request-nonce')) {
            return response()->json('Invalid Request', 422);
        }
        
        $validator = Validator::make($request->all(), [
            'images' => 'array',
            'category' => 'required|integer|in:1,2',
            'filters' => 'required|array',
            'filters.year' => 'required|string',
            'filters.view' => 'required|string',
            'filters.class' => 'required|string',
            'filters.resolution' => 'required|string|in:high,low',
            'filters.folder_format' => 'required|string|in:all,organize',
            'tab' => 'required|string|in:PORTRAITS,GROUPS,OTHERS',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $category = $request->input('category');
        $selectedFilters = $request->input('filters');
        $school = SchoolContextHelper::getSchool();
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
            $imageContent = base64_encode($this->imageService->getImageContent($key));
            // return response()->json(['success' => true, 'data' => $imageContent]);
            $data = $imageContent;

            switch ($request->input('tab')) {
                case PhotographyHelper::TAB_GROUPS:
                case PhotographyHelper::TAB_OTHERS:
                    $object = Folder::where('ts_folderkey', $key)->first();
                    break;
                case PhotographyHelper::TAB_PORTRAITS:
                    $object = Subject::where('ts_subjectkey', $key)->first();
                    break;
            }
            $fileFormat = FilenameFormat::where('format_key', $request->input('filenameFormat'))->first();
            $filename = null == $fileFormat ? $key : $object->getFilename($fileFormat->format);
            return response()->json(['success' => true, 'data' => $data, 'filename' => $filename]);
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

    // TODO: Refine implementation with ImageService and new table for uploaded images
    public function uploadImage(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        // ]);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // If there is an existing uploaded image, delete previous image
            $existingImage = Image::where('keyvalue', $request->input('key'))->first();
            if ($existingImage) {
                // Remove the existing image file from storage
                if ($existingImage->path) {
                    \Storage::disk('public')->delete($existingImage->path);
                }
                // Optionally, delete the image record from the database
                // $existingImage->delete();
            }
            $path = $request->file('image')->store('images', 'public');

            return response()->json(['path' => $path]);
        }
        
        return response()->json(['success' => false, 'message' => 'Image file is required.'], 422);
    }

    // TODO: Refine implementation with ImageService and new table for uploaded images
    public function removeImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], status: 422);
        }

        // Find the image by key
        $image = Image::where('keyvalue', $request->input('key'))->first();
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        // Remove the image file from storage
        $deleted = false;
        if ($image->path) {
            $deleted = \Storage::disk('public')->delete($image->path);
        }

        // Optionally, delete the image record from the database
        // $image->delete();

        return response()->json(['success' => $deleted]);
    }
}
