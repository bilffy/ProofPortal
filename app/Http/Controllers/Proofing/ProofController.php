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

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function MyFoldersList($hash)
    {
        $decryptedJobKey = $this->getDecryptData($hash);
        $selectedJob = $this->jobService->getJobByJobKey($decryptedJobKey)->with(['seasons'])->first();
        $selectedSeason = $selectedJob->seasons;
        return $this->renderFolderProofingView($selectedJob, $selectedSeason, $selectedJob->ts_job_id);
    }

    protected function renderFolderProofingView($selectedJob, $selectedSeason, $tsJobId)
    {
        $selectedFolders = $this->folderService->getFolderByJobId($tsJobId)->with('images')->where('is_visible_for_proofing', 1)->orderBy('ts_foldername', 'asc')->get();
        $reviewStatusesColours = $this->statusService->getAllStatusData('id', 'status_external_name', 'colour_code')->get();
        $getChangelog = $this->proofingChangelogService->getAllChangelogsByJobkeyExceptTraditional($selectedJob->ts_jobkey)->select('keyvalue')->get();
        return view('proofing.franchise.proof-my-people.folder-proofing', [
            'selectedJob' => $selectedJob,
            'selectedSeason' => $selectedSeason,
            'selectedFolders' => $selectedFolders,
            'getChangelog' => $getChangelog,
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
        // exec('php artisan view:clear');
        // exec('php artisan view:cache');
        $decryptedFolderKey = $this->getDecryptData($folderKey);
        $currentFolder = $this->folderService->getFolderByKey($decryptedFolderKey)
                        ->with(['job.seasons', 'job.subjects', 'subjects', 'subjects.images'])
                        ->select(
                            'id',
                            'ts_foldername',
                            'ts_folder_id',
                            'ts_folderkey',
                            'is_edit_portraits',
                            'is_edit_groups',
                            'ts_job_id', 
                            'is_edit_job_title', 
                            'is_edit_salutation', 
                            'show_prefix_suffix_portraits', 
                            'show_prefix_suffix_groups', 
                            'show_salutation_portraits', 
                            'show_salutation_groups', 
                            'teacher', 
                            'principal', 
                            'deputy',
                            'is_subject_list_allowed',
                            'is_edit_principal',
                            'is_edit_deputy',
                            'is_edit_teacher')->first();
          
        $subQuerySubjects = $this->subjectService->getByJobId($currentFolder->ts_job_id, 'ts_folder_id')->distinct()
                            ->pluck('ts_folder_id');

        $folderSelections = $this->folderService->getHomeFolders($subQuerySubjects)
                            ->select(['folders.id', 'ts_foldername'])
                            ->get();

        $selectedJob = $this->jobService->getJobById($currentFolder->ts_job_id);
        $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($selectedJob->ts_season_id)->first();

        $homedSubjects = $this->subjectService
        ->getAllHomedSubjectsByFolderID($currentFolder->ts_folder_id)
        ->sortBy([
            ['ts_folder_id', 'asc'],
            ['ts_subject_id', 'asc'],
        ])
        ->values();
        
        $attachedSubjects = $this->subjectService
            ->getAllAttachedSubjectsByFolderID($currentFolder->ts_folder_id)
            ->sortBy([
                ['ts_folder_id', 'asc'],
                ['ts_subject_id', 'asc'],
            ])
            ->values();
        
        $allSubjects = $attachedSubjects->merge($homedSubjects);
        
        $allSubjectsByJob = $this->subjectService->getSubjectByJobId($currentFolder->job->ts_job_id)
                            ->select([
                                'ts_subject_id',
                                'ts_subjectkey',
                                'salutation',
                                'prefix',
                                'firstname',
                                'lastname',
                                'suffix',
                                'title'
                            ])->get();

        $groupDetails = $this->folderService->getGroupByFolder($currentFolder->ts_folderkey);

        $rawGroupDetails = $groupDetails['groupDetails'] ?? [];

        $finalGroupDetails = [];
                
            foreach ($rawGroupDetails as $rowKey => $subjects) {

                $finalGroupDetails[$rowKey] = [];
            
                if (!is_iterable($subjects)) {
                    continue;
                }        
                
                foreach ($subjects as $subjectStr) {
                    $subjectStr = trim($subjectStr);
            
                    $subject = null;
            
                    // Match by ID or NAME
                    if (str_starts_with($subjectStr, 'SUBJECTKEY:')) {
                        $subjectKey = trim(str_replace('SUBJECTKEY:', '', $subjectStr));
                        // $subject = $allSubjects->firstWhere('ts_subjectkey', $subjectKey);
                        // $subject = $allSubjectsByJob->firstWhere('ts_subjectkey', $subjectKey);
                        $subjectsByKey = $allSubjectsByJob->keyBy('ts_subjectkey');
                        $subject = $subjectsByKey[$subjectKey] ?? null;
                    } elseif (str_starts_with($subjectStr, 'NAME:')) {
                        $nameOnly = trim(str_replace('NAME:', '', $subjectStr));
                        $finalGroupDetails[$rowKey][] = $nameOnly;
                        continue;
                    }
            
                    // If subject found, build clean name dynamically
                    if ($subject) {
                        $useSalutation = $currentFolder->show_salutation_groups;
                        $usePrefixSuffix = $currentFolder->show_prefix_suffix_groups;
            
                        $salutation = trim($subject->salutation ?? '');
                        $prefix = trim($subject->prefix ?? '');
                        $suffix = trim($subject->suffix ?? '');
                        $first = trim($subject->firstname ?? '');
                        $last = trim($subject->lastname ?? '');
            
                        // Dynamically build name only from non-empty parts
                        $parts = [];
            
                        if ($useSalutation && $salutation !== '') $parts[] = $salutation;
                        if ($usePrefixSuffix && $prefix !== '') $parts[] = $prefix;
                        if ($first !== '') $parts[] = $first;
                        if ($last !== '') $parts[] = $last;
                        if ($usePrefixSuffix && $suffix !== '') $parts[] = $suffix;
            
                        $fullName = implode(' ', $parts);
            
                        $finalGroupDetails[$rowKey][] = $fullName;
                    }
                }
            }        
    
            // Now create JSON representation
            $groupValue = json_encode($finalGroupDetails, JSON_UNESCAPED_UNICODE);
    
            // For debugging
            $groupDetails = ([
                'groupDetails' => $finalGroupDetails,
                'groupValue' => $groupValue,
            ]);

        $formattedFoldersWithChanges = $this->proofingChangelogService->getAllProofingChangelogFolder($currentFolder->job->ts_jobkey,$currentFolder->ts_folderkey)->get();      
        $folder_questions = $this->fetchProofingQuestions('FOLDER');
        $subject_questions = $this->fetchProofingQuestions('SUBJECT');
        $group_questions = $this->fetchProofingQuestions('GROUP');

        return view('proofing.franchise.proof-my-people.validate-people', [
            'currentFolder' => $currentFolder,
            'selectedSeason' => $currentFolder->job->seasons,
            'selectedJob' => $currentFolder->job,
            'allSubjects' => $allSubjects,
            'formattedFoldersWithChanges' => $formattedFoldersWithChanges,
            'folder_questions' => $folder_questions,
            'subject_questions' => $subject_questions,
            'group_questions' => $group_questions,
            'folderSelections' => $folderSelections,
            'groupDetails' => $groupDetails,
            'allSubjectsByJob' => $allSubjectsByJob->sortBy([
                ['ts_subject_id', 'asc'],
            ])->values(),
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function gridSubjects(Request $request)
    {
        $jobId = Crypt::decryptString($request->job);
        $folderKey = Crypt::decryptString($request->folder);
    
        $currentFolder = $this->folderService->getFolderByKey($folderKey)
            ->with(['job.seasons', 'job.subjects', 'subjects', 'subjects.images'])
            ->first();
    
        $selectedJob = $this->jobService->getJobById($currentFolder->ts_job_id);
    
        $search = $request->input('search', '');
        $perPage = 20;
        $page = $request->input('page', 1);
    
        $query = Subject::where('ts_job_id', $jobId);
    
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
            return redirect()->route('proofing'); // Adjust route name as needed
        }

        try {
            $subjectkey = $this->getDecryptData($subjectkey);
        } catch (\Exception $e) {
            $body = "<div>Sorry, the Subjectkey {$subjectkey} could not be found</div>";
            return response($body, 200)->header('Content-Type', 'text/html');
        }

        // Assuming the SubjectChange model and findSubjectChanges method are available
        $awaitingApproval = $this->statusService->awaitingApproval;
        $autoApproved = $this->statusService->autoApproved;
        $rejected = $this->statusService->rejected;
        $approved = $this->statusService->approved;
        $subjectChanges = $this->proofingChangelogService->getAllProofingChangelogBySubjectkey($subjectkey)->orderByDesc('id')->get();
        return view('proofing.franchise.proof-my-people.html', [
            'subjectChanges' => $subjectChanges,
            'autoApproved' => $autoApproved,
            'awaitingApproval' => $awaitingApproval,
            'rejected' => $rejected,
            'approved' => $approved,
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function insertFolderProofingChangeLog(Request $request, $folderKey){
        $issue = $request->input('issue');
        $note = $request->input('note');
        $key_encrypted = $request->input('key_encrypted');
        $newValue = $request->input('newValue');
        $decryptedFolderKey = $this->getDecryptData($folderKey);
        return $this->proofingChangelogService->insertFolderProofingChangeLog($decryptedFolderKey, $issue, $note, $newValue);
    }

    public function ProofingDescription($id){
        return $this->proofingDescriptionService->getAllProofingDescriptionById($id, 'issue_description');
    }

    public function insertSubjectProofingChangeLog(Request $request){
        $responseData = $this->proofingChangelogService->insertSubjectProofingChangeLog($request->all());
        return response()->json(['responseData'=>$responseData]);
    }

    public function insertGroupProofingChangeLog(Request $request){
        $jsonData = $request->json()->all();
        $jobKey = $this->encryptDecryptService->decryptStringMethod($jsonData['jobHash']);
        $folderKey = $this->encryptDecryptService->decryptStringMethod($jsonData['folderHash']);
        unset($jsonData['folderHash']);
        unset($jsonData['jobHash']);
        $this->proofingChangelogService->insertGroupProofingChangeLog($jobKey, $folderKey, $jsonData);
    }

    public function submitProof(Request $request)
    {
        $decryptedFolderKey = $this->getDecryptData($request->folderHash);
    
        // Retrieve folder with related job in a single query
        $folder = $this->folderService->getFolderByKey($decryptedFolderKey)
                ->select('status_id', 'is_locked', 'ts_folder_id', 'ts_job_id', 'id')->first(); // Ensure the folder is found
    
        $hash = Crypt::encryptString($folder->job->ts_jobkey);
        $location = URL::signedRoute('my-folders-list', ['hash' => $hash]);
    
        $isSaveForLater = $request->submitProof === 'save-for-later';
        $isMarkAsComplete = $request->submitProof === 'mark-as-complete';

        $statusFields = [
            $this->statusService->modified => 'folder_status_modified',
            $this->statusService->completed => 'folder_status_completed'
        ];
    
        if ($isSaveForLater || $isMarkAsComplete) {
            $folder->is_locked = $isSaveForLater ? false : true;
            $folder->status_id = $isSaveForLater ? $this->statusService->modified : $this->statusService->completed;
            $folder->save();
            
            $this->emailService->saveEmailFolderContent($folder->ts_folder_id, $statusFields[$folder->status_id], Carbon::now(), $folder->status_id);

            if ($folder->job) {
                if ($isMarkAsComplete) {
                    $incompleteFolders = $folder->job->folders->filter(function($f) {
                        return $f->status_id != $this->statusService->completed;
                    });
                    
                    $jobStatus = $incompleteFolders->isEmpty() ? $this->statusService->completed : $this->statusService->incomplete;
                } else if ($isSaveForLater) {
                    $jobStatus = $this->statusService->incomplete;
                }
                $folder->job()->update(['job_status_id' => $jobStatus]);

                if ($jobStatus === $this->statusService->completed) {
                    $this->emailService->saveEmailContent($folder->job->ts_jobkey, 'job_status_completed', Carbon::now(), $jobStatus);
                }
            }
        
            if ($isSaveForLater) {
                $folder->subjects()->update(['is_locked' => false]);
            }
        }
        return response()->json(['status'=>true,'url'=>$location,'csrf' => csrf_token()]);

    }
}
