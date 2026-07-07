<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupStagingImages extends Command
{
    protected $signature = 'images:cleanup-staging {--days=3 : Delete directories older than X days}';

    protected $description = 'Clean up failed or leftover local image staging directories from processing jobs';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $diskName = filled(config('services.proofing_cache_disk'))
            ? (string) config('services.proofing_cache_disk')
            : 'proofing_cache';
        $stagingRoot = config("filesystems.disks.{$diskName}.root", storage_path('app/proofing_cache'));
        $staging = Storage::disk($diskName);
        $cutoff = now()->subDays($days);

        $this->info("Starting local staging cleanup for directories older than {$days} days...");
        $this->line("Disk: {$diskName} ({$stagingRoot})");
        Log::info('Image staging cleanup command initiated', [
            'older_than_days' => $days,
            'disk' => $diskName,
            'root' => $stagingRoot,
        ]);

        $directories = $staging->directories();
        if (empty($directories)) {
            $this->warn("No staging directories found on disk [{$diskName}].");

            return Command::SUCCESS;
        }

        $deletedCount = 0;

        foreach ($directories as $dir) {
            $lastModifiedTimestamp = $staging->lastModified($dir);
            $lastModified = Carbon::createFromTimestamp($lastModifiedTimestamp);

            if ($lastModified->lte($cutoff)) {
                try {
                    $staging->deleteDirectory($dir);
                    $this->line("Deleted directory: {$dir} (last modified: {$lastModified->toDateTimeString()})");
                    $deletedCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to delete directory [{$dir}]: " . $e->getMessage());
                    Log::error('Failed to delete staging folder during cleanup', [
                        'directory' => $dir,
                        'disk' => $diskName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Cleanup finished. Removed {$deletedCount} staging director" . ($deletedCount === 1 ? 'y' : 'ies') . " older than {$days} days.");
        Log::info('Image staging cleanup command completed', [
            'deleted_folders_count' => $deletedCount,
            'older_than_days' => $days,
            'disk' => $diskName,
        ]);

        return Command::SUCCESS;
    }
}
