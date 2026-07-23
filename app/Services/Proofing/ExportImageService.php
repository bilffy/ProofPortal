<?php

namespace App\Services\Proofing;

use App\Jobs\SyncImagesToProd02;
use App\Models\Image;
use App\Models\Job;
use Exception;
use finfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportImageService
{
    public const EXPORT_PENDING = 0;
    public const EXPORT_SYNCED = 1;
    public const EXPORT_DOWNLOADED = 2;
    public const EXPORT_THUMBNAIL_MISSING = -1;
    public const EXPORT_UPLOAD_FAILED = -3;

    /** @deprecated Use EXPORT_THUMBNAIL_MISSING */
    public const EXPORT_FAILED = -1;

    protected StatusService $statusService;

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Phase 1: download Timestone thumbnails to local disk in batches of 100 (exportStatus → 2).
     * Phase 2: bulk-upload via ImageUploader (exportStatus → 1, -3 on upload failure), then delete local staging.
     */
    public function getAllUnsyncJobsImages($jobkey)
    {
        Log::info('Image sync started', ['jobkey' => $jobkey]);

        DB::connection('timestone')->disableQueryLog();
        DB::disableQueryLog();

        $job = Job::with('seasons')->where('ts_jobkey', $jobkey)->first();
        if (!$job) {
            Log::warning('Image sync aborted: job not found', ['jobkey' => $jobkey]);

            return ['message' => 'Job not found'];
        }

        try {
            $pendingDownloads = $this->getAllUnsyncImagesQuery($jobkey)->count();

            if ($pendingDownloads > 0) {
                $images = $this->getAllUnsyncImagesQuery($jobkey)->limit(100)->get();
                $imageKeys = $images->pluck('ts_imagekey')->toArray();

                $downloadSummary = $this->downloadBatchToLocal($job, $imageKeys);
                $remainingDownloads = $this->getAllUnsyncImagesQuery($jobkey)->count();

                Log::info('Image sync download batch finished', array_merge(
                    ['jobkey' => $jobkey, 'remaining_pending' => $remainingDownloads],
                    $downloadSummary
                ));

                if ($remainingDownloads > 0) {
                    dispatch((new SyncImagesToProd02($jobkey))->delay(now()->addSeconds(3)));

                    return [
                        'success' => true,
                        'phase' => 'download',
                        'processed' => count($imageKeys),
                        'summary' => $downloadSummary,
                    ];
                }
            }

            return $this->finishJobSync($job, $jobkey);
        } catch (Exception $e) {
            Log::error('Image sync error', ['jobkey' => $jobkey, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getAllUnsyncImagesQuery($jobkey)
    {
        return Image::query()
            ->join('jobs', 'images.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('images.exportStatus', self::EXPORT_PENDING)
            ->where('images.keyorigin', 'Subject')
            ->where('jobs.imagesync_status_id', $this->statusService->unsync)
            ->when($jobkey, fn ($query) => $query->where('jobs.ts_jobkey', $jobkey))
            ->select([
                'images.ts_image_id',
                'images.ts_imagekey',
                'images.keyvalue',
            ]);
    }

    public function getPendingUploadImagesQuery(string $jobkey)
    {
        return Image::query()
            ->join('jobs', 'images.ts_job_id', '=', 'jobs.ts_job_id')
            ->where('images.exportStatus', self::EXPORT_DOWNLOADED)
            ->where('images.keyorigin', 'Subject')
            ->where('jobs.ts_jobkey', $jobkey)
            ->select([
                'images.ts_image_id',
                'images.ts_imagekey',
                'images.keyvalue',
                'images.name',
            ]);
    }

    /**
     * Fetch thumbnails from Timestone, save to local disk, update DB in chunks of 10.
     * exportStatus = 2 when downloaded, -1 when thumbnail missing / not in Timestone.
     *
     * @return array{downloaded: int, failed: int, missing: int}
     */
    public function downloadBatchToLocal(Job $job, array $imageKeys, int $chunkSize = 10): array
    {
        $summary = ['downloaded' => 0, 'failed' => 0, 'missing' => 0];
        if (empty($imageKeys)) return $summary;
    
        $this->ensureStagingRootExists();
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $downloadedBatch = [];
    
        // 1. Get the list of images without the heavy blobs
        foreach ($this->findImageMatchesByImageKeys($imageKeys)->cursor() as $row) {
            // 2. Fetch the BLOB specifically for this ID
            $thumbnail = DB::connection('timestone')
                ->table('ImageBitmaps')
                ->where('ImageID', $row->ImageID)
                ->value('Thumbnail');
    
            $thumbnail = $this->normalizeThumbnail($thumbnail);
    
            if ($thumbnail === null) {
                $summary['failed']++;
                continue;
            }
    
            // 3. Process the file
            try {
                $extension = $this->getExtensionFromMimeType($fileInfo->buffer($thumbnail));
                $subjectKey = (string) $row->SubjectKey;
                $localPath = sprintf('%s/%s.%s', $this->localDir($job, $subjectKey), $subjectKey, $extension);
    
                $this->writeStagingFile($localPath, $thumbnail);
    
                [$p1, $p2, $p3] = $this->buildPartition($subjectKey);
                // Cast key to string: PHP converts numeric-looking keys to int, which
                // PDO then binds as numbers and breaks alphanumeric ts_imagekey compares.
                $downloadedBatch[(string) $row->ImageKey] = [
                    'path' => sprintf('%s/%s/%s/', $p3, $p1, $p2),
                    'name' => sprintf('%s.%s', $subjectKey, $extension),
                ];
    
                if (count($downloadedBatch) >= $chunkSize) {
                    $summary['downloaded'] += count($downloadedBatch);
                    $this->updateBatchDownloadStatuses($downloadedBatch);
                    $downloadedBatch = [];
                    // Clear memory after each batch
                    gc_collect_cycles();
                }
            } catch (Exception $e) {
                Log::error('Download failed for key ' . $row->ImageKey, ['error' => $e->getMessage()]);
            }
        }
    
        if (!empty($downloadedBatch)) {
            $summary['downloaded'] += count($downloadedBatch);
            $this->updateBatchDownloadStatuses($downloadedBatch);
        }
    
        return $summary;
    }

    protected function finishJobSync(Job $job, string $jobkey): array
    {
        $this->reconcileMissingLocalFiles($job, $jobkey);

        // Increased default chunk size from 10 to 50 for optimized HTTP batch performance
        $uploadSummary = $this->uploadLocalImagesToProd($job, $jobkey, 50);
        $pendingUploads = $this->countPendingUploads($job);

        Log::info('Image sync upload batch finished', array_merge(
            ['jobkey' => $jobkey, 'pending_uploads' => $pendingUploads],
            $uploadSummary
        ));

        if ($pendingUploads > 0) {
            dispatch((new SyncImagesToProd02($jobkey))->delay(now()->addMinutes(5)));

            return [
                'success' => false,
                'phase' => 'upload',
                'pending_uploads' => $pendingUploads,
                'summary' => $uploadSummary,
            ];
        }

        if (!$this->canMarkJobSynced($job)) {
            $outstanding = $this->exportStatusCounts($job);
            Log::warning('Image sync incomplete: outstanding export work remains', [
                'jobkey' => $jobkey,
                'status_counts' => $outstanding,
            ]);

            dispatch((new SyncImagesToProd02($jobkey))->delay(now()->addMinutes(5)));

            return [
                'success' => false,
                'phase' => 'incomplete',
                'status_counts' => $outstanding,
            ];
        }

        $this->cleanupLocalStaging($jobkey);

        Job::where('ts_jobkey', $jobkey)->update(['imagesync_status_id' => $this->statusService->sync]);
        Log::info('Image sync complete', [
            'jobkey' => $jobkey,
            'status_counts' => $this->exportStatusCounts($job),
        ]);

        return ['message' => 'Sync Complete', 'phase' => 'complete', 'summary' => $uploadSummary];
    }

    /**
     * Processes locally cached images and aggregates them into multi-file batches for dispatching.
     * * @return array{uploaded: int, failed: int, orphaned: int}
     */
    protected function uploadLocalImagesToProd(Job $job, string $jobkey, int $chunkSize = 50): array
    {
        $summary = ['uploaded' => 0, 'failed' => 0, 'orphaned' => 0];
        $localDir = $this->localStagingDir($jobkey);

        if (!$this->staging()->exists($localDir)) {
            return $summary;
        }

        $files = $this->staging()->allFiles($localDir);
        
        // Temporary placeholders for buffering batches
        $pendingBatchFiles = []; 
        $pendingBatchKeys = [];  
        $pendingBatchData = [];

        foreach ($files as $file) {
            $subjectKey = pathinfo($file, PATHINFO_FILENAME);
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            $image = Image::where('ts_job_id', $job->ts_job_id)
                ->where('keyorigin', 'Subject')
                ->where('keyvalue', $subjectKey)
                ->where('exportStatus', self::EXPORT_DOWNLOADED)
                ->first();

            if (!$image) {
                $this->staging()->delete($file);
                $summary['orphaned']++;
                Log::warning('Removed orphaned local file during upload', [
                    'jobkey' => $jobkey,
                    'file' => $file,
                ]);
                continue;
            }

            [$p1, $p2, $p3] = $this->buildPartition($subjectKey);
            $seasonCode = $job->seasons?->code ?? '';
            $remotePath = ltrim(sprintf(
                '%s/%s/%s/subjects/%s/%s/%s/%s.%s',
                $seasonCode,
                $job->ts_schoolkey,
                $job->ts_jobkey,
                $p3,
                $p1,
                $p2,
                $subjectKey,
                $extension
            ), '/');
            $filename = sprintf('%s.%s', $subjectKey, $extension);

            // Append item attributes into the pending batch instead of immediately firing an HTTP connection
            $pendingBatchFiles[] = $file;
            $pendingBatchKeys[] = $image->ts_imagekey;
            $pendingBatchData[] = [
                'filename' => $filename,
                'remote_path' => $remotePath
            ];

            // Trigger payload transmission once buffer threshold hits the targeted chunk size
            if (count($pendingBatchFiles) >= $chunkSize) {
                $this->processHttpBatchUpload($pendingBatchFiles, $pendingBatchKeys, $pendingBatchData, $summary);
                $pendingBatchFiles = [];
                $pendingBatchKeys = [];
                $pendingBatchData = [];
            }
        }

        // Catch and process remaining leftovers that didn't fully satisfy the strict chunk limits
        if (!empty($pendingBatchFiles)) {
            $this->processHttpBatchUpload($pendingBatchFiles, $pendingBatchKeys, $pendingBatchData, $summary);
        }

        if ($summary['failed'] === 0) {
            $this->cleanupLocalStaging($jobkey);
        }

        return $summary;
    }

    /**
     * Executes the network delivery mapping payloads into ImageUploader->uploadBatch().
     */
    private function processHttpBatchUpload(array $files, array $imageKeys, array $metaData, array &$summary): void
    {
        try {
            $uploader = new ImageUploader();
            $filesPayload = [];

            // Compile unified structure conforming to ImageUploader requirements
            foreach ($files as $index => $file) {
                $filesPayload[] = [
                    'content'     => $this->staging()->get($file),
                    'filename'    => $metaData[$index]['filename'],
                    'remote_path' => $metaData[$index]['remote_path']
                ];
            }

            // Push the batch data to the Core PHP API over a single request block
            $uploader->uploadBatch($filesPayload);

            // Update local tracking metrics and clear out transient local disk contents on verified success
            $summary['uploaded'] += $this->updateBatchUploadStatuses($imageKeys);

            foreach ($files as $file) {
                $this->staging()->delete($file);
            }

        } catch (Exception $e) {
            $summary['failed'] += $this->markUploadFailed($imageKeys, 'upload_batch_failed');
            Log::error('Batch image upload failed', [
                'image_keys' => $imageKeys,
                'export_status' => self::EXPORT_UPLOAD_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark exportStatus=2 rows as failed when their local file is missing.
     */
    protected function reconcileMissingLocalFiles(Job $job, string $jobkey): void
    {
        foreach ($this->getPendingUploadImagesQuery($jobkey)->cursor() as $image) {
            if ($this->localFileExists($job, (string) $image->keyvalue, $image->name)) {
                continue;
            }

            $this->markUploadFailed([$image->ts_imagekey], 'local_file_missing');
            Log::error('Downloaded image missing local file', [
                'jobkey' => $jobkey,
                'image_key' => $image->ts_imagekey,
                'subject_key' => $image->keyvalue,
                'expected_name' => $image->name,
            ]);
        }
    }

    protected function updateBatchDownloadStatuses(array $batchData): void
    {
        if (empty($batchData)) {
            return;
        }

        $pathCases = [];
        $nameCases = [];
        $bindings = [];
        $keys = [];

        // Always bind ts_imagekey as string. PHP casts numeric-looking array keys
        // (e.g. "52278852") to int; PDO then binds them as numbers and MySQL coerces
        // the whole IN/CASE compare to DOUBLE, which fails for keys like "8YG2LMQC".
        foreach ($batchData as $imageKey => $data) {
            $keys[] = (string) $imageKey;
        }

        foreach ($batchData as $imageKey => $data) {
            $pathCases[] = 'WHEN ts_imagekey = ? THEN ?';
            $bindings[] = (string) $imageKey;
            $bindings[] = (string) ($data['path'] ?? '');
        }

        foreach ($batchData as $imageKey => $data) {
            $nameCases[] = 'WHEN ts_imagekey = ? THEN ?';
            $bindings[] = (string) $imageKey;
            $bindings[] = (string) ($data['name'] ?? '');
        }

        foreach ($keys as $key) {
            $bindings[] = $key;
        }

        $pathCasesSql = implode(' ', $pathCases);
        $nameCasesSql = implode(' ', $nameCases);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        DB::statement("
            UPDATE images
            SET exportStatus = ?,
                image_path = CASE {$pathCasesSql} ELSE image_path END,
                name = CASE {$nameCasesSql} ELSE name END
            WHERE ts_imagekey IN ({$placeholders})
        ", array_merge([self::EXPORT_DOWNLOADED], $bindings));
    }

    protected function updateBatchUploadStatuses(array $imageKeys): int
    {
        if (empty($imageKeys)) {
            return 0;
        }

        $updated = Image::whereIn('ts_imagekey', $imageKeys)
            ->where('exportStatus', self::EXPORT_DOWNLOADED)
            ->update(['exportStatus' => self::EXPORT_SYNCED]);

        Log::info('Marked images as uploaded', [
            'count' => $updated,
            'export_status' => self::EXPORT_SYNCED,
        ]);

        return $updated;
    }

    protected function flushThumbnailMissingBatch(array &$failedBatch, int $chunkSize, bool $force = false): int
    {
        if (empty($failedBatch)) {
            return 0;
        }

        if (!$force && count($failedBatch) < $chunkSize) {
            return 0;
        }

        $count = $this->markThumbnailMissing($failedBatch, 'thumbnail_missing');
        $failedBatch = [];

        return $count;
    }

    protected function markThumbnailMissing(array $imageKeys, string $reason = 'unknown'): int
    {
        if (empty($imageKeys)) {
            return 0;
        }

        $updated = Image::whereIn('ts_imagekey', $imageKeys)
            ->where('exportStatus', self::EXPORT_PENDING)
            ->update(['exportStatus' => self::EXPORT_THUMBNAIL_MISSING]);

        Log::warning('Marked images as thumbnail missing', [
            'count' => $updated,
            'reason' => $reason,
            'export_status' => self::EXPORT_THUMBNAIL_MISSING,
            'image_keys' => $imageKeys,
        ]);

        return $updated;
    }

    protected function markUploadFailed(array $imageKeys, string $reason = 'unknown'): int
    {
        if (empty($imageKeys)) {
            return 0;
        }

        $updated = Image::whereIn('ts_imagekey', $imageKeys)
            ->where('exportStatus', self::EXPORT_DOWNLOADED)
            ->update(['exportStatus' => self::EXPORT_UPLOAD_FAILED]);

        Log::warning('Marked images as upload failed', [
            'count' => $updated,
            'reason' => $reason,
            'export_status' => self::EXPORT_UPLOAD_FAILED,
            'image_keys' => $imageKeys,
        ]);

        return $updated;
    }

    protected function countPendingUploads(Job $job): int
    {
        return Image::query()
            ->where('ts_job_id', $job->ts_job_id)
            ->where('keyorigin', 'Subject')
            ->where('exportStatus', self::EXPORT_DOWNLOADED)
            ->count();
    }

    protected function canMarkJobSynced(Job $job): bool
    {
        return !Image::query()
            ->where('ts_job_id', $job->ts_job_id)
            ->where('keyorigin', 'Subject')
            ->whereIn('exportStatus', [self::EXPORT_PENDING, self::EXPORT_DOWNLOADED])
            ->exists();
    }

    protected function exportStatusCounts(Job $job): array
    {
        return Image::query()
            ->where('ts_job_id', $job->ts_job_id)
            ->where('keyorigin', 'Subject')
            ->selectRaw('exportStatus, COUNT(*) as total')
            ->groupBy('exportStatus')
            ->pluck('total', 'exportStatus')
            ->all();
    }

    protected function localFileExists(Job $job, string $subjectKey, ?string $name = null): bool
    {
        if ($name) {
            return $this->staging()->exists(sprintf('%s/%s', $this->localDir($job, $subjectKey), $name));
        }

        $dir = $this->localDir($job, $subjectKey);
        foreach (['jpg', 'jpeg', 'png'] as $extension) {
            if ($this->staging()->exists("{$dir}/{$subjectKey}.{$extension}")) {
                return true;
            }
        }

        return false;
    }

    protected function localStagingDir(string $jobkey): string
    {
        return $jobkey;
    }

    protected function cleanupLocalStaging(string $jobkey): void
    {
        $localDir = $this->localStagingDir($jobkey);

        if ($this->staging()->exists($localDir)) {
            $this->staging()->deleteDirectory($localDir);
            Log::info('Deleted local staging directory', ['jobkey' => $jobkey]);
        }
    }

    protected function localDir(Job $job, string $subjectKey): string
    {
        [$p1, $p2, $p3] = $this->buildPartition($subjectKey);

        return sprintf('%s/%s/%s/%s', $job->ts_jobkey, $p3, $p1, $p2);
    }

    protected function buildPartition(string $subjectKey): array
    {
        $hash = hash_hmac('sha256', 'subjects', $subjectKey);

        return [substr($hash, 0, 2), substr($hash, 2, 2), substr($hash, 4, 2)];
    }

    protected function stagingDisk(): string
    {
        $disk = config('services.proofing_cache_disk');

        return filled($disk) ? (string) $disk : 'proofing_cache';
    }

    protected function staging()
    {
        return Storage::disk($this->stagingDisk());
    }

    protected function ensureStagingRootExists(): void
    {
        $diskConfig = config('filesystems.disks.' . $this->stagingDisk());
        $absoluteRoot = $diskConfig['root'] ?? storage_path('app/proofing_cache');

        if (!is_dir($absoluteRoot) && !@mkdir($absoluteRoot, 0775, true) && !is_dir($absoluteRoot)) {
            throw new Exception("Cannot create proofing staging root: {$absoluteRoot}");
        }
    }

    protected function writeStagingFile(string $relativePath, string $contents): void
    {
        $directory = dirname($relativePath);

        if ($directory !== '.' && $directory !== '' && !$this->staging()->exists($directory)) {
            $this->staging()->makeDirectory($directory);
        }

        $this->staging()->put($relativePath, $contents);
    }

    protected function normalizeThumbnail(mixed $thumbnail): ?string
    {
        if ($thumbnail === null) {
            return null;
        }

        if (is_resource($thumbnail)) {
            $thumbnail = stream_get_contents($thumbnail);
        }

        if (!is_string($thumbnail) || $thumbnail === '') {
            return null;
        }

        return $thumbnail;
    }

    public function findImageMatchesByImageKeys($imageKeys = null)
    {
        // Fetch ONLY metadata. Do NOT join the ImageBitmaps table here.
        return DB::connection('timestone')
            ->table('Images')
            ->join('ImageMatches', 'ImageMatches.ImageID', '=', 'Images.ImageID')
            ->join('Subjects', 'ImageMatches.SubjectID', '=', 'Subjects.SubjectID')
            ->whereIn('Images.ImageKey', $imageKeys)
            ->select([
                'Subjects.SubjectKey',
                'Images.ImageKey',
                'Images.ImageID', // We only need the ID for the next step
            ])
            ->distinct()
            ->orderBy('Subjects.SubjectKey');
    }

    private function getExtensionFromMimeType($mimeType): string
    {
        $mimes = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
        ];

        return $mimes[$mimeType] ?? 'jpg';
    }
}