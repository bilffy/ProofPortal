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
    protected $statusService;
    protected $folderService;

    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, StatusService $statusService, FolderService $folderService){
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->statusService = $statusService;
        $this->folderService = $folderService;
    }

    private function getDecryptData($data){
        return $this->encryptDecryptService->decryptStringMethod($data);
    }

    public function changeStatus($hash){
        $decryptedJobKey = $this->getDecryptData($hash);
        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->with('reviewStatuses')->first();
        
        if (!$selectedJob) {
            abort(404); 
        }
        
        // Filter out null IDs if some statuses are not defined in the database
        $statusIds = array_filter([$this->statusService->unlocked, $this->statusService->completed]);
        
        $reviewStatusesNewList = $this->statusService->getDataById($statusIds)
            ->pluck('status_external_name', 'id')
            ->sortBy('status_external_name'); // Alphabetical sort is usually preferred over descending name sort unless specified
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
        // Cast ChangeStatus to integer to ensure strict comparisons (===) in services work correctly
        $this->jobService->updateJobStatus($decryptedJobId, (int) $request->ChangeStatus);
        return response()->json(['message' => 'Job statuses updated successfully.']);
    }

}
