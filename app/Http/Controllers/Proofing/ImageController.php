<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\ImageUploader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Services\Proofing\ImageService;
use App\Services\Proofing\SeasonService;
use Intervention\Image\Facades\Image;
use App\Services\Proofing\JobService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use Session;
use Storage;
use Auth;
use Log;
use Illuminate\Support\Facades\URL;

class ImageController extends Controller
{
    
    protected $jobService;
    protected $imageService;
    protected $seasonService;

    public function __construct(JobService $jobService, ImageService $imageService, SeasonService $seasonService)
    {
        $this->jobService = $jobService;
        $this->imageService = $imageService;
        $this->seasonService = $seasonService;
    }

    public function zoom(Request $request)
    {
        try {
            $w = $request->query('imgClientSizeW');
            $h = $request->query('imgClientSizeH');
            $xPercent = $request->query('mousePosPercentX');
            $yPercent = $request->query('mousePosPercentY');
            $artifactImage = Crypt::decryptString($request->query('artifactNameCrypt'));
            
            $imageContent = null; // Initialize imageContent

            $folderKey = pathinfo($artifactImage, PATHINFO_FILENAME);
            $extension = pathinfo($artifactImage, PATHINFO_EXTENSION) ?: 'jpg';
                
            $folder = \App\Models\Folder::where('ts_folderkey', $folderKey)->first();
            if ($folder && $folder->job) {
                $job = $folder->job;
                //secret location
                $hash = hash_hmac('sha256', 'folders', $folderKey);
                $p1 = substr($hash, 0, 2);
                $p2 = substr($hash, 2, 2);
                $p3 = substr($hash, 4, 2);
                $cachePath = "{$job->seasons->code}/{$job->ts_schoolkey}/{$job->ts_jobkey}/folders/{$p3}/{$p1}/{$p2}/{$folderKey}.{$extension}";
                //old location
                // $char1 = $folderKey[0];
                // $char2 = $folderKey[1];
                // $cachePath = "{$job->seasons->code}/{$job->ts_schoolkey}/{$job->ts_jobkey}/folders/{$char1}/{$char2}/{$folderKey}.{$extension}";
                $imageUrl = rtrim(config('services.exportImageLocation'), '/') . '/' . $cachePath;

                $response = Http::timeout(15)->withoutVerifying()->get($imageUrl);
                if ($response->successful()) {
                    $imageContent = $response->body();
                }
            }
            
            if (!$imageContent) {
                return response()->json(['error' => 'Image not found'], 404);
            }

            $image = Image::make($imageContent);

            // Calculate crop coordinates
            $xPosition = intval($image->width() * $xPercent);
            $yPosition = intval($image->height() * $yPercent);

            $w = intval($w);
            $h = intval($h);
            $a = $request->query('anchor', 5); // Default to center if not provided

            switch ($a) {
                case 7:
                    $xPoint = $xPosition;
                    $yPoint = $yPosition;
                    break;
                case 8:
                    $xPoint = round($xPosition - ($w / 2), 0);
                    $yPoint = $yPosition;
                    break;
                case 9:
                    $xPoint = round($xPosition - $w, 0);
                    $yPoint = $yPosition;
                    break;
                case 4:
                    $xPoint = $xPosition;
                    $yPoint = round($yPosition - ($h / 2), 0);
                    break;
                case 5:
                    $xPoint = round($xPosition - ($w / 2), 0);
                    $yPoint = round($yPosition - ($h / 2), 0);
                    break;
                case 6:
                    $xPoint = round($xPosition - $w, 0);
                    $yPoint = round($yPosition - ($h / 2), 0);
                    break;
                case 1:
                    $xPoint = $xPosition;
                    $yPoint = round($yPosition - $h, 0);
                    break;
                case 2:
                    $xPoint = round($xPosition - ($w / 2), 0);
                    $yPoint = round($yPosition - $h, 0);
                    break;
                case 3:
                    $xPoint = round($xPosition - $w, 0);
                    $yPoint = round($yPosition - $h, 0);
                    break;
                default:
                    $xPoint = $xPosition;
                    $yPoint = $yPosition;
                    break;
            }

            // Crop the image
            $image = $image->crop($w, $h, $xPoint, $yPoint);

            // Apply watermark to the cropped image
            $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');
            if (file_exists($watermarkUrl)) {
                $watermark = Image::make($watermarkUrl);
                // Resize watermark to cover the zoomed dimensions
                $watermark->resize($image->width(), $image->height());
                // Insert the resized watermark overlay once
                $image->insert($watermark, 'top-left', 0, 0);
            }

            // Encode the image to a string
            $imageContent = (string) $image->encode();

            // Get MIME type
            $mimeType = $image->mime();
            // \Log::info('MIME Type: ' . $mimeType);

            // Return response with proper headers
            return new Response($imageContent, 200, ['Content-Type' => $mimeType]);
        } catch (\Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            return response()->json(['error' => 'Image processing failed'], 500);
        }
    }

    public function serveImage($fileOrigin,$filename,$jobKey)
    {
        try {
            // 1. Decrypt the filename
            $deCryptfilename = Crypt::decryptString($filename);
            $deCryptjobKey = Crypt::decryptString($jobKey);
            
            $selectedJob = $this->jobService->getJobByJobKey($deCryptjobKey)->first();
            $selectedSeason = $this->seasonService->getSeasonBySeasonID($selectedJob->ts_season_id)->first();

            // Basic validation for the decrypted string
            if (empty($deCryptfilename) || strlen($deCryptfilename) < 2) {
                Log::error("Invalid decrypted filename: " . json_encode($deCryptfilename));
                return $this->serveFallback();
            }
            //old location
            // $char1 = $deCryptfilename[0];
            // $char2 = $deCryptfilename[1];
            // $imageUrl = rtrim(config('services.exportImageLocation'), '/') . "/{$selectedSeason->code}/{$selectedJob->ts_schoolkey}/{$deCryptjobKey}/{$char1}/{$char2}/{$deCryptfilename}.jpg";

            //secret location
            $hash = hash_hmac('sha256', $fileOrigin, $deCryptfilename);
            $p1 = substr($hash, 0, 2);
            $p2 = substr($hash, 2, 2);
            $p3 = substr($hash, 4, 2);
            $imageUrl = rtrim(config('services.exportImageLocation'), '/') . "/{$selectedSeason->code}/{$selectedJob->ts_schoolkey}/{$deCryptjobKey}/{$fileOrigin}/{$p3}/{$p1}/{$p2}/{$deCryptfilename}.jpg";

            // 3. Try HTTP first (Proxy)
            $response = Http::timeout(15)
                ->withoutVerifying() // FIXES: cURL error 60
                ->get($imageUrl);

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', $response->header('Content-Type', 'image/jpeg'))
                    ->header('Cache-Control', 'public, max-age=86400');
            }

            return $this->serveFallback();

        } catch (\Exception $e) {
            Log::error("Error serving image via proxy: " . $e->getMessage());
            return $this->serveFallback();
        }
    }

    private function serveFallback()
    {
        $path = public_path('proofing-assets/img/subject-image.png');

        if (!file_exists($path)) {
            Log::error("Fallback image missing at: " . $path);
            return response()->json(['error' => 'Image not found'], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400'
        ]);
    }

    public function bulkUploadImage($jobHash, $step = null)
    {
        $selectedJob = $this->jobService->getJobByJobKey(Crypt::decryptString($jobHash))->first();
        
        if (!$selectedJob) {
            abort(404); 
        }

        $sessionFiles = '';
        $uploadSession = sha1(Crypt::encryptString(Str::random(2048)));

        if($step === 'match'){
            if(Session::has('upload_session')){
                $uploadSession = session('upload_session');
                $sessionFiles = Storage::disk('public')->files($uploadSession);
            }else{
                return redirect()->back()->with('error', 'Please upload some images.');
            }
        }

        $user = Auth::user();
        return view('proofing.franchise.bulk-upload', [
            'selectedJob' => $selectedJob,
            'step' => $step,
            'jobHash' => $jobHash,
            'uploadedImages' => $sessionFiles,
            'uploadSession' => $uploadSession,
            'user' => new UserResource($user)
        ]);
    }

    public function showgroupImage($filename)
    {
        try {
            $deCryptfilename = Crypt::decryptString($filename);
            $folderKey = pathinfo($deCryptfilename, PATHINFO_FILENAME);
            $extension = pathinfo($deCryptfilename, PATHINFO_EXTENSION) ?: 'jpg';
            
            $imageContent = null;
            $contentType = 'image/jpeg';

            // Try HTTP cache server first
            $folder = \App\Models\Folder::where('ts_folderkey', $folderKey)->first();
            if ($folder && $folder->job) {
                $job = $folder->job;
                $seasonCode = $job->seasons->code;
                $schoolKey = $job->ts_schoolkey;
                $jobKey = $job->ts_jobkey;
                //secret location
                $hash = hash_hmac('sha256', 'folders', $folderKey);
                $p1 = substr($hash, 0, 2);
                $p2 = substr($hash, 2, 2);
                $p3 = substr($hash, 4, 2);
                $cachePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$p3}/{$p1}/{$p2}/{$folderKey}.{$extension}";
                //old location
                // $char1 = $folderKey[0];
                // $char2 = $folderKey[1];
                // $cachePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$char1}/{$char2}/{$folderKey}.{$extension}";
                $imageUrl = rtrim(config('services.exportImageLocation'), '/') . '/' . $cachePath;

                $response = Http::timeout(15)->withoutVerifying()->get($imageUrl);
                if ($response->successful()) {
                    $imageContent = $response->body();
                    $contentType = $response->header('Content-Type', 'image/jpeg');
                } else {
                    Log::warning("Cache server returned {$response->status()} for group image: {$imageUrl}");
                }
            }

            // Fallback to local
            if (!$imageContent) {
                $path = 'groupImages/' . $deCryptfilename;
                if (Storage::disk('public')->exists($path)) {
                    $imageContent = Storage::disk('public')->get($path);
                    $contentType = Storage::disk('public')->mimeType($path);
                }
            }

            // Apply watermark and return
            if ($imageContent) {
                try {
                    $img = Image::make($imageContent);
                    $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');
                    
                    if (file_exists($watermarkUrl)) {
                        $watermark = Image::make($watermarkUrl);
                        
                        // Resize watermark to cover the entire image
                        $watermark->resize($img->width(), $img->height());
                        
                        // Insert the resized watermark overlay once
                        $img->insert($watermark, 'top-left', 0, 0);
                    }
                    
                    return response((string) $img->encode(), Response::HTTP_OK)
                        ->header('Content-Type', $contentType)
                        ->header('Cache-Control', 'public, max-age=86400');
                } catch (\Exception $imgEx) {
                    Log::error("Watermarking failed on group image: " . $imgEx->getMessage());
                    return response($imageContent, Response::HTTP_OK)->header('Content-Type', $contentType);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error showing group image: ' . $e->getMessage(), [
                'folderKey' => $folderKey ?? null,
                'imageUrl' => $imageUrl ?? null,
            ]);
        }

        return $this->serveFallback();
    }

    public function groupImageUpload(Request $request)
    {
        // Ensure a file is uploaded
        if ($request->hasFile('file')) 
        {
            Session::pull('upload_session');
            
            $file = $request->file('file');
        
            // Validate file type and size
            $request->validate([
                'file' => 'image|mimes:jpeg,png,jpg|max:15360', // Restrict to image files only
                'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/', // Prevent directory traversal
            ]);
        
            // Generate a filename
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalFilename . '.' . $file->getClientOriginalExtension();
        
            // Store the file in the 'public/groupImages' directory
            $path = $file->storeAs($request->input('upload_session'), $filename, 'public');
                
            // Store session data
            session([
                'upload_session' => $request->input('upload_session')
            ]);
            
            // Save session
            session()->save(); 
        }
    }

    public function groupImageDelete(Request $request)
    {
        // Validate the upload session to prevent directory traversal
        $request->validate([
            'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
        ]);

        Session::pull('upload_session');   
        
        // Get the upload session from the request
        $uploadSession = $request->input('upload_session');
    
        // Check if the folder exists
        if (Storage::disk('public')->exists($uploadSession)) {
            // Delete the folder and its contents
            Storage::disk('public')->deleteDirectory($uploadSession);
        }
    
        // Redirect to the franchise dashboard
        return response()->json(['status'=>true]); 
    }

    public function groupImageSubmit(Request $request)
    {
        // Validate the request as necessary
        $request->validate([
            'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/', // Prevent directory traversal
            'artifact-to-folder-map' => 'required|string', // Ensure this field is sent
            'jobHash' => 'nullable|string',
        ]);
    
        // Decode the JSON data from the artifact-to-folder-map
        $artifactToFolderMap = json_decode($request->input('artifact-to-folder-map'), true);
        $folderPath = $request->input('upload_session');
    
        $jobKeyContextRequest = null;
        if ($request->has('jobHash') && !empty($request->input('jobHash'))) {
            $jobKeyContextRequest = Crypt::decryptString($request->input('jobHash'));
        }

        foreach ($artifactToFolderMap as $artifact => $folderKey) {
            // Check if folderKey is not 'discard_image' or 'no_match'
            if ($folderKey !== "discard_image" && $folderKey !== "no_match") {
                $extension = pathinfo($artifact, PATHINFO_EXTENSION); // Get the file extension
                
                // Lookup Folder details dynamically to ensure 100% correct hierarchy context
                $folder = \App\Models\Folder::where('ts_folderkey', $folderKey)->first();
                $seasonCodeLocal = null;
                $schoolKeyLocal  = null;
                $jobKeyLocal     = null;

                if ($folder && $folder->job) {
                    $jobLocal = $folder->job;
                    $seasonCodeLocal = $jobLocal->seasons->code ?? null;
                    $schoolKeyLocal = $jobLocal->ts_schoolkey;
                    $jobKeyLocal = $jobLocal->ts_jobkey;
                }
                
                if ($seasonCodeLocal && $schoolKeyLocal && $jobKeyLocal) {
                    //secret location
                    $hash = hash_hmac('sha256', 'folders', $folderKey);
                    $p1 = substr($hash, 0, 2);
                    $p2 = substr($hash, 2, 2);
                    $p3 = substr($hash, 4, 2);
                    $remotePath = "{$seasonCodeLocal}/{$schoolKeyLocal}/{$jobKeyLocal}/folders/{$p3}/{$p1}/{$p2}/{$folderKey}.{$extension}";
                    $path = "{$p3}/{$p1}/{$p2}/";
                    $fileName = "{$folderKey}.{$extension}";
                    //old location
                    // $char1 = $folderKey[0];
                    // $char2 = $folderKey[1];
                    // $remotePath = "{$seasonCode}/{$schoolKey}/{$jobKeyContext}/folders/{$char1}/{$char2}/{$fileName}";

                    if (Storage::disk('public')->exists($artifact)) {
                        $fileContent = Storage::disk('public')->get($artifact);
                        $uploader = new ImageUploader();
                        $uploader->upload($fileContent, $remotePath, "{$fileName}");
                        $this->imageService->createGroupImage($folderKey, $path, $fileName);
                        Storage::disk('public')->delete($artifact);
                    }
                } else {
                    Log::error("Missing job context for group image upload mapping: folderKey = " . $folderKey);
                }
            } else {
                if (Storage::disk('public')->exists($artifact)) {
                    Storage::disk('public')->delete($artifact);
                }
            }
        }
        
        if (Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->deleteDirectory($folderPath);
        }
    
        // Return a response, redirect, or whatever your flow requires
        if ($request->has('jobHash')) {
            return redirect()->to(URL::signedRoute('proofing.dashboard', ['hash' => $request->input('jobHash')]));
        }
        return redirect()->route('proofing');
    }

    public function groupImageUploadFile(Request $request)
    {
        // Validate the request for a file
        $request->validate([
            'file' => 'image|mimes:jpeg,png,jpg|max:15360', // Restrict to image files only
            'folder_key' => 'required|string',
            'folder_name' => 'required|string',
        ]);

        // Retrieve the uploaded file
        $file = $request->file('file');
        $folderKey = $request->input('folder_key');
        $extension = $file->getClientOriginalExtension();
        // // Define the file name as folder_key.extension
        //     $fileName = $folderKey . '.' . $extension;
            
        // // Store the file in the 'groupImages' folder in the public disk
        //     $filePath = $file->storeAs('groupImages', $fileName, 'public');
        $folder = \App\Models\Folder::where('ts_folderkey', $folderKey)->first();
        if ($folder && $folder->job) {
            $job = $folder->job;
            $seasonCode = $job->seasons->code;
            $schoolKey = $job->ts_schoolkey;
            $jobKey = $job->ts_jobkey;
            //secret location
            $hash = hash_hmac('sha256', 'folders', $folderKey);
            $p1 = substr($hash, 0, 2);
            $p2 = substr($hash, 2, 2);
            $p3 = substr($hash, 4, 2);
            $path = "{$p3}/{$p1}/{$p2}/";
            $fileName = "{$folderKey}.{$extension}";
            $remotePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$p3}/{$p1}/{$p2}/{$fileName}";
            //old location
            // $char1 = $folderKey[0];
            // $char2 = $folderKey[1];
            // $remotePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$char1}/{$char2}/{$fileName}";

            try {
                $uploader = new ImageUploader();
                $uploader->upload(file_get_contents($file), $remotePath, "{$fileName}");
                
                $this->imageService->createGroupImage($folderKey, $path, $fileName);
                $encryptedFilename = Crypt::encryptString($fileName);
                
                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'full_url' => route('image.show', ['filename' => $encryptedFilename]),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to upload group image for folder: {$folderKey} - " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Upload failed'], 500);
    }

}
