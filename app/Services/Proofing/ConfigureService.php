<?php

namespace App\Services\Proofing;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\SubjectService;
use App\Services\Proofing\ImageService;
use App\Services\Proofing\TimestoneTableService;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\FolderSubjectService;
use App\Services\Proofing\EmailService;
use Illuminate\Support\Facades\DB;

class ConfigureService
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $folderService;
    protected $subjectService;
    protected $imageService;
    protected $proofingChangelogService;
    protected $timestoneTableService;
    protected $folderSubjectService;
    protected $emailService;

    public function __construct(
        EncryptDecryptService $encryptDecryptService, JobService $jobService, FolderService $folderService, ProofingChangelogService $proofingChangelogService,
        SubjectService $subjectService, ImageService $imageService, TimestoneTableService $timestoneTableService, FolderSubjectService $folderSubjectService, 
        EmailService $emailService
        )
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->folderService = $folderService;
        $this->subjectService = $subjectService;
        $this->imageService = $imageService;
        $this->proofingChangelogService = $proofingChangelogService;
        $this->timestoneTableService = $timestoneTableService;
        $this->folderSubjectService = $folderSubjectService;
        $this->emailService = $emailService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function insertProofingTimeline($data){
        $tsJobKey = $this->getDecryptData($data['jobHash']);
        $getJobData = $this->jobService->updateJobData($tsJobKey, $data['dataType'], $data['date']);
    }

    public function sendEmailDates($data){
        $tsJobKey = $this->getDecryptData($data['jobHash']);
        if ($data['dataType'] === 'proof_start' || $data['dataType'] === 'proof_warning' || $data['dataType'] === 'proof_due'|| $data['dataType'] === 'proof_catchup') {
            $saveEmailContent = $this->emailService->saveEmailContent($tsJobKey, $data['dataType'], $data['date'], null);
        }
    }

    public function mergeDuplicateFolders($tsJobId){
                
        $duplicateFolders = $this->folderService->getFolderByJobId($tsJobId)
            ->select('ts_folderkey','teacher', 'principal', 'deputy', DB::raw('count(*) as total'))
            ->groupBy('ts_folderkey')
            ->having('total', '>', 1)
            ->get();
        
        $deleteFolders = 0;
        $deletedSubjects = 0;
        $deletedImages = 0;
        $deletedAttachedSubjects = 0;
        $deletedTraditionalPhoto = 0;
        
        foreach ($duplicateFolders as $duplicateFolder) {
            $folders = $this->folderService->getAllFolderAssociationByKey($duplicateFolder->ts_folderkey)
                ->get(); // Eager load relationships

            $keepFolder = $folders->first(); // Keep the first folder
            
            // Collect IDs for bulk deletes
            $attachedSubjectIdsToDelete = [];
            $changelogIdsToDelete = [];

            foreach ($folders as $folder) {
                if ($folder->id !== $keepFolder->id) {
                    // Update principal, deputy, teacher data
                    if(isset($folder->principal) && is_null($keepFolder->principal)){
                        $this->folderService->updatePrincipal($keepFolder, $folder->principal);
                    }
                    if(isset($folder->deputy) && is_null($keepFolder->deputy)){
                        $this->folderService->updateDeputy($keepFolder, $folder->deputy);
                    }
                    if(isset($folder->teacher) && is_null($keepFolder->teacher)){
                        $this->folderService->updateTeacher($keepFolder, $folder->teacher);
                    }

                    // 1. Merge attached subjects
                    $attachedSubjectsToMerge = $folder->attachedsubjects()
                        ->whereNotIn('ts_subject_id', $keepFolder->subjects->pluck('ts_subject_id'))
                        ->get();
                    if ($attachedSubjectsToMerge->isNotEmpty()) {
                        foreach ($attachedSubjectsToMerge as $attachedSubject) {
                            $keepFolder->attachedsubjects()->save($attachedSubject); // Save each attached subject individually
                        }
                    }

                    // Collect attachedSubject IDs for deletion
                    foreach ($folder->attachedsubjects->groupBy('ts_subject_id') as $attachedSubjectGroup) {
                        $attachedSubjectGroup->shift(); // Keep the first attachedSubject
                        $attachedSubjectIdsToDelete = array_merge($attachedSubjectIdsToDelete, $attachedSubjectGroup->pluck('id')->toArray());
                        $deletedAttachedSubjects++;
                    }

                    // 2. Merge proofing changelogs and delete duplicates
                    $changelogToMerge = $folder->proofingChangelogs()
                        ->where('notes', 'LIKE', 'Traditional Photo People Row Positions for Folder%')
                        ->whereNotIn('keyvalue', $keepFolder->proofingChangelogs()
                            ->where('notes', 'LIKE', 'Traditional Photo People Row Positions for Folder%')
                            ->pluck('keyvalue'))
                        ->get();

                    if ($changelogToMerge->isNotEmpty()) {
                        foreach ($changelogToMerge as $changelog) {
                            $keepFolder->proofingChangelogs()->save($changelog); // Save each attached subject individually
                        }
                    }

                    // Collect changelog IDs for bulk deletion
                    foreach ($folder->proofingChangelogs->groupBy('keyvalue') as $changelogGroup) {
                        $changelogGroup->shift(); // Keep the first changelog
                        $changelogIdsToDelete = array_merge($changelogIdsToDelete, $changelogGroup->pluck('id')->toArray());
                        $deletedTraditionalPhoto++;
                    }

                    $folder->delete(); // Delete the duplicate folder
                    $deleteFolders++;
                }
            }

            // Bulk delete attached subjects, and changelogs
            if (!empty($attachedSubjectIdsToDelete)) {
                $this->folderSubjectService->deleteFolderSubject($attachedSubjectIdsToDelete);
            }
            if (!empty($changelogIdsToDelete)) {
                $this->proofingChangelogService->deleteChangelog($changelogIdsToDelete);
            }
        }
    }

    public function mergeDuplicateSubjects($tsJobId)
    {
        $duplicateSubjects = $this->subjectService->getSubjectByJobId($tsJobId)
            ->select('ts_subjectkey', DB::raw('count(*) as total'))
            ->groupBy('ts_subjectkey')
            ->having('total', '>', 1)
            ->get();
    
        $deletedSubjects = 0;
        $deletedImages = 0;
        $deletedAttachedSubjects = 0;

        foreach ($duplicateSubjects as $duplicateSubject) {
            $subjects = $this->subjectService->getAllSubjectAssociationByKey($duplicateSubject->ts_subjectkey);
            
            $keepSubject = $subjects->first();//keep the first subject

            $imagesToDelete = [];
            $attachedSubjectsToDelete = [];

            // Delete the remaining subjects in the group (if any)
            foreach ($subjects as $subject) {
                if ($subject->id !== $keepSubject->id) {
                // Merge images exists in subjects other than image in keepSubject (batch insert)
                $imagesToMerge = $subject->images()->whereNotIn('ts_imagekey', $keepSubject->images->pluck('ts_imagekey'))->get();
                if ($imagesToMerge->isNotEmpty()) {
                    foreach ($imagesToMerge as $image) {
                        $keepSubject->images()->save($image); // Save each image individually
                    }
                }

                // Collect images for batch deletion
                $subjectImages = $subject->images->groupBy('ts_imagekey');
                foreach ($subjectImages as $imageGroup) {
                    $imagesToDelete = array_merge($imagesToDelete, $imageGroup->slice(1)->pluck('id')->toArray());  // Keep first, delete rest
                }

                // Merge attached subjects exists in subjects other than attached subjects in keepSubject (batch insert)
                $attachedSubjectsToMerge = $subject->attachedsubjects()->whereNotIn('ts_subject_id', $keepSubject->attachedsubjects()->pluck('ts_subject_id'))->get();
                if ($attachedSubjectsToMerge->isNotEmpty()) {
                    foreach ($attachedSubjectsToMerge as $attachedSubject) {
                        $keepSubject->attachedsubjects()->save($attachedSubject); // Save each attached subject individually
                    }
                }

                // Collect attached subjects for batch deletion
                $subjectAttachedSubjects = $subject->attachedsubjects->groupBy(function ($attachedSubject) {
                    return $attachedSubject->ts_folder_id . '-' . $attachedSubject->ts_subject_id;
                });
                foreach ($subjectAttachedSubjects as $attachedSubjectGroup) {
                    $attachedSubjectsToDelete = array_merge($attachedSubjectsToDelete, $attachedSubjectGroup->slice(1)->pluck('id')->toArray());
                }

                // Delete subject
                $subject->delete();
                $deletedSubjects++;
                }
            }

            // Batch delete collected images and attached subjects
            $this->imageService->deleteImage($imagesToDelete);
            $this->folderSubjectService->deleteFolderSubject($attachedSubjectsToDelete);

            $deletedImages += count($imagesToDelete);
            $deletedAttachedSubjects += count($attachedSubjectsToDelete); 
        }
    }

    public function updateSubjectAssociations($tsJobId)
    {
        // Timestone
        $tsSubjects = $this->timestoneTableService->getAllTimestoneSubjectsByJobID($tsJobId); // Format subjects by SubjectKey

        // Blueprint
        $bpSubjects = $this->subjectService->getByJobId($tsJobId, 'id', 'ts_folder_id', 'ts_subjectkey', 'ts_subject_id')
                ->orderBy('ts_subjectkey', 'asc')
                ->get();

        $bpSubjectsUpdated = [];
        foreach ($bpSubjects as $bpSubject) {
            if (isset($tsSubjects[$bpSubject->ts_subjectkey])) {
                // Convert TS to BP folder_id
                $folderKey = $tsSubjects[$bpSubject->ts_subjectkey]->FolderKey;

                // Fetch the single folder ID using first()
                $bpFolder = $this->folderService->getFolderByJobIdAndFolderKey($tsJobId,$folderKey)->first(); // Use first() to fetch a single record
        
                // Check if we have a result and if ts_folder_id is different
                if ($bpFolder && $bpSubject->ts_folder_id != $bpFolder->ts_folder_id) {
                    $bpSubject->ts_folder_id = $bpFolder->ts_folder_id;
                    $bpSubjectsUpdated[] = $bpSubject;
                }
            }
        }

        // Save the updated subjects
        foreach ($bpSubjectsUpdated as $updatedSubject) {
            $updatedSubject->save(); // Save the updated record
        }

        // Update FoldersSubjects table from Timestone
        $this->recastTimestoneLinksOfSubjectsToFolders($bpSubjects->pluck('ts_subject_id'));
    }
    

    private function recastTimestoneLinksOfSubjectsToFolders($tsSubjectIds = null)
    {
        //Timestone
        $tsFolderSubjectPairs = $this->timestoneTableService->getAllTimestoneSubjectFolders($tsSubjectIds);

        //Blueprint
        $bpFolderSubjectPairs = $this->folderSubjectService->getAllBlueprintSubjectFolders($tsSubjectIds);
        
        // Group Timestone data by SubjectID for easier comparison
        $tsPairsGrouped = $tsFolderSubjectPairs->groupBy('SubjectID');

        // Group Blueprint data by ts_subject_id for easier comparison
        $bpPairsGrouped = $bpFolderSubjectPairs->groupBy('ts_subject_id');

        // Loop through Blueprint folder-subject pairs
        foreach ($bpPairsGrouped as $subjectId => $bpPairs) {
            $bpFolderIds = $bpPairs->pluck('ts_folder_id')->toArray(); // Get all FolderIDs for this SubjectID

            // Check if the subject exists in Timestone
            if (isset($tsPairsGrouped[$subjectId])) {
                $tsFolderIds = $tsPairsGrouped[$subjectId]->pluck('FolderID')->toArray(); // Get all associated ts_folder_ids from Timestone

                // Sort arrays to ensure comparison works correctly
                sort($bpFolderIds);
                sort($tsFolderIds);

                // Compare the arrays of folder IDs
                if ($bpFolderIds !== $tsFolderIds) {
                    // If they are different, update Blueprint records
                    // First, delete old Blueprint records for this subject
                    $this->folderSubjectService->deleteFolderSubjectBySubjectId($subjectId);

                    // Insert new FolderSubject records from Timestone
                    foreach ($tsFolderIds as $folderId) {
                        $this->folderSubjectService->createFolderSubject($folderId,$subjectId);
                    }
                }
            }
        }
    }

    public function updatePeopleImage($tsJobId)
    {
        // Timestone
        $tsSubjectImages = $this->timestoneTableService->getAllTimestoneHomeSubjectsImageByJobID($tsJobId); // Format subjects by SubjectId

        // Blueprint
        $bpSubjectImages = $this->subjectService->getAllHomedSubjectsImageByJobId($tsJobId);

        $this->updateImage($tsSubjectImages, $bpSubjectImages);

        $this->updateAttachedPeopleImage($tsJobId);
    }

    private function updateAttachedPeopleImage($tsJobId)
    {
        //Timestone
        $tsAttachedSubjectImages = $this->timestoneTableService->getAllTimestoneAttachedSubjectsImageByJobID($tsJobId)->get()->keyBy('SubjectID');

        //Blueprint
        $bpFolders = $this->folderService->getFolderByJobId($tsJobId)->pluck('ts_folder_id');
        $bpAttachedSubjectIDs = $this->folderSubjectService->getAllBlueprintSubjectFoldersByFolderIds($bpFolders->toArray());
        $bpAttachedSubjectImages = $this->folderSubjectService->getAllAttachedSubjectsImageBySubjectIds($bpAttachedSubjectIDs->pluck('ts_subject_id')->toArray());

        $this->updateImage($tsAttachedSubjectImages, $bpAttachedSubjectImages);
    }

    private function updateImage($tsSubjectImages, $bpSubjectImages)
    {
        $bpSubjectImageUpdated = [];

        foreach ($bpSubjectImages as $bpSubjectImage) 
        {
            $tsSubjectKey = $bpSubjectImage->ts_subjectkey;

                if (isset($tsSubjectImages[$tsSubjectKey])) {
                    //fetching timestone imagekey and imageid of subject
                    $imageKey = $tsSubjectImages[$tsSubjectKey]->ImageKey;
                    $imageID = $tsSubjectImages[$tsSubjectKey]->ImageID;

                    if (isset($bpSubjectImage['images']) )
                    {
                        // Check if we have a result and if ts_image_id or ts_imagekey is different
                        if (($bpSubjectImage['images']->ts_image_id != $imageID) || ($bpSubjectImage['images']->ts_imagekey != $imageKey)) {
                            $bpSubjectImage['images']->ts_image_id = $imageID;
                            $bpSubjectImage['images']->ts_imagekey = $imageKey;

                            $this->imageService->updateOrCreateImageRecord($bpSubjectImage);

                            // Add to the updated list
                            $bpSubjectImageUpdated[] = $bpSubjectImage;
                        }
                    } else {
                        $bpSubjectImage['images'] = (object)[
                            'ts_image_id' => $imageID,
                            'ts_imagekey' => $imageKey
                        ];
        
                        $this->imageService->updateOrCreateImageRecord($bpSubjectImage);
                        $bpSubjectImageUpdated[] = $bpSubjectImage;
                    }
                } else {
                        if (isset($bpSubjectImage['images']))
                        {
                            $this->imageService->deleteImageBytsSubjectKey($tsSubjectKey);
                        }
                }
        }
        return $bpSubjectImageUpdated;
    }

    public function peopleImageCount($tsJobId)
    {
        // Timestone
        $tsHomedSubjectImages = $this->timestoneTableService->getAllTimestoneHomeSubjectsImageByJobID($tsJobId);
        $tsHomedSubjectImagesCount = $tsHomedSubjectImages->count(); // Format subjects by SubjectId
        $tsHomedSubjectIds = $tsHomedSubjectImages->pluck('Subjects.SubjectID')->toArray();

        $tsAttachedSubjectImagesCount = $this->timestoneTableService->getAllTimestoneAttachedSubjectsImageByJobID($tsJobId)->whereNotIn('SubjectFolders.SubjectID', $tsHomedSubjectIds)->groupBy('SubjectFolders.SubjectID')->count();
        $totalTSSubjectImages = $tsHomedSubjectImagesCount + $tsAttachedSubjectImagesCount;

        // Blueprint
        $bpSubjectCount = $this->subjectService->getSubjectByJobId($tsJobId)->count();
        $bpHomedSubjectImages = $this->subjectService->getAllHomedSubjectsImageByJobId($tsJobId);
        $bpHomedSubjectImagesCount = $bpHomedSubjectImages->count();
        $bpHomedSubjectIds =  $bpHomedSubjectImages->pluck('ts_subject_id')->toArray();

        $bpFolders = $this->folderService->getFolderByJobId($tsJobId)->pluck('ts_folder_id');
        $bpAttachedSubjectIDs = $this->folderSubjectService->getAllBlueprintSubjectFoldersByFolderIds($bpFolders->toArray())->pluck('ts_subject_id')->toArray();
        $bpAttachedSubjectImages = $this->folderSubjectService->getAllAttachedSubjectsImageBySubjectIds($bpAttachedSubjectIDs)->whereNotIn('ts_subject_id', $bpHomedSubjectIds)->groupBy('ts_subject_id')->count();

        $totalBPSubjectImages = $bpHomedSubjectImagesCount + $bpAttachedSubjectImages;

        return [
            'totalTSSubjectImages' => $totalTSSubjectImages,
            'totalBPSubjectImages' => $totalBPSubjectImages,
            'bpSubjectCount' => $bpSubjectCount
        ];
    }
}


