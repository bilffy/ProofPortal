<?php

namespace App\Services\Proofing;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use App\Services\Proofing\StatusService;
use App\Models\EmailCategory;
use App\Models\FolderUser;
use App\Models\Template;
use App\Models\Folder;
use App\Models\Status;
use App\Models\Email;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Address;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Part\TextPart;
use Auth;
use DB;


class EmailService
{
    /**
     * Create a new class instance.
     */
    protected $statusService;

    public function __construct(StatusService $statusService)
    {
        // 
        $this->statusService = $statusService;
    }

    protected function generateEmail($authUser, $recipient, $subject, $content, $sentDate): SymfonyEmail
    {
        $htmlPart = new TextPart($content, 'utf-8', 'html', 'base64');
        $dateTime = Carbon::parse($sentDate);
        $email = (new SymfonyEmail())
            // ->from(new Address($authUser->email, $authUser->name))
            ->from(new Address('noreply@msp.com.au', 'MSP Portal - Do Not Reply'))
            ->to(new Address($recipient->email, $recipient->name))
            ->subject($subject)
            ->setBody($htmlPart)
            ->date($dateTime);

        // DO NOT manually set Message-ID here. 
        // Symfony Mailer will generate a valid one automatically.
        
        return $email;
    }
    
    protected function storeEmailRecord($authUser, $selectedJob, $recipient, $template, $date, $emlContent, $tsJobKey)
    {
        return Email::create([
            'generated_from_user_id' => $authUser->id,
            'alphacode' => $selectedJob->franchises->alphacode ?? null,
            'ts_jobkey' => $tsJobKey,
            'ts_schoolkey' => $selectedJob->ts_schoolkey,
            'sentdate' => $date,
            'email_from' => $authUser->email,
            'email_to' => $recipient->email,
            'email_content' => $emlContent,
            'template_id' => $template->id,
            'status_id' => $this->statusService->pending
        ]);
      
    }

    private function isProofScheduleTemplate(?string $templateName): bool
    {
        return in_array($templateName, [
            'proof_start',
            'proof_warning',
            'proof_due',
            'proof_catchup',
        ], true);
    }

    /**
     * Expire/soft-delete all pending emails for a job.
     */
    public function expirePendingEmailsForJob(string $jobKey): int
    {
        return Email::where('ts_jobkey', $jobKey)
            ->where('status_id', $this->statusService->pending)
            ->update([
                'status_id' => $this->statusService->expired,
                'deleted_at' => now(),
            ]);
    }

    /**
     * Completed jobs should not generate emails, except completion notifications
     * created at the moment the job/folder is marked completed.
     */
    private function shouldBlockEmailGenerationForJob(?Job $job, ?string $templateName = null): bool
    {
        if (!$job || (int) $job->job_status_id !== (int) $this->statusService->completed) {
            return false;
        }

        return !in_array($templateName, [
            'job_status_completed',
            'folder_status_completed',
        ], true);
    }

    /**
     * Master switch: matrix role flags are ignored while notifications are off.
     */
    private function areJobNotificationsEnabled(?Job $job): bool
    {
        return $job && (int) $job->notifications_enabled === 1;
    }

    private function findJobByKey(string $jobKey): ?Job
    {
        return Job::where('ts_jobkey', $jobKey)->first();
    }

    /**
     * Re-evaluate pending proof schedule emails for a job.
     * Call after folders are unlocked/reopened so users who were skipped
     * while their folders were Completed can receive them again.
     */
    public function refreshProofScheduleEmails(string $jobKey): void
    {
        $job = $this->findJobByKey($jobKey);
        if ($this->shouldBlockEmailGenerationForJob($job) || !$this->areJobNotificationsEnabled($job)) {
            return;
        }

        foreach (['proof_start', 'proof_warning', 'proof_due', 'proof_catchup'] as $field) {
            $this->updateEmailSend($field, $jobKey);
        }
    }

    /**
     * All folder names for a job that are visible for proofing.
     */
    private function getVisibleProofingFolderNames($folders): array
    {
        return $folders
            ->filter(fn ($folder) => (int) ($folder->is_visible_for_proofing ?? 0) === 1)
            ->pluck('ts_foldername')
            ->values()
            ->toArray();
    }

    /**
     * Folder names assigned to the user within the given job folders.
     * Only includes folders with is_visible_for_proofing = 1.
     * For proof schedule templates, Completed folders are also excluded.
     */
    private function getAssignedFolderNamesForUser($folders, int $userId, bool $excludeCompleted = false): array
    {
        $completedStatusId = $this->statusService->completed;

        return $folders
            ->filter(function ($folder) use ($userId, $excludeCompleted, $completedStatusId) {
                if ((int) ($folder->is_visible_for_proofing ?? 0) !== 1) {
                    return false;
                }

                if ($excludeCompleted && (int) $folder->status_id === (int) $completedStatusId) {
                    return false;
                }

                return DB::table('folder_users')
                    ->where('ts_folder_id', $folder->ts_folder_id)
                    ->where('user_id', $userId)
                    ->exists();
            })
            ->pluck('ts_foldername')
            ->values()
            ->toArray();
    }
    
    public function updateEmailSend($field, $decryptedJobKey)
    {
        $authUser = Auth::user();
    
        $template = Template::where('template_name', $field)->firstOrFail();
    
        $columnsToSelect = [
            'id',
            'notifications_matrix',
            'notifications_enabled',
            'ts_schoolkey',
            'ts_account_id',
            'ts_job_id',
            'ts_jobname',
            'proof_due',
            'job_status_id'
        ];
    
        if (Schema::hasColumn('jobs', $field)) {
            $columnsToSelect[] = $field;
        }
    
        $selectedJob = Job::with(['franchises', 'folders'])
            ->where('ts_jobkey', $decryptedJobKey)
            ->select($columnsToSelect)
            ->firstOrFail();

        if (!$selectedJob) {
            abort(404); 
        }

        // Matrix may still hold role flags; do not create/update emails while off
        if ($this->shouldBlockEmailGenerationForJob($selectedJob, $field) || !$this->areJobNotificationsEnabled($selectedJob)) {
            return;
        }
    
        $sentDate = $selectedJob->$field;
    
        if (empty($sentDate)) {
            return;
        }
    
        $sentDateCarbon = Carbon::parse($sentDate);
    
        $allJobFolders = $this->getVisibleProofingFolderNames($selectedJob->folders);
        $notificationsMatrix = json_decode($selectedJob->notifications_matrix, true) ?? [];
    
        /**
         * --------------------------------------------
         * Roles with email enabled
         * --------------------------------------------
         */
        $rolesWithEmailEnabled = [];
    
        if (!empty($notificationsMatrix['schools'][$field])) {
            foreach ($notificationsMatrix['schools'][$field] as $role => $enabled) {
                if ($enabled === true) {
                    $rolesWithEmailEnabled[] = $role;
                }
            }
        }
    
        if (empty($rolesWithEmailEnabled)) {
            $isDelete = Email::where('template_id', $template->id)
            ->where('ts_jobkey', $decryptedJobKey)
            ->where('status_id', $this->statusService->pending)
            ->whereDate('sentdate', $sentDateCarbon)
            ->where('email_from', $authUser->email)
            ->update([
                'status_id' => $this->statusService->expired, // Sets status to Expired
                'deleted_at' => now()
            ]);

            return;
        }
    
        $roleNames = array_unique(array_map(fn ($role) =>
            str_replace(
                ['franchise', 'photocoordinator', 'teacher'],
                ['Franchise', 'Photo Coordinator', 'Teacher'],
                $role
            ),
            $rolesWithEmailEnabled
        ));
    
        $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->toArray();
    
        if (empty($roleIds)) {
            return;
        }
    
        /**
         * --------------------------------------------
         * Fetch users
         * --------------------------------------------
         */
        $users = User::whereHas('roles', fn ($q) => $q->whereIn('id', $roleIds))
            ->whereHas('jobs', fn ($q) => $q->where('jobs.ts_job_id', $selectedJob->ts_job_id))
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();
    
        /**
         * --------------------------------------------
         * DELETE stale emails (ONCE)
         * --------------------------------------------
         */
        $validEmails = $users->pluck('email')->toArray();

        $isDelete = Email::where('template_id', $template->id)
            ->where('ts_jobkey', $decryptedJobKey)
            ->where('status_id', $this->statusService->pending)
            ->whereDate('sentdate', $sentDateCarbon)
            ->where('email_from', $authUser->email)
            ->whereNotIn('email_to', $validEmails)
            ->update([
                'status_id' => $this->statusService->expired, // Sets status to Expired
                'deleted_at' => now()
            ]);
    
        /**
         * --------------------------------------------
         * INSERT missing emails
         * --------------------------------------------
         */
        $excludeCompleted = $this->isProofScheduleTemplate($field);

        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        if (!File::exists($templatePath)) {
            throw new \Exception("Email template not found: {$templatePath}");
        }
        $templateContent = File::get($templatePath);
        $statusModel = Status::find($selectedJob->job_status_id);

        foreach ($users as $user) {
            $userFolders = $this->getAssignedFolderNamesForUser(
                $selectedJob->folders,
                $user->id,
                $excludeCompleted
            );

            // No non-Completed folders assigned → do not send proof schedule emails
            if ($excludeCompleted && empty($userFolders)) {
                Email::where('template_id', $template->id)
                    ->where('ts_jobkey', $decryptedJobKey)
                    ->where('status_id', $this->statusService->pending)
                    ->whereDate('sentdate', $sentDateCarbon)
                    ->where('email_to', $user->email)
                    ->update([
                        'status_id' => $this->statusService->expired,
                        'deleted_at' => now(),
                    ]);
                continue;
            }

            $pendingEmailQuery = Email::where('template_id', $template->id)
                ->where('ts_jobkey', $decryptedJobKey)
                ->where('status_id', $this->statusService->pending)
                ->whereDate('sentdate', $sentDateCarbon)
                ->where('email_to', $user->email);

            // Non-proof templates stay scoped to the generating user.
            if (!$excludeCompleted) {
                $pendingEmailQuery->where('email_from', $authUser->email);
            }

            $emailRecord = $pendingEmailQuery->first();

            // Non-proof schedule: keep existing pending email as-is.
            if ($emailRecord && !$excludeCompleted) {
                continue;
            }

            $data = [
                'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                'FOLDERS' => $userFolders,
                'ALLFOLDERS' => $allJobFolders,
                'JOB_NAME' => $selectedJob->ts_jobname ?? '',
                'REVIEW_DUE' => $selectedJob->proof_due
                    ? Carbon::parse($selectedJob->proof_due)->format('l j F, Y')
                    : '',
                'FRANCHISE_NAME' => $user->getSchoolOrFranchiseDetail()->name ?? '',
                'FRANCHISE_PHONE' => $user->getSchoolOrFranchiseDetail()->phone ?? '',
                'FRANCHISE_EMAIL' => $user->getSchoolOrFranchiseDetail()->email ?? '',
                'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                'FRANCHISE_ADDRESS1' => $user->getSchoolOrFranchiseDetail()->address ?? '',
                'FRANCHISE_SUBURB' => $user->getSchoolOrFranchiseDetail()->suburb ?? '',
                'FRANCHISE_STATE' => $user->getSchoolOrFranchiseDetail()->state ?? '',
                'FRANCHISE_POSTCODE' => $user->getSchoolOrFranchiseDetail()->postcode ?? '',
                'APP_URL' => Config::get('app.url'),
                'JOB_STATUS_NAME' => $statusModel->status_external_name ?? '',
            ];

            $processedContent = $this->replaceTemplateVariables($templateContent, $data);

            $templateSubject = $template->template_subject;
            if (strpos($template->template_subject, 'JOB_NAME') !== false) {
                $templateSubject = str_replace('JOB_NAME', $selectedJob->ts_jobname, $template->template_subject);
            }
            $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $sentDate);
            $emlContent = MessageConverter::toEmail($emailMessage)->toString();

            if ($emailRecord) {
                // Proof schedule: refresh folder list when more folders are unlocked
                $emailRecord->update([
                    'email_content' => $emlContent,
                    'sentdate' => $sentDate,
                ]);
            } else {
                $this->storeEmailRecord($authUser, $selectedJob, $user, $template, $sentDate, $emlContent, $decryptedJobKey);
            }
        }
    }
    

    public function saveEmailContent($tsJobKey, $field, $date, $status = null)
    {
        $authUser = Auth::user();
    
        // Fetch the template
        $template = Template::where('template_name', $field)->firstOrFail();
    
        // Fetch the job data
        $hasDateColumn = Schema::hasColumn('jobs', $field);
        $columnsToSelect = ['id', 'notifications_matrix', 'notifications_enabled', 'ts_schoolkey', 'ts_account_id', 'ts_job_id', 'ts_jobname', 'proof_due', 'job_status_id'];
        if ($hasDateColumn) {
            $columnsToSelect[] = $field;
        }
    
        $selectedJob = Job::with(['franchises','folders'])
            ->where('ts_jobkey', $tsJobKey)
            ->select($columnsToSelect)
            ->firstOrFail();

        if (!$selectedJob) {
            abort(404); 
        }

        if ($this->shouldBlockEmailGenerationForJob($selectedJob, $field)) {
            return;
        }

        // Ensure the job object has the updated date for placeholders
        if ($hasDateColumn) {
            $selectedJob->$field = $date;
        }
    
        $allJobFolders = $this->getVisibleProofingFolderNames($selectedJob->folders);
        $notificationsMatrix = json_decode($selectedJob->notifications_matrix, true);
    
        $rolesWithFieldTrue = $notificationsMatrix['schools'][$field] ?? [];
        $rolesWithFieldTrue = array_keys(array_filter($rolesWithFieldTrue, fn($v) => $v === true));
        // \Log::info('saveEmailContent roles check', ['field' => $field, 'roles' => $rolesWithFieldTrue, 'notifications_enabled' => $selectedJob->notifications_enabled]);
   
        // Prepare template content
        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        $templateContent = File::exists($templatePath) ? File::get($templatePath) : '';
        $statusModel = $status ? Status::find($status) : null;
    
        if (!empty($rolesWithFieldTrue) && $this->areJobNotificationsEnabled($selectedJob)) {

            $roleNames = array_map(fn($role) => str_replace(
                ['franchise', 'photocoordinator', 'teacher'],
                ['Franchise', 'Photo Coordinator', 'Teacher'],
                $role // <-- only the single role
            ), $rolesWithFieldTrue);
            
    
            $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
    
            $userIds = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
                ->whereHas('jobs', fn($q) => $q->where('jobs.ts_job_id', $selectedJob->ts_job_id))
                ->pluck('id');
    
            $users = User::whereIn('id', $userIds)->select('id','name','email','firstname','lastname')->get();
            // \Log::info('saveEmailContent users found', ['count' => $users->count(), 'job' => $tsJobKey]);
    
            $excludeCompleted = $this->isProofScheduleTemplate($field);

            foreach ($users as $user) {
                $userFolders = $this->getAssignedFolderNamesForUser(
                    $selectedJob->folders,
                    $user->id,
                    $excludeCompleted
                );

                // No non-Completed folders assigned → do not send proof schedule emails
                if ($excludeCompleted && empty($userFolders)) {
                    Email::where([
                        'generated_from_user_id' => $authUser->id,
                        'alphacode' => $selectedJob->franchises->alphacode ?? null,
                        'ts_jobkey' => $tsJobKey,
                        'ts_schoolkey' => $selectedJob->ts_schoolkey,
                        'email_from' => $authUser->email,
                        'email_to' => $user->email,
                        'template_id' => $template->id,
                        'status_id' => $this->statusService->pending,
                    ])->update([
                        'status_id' => $this->statusService->expired,
                        'deleted_at' => now(),
                    ]);
                    continue;
                }

                $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
                if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
                        throw new \Exception("Template file not found at {$templatePath}");
                }
                $templateContent = File::get($templatePath);
    
                $data = [
                    'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                    'FOLDERS' => $userFolders,
                    'ALLFOLDERS' => $allJobFolders,
                    'JOB_NAME' => $selectedJob->ts_jobname ?? '',
                    'REVIEW_DUE' => isset($selectedJob->proof_due) ? Carbon::parse($selectedJob->proof_due)->format('l j F, Y') : '',
                    'FRANCHISE_NAME' => $user->getSchoolOrFranchiseDetail()->name ?? '',
                    'FRANCHISE_PHONE' => $user->getSchoolOrFranchiseDetail()->phone ?? '',
                    'FRANCHISE_EMAIL' => $user->getSchoolOrFranchiseDetail()->email ?? '',
                    'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                    'FRANCHISE_ADDRESS1' => $user->getSchoolOrFranchiseDetail()->address ?? '',
                    'FRANCHISE_SUBURB' => $user->getSchoolOrFranchiseDetail()->suburb ?? '',
                    'FRANCHISE_STATE' => $user->getSchoolOrFranchiseDetail()->state ?? '',
                    'FRANCHISE_POSTCODE' => $user->getSchoolOrFranchiseDetail()->postcode ?? '',
                    'APP_URL' => Config::get('app.url'),
                    'JOB_STATUS_NAME' => $statusModel->status_external_name ?? '',
                ];
    
                $processedContent = $this->replaceTemplateVariables($templateContent, $data);
                //update the subject with the job name
                $templateSubject = $template->template_subject;
                if (strpos($template->template_subject, 'JOB_NAME') !== false) {
                    $templateSubject = str_replace('JOB_NAME', $selectedJob->ts_jobname, $template->template_subject);
                }
                $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $date);
    
                $emlContent = MessageConverter::toEmail($emailMessage)->toString();
    
                // Save email record per user
                $emailRecord = Email::where([
                    'generated_from_user_id' => $authUser->id,
                    'alphacode' => $selectedJob->franchises->alphacode ?? null,
                    'ts_jobkey' => $tsJobKey,
                    'ts_schoolkey' => $selectedJob->ts_schoolkey,
                    'email_from' => $authUser->email,
                    'email_to' => $user->email,
                    'template_id' => $template->id,
                    'status_id' => $this->statusService->pending
                ])->first();
    
                if ($emailRecord) {
                    // Update existing
                    $emailRecord->update([
                        'sentdate' => $date,
                        'email_content' => $emlContent
                    ]);
                } else {
                    // Insert new
                    $this->storeEmailRecord($authUser, $selectedJob, $user, $template, $date, $emlContent, $tsJobKey);
                }
            }
        } 
    }    
    
    public function saveEmailFolderContent($tsFolderIds, $field, $date, $status = null)
    {
        $authUser = Auth::user();
        $template = Template::where('template_name', $field)->first();
        $selectedFolders = Folder::with('job.franchises');
        if (is_array($tsFolderIds)) {
            $selectedFolders = $selectedFolders->whereIn('ts_folder_id', $tsFolderIds)->get();
        } else {
            $selectedFolders = $selectedFolders->where('ts_folder_id', $tsFolderIds)->get();
        }

        $selectedFolder = $selectedFolders->first();
        if (!$selectedFolder || !$selectedFolder->job) {
            return response()->json(['error' => 'No folder or job found'], 404);
        }

        if ($this->shouldBlockEmailGenerationForJob($selectedFolder->job, $field)
            || !$this->areJobNotificationsEnabled($selectedFolder->job)) {
            return;
        }

        $changedFolderIds = $selectedFolders->pluck('ts_folder_id')->filter()->values()->toArray();
        if (empty($changedFolderIds)) {
            return;
        }
    
        $notificationsMatrix = json_decode($selectedFolder->job->notifications_matrix, true) ?? [];
        $rolesWithFieldTrue = $notificationsMatrix['folders'][$field] ?? [];
        $roleNames = array_keys(array_filter($rolesWithFieldTrue, fn($value) => $value === true));
    
        $mappedRoleNames = array_map(fn($role) => str_replace(
            ['franchise', 'photocoordinator', 'teacher'],
            ['Franchise', 'Photo Coordinator', 'Teacher'],
            $role
        ), $roleNames);
    
        $roleIds = Role::whereIn('name', $mappedRoleNames)->pluck('id')->toArray();
        if (empty($roleIds)) {
            return;
        }

        // Only users with the configured role who are assigned to the changed folder(s).
        $users = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
            ->whereHas('jobs', fn($q) => $q->where('jobs.ts_job_id', $selectedFolder->job->ts_job_id))
            ->whereExists(function ($query) use ($changedFolderIds) {
                $query->selectRaw('1')
                    ->from('folder_users')
                    ->whereColumn('folder_users.user_id', 'users.id')
                    ->whereIn('folder_users.ts_folder_id', $changedFolderIds);
            })
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $userFolderNames = FolderUser::query()
            ->whereIn('folder_users.user_id', $users->pluck('id'))
            ->whereIn('folder_users.ts_folder_id', $changedFolderIds)
            ->join('folders', 'folders.ts_folder_id', '=', 'folder_users.ts_folder_id')
            ->select('folder_users.user_id', 'folders.ts_foldername')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('ts_foldername')->unique()->values()->toArray());

        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
            throw new \Exception("Template file not found at {$templatePath}");
        }
        $templateContent = File::get($templatePath);
    
        $statusName = Status::where('id', $status)->value('status_external_name') ?? '';

        foreach ($users as $user) {
            $folderNamesArray = $userFolderNames->get($user->id, []);
            if (empty($folderNamesArray)) {
                continue;
            }

            $folderNames = implode(', ', $folderNamesArray);
            $schoolOrFranchise = $user->getSchoolOrFranchiseDetail();
            $userData = [
                'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                'FRANCHISE_NAME' => $schoolOrFranchise->name ?? '',
                'FRANCHISE_PHONE' => $schoolOrFranchise->phone ?? '',
                'FRANCHISE_EMAIL' => $schoolOrFranchise->email ?? '',
                'FRANCHISE_ADDRESS1' => $schoolOrFranchise->address ?? '',
                'FRANCHISE_SUBURB' => $schoolOrFranchise->suburb ?? '',
                'FRANCHISE_STATE' => $schoolOrFranchise->state ?? '',
                'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                'FRANCHISE_POSTCODE' => $schoolOrFranchise->postcode ?? '',
            ];

            $constantData = [
                'JOB_NAME'           => $selectedFolder->job->ts_jobname ?? '',
                'FOLDER_NAME'        => $folderNames,
                'FOLDER_NAMES'       => $folderNamesArray,
                'FOLDER_STATUS_NAME' => $statusName,
                'APP_URL'            => Config::get('app.url'),
                'FRANCHISE_WEB_ADDRESS' => 'www.msp.com.au',
            ];
    
            $data = array_merge($constantData, $userData);
            $processedContent = $this->replaceTemplateVariables($templateContent, $data);

            $templateSubject = $template->template_subject;

            // Safely replace JOB_NAME
            if (strpos($templateSubject, 'JOB_NAME') !== false) {
                $jobName = $selectedFolder->job->ts_jobname ?? 'Unknown Job';
                $templateSubject = str_replace('JOB_NAME', $jobName, $templateSubject);
            }

            // Safely replace FOLDER_NAME
            if (strpos($templateSubject, 'FOLDER_NAME') !== false) {
                $templateSubject = str_replace('FOLDER_NAME', $folderNames ?: 'Unknown Folder', $templateSubject);
            }

            $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $date);
            $emlContent = MessageConverter::toEmail($emailMessage)->toString();

            $this->storeEmailRecord($authUser, $selectedFolder->job, $user, $template, $date, $emlContent, $selectedFolder->job->ts_jobkey);
        }
    }   

    /**
     * For each newly invited user, insert pending email records for the 4
     * scheduled notification templates (proof_start, proof_warning, proof_due,
     * proof_catchup) using the job's corresponding date columns as sentdate.
     *
     * Template mapping:
     *   ID 1  – proof_start    → jobs.proof_start
     *   ID 2  – proof_warning  → jobs.proof_warning
     *   ID 3  – proof_due      → jobs.proof_due
     *   ID 4  – proof_catchup  → jobs.proof_catchup
     */
    public function saveScheduledEmailsForInviteUsers(array $userIds, string $jobKey): void
    {
        $authUser = Auth::user();

        // Load the job with its date columns and related data
        $selectedJob = Job::with(['franchises', 'folders'])
            ->where('ts_jobkey', $jobKey)
            ->select(['id', 'ts_job_id', 'ts_jobkey', 'ts_jobname', 'ts_schoolkey', 'ts_account_id',
                      'proof_start', 'proof_warning', 'proof_due', 'proof_catchup', 'job_status_id',
                      'notifications_matrix', 'notifications_enabled'])
            ->first();

        if (!$selectedJob) {
            return;
        }

        if ($this->shouldBlockEmailGenerationForJob($selectedJob) || !$this->areJobNotificationsEnabled($selectedJob)) {
            return;
        }

        // Map template_name → job column
        $templateDateMap = [
            'proof_start'   => $selectedJob->proof_start,
            'proof_warning' => $selectedJob->proof_warning,
            'proof_due'     => $selectedJob->proof_due,
            'proof_catchup' => $selectedJob->proof_catchup,
        ];

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();

        foreach ($templateDateMap as $templateName => $sentDate) {
            // Skip if the job doesn't have this date configured
            if (empty($sentDate)) {
                continue;
            }

            $template = Template::where('template_name', $templateName)->first();
            if (!$template) {
                continue;
            }

            $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
            if (!File::exists($templatePath)) {
                continue;
            }
            $templateContent = File::get($templatePath);

            $allJobFolders = $this->getVisibleProofingFolderNames($selectedJob->folders);
            $statusModel   = Status::find($selectedJob->job_status_id);

            foreach ($users as $user) {
                // Resolve visible, non-Completed folders assigned to this user.
                $userFolders = $this->getAssignedFolderNamesForUser(
                    $selectedJob->folders,
                    $user->id,
                    true
                );

                // No non-Completed folders assigned → do not send proof schedule emails
                if (empty($userFolders)) {
                    Email::where('template_id', $template->id)
                        ->where('ts_jobkey', $jobKey)
                        ->where('email_to', $user->email)
                        ->where('status_id', $this->statusService->pending)
                        ->update([
                            'status_id' => $this->statusService->expired,
                            'deleted_at' => now(),
                        ]);
                    continue;
                }

                // Skip if a pending record already exists for this user + template + job
                // (unless we need to refresh folder list for proof schedule templates)
                $emailRecord = Email::where('template_id', $template->id)
                    ->where('ts_jobkey', $jobKey)
                    ->where('email_to', $user->email)
                    ->where('status_id', $this->statusService->pending)
                    ->first();

                $data = [
                    'INVITEE_FIRST_NAME'    => $user->firstname ?? '',
                    'FOLDERS'               => $userFolders,
                    'ALLFOLDERS'            => $allJobFolders,
                    'JOB_NAME'              => $selectedJob->ts_jobname ?? '',
                    'REVIEW_DUE'            => $selectedJob->proof_due
                                                ? Carbon::parse($selectedJob->proof_due)->format('l j F, Y')
                                                : '',
                    'FRANCHISE_NAME'        => $user->getSchoolOrFranchiseDetail()->name     ?? '',
                    'FRANCHISE_PHONE'       => $user->getSchoolOrFranchiseDetail()->phone    ?? '',
                    'FRANCHISE_EMAIL'       => $user->getSchoolOrFranchiseDetail()->email    ?? '',
                    'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                    'FRANCHISE_ADDRESS1'    => $user->getSchoolOrFranchiseDetail()->address  ?? '',
                    'FRANCHISE_SUBURB'      => $user->getSchoolOrFranchiseDetail()->suburb   ?? '',
                    'FRANCHISE_STATE'       => $user->getSchoolOrFranchiseDetail()->state    ?? '',
                    'FRANCHISE_POSTCODE'    => $user->getSchoolOrFranchiseDetail()->postcode ?? '',
                    'APP_URL'               => Config::get('app.url'),
                    'JOB_STATUS_NAME'       => $statusModel->status_external_name ?? '',
                ];

                $processedContent = $this->replaceTemplateVariables($templateContent, $data);

                $templateSubject = $template->template_subject;
                if (strpos($templateSubject, 'JOB_NAME') !== false) {
                    $templateSubject = str_replace('JOB_NAME', $selectedJob->ts_jobname, $templateSubject);
                }

                $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $sentDate);
                $emlContent   = MessageConverter::toEmail($emailMessage)->toString();

                if ($emailRecord) {
                    $emailRecord->update([
                        'email_content' => $emlContent,
                        'sentdate' => $sentDate,
                    ]);
                } else {
                    $this->storeEmailRecord($authUser, $selectedJob, $user, $template, $sentDate, $emlContent, $jobKey);
                }
            }
        }
    }

    /**
     * Visible-for-proofing folder names assigned to a user on a job.
     */
    public function getInvitationFolderNamesForUser(int $userId, string $jobKey): array
    {
        return FolderUser::where('user_id', $userId)
            ->whereHas('folder', function ($query) use ($jobKey) {
                $query->where('is_visible_for_proofing', 1)
                    ->whereHas('job', function ($jobQuery) use ($jobKey) {
                        $jobQuery->where('ts_jobkey', $jobKey);
                    });
            })
            ->with('folder')
            ->get()
            ->pluck('folder.ts_foldername')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * When folder proofing visibility changes, refresh pending invitation emails
     * for users who have access to those folders so {#FOLDERS} stays current.
     */
    public function refreshPendingInvitationEmailsForFolders(array $folderIds): void
    {
        $folderIds = array_values(array_filter(array_map('intval', $folderIds), fn ($id) => $id > 0));
        if ($folderIds === []) {
            return;
        }

        $userIdsWithAccess = FolderUser::whereIn('ts_folder_id', $folderIds)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values();

        if ($userIdsWithAccess->isEmpty()) {
            return;
        }

        $jobKeys = Folder::query()
            ->whereIn('folders.ts_folder_id', $folderIds)
            ->join('jobs', 'jobs.ts_job_id', '=', 'folders.ts_job_id')
            ->distinct()
            ->pluck('jobs.ts_jobkey')
            ->filter()
            ->values();

        if ($jobKeys->isEmpty()) {
            return;
        }

        $templateIds = Template::whereIn('template_name', [
            'photocordinator_invitation',
            'teacher_invitation',
        ])->pluck('id');

        if ($templateIds->isEmpty()) {
            return;
        }

        $usersByEmail = User::whereIn('id', $userIdsWithAccess)
            ->get(['id', 'email', 'firstname', 'lastname', 'name'])
            ->keyBy('email');

        $pendingEmails = Email::whereIn('ts_jobkey', $jobKeys)
            ->whereIn('template_id', $templateIds)
            ->where('status_id', $this->statusService->pending)
            ->whereIn('email_to', $usersByEmail->keys())
            ->get();

        $jobsByKey = Job::whereIn('ts_jobkey', $jobKeys)->get()->keyBy('ts_jobkey');

        foreach ($pendingEmails as $pendingEmail) {
            $user = $usersByEmail->get($pendingEmail->email_to);
            $job = $jobsByKey->get($pendingEmail->ts_jobkey);
            if (!$user || !$job) {
                continue;
            }

            if ($this->shouldBlockEmailGenerationForJob($job)) {
                continue;
            }

            $folderNames = $this->getInvitationFolderNamesForUser((int) $user->id, $job->ts_jobkey);
            $updatedContent = $this->rebuildInvitationEmailContent(
                $pendingEmail,
                $user,
                $folderNames,
                $job
            );

            if ($updatedContent) {
                $pendingEmail->update(['email_content' => $updatedContent]);
            }
        }
    }

    public function saveInvitationContent($role, $user, $date, $jobkey)
    {
        $authUser = Auth::user();
        if($role == 'photocoordinator') {
            $field = 'photocordinator_invitation';
        } elseif($role == 'teacher') {
            $field = 'teacher_invitation';
        }

        $job = $this->findJobByKey($jobkey);
        if ($this->shouldBlockEmailGenerationForJob($job, $field ?? null)) {
            return;
        }

        $template = Template::where('template_name', $field)->first();
        $inviteUser = User::find($user);
        $selectedFolders = $this->getInvitationFolderNamesForUser((int) $user, $jobkey);
        $folderUsers = FolderUser::where('user_id', $user)
        ->whereHas('folder', function ($query) use ($jobkey) {
            $query->where('is_visible_for_proofing', 1)
                ->whereHas('job', function ($jobQuery) use ($jobkey) {
                    $jobQuery->where('ts_jobkey', $jobkey);
                });
        })
        ->with('folder.job') // Ensure the job relationship is eager-loaded
        ->get();

        $data = [
                'INVITEE_FIRST_NAME' => $inviteUser->firstname ?? '',
                'INVITEE_LAST_NAME' => $inviteUser->lastname ?? '',
                'SENDER_FIRST_NAME' => $authUser->firstname ?? '',
                'SENDER_LAST_NAME' => $authUser->lastname ?? '',
                'JOB_NAME' => $folderUsers->first()->folder->job->ts_jobname ?? '',
                'FOLDERS' => $selectedFolders,
                'REVIEW_DUE' => isset($folderUsers->first()->folder->job->proof_due) ? Carbon::parse($folderUsers->first()->folder->job->proof_due)->format('l j F, Y') : '',
                'APP_URL' => Config::get('app.url'),
                'FRANCHISE_NAME' => $authUser->getSchoolOrFranchiseDetail()->name ?? '',
                'FRANCHISE_PHONE' => $authUser->getSchoolOrFranchiseDetail()->phone ?? '',
                'FRANCHISE_EMAIL' => $authUser->getSchoolOrFranchiseDetail()->email ?? '',
                'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                'FRANCHISE_ADDRESS1' => $authUser->getSchoolOrFranchiseDetail()->address ?? '',
                'FRANCHISE_SUBURB' => $authUser->getSchoolOrFranchiseDetail()->suburb ?? '',
                'FRANCHISE_STATE' => $authUser->getSchoolOrFranchiseDetail()->state ?? '',
                'FRANCHISE_POSTCODE' => $authUser->getSchoolOrFranchiseDetail()->postcode ?? '',
        ];

        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
            throw new \Exception("Template file not found at {$templatePath}");
        }
        $templateContent = File::get($templatePath);

        $beforeProcessedContent = $this->replaceTemplateVariables($templateContent, $data);

        if ($authUser->getSchoolOrFranchiseDetail()->name === 'Sydney West') {
            $downloadInstructions = '
                <tr>
                    <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
                        <b>Download Instructions for</b><br>
                        <a href="https://www.msp.com.au/wp-content/uploads/blueprint/Online%20Proofing%20Guide%20Photo%20Coordinators.pdf" target="_blank">
                            <img src="https://www.msp.com.au/wp-content/uploads/2019/10/photoco_btn.png" width="225">
                        </a>
                    </td>
                </tr>
            ';
        } else {
            $downloadInstructions = '';
        }  
        
        $processedContent = str_replace("{DOWNLOAD_INSTRUCTIONS}", $downloadInstructions, $beforeProcessedContent); 

        $templateSubject = $template->template_subject;

        // Safely replace INVITEE_FIRST_NAME, INVITEE_LAST_NAME
        if (strpos($templateSubject, 'INVITEE_FIRST_NAME') !== false) {
            $templateSubject = str_replace('INVITEE_FIRST_NAME', $inviteUser->firstname, $templateSubject);
        }

        if (strpos($templateSubject, 'INVITEE_LAST_NAME') !== false) {
            $templateSubject = str_replace('INVITEE_LAST_NAME', $inviteUser->lastname, $templateSubject);
        }

        if (strpos($templateSubject, 'JOB_NAME') !== false) {
            $jobName = $folderUsers->first()->folder->job->ts_jobname ?? 'Unknown Job'; // Fallback if job name is null
            $templateSubject = str_replace('JOB_NAME', $jobName, $templateSubject);
        }


        $emailMessage = $this->generateEmail($authUser, $inviteUser, $templateSubject, $processedContent, $date);

        // Convert to RFC822 .eml
        $emlContent = MessageConverter::toEmail($emailMessage)->toString();

        // $filePath = public_path("$field.eml");
        // file_put_contents($filePath, $emlContent);

        $this->storeEmailRecord($authUser, $folderUsers->first()->folder->job, $inviteUser, $template, $date, $emlContent, $jobkey);
    }    

    /**
     * Re-render an existing invitation email's content with a new/updated folder list.
     *
     * @param Email   $pendingEmail     The existing email record
     * @param User    $inviteUser       The recipient user
     * @param array   $remainingFolders Folder names the user still has access to
     * @param Job     $jobObj           The job the email is linked to
     * @return string|null              The new .eml content, or null on failure
     */
    public function rebuildInvitationEmailContent($pendingEmail, $inviteUser, array $remainingFolders, $jobObj): ?string
    {
        $authUser = Auth::user();

        // Resolve the template used by the original email record
        $template = Template::find($pendingEmail->template_id);
        if (!$template) {
            return null;
        }

        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        if (!File::exists($templatePath)) {
            return null;
        }

        $templateContent = File::get($templatePath);

        $data = [
            'INVITEE_FIRST_NAME'   => $inviteUser->firstname ?? '',
            'INVITEE_LAST_NAME'    => $inviteUser->lastname  ?? '',
            'SENDER_FIRST_NAME'    => $authUser->firstname   ?? '',
            'SENDER_LAST_NAME'     => $authUser->lastname    ?? '',
            'JOB_NAME'             => $jobObj->ts_jobname    ?? '',
            'FOLDERS'              => $remainingFolders,
            'REVIEW_DUE'           => isset($jobObj->proof_due)
                                        ? Carbon::parse($jobObj->proof_due)->format('l j F, Y')
                                        : '',
            'APP_URL'              => Config::get('app.url'),
            'FRANCHISE_NAME'       => $authUser->getSchoolOrFranchiseDetail()->name     ?? '',
            'FRANCHISE_PHONE'      => $authUser->getSchoolOrFranchiseDetail()->phone    ?? '',
            'FRANCHISE_EMAIL'      => $authUser->getSchoolOrFranchiseDetail()->email    ?? '',
            'FRANCHISE_WEB_ADDRESS'=> Config::get('app.franchise_web_address', 'www.msp.com.au'),
            'FRANCHISE_ADDRESS1'   => $authUser->getSchoolOrFranchiseDetail()->address  ?? '',
            'FRANCHISE_SUBURB'     => $authUser->getSchoolOrFranchiseDetail()->suburb   ?? '',
            'FRANCHISE_STATE'      => $authUser->getSchoolOrFranchiseDetail()->state    ?? '',
            'FRANCHISE_POSTCODE'   => $authUser->getSchoolOrFranchiseDetail()->postcode ?? '',
        ];

        $beforeProcessedContent = $this->replaceTemplateVariables($templateContent, $data);

        // Apply any franchise-specific download instructions (mirrors saveInvitationContent logic)
        if ($authUser->getSchoolOrFranchiseDetail()->name === 'Sydney West') {
            $downloadInstructions = '
                <tr>
                    <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
                        <b>Download Instructions for</b><br>
                        <a href="https://www.msp.com.au/wp-content/uploads/blueprint/Online%20Proofing%20Guide%20Photo%20Coordinators.pdf" target="_blank">
                            <img src="https://www.msp.com.au/wp-content/uploads/2019/10/photoco_btn.png" width="225">
                        </a>
                    </td>
                </tr>
            ';
        } else {
            $downloadInstructions = '';
        }

        $processedContent = str_replace("{DOWNLOAD_INSTRUCTIONS}", $downloadInstructions, $beforeProcessedContent);

        // Rebuild the subject line
        $templateSubject = $template->template_subject;
        $templateSubject = str_replace('INVITEE_FIRST_NAME', $inviteUser->firstname ?? '', $templateSubject);
        $templateSubject = str_replace('INVITEE_LAST_NAME',  $inviteUser->lastname  ?? '', $templateSubject);
        $templateSubject = str_replace('JOB_NAME',           $jobObj->ts_jobname    ?? '', $templateSubject);

        $emailMessage = $this->generateEmail($authUser, $inviteUser, $templateSubject, $processedContent, now());

        return MessageConverter::toEmail($emailMessage)->toString();
    }

    protected function replaceTemplateVariables(string $content, array $data): string
    {
        // Replace simple variables
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $content = str_replace("{{$key}}", $value, $content);
            }
        }
    
        // Replace folder array dynamically
        if (isset($data['FOLDERS'])) {
            $folderHtml = '';
            foreach ($data['FOLDERS'] as $folder) {
                $folderHtml .= '<strong style="font-weight: 700;">- ' . $folder . '</strong><br/>';
            }
            $content = str_replace("{#FOLDERS}", $folderHtml, $content);
        }
        
        if (isset($data['ALLFOLDERS'])) {
            $folderHtml = '';
            foreach ($data['ALLFOLDERS'] as $folder) {
                $folderHtml .= '<strong style="font-weight: 700;">- ' . $folder . '</strong><br/>';
            }
            $content = str_replace("{#ALLFOLDERS}", $folderHtml, $content);
        }

        // Replace bulk folder status list (change_folder_status_template)
        if (isset($data['FOLDER_NAMES'])) {
            $folderHtml = '';
            foreach ($data['FOLDER_NAMES'] as $folder) {
                $folderHtml .= '<strong style="font-weight: 700;">- ' . $folder . '</strong><br/>';
            }
            $content = str_replace("{#FOLDER_NAMES}", $folderHtml, $content);
        }
    
        return $content;
    }
    
}
