<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\ProofingDescriptionService;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\SubjectService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\JobService;
use Illuminate\Support\Facades\Crypt;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\Subject;
use Carbon\Carbon;
use URL;
use Auth;

class ProofController extends Controller
{
    protected $statusService;
    protected $seasonService;
    protected $jobService;
    protected $folderService;
    protected $subjectService;
    protected $proofingDescriptionService;
    protected $proofingChangelogService;
    protected $encryptDecryptService;
    protected $emailService;

    public function __construct(StatusService $statusService, SeasonService $seasonService, JobService $jobService, FolderService $folderService, 
                                SubjectService $subjectService, ProofingDescriptionService $proofingDescriptionService, 
                                ProofingChangelogService $proofingChangelogService, EncryptDecryptService $encryptDecryptService, 
                                EmailService $emailService)
    {
        $this->statusService = $statusService;
        $this->seasonService = $seasonService;
        $this->jobService = $jobService;
        $this->folderService = $folderService;
        $this->subjectService = $subjectService;
        $this->proofingDescriptionService = $proofingDescriptionService;
        $this->proofingChangelogService = $proofingChangelogService;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->emailService = $emailService;
    }

    private function getDecryptData($hash) {
        // Safe check for null before passing to the service
        if (!$hash) return null;
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function MyFoldersList($hash)
    {
        $decryptedJobKey = $this->getDecryptData($hash);
        if (!$decryptedJobKey) {
            return redirect()->route('proofing')->with('error', 'Invalid Job Key');
        }

        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->with(['seasons'])->first();

        if (!$selectedJob) {
            abort(404);
        }

        $selectedSeason = $selectedJob->seasons;
        return $this->renderFolderProofingView($selectedJob, $selectedSeason, $selectedJob->ts_job_id);
    }

    protected function renderFolderProofingView($selectedJob, $selectedSeason, $tsJobId)
    {
        // Eager load subjects and images to prevent N+1 queries
        $selectedFolders = $this->folderService->getFolderByJobId($tsJobId)
            ->with(['images', 'subjects']) 
            ->where('is_visible_for_proofing', 1)
            ->whereHas('folderUsers', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('ts_foldername', 'asc')
            ->get();

        $reviewStatusesColours = $this->statusService->getAllStatusData('id', 'status_external_name', 'colour_code')->get();
        // Get all unique keyvalues that have changes to avoid repeated DB lookups
        $getChangelog = $this->proofingChangelogService->getAllChangelogsByJobkeyExceptTraditional($selectedJob->ts_jobkey)
            ->select('keyvalue')
            ->get();
        $changelogKeysMap = $getChangelog->pluck('keyvalue')->unique()->flip();

        $preparedFolders = $selectedFolders->map(function ($folder) use ($selectedJob, $changelogKeysMap) {
            $hasChanges = false;
            
            // 1. Check if the folder key itself has changes
            if (isset($changelogKeysMap[$folder->ts_folderkey])) {
                $hasChanges = true;
            }

            // 2. Check if subjects in this folder have changes
            if (!$hasChanges) {
                foreach ($folder->subjects as $subject) {
                    if (isset($changelogKeysMap[$subject->ts_subjectkey])) {
                        $hasChanges = true;
                        break;
                    }
                }
            }

            // 3. Check for specific subject-folder-change entries
            if (!$hasChanges) {
                 if ($this->proofingChangelogService->subjectChangedFolderCount($folder->id) > 0) {
                     $hasChanges = true;
                 }
            }

            $displayStatusId = $folder->status_id;
            $isManuallyModified = 0;

            // Auto-promotion logic: Transition to 'Modified' if changes detected and not already 'Modified'
            if ($folder->status_id == $this->statusService->none && $hasChanges) {
                $isManuallyModified = 1;
                $displayStatusId = $this->statusService->modified;
                
                // Perform updates
                $folder->update(['status_id' => $this->statusService->modified]);
                
                // If the job itself wasn't already modified, update it and send job notification
                if ($selectedJob->job_status_id != $this->statusService->modified) {
                    $selectedJob->update(['job_status_id' => $this->statusService->modified]);
                    $this->emailService->saveEmailContent($selectedJob->ts_jobkey, 'job_status_modified', Carbon::now(), $this->statusService->modified);
                }

                // Send folder notification for the transition to 'Modified'
                $this->emailService->saveEmailFolderContent($folder->ts_folder_id, 'folder_status_modified', Carbon::now(), $this->statusService->modified);                                                        
            }

            return [
                'folder' => $folder,
                'displayStatusId' => $displayStatusId,
                'isManuallyModified' => $isManuallyModified
            ];
        });

        return view('proofing.franchise.proof-my-people.folder-proofing', [
            'selectedJob' => $selectedJob,
            'selectedSeason' => $selectedSeason,
            'preparedFolders' => $preparedFolders,
            'noneStatus' => $this->statusService->none,
            'syncStatus' => $this->statusService->sync,
            'unsyncStatus' => $this->statusService->unsync,
            'modifiedStatus' => $this->statusService->modified,
            'completeStatus' => $this->statusService->completed,
            'reviewStatusesColours' => $reviewStatusesColours,
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function MyFoldersValidate($folderKey = null)
    {
        $decryptedFolderKey = $this->getDecryptData($folderKey);
        
        if (!$decryptedFolderKey) {
            return redirect()->route('proofing')->with('error', 'Invalid Folder Key');
        }

        $currentFolder = $this->folderService->getFolderByKey($decryptedFolderKey)
                        ->with(['job.seasons'])
                        ->select(
                            'id', 'ts_foldername', 'ts_folder_id', 'ts_folderkey',
                            'is_edit_portraits', 'is_edit_groups', 'ts_job_id', 
                            'is_edit_job_title', 'is_edit_salutation', 
                            'show_prefix_suffix_portraits', 'show_prefix_suffix_groups', 
                            'show_salutation_portraits', 'show_salutation_groups', 
                            'teacher', 'principal', 'deputy',
                            'is_subject_list_allowed', 'is_edit_principal', 
                            'is_edit_deputy', 'is_edit_teacher'
                        )
                        ->whereHas('folderUsers', function($query) {
                            $query->where('user_id', Auth::id());
                        })
                        ->first();

        if (!$currentFolder) abort(404);
          
        $subQuerySubjects = $this->subjectService->getByJobId($currentFolder->ts_job_id, 'ts_folder_id')
                            ->distinct()
                            ->pluck('ts_folder_id');

        $folderSelections = $this->folderService->getHomeFolders($subQuerySubjects)
                            ->select(['folders.id', 'ts_foldername'])
                            ->get();

        $selectedJob = $currentFolder->job;
        $selectedSeason = $selectedJob->seasons;

        $homedSubjects = $this->subjectService->getAllHomedSubjectsByFolderID($currentFolder->ts_folder_id)
            ->sortBy([['ts_folder_id', 'asc'], ['ts_subject_id', 'asc']])
            ->values();
        
        $attachedSubjects = $this->subjectService->getAllAttachedSubjectsByFolderID($currentFolder->ts_folder_id)
            ->sortBy([['ts_folder_id', 'asc'], ['ts_subject_id', 'asc']])
            ->values();
        
        $allSubjects = $attachedSubjects->merge($homedSubjects);
        
        $allSubjectsByJob = $this->subjectService->getSubjectByJobId($selectedJob->ts_job_id)
                            ->select([
                                'ts_subject_id', 'ts_subjectkey', 'salutation', 
                                'prefix', 'firstname', 'lastname', 'suffix', 'title'
                            ])->get();

        $groupDetails = $this->folderService->getGroupByFolder($currentFolder->ts_folderkey);
        $rawGroupDetails = $groupDetails['groupDetails'] ?? [];
        $finalGroupDetails = [];
                
        foreach ($rawGroupDetails as $rowKey => $subjects) {
            $finalGroupDetails[$rowKey] = [];
            if (!is_iterable($subjects)) continue;

            $subjectsByKey = $allSubjectsByJob->keyBy('ts_subjectkey');
            
            foreach ($subjects as $subjectStr) {
                $subjectStr = trim($subjectStr ?? '');
                
                if (str_starts_with($subjectStr, 'SUBJECTKEY:')) {
                    $subjectKey = trim(str_replace('SUBJECTKEY:', '', $subjectStr));
                    $subject = $subjectsByKey[$subjectKey] ?? null;

                    if ($subject) {
                        $parts = [];
                        if ($currentFolder->show_salutation_groups && !empty($subject->salutation)) $parts[] = trim($subject->salutation);
                        if ($currentFolder->show_prefix_suffix_groups && !empty($subject->prefix)) $parts[] = trim($subject->prefix);
                        if (!empty($subject->firstname)) $parts[] = trim($subject->firstname);
                        if (!empty($subject->lastname)) $parts[] = trim($subject->lastname);
                        if ($currentFolder->show_prefix_suffix_groups && !empty($subject->suffix)) $parts[] = trim($subject->suffix);
            
                        $finalGroupDetails[$rowKey][] = implode(' ', $parts);
                    }
                } elseif (str_starts_with($subjectStr, 'NAME:')) {
                    $finalGroupDetails[$rowKey][] = trim(str_replace('NAME:', '', $subjectStr));
                }
            }
        }        
    
        $groupDetails = [
            'groupDetails' => $finalGroupDetails,
            'groupValue' => json_encode($finalGroupDetails, JSON_UNESCAPED_UNICODE),
        ];

        $formattedFoldersWithChanges = $this->proofingChangelogService->getAllProofingChangelogFolder($selectedJob->ts_jobkey, $currentFolder->ts_folderkey)->get();      
        $folder_questions = $this->fetchProofingQuestions('FOLDER');
        $subject_questions = $this->fetchProofingQuestions('SUBJECT');
        $group_questions = $this->fetchProofingQuestions('GROUP');

        return view('proofing.franchise.proof-my-people.validate-people', [
            'currentFolder' => $currentFolder,
            'selectedSeason' => $selectedSeason,
            'selectedJob' => $selectedJob,
            'allSubjects' => $allSubjects,
            'formattedFoldersWithChanges' => $formattedFoldersWithChanges,
            'folder_questions' => $folder_questions,
            'subject_questions' => $subject_questions,
            'group_questions' => $group_questions,
            'folderSelections' => $folderSelections,
            'groupDetails' => $groupDetails,
            'allSubjectsByJob' => $allSubjectsByJob->sortBy([['ts_subject_id', 'asc']])->values(),
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function gridSubjects(Request $request)
    {
        if (!$request->job || !$request->folder) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $jobId = $this->getDecryptData($request->job);
        $folderKey = $this->getDecryptData($request->folder);

        if (!$jobId || !$folderKey) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }
    
        $currentFolder = $this->folderService->getFolderByKey($folderKey)
            ->with(['job'])
            ->whereHas('folderUsers', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();

        if (!$currentFolder) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $selectedJob = $currentFolder->job;
        if (!$selectedJob) abort(404); 
    
        $search = $request->input('search', '');
        $perPage = 20;
        $page = $request->input('page', 1);
    
        $query = Subject::where('ts_job_id', $selectedJob->ts_job_id);
    
        if (!empty($search)) {
            // Split search into words
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
    
            $query->where(function($q) use ($words) {
                foreach ($words as $word) {
                    $q->where(function($q2) use ($word) {
                        $q2->where('firstname', 'like', "%{$word}%")
                           ->orWhere('lastname', 'like', "%{$word}%")
                           ->orWhere('salutation', 'like', "%{$word}%")
                           ->orWhere('prefix', 'like', "%{$word}%")
                           ->orWhere('suffix', 'like', "%{$word}%")
                           ->orWhere('title', 'like', "%{$word}%");
                    });
                }
            });
        }
    
        $subjects = $query->orderBy('ts_subject_id')
            ->paginate($perPage, ['*'], 'page', $page);
    
        $html = view(
            'proofing.franchise.proof-my-people.grid-subject-rows',
            compact('subjects', 'folderKey', 'currentFolder', 'selectedJob')
        )->render();
    
        return response()->json([
            'html' => $html,
            'hasMore' => $subjects->hasMorePages(),
        ]);
    }
    
    protected function fetchProofingQuestions($type)
    {
        return $this->proofingDescriptionService->getAllProofingDescriptionData(
            $type, 'id', 'issue_name', 'issue_description', 'issue_category_id', 'issue_error_message', 'is_proceed_confirm'
        );
    }

    public function viewChangeHtml(Request $request, $subjectkey){
         if (!$request->ajax()) {
            return redirect()->route('proofing');
        }

        $subjectkey = $this->getDecryptData($subjectkey);
        if (!$subjectkey) {
            $body = "<div>Invalid subject key provided.</div>";
            return response($body, 200)->header('Content-Type', 'text/html');
        }

        // Assuming the SubjectChange model and findSubjectChanges method are available
        $awaitingApproval = $this->statusService->awaitingApproval;
        $autoApproved = $this->statusService->autoApproved;
        $rejected = $this->statusService->rejected;
        $approved = $this->statusService->approved;
        $subjectChanges = $this->proofingChangelogService->getAllProofingChangelogBySubjectkey($subjectkey)->orderBy('id', 'asc')->get();
        return view('proofing.franchise.proof-my-people.html', [
            'subjectChanges' => $subjectChanges,
            'autoApproved' => $autoApproved,
            'awaitingApproval' => $awaitingApproval,
            'rejected' => $rejected,
            'approved' => $approved,
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function insertFolderProofingChangeLog(Request $request, $folderKey)
    {
        \Log::info('insertFolderProofingChangeLog hit', [
            'folderKey' => $folderKey,
            'issue' => $request->input('issue'),
            'note' => $request->input('note'),
            'newValue' => $request->input('newValue')
        ]);

        $request->validate([
            'issue' => 'required|string',
            'note' => 'required|string',
            'newValue' => 'nullable|string',
        ]);

        $decryptedFolderKey = $this->getDecryptData($folderKey);
        if (!$decryptedFolderKey) {
            \Log::error('Folder key decryption failed', ['hash' => $folderKey]);
            return response()->json(['error' => 'Invalid key'], 400);
        }

        \Log::info('Folder key decrypted', ['key' => $decryptedFolderKey]);

        $this->proofingChangelogService->insertFolderProofingChangeLog(
            $decryptedFolderKey, 
            $request->input('issue'), 
            $request->input('note'), 
            $request->input('newValue')
        );

        return response()->json(['status' => true]);
    }

    public function ProofingDescription($id){
        return $this->proofingDescriptionService->getAllProofingDescriptionById($id, 'issue_description');
    }

    public function insertSubjectProofingChangeLog(Request $request){
        $request->validate([
            'subject_key_encrypted' => 'required|string',
            'folder_key_encrypted' => 'nullable|string',
            'new_first_name' => 'nullable|string',
            'new_last_name' => 'nullable|string',
            'new_title' => 'nullable|string',
            'new_salutation' => 'nullable|string',
        ]);

        $responseData = $this->proofingChangelogService->insertSubjectProofingChangeLog($request->all());
        return response()->json(['responseData'=>$responseData]);
    }

    public function insertGroupProofingChangeLog(Request $request){
        $request->validate([
            'jobHash' => 'required|string',
            'folderHash' => 'required|string',
        ]);

        $jsonData = $request->json()->all();
        $jobKey = $this->getDecryptData($jsonData['jobHash']);
        $folderKey = $this->getDecryptData($jsonData['folderHash']);

        if (!$jobKey || !$folderKey) return response()->json(['error' => 'Invalid keys'], 400);

        unset($jsonData['folderHash'], $jsonData['jobHash']);
        $this->proofingChangelogService->insertGroupProofingChangeLog($jobKey, $folderKey, $jsonData);
        return response()->json(['status' => true]);
    }

    public function submitProof(Request $request)
    {
        $decryptedFolderKey = $this->getDecryptData($request->folderHash);
        if (!$decryptedFolderKey) return response()->json(['status' => false, 'message' => 'Invalid folder'], 422);

        $folder = $this->folderService->getFolderByKey($decryptedFolderKey)
                ->with(['job.folders'])
                ->select('status_id', 'is_locked', 'ts_folder_id', 'ts_job_id', 'id', 'ts_folderkey')
                ->first();

        if (!$folder || !isset($folder->job) || !$folder->job->ts_jobkey) {
            return response()->json(['status' => false, 'message' => 'Job data missing'], 422);
        }
    
        $hash = Crypt::encryptString($folder->job->ts_jobkey);
        $location = URL::signedRoute('my-folders-list', ['hash' => $hash]);
    
        $isSaveForLater = $request->submitProof === 'save-for-later';
        $isMarkAsComplete = $request->submitProof === 'mark-as-complete';

        $statusFields = [
            $this->statusService->modified => 'folder_status_modified',
            $this->statusService->completed => 'folder_status_completed'
        ];
    
        if ($isSaveForLater || $isMarkAsComplete) {
            $folder->is_locked = !$isSaveForLater;
            $folder->status_id = $isSaveForLater ? $this->statusService->modified : $this->statusService->completed;
            $folder->save();
            
            $this->emailService->saveEmailFolderContent($folder->ts_folder_id, $statusFields[$folder->status_id], Carbon::now(), $folder->status_id);

            $job = $folder->job;
            if ($isMarkAsComplete) {
                $incompleteFolders = $job->folders()->where('status_id', '!=', $this->statusService->completed)->count();
                $jobStatus = ($incompleteFolders === 0) ? $this->statusService->completed : $this->statusService->incomplete;
            } else {
                $jobStatus = $this->statusService->incomplete;
            }

            $job->update(['job_status_id' => $jobStatus]);

            if ($jobStatus === $this->statusService->completed) {
                $this->emailService->saveEmailContent($job->ts_jobkey, 'job_status_completed', Carbon::now(), $jobStatus);
            }
        
            if ($isSaveForLater) {
                $folder->subjects()->update(['is_locked' => false]);
            }
        }
        return response()->json(['status'=>true,'url'=>$location,'csrf' => csrf_token()]);
    }
}
