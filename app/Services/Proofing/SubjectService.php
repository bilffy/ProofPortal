<?php

namespace App\Services\Proofing;
use App\Models\Subject;
use App\Services\Proofing\FolderSubjectService;

class SubjectService
{
    /**
     * Create a new class instance.
     */

    public function __construct(FolderSubjectService $folderSubjectService)
    {
        $this->folderSubjectService = $folderSubjectService;
    }

    public function getAllHomedSubjectsByFolderID($folderId)
    {
        // Eager load images for subjects
        $homedSubjects = Subject::with(['images:id,keyvalue,ts_imagekey'])
        ->where('ts_folder_id', $folderId)
        ->select('id', 'firstname', 'lastname', 'ts_subjectkey', 'is_locked', 'title', 'salutation', 'prefix', 'suffix', 'ts_subject_id')->get();

        return $homedSubjects;
    }

    public function getAllAttachedSubjectsByFolderID($folderId)
    {
        // Eager load images for attached subjects
        $attachedSubjects = $this->folderSubjectService->getSubjectInAttachedFolder($folderId);

        return $attachedSubjects->map(function($attachedSubject) {
            return $attachedSubject->subject;
        });
    }

    public function getAllHomedSubjectsImageByJobId($tsJobId)
    {
        // return Subject::where('ts_job_id', $tsJobId)
        //     ->with(['images' => function ($query) {
        //         // Select specific fields from the images table and order them
        //         $query->select('id', 'ts_image_id', 'ts_imagekey', 'keyvalue', 'ts_job_id')
        //             ->orderBy('ts_imagekey', 'asc');
        //     }]);

        return Subject::where('ts_job_id', $tsJobId)
        ->select('id', 'ts_subject_id', 'ts_job_id') // Select only required columns from 'subjects' table
        ->with(['images:id,ts_image_id,ts_imagekey,keyvalue,ts_job_id']) // Load only necessary fields from 'images' table
        ->whereHas('images') // Ensure that the subject has images
        ->get();
    }

    public function getSubjectByJobId($tsJobId)
    {
        return Subject::where('ts_job_id', $tsJobId);
    }

    public function getAllSubjectAssociationByKey($subjectkey)
    {
        return Subject::with(['images', 'attachedsubjects'])
            ->where('ts_subjectkey', $subjectkey)
            ->get();
    }

    public function getByJobId($tsJobId,...$selectedValues)
    {
        return Subject::select($selectedValues)
        ->where('ts_job_id', $tsJobId);
    }

    public function getBySubjectKey($subjectKey, ...$selectedValues)
    {
        return Subject::select($selectedValues)
            ->where('ts_subjectkey', $subjectKey);
    }

    public function getBySubjectTSsubjectID($TSsubjectID, ...$selectedValues)
    {
        return Subject::select($selectedValues)
            ->where('ts_subject_id', $TSsubjectID);
    }

    public function getSubjectById($id)
    {
        return Subject::find($id);
    }
}
