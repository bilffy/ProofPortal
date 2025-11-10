<?php

namespace App\Console;

declare(strict_types=1);

use App\Console\Commands\CleanUploadedImagesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The commands to register.
     *
     * @var array<class-string>
     */
    protected array $commands = [
        CleanUploadedImagesCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule the CleanUploadedImagesCommand to run daily at midnight
        $schedule->command(CleanUploadedImagesCommand::class)->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function defineCommands(): void
    {
        $this->load([
            __DIR__.'/Commands',
        ]);
    }
}