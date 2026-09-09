<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\SchoolService;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Crypt;
use App\Http\Resources\UserResource;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\FolderUser;
use App\Models\Franchise;
use App\Models\JobUser;
use App\Models\Folder;
use App\Models\Job;
use App\Models\User;
use App\Models\Email;
use App\Models\School;
use Carbon\Carbon;
use App\Helpers\SchoolContextHelper;
use App\Helpers\RoleHelper;
use Session;
use Auth;


class InvitationController extends Controller
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $schoolService;
    protected $emailService;
    protected $statusService;

    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, SchoolService $schoolService, StatusService $statusService, EmailService $emailService)
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->emailService = $emailService;
        $this->statusService = $statusService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function manageStaffs($hashedJob)
    {
        $selectedJob = $this->jobService->getJobByJobKey($this->getDecryptData($hashedJob))->first(); 
        
        if (!$selectedJob) {
            abort(404); 
        }

        $tsJobId = $selectedJob->ts_job_id;
        // Fetch users assigned to this job (job_users) with Teacher / Photo Coordinator / Franchise roles
        $roles = [
            RoleHelper::ROLE_TEACHER,
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_FRANCHISE,
        ];

        $users = User::with('roles')
            ->whereHas('roles', fn($q) =>
            $q->whereIn('name', $roles)
        )
        ->whereHas('jobs', fn($q) =>
            $q->where('jobs.ts_job_id', $tsJobId)
        )
        ->get();

        $photocoordinators = [];
        $teachers          = [];
        $otherList         = [];
        $canDisableMap     = [];

        $currentUser      = Auth::user();
        $currentUserLevel = $currentUser->getRoleId();

        foreach ($users as $user) {
            // Build canDisableMap inline by role-of-viewer rules (no changes needed in User.php)
            if ($currentUser->isFranchiseLevel()) {
                // Franchise can revoke both PCs and Teachers
                $canDisableMap[$user->id] = $user->hasRole('Photo Coordinator') || $user->hasRole('Teacher');
            } elseif ($currentUser->isPhotoCoordinator()) {
                // Photo Coordinator can revoke Teachers only
                $canDisableMap[$user->id] = $user->hasRole('Photo Coordinator') || $user->hasRole('Teacher');
            } else {
                $canDisableMap[$user->id] = $currentUser->canDisable($user->id);
            }

            // Bucket by current user's role
            if ($currentUser->isFranchiseLevel()) {
                // Franchise: PCs → photocoordinators, Teachers → teachers, everyone else (franchise-level incl. self) → otherList
                if ($user->hasRole('Photo Coordinator')) {
                    $photocoordinators[] = $user;
                } elseif ($user->hasRole('Teacher')) {
                    $teachers[] = $user;
                } else {
                    $otherList[] = $user;
                }
            } elseif ($currentUser->isPhotoCoordinator()) {
                // Photo Coordinator: self -> otherList, Teachers -> teachers, other PCs/higher roles hidden
                if ($user->id == $currentUser->id) {
                    $otherList[] = $user;
                } elseif ($user->hasRole('Photo Coordinator')) {
                    $photocoordinators[] = $user;
                } elseif ($user->hasRole('Teacher')) {
                    $teachers[] = $user;
                }
            } else {
                // Fallback for other roles (School Administrator, RcUser, etc.)
                if ($user->hasRole('Photo Coordinator')) {
                    $photocoordinators[] = $user;
                } elseif ($user->hasRole('Teacher')) {
                    $teachers[] = $user;
                } else {
                    $userLevel = $user->roles->first()?->id;
                    if ($canDisableMap[$user->id] || $userLevel == $currentUserLevel) {
                        $otherList[] = $user;
                    }
                }
            }
        }

        $foldersByUserId = FolderUser::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereHas('folder', fn ($q) => $q->where('ts_job_id', $tsJobId))
            ->with(['folder' => fn ($q) => $q->select('ts_folder_id', 'ts_foldername', 'ts_job_id')])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('folder')->filter()->values());

        $user = $currentUser;
        return view('proofing.franchise.invitations.manage_photocoordinator_teacher', [
            'selectedJob'       => $selectedJob,
            'photocoordinators' => $photocoordinators,
            'teachers'          => $teachers,
            'otherList'         => $otherList,
            'canDisableMap'     => $canDisableMap,
            'foldersByUserId'   => $foldersByUserId,
            'user'              => new UserResource($user)
        ]);
    }


    public function showInvitation()
    {
        return view('proofing.franchise.invitations.show-invitation');
    }

    public function index($role = null)
    { 
        $selectedJob = Session::get('selectedJob');
        
        if (!$selectedJob) {
            abort(404); 
        }

        $selectedSeason = Session::get('selectedSeason');
        $user = Auth::user();
        $myActiveReviewSchoolsCount = JobUser::where('user_id',$user->id)
                                    ->whereHas('job', function($query) {
                                        $query->whereIn('job_status_id', [
                                            $this->statusService->none, 
                                            $this->statusService->viewed, 
                                            $this->statusService->modified, 
                                            $this->statusService->unlocked, 
                                            $this->statusService->active, 
                                            $this->statusService->incomplete
                                        ]); // Filter FolderUser by user_id
                                    })
                                    ->count(); //get all jobs under auth_user having status - None, Viewed, Modified, Unlocked, Activated, Incomplete
        $mySchools = JobUser::with('job')->where('user_id',$user->id)->get(); //get all jobs under auth_user
        return view('proofing.franchise.invitations.invitation_index', [
            'role' => $role,
            'syncStatus' => $this->statusService->sync,
            'selectedJob' => $selectedJob,
            'selectedSeason' => $selectedSeason,
            'myActiveReviewSchoolsCount' => $myActiveReviewSchoolsCount,
            'mySchools' => $mySchools,
            'user' => new UserResource($user)
        ]);
    }

    public function inviteSingle($role, $jobKeyHash)
    {
        $jobId = $this->getDecryptData($jobKeyHash);
        $selectedJob = $this->jobService->getJobByJobKey($jobId)->first();

        if (!$selectedJob) {
            abort(404); 
        }
        
        $currentSchool = SchoolContextHelper::getSchool()?->id;
        $user = Auth::user(); 
    
        $users = User::query()
            ->whereHas('roles', function($q) use ($role) {
                // Removes spaces and converts to lowercase for a truly fuzzy match
                $q->whereRaw("LOWER(REPLACE(name, ' ', '')) = ?", [strtolower($role)]);
            })
            ->whereHas('schools', function($q) use ($currentSchool) {
                $q->where('school_id', $currentSchool);
            })
            ->leftJoin('franchise_users', 'franchise_users.user_id', '=', 'users.id')
            ->leftJoin('franchises', 'franchises.id', '=', 'franchise_users.franchise_id')
            ->leftJoin('school_users', 'school_users.user_id', '=', 'users.id')
            ->leftJoin('schools', 'schools.id', '=', 'school_users.school_id')
            ->leftJoin('school_franchises', 'school_franchises.school_id', '=', 'school_users.school_id')
            ->leftJoin('franchises as sf', 'sf.id', '=', 'school_franchises.franchise_id')
            // Prefixing with 'users.' prevents ambiguity and 'distinct()' prevents duplicates
            ->select('users.firstname', 'users.lastname', 'users.email')
            ->whereIn('users.status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
            ->distinct() 
            ->get();
    
        return view('proofing.franchise.invitations.invitation_single', [
            'expiryDate' => $this->getAccountExpirationDate(),
            'role' => $role,
            'users' => $users,
            'selectedJob' => $selectedJob,
            'user' => new UserResource($user)
        ]);
    }

    // public function validateEmail(Request $request)
    // {
    //     $email = $request->email;
    //     $role  = $request->role;  
    //     $user  = Auth::user();
    
    //     if (!$email || !$role) {
    //         return response()->json([
    //             'exists' => false,
    //             'message' => 'Invalid request'
    //         ]);
    //     }
    
    //     $query = User::query()
    //         ->join('model_has_roles', function ($join) {
    //             $join->on('users.id', '=', 'model_has_roles.model_id')
    //                 ->where('model_has_roles.model_type', '=', User::class);
    //         })
    //         ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    //         ->leftJoin('franchise_users', 'franchise_users.user_id', '=', 'users.id')
    //         ->leftJoin('franchises', 'franchises.id', '=', 'franchise_users.franchise_id')
    //         ->leftJoin('school_users', 'school_users.user_id', '=', 'users.id')
    //         ->leftJoin('schools', 'schools.id', '=', 'school_users.school_id')
    //         ->leftJoin('school_franchises', 'school_franchises.school_id', '=', 'school_users.school_id')
    //         ->leftJoin('franchises as sf', 'sf.id', '=', 'school_franchises.franchise_id')
    //         ->where('users.email', $email);
    
    //     if (!$query->exists()) {
    //         return response()->json([
    //             'exists' => false,
    //             'message' => "Email not found. Please add the user for the role {$role}."
    //         ]);
    //     }
    
    //     if ($user->isSchoolLevel()) {
    //         $school = $user->getSchool();
    //         $query->where('schools.id', $school->id);
    
    //         if (!$query->exists()) {
    //             return response()->json([
    //                 'exists' => false,
    //                 'message' => "Email not associated with the respective school."
    //             ]);
    //         }
    //     }
    
    //     $normalizedRole = strtolower(str_replace(' ', '', $role));
  
    //     $roleCheckQuery = clone $query;  
    //     $roleCheckQuery->whereRaw(
    //         "REPLACE(LOWER(roles.name), ' ', '') = ?",
    //         [$normalizedRole]
    //     );
    
    //     if (!$roleCheckQuery->exists()) {
    //         return response()->json([
    //             'exists' => false,
    //             'message' => "Email not associated with the role {$role}. Please add the user for the role {$role}."
    //         ]);
    //     }
    
    //     return response()->json(['exists' => true]);
    // }      

    public function inviteMulti($role, $jobKeyHash)
    {
        $jobId = $this->getDecryptData($jobKeyHash);
        $selectedJob = $this->jobService->getJobByJobKey($jobId)->first();

        if (!$selectedJob) {
            abort(404); 
        }
        
        $currentSchool = SchoolContextHelper::getSchool()?->id;
        $user = Auth::user(); 
    
        $users = User::query()
            ->whereHas('roles', function($q) use ($role) {
                // Removes spaces and converts to lowercase for a truly fuzzy match
                $q->whereRaw("LOWER(REPLACE(name, ' ', '')) = ?", [strtolower($role)]);
            })
            ->whereHas('schools', function($q) use ($currentSchool) {
                $q->where('school_id', $currentSchool);
            })
            ->leftJoin('franchise_users', 'franchise_users.user_id', '=', 'users.id')
            ->leftJoin('franchises', 'franchises.id', '=', 'franchise_users.franchise_id')
            ->leftJoin('school_users', 'school_users.user_id', '=', 'users.id')
            ->leftJoin('schools', 'schools.id', '=', 'school_users.school_id')
            ->leftJoin('school_franchises', 'school_franchises.school_id', '=', 'school_users.school_id')
            ->leftJoin('franchises as sf', 'sf.id', '=', 'school_franchises.franchise_id')
            // Prefixing with 'users.' prevents ambiguity and 'distinct()' prevents duplicates
            ->select('users.firstname', 'users.lastname', 'users.email')
            ->whereIn('users.status', [User::STATUS_ACTIVE, User::STATUS_INVITED])
            ->distinct() 
            ->get();
    
        return view('proofing.franchise.invitations.invitation_multi', [
            'expiryDate' => $this->getAccountExpirationDate(),
            'role' => $role,
            'users' => $users,
            'selectedJob' => $selectedJob,
            'user' => new UserResource($user)
        ]);
    }

    public function inviteSend(Request $request)
    {
        $failedEmails = [];
        $inviteUsers = [];
        $attemptedEmails = [];
        $user = Auth::user();

        if ($user->isSchoolLevel()) {
            $user->load('schools');
        }

        $peopleArray = $request->people
            ? json_decode($request->people, true)
            : [[null, null, $request->email, $request->folder]];

        $normalizedRole = strtolower(str_replace(' ', '', $request->role));
        $currentSchool = SchoolContextHelper::getSchool();

        $entries = [];
        foreach ($peopleArray as $person) {
            $email = isset($person[2]) ? strtolower(trim((string) $person[2])) : null;
            if (!$email) {
                continue;
            }

            $attemptedEmails[$email] = true;
            $entries[] = [
                'email' => $email,
                'folder_key' => $person[3] ?? null,
            ];
        }

        if ($entries === []) {
            session()->flash('error', 'No email addresses were submitted.');
            return redirect()->back();
        }

        $emails = collect($entries)->pluck('email')->unique()->values()->all();
        $emailPlaceholders = implode(',', array_fill(0, count($emails), '?'));

        $usersByEmail = User::with(['roles:id,name', 'schools:id'])
            ->whereRaw("LOWER(email) IN ({$emailPlaceholders})", $emails)
            ->get()
            ->keyBy(fn ($inviteUser) => strtolower($inviteUser->email));

        $folderKeys = collect($entries)->pluck('folder_key')->filter()->unique()->values();
        $needsAllFolders = $folderKeys->contains('*');
        $specificFolderKeys = $folderKeys->filter(fn ($key) => $key !== '*')->values()->all();

        $jobFoldersQuery = Folder::whereHas('job', fn ($q) => $q->where('ts_jobkey', $request->job_key))
            ->select('ts_folder_id', 'ts_job_id', 'ts_folderkey');

        $allJobFolders = $needsAllFolders ? $jobFoldersQuery->get() : collect();
        $foldersByKey = $specificFolderKeys !== []
            ? (clone $jobFoldersQuery)->whereIn('ts_folderkey', $specificFolderKeys)->get()->groupBy('ts_folderkey')
            : collect();

        $folderAssignments = [];

        foreach ($entries as $entry) {
            $email = $entry['email'];
            $folderKey = $entry['folder_key'];
            $inviteUser = $usersByEmail->get($email);

            if (!$inviteUser) {
                $failedEmails[$email] = 'user does not exist';
                continue;
            }

            $validationError = $this->validateInviteUser(
                $inviteUser,
                $normalizedRole,
                $request->role,
                $currentSchool,
                $user
            );

            if ($validationError) {
                $failedEmails[$email] = $validationError;
                continue;
            }

            $folders = $folderKey === '*'
                ? $allJobFolders
                : $foldersByKey->get($folderKey, collect());

            if ($folders->isEmpty()) {
                $failedEmails[$email] = 'no matching class/group found';
                continue;
            }

            foreach ($folders as $folder) {
                $folderAssignments[] = [
                    'user_id' => $inviteUser->id,
                    'ts_job_id' => $folder->ts_job_id,
                    'ts_folder_id' => $folder->ts_folder_id,
                ];
            }
        }

        $this->bulkAssignInviteFolders($folderAssignments, $inviteUsers);

        $uniqueInviteUserIds = array_values(array_unique($inviteUsers));

        foreach ($uniqueInviteUserIds as $inviteUserId) {
            $this->emailService->saveInvitationContent(
                $request->role,
                $inviteUserId,
                now(),
                $request->job_key
            );
        }

        if ($uniqueInviteUserIds !== []) {
            $this->emailService->saveScheduledEmailsForInviteUsers(
                $uniqueInviteUserIds,
                $request->job_key
            );
        }

        $successDetails = User::whereIn('id', $uniqueInviteUserIds)
            ->select('id', 'firstname', 'lastname', 'email')
            ->get()
            ->map(fn ($inviteUser) => "{$inviteUser->firstname} {$inviteUser->lastname} ({$inviteUser->email})")
            ->all();

        $successCount = count($successDetails);
        $failCount = count($failedEmails);
        $attemptedCount = count($attemptedEmails);

        if ($successCount > 0 && $failCount === 0) {
            session()->flash(
                'success',
                'All invitations were sent successfully (' . $successCount . ' of ' . $attemptedCount . '): '
                . implode(', ', $successDetails) . '.'
            );
        } else {
            if ($successCount > 0) {
                session()->flash(
                    'success',
                    'Invitations sent (' . $successCount . ' of ' . $attemptedCount . '): '
                    . implode(', ', $successDetails) . '.'
                );
            }

            if ($failCount > 0) {
                $failedList = [];
                foreach ($failedEmails as $failedEmail => $reason) {
                    $failedList[] = e($failedEmail) . ' (' . e($reason) . ')';
                }

                $prefix = $successCount > 0
                    ? 'Some invitations failed (' . $failCount . ' of ' . $attemptedCount . ')'
                    : 'No invitations were sent';

                session()->flash(
                    'error',
                    $prefix . '. Failed emails: ' . implode(', ', $failedList) . '.'
                );
            }
        }

        return redirect()->back();
    }

    public function emailNotFound(Request $request)
    {
            session()->forget([
                'selectedSeasonDashboard',
                'selectedSeason',
                'selectedJob',
                'openSeason',
                'openJob',
                'approvedSubjectChangesCount',
                'awaitApprovalSubjectChangesCount'
            ]);
        // Continue with existing logic
        return redirect()->route('users.create');
    }

    // public function inviteSend(Request $request)
    // {
    //     $errorMessages = [];
    //     $inviteUsers = [];
    //     $user = Auth::user();

    //     // Get emails for the user's franchise
    //     // $emails = $user->isFranchiseLevel()
    //     //     ? User::whereHas('franchises', fn($query) => $query->where('franchise_id', $user->getFranchise()->id))
    //     //         ->whereNotNull('email')
    //     //         ->pluck('email')
    //     //         ->toArray()
    //     //     : [];

    //     // Process multiple people or a single email
    //     $peopleArray = $request->people ? json_decode($request->people, true) : [[null, null, $request->email, $request->folder]];

        
    //     foreach ($peopleArray as $person) {
    //         $email = $person[2] ?? null;
    //         $folderKey = $person[3] ?? null;

    //         if($email){
    //             // if (!in_array($email, $emails)) {
    //             //     $errorMessages[] = "Email - {$email} does not exist.";
    //             //     continue;
    //             // }

    //             $inviteUser = User::where('email', $email)->select('id')->first();

    //             $folders = $folderKey === '*'
    //                 ? Folder::whereHas('job', fn($query) => $query->where('ts_jobkey', $request->job_key))
    //                     ->select('ts_folder_id','ts_job_id')
    //                     ->get()
    //                 : Folder::whereHas('job', fn($query) => $query->where('ts_jobkey', $request->job_key))
    //                     ->where('ts_folderkey', $folderKey)
    //                     ->select('ts_folder_id','ts_job_id')
    //                     ->get();

    //             foreach ($folders as $folder) {
    //                 $this->saveFolderUser($folder->ts_job_id, $folder->ts_folder_id, $inviteUser->id, $inviteUsers);
    //             }
    //         }
    //     }

    //     // Save email folder content for unique users
    //     foreach (array_unique($inviteUsers) as $inviteUserId) {
    //         $this->emailService->saveInvitationContent(
    //             $request->role,
    //             $inviteUserId,
    //             Carbon::now(),
    //             $request->job_key
    //         );
    //     }

    //     // Flash error or success messages
    //     if ($errorMessages) {
    //         session()->flash('errors', array_unique($errorMessages));
    //     } else {
    //         session()->flash('success', 'Invitations sent successfully.');
    //     }

    //     return redirect()->back();
    // }

    private function validateInviteUser(
        User $inviteUser,
        string $normalizedRole,
        string $requestRole,
        $currentSchool,
        User $inviter
    ): ?string {
        if ($currentSchool) {
            if (!$inviteUser->schools->contains('id', $currentSchool->id)) {
                return 'not associated with this school';
            }
        } elseif ($inviter->isSchoolLevel()) {
            $school = $inviter->schools->first();
            if (!$school || !$inviteUser->schools->contains('id', $school->id)) {
                return 'not associated with your school';
            }
        }

        $hasRole = $inviteUser->roles->contains(
            fn ($role) => strtolower(str_replace(' ', '', $role->name)) === $normalizedRole
        );

        if (!$hasRole) {
            return "not associated with the role {$requestRole}";
        }

        return null;
    }

    private function bulkAssignInviteFolders(array $assignments, array &$inviteUsers): void
    {
        if ($assignments === []) {
            return;
        }

        $userIds = collect($assignments)->pluck('user_id')->unique()->values();
        $jobIds = collect($assignments)->pluck('ts_job_id')->unique()->values();
        $folderIds = collect($assignments)->pluck('ts_folder_id')->unique()->values();

        $existingJobUsers = JobUser::whereIn('user_id', $userIds)
            ->whereIn('ts_job_id', $jobIds)
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->ts_job_id}|{$row->user_id}" => true]);

        $existingFolderUsers = FolderUser::whereIn('user_id', $userIds)
            ->whereIn('ts_folder_id', $folderIds)
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->ts_folder_id}|{$row->user_id}" => true]);

        $now = now();
        $newJobUsers = [];
        $newFolderUsers = [];

        foreach ($assignments as $assignment) {
            $userId = $assignment['user_id'];
            $jobId = $assignment['ts_job_id'];
            $folderId = $assignment['ts_folder_id'];

            $inviteUsers[] = $userId;

            $jobUserKey = "{$jobId}|{$userId}";
            if (!isset($existingJobUsers[$jobUserKey])) {
                $newJobUsers[] = [
                    'ts_job_id' => $jobId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existingJobUsers[$jobUserKey] = true;
            }

            $folderUserKey = "{$folderId}|{$userId}";
            if (!isset($existingFolderUsers[$folderUserKey])) {
                $newFolderUsers[] = [
                    'ts_folder_id' => $folderId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existingFolderUsers[$folderUserKey] = true;
            }
        }

        if ($newJobUsers !== []) {
            JobUser::insert($newJobUsers);
        }

        if ($newFolderUsers !== []) {
            FolderUser::insert($newFolderUsers);
        }
    }
    
    public function revokeJobUser($userId, $tsJobId)
    {
        // Decrypt the IDs
        $user = Crypt::decryptString($userId);
        $job = Crypt::decryptString($tsJobId);

        FolderUser::where('user_id', $user)
            ->whereHas('folder.job', function ($query) use ($job) {
                $query->where('ts_job_id', $job);
            })
            ->delete();
    
        // Delete JobUser record using decrypted values
        JobUser::where([['ts_job_id', $job], ['user_id', $user]])->delete();
        
        // Delete the entry in emails table
        $userObj = User::find($user);
        $jobObj = Job::where('ts_job_id', $job)->first();
        
        if ($userObj && $jobObj) {
            Email::where('status_id', $this->statusService->pending)
                ->where('email_to', $userObj->email)
                ->where('ts_jobkey', $jobObj->ts_jobkey)
                ->delete();
        }
    
        // Redirect back to the previous page
        return redirect()->back();
    }
    
    public function revokeFolderUser($userId, $tsFolderId, $tsJobId)
    {
        // Decrypt the IDs
        $user = Crypt::decryptString($userId);
        $folder = Crypt::decryptString($tsFolderId);
        $job = Crypt::decryptString($tsJobId);
  
        // Delete FolderUser record using decrypted values and check job ID using 'whereHas'
        FolderUser::where([['ts_folder_id', $folder], ['user_id', $user]])
            ->whereHas('folder.job', function ($query) use ($job) {
                $query->where('ts_job_id', $job);
            })
            ->delete();

        // Update pending invitation email with the user's remaining accessible folders
        $userObj = User::find($user);
        $jobObj  = Job::where('ts_job_id', $job)->first();

        if ($userObj && $jobObj) {
            $pendingEmail = Email::where('status_id', $this->statusService->pending)
                ->where('email_to', $userObj->email)
                ->where('ts_jobkey', $jobObj->ts_jobkey)
                ->first();

            if ($pendingEmail) {
                // Fetch remaining folder names for this user on this job (after deletion)
                $remainingFolders = $this->emailService->getInvitationFolderNamesForUser(
                    (int) $user,
                    $jobObj->ts_jobkey
                );

                // Re-render the email content with updated folder list
                $updatedContent = $this->emailService->rebuildInvitationEmailContent(
                    $pendingEmail,
                    $userObj,
                    $remainingFolders,
                    $jobObj
                );

                if ($updatedContent) {
                    $pendingEmail->update(['email_content' => $updatedContent]);
                }
            }
        }
    
        // Redirect back to the previous page
        return redirect()->back();
    }

    public function getAccountExpirationDate()
    {
        $days = 5000;

        // Fallback to 365 days if the setting is less than or equal to zero
        if ($days <= 0) {
            $days = 365;
        }

        // Get the end of the day for the expiration date
        return Carbon::now()->addDays($days)->endOfDay();
    }
}
