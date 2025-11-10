<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanUploadedImagesCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'images:clean-uploaded';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up uploaded images that are no longer needed';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Logic to clean up uploaded images goes here
        $this->info('Cleaning up uploaded images...');


        

        // Cleanup done
        $this->info('Cleanup completed.');
    }
}