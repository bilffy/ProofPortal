<?php

namespace App\Http\Controllers;

use App\Helpers\PhotographyHelper;
use App\Helpers\SchoolContextHelper;
use App\Helpers\UserStatusHelper;
use App\Models\DownloadCategory;
use App\Models\DownloadDetail;
use App\Models\DownloadRequested;
use App\Models\DownloadType;
use App\Models\Folder;
use App\Models\Image;
use App\Models\Job;
use App\Models\Season;
use App\Models\Status;
use App\Models\User;
use App\Services\ImageService;
use App\Services\SchoolService;
use Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use function PHPUnit\Framework\isEmpty;

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
            return redirect()->route('photography.configure');
        } else {
            return redirect()->route('photography.portraits');
        }
    }

    public function showConfiguration()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'configure']);
    }

    public function showPortraits()
    {
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()), 
                'currentTab' => 'portraits',
            ]
        );
    }

    public function showGroups()
    {
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()), 
                'currentTab' => 'groups',
            ]
        );
    }

    public function showOthers()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'others']);
    }

    public function requestDownloadDetails(Request $request)
    {
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

        // If there is only one image, return the image content
        if (count($images) === 1) {
            $key = base64_decode(base64_decode(preg_replace('/^img_/', '', $images[0])));
            $imageContent = base64_encode($this->imageService->getImageContent($key));
            return response()->json(['success' => true, 'data' => $imageContent]);
        }
        
        if (empty($class)) {
            // Extract the records from the folder_tags table and return an array of tag values
            // based on the selected year, school key, operator, and view
            $tags = $this->imageService->getFolderForView(
                $selectedFilters['year'],
                $schoolKey,
                $view == "ALL" ? '!=' : '=',
                $view != 'ALL' ? $view : 'ALL'
            )->pluck('external_name')->toArray();

            $folders = $this->imageService->getFoldersByTag(
                $selectedFilters['year'],
                $schoolKey,
                $tags,
                'is_visible_for_portrait' // TODO: get visibility based on selected tab
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
        
        $downloadCategory = DownloadCategory::where('category_name', $category)->first();
        $downloadType = DownloadType::where('download_type', 'Portrait')->first();
        
        $season = Season::where('ts_season_id', $selectedFilters['year'])->first();
        // get the season code as the year
        $selectedFilters['year'] = $season->code;
        
        $downloadRequest = DownloadRequested::create([
            'user_id' => auth()->id(),
            'requested_date' => now(),
            'download_category_id' => $downloadCategory->id,
            'download_type_id' => $downloadType->id,
            'filters' => json_encode($selectedFilters),
            'status_id' => Status::where('status_internal_name', 'PENDING')->first()->id,
        ]);

        foreach ($images as $image) {

            // remove the img_ prefix, then decode the base64 encoded image
            $key = base64_decode(base64_decode(preg_replace('/^img_/', '', $image)));
            
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
        return response()->json(['success' => true, 'data' => [$images]]);
    }
}
