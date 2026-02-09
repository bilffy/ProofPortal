<?php

namespace App\Services\Proofing;
use App\Services\Proofing\FolderService;
use App\Models\Image;
use Carbon\Carbon;

class ImageService
{
    protected $folderService;
    /**
     * Create a new class instance.
     */
    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    public function deleteImage($imagesToDelete)
    {
        return Image::whereIn('id', $imagesToDelete)->delete();
    }

    public function updateOrCreateImageRecord(array $data)
    {
        Image::updateOrCreate(
            [
                // ✅ Proper uniqueness (prevents duplicates)
                'keyorigin'   => 'Subject',
                'keyvalue'    => $data['ts_subjectkey'],
                'ts_imagekey' => $data['ts_imagekey'],
            ],
            [
                'ts_image_id' => $data['ts_image_id'],
                'ts_job_id'   => $data['ts_job_id'],
                'is_primary'  => $data['is_primary'],
                'protected'   => 0,
            ]
        );
    
        return true;
    }   

    public function getImageBySubjectAndImageKey(string $subjectKey, string $imageKey)
    {
        return Image::where('keyorigin', 'Subject')
            ->where('keyvalue', $subjectKey)
            ->where('ts_imagekey', $imageKey)
            ->first();
    }
    
    public function deleteImageBytsSubjectKey($tsSubjectKey)
    {
        return Image::where([
                ['keyvalue', '=', $tsSubjectKey],
                ['keyorigin', '=', 'Subject']
            ])->delete();
    }


    public function createGroupImage($folderKey = null, $extension = null)
    {
        $folderData = $this->folderService->getFolderByKey($folderKey)->select('ts_folderkey', 'ts_job_id')->first();

        // Check if folder data exists
        if ($folderData !== null) {
            Image::updateOrCreate(
                ['keyvalue' => $folderData->ts_folderkey],
                [
                    'name' => $folderKey.'.'.$extension,
                    'ts_job_id' => $folderData->ts_job_id,  // Use null if ts_job_id is not provided
                    'keyorigin' => 'Folder',
                    'created_at' => Carbon::now()
                ]
            );
        }
    }

    public function deleteGroupImage($folderKey = null)
    {
        // Validate if folderKey is null
        if (!$folderKey) {
            return null;
        }
    
        // Fetch folder data by folder key
        $folderData = $this->folderService->getFolderByKey($folderKey)->select('ts_folderkey', 'ts_job_id')->first();
    
        // Ensure folder data is found
        if (!$folderData) {
            return null;
        }
    
        // Find the image record associated with folder
        $image = Image::where([
            ['keyvalue', $folderData->ts_folderkey],
            ['ts_job_id', $folderData->ts_job_id]
        ])->first();
    
        // Ensure the image exists and then delete it
        if ($image !== null) {
            $fileName = $image->name;
            $image->delete();
            return $fileName;
        }
    
        return null;
    }

}
