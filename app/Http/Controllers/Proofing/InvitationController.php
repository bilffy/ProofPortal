<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\Crypt;
use App\Http\Resources\UserResource;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\FolderUser;
use App\Models\Franchise;
use App\Models\JobUser;
use App\Models\Folder;
use App\Models\User;
use App\Models\School;
use Carbon\Carbon;
use Session;
use Auth;


class InvitationController extends Controller
{
    public function __construct(EncryptDecryptService $encryptDecryptService, JobService $jobService, StatusService $statusService, EmailService $emailService)
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
        $tsJobId = $selectedJob->ts_job_id;
        // Fetch the users with roles "Teacher" or "Photo Coordinator" for the given ts_job_id
        $users = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Teacher', 'Photo Coordinator', 'Franchise']); // Filter roles
        })
        ->whereIn('id', function ($query) use ($tsJobId) {
            $query->select('user_id')
                ->from('school_app.job_users')
                ->where('ts_job_id', $tsJobId);
        })
        ->get();

        $photocoordinators = [];
        $teachers = [];
        $otherList = [];
        foreach ($users as $user) {
            if ($user->hasRole('Photo Coordinator')) {
                $photocoordinators[] = $user; // Add to photocoordinators array
            } elseif ($user->hasRole('Teacher')) {
                $teachers[] = $user; // Add to teachers array
            }else{
                $otherList[] = $user; // Add to teachers array
            }
        }

        $user = Auth::user();
        return view('proofing.franchise.invitations.manage_photocoordinator_teacher', [
            'selectedJob' => $selectedJob,
            'photocoordinators' => $photocoordinators,
            'teachers' => $teachers,
            'otherList' => $otherList,
            'user' => new UserResource($user)
        ]);
    }

    public function showInvitation()
    {
        return view('proofing.franchise.invitations.show-invitation');
    }

    public function index($role = null)
    { 
        $selectedJob = Session::get('selectedJob');
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

    // public function inviteSingle($role, $jobKeyHash)
    // {
    //     $selectedJob = $this->jobService->getJobByJobKey($this->getDecryptData($jobKeyHash))->first();
    //     $user = Auth::user(); 
    //     $userLevels = $user->isFranchiseLevel() ? Franchise::orderBy('name')->where('id', '=', $user->getFranchise()->id)->get() : [];
    //     foreach ($userLevels as $userLevel) {
    //         $emails[] = User::whereHas('franchises', function ($query) use ($userLevel) {
    //             $query->where('franchise_id', $userLevel->id);
    //         })
    //         ->whereNotNull('email') // Ensure the email is registered
    //         ->pluck('email')
    //         ->toArray(); // Ensure the plucked emails are converted to an array
    //     }

    //     return view('proofing.franchise.invitations.invitation_single', [
    //         'expiryDate' => $this->getAccountExpirationDate(),
    //         'role' => $role,
    //         'selectedJob' => $selectedJob,
    //         'emails' => $emails,
    //         'user' => new UserResource($user)
    //     ]);
    // }

    public function inviteSingle($role, $jobKeyHash)
    {
        $selectedJob = $this->jobService
            ->getJobByJobKey($this->getDecryptData($jobKeyHash))
            ->first();
    
        $user = Auth::user(); 
        $franchise = $user->getFranchise();
    
        $emails = [];
    
        if ($franchise) {
            // 1️⃣ Users who belong to the same franchise
            $franchiseUserEmails = User::whereHas('franchises', function ($query) use ($franchise) {
                    $query->where('franchise_id', $franchise->id);
                })
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
    
            $emails = array_merge($emails, $franchiseUserEmails);
    
            // 2️⃣ Users who belong to schools under the franchise
            $schoolIds = School::whereHas('franchises', function ($query) use ($franchise) {
                    $query->where('franchise_id', $franchise->id);
                })
                ->pluck('id');
    
            if ($schoolIds->isNotEmpty()) {
                $schoolUserEmails = User::whereHas('schools', function ($query) use ($schoolIds) {
                        $query->whereIn('school_id', $schoolIds);
                    })
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->toArray();
    
                $emails = array_merge($emails, $schoolUserEmails);
            }
    
            // 3️⃣ Users with 'admin' role (anywhere in the system)
            $adminEmails = User::role(['Super Admin', 'Admin'])
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
    
            $emails = array_merge($emails, $adminEmails);
        }
    
        // 4️⃣ Clean and flatten
        $emails = collect($emails)
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    
        return view('proofing.franchise.invitations.invitation_single', [
            'expiryDate' => $this->getAccountExpirationDate(),
            'role' => $role,
            'selectedJob' => $selectedJob,
            'emails' => $emails,
            'user' => new UserResource($user)
        ]);
    }
    

    public function inviteMulti($role, $jobKeyHash)
    {
        $selectedJob = $this->jobService->getJobByJobKey($this->getDecryptData($jobKeyHash))->first(); 
        $user = Auth::user();

        return view('proofing.franchise.invitations.invitation_multi', [
            'expiryDate' => $this->getAccountExpirationDate(),
            'role' => $role,
            'selectedJob' => $selectedJob,
            'user' => new UserResource($user)
        ]);
    }

    public function inviteSend(Request $request)
    {
        $errorMessages = [];
        $inviteUsers = [];
        $user = Auth::user();

        // Get emails for the user's franchise
        // $emails = $user->isFranchiseLevel()
        //     ? User::whereHas('franchises', fn($query) => $query->where('franchise_id', $user->getFranchise()->id))
        //         ->whereNotNull('email')
        //         ->pluck('email')
        //         ->toArray()
        //     : [];

        // Process multiple people or a single email
        $peopleArray = $request->people ? json_decode($request->people, true) : [[null, null, $request->email, $request->folder]];

        
        foreach ($peopleArray as $person) {
            $email = $person[2] ?? null;
            $folderKey = $person[3] ?? null;

            if($email){
                // if (!in_array($email, $emails)) {
                //     $errorMessages[] = "Email - {$email} does not exist.";
                //     continue;
                // }

                $inviteUser = User::where('email', $email)->select('id')->first();

                $folders = $folderKey === '*'
                    ? Folder::whereHas('job', fn($query) => $query->where('ts_jobkey', $request->job_key))
                        ->select('ts_folder_id','ts_job_id')
                        ->get()
                    : Folder::whereHas('job', fn($query) => $query->where('ts_jobkey', $request->job_key))
                        ->where('ts_folderkey', $folderKey)
                        ->select('ts_folder_id','ts_job_id')
                        ->get();

                foreach ($folders as $folder) {
                    $this->saveFolderUser($folder->ts_job_id, $folder->ts_folder_id, $inviteUser->id, $inviteUsers);
                }
            }
        }

        // Save email folder content for unique users
        foreach (array_unique($inviteUsers) as $inviteUserId) {
            $this->emailService->saveInvitationContent(
                $request->role,
                $inviteUserId,
                Carbon::now(),
                $request->job_key
            );
        }

        // Flash error or success messages
        if ($errorMessages) {
            session()->flash('errors', array_unique($errorMessages));
        } else {
            session()->flash('success', 'Invitations sent successfully.');
        }

        return redirect()->back();
    }

    /**
     * Save user-folder association without duplication.
     */
    private function saveFolderUser($tsJobId, $folderId, $userId, &$inviteUsers)
    {
        if (!JobUser::where([['ts_job_id', $tsJobId], ['user_id', $userId]])->exists()) {
            JobUser::create([
                'ts_job_id' => $tsJobId,
                'user_id' => $userId,
            ]);
        }

        if (!FolderUser::where([['ts_folder_id', $folderId], ['user_id', $userId]])->exists()) {
            FolderUser::create([
                'ts_folder_id' => $folderId,
                'user_id' => $userId,
            ]);
        }

        $inviteUsers[] = $userId;
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
