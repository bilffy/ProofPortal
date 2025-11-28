<?php

use App\Console\Commands\CleanUploadedImagesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

$logsPath = storage_path('logs/clean_uploaded_images.log');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(CleanUploadedImagesCommand::class)
    ->timezone(env('APP_TIMEZONE', 'UTC'))
    ->at('1:00')
    ->runInBackground()
    ->appendOutputTo($logsPath);
