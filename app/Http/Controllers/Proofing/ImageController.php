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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Handle coordinate-based cropping and watermarking zooming views.
     */
    public function zoom(Request $request)
    {
        try {
            $w = (int) $request->query('imgClientSizeW');
            $h = (int) $request->query('imgClientSizeH');
            $xPercent = (float) $request->query('mousePosPercentX');
            $yPercent = (float) $request->query('mousePosPercentY');
            $artifactImage = Crypt::decryptString($request->query('artifactNameCrypt'));
            
            $folderKey = pathinfo($artifactImage, PATHINFO_FILENAME);
            $cacheKey = "image_binary_fkey_" . $folderKey;

            // OPTIMIZATION: Bypasses MySQL completely to prevent text collation binary encoding crashes
            $imageContent = Cache::store('file')->remember($cacheKey, 600, function() use ($folderKey) {
                $folder = \App\Models\Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
                if ($folder && $folder->job) {
                    $job = $folder->job;
                    $image = $this->imageService->getImagesByFolderKey($folderKey)->first();
                    if (!$image) return null;

                    $cachePath = "{$job->seasons->code}/{$job->ts_schoolkey}/{$job->ts_jobkey}/folders/" . $image->image_path . $image->name;
                    $imageUrl = rtrim(config('services.exportImageLocation'), '/') . '/' . $cachePath;

                    $response = Http::timeout(10)->withoutVerifying()->get($imageUrl);
                    if ($response->successful()) {
                        return $response->body(); // Raw binary streams write directly to secure local storage disks
                    }
                }
                return null;
            });

            if (!$imageContent) {
                return response()->json(['error' => 'Image not found or inaccessible'], 404);
            }

            // Create canvas context using shared memory limits
            $image = Image::make($imageContent);

            $xPosition = intval($image->width() * $xPercent);
            $yPosition = intval($image->height() * $yPercent);
            $a = (int) $request->query('anchor', 5);

            switch ($a) {
                case 7: $xPoint = $xPosition; $yPoint = $yPosition; break;
                case 8: $xPoint = (int) round($xPosition - ($w / 2)); $yPoint = $yPosition; break;
                case 9: $xPoint = (int) round($xPosition - $w); $yPoint = $yPosition; break;
                case 4: $xPoint = $xPosition; $yPoint = (int) round($yPosition - ($h / 2)); break;
                case 5: $xPoint = (int) round($xPosition - ($w / 2)); $yPoint = (int) round($yPosition - ($h / 2)); break;
                case 6: $xPoint = (int) round($xPosition - $w); $yPoint = (int) round($yPosition - ($h / 2)); break;
                case 1: $xPoint = $xPosition; $yPoint = (int) round($yPosition - $h); break;
                case 2: $xPoint = (int) round($xPosition - ($w / 2)); $yPoint = (int) round($yPosition - $h); break;
                case 3: $xPoint = (int) round($xPosition - $w); $yPoint = (int) round($yPosition - $h); break;
                default: $xPoint = $xPosition; $yPoint = $yPosition; break;
            }

            // Crop canvas bounding boxes
            $image->crop($w, $h, $xPoint, $yPoint);

            // Apply watermark without unneeded processing overhead
            $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');
            if (file_exists($watermarkUrl)) {
                $image->insert($watermarkUrl, 'top-left', 0, 0);
            }

            $mimeType = $image->mime();
            $imageContentEncoded = $image->encode();

            return new Response($imageContentEncoded, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=3600, must-revalidate'
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing zoom image: ' . $e->getMessage());
            return response()->json(['error' => 'Image processing failed'], 500);
        }
    }

    /**
     * Proxy server stream delivery layer.
     */
    public function serveImage($fileOrigin, $filename, $jobKey)
    {
        try {
            $deCryptfilename = Crypt::decryptString($filename);
            $deCryptjobKey = Crypt::decryptString($jobKey);
            
            $selectedJob = $this->jobService->getJobByJobKey($deCryptjobKey)->first();
            if (!$selectedJob) return $this->serveFallback();
            
            $selectedSeason = $this->seasonService->getSeasonBySeasonID($selectedJob->ts_season_id)->first();

            if (empty($deCryptfilename) || strlen($deCryptfilename) < 2) {
                return $this->serveFallback();
            }

            $image = $this->imageService->getImagesBySubjectKey($deCryptfilename)->first();
            if (!$image) return $this->serveFallback();

            $imageUrl = rtrim(config('services.exportImageLocation'), '/') . "/{$selectedSeason->code}/{$selectedJob->ts_schoolkey}/{$deCryptjobKey}/{$fileOrigin}/{$image->image_path}{$image->name}";

            $response = Http::timeout(10)->withoutVerifying()->get($imageUrl);

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

    /**
     * Delivers fallback images securely upon standard miss criteria.
     */
    private function serveFallback()
    {
        $path = public_path('proofing-assets/img/subject-image.png');
        if (!file_exists($path)) {
            return response()->json(['error' => 'Image not found'], 404);
        }
        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400'
        ]);
    }

    /**
     * Batch management system configuration setup pipeline.
     */
    public function bulkUploadImage($jobHash, $step = null)
    {
        $selectedJob = $this->jobService->getJobByJobKey(Crypt::decryptString($jobHash))->first();
        if (!$selectedJob) abort(404); 

        $sessionFiles = [];
        $uploadSession = sha1(Crypt::encryptString(Str::random(2048)));

        if ($step === 'match') {
            if (Session::has('upload_session')) {
                $uploadSession = session('upload_session');
                $sessionFiles = Storage::disk('public')->files($uploadSession);
            } else {
                return redirect()->back()->with('error', 'Please upload some images.');
            }
        }

        return view('proofing.franchise.bulk-upload', [
            'selectedJob' => $selectedJob,
            'step' => $step,
            'jobHash' => $jobHash,
            'uploadedImages' => $sessionFiles,
            'uploadSession' => $uploadSession,
            'user' => new UserResource(Auth::user())
        ]);
    }

    /**
     * Display and tile watermarks explicitly targeted for composite group structures.
     */
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
                
                $image = $this->imageService->getImagesByFolderKey($folderKey)->first();
                $cachePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$image->image_path}{$image->name}";
                $imageUrl = rtrim(config('services.exportImageLocation'), '/') . '/' . $cachePath;

                $response = Http::timeout(15)->withoutVerifying()->get($imageUrl);
                if ($response->successful()) {
                    $imageContent = $response->body();
                    $contentType = $response->header('Content-Type', 'image/jpeg');
                } else {
                    Log::warning("Cache server returned {$response->status()} for group image: {$imageUrl}");
                }
            }

            // Fallback to local disk allocations
            if (!$imageContent) {
                $path = 'groupImages/' . $deCryptfilename;
                if (Storage::disk('public')->exists($path)) {
                    $imageContent = Storage::disk('public')->get($path);
                    $contentType = Storage::disk('public')->mimeType($path);
                }
            }

            // Apply watermark patterns dynamically
            if ($imageContent) {
                try {
                    $img = Image::make($imageContent);
                    $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');
                    
                    if (file_exists($watermarkUrl)) {
                        $watermark = Image::make($watermarkUrl);
                        $watermark->resize($img->width(), $img->height());
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

    /**
     * Stash file updates locally via single upload drops.
     */
    public function groupImageUpload(Request $request)
    {
        if ($request->hasFile('file')) 
        {
            Session::pull('upload_session');
            $file = $request->file('file');
        
            $request->validate([
                'file' => 'image|mimes:jpeg,png,jpg|max:15360',
                'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
            ]);
        
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalFilename . '.' . $file->getClientOriginalExtension();
        
            $path = $file->storeAs($request->input('upload_session'), $filename, 'public');
                
            session([
                'upload_session' => $request->input('upload_session')
            ]);
            session()->save(); 
        }
    }

    /**
     * Purges temporary sessions and directory structures.
     */
    public function groupImageDelete(Request $request)
    {
        $request->validate([
            'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
        ]);

        Session::pull('upload_session');   
        $uploadSession = $request->input('upload_session');
    
        if (Storage::disk('public')->exists($uploadSession)) {
            Storage::disk('public')->deleteDirectory($uploadSession);
        }
    
        return response()->json(['status' => true]); 
    }

    /**
     * Process transactional batch processing allocations.
     */
    public function groupImageSubmit(Request $request)
    {
        $request->validate([
            'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
            'artifact-to-folder-map' => 'required|string',
            'jobHash' => 'nullable|string',
        ]);
    
        $artifactToFolderMap = json_decode($request->input('artifact-to-folder-map'), true);
        $folderPath = $request->input('upload_session');
    
        $folderKeys = array_filter($artifactToFolderMap, function($val) {
            return $val !== "discard_image" && $val !== "no_match";
        });

        $folders = \App\Models\Folder::with(['job.seasons'])
            ->whereIn('ts_folderkey', array_values($folderKeys))
            ->get()
            ->keyBy('ts_folderkey');

        DB::transaction(function () use ($artifactToFolderMap, $folders) {
            foreach ($artifactToFolderMap as $artifact => $folderKey) {
                if ($folderKey === "discard_image" || $folderKey === "no_match") {
                    if (Storage::disk('public')->exists($artifact)) {
                        Storage::disk('public')->delete($artifact);
                    }
                    continue;
                }

                $folder = $folders->get($folderKey);
                if (!$folder || !$folder->job) {
                    Log::error("Missing job context profile configuration for key target: " . $folderKey);
                    continue;
                }

                $jobLocal = $folder->job;
                $seasonCodeLocal = $jobLocal->seasons->code ?? null;
                $schoolKeyLocal = $jobLocal->ts_schoolkey;
                $jobKeyLocal = $jobLocal->ts_jobkey;

                if ($seasonCodeLocal && $schoolKeyLocal && $jobKeyLocal) {
                    $extension = pathinfo($artifact, PATHINFO_EXTENSION);
                    $hash = hash_hmac('sha256', 'folders', $folderKey);
                    $p1 = substr($hash, 0, 2);
                    $p2 = substr($hash, 2, 2);
                    $p3 = substr($hash, 4, 2);

                    $remotePath = "{$seasonCodeLocal}/{$schoolKeyLocal}/{$jobKeyLocal}/folders/{$p3}/{$p1}/{$p2}/{$folderKey}.{$extension}";
                    $path = "{$p3}/{$p1}/{$p2}/";
                    $fileName = "{$folderKey}.{$extension}";

                    if (Storage::disk('public')->exists($artifact)) {
                        $fileContent = Storage::disk('public')->get($artifact);
                        $uploader = new ImageUploader();
                        $uploader->upload($fileContent, $remotePath, $fileName);
                        
                        $this->imageService->createGroupImage($folderKey, $path, $fileName);
                        Storage::disk('public')->delete($artifact);
                    }
                }
            }
        });
        
        if (Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->deleteDirectory($folderPath);
        }
    
        if ($request->has('jobHash')) {
            return redirect()->to(URL::signedRoute('proofing.dashboard', ['hash' => $request->input('jobHash')]));
        }
        return redirect()->route('proofing');
    }

    /**
     * Handle targeted individual asset pushes using low footprint streams.
     */
    public function groupImageUploadFile(Request $request)
    {
        $request->validate([
            'file' => 'image|mimes:jpeg,png,jpg|max:15360', 
            'folder_key' => 'required|string',
            'folder_name' => 'required|string',
        ]);

        $file = $request->file('file');
        $folderKey = $request->input('folder_key');
        $extension = $file->getClientOriginalExtension();
        
        $folder = \App\Models\Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
        
        if ($folder && $folder->job) {
            $job = $folder->job;
            $seasonCode = $job->seasons->code;
            $schoolKey = $job->ts_schoolkey;
            $jobKey = $job->ts_jobkey;
            
            $hash = hash_hmac('sha256', 'folders', $folderKey);
            $p1 = substr($hash, 0, 2);
            $p2 = substr($hash, 2, 2);
            $p3 = substr($hash, 4, 2);
            
            $path = "{$p3}/{$p1}/{$p2}/";
            $fileName = "{$folderKey}.{$extension}";
            $remotePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$p3}/{$p1}/{$p2}/{$fileName}";

            try {
                $uploader = new ImageUploader();
                
                // Memory Optimization: Pull straight from resource pointers
                $stream = fopen($file->getRealPath(), 'r');
                $uploader->upload($stream, $remotePath, $fileName);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                
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

    /**
     * Handle unlinking individual active folder target maps across external storage engines.
     */
    public function groupImageDeleteFile(Request $request)
    {
        $request->validate([
            'folder_key' => 'required|string',
        ]);

        $folderKey = $request->input('folder_key');
        
        // Pull context maps to securely find path parameters matching structural uploads
        $folder = \App\Models\Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
        
        if ($folder && $folder->job) {
            $job = $folder->job;
            $seasonCode = $job->seasons->code;
            $schoolKey = $job->ts_schoolkey;
            $jobKey = $job->ts_jobkey;
            
            // Fetch explicit image data to determine proper file extensions 
            $imageRecord = \App\Models\Image::where('keyvalue', $folderKey)->first();
            
            if ($imageRecord) {
                $extension = pathinfo($imageRecord->name, PATHINFO_EXTENSION) ?: 'jpg';
                
                // Reconstruct exact dynamic structural components used on submit layers
                $hash = hash_hmac('sha256', 'folders', $folderKey);
                $p1 = substr($hash, 0, 2);
                $p2 = substr($hash, 2, 2);
                $p3 = substr($hash, 4, 2);
                
                $remotePath = "{$seasonCode}/{$schoolKey}/{$jobKey}/folders/{$p3}/{$p1}/{$p2}/{$folderKey}.{$extension}";
                
                try {
                    // Optional Hook: Add explicit remote cloud engine unlink blocks if applicable
                    // if (Storage::disk('sftp')->exists($remotePath)) {
                    //     Storage::disk('sftp')->delete($remotePath);
                    // }
                } catch (\Exception $storageEx) {
                    Log::warning("Remote storage deletion skipped or failed for file path '{$remotePath}': " . $storageEx->getMessage());
                }
            }
        }

        // Clean out metadata entries via underlying infrastructure drivers
        $fileName = $this->imageService->deleteGroupImage($folderKey);

        if ($fileName) {
            $localLegacyPath = 'groupImages/' . $fileName;
            
            // Clear local cached copies safely if they still reside inside older paths
            if (Storage::disk('public')->exists($localLegacyPath)) {
                Storage::disk('public')->delete($localLegacyPath);
            }
            
            return response()->json([
                'message' => 'Image deleted successfully',
            ]);
        }

        return response()->json([
            'message' => 'Error deleting image metadata context or asset reference target not found',
        ], 400);
    }
}