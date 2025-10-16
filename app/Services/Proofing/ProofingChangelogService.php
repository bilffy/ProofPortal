<?php

namespace App\Services\Proofing;
use App\Models\ProofingChangelog;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\SubjectService;
use App\Services\Proofing\FolderSubjectService;
use App\Services\Proofing\ProofingDescriptionService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Config;
use App\Models\Folder;
use Illuminate\Support\Carbon;
use Auth;

class ProofingChangelogService
{    
    protected $folderService;
    protected $subjectService;
    protected $proofingDescriptionService;
    protected $encryptDecryptService;
    protected $statusService;

    protected function getGroupPositionService()
    {
        return app(GroupPositionService::class);
    }


    public function __construct(FolderService $folderService, SubjectService $subjectService, ProofingDescriptionService $proofingDescriptionService, StatusService $statusService, EncryptDecryptService $encryptDecryptService, FolderSubjectService $folderSubjectService)
    {
        $this->folderService = $folderService;
        $this->subjectService = $subjectService;
        $this->proofingDescriptionService = $proofingDescriptionService;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->statusService = $statusService;
        $this->folderSubjectService = $folderSubjectService;
    }

    public function getAllProofingChangelogFolder($jobkey, $folderkey)
    {
        return ProofingChangelog::with('issue')->where([['ts_jobkey',$jobkey],['keyvalue', $folderkey]]);
    }

    public function getAllProofingChangelogBySubjectkey($subjectkey)
    {
        return ProofingChangelog::with('statuses')->where('keyvalue', $subjectkey);
    }

    public function deleteChangelog($changelogIdsToDelete)
    {
        return ProofingChangelog::whereIn('id', $changelogIdsToDelete)->delete();
    }

    public function getGroupPositionData($folderKey, $proofingIssueID, $status)
    {
        return ProofingChangelog::where([
            ['keyorigin', 'Folder'],
            ['keyvalue', $folderKey],
            ['issue_id', $proofingIssueID],
            ['resolved_status_id', $status]
        ])->orderBy('change_datetime', 'desc')->value('change_to');
    }

    public function approveProofingChangelogById($id)
    {
        $proofingChangelog = ProofingChangelog::find($id);
        $proofingChangelog->resolved_status_id = $this->statusService->active;
        $proofingChangelog->approvalStatus = $this->statusService->approved;
        $proofingChangelog->decision_datetime = Carbon::now();
        $proofingChangelog->save();
        return;
    }

    public function rejectProofingChangelogById($id)
    {
        $proofingChangelog = ProofingChangelog::find($id);
        $proofingChangelog->resolved_status_id = $this->statusService->inactive;
        $proofingChangelog->approvalStatus = $this->statusService->rejected;
        $proofingChangelog->decision_datetime = Carbon::now();
        $proofingChangelog->save();
        return;
    }

    public function modifyProofingChangelogById($id)
    {
        $proofingChangelog = ProofingChangelog::find($id);
        $proofingChangelog->resolved_status_id = $this->statusService->active;
        $proofingChangelog->approvalStatus = $this->statusService->modified;
        $proofingChangelog->decision_datetime = Carbon::now();
        $proofingChangelog->save();
        return;
    }

    public function getAllApprovedSubjectChangeByJobKey($jobKey)
    {
        $subjectChanges = ProofingChangelog::join('issues', 'issues.id', '=', 'changelogs.issue_id')
            ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
            ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
            ->where([
                ['keyorigin', 'Subject'],
                ['ts_jobkey', $jobKey],
                ['resolved_status_id', $this->statusService->active]
            ])
            ->select(
                'subjects.firstname', 
                'subjects.lastname', 
                'subjects.ts_subjectkey', 
                'subjects.ts_subject_id', 
                'folders.ts_folderkey', 
                'folders.ts_foldername', 
                'issues.external_issue_name', 
                'changelogs.notes', 
                'changelogs.user_id', 
                'changelogs.change_datetime'
            )
            ->orderBy('changelogs.change_datetime', 'ASC')
            ->get();
            
        $subjectsFolderList = $this->getAllFolderList($subjectChanges);

        return [
            'subjectsFolderList' => $subjectsFolderList, 
            'subjectChanges' => $subjectChanges
        ];

    }

    public function getAllAwaitApprovedSubjectChangeByJobKey($jobKey){
        $subjectChanges = ProofingChangelog::join('issues', 'issues.id', '=', 'changelogs.issue_id')
        ->join('subjects', 'subjects.ts_subjectkey', '=', 'changelogs.keyvalue')
        ->join('folders', 'folders.ts_folder_id', '=', 'subjects.ts_folder_id')
        ->where([
            ['keyorigin', 'Subject'],
            ['ts_jobkey', $jobKey],
            ['resolved_status_id', $this->statusService->inactive],
            ['approvalStatus', $this->statusService->awaitingApproval]
        ])
        ->select(
            'subjects.firstname', 
            'subjects.lastname', 
            'subjects.ts_subjectkey', 
            'subjects.ts_subject_id', 
            'folders.ts_folderkey', 
            'folders.ts_foldername', 
            'issues.external_issue_name', 
            'changelogs.id', 
            'changelogs.notes', 
            'changelogs.user_id', 
            'changelogs.change_datetime'
        )
        ->orderBy('changelogs.change_datetime', 'ASC')
        ->get();

        $subjectsFolderList = $this->getAllFolderList($subjectChanges);

        return [
            'subjectsFolderList' => $subjectsFolderList, 
            'subjectChanges' => $subjectChanges
        ];
    }

    public function getAllFolderList($subjectChanges){
        $subjectsFolderList = [];
        
        foreach ($subjectChanges as $subjectChange) {
            // Initialize array if it doesn't exist
            if (!isset($subjectsFolderList[$subjectChange->ts_subjectkey])) {
                $subjectsFolderList[$subjectChange->ts_subjectkey] = ['names' => [], 'keys' => []];
            }
    
            // Add the current folder to the list
            $subjectsFolderList[$subjectChange->ts_subjectkey]['names'][] = $subjectChange->ts_foldername;
            $subjectsFolderList[$subjectChange->ts_subjectkey]['keys'][] = $subjectChange->ts_folderkey;
    
            // Fetch attached folders for the subject
            $attachedFolders = $this->folderSubjectService->getAttachedFolders($subjectChange->ts_subject_id);
    
            foreach ($attachedFolders as $attachedFolder) {
                $subjectsFolderList[$subjectChange->ts_subjectkey]['names'][] = $attachedFolder->ts_foldername;
                $subjectsFolderList[$subjectChange->ts_subjectkey]['keys'][] = $attachedFolder->ts_folderkey;
            }
    
            // Make names and keys unique
            $subjectsFolderList[$subjectChange->ts_subjectkey]['names'] = array_unique($subjectsFolderList[$subjectChange->ts_subjectkey]['names']);
            $subjectsFolderList[$subjectChange->ts_subjectkey]['keys'] = array_unique($subjectsFolderList[$subjectChange->ts_subjectkey]['keys']);
        }
        return $subjectsFolderList;
    }
    
    public function getFolderGeneralChangeByJobKey($jobKey){
        return ProofingChangelog::join('issues', 'issues.id', '=', 'changelogs.issue_id')
        ->join('folders', 'folders.ts_folderkey', '=', 'changelogs.keyvalue')
        ->whereIn('issues.issue_name', ['FOLDER_NAME_CHANGE', 'GENERAL_ISSUES'])
        ->where('ts_jobkey', $jobKey)
        ->orderBy('issues.id', 'ASC')
        ->select(
            'folders.ts_foldername', 
            'folders.ts_folderkey', 
            'issues.external_issue_name', 
            'changelogs.id', 
            'changelogs.notes', 
            'changelogs.user_id', 
            'changelogs.change_datetime'
        )
        ->get();
    }

    public function getAllChangelogsByJobkeyExceptTraditional($jobkey) {
        return ProofingChangelog::where('notes', 'NOT LIKE', 'Traditional Photo People Row Positions for Folder%')
            ->where('ts_jobkey', $jobkey);
    }    

    public function insertFolderProofingChangeLog($decryptedFolderKey, $issue, $note, $newValue) {
        $folderData = $this->folderService->getFolderByKey($decryptedFolderKey)
        ->with(['job' => function($query) {
            $query->select('ts_job_id', 'ts_jobkey'); // Select columns from the jobs table
        }])
        ->select('ts_folderkey', 'ts_job_id', 'ts_foldername', 'id')->first(); // Select columns from the folders table

        if (!$folderData || !$folderData->job) {
            return;
        }
        $currentValue = '';
        $constants = Config::get('constants');        
        $textValue = ($newValue === "1") ? 'Yes' : (($newValue === "0") ? 'No' : null);
        $isResolved = ($newValue === "1") ? $this->statusService->active : (($newValue === "0") ? $this->statusService->inactive : null);
        $replace = [];
        switch ($issue) {
            case $constants['FOLDER_NAME_CHANGE']:
                $replace = ['CHANGEFROM' => $folderData->ts_foldername, 'CHANGETO' => $newValue];
                $currentValue = $folderData->ts_foldername;
                $isResolved =  $this->statusService->active;
                $keyOrigin =  'Folder';
                $folder = $this->folderService->findFolderId($folderData->id);
                $folder->ts_foldername = $newValue;
                $folder->save();
                break;
            case $constants['FOLDER_BELONG_SUBJECTS']:
                $replace = ['FOLDER' => $folderData->ts_foldername, 'VALUE' => $textValue];
                $keyOrigin =  'Folder';
                break;
            case $constants['SUBJECT_MISSING_NAMES']:
                $keyOrigin =  'Folder';
            case $constants['GENERAL_ISSUES']:
                $replace = ['DATA' => $newValue];
                $keyOrigin =  'Folder';
                $isResolved = $this->statusService->active;
                break;
            case $constants['TRADITIONAL_PHOTO_TAGGED']:
                $replace = ['VALUE' => $textValue];
                $keyOrigin =  'Group';
                break;
            case $constants['DEPUTY']:
                $folder = $this->folderService->findFolderId($folderData->id);
                if(isset($folder->deputy) && isset($newValue)){
                    $currentValue = $folder->deputy;
                    $replace = ['OLDVALUE' => $currentValue, 'NEWVALUE' => $newValue];
                }elseif(empty($currentValue) && isset($newValue)){
                   $replace = ['VALUE' => $newValue]; 
                }
                $isResolved =  $this->statusService->active;
                $folder->deputy = $newValue;
                $folder->save();
                $keyOrigin =  'Group';
                break;
            case $constants['TEACHER']:
                $folder = $this->folderService->findFolderId($folderData->id);
                if(isset($folder->teacher) && isset($newValue)){
                    $currentValue = $folder->principal;
                    $replace = ['OLDVALUE' => $currentValue, 'NEWVALUE' => $newValue];
                }elseif(empty($currentValue) && isset($newValue)){
                   $replace = ['VALUE' => $newValue]; 
                }
                $isResolved =  $this->statusService->active;
                $folder->teacher = $newValue;
                $folder->save();
                $keyOrigin =  'Group';
                break;
            case $constants['PRINCIPAL']:
                if(isset($folder->principal) && isset($newValue)){
                    $currentValue = $folder->principal;
                    $replace = ['OLDVALUE' => $currentValue, 'NEWVALUE' => $newValue];
                }elseif(empty($currentValue) && isset($newValue)){
                   $replace = ['VALUE' => $newValue]; 
                }
                $isResolved =  $this->statusService->active;
                $folder = $this->folderService->findFolderId($folderData->id);
                $folder->principal = $newValue;
                $folder->save();
                $keyOrigin =  'Group';
                break;
            default:
                $replace = ['VALUE' => $textValue];
                $keyOrigin = 'Folder';
                break;
        }

        $changeNote = str_replace(array_keys($replace), $replace, $note);
        ProofingChangelog::insert([
            'ts_jobkey' => $folderData->job->ts_jobkey,
            'keyorigin' => $keyOrigin,
            'keyvalue' => $folderData->ts_folderkey,
            'change_from' => $currentValue,
            'change_to' => $newValue,
            'notes' => $changeNote,
            'user_id' => Auth::user()->id,
            'issue_id' => $this->proofingDescriptionService->getAllProofingDescriptionByDescription($issue, 'id')->id,
            'change_datetime' => Carbon::now(),
            'resolved_status_id' => $isResolved
        ]);
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function insertSubjectProofingChangeLog($data)
    {
        $requestData = collect($data);

        $decryptedSubjectKey = $this->getDecryptData($requestData['subject_key_encrypted']);

        $subjectData = $this->subjectService->getBySubjectKey(
            $decryptedSubjectKey,
            'firstname',
            'lastname',
            'title',
            'salutation',
            'ts_subjectkey',
            'ts_job_id',
            'ts_folder_id',
            'id'
        )->with([
            'job' => function ($query) {
                $query->select('ts_job_id', 'ts_jobkey');
            },
            'folder' => function ($query) {
                $query->select('id','ts_folder_id', 'ts_foldername', 'ts_folderkey');
            }
        ])->first();

        if (!$subjectData) return;
   
        $currentValue = $newValue = $approvalStatus = $isResolved = $note = $result = '';
        $currentfirstname = $subjectData->firstname;
        $currentlastname = $subjectData->lastname;
        $replace = [];
        $responseData = [];
        
        if($requestData->has('issue')){
            if ($currentfirstname != $requestData['new_first_name'] || $currentlastname != $requestData['new_last_name']) {
                $issue = 'SUBJECT_ISSUE_SPELLING';
            }elseif ($subjectData->title != $requestData->get('new_title') || $subjectData->salutation != $requestData['new_salutation']) {
                $issue = 'SUBJECT_ISSUE_JOBTITLE_SALUTATION';
            }
            $proofingDescription = $this->proofingDescriptionService->getAllProofingDescriptionByIssueName($issue, 'issue_description', 'issue_category_id', 'id');
            $issueId = $proofingDescription->id;
        }else{
            $proofingDescription = $this->proofingDescriptionService->getAllProofingDescriptionById($requestData['subjects_questions'], 'issue_description', 'issue_category_id');
            $issueId = $requestData['subjects_questions'];
        }
        $constants = Config::get('constants');
        $description = $proofingDescription->issue_description;
            
        $issue = array_search($description, $constants); 

        if (!$issue) return;
        
        $subject = $this->subjectService->getSubjectById($subjectData->id);

        switch ($issue) {
            case 'SUBJECT_ISSUE_SPELLING':
                $currentValue = "{$currentfirstname} {$currentlastname}";
                $newValue = "{$requestData['new_first_name']} {$requestData['new_last_name']}";
                $approvalStatus = $this->statusService->autoApproved;
                $isResolved = $this->statusService->active;
                $note = "{$issue}_NOTE";
                $replace = [
                    'OLDFIRSTNAME' => $currentfirstname,
                    'OLDLASTNAME' => $currentlastname,
                    'NEWFIRSTNAME' => $requestData['new_first_name'],
                    'NEWLASTNAME' => $requestData['new_last_name']
                ];
                $subject->firstname = $requestData['new_first_name'];
                $subject->lastname = $requestData['new_last_name'];

                $groupsName = ProofingChangelog::where([
                    ['ts_jobkey', $subjectData->job->ts_jobkey],
                    ['keyvalue', $subjectData->folder->ts_folderkey],
                    ['notes', 'LIKE', 'Traditional Photo People Row Positions for Folder%']
                ])
                ->select('change_to','id')
                ->orderBy('id', 'DESC')->first();

                if(isset($groupsName)){
                    // Perform the string replacement
                    $updatedChangeTo = str_replace(
                        [$currentfirstname .' '. $currentlastname],
                        [$requestData['new_first_name'] .' '. $requestData['new_last_name']],
                        $groupsName->change_to
                    );
                    // Update the 'change_to' field
                    $groupsName->change_to = $updatedChangeTo;
                    $groupsName->save(); // Save the changes to the database
                }

                $this->getGroupPositionService()->updateGroupPosition($subjectData->job->ts_jobkey, $subjectData->folder->ts_folderkey, $currentfirstname, $currentlastname, $requestData['new_first_name'], $requestData['new_last_name']);
                
                try{
                    $result = $subject->save();
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $result = false;
                }
                if ($result) {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections have been saved. Make another correction or close."),
                            "full_name" => "<strong>{$subject->firstname} {$subject->lastname}</strong >",
                            "alert" => "success"
                    ];
                } else {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections could not be saved. Please try again."),
                            "full_name" => "<strong>{$subject->firstname} {$subject->lastname}</strong >",
                            "alert" => "danger"
                    ];
                }
                break;
            case 'SUBJECT_ISSUE_PICTURE':
                $isResolved = isset($requestData['action']) ? $this->statusService->active : $this->statusService->inactive;
                $approvalStatus = isset($requestData['action']) ? $this->statusService->approved : $this->statusService->awaitingApproval;
                if(!empty($requestData['picture_issue'])){
                    $note = isset($requestData['picture_issue']) ? "{$issue}_EXIST_NOTE" : "{$issue}_NOTEXIST_NOTE";
                    $newValue = $requestData['picture_issue'];
                    $replace = [
                        'FIRSTNAME' => $currentfirstname,
                        'LASTNAME' => $currentlastname,
                        'VALUE' => $requestData['picture_issue'] ?? ''
                    ];
                }else{
                    $note = 'Picture is not of '.$currentfirstname.' '.$currentlastname.'. I\'m not sure who it belongs to.';
                    $newValue = '';
                }
                break;
            case 'SUBJECT_ISSUE_CLASS':
                $currentValue = "Folder From: ".$subjectData->folder->id;
                $newValue = "Folder To: ".$requestData['folder_issue'];
                $isResolved = isset($requestData['action']) ? $this->statusService->active : $this->statusService->inactive;
                $approvalStatus = isset($requestData['action']) ? $this->statusService->approved : $this->statusService->awaitingApproval;
                $newFolder = isset($requestData['folder_issue']) ? $this->folderService->getFolderById($requestData['folder_issue'])->select('ts_foldername')->first() : null;
                $note = isset($requestData['folder_issue']) ? "{$issue}_EXIST_NOTE" : "{$issue}_NOTEXIST_NOTE";
                $replace = [
                    'FIRSTNAME' => $currentfirstname,
                    'LASTNAME' => $currentlastname,
                    'CURRENTFOLDER' => $subjectData->folder->ts_foldername,
                    'NEWFOLDER' => $newFolder->ts_foldername ?? ''
                ];
                break;
            case 'SUBJECT_ISSUE_LEFT_SCHOOL':
                $isResolved = $this->statusService->inactive;  
                $approvalStatus = $this->statusService->awaitingApproval;
                $note = "{$issue}_NOTE";
                $replace = [
                    'FIRSTNAME' => $currentfirstname,
                    'LASTNAME' => $currentlastname,
                    'CURRENTFOLDER' => $subjectData->folder->ts_foldername
                ];
                break;
            case 'SUBJECT_ISSUE_JOBTITLE_SALUTATION':
                $currentValue = "[Title: {$subjectData->title}] [Salutation: {$subjectData->salutation}]";
                $newValue = "[Title: {$requestData['new_title']}] [Salutation: {$requestData['new_salutation']}]";
                $approvalStatus = $this->statusService->autoApproved;
                $isResolved = $this->statusService->active;
                $note = 'SUBJECT_ISSUE_JOBTITLE_SALUTATION_NOTE';
                $replace = [
                    'TITLEFROM' => $subjectData->title,
                    'SALUTATIONFROM' => $subjectData->salutation,
                    'TITLETO' => $requestData['new_title'],
                    'SALUTATIONTO' => $requestData['new_salutation']
                ];
                if ($requestData->has('new_title')) {
                    if ($requestData->get('new_title') !== null) {
                        $titleValue = $requestData->get('new_title');
                    } else {
                        $titleValue = '';
                    }
                }
                if ($requestData->has('new_salutation')) {
                    if ($requestData->get('new_salutation') !== null) {
                        $salutationValue = $requestData->get('new_salutation');
                    } else {
                        $salutationValue = '';
                    }
                }
                $title = $titleValue ?? $subjectData->title;
                $salutation = $salutationValue ?? $subjectData->salutation;
                $subject->title = $title;
                $subject->salutation = $salutation;
                try{
                    $result = $subject->save();
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $result = false;
                }
                
                if ($result) {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections have been saved. Make another correction or close."),
                            "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong >",
                            "alert" => "success"
                    ];
                } else {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections could not be saved. Please try again."),
                            "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong >",
                            "alert" => "danger"
                    ];
                }
                break;
            case 'SUBJECT_ISSUE_SALUTATION':
                $currentValue = "[Salutation: {$subjectData->salutation}]";
                $newValue = "[Salutation: {$requestData['new_salutation']}]";
                $approvalStatus = $this->statusService->autoApproved;
                $isResolved = $this->statusService->active;
                $note = 'SUBJECT_ISSUE_SALUTATION_NOTE';
                $replace = [
                    'SALUTATIONFROM' => $subjectData->salutation,
                    'SALUTATIONTO' => $requestData['new_salutation']
                ];
                if ($requestData->has('new_salutation')) {
                    if ($requestData->get('new_salutation') !== null) {
                        $salutationValue = $requestData->get('new_salutation');
                    } else {
                        $salutationValue = '';
                    }
                }
                $salutation = $salutationValue ?? $subjectData->salutation;
                $subject->salutation = $salutation;
                try{
                    $result = $subject->save();
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $result = false;
                }
                
                if ($result) {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections have been saved. Make another correction or close."),
                            "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong >",
                            "alert" => "success"
                    ];
                } else {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections could not be saved. Please try again."),
                            "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong >",
                            "alert" => "danger"
                    ];
                }
                break;
            case 'SUBJECT_ISSUE_JOBTITLE':
                $currentValue = "[Title: {$subjectData->title}]";
                $newValue = "[Title: {$requestData['new_title']}]";
                $approvalStatus = $this->statusService->autoApproved;
                $isResolved = $this->statusService->active;
                $note = 'SUBJECT_ISSUE_JOBTITLE_NOTE';
                $replace = [
                    'TITLEFROM' => $subjectData->title,
                    'TITLETO' => $requestData['new_title']
                ];
                if ($requestData->has('new_title')) {
                    if ($requestData->get('new_title') !== null) {
                        $titleValue = $requestData->get('new_title');
                    } else {
                        $titleValue = '';
                    }
                }
                $title = $titleValue ?? $subjectData->title;
                $subject = $this->subjectService->getSubjectById($subjectData->id);
                $subject->title = $title;
                try{
                    $result = $subject->save();
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $result = false;
                }
                
                if ($result) {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections have been saved. Make another correction or close."),
                            "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong >",
                            "alert" => "success"
                    ];
                } else {
                    $htmlUpdates = [
                            "acknowledge" => __("Corrections could not be saved. Please try again."),
                            "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong >",
                            "alert" => "danger"
                    ];
                }
                break;
            case 'SUBJECT_REMOVE_PICTURE':
                $isResolved = $this->statusService->inactive;
                $approvalStatus = $this->statusService->awaitingApproval;
                $note = 'SUBJECT_REMOVE_PICTURE_NOTE';
                $replace = [
                    'FIRSTNAME' => $currentfirstname,
                    'LASTNAME' => $currentlastname,
                    'FOLDER' => $subjectData->folder->ts_foldername,
                ];
                break;
            case 'SUBJECT_ABSENT':
                $isResolved = $this->statusService->inactive;
                $approvalStatus = $this->statusService->awaitingApproval;
                $note = 'SUBJECT_ABSENT_NOTE';
                $replace = [
                    'FIRSTNAME' => $currentfirstname,
                    'LASTNAME' => $currentlastname,
                    'FOLDER' => $subjectData->folder->ts_foldername,
                ];
                break;
            default:
                break;
        }

        $changeNote = count($replace) > 0 ? str_replace(array_keys($replace), $replace, $constants[$note]) : $note;
        if(!$result){
            try{
                $result = ProofingChangelog::insert([
                    'ts_jobkey' => $subjectData->job->ts_jobkey,
                    'keyvalue' => $subjectData->ts_subjectkey,
                    'keyorigin' => 'Subject',
                    'change_from' => $currentValue,
                    'change_to' => $newValue,
                    'user_id' => Auth::user()->id,
                    'notes' => $changeNote,
                    'issue_id' => $issueId,
                    'resolved_status_id' => $isResolved,
                    'change_datetime' => Carbon::now(),
                    'approvalStatus' => $approvalStatus
                ]);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $result = false;
            }
            
            $htmlUpdates = [
                "acknowledge" => $result ? __("The issue has been logged. Make another correction or close.") : __("Corrections could not be saved. Please try again."),
                "full_name" => "<strong>{$currentfirstname} {$currentlastname}</strong>",
                "alert" => $result ? "success" : "danger"
            ];
        }else{
            ProofingChangelog::insert([
                'ts_jobkey' => $subjectData->job->ts_jobkey,
                'keyvalue' => $subjectData->ts_subjectkey,
                'keyorigin' => 'Subject',
                'change_from' => $currentValue,
                'change_to' => $newValue,
                'user_id' => Auth::user()->id,
                'notes' => $changeNote,
                'issue_id' => $issueId,
                'resolved_status_id' => $isResolved,
                'change_datetime' => Carbon::now(),
                'approvalStatus' => $approvalStatus
            ]);
        }

        $responseData = [];

        if ($requestData->has('picture_issue')) {
            if ($requestData->get('picture_issue') !== null) {
                $responseData['picture'] = $requestData->get('picture_issue');
            } else {
                $responseData['picture'] = '';
            }
        }
        
        if ($requestData->has('folder_issue')) {
            if ($requestData->get('folder_issue') !== null) {
                $responseData['folder'] = $requestData->get('folder_issue');
            } else {
                $responseData['folder'] = '';
            }
        }
        
        if ($requestData->has('new_title')) {
            if ($requestData->get('new_title') !== null) {
                $responseData['title'] = $requestData->get('new_title');
            } else {
                $responseData['title'] = '';
            }
        }
        
        if ($requestData->has('new_salutation')) {
            if ($requestData->get('new_salutation') !== null) {
                $responseData['salutation'] = $requestData->get('new_salutation');
            } else {
                $responseData['salutation'] = '';
            }
        }

        // Always add 'resolved_status_id'
        if($approvalStatus === $this->statusService->awaitingApproval || $approvalStatus === 0){
            $responseData['resolved_status_id'] = 1;
        }elseif($approvalStatus === $this->statusService->autoApproved || $approvalStatus === 1){
            $responseData['resolved_status_id'] = 0;
        }
        
        // Always add 'first_name', 'last_name', 'oldfirst_name', and 'oldlast_name'
        $responseData['first_name'] = $requestData['new_first_name'] ?? $currentfirstname;
        $responseData['last_name'] = $requestData['new_last_name'] ?? $currentlastname;
        $responseData['oldfirst_name'] = $currentfirstname;
        $responseData['oldlast_name'] = $currentlastname;
        $responseData['title_old'] = $subject->title;
        $responseData['salutation_old'] = $subject->salutation;
        // Always add 'htmlUpdates'
        $responseData['htmlUpdates'] = $htmlUpdates;
        
        return $responseData;
    }

    public function insertGroupProofingChangeLog($jobKey, $folderkey, $data) {
        // Retrieve subject IDs and current folder details
        $getData = $this->folderService->getSubjectIDByName($folderkey, $data);
        // Decode JSON data
        $jsonData = json_decode($getData['jsonData'], true);
        
        // Insert into ProofingChangelog
        ProofingChangelog::create([
            'ts_jobkey' => $jobKey,
            'keyorigin' => 'Folder',
            'keyvalue' => $folderkey,
            'change_to' => json_encode($data),
            'user_id' => Auth::user()->id,
            'notes' => 'Traditional Photo People Row Positions for Folder "'.$folderkey.'" have been created.',
            'issue_id' => $this->proofingDescriptionService->getAllProofingDescriptionByIssueName('TRADITIONAL PHOTO', 'id')->id,
            'change_datetime' => Carbon::now(),
            'resolved_status_id' => $this->statusService->active
        ]);
        // Get keys (rowLabels) from jsonData
        $rowLabels = array_keys($jsonData);

        $this->getGroupPositionService()->deleteGroupPosition($folderkey);
        
        // Insert into GroupPosition
        foreach ($rowLabels as $rowIndex => $rowLabel) {
            // Create a new variable for modified row label
            $modifiedRowLabel = 'Absent';

            if(count($rowLabels) < 2){
                if($rowLabel === 'Row_0'){
                    $modifiedRowLabel = 'Back Row';
                }
            } elseif(count($rowLabels) === 2){
                if($rowLabel === 'Row_0'){
                    $modifiedRowLabel = 'Back Row';
                } elseif($rowLabel === 'Row_1'){
                    $modifiedRowLabel = 'Front Row';
                }
            } elseif(count($rowLabels) > 2){
                if($rowLabel === 'Row_0'){
                    $modifiedRowLabel = 'Back Row';
                } elseif($rowLabel === 'Row_'.(count($rowLabels) - 2)){
                    $modifiedRowLabel = 'Front Row';
                }  elseif($rowLabel !== 'Absent'){
                    $modifiedRowLabel = 'Middle Row '.(count($rowLabels) - 2 - $rowIndex) ;
                }
            }

            foreach ($jsonData[$rowLabel] as $index => $subjectKey) {
                $subjectSplit = explode(':' ,$subjectKey);
                $this->getGroupPositionService()->createGroupPosition($getData['jobKey'], $folderkey, $subjectSplit[0], $subjectSplit[1], $modifiedRowLabel, count($rowLabels) - $rowIndex, $index + 1);
            }
        }
    }
}
