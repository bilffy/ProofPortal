<?php

namespace App\Services\Proofing;
use Illuminate\Support\Facades\DB;

class TimestoneTableService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllTimestoneSubjectsByJobID($tsJobId)
    {
        return DB::connection('timestone')
        ->table('Subjects')
        ->join('Folders', 'Folders.FolderID', '=', 'Subjects.FolderID')
        ->where('Subjects.JobID', $tsJobId)
        ->select(['SubjectID', 'Folders.FolderKey', 'SubjectKey'])
        ->orderBy('SubjectKey', 'asc')
        ->get()
        ->keyBy('SubjectKey'); // Format subjects by SubjectKey
    }

    public function getAllTimestoneSubjectFolders($tsSubjectIds)
    {
        return DB::connection('timestone')
        ->table('SubjectFolders')->whereIn('SubjectID', $tsSubjectIds)
        ->select('FolderID', 'SubjectID')
        ->get();
    }

    public function getAllTimestoneHomeSubjectsImageByJobID($tsJobId)
    {
        return DB::connection('timestone')
        ->table('Subjects')
        ->join('ImageMatches', 'ImageMatches.SubjectID', '=', 'Subjects.SubjectID')
        ->join('Images', 'Images.ImageID', '=', 'ImageMatches.ImageID')
        ->where('Subjects.JobID', $tsJobId)
        ->select(['Images.ImageID', 'Images.ImageKey', 'Subjects.SubjectID'])
        ->orderBy('SubjectID', 'asc')
        ->get()
        ->keyBy('SubjectID'); // Format subjects by SubjectKey
    }

    public function getAllTimestoneAttachedSubjectsImageByJobID($tsJobId)
    {
        return DB::connection('timestone')
        ->table('SubjectFolders')
        ->join('ImageMatches', 'ImageMatches.SubjectID', '=', 'SubjectFolders.SubjectID')
        ->join('Images', 'Images.ImageID', '=', 'ImageMatches.ImageID')
        ->where('Images.JobID', $tsJobId)
        ->select(['Images.ImageID', 'Images.ImageKey', 'SubjectFolders.SubjectID'])
        ->orderBy('SubjectFolders.SubjectID', 'asc'); // Format subjects by SubjectKey
    }
}
