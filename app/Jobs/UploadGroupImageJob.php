<?php

namespace App\Jobs;

use App\Services\Proofing\ImageUploader;
use App\Services\Proofing\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadGroupImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        protected string $localFilePath,
        protected string $remotePath,
        protected string $fileName,
        protected string $storagePath,
        protected string $folderKey,
    ) {}

    public function handle(ImageService $imageService): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($this->localFilePath)) {
            Log::warning("UploadGroupImageJob: local file missing: {$this->localFilePath}");
            return;
        }

        $stream = fopen($disk->path($this->localFilePath), 'r');

        try {
            $uploader = new ImageUploader();
            $uploader->upload($stream, $this->remotePath, $this->fileName);
            $imageService->createGroupImage($this->folderKey, $this->storagePath, $this->fileName);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            // Clean up the temp file after upload
            $disk->delete($this->localFilePath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("UploadGroupImageJob failed for folder {$this->folderKey}: " . $exception->getMessage());
    }
}
