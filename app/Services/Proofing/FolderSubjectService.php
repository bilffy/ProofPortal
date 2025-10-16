<?php

namespace App\Services\Proofing;
use App\Models\FolderSubject;

class FolderSubjectService
{
    public function getAttachedFolders($subjectChangeTSId){
        return FolderSubject::join('folders', 'folders.ts_folder_id', '=', 'folder_subjects.ts_folder_id')
        ->where('folder_subjects.ts_subject_id', $subjectChangeTSId)
        ->select('folders.ts_foldername', 'folders.ts_folderkey')
        ->get();
    }

    public function getSubjectInAttachedFolder($folderId){
        return FolderSubject::with(['subject.images:id,keyvalue,ts_imagekey'])
        ->where('ts_folder_id', $folderId)
        ->get();
    }

    public function deleteFolderSubject($attachedSubjectIdsToDelete)
    {
        return FolderSubject::whereIn('id', $attachedSubjectIdsToDelete)->delete();
    }

    public function deleteFolderSubjectBySubjectId($subjectId)
    {
        return FolderSubject::where('ts_subject_id', $subjectId)->delete();;
    }

    public function getAllBlueprintSubjectFolders($tsSubjectIds)
    {
        return FolderSubject::whereIn('ts_subject_id', $tsSubjectIds)
        ->select('ts_folder_id', 'ts_subject_id')->get();
    }

    public function getAllBlueprintSubjectFoldersByFolderIds($tsFolderIds)
    {
        return FolderSubject::whereIn('ts_folder_id', $tsFolderIds)
        ->select('ts_folder_id', 'ts_subject_id')->get();
    }

    public function createFolderSubject($folderId,$subjectId)
    {
        return FolderSubject::create([
            'ts_folder_id' => $folderId,
            'ts_subject_id' => $subjectId
        ]);
    }

    // public function getAllAttachedSubjectsImageBySubjectIds($bpsubjectIds)
    // {
    //     // return FolderSubject::whereIn('ts_subject_id', $bpsubjectIds)
    //     //     ->with(['images' => function ($query) {
    //     //         // You can order and filter within the images relation
    //     //         $query->select('id', 'ts_image_id', 'ts_imagekey', 'keyvalue', 'ts_job_id')
    //     //             ->orderBy('ts_imagekey', 'asc');
    //     //     }]);

    //     return FolderSubject::whereIn('ts_subject_id', $bpsubjectIds)
    //     ->select('id', 'ts_subject_id', 'ts_folder_id') // Fetch only required columns from `folder_subjects`
    //     ->with(['images:id,ts_image_id,ts_imagekey,keyvalue,ts_job_id']) // Fetch specific fields from `images` table
    //     ->whereHas('images')
    //     ->get();
    // }

    public function getAllAttachedSubjectsImageBySubjectIds($bpsubjectIds)
    {
        return FolderSubject::whereIn('ts_subject_id', $bpsubjectIds)
            ->select('folder_subjects.id', 'folder_subjects.ts_subject_id', 'folder_subjects.ts_folder_id') // Fetch only required columns from `folder_subjects`
            ->with(['images:images.id,ts_image_id,ts_imagekey,keyvalue,subjects.ts_job_id']) // Fetch specific fields from `images` table
            ->whereHas('images', function($query) {
                $query->select('images.id', 'ts_image_id', 'ts_imagekey', 'keyvalue', 'subjects.ts_job_id'); // Explicitly select columns in `images` table
            })
            ->get();
    }
}
        