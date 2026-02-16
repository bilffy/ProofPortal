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
    protected $subjectService;             
    protected $proofingDescriptionService; 
    protected $emailService;

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
        $proofingIssueID = $this->proofingDescriptionService
            ->getAllProofingDescriptionByIssueName('TRADITIONAL PHOTO', 'id')->id;
    
        // Attempt to get active group positions
        $groupPositions = $this->getProofingChangelogService()
            ->getGroupPositionData($folderKey, $proofingIssueID, $this->statusService->active);
    
        // If not found, attempt inactive
        if (empty($groupPositions)) {
            $groupPositions = $this->getProofingChangelogService()
                ->getGroupPositionData($folderKey, $proofingIssueID, $this->statusService->inactive);
        }
    
        if (!$groupPositions || empty($groupPositions->change_to)) {
            return [];
        }
    
        $groupValue = $groupPositions->change_to;
        $groupData = json_decode($groupValue, true);
        $finalGroups = [];
    
        foreach ($groupData as $row => $subjects) {
            if (!is_array($subjects)) continue; // Safety check
    
            $isAbsent = stripos($row, 'Absent') !== false;
            $rowKey = $isAbsent ? 'Absent' : $row;
    
            $finalGroups[$rowKey] = [];
    
            foreach ($subjects as $subjectName) {
                if (!empty($subjectName)) {
                    $finalGroups[$rowKey][] = $subjectName;
                }
            }
        }
    
        // Optional: sort so 'Absent' always comes last
        if (isset($finalGroups['Absent'])) {
            $absent = $finalGroups['Absent'];
            unset($finalGroups['Absent']);
            $finalGroups['Absent'] = $absent;
        }
    
        return [
            'groupDetails' => $finalGroups,
            'groupValue' => $groupValue,
            'groupNotes' => $groupPositions->notes ?? null
        ];
    }    

    public function getSubjectIDByName($folderKey, $data) {
        // Retrieve folder and job IDs
        $currentFolder = $this->getFolderByKey($folderKey)->select('ts_folder_id', 'ts_job_id', 'show_prefix_suffix_groups', 'show_salutation_groups')->first();
    
        // Check if $currentFolder is found
        if (!$currentFolder) {
            return ['error' => 'Folder not found.'];
        }
    
        // Get subjects
        // $homedSubjects = $this->subjectService->getAllHomedSubjectsByFolderID($currentFolder->ts_folder_id);
        // $attachedSubjects = $this->subjectService->getAllAttachedSubjectsByFolderID($currentFolder->ts_folder_id);
        // $allSubjects = $attachedSubjects->merge($homedSubjects);
        $allSubjects = $this->subjectService->getSubjectByJobId($currentFolder->job->ts_job_id)->get();

        $useSalutation = $currentFolder->show_salutation_groups;
        $usePrefixSuffix = $currentFolder->show_prefix_suffix_groups;
    
        // Initialize result array
        $result = [];
        $formattedResult = [];
        
        // Process each group of names
        foreach ($data as $rowLabel => $names) {
            $result[$rowLabel] = [];
            $formattedResult[$rowLabel] = [];
            
            // Iterate through each name in the current group
            foreach ($names as $name) {
                // Find the subject in the merged collection

                $subject = $allSubjects->first(function ($item) use ($name, $useSalutation, $usePrefixSuffix) {
                    // Build the display name exactly as system uses
                    $salutation = trim($item->salutation ?? '');
                    $prefix = trim($item->prefix ?? '');
                    $suffix = trim($item->suffix ?? '');
                    $firstname = trim($item->firstname ?? '');
                    $lastname = trim($item->lastname ?? '');
    
                    $parts = [];
                    if ($useSalutation && $salutation !== '') $parts[] = $salutation;
                    if ($usePrefixSuffix && $prefix !== '') $parts[] = $prefix;
                    $parts[] = $firstname;
                    $parts[] = $lastname;
                    if ($usePrefixSuffix && $suffix !== '') $parts[] = $suffix;
    
                    $fullName = implode(' ', array_filter($parts, fn($v) => $v !== ''));

                    return $fullName === $name;
                });
    
                // Add result
                if ($subject) {
                    $result[$rowLabel][] = $subject->ts_subjectkey . ':' . $name;
                    $formattedResult[$rowLabel][] = "SUBJECTKEY: " . $subject->ts_subjectkey;
                } else {
                    $result[$rowLabel][] = '--Not Found--:' . $name;
                    $formattedResult[$rowLabel][] = "NAME: " . $name;
                }
            }
        }
    
        // Convert result to JSON and handle errors
        $jsonData = json_encode($result);
        $jsonformattedResult = json_encode($formattedResult);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'JSON encoding error: '.json_last_error_msg()];
        }
    
        // Ensure jobKey exists
        $jobKey = isset($currentFolder->job) ? $currentFolder->job->ts_jobkey : 'Unknown Job Key';
    
        return [
            'jsonData' => $jsonData,
            'jsonformattedResult' => $jsonformattedResult,
            'jobKey' => $jobKey
        ];
    }

    /**
     * Counts folders in a job that do NOT have the specified status.
     * TNJ NOT FOUND folders are excluded from this count as they are considered inactive.
     */
    public function countFoldersByNotStatus($tsJobId, $statusId)
    {
        return Folder::where('ts_job_id', $tsJobId)
            ->where('status_id', '!=', $statusId)
            ->where('status_id', '!=', $this->statusService->tnjNotFound)
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
    
        $newStatusId = (int) $data['new_status'];
        
        // Fetch current folder statuses to identify which ones are actually changing
        $folders = \App\Models\Folder::whereIn('ts_folder_id', $decryptedFolderIds)
            ->select('ts_folder_id', 'status_id')
            ->get();
            
        $changingFolderIds = [];
        foreach ($folders as $folder) {
            if ($folder->status_id != $newStatusId) {
                $changingFolderIds[] = $folder->ts_folder_id;
            }
        }

        $this->updateFolderStatus($decryptedFolderIds, $newStatusId);
        
        // Only send emails for folders that actually changed status
        if (!empty($changingFolderIds)) {
            $this->sendEmailContent($changingFolderIds, $newStatusId);
        }

        $tsJobId = $this->getDecryptData($data['JobId']);

        // If all active folders are now Completed, mark the Job as Completed
        if ($newStatusId == $this->statusService->completed) {
            if ($this->countFoldersByNotStatus($tsJobId, $this->statusService->completed) === 0) {
                $this->getJobService()->updateJobStatus($tsJobId, $this->statusService->completed); 
            }
        } 
        // If all active folders are now Unlocked, mark the Job as Unlocked
        elseif ($newStatusId == $this->statusService->unlocked) {
            if ($this->countFoldersByNotStatus($tsJobId, $this->statusService->unlocked) === 0) {
                $this->getJobService()->updateJobStatus($tsJobId, $this->statusService->unlocked); 
            }
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
