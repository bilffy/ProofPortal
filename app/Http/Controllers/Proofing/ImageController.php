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
    private const GROUP_IMAGE_MIN_BYTES = 256;
    private const GROUP_IMAGE_MIN_DIMENSION = 20;
    private const GROUP_IMAGE_MIN_JPEG_BYTES = 512;
    private const GROUP_IMAGE_THUMB_MAX_WIDTH = 200;

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

            // Always resolve from DB (do not cache meta): re-uploads keep the same filename/path,
            // so a stale meta/version would keep serving the previous image binary.
            $metaData = null;
            $folder = Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
            if ($folder && $folder->job) {
                $image = $this->imageService->getImagesByFolderKey($folderKey)->first();
                if ($image) {
                    $metaData = [
                        'path' => "{$folder->job->seasons->code}/{$folder->job->ts_schoolkey}/{$folder->job->ts_jobkey}/folders/{$image->image_path}{$this->normalizeImageFilename($image->name)}",
                        'version' => $image->updated_at ? strtotime($image->updated_at) : 'v1',
                    ];
                }
            }

            if (!$metaData) {
                return response()->json(['error' => 'Image metadata not found'], 404);
            }

            // Layer 1: Serve final processed image variant
            $processedKey = "zoom_out_v3_{$folderKey}_{$metaData['version']}_{$w}_{$h}_{$xPercent}_{$yPercent}_{$a}";
            if ($cached = Cache::store('file')->get($processedKey)) {
                return new Response($cached, 200, [
                    'Content-Type'  => 'image/jpeg',
                    'Cache-Control' => 'private, max-age=60',
                ]);
            }

            // Layer 2: Fetch raw binary (tied explicitly to structural cache token version)
            $binaryKey = "zoom_bin_v3_{$folderKey}_{$metaData['version']}";
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
                'Cache-Control' => 'private, max-age=60',
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

            $image = $this->imageService->getImagesBySubjectKey($deCryptfilename)->first();
            if (!$image) {
                Log::warning('serveImage: subject image not found', [
                    'subjectKey' => $deCryptfilename,
                    'jobKey' => $deCryptjobKey,
                ]);
                return $this->serveFallback();
            }

            $job = $image->jobs()->with('seasons')->first();
            if (!$job || (string) $job->ts_jobkey !== (string) $deCryptjobKey || !$job->seasons) {
                // Fallback: resolve season/school from any folder on the requested job
                $folderContext = Folder::with(['job.seasons'])
                    ->whereHas('job', function ($query) use ($deCryptjobKey) {
                        $query->where('ts_jobkey', $deCryptjobKey);
                    })->first();

                if (!$folderContext || !$folderContext->job || !$folderContext->job->seasons) {
                    Log::warning('serveImage: job/season context not found', [
                        'subjectKey' => $deCryptfilename,
                        'jobKey' => $deCryptjobKey,
                        'imageJobId' => $image->ts_job_id,
                    ]);
                    return $this->serveFallback();
                }

                $job = $folderContext->job;
            }

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

            Log::warning('serveImage: remote image fetch failed', [
                'subjectKey' => $deCryptfilename,
                'status' => $response->status(),
                'url' => $imageUrl,
            ]);

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

    public function showgroupImage(Request $request, $filename)
    {
        $variant = $request->query('variant') === 'thumb' ? 'thumb' : 'full';

        try {
            $deCryptfilename = Crypt::decryptString($filename);
            $folderKey = pathinfo($deCryptfilename, PATHINFO_FILENAME);
            $imageRecord = $this->imageService->getImagesByFolderKey($folderKey)->first();

            if ($variant === 'thumb') {
                $diskThumb = $this->readGroupImageThumb($folderKey);
                if ($diskThumb !== null && $this->isValidCachedGroupJpeg($diskThumb)) {
                    return $this->groupImageResponse($diskThumb);
                }
            }

            $outputKey = $this->groupImageOutputCacheKey($folderKey, $imageRecord, $variant);

            $legacyKey = $variant === 'full' ? "group_img_out_{$folderKey}" : null;
            $cached = Cache::store('file')->get($outputKey);
            if ($cached === null && $legacyKey !== null) {
                $cached = Cache::store('file')->get($legacyKey);
            }

            if ($this->isValidCachedGroupJpeg($cached)) {
                if ($variant === 'thumb') {
                    $this->storeGroupImageThumb($folderKey, $cached);
                }

                return $this->groupImageResponse($cached);
            }

            if ($cached !== null) {
                Cache::store('file')->forget($outputKey);
                if ($legacyKey !== null) {
                    Cache::store('file')->forget($legacyKey);
                }
            }

            // Thumbs only need one output file; skip caching multi-MB source bytes.
            $cacheSource = $variant === 'full';
            $imageContent = $this->resolveGroupImageSourceBytes($folderKey, $imageRecord, $cacheSource);
            if ($imageContent === null) {
                return $this->serveFallback();
            }

            $encoded = $this->buildWatermarkedGroupJpeg($imageContent, $variant);
            $sourceByteLength = $variant === 'full' ? strlen($imageContent) : 0;
            if (!$this->isValidCachedGroupJpeg($encoded, $sourceByteLength)) {
                Log::warning('Rejected invalid group image output; not caching', [
                    'folderKey' => $folderKey,
                    'variant' => $variant,
                    'sourceBytes' => strlen($imageContent),
                    'outputBytes' => strlen($encoded),
                ]);

                return $this->serveFallback();
            }

            if ($variant === 'thumb') {
                $this->storeGroupImageThumb($folderKey, $encoded);
            } else {
                Cache::store('file')->put($outputKey, $encoded, 86400);
            }

            return $this->groupImageResponse($encoded);
        } catch (\Exception $e) {
            Log::error('Error showing group image: ' . $e->getMessage(), [
                'variant' => $variant ?? 'full',
            ]);
        }

        return $this->serveFallback();
    }

    /**
     * Pre-generate table thumbnails in the background (config-job page).
     */
    public function warmGroupImageThumbs(Request $request)
    {
        $folderKeys = $request->input('folder_keys', []);
        if (!is_array($folderKeys)) {
            return response()->json(['message' => 'Invalid folder list.'], 422);
        }

        $folderKeys = array_values(array_unique(array_filter(array_map(
            fn ($key) => is_string($key) ? trim($key) : '',
            $folderKeys
        ))));
        $folderKeys = array_slice($folderKeys, 0, 6);

        $warmed = [];
        $skipped = [];
        $failed = [];

        foreach ($folderKeys as $folderKey) {
            $existing = $this->readGroupImageThumb($folderKey);
            if ($existing !== null && $this->isValidCachedGroupJpeg($existing)) {
                $skipped[] = $folderKey;
                continue;
            }

            try {
                $imageRecord = $this->imageService->getImagesByFolderKey($folderKey)->first();
                if (!$imageRecord) {
                    $failed[] = $folderKey;
                    continue;
                }

                $imageContent = $this->resolveGroupImageSourceBytes($folderKey, $imageRecord, false);
                if ($imageContent === null) {
                    $failed[] = $folderKey;
                    continue;
                }

                $encoded = $this->buildWatermarkedGroupJpeg($imageContent, 'thumb');
                if (!$this->isValidCachedGroupJpeg($encoded)) {
                    $failed[] = $folderKey;
                    continue;
                }

                $this->storeGroupImageThumb($folderKey, $encoded);
                $warmed[] = $folderKey;
            } catch (\Throwable $e) {
                Log::warning('Group image thumb warm failed', [
                    'folderKey' => $folderKey,
                    'error' => $e->getMessage(),
                ]);
                $failed[] = $folderKey;
            }
        }

        return response()->json([
            'warmed' => $warmed,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);
    }

    private function groupImageResponse(string $jpeg)
    {
        return response($jpeg, Response::HTTP_OK)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function resolveGroupImageSourceBytes(string $folderKey, $imageRecord, bool $persistSourceCache = true): ?string
    {
        $sourceKey = $this->groupImageSourceCacheKey($folderKey, $imageRecord);
        if ($persistSourceCache) {
            $cachedSource = Cache::store('file')->get($sourceKey);
            if ($this->isValidGroupImageSource($cachedSource)) {
                return $cachedSource;
            }

            if ($cachedSource !== null) {
                Cache::store('file')->forget($sourceKey);
            }
        }

        $deCryptfilename = $imageRecord?->name;
        $imageContent = null;

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

        if ($imageUrl) {
            $imageUrl = $this->normalizeCacheImageUrl($imageUrl);
            $response = Http::timeout(15)->withoutVerifying()->get($imageUrl);
            if ($response->successful()) {
                $imageContent = $response->body();
            } else {
                Log::warning("Cache server returned {$response->status()} for group image: {$imageUrl}");
            }
        }

        if (!$imageContent && $deCryptfilename) {
            $path = 'groupImages/' . $deCryptfilename;
            if (Storage::disk('public')->exists($path)) {
                $imageContent = Storage::disk('public')->get($path);
            }
        }

        if (!$imageContent || !$this->isValidGroupImageSource($imageContent)) {
            if ($imageContent) {
                Log::warning('Rejected invalid group image source', ['folderKey' => $folderKey]);
            }

            return null;
        }

        if ($persistSourceCache) {
            Cache::store('file')->put($sourceKey, $imageContent, 86400);
        }

        return $imageContent;
    }

    private function cleanupBulkUploadSession(string $sessionPath): void
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($sessionPath)) {
            return;
        }

        foreach ($disk->files($sessionPath) as $file) {
            try {
                if ($disk->exists($file)) {
                    $disk->delete($file);
                }
            } catch (\Throwable $e) {
                Log::warning('Bulk upload temp file cleanup skipped', [
                    'path' => $file,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $absolute = $disk->path($sessionPath);
        if (!is_dir($absolute)) {
            return;
        }

        $entries = @scandir($absolute);
        if ($entries === false) {
            return;
        }

        $remaining = array_diff($entries, ['.', '..']);
        if ($remaining !== []) {
            return;
        }

        if (!@rmdir($absolute)) {
            Log::warning('Bulk upload session directory could not be removed', [
                'path' => $sessionPath,
            ]);
        }
    }

    private function groupImageThumbRelativePath(string $folderKey): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9._-]/', '_', $folderKey) ?: 'unknown';

        return "groupImageThumbs/{$safeKey}.jpg";
    }

    private function readGroupImageThumb(string $folderKey): ?string
    {
        $path = $this->groupImageThumbRelativePath($folderKey);
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($path);

        return is_string($bytes) && $bytes !== '' ? $bytes : null;
    }

    private function storeGroupImageThumb(string $folderKey, string $jpeg): void
    {
        if (!$this->isValidCachedGroupJpeg($jpeg)) {
            return;
        }

        Storage::disk('public')->put($this->groupImageThumbRelativePath($folderKey), $jpeg);
    }

    private function deleteGroupImageThumb(string $folderKey): void
    {
        $path = $this->groupImageThumbRelativePath($folderKey);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function buildWatermarkedGroupJpeg(string $imageContent, string $variant): string
    {
        $img = null;
        $watermark = null;

        try {
            $img = Image::make($imageContent);

            if ($variant === 'thumb') {
                $img->resize(self::GROUP_IMAGE_THUMB_MAX_WIDTH, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $watermarkUrl = public_path('proofing-assets/img/msp_w_ios.png');
            if (file_exists($watermarkUrl)) {
                $watermark = Image::make($watermarkUrl);
                $watermark->resize($img->width(), $img->height());
                $img->insert($watermark, 'top-left', 0, 0);
            }

            return (string) $img->encode('jpg', $variant === 'thumb' ? 80 : 85);
        } finally {
            if ($img instanceof \Intervention\Image\Image) {
                $img->destroy();
            }
            if ($watermark instanceof \Intervention\Image\Image) {
                $watermark->destroy();
            }
        }
    }

    public function groupImageUpload(Request $request)
    {
        try {
            $file = $this->resolveImageUploadFile($request);
            if (!$file) {
                return response()->json(['message' => 'Please select an image to upload.'], 422);
            }

            $request->validate([
                'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
            ]);

            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?: ('upload_' . time() . '.jpg');

            $file->storeAs($request->input('upload_session'), $filename, 'public');

            if (Session::get('upload_session') !== $request->input('upload_session')) {
                Session::put('upload_session', $request->input('upload_session'));
            }

            return response()->json(['message' => 'Image uploaded successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed bulk group image upload', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Upload failed.',
            ], 422);
        }
    }

    public function groupImageDelete(Request $request)
    {
        $request->validate([
            'upload_session' => 'required|string|regex:/^[a-zA-Z0-9]+$/',
        ]);

        Session::pull('upload_session'); 
        $uploadSession = $request->input('upload_session');
    
        $this->cleanupBulkUploadSession($uploadSession);
    
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
                        $localPath = Storage::disk('public')->path($artifact);
                        $localBytes = @file_get_contents($localPath);
                        if ($localBytes === false) {
                            Log::error("Unable to read group image for validation: {$artifact}");
                            continue;
                        }

                        try {
                            $this->validateGroupImageBytes($localBytes);
                        } catch (\InvalidArgumentException $e) {
                            Log::warning('Rejected bulk group image upload', [
                                'artifact' => $artifact,
                                'folderKey' => $folderKey,
                                'error' => $e->getMessage(),
                            ]);
                            Storage::disk('public')->delete($artifact);
                            continue;
                        }

                        // Stream each file — do not load all image bodies into memory at once
                        $stream = Storage::disk('public')->readStream($artifact);
                        if ($stream === false) {
                            Log::error("Unable to open group image stream: {$artifact}");
                            continue;
                        }

                        try {
                            $uploader->upload($stream, $remotePath, $fileName);

                            if (!$this->verifyRemoteGroupImage($remotePath)) {
                                Log::error("Remote verification failed for bulk group image: {$remotePath}");
                                continue;
                            }

                            $this->clearGroupImageCaches($folderKey);
                            $this->imageService->createGroupImage($folderKey, $path, $fileName);
                            $this->clearGroupImageCaches($folderKey);
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
        
        $this->cleanupBulkUploadSession($folderPath);
    
        if ($request->has('jobHash')) {
            return redirect()->to(URL::signedRoute('proofing.dashboard', ['hash' => $request->input('jobHash')]));
        }
        return redirect()->route('proofing');
    }

    public function groupImageUploadFile(Request $request)
    {
        try {
            $file = $this->resolveImageUploadFile($request);
            if (!$file) {
                return response()->json(['message' => 'Please select an image to upload.'], 422);
            }

            $validator = Validator::make($request->all(), [
                'folder_key' => 'required|string',
                'folder_name' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!$file->isValid()) {
                return response()->json(['message' => $file->getErrorMessage()], 422);
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

            $uploadBytes = @file_get_contents($file->getRealPath());
            if ($uploadBytes === false) {
                return response()->json(['message' => 'Unable to read the uploaded file.'], 500);
            }

            try {
                $this->validateGroupImageBytes($uploadBytes);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

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

            if (!$this->verifyRemoteGroupImage($remotePath)) {
                return response()->json([
                    'message' => 'Upload could not be verified. Please try again.',
                ], 502);
            }

            $this->clearGroupImageCaches($folderKey);
            $this->imageService->createGroupImage($folderKey, $path, $fileName);
            $this->clearGroupImageCaches($folderKey);
            $this->storeGroupImageThumb($folderKey, $this->buildWatermarkedGroupJpeg($uploadBytes, 'thumb'));

            $encryptedFilename = Crypt::encryptString($fileName);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'full_url' => route('image.show', ['filename' => $encryptedFilename]),
                'thumb_url' => route('image.show', ['filename' => $encryptedFilename, 'variant' => 'thumb']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
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

    /**
     * Accept either a multipart file or a JSON base64 payload.
     * Base64 avoids Cloudflare WAF false-positives on some camera JPEG multipart bodies.
     */
    private function resolveImageUploadFile(Request $request): ?\Illuminate\Http\UploadedFile
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|file|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png,image/jpg|max:15360',
            ], [
                'file.mimes' => 'Only JPG and PNG images are allowed.',
                'file.mimetypes' => 'Only JPG and PNG images are allowed.',
                'file.max' => 'Each image must be 15MB or smaller.',
            ]);

            return $request->file('file');
        }

        $base64 = $request->input('file_base64');
        if (!$base64 || !is_string($base64)) {
            return null;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
            $extFromDataUrl = strtolower($matches[1]);
            if ($extFromDataUrl === 'jpeg') {
                $extFromDataUrl = 'jpg';
            }
        } else {
            $extFromDataUrl = null;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            throw new \InvalidArgumentException('Invalid image data.');
        }

        $maxBytes = 15360 * 1024;
        if (strlen($binary) > $maxBytes) {
            throw new \InvalidArgumentException('Each image must be 15MB or smaller.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mspimg');
        if ($tmpPath === false || file_put_contents($tmpPath, $binary) === false) {
            throw new \RuntimeException('Unable to store uploaded image.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($allowed[$mime])) {
            @unlink($tmpPath);
            throw new \InvalidArgumentException('Only JPG and PNG images are allowed.');
        }

        $extension = $extFromDataUrl && in_array($extFromDataUrl, ['jpg', 'jpeg', 'png'], true)
            ? ($extFromDataUrl === 'jpeg' ? 'jpg' : $extFromDataUrl)
            : $allowed[$mime];

        $originalName = $request->input('filename') ?: ('upload.' . $extension);
        $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $extension;

        return new \Illuminate\Http\UploadedFile(
            $tmpPath,
            $originalName,
            $mime,
            UPLOAD_ERR_OK,
            true
        );
    }

    public function groupImageDeleteFile(Request $request)
    {
        $folderKey = $request->input('folder_key');
        
        $folder = Folder::with(['job.seasons'])->where('ts_folderkey', $folderKey)->first();
        if ($folder && $folder->job) {
            $imageRecord = \App\Models\Image::where('keyvalue', $folderKey)->first();
        }

        $fileName = $this->imageService->deleteGroupImage($folderKey);

        $this->clearGroupImageCaches($folderKey);

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

    private function groupImageOutputCacheKey(string $folderKey, $image = null, string $variant = 'full'): string
    {
        $version = '0';
        if ($image && $image->updated_at) {
            $version = (string) strtotime($image->updated_at);
        }

        return "group_img_out_{$variant}_{$folderKey}_{$version}";
    }

    private function groupImageSourceCacheKey(string $folderKey, $image = null): string
    {
        $version = '0';
        if ($image && $image->updated_at) {
            $version = (string) strtotime($image->updated_at);
        }

        return "group_img_src_{$folderKey}_{$version}";
    }

    private function clearGroupImageCaches(string $folderKey, $image = null): void
    {
        $this->deleteGroupImageThumb($folderKey);
        Cache::store('file')->forget("group_img_out_{$folderKey}");
        Cache::forget("zoom_meta_v2_{$folderKey}");
        Cache::forget("group_img_meta_{$folderKey}");

        if (!$image) {
            $image = $this->imageService->getImagesByFolderKey($folderKey)->first();
        }

        if ($image) {
            $version = $image->updated_at ? (string) strtotime($image->updated_at) : 'v1';
            Cache::store('file')->forget($this->groupImageOutputCacheKey($folderKey, $image, 'full'));
            Cache::store('file')->forget($this->groupImageOutputCacheKey($folderKey, $image, 'thumb'));
            Cache::store('file')->forget($this->groupImageSourceCacheKey($folderKey, $image));
            Cache::store('file')->forget("zoom_bin_v3_{$folderKey}_{$version}");
            Cache::store('file')->forget("zoom_bin_{$folderKey}_{$version}");
            Cache::store('file')->forget("zoom_bin_{$folderKey}_v1");
            Cache::store('file')->forget("zoom_bin_{$folderKey}_0");
            Cache::store('file')->forget("zoom_bin_v3_{$folderKey}_v1");
            Cache::store('file')->forget("zoom_bin_v3_{$folderKey}_0");
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateGroupImageBytes(string $bytes): void
    {
        if (strlen($bytes) < self::GROUP_IMAGE_MIN_BYTES) {
            throw new \InvalidArgumentException('Image file is too small or corrupt.');
        }

        $info = @getimagesizefromstring($bytes);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            throw new \InvalidArgumentException('Unable to read image. Please try a different file.');
        }

        if ($info[0] < self::GROUP_IMAGE_MIN_DIMENSION || $info[1] < self::GROUP_IMAGE_MIN_DIMENSION) {
            throw new \InvalidArgumentException('Image dimensions are too small.');
        }

        if ($this->isMostlyBlackImage($bytes)) {
            throw new \InvalidArgumentException('Image appears blank or corrupt. Please try uploading again.');
        }
    }

    private function isValidGroupImageSource(?string $bytes): bool
    {
        if ($bytes === null || $bytes === '') {
            return false;
        }

        try {
            $this->validateGroupImageBytes($bytes);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    private function isValidCachedGroupJpeg(?string $jpeg, int $sourceBytes = 0): bool
    {
        if ($jpeg === null || $jpeg === '') {
            return false;
        }

        if (strlen($jpeg) < self::GROUP_IMAGE_MIN_JPEG_BYTES) {
            return false;
        }

        if (strncmp($jpeg, "\xFF\xD8\xFF", 3) !== 0) {
            return false;
        }

        if ($sourceBytes > 0 && strlen($jpeg) < min(1024, (int) ($sourceBytes * 0.01))) {
            return false;
        }

        return true;
    }

    private function isMostlyBlackImage(string $bytes): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        $img = @imagecreatefromstring($bytes);
        if ($img === false) {
            return true;
        }

        $width = imagesx($img);
        $height = imagesy($img);
        if ($width < 1 || $height < 1) {
            imagedestroy($img);
            return true;
        }

        $points = [
            [intval($width / 2), intval($height / 2)],
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1],
        ];

        $darkSamples = 0;
        foreach ($points as [$x, $y]) {
            $rgb = imagecolorat($img, $x, $y);
            $red = ($rgb >> 16) & 0xFF;
            $green = ($rgb >> 8) & 0xFF;
            $blue = $rgb & 0xFF;

            if ($red <= 12 && $green <= 12 && $blue <= 12) {
                $darkSamples++;
            }
        }

        imagedestroy($img);

        return $darkSamples === count($points);
    }

    private function verifyRemoteGroupImage(string $remotePath): bool
    {
        $url = rtrim(config('services.exportImageLocation'), '/') . '/' . ltrim($remotePath, '/');
        $response = Http::timeout(15)->withoutVerifying()->get($url);

        if (!$response->successful()) {
            Log::warning("Remote group image verification failed: {$url}", [
                'status' => $response->status(),
            ]);
            return false;
        }

        try {
            $this->validateGroupImageBytes($response->body());
            return true;
        } catch (\InvalidArgumentException $e) {
            Log::warning("Remote group image failed validation: {$url}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
