<?php

namespace App\Jobs;

use App\Services\Proofing\ExportImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SyncImagesToProd02 implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobKey;

    // Give the job plenty of time to run (5 minutes)
    public $timeout = 300;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return $this->jobKey;
    }

    public function __construct($jobKey = null)
    {
        $this->jobKey = $jobKey;
    }

    public function handle(ExportImageService $exportImageService)
    {\Log::info('started');
        // This calls your 100/35/200ms logic we wrote earlier
        $exportImageService->getAllUnsyncJobsImages($this->jobKey);
    }
}