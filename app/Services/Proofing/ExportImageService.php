<?php

namespace App\Services\Proofing;

use App\Services\Proofing\StatusService;
use App\Models\Image;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Proofing\ImageUploader;
use App\Jobs\SyncImagesToProd02;
use Exception;
use finfo;

class ExportImageService
{
    protected $statusService;

    /**
     * Create a new class instance.
     */
    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /* Export Image */

    public function getAllUnsyncJobsImages($jobkey)
    {
        \Log::info($jobkey);
        // Disable logs to save RAM
        DB::connection('timestone')->disableQueryLog();
        DB::disableQueryLog();

        $query = $this->getAllUnsyncImagesQuery($jobkey);
        
        // 1. Grab exactly 100 image keys to work on
        $images = $query->limit(100)->get();

        if ($images->isEmpty()) {
            Job::where('ts_jobkey', $jobkey)->update(['imagesync_status_id' => $this->statusService->sync]);
            Log::info("Sync Complete for Job: $jobkey");
            return ['message' => 'Sync Complete'];
        }

        $imageKeys = $images->pluck('ts_imagekey')->toArray();

        try {
            // 2. Process this batch of 100
            $this->saveArtifactByImageKeyUsingMassInjection($imageKeys, 10);

            // 3. CHECK: Are there more images left?
            $remainingCount = $this->getAllUnsyncImagesQuery($jobkey)->count();

            if ($remainingCount > 0) {
                // This triggers the NEXT 100 automatically
                dispatch((new SyncImagesToProd02($jobkey))->delay(now()->addSeconds(3))); 
                Log::info("Dispatched next batch for $jobkey. Remaining: $remainingCount");
            }

            return ['success' => true, 'processed' => count($imageKeys)];

        } catch (Exception $e) {
            Log::error("Image Export Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the query builder for unsync images.
     */
    public function getAllUnsyncImagesQuery($jobkey)
    {
        return Image::query()
            ->join('jobs', 'images.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('images.exportStatus', 0)
            ->where('images.keyorigin', 'Subject')
            ->where('jobs.imagesync_status_id', $this->statusService->unsync)
            ->when($jobkey, function ($query) use ($jobkey) {
                return $query->where('jobs.ts_jobkey', $jobkey);
            })
            ->select([
                'images.ts_image_id',      // Required for chunkById
                'images.ts_imagekey',   // Required for syncing
            ]);
    }

    public function saveArtifactByImageKeyUsingMassInjection(array $imageKeys = [], $internalChunk = 10)
    {
        if (empty($imageKeys)) return false;
        
        // Use cursor to stream 1 image at a time from the DB
        $images = $this->findImageMatchesByImageKeys($imageKeys)->cursor();
        
        $batchData = []; // Multi-dimensional tracking array
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $uploader = new ImageUploader();

        foreach ($images as $image) {
            if (!$image->Thumbnail) continue;
                
            $mimeType = $fileInfo->buffer($image->Thumbnail);
            $extension = $this->getExtensionFromMimeType($mimeType);
            $prefix = config('services.proofing_cache_prefix');
            $sKey = (string)$image->SubjectKey;
            
            // Generate partitioning subdirectories
            $hash = hash_hmac('sha256', 'subjects', $sKey);
            $p1 = substr($hash, 0, 2);
            $p2 = substr($hash, 2, 2);
            $p3 = substr($hash, 4, 2);
            
            $remotePath = sprintf(
                '%s/%s/%s/%s/subjects/%s/%s/%s/%s.%s',
                $prefix, $image->Code, $image->SchoolKey, $image->JobKey,
                $p3, $p1, $p2, $sKey, $extension
            );
            $remotePath = ltrim($remotePath, '/');

            // Formulate the path and filename strings
            $databaseRelativePath = sprintf('%s/%s/%s/%s.%s', $p3, $p1, $p2, $sKey, $extension);
            $filename = sprintf('%s.%s', $sKey, $extension);
                
            try {
                $uploader->upload($image->Thumbnail, $remotePath, $filename);
                
                // Track both variables under the unique ImageKey identifier
                $batchData[$image->ImageKey] = [
                    'path' => $databaseRelativePath,
                    'name' => $filename,
                ];
                
                // Update DB every 10 images to show progress and free RAM
                if (count($batchData) >= 10) {
                    $this->updateBatchStatusesAndPaths($batchData);
                    $batchData = []; 
                    gc_collect_cycles(); // Force PHP memory garbage collector cycle
                }
            } catch (Exception $e) {
                Log::error("Image Upload Error for {$image->ImageKey}: " . $e->getMessage());
            }
            
            unset($image); // Clear binary buffer reference
        }

        // Final update for any remaining keys (the last 1-9 images)
        if (!empty($batchData)) {
            $this->updateBatchStatusesAndPaths($batchData);
        }

        return true;
    }

    /**
     * Executes an optimized raw SQL statement using independent CASE WHEN expressions
     * to simultaneously update multiple custom dynamic values per image row.
     */
    protected function updateBatchStatusesAndPaths(array $batchData)
    {
        $keys = array_keys($batchData);
        $pathCases = [];
        $nameCases = [];
        $bindings = [];

        // 1. Build the CASE statement fragment parameters for the image_path modifications
        foreach ($batchData as $imageKey => $data) {
            $pathCases[] = "WHEN ts_imagekey = ? THEN ?";
            $bindings[] = $imageKey;
            $bindings[] = $data['path'];
        }

        // 2. Build the CASE statement fragment parameters for the name modifications
        foreach ($batchData as $imageKey => $data) {
            $nameCases[] = "WHEN ts_imagekey = ? THEN ?";
            $bindings[] = $imageKey;
            $bindings[] = $data['name'];
        }

        $pathCasesSql = implode(' ', $pathCases);
        $nameCasesSql = implode(' ', $nameCases);
        
        // 3. Append final whereIn comparison parameters to validate selection safety
        foreach ($keys as $key) {
            $bindings[] = $key;
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        // Executes dual CASE evaluations within a single operational database statement transaction
        DB::statement("
            UPDATE images 
            SET exportStatus = 1, 
                image_path = CASE $pathCasesSql ELSE image_path END,
                name = CASE $nameCasesSql ELSE name END
            WHERE ts_imagekey IN ($placeholders)
        ", $bindings);
    }

    public function findImageMatchesByImageKeys($imageKeys = null) 
    {
        $query = DB::connection('timestone')
                ->table('ImageMatches')
                ->join('Images', 'ImageMatches.ImageID', '=', 'Images.ImageID')
                ->join('Subjects', 'ImageMatches.SubjectID', '=', 'Subjects.SubjectID')
                ->join('ImageBitmaps', 'ImageMatches.ImageID', '=', 'ImageBitmaps.ImageID')
                ->join('Jobs', 'Jobs.JobID', '=', 'Subjects.JobID')
                ->join('JobDetails', 'JobDetails.JobID', '=', 'Jobs.JobID')
                ->join('Seasons', 'Jobs.SeasonID', '=', 'Seasons.SeasonID')
                ->whereIn('Images.ImageKey', $imageKeys) // Filter by the keys we found
                ->select([
                    'Subjects.SubjectKey',
                    'Images.ImageKey',
                    'ImageBitmaps.Thumbnail',
                    'Jobs.JobKey',
                    'JobDetails.SchoolKey',
                    'Seasons.Code'
                ])->orderBy('Subjects.SubjectKey');

        return $query; 
    }

    private function getExtensionFromMimeType($mimeType)
    {
        $mimes = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
        ];
        return $mimes[$mimeType] ?? 'jpg';
    }
}