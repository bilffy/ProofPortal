<?php

namespace App\Jobs;

use App\Services\Proofing\ExportImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncImagesToProd02 implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobKey;

    public $timeout = 300;

    public $uniqueFor = 300;

    public function __construct($jobKey = null)
    {
        $this->jobKey = $jobKey;
    }

    public function uniqueId(): string
    {
        return (string) $this->jobKey;
    }

    public function handle(ExportImageService $exportImageService): void
    {
        $lock = Cache::lock('sync_images_prod_' . $this->jobKey, 300);

        if (!$lock->get()) {
            Log::info('Skipped SyncImagesToProd02: lock held', ['jobkey' => $this->jobKey]);

            return;
        }

        try {
            Log::info('Started SyncImagesToProd02', ['jobkey' => $this->jobKey]);

            $result = $exportImageService->getAllUnsyncJobsImages($this->jobKey);

            Log::info('Finished SyncImagesToProd02', [
                'jobkey' => $this->jobKey,
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            Log::error('SyncImagesToProd02 failed', [
                'jobkey' => $this->jobKey,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }
}
