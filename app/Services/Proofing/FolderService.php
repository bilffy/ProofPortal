<?php

namespace App\Services\Proofing;
use App\Models\Folder;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SubjectService;
use App\Services\Proofing\ProofingDescriptionService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\EmailService;
use Carbon\Carbon;

class FolderService
{
    protected $encryptDecryptService;
    protected $statusService;

    protected function getJobService()
    {
        return app(JobService::class);
    }

    protected function getProofingChangelogService()
    {
        return app(ProofingChangelogService::class);
    }

    public function __construct(EncryptDecryptService $encryptDecryptService, StatusService $statusService, SubjectService $subjectService, ProofingDescriptionService $proofingDescriptionService, EmailService $emailService)
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->statusService = $statusService;
        $this->subjectService = $subjectService;
        $this->proofingDescriptionService = $proofingDescriptionService;
        $this->emailService = $emailService;
    }

    private function getDecryptData($hash)
    {
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function getFolderByJobId($tsJobId)
    {
        return Folder::where('ts_job_id', $tsJobId)->where('status_id', '!=', $this->statusService->tnjNotFound);
    }

    public function getFolderByJobIdAndFolderKey($tsJobId,$folderkey)
    {
        return Folder::select(['ts_folder_id'])
        ->where('ts_job_id', $tsJobId)
        ->where('ts_folderkey', $folderkey);
    }

    public function getFolderByKey($folderkey){
        return Folder::with(['job','subjects'])->where('ts_folderkey', $folderkey);
    }

    public function getAllFolderAssociationByKey($folderkey){
        return Folder::with(['subjects.images', 'attachedsubjects', 'proofingChangelogs'])->where('ts_folderkey', $folderkey);
    }

    public function getFolderById($id,...$selectedValues){
        return Folder::where('id', $id)->select($selectedValues);
    }

    public function getHomeFolders($subQuerySubjects,...$selectedValues){
        return Folder::join('jobs', 'jobs.ts_job_id', '=', 'folders.ts_job_id')
            ->whereIn('folders.ts_folder_id', $subQuerySubjects)
            ->orderBy('folders.ts_foldername', 'asc')->select($selectedValues);
    }

    public function findFolderId($folderId){
        return Folder::find($folderId);
    }

    public function getGroupByFolder($folderKey) {
        // Retrieve the issue ID
        $proofingIssueID = $this->proofingDescriptionService->getAllProofingDescriptionByIssueName('TRADITIONAL PHOTO', 'id')->id;

        // Attempt to get the active group positions
        $groupPositions =  $this->getProofingChangelogService()->getGroupPositionData($folderKey, $proofingIssueID, $this->statusService->active);
        
        // If not found, attempt to get inactive group positions
        if (empty($groupPositions)) {
            $groupPositions =  $this->getProofingChangelogService()->getGroupPositionData($folderKey, $proofingIssueID, $this->statusService->inactive);
        }        
        $groupValue = $groupPositions;

        if ($groupPositions) {
            // Decode JSON to PHP array
            $groupData = json_decode($groupPositions, true);
    
            $groupDetails = [];
            $absentDetails = [];
    
            foreach ($groupData as $row => $subjects) {
                // Check if the key contains "Absent" (case-insensitive)
                if (stripos($row, 'Absent') !== false) {
                    $absentDetails[$row] = [];
                } else {
                    $groupDetails[$row] = [];
                }
                
                foreach ($subjects as $subjectName) {
                    if ($subjectName) {
                        $formattedSubject = $subjectName;
                        if (stripos($row, 'Absent') !== false) {
                            $absentDetails[$row][] = $formattedSubject;
                        } else {
                            $groupDetails[$row][] = $formattedSubject;
                        }
                    }
                }
            }
    
            // Merge the absent details at the end
            $groupDetails = array_merge($groupDetails, $absentDetails);
    
            return ['groupDetails' => $groupDetails, 'groupValue' => $groupValue];
        }
    
        return [];
    }

    public function getSubjectIDByName($folderKey, $data) {
        // Retrieve folder and job IDs
        $currentFolder = $this->getFolderByKey($folderKey)->select('ts_folder_id', 'ts_job_id')->first();
    
        // Check if $currentFolder is found
        if (!$currentFolder) {
            return ['error' => 'Folder not found.'];
        }
    
        // Get subjects
        $homedSubjects = $this->subjectService->getAllHomedSubjectsByFolderID($currentFolder->ts_folder_id);
        $attachedSubjects = $this->subjectService->getAllAttachedSubjectsByFolderID($currentFolder->ts_folder_id);
        $allSubjects = $attachedSubjects->merge($homedSubjects);
    
        // Initialize result array
        $result = [];
        
        // Process each group of names
        foreach ($data as $rowLabel => $names) {
            $result[$rowLabel] = [];
            
            // Iterate through each name in the current group
            foreach ($names as $name) {
                // Find the subject in the merged collection
                $subject = $allSubjects->filter(function ($item) use ($name) {
                    return ($item->firstname.' '.$item->lastname === $name);
                })->first();
                
                // If subject is found, add its ID to the result
                if ($subject) {
                    $result[$rowLabel][] = $subject->ts_subjectkey.':'.$subject->firstname.' '.$subject->lastname;
                } else {
                    $result[$rowLabel][] = '--Not Found--:'.$name;
                }
            }
        }
    
        // Convert result to JSON and handle errors
        $jsonData = json_encode($result);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'JSON encoding error: '.json_last_error_msg()];
        }
    
        // Ensure jobKey exists
        $jobKey = isset($currentFolder->job) ? $currentFolder->job->ts_jobkey : 'Unknown Job Key';
    
        return [
            'jsonData' => $jsonData,
            'jobKey' => $jobKey
        ];
    }

    public function findIncompletedFolders($tsJobId)
    {
        return Folder::where('ts_job_id', $tsJobId)
        ->where('status_id', '!=', $this->statusService->completed)
        ->count();
    }

    public function updateFolderStatus($folderIds, $status)
    {
        // Update folder statuses in bulk
        Folder::whereIn('ts_folder_id', $folderIds)
            ->update(['status_id' => $status]);
    }

    public function sendEmailContent($folderIds, $status)
    {
        // Check if the status requires email content to be saved
        if (in_array($status, [$this->statusService->modified, $this->statusService->completed, $this->statusService->unlocked])) {
            $statusFields = [
                $this->statusService->modified => 'folder_status_modified',
                $this->statusService->completed => 'folder_status_completed',
                $this->statusService->unlocked => 'folder_status_unlocked',
            ];

            // Save email content for each folder
            if (isset($statusFields[$status])) {
                $this->emailService->saveEmailFolderContent($folderIds, $statusFields[$status], Carbon::now(), $status);
            }
        }
    }

    public function proofingFolderStatus($data){

        $folderIds = is_array($data['folder_ids']) ? $data['folder_ids'] : [$data['folder_ids']];

        $decryptedFolderIds = array_map(function ($hash) {
            return $this->getDecryptData($hash);
        }, $folderIds);
    

        $this->updateFolderStatus($decryptedFolderIds, $data['new_status']);
        $this->sendEmailContent($decryptedFolderIds, $data['new_status']);

        $tsJobId = $this->getDecryptData($data['JobId']);

        $incompleteFolders = $this->findIncompletedFolders($tsJobId);

        if ($incompleteFolders === 0) {
            $this->getJobService()->updateJobStatus($tsJobId, $this->statusService->completed); 
        }

        return ['success' => true];
    }

    public function updateFolderData($folderIds, $field, $value)
    {
        return Folder::whereIn('ts_folder_id', $folderIds)
        ->update([$field => $value]);
    }

    public function updatePrincipal($keepFolder, $principal)
    {
        return $keepFolder->update(['principal' =>  $principal]);
    }

    public function updateDeputy($keepFolder, $deputy)
    {
        return $keepFolder->update(['deputy' =>  $deputy]);
    }

    public function updateTeacher($keepFolder, $teacher)
    {
        return $keepFolder->update(['teacher' =>  $teacher]);
    }
}
