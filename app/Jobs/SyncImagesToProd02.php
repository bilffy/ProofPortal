<?php

namespace App\Jobs;

use App\Services\Proofing\ExportImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SyncImagesToProd02 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobKey;

    // Give the job plenty of time to run (5 minutes)
    public $timeout = 300;

    public function __construct($jobKey = null)
    {
        $this->jobKey = $jobKey;
    }

    public function handle(ExportImageService $exportImageService)
    {
        // Try to get a lock for this specific job key for 5 minutes.
        $lock = Cache::lock('sync_images_prod_' . $this->jobKey, 300);

        if ($lock->get()) {
            try {
                \Log::info('started SyncImagesToProd02 for ' . $this->jobKey);
                $exportImageService->getAllUnsyncJobsImages($this->jobKey);
            } finally {
                // Ensure the lock is released when the processing is done
                $lock->release();
            }
        } else {
            \Log::info('Skipped SyncImagesToProd02: Already running for ' . $this->jobKey);
        }
    }
}
