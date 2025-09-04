<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\JobService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Auth;

class ReviewStatusController extends Controller
{
     
    protected $encryptDecryptService;
    protected $jobService;

    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, StatusService $statusService, FolderService $folderService){
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->statusService = $statusService;
        $this->folderService = $folderService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function changeStatus($hash){
        $decryptedJobKey = $this->getDecryptData($hash);
        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->with('reviewStatuses')->first();
        $reviewStatusesNewList = $this->statusService->getDataById([$this->statusService->unlocked, $this->statusService->completed])->pluck('status_external_name', 'id')
            ->sortByDesc(function ($statusExternalName, $statusId) {
                return $statusExternalName;
            });
        $selectedFolders = $this->folderService->getFolderByJobId($selectedJob->ts_job_id)->with('reviewStatuses')->orderBy('ts_foldername', 'asc')->get();
        $user = Auth::user();
        return view('proofing.franchise.change-status', [
            'selectedJob' => $selectedJob,
            'selectedFolders' => $selectedFolders,
            'reviewStatusesNewList' => $reviewStatusesNewList,
            'completeStatus' => $this->statusService->completed,
            'locked' => $this->statusService->locked,
            'archived' => $this->statusService->archived,
            'user' => new UserResource($user)
        ]);
    }

    public function updateFolderStatus(Request $request)
    {
        $this->folderService->proofingFolderStatus($request->all());
        return response()->json(['message' => 'Folder statuses updated successfully.']);
    }

    public function updateJobStatus(Request $request)
    {
        $decryptedJobId = $this->getDecryptData($request->JobId);
        $this->jobService->updateJobStatus($decryptedJobId, $request->ChangeStatus);
        return response()->json(['message' => 'Job statuses updated successfully.']);
    }

}
