<?php

namespace App\Services\Proofing;
use App\Services\Proofing\FolderService;
use App\Models\Image;
use Carbon\Carbon;

class ImageService
{
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

    public function updateOrCreateImageRecord($bpSubjectImage)
    {
        // Check if the image already exists
        $image = Image::where('keyvalue', $bpSubjectImage->ts_subjectkey)->first();
    
        // Update or create the image record
        Image::updateOrCreate(
            ['keyvalue' => $bpSubjectImage->ts_subjectkey],
            [
                'ts_image_id' => $bpSubjectImage['images']->ts_image_id,
                'ts_imagekey' => $bpSubjectImage['images']->ts_imagekey,
                'ts_job_id' => $bpSubjectImage->ts_job_id,  // Use null if ts_job_id is not provided
                'keyorigin' => 'Subject',
                'protected' => 0,
                'created_at' => Carbon::now()
            ]
        );
    
        return true;
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
