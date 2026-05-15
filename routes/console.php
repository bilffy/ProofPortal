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

Schedule::call(function () {
    $statusService = app(StatusService::class);
    $jobs = Job::where('proof_start', '>', Carbon::today())
    ->where('imagesync_status_id', $statusService->unsync)
    ->whereHas('images', function ($query) {
        $query->where('exportStatus', 0)
              ->where('keyorigin', 'Subject');
    })
    ->chunkById(50, function ($jobs) {
        foreach ($jobs as $job) {
            SyncImagesToProd02::dispatch($job->ts_jobkey);
        }
    });

})
->name('sync_images_scheduler')
->everyMinute()
->withoutOverlapping();


