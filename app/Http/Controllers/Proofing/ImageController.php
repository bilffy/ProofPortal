<?php

namespace App\Http\Controllers\proofing;

use App\Http\Controllers\Controller;

use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\ImageService;
use Intervention\Image\Facades\Image;
use App\Services\Proofing\JobService;
use Illuminate\Support\Facades\Crypt; 
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Client;
use Session;
use Storage;
use Auth;
use Log;

class ImageController extends Controller
{
    
    protected $encryptDecryptService;

    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, ImageService $imageService)
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->imageService = $imageService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function zoom(Request $request)
    {
        try {
            $w = $request->query('imgClientSizeW');
            $h = $request->query('imgClientSizeH');
            $xPercent = $request->query('mousePosPercentX');
            $yPercent = $request->query('mousePosPercentY');
            $artifactImage = $request->query('artifactNameCrypt');
            
            $image = Image::make(storage_path('app/public/groupImages/'.$artifactImage));

            // Calculate crop coordinates
            $xPosition = intval($image->width() * $xPercent);
            $yPosition = intval($image->height() * $yPercent);

            $w = intval($w);
            $h = intval($h);

            $a = 5; // Default value, can be set from query parameters if needed

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

            // Encode the image to a string
            $imageContent = (string) $image->encode();

            // Get MIME type
            $mimeType = $image->mime();
            // \Log::info('MIME Type: ' . $mimeType);

            // Return response with proper headers
            return new Response($imageContent, 200, ['Content-Type' => $mimeType]);
        } catch (\Exception $e) {
            \Log::error('Error processing image: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function serveImage($filename)
    {
        try {
            $deCryptfilename = Crypt::decryptString($filename);
    
            if (empty($deCryptfilename) || strlen($deCryptfilename) < 2) {
                Log::error("Invalid filename: " . json_encode($deCryptfilename));
                abort(404, 'Invalid image name');
            }
    
            $networkPath = "\\\\Filestore.msp.local\\keyimage_store_uat\\{$deCryptfilename[0]}\\{$deCryptfilename[1]}\\{$deCryptfilename}_800.jpg";
            $fallbackPath = public_path('proofing-assets/img/subject-image.png');
    
            $finalPath = file_exists($networkPath) ? $networkPath : $fallbackPath;
    
            // Return as response with headers (streamed, not loaded fully into memory)
            return response()->file($finalPath, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=86400' // cache for 1 day
            ]);
    
        } catch (\Exception $e) {
            Log::error("Error serving image: " . $e->getMessage());
            abort(404, 'Unable to serve image');
        }
    }
    
    // public function serveImage($filename)
    // {
    //     try {
    //         $filename = str_replace('-', '\\', $filename);

    //         $networkPath = '\\\\hades\\ITDept\\Bilffy\\Chrome Media\\ChromeMediaImages1' . $filename;
    
    //         if (!file_exists($networkPath) || empty($filename)) {
    //             Log::error("File not found: " . $networkPath);
    //             $networkPath = public_path('proofing-assets/img/subject-image.png');
    //         }
    
    //         $response = response()->file($networkPath, [
    //             'Content-Type' => mime_content_type($networkPath)
    //         ]);
    
    //         $response->headers->set('Access-Control-Allow-Origin', '*');
    
    //         return $response;
    //     } catch (\Exception $e) {
    //         Log::error("Error serving image: " . $e->getMessage());
    //         abort(404, 'Invalid image URL');
    //     }
    // }

    public function bulkUploadImage($jobHash, $step = null)
    {
        $selectedJob = $this->jobService->getJobByJobKey($this->getDecryptData($jobHash))->first();
        $sessionFiles = '';
        $uploadSession = $uploadSession ?? sha1(Crypt::encryptString(Str::random(2048)));

        if($step === 'match'){
            if(Session::has('upload_session')){
                $uploadSession = session('upload_session');
                $sessionFiles = Storage::disk('public')->files('groupImages/'.$uploadSession);
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
        $path = 'groupImages/' . $this->getDecryptData($filename);

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);

        return response($file, Response::HTTP_OK)->header('Content-Type', $type);
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
            ]);
        
            // Generate a filename
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalFilename . '.' . $file->getClientOriginalExtension();
        
            // Store the file in the 'public/groupImages' directory
            $path = $file->storeAs('groupImages/'.$request->input('upload_session'), $filename, 'public');
                
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
        Session::pull('upload_session');   
        
        // Get the upload session from the request
        $uploadSession = $request->input('upload_session');
    
        // Define the path to the folder
        $folderPath = 'groupImages/' . $uploadSession;
    
        // Check if the folder exists
        if (Storage::disk('public')->exists($folderPath)) {
            // Delete the folder and its contents
            Storage::disk('public')->deleteDirectory($folderPath);
        }
    
        // Redirect to the franchise dashboard
        return response()->json(['status'=>true]); 
    }

    public function groupImageSubmit(Request $request)
    {
        // Validate the request as necessary
        $request->validate([
            'upload_session' => 'required|string',
            'artifact-to-folder-map' => 'required|string', // Ensure this field is sent
        ]);
    
        // Decode the JSON data from the artifact-to-folder-map
        $artifactToFolderMap = json_decode($request->input('artifact-to-folder-map'), true);
        $folderPath = 'groupImages/' . session('upload_session');
    
        // Now you can process this mapping as needed
        foreach ($artifactToFolderMap as $artifact => $folderKey) {
            // Check if folderKey is not 'discard_image' or 'no_match'
            if ($folderKey !== "discard_image" && $folderKey !== "no_match") {
                $extension = pathinfo($artifact, PATHINFO_EXTENSION); // Get the file extension

                $newPath = 'groupImages/' . $folderKey . '.' . $extension; // Ensure you're placing it in a folder
    
                // Check if the artifact exists before moving
                if (Storage::disk('public')->exists($artifact)) {
                    // Move the file to the new location
                    Storage::disk('public')->move($artifact, $newPath);
                    $this->imageService->createGroupImage($folderKey, $extension);
                }
            }else{
                Storage::disk('public')->delete($artifact);
            }
        }
        Storage::disk('public')->deleteDirectory($folderPath);
    
        // Return a response, redirect, or whatever your flow requires
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
            
        // Get the folder_key and file extension
            $folderKey = $request->input('folder_key');
            $extension = $file->getClientOriginalExtension();
            
        // Define the file name as folder_key.extension
            $fileName = $folderKey . '.' . $extension;
            
        // Store the file in the 'groupImages' folder in the public disk
            $filePath = $file->storeAs('groupImages', $fileName, 'public');
            $this->imageService->createGroupImage($folderKey, $extension);
            
        // Respond with success and the full URL of the uploaded file
            return response()->json([
                'message' => 'Image uploaded successfully',
                'full_url' => asset('/storage/'.$filePath),  // This generates the public URL
            ]);
    }

    public function groupImageDeleteFile(Request $request)
    {
        $folderKey = $request->input('folder_key');
        $fileName = $this->imageService->deleteGroupImage($folderKey);

        if ($fileName) {
            // Correct usage of relative path with 'Storage::delete'
            Storage::disk('public')->delete('groupImages/' . $fileName);
            
            return response()->json([
                'message' => 'Image deleted successfully',
            ]);
        } else {
            return response()->json([
                'message' => 'Error deleting image',
            ], 400);
        }
    }
}
