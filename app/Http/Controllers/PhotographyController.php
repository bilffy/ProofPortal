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
