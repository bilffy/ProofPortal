<?php

namespace App\Http\Controllers;

use App\Helpers\SchoolContextHelper;
use App\Models\DownloadCategory;
use App\Models\DownloadDetail;
use App\Models\DownloadRequested;
use App\Models\DownloadType;
use App\Models\Image;
use App\Models\Job;
use App\Services\ImageService;
use Auth;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class PhotographyController extends Controller
{
    
    /**
     * @var ImageService $imageService
     */
    private ImageService $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
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

    public function configure()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'configure']);
    }

    public function showPortraits()
    {   
        $job = SchoolContextHelper::getSchoolJob();
        $tsSeasonId = $job->ts_season_id; // temporary value this should be dynamic from the job table like jobs.ts_season_id
        $tsSchollKey = $job->ts_schoolkey; // temporary value this should be dynamic from the job table like jobs.ts_schoolkey
        $selectedTag = 'student'; // temporary value this should be dynamic based on the selected views e.g. student, staff, etc.

        $options = [
            'tsSeasonId' => $tsSeasonId, // temporary value this should be dynamic based on the request the job table like jobs.ts_season_id
            'schoolKey' => $tsSchollKey, // temporary value this should be dynamic based on the request the job table like jobs.ts_schoolkey
            'folderKey' => 'FZT6C5AC', // temporary value this should be dynamic based on the request the folders table like folders.ts_folderkey      
        ];

        // dd($this->imageService->getImagesAsBase64($options));
        
        // foreach ($this->imageService->getImagesAsBase64($options) as $image) {
        //     if (isset($image['meta-data']['base64'])) {
        //         echo "<img src='data:image/jpeg;base64," . $image['meta-data']['base64'] . "' /><br/>";
        //     } else {
        //         echo "<img src='data:image/jpeg;base64," . $image . "' /><br/>";
        //     }
        // }
        // dd([
        //     'user' => new UserResource(Auth::user()), 
        //     'currentTab' => 'portraits',
        //     'years' => $this->imageService->getAllYears(),
        //     'views' => $this->imageService->getFolderForView(
        //         $tsSeasonId,
        //         $tsSchollKey, 
        //         '!=', 
        //         'SP',
        //     ),
        //     'classes' => $this->imageService->getFoldersByTag(
        //         $tsSeasonId,
        //         $tsSchollKey,
        //         $selectedTag,
        //         'is_visible_for_portrait'
        //     ),
        // ]);
        // exit;
        
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()), 
                'currentTab' => 'portraits',
                'years' => $this->imageService->getAllYears(),
                'views' => $this->imageService->getFolderForView(
                    $tsSeasonId,
                    $tsSchollKey, 
                    '!=', 
                    'SP',
                ),
                'classes' => $this->imageService->getFoldersByTag(
                    $tsSeasonId,
                    $tsSchollKey,
                    [$selectedTag],
                    'is_visible_for_portrait'
                ),
            ]
        );
    }

    public function showGroups()
    {
        $tsSeasonId = 25; // temporary value this should be dynamic from the job table like jobs.ts_season_id
        $tsSchollKey = 111; // temporary value this should be dynamic from the job table like jobs.ts_schoolkey
        $selectedTag = 'student'; // temporary value this should be dynamic based on the selected views e.g. student, staff, etc.
        
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()), 
                'currentTab' => 'groups',
                'years' => $this->imageService->getAllYears(),
                'views' => $this->imageService->getFolderForView(
                    $tsSeasonId,
                    $tsSchollKey,
                    '=',
                    'SP',
                ),
                'classes' => $this->imageService->getFoldersByTag(
                    $tsSeasonId,
                    $tsSchollKey,
                    [$selectedTag],
                    'is_visible_for_group'
                ),
            ]
        );
    }

    public function showOthers()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'others']);
    }
    
    /**
     * Show the photos based on the selected options.
     *
     */
    public function showPhotos()
    {
        $options = [
            'seasonId' => 25, // temporary value this should be dynamic based on the request the job table like jobs.ts_season_id
            'schoolKey' => 111, // temporary value this should be dynamic based on the request the job table like jobs.ts_schoolkey
            'folderKey' => 'FZT6C5AC', // temporary value this should be dynamic based on the request the folders table like folders.ts_folderkey      
        ];
        
        
        
        return view('photography', 
            [
                'user' => new UserResource(Auth::user()), 
                'photos' => $this->imageService->getFilteredPhotographyImages($options),
            ]
        );
    }

    public function requestDownloadDetails(Request $request)
    {   
        $images = $request->input('images');
        $category = $request->input('category');
        $downloadCategory = DownloadCategory::where('category_name', $category)->first();
        $downloadType = DownloadType::where('download_type', 'Portrait')->first();
        
        $downloadRequest = DownloadRequested::create([
            'user_id' => auth()->id(),
            'requested_date' => now(),
            'download_category_id' => $downloadCategory->id,
            'download_type_id' => $downloadType->id,
            'filters' => json_encode($request->input('filters')),
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
