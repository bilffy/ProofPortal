<?php

// use App\Console\Commands\CleanUploadedImagesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Job;
use App\Jobs\SyncImagesToProd02;
use Illuminate\Support\Carbon;
use App\Services\Proofing\StatusService;

// $logsPath = storage_path('logs/clean_uploaded_images.log');

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// Schedule::command(CleanUploadedImagesCommand::class)
//     ->timezone(env('APP_TIMEZONE', 'UTC'))
//     ->at('1:00')
//     ->runInBackground()
//     ->appendOutputTo($logsPath);

// In your Console Scheduler configuration
Schedule::command('images:sync-scheduler')
    ->everyMinute()
    ->name('sync_images_scheduler')
    ->withoutOverlapping(10); // Prevents locks from hanging forever if a process crashes

Schedule::command('images:cleanup-staging --days=3')->daily();

Schedule::command('artwork:cleanup --days=90')->daily();

// Delete sync_errors, synclogs, user_otps and logs rows created over 180 days ago, every 3 days
// (Laravel has no "every three days" helper, so it is checked daily and runs on every third day of the year).
Schedule::command('records:cleanup --days=180')
    ->dailyAt('02:00')
    ->when(fn () => now()->dayOfYear % 3 === 0)
    ->name('records_cleanup')
    ->withoutOverlapping();
