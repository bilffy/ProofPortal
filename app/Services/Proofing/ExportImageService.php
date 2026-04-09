<?php

namespace App\Services\Proofing;
use App\Services\Proofing\StatusService;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SyncImagesToSftp;
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
        // Disable logs to save RAM
        DB::connection('timestone')->disableQueryLog();
        DB::disableQueryLog();

        $query = $this->getAllUnsyncImagesQuery($jobkey);
        
        // 1. Grab exactly 100 image keys to work on
        $images = $query->limit(100)->get();

        if ($images->isEmpty()) {
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
                dispatch(new SyncImagesToSftp($jobkey)); 
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
        
        $successfullyUploadedKeys = [];
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($images as $image) {
            if (!$image->Thumbnail) continue;
                
            $mimeType = $fileInfo->buffer($image->Thumbnail);
            $extension = $this->getExtensionFromMimeType($mimeType);
            $sKey = (string)$image->SubjectKey;

            $fullPath = sprintf(
                '%s/%s/%s/%s/%s/%s.%s',
                $image->Code, $image->SchoolKey, $image->JobKey,
                $sKey[0], $sKey[1], $sKey, $extension
            );
                
            try {
                $result = Storage::disk('sftp')->put($fullPath, $image->Thumbnail);
                
                if ($result) {
                    $successfullyUploadedKeys[] = $image->ImageKey;
                    
                    // Update DB every 10 images to show progress and free RAM
                    if (count($successfullyUploadedKeys) >= 10) {
                        Image::whereIn('ts_imagekey', $successfullyUploadedKeys)->update(['exportStatus' => 1]);
                        $successfullyUploadedKeys = []; 
                        gc_collect_cycles(); // Force PHP to empty the "trash"
                    }
                    usleep(200000); // 200ms breath for the SFTP server
                }
            } catch (Exception $e) {
                Log::error("SFTP Error: " . $e->getMessage());
            }
            
            unset($image); // Clear the binary data from the variable
        }

        // Final update for any remaining keys (the last 1-9 images)
        if (!empty($successfullyUploadedKeys)) {
            Image::whereIn('ts_imagekey', $successfullyUploadedKeys)->update(['exportStatus' => 1]);
        }

        return true;
    }

    public function findImageMatchesByImageKeys(
        $imageKeys = null
    ) {

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