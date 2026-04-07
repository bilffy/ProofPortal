<?php

namespace App\Services;
use App\Services\StatusService;
use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $query = $this->getAllUnsyncImagesQuery($jobkey);
        
        // 1. We only want to process 100 images in this execution
        $totalCount = $query->count();
        if ($totalCount === 0) {
            return ['message' => 'Nothing to sync'];
        }

        DB::connection('timestone')->disableQueryLog();
        DB::disableQueryLog();

        try {
            // 2. Fetch 100 total, but process them in internal chunks of 35
            $query->limit(100)->chunkById(100, function ($images) {
                $imageKeys = $images->pluck('ts_imagekey')->filter()->unique()->toArray();
                
                if (!empty($imageKeys)) {
                    // Pass the new chunk size (35) to the next function
                    $this->saveArtifactByImageKeyUsingMassInjection($imageKeys, 35);
                }
                
                return false; // Exit chunk after the first 100 are handled
            }, 'ts_image_id');

            return ['success' => true, 'processed' => 100];
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

    public function saveArtifactByImageKeyUsingMassInjection(array $imageKeys = [], $internalChunk = 35)
    {
        if (empty($imageKeys)) return false;

        $query = $this->findImageMatchesByImageKeys($imageKeys);
        
        // Increase time limit for the 100 files + sleep overhead
        set_time_limit(300); 

        // 3. Process in chunks of 35 as requested
        $query->chunk($internalChunk, function ($images) {
            $successfullyUploadedKeys = [];
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);

            foreach ($images as $image) {
                if (!$image->Thumbnail) continue;
            
                $mimeType = $fileInfo->buffer($image->Thumbnail);
                $extension = $this->getExtensionFromMimeType($mimeType);

                // Pathing using the 6-character SubjectKey logic
                $sKey = (string)$image->SubjectKey;
                $fullPath = sprintf(
                    '%s/%s/%s/%s/%s/%s.%s',
                    $image->Code,
                    $image->SchoolKey,
                    $image->JobKey,
                    $sKey[0], // First Char
                    $sKey[1], // Second Char
                    $sKey,    // SubjectKey
                    $extension
                );
            
                try {
                    $result = Storage::disk('sftp')->put($fullPath, $image->Thumbnail);
                    
                    if ($result) {
                        $successfullyUploadedKeys[] = $image->ImageKey;
                        
                        // 4. Add the 200ms delay to throttle the injection
                        usleep(200000); 
                    }
                } catch (Exception $e) {
                    Log::error("SFTP Error: " . $e->getMessage());
                }
            }

            // Update database for this chunk of 35
            if (!empty($successfullyUploadedKeys)) {
                Image::whereIn('ts_imagekey', $successfullyUploadedKeys)->update(['exportStatus' => 1]);
            }
            
            unset($images);
        });

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