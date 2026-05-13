<?php

// use App\Console\Commands\CleanUploadedImagesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Job;
use App\Jobs\SyncImagesToProd02;
use Illuminate\Support\Carbon;

// $logsPath = storage_path('logs/clean_uploaded_images.log');

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// Schedule::command(CleanUploadedImagesCommand::class)
//     ->timezone(env('APP_TIMEZONE', 'UTC'))
//     ->at('1:00')
//     ->runInBackground()
//     ->appendOutputTo($logsPath);

Schedule::call(function () {
    $jobs = Job::where('proof_start', '>', Carbon::today())
        ->whereHas('images', function ($query) {
            $query->where('exportStatus', 0)
                  ->where('keyorigin', 'Subject');
        })
        ->get();

    foreach ($jobs as $job) {
        SyncImagesToProd02::dispatch($job->ts_jobkey);
    }
})
->everyFiveMinutes()
->runInBackground()
->withoutOverlapping();
