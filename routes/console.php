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