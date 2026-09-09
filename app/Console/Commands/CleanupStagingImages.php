<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SplFileInfo;

class CleanupStagingImages extends Command
{
    protected $signature = 'images:cleanup-staging {--days=3 : Delete files and directories older than X days}';

    protected $description = 'Clean up old proofing staging dirs, group image thumbs, and framework file cache data';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $this->info("Starting image/cache cleanup for items older than {$days} days (before {$cutoff->toDateTimeString()})...");
        Log::info('Image staging cleanup command initiated', [
            'older_than_days' => $days,
            'cutoff' => $cutoff->toDateTimeString(),
        ]);

        $deletedStaging = $this->cleanupProofingStaging($cutoff, $days);
        $deletedThumbs = $this->cleanupGroupImageThumbs($cutoff);
        $deletedCacheFiles = $this->cleanupFrameworkCacheData($cutoff);

        $this->newLine();
        $this->info('Cleanup finished.');
        $this->line("  Proofing staging directories removed: {$deletedStaging}");
        $this->line("  Group image thumbs removed: {$deletedThumbs}");
        $this->line("  Framework cache files removed: {$deletedCacheFiles}");

        Log::info('Image staging cleanup command completed', [
            'older_than_days' => $days,
            'deleted_staging_directories' => $deletedStaging,
            'deleted_group_image_thumbs' => $deletedThumbs,
            'deleted_framework_cache_files' => $deletedCacheFiles,
        ]);

        return Command::SUCCESS;
    }

    private function cleanupProofingStaging(Carbon $cutoff, int $days): int
    {
        $diskName = filled(config('services.proofing_cache_disk'))
            ? (string) config('services.proofing_cache_disk')
            : 'proofing_cache';
        $stagingRoot = config("filesystems.disks.{$diskName}.root", storage_path('app/proofing_cache'));
        $staging = Storage::disk($diskName);

        $this->info("Scanning proofing staging on disk [{$diskName}] ({$stagingRoot})...");

        $directories = $staging->directories();
        if ($directories === []) {
            $this->warn("No staging directories found on disk [{$diskName}].");

            return 0;
        }

        $deletedCount = 0;

        foreach ($directories as $dir) {
            $lastModified = Carbon::createFromTimestamp($staging->lastModified($dir));

            if ($lastModified->lte($cutoff)) {
                try {
                    $staging->deleteDirectory($dir);
                    $this->line("Deleted staging directory: {$dir} (last modified: {$lastModified->toDateTimeString()})");
                    $deletedCount++;
                } catch (\Throwable $e) {
                    $this->error("Failed to delete staging directory [{$dir}]: " . $e->getMessage());
                    Log::error('Failed to delete staging folder during cleanup', [
                        'directory' => $dir,
                        'disk' => $diskName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $deletedCount;
    }

    private function cleanupGroupImageThumbs(Carbon $cutoff): int
    {
        $disk = Storage::disk('public');
        $thumbDirectory = 'groupImageThumbs';
        $thumbRoot = $disk->path($thumbDirectory);

        $this->info("Scanning group image thumbs in {$thumbRoot}...");

        if (!$disk->exists($thumbDirectory)) {
            $this->warn('No groupImageThumbs directory found.');

            return 0;
        }

        $deletedCount = 0;

        foreach ($disk->files($thumbDirectory) as $file) {
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));

            if ($lastModified->lte($cutoff)) {
                try {
                    $disk->delete($file);
                    $this->line("Deleted thumb: {$file} (last modified: {$lastModified->toDateTimeString()})");
                    $deletedCount++;
                } catch (\Throwable $e) {
                    $this->error("Failed to delete thumb [{$file}]: " . $e->getMessage());
                    Log::error('Failed to delete group image thumb during cleanup', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $deletedCount;
    }

    private function cleanupFrameworkCacheData(Carbon $cutoff): int
    {
        $cacheRoot = storage_path('framework/cache/data');

        $this->info("Scanning framework file cache in {$cacheRoot}...");

        if (!File::isDirectory($cacheRoot)) {
            $this->warn('No framework cache data directory found.');

            return 0;
        }

        $deletedCount = 0;

        /** @var SplFileInfo $file */
        foreach (File::allFiles($cacheRoot) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            $lastModified = Carbon::createFromTimestamp($file->getMTime());
            if ($lastModified->gt($cutoff)) {
                continue;
            }

            try {
                File::delete($file->getPathname());
                $deletedCount++;
            } catch (\Throwable $e) {
                $this->error("Failed to delete cache file [{$file->getPathname()}]: " . $e->getMessage());
                Log::error('Failed to delete framework cache file during cleanup', [
                    'file' => $file->getPathname(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->pruneEmptyCacheDirectories($cacheRoot);

        if ($deletedCount > 0) {
            $this->line("Deleted {$deletedCount} framework cache file(s) older than cutoff.");
        }

        return $deletedCount;
    }

    private function pruneEmptyCacheDirectories(string $root): void
    {
        $directories = File::directories($root);

        foreach ($directories as $directory) {
            $this->pruneEmptyCacheDirectories($directory);

            if ($this->directoryIsEmptyExceptGitignore($directory)) {
                @rmdir($directory);
            }
        }
    }

    private function directoryIsEmptyExceptGitignore(string $directory): bool
    {
        $entries = @scandir($directory);
        if ($entries === false) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if ($entry === '.gitignore') {
                continue;
            }

            return false;
        }

        return true;
    }
}
