<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Services\Proofing\StatusService; // Fixed namespace mapping
use App\Jobs\SyncImagesToProd02;
use Illuminate\Support\Carbon; // Cleaned up Carbon instantiation compatibility

class SyncImagesScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:sync-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches synchronization jobs for unsynced assets';

    /**
     * Execute the console command.
     */
    public function handle(StatusService $statusService)
    {
        // Chunk optimization prevents heavy memory usage and long open db lock constraints
        Job::where('imagesync_status_id', $statusService->unsync)
            ->where('show_proofing', 1)
            ->whereHas('images', function ($query) {
                $query->where('exportStatus', 0)
                      ->where('keyorigin', 'Subject');
            })
            ->chunkById(50, function ($jobs) {
                foreach ($jobs as $job) {
                    SyncImagesToProd02::dispatch($job->ts_jobkey);
                }
            });
    }
}