<?php

namespace App\Console\Commands;

use App\Models\OrderArtworkFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupArtworkUploads extends Command
{
    private const DIRECTORY = 'artwork-uploads';

    protected $signature = 'artwork:cleanup
        {--days=90 : Delete uploaded artwork files older than X days}
        {--dry-run : Only list what would be deleted}';

    protected $description = 'Delete order artwork uploads (public disk, artwork-uploads/) older than X days. The order_artwork_files rows are kept so order history still lists the files, flagged as expired.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);
        $disk = Storage::disk('public');

        $this->info(($dryRun ? '[dry run] ' : '') . "Artwork cleanup: files older than {$days} days (before {$cutoff->toDateTimeString()})");

        $deleted = 0;
        $bytes = 0;

        // 1. Files that belong to an order and were uploaded before the cutoff.
        OrderArtworkFile::where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($files) use ($disk, $dryRun, &$deleted, &$bytes) {
                foreach ($files as $file) {
                    if (!$this->isInsideArtworkDirectory($file->path) || !$disk->exists($file->path)) {
                        continue;
                    }

                    $bytes += (int) $disk->size($file->path);
                    $deleted++;
                    $this->line("  {$file->path}");

                    if (!$dryRun) {
                        $disk->delete($file->path);
                    }
                }
            });

        // 2. Files nobody references (e.g. an order that failed part-way).
        $referenced = OrderArtworkFile::pluck('path')->flip();
        $orphans = 0;

        foreach ($disk->files(self::DIRECTORY) as $path) {
            if ($referenced->has($path) || $disk->lastModified($path) >= $cutoff->getTimestamp()) {
                continue;
            }

            $bytes += (int) $disk->size($path);
            $orphans++;
            $this->line("  {$path} (not linked to an order)");

            if (!$dryRun) {
                $disk->delete($path);
            }
        }

        $mb = round($bytes / 1048576, 1);
        $this->info(($dryRun ? 'Would delete' : 'Deleted') . " {$deleted} order file(s) and {$orphans} unlinked file(s), {$mb} MB.");

        if (!$dryRun) {
            Log::info('Artwork uploads cleanup completed', [
                'older_than_days' => $days,
                'deleted_order_files' => $deleted,
                'deleted_unlinked_files' => $orphans,
                'megabytes' => $mb,
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Never delete anything outside artwork-uploads/, whatever a row says.
     */
    private function isInsideArtworkDirectory(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, self::DIRECTORY . '/')
            && !str_contains($path, '..');
    }
}
