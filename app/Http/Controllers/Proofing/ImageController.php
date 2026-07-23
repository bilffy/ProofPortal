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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use App\Models\Folder;

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
        $imgHandle = null;
        try {
            $w = intval($request->query('imgClientSizeW'));
            $h = intval($request->query('imgClientSizeH'));
            $xPercent = (float) $request->query('mousePosPercentX');
            $yPercent = (float) $request->query('mousePosPercentY');
            $a = intval($request->query('anchor', 5));
            $artifactImage = Crypt::decryptString($request->query('artifactNameCrypt'));

            $folderKey = pathinfo($artifactImage, PATHINFO_FILENAME);

            // Fetch and resolve image location + version metadata (Cached 10 mins)
            $metaData = Cache::remember("zoom_meta_v2_{$folderKey}", 600, function () use ($folderKey) {
                $folder = Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
                if ($folder && $folder->job) {
                    $image = $this->imageService->getImagesByFolderKey($folderKey)->first();
                    if (!$image) return null;
                    
                    return [
                        'path' => "{$folder->job->seasons->code}/{$folder->job->ts_schoolkey}/{$folder->job->ts_jobkey}/folders/{$image->image_path}{$this->normalizeImageFilename($image->name)}",
                        'version' => $image->updated_at ? strtotime($image->updated_at) : 'v1'
                    ];
                }
                return null;
            });

            if (!$metaData) {
                return response()->json(['error' => 'Image metadata not found'], 404);
            }

            // Layer 1: Serve final processed image variant
            $processedKey = "zoom_out_{$folderKey}_{$metaData['version']}_{$w}_{$h}_{$xPercent}_{$yPercent}_{$a}";
            if ($cached = Cache::store('file')->get($processedKey)) {
                return new Response($cached, 200, [
                    'Content-Type'  => 'image/jpeg',
                    'Cache-Control' => 'public, max-age=3600',
                ]);
            }

            // Layer 2: Fetch raw binary (tied explicitly to structural cache token version)
            $binaryKey = "zoom_bin_{$folderKey}_{$metaData['version']}";
            $imageContent = Cache::store('file')->remember($binaryKey, 600, function () use ($metaData) {
                $path = $this->normalizeCacheImageUrl($metaData['path']);
                $response = Http::timeout(15)->withoutVerifying()->get(rtrim(config('services.exportImageLocation'), '/') . '/' . ltrim($path, '/'));
                return $response->successful() ? $response->body() : null;
            });
                
            if (!$imageContent) {
                return response()->json(['error' => 'Image content not found'], 404);
            }

            $imgHandle = Image::make($imageContent);
            $xPosition = intval($imgHandle->width() * $xPercent);
            $yPosition = intval($imgHandle->height() * $yPercent);

            switch ($a) {
                case 7: $xPoint = $xPosition; $yPoint = $yPosition; break;
                case 8: $xPoint = intval(round($xPosition - ($w / 2))); $yPoint = $yPosition; break;
                case 9: $xPoint = intval(round($xPosition - $w)); $yPoint = $yPosition; break;
                case 4: $xPoint = $xPosition; $yPoint = intval(round($yPosition - ($h / 2))); break;
                case 5: $xPoint = intval(round($xPosition - ($w / 2))); $yPoint = intval(round($yPosition - ($h / 2))); break;
                case 6: $xPoint = intval(round($xPosition - $w)); $yPoint = intval(round($yPosition - ($h / 2))); break;
                case 1: $xPoint = $xPosition; $yPoint = intval(round($yPosition - $h)); break;
                case 2: $xPoint = intval(round($xPosition - ($w / 2))); $yPoint = intval(round($yPosition - $h)); break;
                case 3: $xPoint = intval(round($xPosition - $w)); $yPoint = intval(round($yPosition - $h)); break;
                default: $xPoint = $xPosition; $yPoint = $yPosition; break;
            }

            $imgHandle->crop($w, $h, $xPoint, $yPoint);

            $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');
            if (file_exists($watermarkUrl)) {
                $watermark = Image::make($watermarkUrl);
                $watermark->resize($imgHandle->width(), $imgHandle->height());
                $imgHandle->insert($watermark, 'top-left', 0, 0);
                $watermark->destroy();
            }

            $encoded = (string) $imgHandle->encode('jpg', 85);
            Cache::store('file')->put($processedKey, $encoded, 3600);

            return new Response($encoded, 200, [
                'Content-Type'  => 'image/jpeg',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing zoom image: ' . $e->getMessage());
            return response()->json(['error' => 'Image processing failed'], 500);
        } finally {
            if ($imgHandle instanceof \Intervention\Image\Image) {
                $imgHandle->destroy();
            }
        }
    }

    public function serveImage($fileOrigin, $filename, $jobKey)
    {
        try {
            $deCryptfilename = Crypt::decryptString($filename);
            $deCryptjobKey = Crypt::decryptString($jobKey);

            if (empty($deCryptfilename) || strlen($deCryptfilename) < 2) {
                Log::error("Invalid decrypted filename: " . json_encode($deCryptfilename));
                return $this->serveFallback();
            }

            // High-Performance Optimization: Unified query execution paths with relational eager loads
            $folderContext = Folder::with(['job.seasons'])
                ->whereHas('job', function($query) use ($deCryptjobKey) {
                    $query->where('ts_jobkey', $deCryptjobKey);
                })->first();

            if (!$folderContext || !$folderContext->job || !$folderContext->job->seasons) {
                return $this->serveFallback();
            }

            $image = $this->imageService->getImagesBySubjectKey($deCryptfilename)->first();
            if (!$image) {
                return $this->serveFallback();
            }

            $job = $folderContext->job;
            $fileName = $this->normalizeImageFilename($image->name);
            if ($fileName === '') {
                return $this->serveFallback();
            }
            $imageUrl = rtrim(config('services.exportImageLocation'), '/') . "/{$job->seasons->code}/{$job->ts_schoolkey}/{$deCryptjobKey}/{$fileOrigin}/{$image->image_path}{$fileName}";

            $response = Http::timeout(15)->withoutVerifying()->get($imageUrl);

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

        if ($step === 'match') {
            if (Session::has('upload_session')) {
                $uploadSession = session('upload_session');
                $sessionFiles = Storage::disk('public')->files($uploadSession);
            } else {
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
        $img = null;
        $watermark = null;
        try {
            $deCryptfilename = Crypt::decryptString($filename);
            $folderKey = pathinfo($deCryptfilename, PATHINFO_FILENAME);

            // Layer 1: serve fully watermarked image from cache (24h)
            $outputKey = "group_img_out_{$folderKey}";
            if ($cached = Cache::store('file')->get($outputKey)) {
                return response($cached, Response::HTTP_OK)
                    ->header('Content-Type', 'image/jpeg')
                    ->header('Cache-Control', 'public, max-age=86400');
            }

            $imageContent = null;
            $contentType = 'image/jpeg';

            // Layer 2: resolve and cache the remote URL metadata (5 min)
            $metaKey = "group_img_meta_{$folderKey}";
            $imageUrl = Cache::remember($metaKey, 300, function () use ($folderKey) {
                $folder = Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
                if ($folder && $folder->job) {
                    $image = $this->imageService->getImagesByFolderKey($folderKey)->first();
                    if ($image) {
                        $job = $folder->job;
                        $fileName = $this->normalizeImageFilename($image->name);
                        return rtrim(config('services.exportImageLocation'), '/') . "/{$job->seasons->code}/{$job->ts_schoolkey}/{$job->ts_jobkey}/folders/{$image->image_path}{$fileName}";
                    }
                }
                return null;
            });

            // Cache may still hold an older URL with .JPG — always lowercase the extension.
            if ($imageUrl) {
                $imageUrl = $this->normalizeCacheImageUrl($imageUrl);
            }

            if ($imageUrl) {
                $response = Http::timeout(15)->withoutVerifying()->get($imageUrl);
                if ($response->successful()) {
                    $imageContent = $response->body();
                    $contentType = $response->header('Content-Type', 'image/jpeg');
                } else {
                    Log::warning("Cache server returned {$response->status()} for group image: {$imageUrl}");
                }
            }

            if (!$imageContent) {
                $path = 'groupImages/' . $deCryptfilename;
                if (Storage::disk('public')->exists($path)) {
                    $imageContent = Storage::disk('public')->get($path);
                    $contentType = Storage::disk('public')->mimeType($path);
                }
            }

            if ($imageContent) {
                $img = Image::make($imageContent);
                $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');

                if (file_exists($watermarkUrl)) {
                    $watermark = Image::make($watermarkUrl);
                    $watermark->resize($img->width(), $img->height());
                    $img->insert($watermark, 'top-left', 0, 0);
                }

                $encoded = (string) $img->encode('jpg', 85);
                Cache::store('file')->put($outputKey, $encoded, 86400);

                return response($encoded, Response::HTTP_OK)
                    ->header('Content-Type', $contentType)
                    ->header('Cache-Control', 'public, max-age=86400');
            }

        } catch (\Exception $e) {
            Log::error('Error showing group image: ' . $e->getMessage(), [
                'folderKey' => $folderKey ?? null,
            ]);
        } finally {
            if ($img instanceof \Intervention\Image\Image) $img->destroy();
            if ($watermark instanceof \Intervention\Image\Image) $watermark->destroy();
        }

        return $this->serveFallback();
    }

    public function groupImageUpload(Request $request)
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file'           => 'image|mimes:jpeg,png,jpg|max:15360',
                'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
            ]);

            $file = $request->file('file');
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                . '.' . $file->getClientOriginalExtension();

            $file->storeAs($request->input('upload_session'), $filename, 'public');

            if (Session::get('upload_session') !== $request->input('upload_session')) {
                Session::put('upload_session', $request->input('upload_session'));
            }
        }
    }

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

    public function groupImageSubmit(Request $request)
    {
        // Bulk submit can process dozens of large JPGs in one request.
        @ini_set('memory_limit', '512M');

        $request->validate([
            'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/', 
            'artifact-to-folder-map' => 'required|string', 
            'jobHash' => 'nullable|string',
        ]);
    
        $artifactToFolderMap = json_decode($request->input('artifact-to-folder-map'), true);
        $folderPath = $request->input('upload_session');
        
        // Anti N+1 Optimization: Extract folder keys and eager load them all at once
        $validFolderKeys = array_filter($artifactToFolderMap, function($key) {
            return $key !== "discard_image" && $key !== "no_match";
        });

        $foldersCollection = Folder::with(['job.seasons'])
            ->whereIn('ts_folderkey', array_values($validFolderKeys))
            ->get()
            ->keyBy('ts_folderkey');

        $uploader = new ImageUploader();
    
        foreach ($artifactToFolderMap as $artifact => $folderKey) {
            if ($folderKey !== "discard_image" && $folderKey !== "no_match") {
                $extension = strtolower(pathinfo($artifact, PATHINFO_EXTENSION));
                
                $folder = $foldersCollection->get($folderKey);
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
                    $hash = hash_hmac('sha256', 'folders', $folderKey);
                    $p1 = substr($hash, 0, 2);
                    $p2 = substr($hash, 2, 2);
                    $p3 = substr($hash, 4, 2);
                    $remotePath = "{$seasonCodeLocal}/{$schoolKeyLocal}/{$jobKeyLocal}/folders/{$p3}/{$p1}/{$p2}/{$folderKey}.{$extension}";
                    $path = "{$p3}/{$p1}/{$p2}/";
                    $fileName = "{$folderKey}.{$extension}";

                    if (Storage::disk('public')->exists($artifact)) {
                        // Stream each file — do not load all image bodies into memory at once
                        $stream = Storage::disk('public')->readStream($artifact);
                        if ($stream === false) {
                            Log::error("Unable to open group image stream: {$artifact}");
                            continue;
                        }

                        try {
                            $uploader->upload($stream, $remotePath, $fileName);
                            $this->imageService->createGroupImage($folderKey, $path, $fileName);
                            
                            Cache::forget("zoom_meta_v2_{$folderKey}");
                            Cache::forget("group_img_meta_{$folderKey}");
                            Cache::store('file')->forget("group_img_out_{$folderKey}");
                        } finally {
                            if (is_resource($stream)) {
                                fclose($stream);
                            }
                            unset($stream);
                        }

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
    
        if ($request->has('jobHash')) {
            return redirect()->to(URL::signedRoute('proofing.dashboard', ['hash' => $request->input('jobHash')]));
        }
        return redirect()->route('proofing');
    }

    public function groupImageUploadFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png,image/jpg|max:15360',
                'folder_key' => 'required|string',
                'folder_name' => 'required|string',
            ], [
                'file.required' => 'Please select an image to upload.',
                'file.mimes' => 'Only JPG and PNG images are allowed.',
                'file.mimetypes' => 'Only JPG and PNG images are allowed.',
                'file.max' => 'Each image must be 15MB or smaller.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                $uploadError = $file ? $file->getErrorMessage() : 'No file received.';
                return response()->json(['message' => $uploadError], 422);
            }

            $folderKey = $request->input('folder_key');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';

            $folder = Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();

            if (!$folder || !$folder->job) {
                return response()->json(['message' => 'Folder or job not found for this upload.'], 404);
            }

            $job = $folder->job;
            if (!$job->seasons) {
                return response()->json(['message' => 'Season not found for this job.'], 404);
            }

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

            $uploader = new ImageUploader();

            $stream = fopen($file->getRealPath(), 'r');
            if ($stream === false) {
                return response()->json(['message' => 'Unable to read the uploaded file.'], 500);
            }

            try {
                $uploader->upload($stream, $remotePath, $fileName);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $this->imageService->createGroupImage($folderKey, $path, $fileName);

            Cache::forget("zoom_meta_v2_{$folderKey}");
            Cache::forget("group_img_meta_{$folderKey}");
            Cache::store('file')->forget("group_img_out_{$folderKey}");

            $encryptedFilename = Crypt::encryptString($fileName);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'full_url' => route('image.show', ['filename' => $encryptedFilename]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to upload group image', [
                'folder_key' => $request->input('folder_key'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Upload failed.',
            ], 500);
        }
    }

    public function groupImageDeleteFile(Request $request)
    {
        $folderKey = $request->input('folder_key');
        
        $folder = Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
        if ($folder && $folder->job) {
            $imageRecord = \App\Models\Image::where('keyvalue', $folderKey)->first();
        }

        $fileName = $this->imageService->deleteGroupImage($folderKey);

        Cache::forget("zoom_meta_v2_{$folderKey}");
        Cache::forget("group_img_meta_{$folderKey}");
        Cache::store('file')->forget("group_img_out_{$folderKey}");

        if ($fileName) {
            if (Storage::disk('public')->exists('groupImages/' . $fileName)) {
                Storage::disk('public')->delete('groupImages/' . $fileName);
            }
            
            return response()->json([
                'message' => 'Image deleted successfully',
            ]);
        } else {
            return response()->json([
                'message' => 'Error deleting image',
            ], 400);
        }
    }

    /**
     * Proofing-cache is case-sensitive; stored objects use lowercase extensions (.jpg).
     */
    private function normalizeImageFilename(?string $filename): string
    {
        if ($filename === null || $filename === '') {
            return '';
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if ($extension === '') {
            return $filename;
        }

        return pathinfo($filename, PATHINFO_FILENAME) . '.' . strtolower($extension);
    }

    /**
     * Lowercase only the file extension on a cache URL or relative path.
     */
    private function normalizeCacheImageUrl(string $urlOrPath): string
    {
        $basename = basename(parse_url($urlOrPath, PHP_URL_PATH) ?: $urlOrPath);
        $normalized = $this->normalizeImageFilename($basename);

        if ($basename === $normalized || $basename === '') {
            return $urlOrPath;
        }

        return preg_replace('/' . preg_quote($basename, '/') . '$/', $normalized, $urlOrPath) ?? $urlOrPath;
    }
}
