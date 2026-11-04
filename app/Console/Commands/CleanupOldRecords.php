<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOldRecords extends Command
{
    private const TABLES = ['sync_errors', 'synclogs', 'user_otps', 'logs'];

    private const CHUNK = 5000;

    protected $signature = 'records:cleanup
        {--days=180 : Delete rows created more than X days ago}
        {--dry-run : Only count what would be deleted}';

    protected $description = 'Delete rows older than X days (by created_at) from sync_errors, synclogs, user_otps and logs.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $this->info(($dryRun ? '[dry run] ' : '') . "Record cleanup: rows created before {$cutoff->toDateTimeString()} ({$days} days)");

        $totals = [];

        foreach (self::TABLES as $table) {
            $query = fn () => DB::table($table)->where('created_at', '<', $cutoff);

            if ($dryRun) {
                $totals[$table] = $query()->count();
            } else {
                // Delete in chunks so a big backlog does not hold a long table lock.
                $deleted = 0;
                do {
                    $count = $query()->limit(self::CHUNK)->delete();
                    $deleted += $count;
                } while ($count === self::CHUNK);

                $totals[$table] = $deleted;
            }

            $this->line("  {$table}: {$totals[$table]}");
        }

        if (!$dryRun) {
            Log::info('Old records cleanup completed', ['older_than_days' => $days, 'deleted' => $totals]);
        }

        return Command::SUCCESS;
    }
}
