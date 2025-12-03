<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use App\Models\Image;
use App\Models\SchoolPhotoUpload;
use App\Services\Storage\StorageServiceInterface;
use Illuminate\Console\Command;

class CleanUploadedImagesCommand extends Command
{
    protected StorageServiceInterface $storage;

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
    public function __construct(StorageServiceInterface $storage)
    {
        parent::__construct();
        $this->storage = $storage;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $date = now()->toDateTimeString();
        $this->info("-------------------------------- Cleaning up uploaded images [{$date}] --------------------------------");

        $candidates = SchoolPhotoUpload::where('deleted_at', null)->get();

        foreach ($candidates as $img) {
            $this->info("> Checking uploaded image {$img->id}...");
            $metadata = $img->metadata;

            if (!isset($metadata['path']) || empty($metadata['path'])) {
                $this->info(">> Marking uploaded image ID {$img->id} as deleted due to missing path.");
                try {
                    $img->update([
                        'deleted_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    $this->error(">> Failed to mark image ID {$img->id} as deleted: " . $e->getMessage());
                }
                $this->info("--------------------------------");
                continue;
            }

            if (!isset($metadata['key']) || !isset($metadata['origin'])) {
                $this->info(">> Skipping uploaded image ID {$img->id} due to missing origin or keyvalue.");
                $this->info("--------------------------------");
                continue;
            }

            $this->info(">> Checking keyvalue ({$metadata['key']}) and keyorigin ({$metadata['origin']})");

            $image = Image::where('keyvalue', $metadata['key'])
                ->where('keyorigin', $metadata['origin'])
                ->first();

            if (is_null($image)) {
                $this->info(">> No corresponding image found in images table for keyvalue {$metadata['key']} and origin {$metadata['origin']}.");
                $this->info("--------------------------------");
                continue;
            }
            
            $isFound = $this->isImageFound($metadata['key']);

            if ($isFound) {
                $this->info(">> Image FOUND. Deleting...");
                // Delete the uploaded image and mark as deleted in DB
                $this->deleteUploadedImage($img);
            } else {
                $this->info(">> Image NOT FOUND.");
            }
            $this->info("--------------------------------");
        }

        $date = now()->toDateTimeString();
        $this->info("-------------------------------- Cleanup completed [{$date}] --------------------------------");
    }

    private function isImageFound($key): bool
    {
        // Get path from base storage as the original images are stored there
        // Should the path change, adjust the path indicated accordingly
        $path = ImageHelper::getImagePath($key, '', ImageHelper::FLAG_STRICT_PATTERN);
        if ($path === '') {
            return false;
        }
        return $this->storage->exists($path);
    }

    /**
     * Delete image from path and mark as deleted in DB
     * @param SchoolPhotoUpload $image
     * @return void
     */
    private function deleteUploadedImage($image): void
    {
        $metadata = $image->metadata;
        try {
            $path = $metadata['path'];
            $deleted = $this->storage->delete($path);
            if ($deleted) {
                $metadata['path'] = ''; // No path means file is deleted
                $image->update([
                    'metadata' => $metadata,
                    'deleted_at' => now(),
                ]);
            }
            $this->info(">> Deleted uploaded image ID {$image->id} from path {$path}.");
        } catch (\Exception $e) {
            $this->error(">> Failed to delete image ID {$image->id}: " . $e->getMessage());
        }
    }
}