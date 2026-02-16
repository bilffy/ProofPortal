<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\ProofingDescriptionService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\SubjectService;
use App\Services\Proofing\JobService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Auth;

class SubjectChangesController extends Controller
{
    
    protected $encryptDecryptService;
    protected $proofingChangelogService;
    protected $jobService;
    protected $folderService;
    protected $subjectService;
    protected $proofingDescriptionService;

    public function __construct(JobService $jobService, ProofingChangelogService $proofingChangelogService, EncryptDecryptService $encryptDecryptService, FolderService $folderService, SubjectService $subjectService, ProofingDescriptionService $proofingDescriptionService)
    {
        $this->proofingChangelogService = $proofingChangelogService;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->folderService = $folderService;
        $this->subjectService = $subjectService;
        $this->proofingDescriptionService = $proofingDescriptionService;
        $this->jobService = $jobService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function approveChange($hash){
        $decryptedJobKey = $this->getDecryptData($hash);
        if (!$decryptedJobKey) abort(404);

        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first(); 
        
        if (!$selectedJob) {
            abort(404); 
        }

        $allFolders = $this->folderService->getFolderByJobId($selectedJob->ts_job_id)->orderBy('ts_foldername', 'asc')->get();
        $subjectData = $this->proofingChangelogService->getAllApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
        $folderChanges = $this->proofingChangelogService->getFolderGeneralChangeByJobKey($selectedJob->ts_jobkey);
        $user = Auth::user();
        return view('proofing.franchise.view-changes.approved-changes', 
        [
            'subjectChanges' => $subjectData['subjectChanges'],
            'attachedFolderNames' => $subjectData['subjectsFolderList'],
            'selectedJob' => $selectedJob,
            'allFolders' => $allFolders, 
            'folderChanges' => $folderChanges,
            'user' => new UserResource($user)
        ]);
    }

    public function awaitApproveChangeFranchise($hash){
        $decryptedJobKey = $this->getDecryptData($hash);
        if (!$decryptedJobKey) abort(404);

        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first();
        
        if (!$selectedJob) {
            abort(404); 
        }

        $allFolders = $this->folderService->getFolderByJobId($selectedJob->ts_job_id)->get();
        $subjectData = $this->proofingChangelogService->getAllAwaitApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
        $folderChanges = $this->proofingChangelogService->getFolderGeneralChangeByJobKey($selectedJob->ts_jobkey);
        $user = Auth::user();
        return view('proofing.franchise.view-changes.await-approve-changes-franchise', [
            'subjectChanges' => $subjectData['subjectChanges'], 
            'attachedFolderNames' => $subjectData['subjectsFolderList'], 
            'selectedJob' => $selectedJob, 
            'allFolders' => $allFolders, 
            'folderChanges' => $folderChanges,
            'user' => new UserResource($user)
        ]);
    }

    public function awaitApproveChangeCoordinator($hash){
        $decryptedJobKey = $this->getDecryptData($hash);
        if (!$decryptedJobKey) abort(404);

        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->first();
        
        if (!$selectedJob) {
            abort(404); 
        }
        
        $subQuerySubjects = $this->subjectService->getByJobId($selectedJob->ts_job_id, 'ts_folder_id')->distinct()->pluck('ts_folder_id')->toArray();
        $allFolders = $this->folderService->getHomeFolders($subQuerySubjects, 'folders.id', 'folders.ts_foldername')->get();
        $subjectData = $this->proofingChangelogService->getAllAwaitApprovedSubjectChangeByJobKey($selectedJob->ts_jobkey);
        $folderChanges = $this->proofingChangelogService->getFolderGeneralChangeByJobKey($selectedJob->ts_jobkey);
        $pictureissueID = $this->proofingDescriptionService->getAllProofingDescriptionByIssueName('SUBJECT_ISSUE_PICTURE', 'id')->id;
        $folderissueID = $this->proofingDescriptionService->getAllProofingDescriptionByIssueName('SUBJECT_ISSUE_CLASS', 'id')->id;
        $user = Auth::user();
        return view('proofing.franchise.view-changes.await-approve-changes-coordinator', [
            'folderissueID' => $folderissueID, 
            'pictureissueID' => $pictureissueID, 
            'subjectChanges' => $subjectData['subjectChanges'], 
            'attachedFolderNames' => $subjectData['subjectsFolderList'], 
            'selectedJob' => $selectedJob, 
            'allFolders' => $allFolders, 
            'folderChanges' => $folderChanges,
            'user' => new UserResource($user)
        ]);
    }

    public function submitApproveChangeCoordinator(Request $request, $hash){
        $request->validate([
            'action' => 'required|in:modify,approve,reject',
            'subject_correction_id' => 'required|integer',
        ]);

        if($request->action == 'modify'){
            $this->proofingChangelogService->insertSubjectProofingChangeLog($request->all());
            $this->proofingChangelogService->modifyProofingChangelogById($request->subject_correction_id);
        }else if($request->action == 'approve'){
            $this->proofingChangelogService->approveProofingChangelogById($request->subject_correction_id);
        }else if($request->action == 'reject'){
            $this->proofingChangelogService->rejectProofingChangelogById($request->subject_correction_id);
        }
        return response()->json(['success' => true, 'action' => $request->action]);
    }
    
}
