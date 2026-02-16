<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Services\Proofing\ImageService;
use Intervention\Image\Facades\Image;
use App\Services\Proofing\JobService;
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
use Illuminate\Support\Facades\URL;

class ImageController extends Controller
{
    
    protected $jobService;
    protected $imageService;

    public function __construct(JobService $jobService, ImageService $imageService)
    {
        $this->jobService = $jobService;
        $this->imageService = $imageService;
    }

    public function zoom(Request $request)
    {
        try {
            $w = $request->query('imgClientSizeW');
            $h = $request->query('imgClientSizeH');
            $xPercent = $request->query('mousePosPercentX');
            $yPercent = $request->query('mousePosPercentY');
            $artifactImage = Crypt::decryptString($request->query('artifactNameCrypt'));
            
            $image = Image::make(storage_path('app/public/groupImages/'.$artifactImage));

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

    public function serveImage($filename,$jobKey)
    {
        try {
            // 1. Decrypt the filename
            $deCryptfilename = Crypt::decryptString($filename);
            $deCryptjobKey = Crypt::decryptString($jobKey);

            // Basic validation for the decrypted string
            if (empty($deCryptfilename) || strlen($deCryptfilename) < 2) {
                Log::error("Invalid decrypted filename: " . json_encode($deCryptfilename));
                return $this->serveFallback();
            }

            // 2. Construct the URL 
            $char1 = $deCryptfilename[0];
            $char2 = $deCryptfilename[1];
            
            $imageUrl = rtrim(config('services.exportImageLocation'), '/') . "/{$deCryptjobKey}/{$char1}/{$char2}/{$deCryptfilename}.jpg";

            // 3. Fetch the image while bypassing SSL verification
            $response = Http::timeout(15)
                ->withoutVerifying() // FIXES: cURL error 60
                ->get($imageUrl);

            // 4. If successful, stream the image back
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
            $path = 'groupImages/' . Crypt::decryptString($filename);

            if (!Storage::disk('public')->exists($path)) {
                abort(404);
            }

            $file = Storage::disk('public')->get($path);
            $type = Storage::disk('public')->mimeType($path);

            return response($file, Response::HTTP_OK)->header('Content-Type', $type);
        } catch (\Exception $e) {
            Log::error('Error showing group image: ' . $e->getMessage());
            abort(404);
        }
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
            } else {
                Storage::disk('public')->delete($artifact);
            }
        }
        Storage::disk('public')->deleteDirectory($folderPath);
    
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
            
        // Get the folder_key and file extension
            $folderKey = $request->input('folder_key');
            $extension = $file->getClientOriginalExtension();
            
        // Define the file name as folder_key.extension
            $fileName = $folderKey . '.' . $extension;
            
        // Store the file in the 'groupImages' folder in the public disk
            $filePath = $file->storeAs('groupImages', $fileName, 'public');
            $this->imageService->createGroupImage($folderKey, $extension);
            $encryptedFilename = Crypt::encryptString($fileName);
            
        // Respond with success and the full URL of the uploaded file
            return response()->json([
                'message' => 'Image uploaded successfully',
                'full_url' => route('image.show', ['filename' => $encryptedFilename]),
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
