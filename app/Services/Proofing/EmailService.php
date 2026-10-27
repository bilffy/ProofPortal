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
use App\Models\Order as OrderModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Address;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Part\TextPart;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Auth;
use DB;


class EmailService
{
    /**
     * Create a new class instance.
     */
    protected $statusService;
    protected $fileTransferService;

    public function __construct(StatusService $statusService, FileTransferService $fileTransferService)
    {
        // 
        $this->statusService = $statusService;
        $this->fileTransferService = $fileTransferService;
    }

    protected function generateEmail($authUser, $recipient, $subject, $content, $sentDate): SymfonyEmail
    {
        // Subject only: strip CR/LF and avoid aggressive header folding.
        // Keep HTML as base64 TextPart — ->html() uses quoted-printable and Outlook
        // was showing raw "=20" / "<=br/>" instead of rendering HTML.
        $subject = preg_replace("/[\r\n]+/", ' ', (string) $subject);
        $subject = trim(preg_replace('/\s{2,}/', ' ', $subject));

        $htmlPart = new TextPart($content, 'utf-8', 'html', 'base64');
        $dateTime = Carbon::parse($sentDate);
        $senderAddress = trim((string) config('mail.from.address')) ?: 'noreply@msp.com.au';
        $email = (new SymfonyEmail())
            // ->from(new Address($authUser->email, $authUser->name))
            ->from(new Address($senderAddress, 'MSP Portal - Do Not Reply'))
            ->to(new Address($recipient->email, $recipient->name))
            ->subject($subject)
            ->setBody($htmlPart)
            ->date($dateTime);

        $subjectHeader = $email->getHeaders()->get('Subject');
        if ($subjectHeader && method_exists($subjectHeader, 'setMaxLineLength')) {
            $subjectHeader->setMaxLineLength(998);
        }

        // DO NOT manually set Message-ID here. 
        // Symfony Mailer will generate a valid one automatically.
        
        return $email;
    }

    // protected function generateEmail($authUser, $recipient, $subject, $content, $sentDate): SymfonyEmail
    // {
    //     $htmlPart = new TextPart($content, 'utf-8', 'html', 'base64');
		
    //     $dateTime = Carbon::parse($sentDate);
    //     $email = (new SymfonyEmail())
    //         // ->from(new Address($authUser->email, $authUser->name))
    //         ->from(new Address('noreply@msp.com.au', 'MSP Portal - Do Not Reply'))
    //         ->to(new Address($recipient->email, $recipient->name))
    //         ->subject($subject)
    //         ->setBody($htmlPart)
    //         ->date($dateTime);

    //     // DO NOT manually set Message-ID here. 
    //     // Symfony Mailer will generate a valid one automatically.
        
    //     return $email;
    // }
    
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
     * Expire/soft-delete pending proof schedule emails for a job only
     * (proof_start, proof_warning, proof_due, proof_catchup).
     */
    public function expirePendingEmailsForJob(string $jobKey): int
    {
        $templateIds = Template::whereIn('template_name', [
            'proof_start',
            'proof_warning',
            'proof_due',
            'proof_catchup',
        ])->pluck('id');

        if ($templateIds->isEmpty()) {
            return 0;
        }

        return Email::where('ts_jobkey', $jobKey)
            ->whereIn('template_id', $templateIds)
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

    /**
     * Matrix JSON may store true, 1, or "true" depending on how it was saved.
     */
    private function isMatrixRoleEnabled($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Map notification-matrix keys to Spatie role names.
     */
    private function mapMatrixRoleKeysToRoleNames(array $roleKeys): array
    {
        return array_values(array_unique(array_map(
            fn ($role) => str_replace(
                ['franchise', 'photocoordinator', 'teacher'],
                ['Franchise', 'Photo Coordinator', 'Teacher'],
                $role
            ),
            $roleKeys
        )));
    }

    /**
     * Role names enabled in notifications_matrix.schools for a proof/schedule field.
     */
    private function getEnabledSchoolMatrixRoleNames(?array $notificationsMatrix, string $field): array
    {
        $roles = $notificationsMatrix['schools'][$field] ?? [];
        if (!is_array($roles) || empty($roles)) {
            return [];
        }

        $enabledKeys = array_keys(array_filter($roles, fn ($v) => $this->isMatrixRoleEnabled($v)));

        return $this->mapMatrixRoleKeysToRoleNames($enabledKeys);
    }

    /**
     * Whether the user has at least one of the given role names.
     */
    private function userHasAnyRoleNames(User $user, array $roleNames): bool
    {
        if (empty($roleNames)) {
            return false;
        }

        return $user->roles()->whereIn('name', $roleNames)->exists();
    }

    private function findJobByKey(string $jobKey): ?Job
    {
        return Job::where('ts_jobkey', $jobKey)->first();
    }

    /**
     * Create new pending proof_* emails for users who have not already received them.
     * Used when notifications_enabled is turned back on (does not restore expired rows).
     */
    public function refreshProofScheduleEmails(string $jobKey): void
    {
        $job = $this->findJobByKey($jobKey);
        if ($this->shouldBlockEmailGenerationForJob($job) || !$this->areJobNotificationsEnabled($job)) {
            return;
        }

        foreach (['proof_start', 'proof_warning', 'proof_due', 'proof_catchup'] as $field) {
            if (empty($job->$field)) {
                continue;
            }

            // Same creation path as setting proof dates, but skip users who already got EMAIL SENT
            $this->saveEmailContent($jobKey, $field, $job->$field, null, true);
        }
    }

    /**
     * When jobs.proof_due changes, rebuild pending proof_start / proof_warning / proof_catchup
     * email bodies so {REVIEW_DUE} matches the new due date. Does not create new rows or
     * change sentdate.
     *
     * @param  mixed  $proofDueDate  Optional override (e.g. the date just saved); falls back to job.proof_due
     */
    public function refreshReviewDueInPendingProofEmails(string $jobKey, $proofDueDate = null): int
    {
        $job = Job::with(['franchises', 'folders'])
            ->where('ts_jobkey', $jobKey)
            ->first();

        if (!$job) {
            return 0;
        }

        if ($proofDueDate !== null && $proofDueDate !== '') {
            $job->proof_due = $proofDueDate;
        }

        if (empty($job->proof_due)) {
            return 0;
        }

        $templates = Template::whereIn('template_name', ['proof_start', 'proof_warning', 'proof_catchup'])
            ->get()
            ->keyBy('id');

        if ($templates->isEmpty()) {
            return 0;
        }

        $pendingEmails = Email::where('ts_jobkey', $jobKey)
            ->whereIn('template_id', $templates->keys())
            ->where('status_id', $this->statusService->pending)
            ->get();

        if ($pendingEmails->isEmpty()) {
            return 0;
        }

        $authUser = Auth::user();
        if (!$authUser) {
            return 0;
        }

        $reviewDue = Carbon::parse($job->proof_due)->format('l j F, Y');
        $allJobFolders = $this->getVisibleProofingFolderNames($job->folders);
        $usersByEmail = $this->usersWithOrganizationRelationsQuery()
            ->whereIn(
            'email',
            $pendingEmails->pluck('email_to')->filter()->unique()->values()->all()
        )
            ->get(['id', 'email', 'firstname', 'lastname', 'name'])
            ->keyBy('email');

        $folderIdsByUserId = $this->loadFolderAssignmentsByUserIds(
            $usersByEmail->pluck('id'),
            $job->folders
        );

        $updated = 0;

        foreach ($pendingEmails as $pendingEmail) {
            $template = $templates->get($pendingEmail->template_id);
            $user = $usersByEmail->get($pendingEmail->email_to);
            if (!$template || !$user) {
                continue;
            }

            $templatePath = resource_path(
                "views/proofing/emails/{$template->template_location}{$template->template_format}"
            );
            if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
                continue;
            }

            $userFolders = $this->getAssignedFolderNamesForUser($job->folders, (int) $user->id, true, $folderIdsByUserId);
            $franchiseDetail = $this->resolveSchoolOrFranchiseDetail($user);

            $data = array_merge([
                'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                'FOLDERS' => $userFolders,
                'ALLFOLDERS' => $allJobFolders,
                'JOB_NAME' => $job->ts_jobname ?? '',
                'REVIEW_DUE' => $reviewDue,
                'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                'APP_URL' => Config::get('app.url'),
                'JOB_STATUS_NAME' => '',
            ], $this->franchiseTemplatePlaceholders($franchiseDetail));

            $processedContent = $this->replaceTemplateVariables(File::get($templatePath), $data);

            $templateSubject = $template->template_subject;
            if (strpos($templateSubject, 'JOB_NAME') !== false) {
                $templateSubject = str_replace('JOB_NAME', $job->ts_jobname ?? '', $templateSubject);
            }

            $sentDate = $pendingEmail->sentdate ?: $job->proof_due;
            $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $sentDate);
            $emlContent = MessageConverter::toEmail($emailMessage)->toString();

            $pendingEmail->update(['email_content' => $emlContent]);
            $updated++;
        }

        return $updated;
    }

    /**
     * True when this recipient already received (EMAIL SENT) this proof schedule template for the job.
     */
    private function hasReceivedProofScheduleEmail(int $templateId, string $jobKey, string $emailTo): bool
    {
        if (!$this->statusService->emailSent) {
            return false;
        }

        return Email::where('template_id', $templateId)
            ->where('ts_jobkey', $jobKey)
            ->where('email_to', $emailTo)
            ->where('status_id', $this->statusService->emailSent)
            ->exists();
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
     * For proof schedule templates, Completed folders are also excluded
     * (they reappear after unlock).
     */
    private function getAssignedFolderNamesForUser(
        $folders,
        int $userId,
        bool $excludeCompleted = false,
        $folderIdsByUserId = null
    ): array {
        $completedStatusId = $this->statusService->completed;
        $assignedFolderIds = $folderIdsByUserId?->get($userId);

        return $folders
            ->filter(function ($folder) use ($userId, $excludeCompleted, $completedStatusId, $assignedFolderIds, $folderIdsByUserId) {
                if ((int) ($folder->is_visible_for_proofing ?? 0) !== 1) {
                    return false;
                }

                if ($excludeCompleted && (int) $folder->status_id === (int) $completedStatusId) {
                    return false;
                }

                if ($folderIdsByUserId !== null) {
                    return $assignedFolderIds !== null && isset($assignedFolderIds[$folder->ts_folder_id]);
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

    /**
     * Batch-load folder assignments for many users in one query.
     * Returns user_id => [ts_folder_id => true, ...] for O(1) lookups.
     */
    private function loadFolderAssignmentsByUserIds($userIds, $folders)
    {
        $userIds = collect($userIds)->filter()->unique()->values();
        $folderIds = collect($folders)->pluck('ts_folder_id')->filter()->unique()->values();

        if ($userIds->isEmpty() || $folderIds->isEmpty()) {
            return collect();
        }

        return FolderUser::whereIn('user_id', $userIds)
            ->whereIn('ts_folder_id', $folderIds)
            ->get(['user_id', 'ts_folder_id'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('ts_folder_id')->flip());
    }

    private function usersWithOrganizationRelationsQuery()
    {
        return User::query()->with(['schools.franchises', 'franchises', 'roles']);
    }

    private function resolveSchoolOrFranchiseDetail(User $user)
    {
        if ($user->isAdmin()) {
            return null;
        }

        if ($user->isFranchiseLevel()) {
            return $user->franchises->first();
        }

        return $user->schools->first()?->franchises->first();
    }

    private function franchiseTemplatePlaceholders($detail): array
    {
        if (!$detail || !is_object($detail)) {
            return [
                'FRANCHISE_NAME' => '',
                'FRANCHISE_PHONE' => '',
                'FRANCHISE_EMAIL' => '',
                'FRANCHISE_ADDRESS1' => '',
                'FRANCHISE_SUBURB' => '',
                'FRANCHISE_STATE' => '',
                'FRANCHISE_POSTCODE' => '',
            ];
        }

        return [
            'FRANCHISE_NAME' => $detail->name ?? '',
            'FRANCHISE_PHONE' => $detail->phone ?? '',
            'FRANCHISE_EMAIL' => $detail->email ?? '',
            'FRANCHISE_ADDRESS1' => $detail->address ?? '',
            'FRANCHISE_SUBURB' => $detail->suburb ?? '',
            'FRANCHISE_STATE' => $detail->state ?? '',
            'FRANCHISE_POSTCODE' => $detail->postcode ?? '',
        ];
    }

    /**
     * Pending proof schedule rows for a user+template+job (any sentdate).
     * Refresh must update existing pending content when folder status changes.
     */
    private function pendingProofScheduleEmailQuery(int $templateId, string $jobKey, string $emailTo)
    {
        return Email::where('template_id', $templateId)
            ->where('ts_jobkey', $jobKey)
            ->where('status_id', $this->statusService->pending)
            ->where('email_to', $emailTo);
    }

    private function loadPendingProofScheduleEmailsByRecipients(int $templateId, string $jobKey, array $emails)
    {
        $emails = array_values(array_filter($emails));
        if ($emails === []) {
            return collect();
        }

        return Email::where('template_id', $templateId)
            ->where('ts_jobkey', $jobKey)
            ->where('status_id', $this->statusService->pending)
            ->whereIn('email_to', $emails)
            ->get()
            ->keyBy('email_to');
    }

    /**
     * @return array<string, true>
     */
    private function loadReceivedProofScheduleEmailRecipients(int $templateId, string $jobKey, array $emails): array
    {
        $emails = array_values(array_filter($emails));
        if ($emails === [] || !$this->statusService->emailSent) {
            return [];
        }

        return Email::where('template_id', $templateId)
            ->where('ts_jobkey', $jobKey)
            ->where('status_id', $this->statusService->emailSent)
            ->whereIn('email_to', $emails)
            ->pluck('email_to')
            ->flip()
            ->all();
    }

    private function expirePendingProofScheduleEmails(int $templateId, string $jobKey, array $emails): void
    {
        $emails = array_values(array_filter($emails));
        if ($emails === []) {
            return;
        }

        Email::where('template_id', $templateId)
            ->where('ts_jobkey', $jobKey)
            ->where('status_id', $this->statusService->pending)
            ->whereIn('email_to', $emails)
            ->update([
                'status_id' => $this->statusService->expired,
                'deleted_at' => now(),
            ]);
    }

    private function loadPendingScheduledEmailsByRecipients(
        int $templateId,
        string $jobKey,
        array $emails,
        $sentDateCarbon,
        string $emailFrom
    ) {
        $emails = array_values(array_filter($emails));
        if ($emails === []) {
            return collect();
        }

        return Email::where('template_id', $templateId)
            ->where('ts_jobkey', $jobKey)
            ->where('status_id', $this->statusService->pending)
            ->whereDate('sentdate', $sentDateCarbon)
            ->where('email_from', $emailFrom)
            ->whereIn('email_to', $emails)
            ->get()
            ->keyBy('email_to');
    }

    private function loadPendingEmailsByRecipientsForAuthUser(
        int $templateId,
        string $jobKey,
        array $emails,
        $authUser,
        $selectedJob
    ) {
        $emails = array_values(array_filter($emails));
        if ($emails === []) {
            return collect();
        }

        return Email::where('generated_from_user_id', $authUser->id)
            ->where('alphacode', $selectedJob->franchises->alphacode ?? null)
            ->where('ts_jobkey', $jobKey)
            ->where('ts_schoolkey', $selectedJob->ts_schoolkey)
            ->where('email_from', $authUser->email)
            ->where('template_id', $templateId)
            ->where('status_id', $this->statusService->pending)
            ->whereIn('email_to', $emails)
            ->get()
            ->keyBy('email_to');
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
    
        // Always reload folders so status_id / is_visible_for_proofing are current
        $selectedJob->load(['franchises', 'folders']);

        $allJobFolders = $this->getVisibleProofingFolderNames($selectedJob->folders);
        $notificationsMatrix = json_decode($selectedJob->notifications_matrix, true) ?? [];
    
        /**
         * --------------------------------------------
         * Roles with email enabled
         * --------------------------------------------
         */
        $roleNames = $this->getEnabledSchoolMatrixRoleNames($notificationsMatrix, $field);
    
        if (empty($roleNames)) {
            Email::where('template_id', $template->id)
            ->where('ts_jobkey', $decryptedJobKey)
            ->where('status_id', $this->statusService->pending)
            ->update([
                'status_id' => $this->statusService->expired, // Sets status to Expired
                'deleted_at' => now()
            ]);

            return;
        }
    
        $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->toArray();
    
        if (empty($roleIds)) {
            return;
        }
    
        /**
         * --------------------------------------------
         * Fetch users
         * --------------------------------------------
         */
        $users = $this->usersWithOrganizationRelationsQuery()
            ->whereHas('roles', fn ($q) => $q->whereIn('id', $roleIds))
            ->whereHas('jobs', fn ($q) => $q->where('jobs.ts_job_id', $selectedJob->ts_job_id))
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();
    
        /**
         * --------------------------------------------
         * DELETE stale emails (ONCE) — by recipient, not by who generated
         * --------------------------------------------
         */
        $validEmails = $users->pluck('email')->filter()->values()->all();

        Email::where('template_id', $template->id)
            ->where('ts_jobkey', $decryptedJobKey)
            ->where('status_id', $this->statusService->pending)
            ->when(!empty($validEmails), fn ($q) => $q->whereNotIn('email_to', $validEmails))
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

        $folderIdsByUserId = $this->loadFolderAssignmentsByUserIds(
            $users->pluck('id'),
            $selectedJob->folders
        );

        $userEmails = $users->pluck('email')->filter()->values()->all();
        $pendingByEmail = $excludeCompleted
            ? $this->loadPendingProofScheduleEmailsByRecipients($template->id, $decryptedJobKey, $userEmails)
            : $this->loadPendingScheduledEmailsByRecipients($template->id, $decryptedJobKey, $userEmails, $sentDateCarbon, $authUser->email);
        $receivedByEmail = $excludeCompleted
            ? $this->loadReceivedProofScheduleEmailRecipients($template->id, $decryptedJobKey, $userEmails)
            : [];
        $emailsToExpire = [];

        foreach ($users as $user) {
            $userFolders = $this->getAssignedFolderNamesForUser(
                $selectedJob->folders,
                $user->id,
                $excludeCompleted,
                $folderIdsByUserId
            );

            if ($excludeCompleted && empty($userFolders)) {
                $emailsToExpire[] = $user->email;
                continue;
            }

            if ($excludeCompleted && isset($receivedByEmail[$user->email])) {
                $emailsToExpire[] = $user->email;
                continue;
            }

            $emailRecord = $pendingByEmail->get($user->email);

            if ($emailRecord && !$excludeCompleted) {
                continue;
            }

            $franchiseDetail = $this->resolveSchoolOrFranchiseDetail($user);

            $data = array_merge([
                'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                'FOLDERS' => $userFolders,
                'ALLFOLDERS' => $allJobFolders,
                'JOB_NAME' => $selectedJob->ts_jobname ?? '',
                'REVIEW_DUE' => $selectedJob->proof_due
                    ? Carbon::parse($selectedJob->proof_due)->format('l j F, Y')
                    : '',
                'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                'APP_URL' => Config::get('app.url'),
                'JOB_STATUS_NAME' => $statusModel->status_external_name ?? '',
            ], $this->franchiseTemplatePlaceholders($franchiseDetail));

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

        $this->expirePendingProofScheduleEmails($template->id, $decryptedJobKey, $emailsToExpire);
    }
    

    public function saveEmailContent($tsJobKey, $field, $date, $status = null, bool $skipAlreadySent = false)
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
    
        $roleNames = $this->getEnabledSchoolMatrixRoleNames($notificationsMatrix, $field);
        // \Log::info('saveEmailContent roles check', ['field' => $field, 'roles' => $roleNames, 'notifications_enabled' => $selectedJob->notifications_enabled]);
   
        // Prepare template content
        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        $templateContent = File::exists($templatePath) ? File::get($templatePath) : '';
        $statusModel = $status ? Status::find($status) : null;
    
        if (!empty($roleNames) && $this->areJobNotificationsEnabled($selectedJob)) {

            $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
    
            $userIds = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
                ->whereHas('jobs', fn($q) => $q->where('jobs.ts_job_id', $selectedJob->ts_job_id))
                ->pluck('id');
    
            $users = $this->usersWithOrganizationRelationsQuery()
                ->whereIn('id', $userIds)
                ->select('id','name','email','firstname','lastname')
                ->get();
            // \Log::info('saveEmailContent users found', ['count' => $users->count(), 'job' => $tsJobKey]);
    
            $excludeCompleted = $this->isProofScheduleTemplate($field);

            // One pending row per recipient (template+job+email_to) — expire recipients no longer in matrix roles
            $validEmails = $users->pluck('email')->filter()->values()->all();
            Email::where('template_id', $template->id)
                ->where('ts_jobkey', $tsJobKey)
                ->where('status_id', $this->statusService->pending)
                ->when(!empty($validEmails), fn ($q) => $q->whereNotIn('email_to', $validEmails))
                ->update([
                    'status_id' => $this->statusService->expired,
                    'deleted_at' => now(),
                ]);

            $folderIdsByUserId = $this->loadFolderAssignmentsByUserIds(
                $users->pluck('id'),
                $selectedJob->folders
            );

            $userEmails = $users->pluck('email')->filter()->values()->all();
            $pendingProofByEmail = $this->loadPendingProofScheduleEmailsByRecipients($template->id, $tsJobKey, $userEmails);
            $pendingAuthByEmail = $this->loadPendingEmailsByRecipientsForAuthUser($template->id, $tsJobKey, $userEmails, $authUser, $selectedJob);
            $receivedByEmail = $this->loadReceivedProofScheduleEmailRecipients($template->id, $tsJobKey, $userEmails);
            $emailsToExpire = [];

            foreach ($users as $user) {
                $userFolders = $this->getAssignedFolderNamesForUser(
                    $selectedJob->folders,
                    $user->id,
                    $excludeCompleted,
                    $folderIdsByUserId
                );

                if ($excludeCompleted && empty($userFolders)) {
                    $emailsToExpire[] = $user->email;
                    continue;
                }

                if ($skipAlreadySent
                    && $excludeCompleted
                    && isset($receivedByEmail[$user->email])
                ) {
                    continue;
                }

                $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
                if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
                        throw new \Exception("Template file not found at {$templatePath}");
                }
                $templateContent = File::get($templatePath);

                $franchiseDetail = $this->resolveSchoolOrFranchiseDetail($user);
    
                $data = array_merge([
                    'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                    'FOLDERS' => $userFolders,
                    'ALLFOLDERS' => $allJobFolders,
                    'JOB_NAME' => $selectedJob->ts_jobname ?? '',
                    'REVIEW_DUE' => isset($selectedJob->proof_due) ? Carbon::parse($selectedJob->proof_due)->format('l j F, Y') : '',
                    'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                    'APP_URL' => Config::get('app.url'),
                    'JOB_STATUS_NAME' => $statusModel->status_external_name ?? '',
                ], $this->franchiseTemplatePlaceholders($franchiseDetail));
    
                $processedContent = $this->replaceTemplateVariables($templateContent, $data);
                $templateSubject = $template->template_subject;
                if (strpos($template->template_subject, 'JOB_NAME') !== false) {
                    $templateSubject = str_replace('JOB_NAME', $selectedJob->ts_jobname, $template->template_subject);
                }
                $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $date);
    
                $emlContent = MessageConverter::toEmail($emailMessage)->toString();
    
                $emailRecord = $excludeCompleted
                    ? $pendingProofByEmail->get($user->email)
                    : $pendingAuthByEmail->get($user->email);
    
                if ($emailRecord) {
                    $emailRecord->update([
                        'sentdate' => $date,
                        'email_content' => $emlContent
                    ]);
                } else {
                    $this->storeEmailRecord($authUser, $selectedJob, $user, $template, $date, $emlContent, $tsJobKey);
                }
            }

            $this->expirePendingProofScheduleEmails($template->id, $tsJobKey, $emailsToExpire);
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
        $roleNames = array_keys(array_filter($rolesWithFieldTrue, fn ($value) => $this->isMatrixRoleEnabled($value)));
    
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
        $users = $this->usersWithOrganizationRelationsQuery()
            ->whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
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
            $franchiseDetail = $this->resolveSchoolOrFranchiseDetail($user);
            $userData = array_merge(
                [
                    'INVITEE_FIRST_NAME' => $user->firstname ?? '',
                    'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                ],
                $this->franchiseTemplatePlaceholders($franchiseDetail)
            );

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

        $users = $this->usersWithOrganizationRelationsQuery()
            ->whereIn('id', $userIds)
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();

        $folderIdsByUserId = $this->loadFolderAssignmentsByUserIds(
            $users->pluck('id'),
            $selectedJob->folders
        );

        $notificationsMatrix = json_decode($selectedJob->notifications_matrix, true) ?? [];

        foreach ($templateDateMap as $templateName => $sentDate) {
            // Skip if the job doesn't have this date configured
            if (empty($sentDate)) {
                continue;
            }

            // Only queue for invitees whose role is selected in the notification matrix
            $enabledRoleNames = $this->getEnabledSchoolMatrixRoleNames($notificationsMatrix, $templateName);
            if (empty($enabledRoleNames)) {
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

            $userEmails = $users->pluck('email')->filter()->values()->all();
            $pendingByEmail = $this->loadPendingProofScheduleEmailsByRecipients($template->id, $jobKey, $userEmails);
            $receivedByEmail = $this->loadReceivedProofScheduleEmailRecipients($template->id, $jobKey, $userEmails);
            $emailsToExpire = [];

            foreach ($users as $user) {
                if (!$this->userHasAnyRoleNames($user, $enabledRoleNames)) {
                    continue;
                }

                $userFolders = $this->getAssignedFolderNamesForUser(
                    $selectedJob->folders,
                    $user->id,
                    true,
                    $folderIdsByUserId
                );

                if (empty($userFolders)) {
                    $emailsToExpire[] = $user->email;
                    continue;
                }

                if (isset($receivedByEmail[$user->email])) {
                    $emailsToExpire[] = $user->email;
                    continue;
                }

                $emailRecord = $pendingByEmail->get($user->email);

                $franchiseDetail = $this->resolveSchoolOrFranchiseDetail($user);

                $data = array_merge([
                    'INVITEE_FIRST_NAME'    => $user->firstname ?? '',
                    'FOLDERS'               => $userFolders,
                    'ALLFOLDERS'            => $allJobFolders,
                    'JOB_NAME'              => $selectedJob->ts_jobname ?? '',
                    'REVIEW_DUE'            => $selectedJob->proof_due
                                                ? Carbon::parse($selectedJob->proof_due)->format('l j F, Y')
                                                : '',
                    'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
                    'APP_URL'               => Config::get('app.url'),
                    'JOB_STATUS_NAME'       => $statusModel->status_external_name ?? '',
                ], $this->franchiseTemplatePlaceholders($franchiseDetail));

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

            $this->expirePendingProofScheduleEmails($template->id, $jobKey, $emailsToExpire);
        }
    }

    /**
     * Visible-for-proofing folder names assigned to a user on a job.
     */
    public function getInvitationFolderNamesForUser(int $userId, string $jobKey): array
    {
        return $this->loadInvitationFolderNamesByUserIds([$userId], [$jobKey])
            ->get("{$userId}|{$jobKey}", []);
    }

    /**
     * Batch-load invitation folder names for many users/jobs in one query.
     *
     * @return \Illuminate\Support\Collection<string, array<int, string>>  "userId|jobKey" => folder names
     */
    private function loadInvitationFolderNamesByUserIds($userIds, $jobKeys)
    {
        $userIds = collect($userIds)->filter()->unique()->values();
        $jobKeys = collect($jobKeys)->filter()->unique()->values();

        if ($userIds->isEmpty() || $jobKeys->isEmpty()) {
            return collect();
        }

        return FolderUser::query()
            ->whereIn('folder_users.user_id', $userIds)
            ->join('folders', 'folders.ts_folder_id', '=', 'folder_users.ts_folder_id')
            ->join('jobs', 'jobs.ts_job_id', '=', 'folders.ts_job_id')
            ->where('folders.is_visible_for_proofing', 1)
            ->whereIn('jobs.ts_jobkey', $jobKeys)
            ->select('folder_users.user_id', 'jobs.ts_jobkey', 'folders.ts_foldername')
            ->get()
            ->groupBy(fn ($row) => $row->user_id . '|' . $row->ts_jobkey)
            ->map(fn ($rows) => $rows->pluck('ts_foldername')->filter()->unique()->values()->toArray());
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

        $invitationFoldersByUserJob = $this->loadInvitationFolderNamesByUserIds(
            $userIdsWithAccess,
            $jobKeys
        );

        foreach ($pendingEmails as $pendingEmail) {
            $user = $usersByEmail->get($pendingEmail->email_to);
            $job = $jobsByKey->get($pendingEmail->ts_jobkey);
            if (!$user || !$job) {
                continue;
            }

            if ($this->shouldBlockEmailGenerationForJob($job)) {
                continue;
            }

            $folderNames = $invitationFoldersByUserJob->get("{$user->id}|{$job->ts_jobkey}", []);
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
        $authUser = $this->usersWithOrganizationRelationsQuery()->find(Auth::id());
        if (!$authUser) {
            return;
        }
        if($role == 'photocoordinator') {
            $field = 'photocordinator_invitation';
        } elseif($role == 'teacher') {
            $field = 'teacher_invitation';
        }

        $job = $this->findJobByKey($jobkey);
        if (!$job || $this->shouldBlockEmailGenerationForJob($job, $field ?? null)) {
            return;
        }

        $template = Template::where('template_name', $field)->first();
        $inviteUser = User::find($user);
        if (!$template || !$inviteUser) {
            return;
        }

        $selectedFolders = $this->getInvitationFolderNamesForUser((int) $user, $jobkey);
        $authFranchiseDetail = $this->resolveSchoolOrFranchiseDetail($authUser);

        $isSetupPending = false;
        $setupUrl = route('login');

        // if ($inviteUser->send_invitation_with_proofing === 1 && !$inviteUser->is_setup_complete && $inviteUser->status === User::STATUS_INVITED) {
        //     $isSetupPending = true;
        //     $token = \Illuminate\Support\Facades\Password::broker('invites')->createToken($inviteUser);
        //     $setupUrl = route('account.setup.create', [
        //         'token' => $token,
        //         'email' => $inviteUser->getHashedIdAttribute(),
        //     ], true);

        //     $inviteUser->status = User::STATUS_INVITED;
        //     $invitedStatus = Status::where('status_external_name', 'invited')->first();
        //     if ($invitedStatus) {
        //         $inviteUser->active_status_id = $invitedStatus->id;
        //     }
        //     $inviteUser->send_invitation_with_proofing = false;
        //     $inviteUser->save();
        // }

        if ($inviteUser->send_invitation_with_proofing === true && !$inviteUser->is_setup_complete && $inviteUser->status === User::STATUS_INVITED) {
            $isSetupPending = true;

            // Generate token and setup URL
            $token = \Illuminate\Support\Facades\Password::broker('invites')->createToken($inviteUser);
            $setupUrl = route('account.setup.create', [
                'token' => $token,
                'email' => $inviteUser->getHashedIdAttribute(),
            ], true);

            // Update properties and save in a single operation
            $invitedStatus = Status::where('status_external_name', 'invited')->first();

            $inviteUser->update([
                'active_status_id' => $invitedStatus?->id ?? $inviteUser->active_status_id,
                'send_invitation_with_proofing' => false,
            ]);
        }

        $userSchoolName = $inviteUser->isSchoolLevel() ? ($inviteUser->getSchool()?->name ?? '') : '';
        $userOrgName = $inviteUser->getSchoolOrFranchise(true);
        $senderOrgName = $authUser->getSchoolOrFranchise(true);
        $senderName = trim(($authUser->firstname ?? '') . ' ' . ($authUser->lastname ?? ''));
        if (empty($senderName)) {
            $senderName = $authUser->name ?? 'MSP Portal';
        }
        $userRoleName = $inviteUser->getRole() ?? ucfirst($role);
        $jobName = $job->ts_jobname ?? '';
        $appUrl = Config::get('app.url');

        if ($isSetupPending) {
            $schoolPart = $userSchoolName !== '' ? " for <strong>{$userSchoolName}</strong>" : '';
            $introText = "<strong style=\"font-weight: 700;\">{$authUser->firstname} {$authUser->lastname}</strong> from the <strong style=\"font-weight: 700;\">{$senderOrgName}</strong> has invited you to access the <strong style=\"font-weight: 700;\">MSP Portal</strong> as a <strong style=\"font-weight: 700;\">{$userRoleName}</strong>{$schoolPart}. You have been assigned to use the MSP Portal Proofing system to manage the distribution and collection of proofs for <strong style=\"font-weight: 700;\">{$jobName}</strong>.<br>";

            $actionSection = '
                                    <p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 14px; color: #00b3e0; line-height: 1.4;">
                                        To get started, click the button to set up your account:
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
                                    <a href="' . $setupUrl . '"><img
                                            src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_button_setup.png"
                                            width="225"></a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 20px 40px 0px 40px">
                                    <p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        During the setup process, you\'ll confirm your name, create a password and then be directed to the login page.
                                        <br/><br/>
                                        This invitation expires after 14 days.
                                    </p>
                                </td>
                            </tr>';
        } else {
            $introText = "<strong style=\"font-weight: 700;\">{$authUser->firstname} {$authUser->lastname}</strong> from <strong style=\"font-weight: 700;\">{$senderOrgName}</strong> has assigned you to use the MSP Portal Proofing system to manage the distribution and collection of proofs for <strong style=\"font-weight: 700;\">{$jobName}</strong>.<br>";

            $actionSection = '
                                    <p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        To review these proofs, please visit
                                        <strong style="font-weight: 700;"><a href="' . $appUrl . '" style="color: #00b3e0; text-decoration: underline;">' . $appUrl . '</a></strong>.
                                    </p>
                                </td>
                            </tr>';
        }

        // Prefer the job already resolved by key. Do not chain folderUsers->first()->folder->job:
        // first() / folder can be null (franchise scope, deleted folder, or no assignments yet).
        $data = array_merge([
                'INTRO_TEXT' => $introText,
                'ACTION_SECTION' => $actionSection,
                'INVITEE_FIRST_NAME' => $inviteUser->firstname ?? '',
                'INVITEE_LAST_NAME' => $inviteUser->lastname ?? '',
                'SENDER_FIRST_NAME' => $authUser->firstname ?? '',
                'SENDER_LAST_NAME' => $authUser->lastname ?? '',
                'SENDER_NAME' => $senderName,
                'SENDER_ORG_NAME' => $senderOrgName,
                'USER_ROLE' => $userRoleName,
                'USER_SCHOOL_NAME' => $userSchoolName,
                'USER_ORG_NAME' => $userOrgName,
                'JOB_NAME' => $jobName,
                'FOLDERS' => $selectedFolders,
                'REVIEW_DUE' => isset($job->proof_due) ? Carbon::parse($job->proof_due)->format('l, j F, Y') : '',
                'APP_URL' => $appUrl,
                'SETUP_URL' => $setupUrl,
                'FRANCHISE_WEB_ADDRESS' => Config::get('app.franchise_web_address', 'www.msp.com.au'),
        ], $this->franchiseTemplatePlaceholders($authFranchiseDetail));

        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
            throw new \Exception("Template file not found at {$templatePath}");
        }
        $templateContent = File::get($templatePath);

        $beforeProcessedContent = $this->replaceTemplateVariables($templateContent, $data);
        $downloadInstructions = '';

        // if ($authUser->getSchoolOrFranchiseDetail()->name === 'Sydney West') {
        //     $downloadInstructions = '
        //         <tr>
        //             <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
        //                 <b>Download Instructions for</b><br>
        //                 <a href="https://www.msp.com.au/wp-content/uploads/blueprint/Online%20Proofing%20Guide%20Photo%20Coordinators.pdf" target="_blank">
        //                     <img src="https://www.msp.com.au/wp-content/uploads/2019/10/photoco_btn.png" width="225">
        //                 </a>
        //             </td>
        //         </tr>
        //     ';
        // }
        
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
            $templateSubject = str_replace('JOB_NAME', $job->ts_jobname ?? 'Unknown Job', $templateSubject);
        }


        $emailMessage = $this->generateEmail($authUser, $inviteUser, $templateSubject, $processedContent, $date);

        // Convert to RFC822 .eml
        $emlContent = MessageConverter::toEmail($emailMessage)->toString();

        // $filePath = public_path("$field.eml");
        // file_put_contents($filePath, $emlContent);

        $this->storeEmailRecord($authUser, $job, $inviteUser, $template, $date, $emlContent, $jobkey);
    }    

    public function rebuildInvitationEmailContent($pendingEmail, $inviteUser, array $remainingFolders, $jobObj): ?string
    {
        $authUser = $this->usersWithOrganizationRelationsQuery()->find(Auth::id());
        if (!$authUser) {
            return null;
        }

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
        $authFranchiseDetail = $this->resolveSchoolOrFranchiseDetail($authUser);

        $isSetupPending = false;
        $setupUrl = route('login');
         if ($inviteUser->send_invitation_with_proofing === true && !$inviteUser->is_setup_complete && $inviteUser->status === User::STATUS_INVITED) {
            $isSetupPending = true;
            $token = \Illuminate\Support\Facades\Password::broker('invites')->createToken($inviteUser);
            $setupUrl = route('account.setup.create', [
                'token' => $token,
                'email' => $inviteUser->getHashedIdAttribute(),
            ], true);
        }

        $userSchoolName = $inviteUser->isSchoolLevel() ? ($inviteUser->getSchool()?->name ?? '') : '';
        $userOrgName = $inviteUser->getSchoolOrFranchise(true);
        $senderOrgName = $authUser->getSchoolOrFranchise(true);
        $senderName = trim(($authUser->firstname ?? '') . ' ' . ($authUser->lastname ?? ''));
        if (empty($senderName)) {
            $senderName = $authUser->name ?? 'MSP Portal';
        }
        $userRoleName = $inviteUser->getRole() ?? '';
        $jobName = $jobObj->ts_jobname ?? '';
        $appUrl = Config::get('app.url');

        if ($isSetupPending) {
            $schoolPart = $userSchoolName !== '' ? " for <strong>{$userSchoolName}</strong>" : '';
            $introText = "<strong>{$authUser->firstname} {$authUser->lastname}</strong> from the <strong>{$senderOrgName}</strong> has invited you to access the <strong>MSP Portal</strong> as a <strong>{$userRoleName}</strong>{$schoolPart}. You have been assigned to use the MSP Portal Proofing system to manage the distribution and collection of proofs for <strong style=\"font-weight: 700;\">{$jobName}</strong>.";

            $actionSection = '
                                    <p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 14px; color: #00b3e0; line-height: 1.4;">
                                        To get started, click the button to set up your account:
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
                                    <a href="' . $setupUrl . '"><img
                                            src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_button_setup.png"
                                            width="225"></a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 20px 40px 0px 40px">
                                    <p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        During the setup process, you\'ll confirm your name, create a password and then be directed to the login page.
                                        <br/><br/>
                                        This invitation expires after 14 days.
                                    </p>
                                </td>
                            </tr>';
        } else {
            $introText = "<strong>{$authUser->firstname} {$authUser->lastname}</strong> from <strong>{$senderOrgName}</strong> has assigned you to use the MSP Portal Proofing system to manage the distribution and collection of proofs for <strong style=\"font-weight: 700;\">{$jobName}</strong>.";

            $actionSection = '
                                    <p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        To review these proofs, please visit
                                        <strong style="font-weight: 700;"><a href="' . $appUrl . '" style="color: #00b3e0; text-decoration: underline;">' . $appUrl . '</a></strong>.
                                    </p>
                                </td>
                            </tr>';
        }

        $data = array_merge([
            'INTRO_TEXT'           => $introText,
            'ACTION_SECTION'       => $actionSection,
            'INVITEE_FIRST_NAME'   => $inviteUser->firstname ?? '',
            'INVITEE_LAST_NAME'    => $inviteUser->lastname  ?? '',
            'SENDER_FIRST_NAME'    => $authUser->firstname   ?? '',
            'SENDER_LAST_NAME'     => $authUser->lastname    ?? '',
            'SENDER_NAME'          => $senderName,
            'SENDER_ORG_NAME'      => $senderOrgName,
            'USER_ROLE'            => $userRoleName,
            'USER_SCHOOL_NAME'     => $userSchoolName,
            'USER_ORG_NAME'        => $userOrgName,
            'JOB_NAME'             => $jobName,
            'FOLDERS'              => $remainingFolders,
            'REVIEW_DUE'           => isset($jobObj->proof_due)
                                        ? Carbon::parse($jobObj->proof_due)->format('l, j F, Y')
                                        : '',
            'APP_URL'              => $appUrl,
            'SETUP_URL'            => $setupUrl,
            'FRANCHISE_WEB_ADDRESS'=> Config::get('app.franchise_web_address', 'www.msp.com.au'),
        ], $this->franchiseTemplatePlaceholders($authFranchiseDetail));

        $beforeProcessedContent = $this->replaceTemplateVariables($templateContent, $data);
        $downloadInstructions = '';

        // // Apply any franchise-specific download instructions (mirrors saveInvitationContent logic)
        // if ($authUser->getSchoolOrFranchiseDetail()->name === 'Sydney West') {
        //     $downloadInstructions = '
        //         <tr>
        //             <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
        //                 <b>Download Instructions for</b><br>
        //                 <a href="https://www.msp.com.au/wp-content/uploads/blueprint/Online%20Proofing%20Guide%20Photo%20Coordinators.pdf" target="_blank">
        //                     <img src="https://www.msp.com.au/wp-content/uploads/2019/10/photoco_btn.png" width="225">
        //                 </a>
        //             </td>
        //         </tr>
        //     ';
        // } 

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

    /**
     * Logs the order notification as pending Email rows: one to the print
     * team (PRINT_REQUEST_EMAIL) and one copy to the person who submitted
     * the order, so they can check what they sent is right.
     *
     * Each recipient gets its own greeting/intro and its own single-use
     * download link (the file-transfer links allow one download each, so
     * sharing one link would let the first person to click kill it for the
     * other). Both rows are written in one transaction.
     *
     * @return Email|null the print team's row, or null if nothing was logged
     */
    public function logOrderEmail(OrderModel $order, User $authUser): ?Email
    {
        $printEmail = trim((string) config('services.print_request_email'));

        if ($printEmail === '') {
            Log::warning('PRINT_REQUEST_EMAIL is not configured - skipping order notification email.', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        $template = Template::where('template_name', 'Ordering')->first();

        if (!$template) {
            Log::warning('No "Ordering" template row found - skipping order notification email.', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        $context = $this->orderEmailContext($order, $authUser, $template);
        $school = $context['school'];
        $franchise = $context['franchise'];

        // Zip + upload once, one link per recipient (print team, sender).
        $links = $order->artworkFiles->isEmpty()
            ? []
            : $this->fileTransferService->uploadOrderArtworkLinks($order, 2);

        $sentDate = now();
        $senderAddress = trim((string) config('mail.from.address')) ?: 'noreply@msp.com.au';

        $printEml = $this->buildOrderEml($context, $order, $authUser, Email::ORDER_RECIPIENT_PRINT, $printEmail, $links[0] ?? null, $sentDate);

        // Copy for the submitter so they can check the order is right.
        $copyEml = filter_var($authUser->email, FILTER_VALIDATE_EMAIL)
            ? $this->buildOrderEml($context, $order, $authUser, Email::ORDER_RECIPIENT_REQUESTOR, $authUser->email, $links[1] ?? null, $sentDate)
            : null;

        $rowDefaults = [
            'generated_from_user_id' => $authUser->id,
            'alphacode' => $franchise->alphacode ?? null,
            'school_id' => $school?->id ?: null,
            'ts_schoolkey' => $school?->schoolkey ?: null,
            'sentdate' => $sentDate,
            'template_id' => $template->id,
            'status_id' => $this->statusService->pending,
        ];

        return DB::transaction(function () use ($order, $rowDefaults, $authUser, $printEmail, $printEml, $copyEml, $senderAddress) {
            // Print team
            $printRecord = Email::create($rowDefaults + [
                'order_id' => $order->id,
                'order_recipient' => Email::ORDER_RECIPIENT_PRINT,
                'email_from' => $authUser->email,
                'email_to' => $printEmail,
                'email_content' => $printEml,
            ]);

            // Submitter's copy
            if ($copyEml !== null) {
                Email::create($rowDefaults + [
                    'order_id' => $order->id,
                    'order_recipient' => Email::ORDER_RECIPIENT_REQUESTOR,
                    'email_from' => $senderAddress,
                    'email_to' => $authUser->email,
                    'email_content' => $copyEml,
                ]);
            }

            return $printRecord;
        });
    }

    /**
     * Everything the order email templates need that does not depend on the
     * recipient: the template text, the sender's school/franchise and the
     * placeholder values shared by both emails.
     *
     * @return array{templateContent: string, school: mixed, franchise: mixed, sharedData: array<string, mixed>}
     */
    protected function orderEmailContext(OrderModel $order, User $authUser, Template $template): array
    {
        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        $templateContent = File::exists($templatePath) ? File::get($templatePath) : '';

        $school = $authUser->getSchool();
        $franchise = $authUser->getFranchise();

        $order->loadMissing(['product', 'fieldValues.customField.fieldOptions', 'artworkRequired', 'artworkFiles']);

        $sharedData = [
            'ORDER_NUMBER' => $order->order_number,
            'PRODUCT_NAME' => $order->product->name ?? '—',
            'FRANCHISE_NAME' => $franchise->name ?? '—',
            'SCHOOL_NAME' => $school->name ?? '—',
            'SUBMITTED_BY' => $authUser->name,
            'SUBMITTED_EMAIL' => $authUser->email,
            'SUBMITTED_CONTACT' => $authUser->contact ?? '—',
            'ORDER_FIELDS_HTML' => $this->buildOrderFieldsHtml($order),
            'ARTWORK_DETAILS_HTML' => $this->buildOrderArtworkHtml($order),
        ];

        return [
            'templateContent' => $templateContent,
            'school' => $school,
            'franchise' => $franchise,
            'sharedData' => $sharedData,
        ];
    }

    /**
     * Builds the raw EML for one order email (print team or requestor).
     *
     * @param array  $context   from orderEmailContext()
     * @param string $recipient Email::ORDER_RECIPIENT_PRINT or ORDER_RECIPIENT_REQUESTOR
     */
    protected function buildOrderEml(array $context, OrderModel $order, User $authUser, string $recipient, string $toAddress, ?string $downloadUrl, $sentDate): string
    {
        if ($recipient === Email::ORDER_RECIPIENT_PRINT) {
            $recipientData = [
                'HEADING_TEXT' => 'Artwork/Print Request',
                'SIGNATURE_HTML' => $this->buildPrintTeamSignatureHtml($order),
                'ORDER_NUMBER_LINE_HTML' => $this->buildOrderNumberLineHtml($order),
                'RECIPIENT_NAME' => 'Printing Team',
                'INTRO_TEXT' => 'Please find the request received for Artwork/Print below.',
            ];
            $toName = 'Print Request';
            $subject = "We have received a Artwork/Print Request - {$order->order_number}";
        } else {
            $recipientData = [
                'HEADING_TEXT' => 'THANK YOU FOR ORDERING WITH MSP PHOTOGRAPHY',
                'SIGNATURE_HTML' => $this->buildRequestorSignatureHtml($order, $context['franchise']),
                'ORDER_NUMBER_LINE_HTML' => '',
                'RECIPIENT_NAME' => e($authUser->name ?: 'there'),
                'INTRO_TEXT' => 'Thanks for your order. This is a copy of the request we have sent to our print team - please check the details below are correct, and let your Local MSP Photography Expert know if anything needs changing.',
            ];
            $toName = $authUser->name ?: $toAddress;
            $subject = "Please check your Artwork/Print Request - {$order->order_number}";
        }

        $content = $this->replaceTemplateVariables($context['templateContent'], $context['sharedData'] + $recipientData + [
            'ARTWORK_DOWNLOAD_BUTTON_HTML' => $this->buildOrderDownloadButtonHtml($order, $downloadUrl),
        ]);

        return MessageConverter::toEmail($this->generateEmail(
            $authUser,
            (object) ['email' => $toAddress, 'name' => $toName],
            $subject,
            $content,
            $sentDate
        ))->toString();
    }

    /**
     * Called when an order email is resent. The artwork download link in the
     * stored body is single-use and expires (file_transfer.expiry_days), so
     * resending the stored body late would send a dead link. This zips the
     * order's artwork again, uploads it for a fresh link, and rebuilds the
     * stored EML with that link. The caller saves the row.
     *
     * Returns true when the body was regenerated. It returns false - and the
     * stored body is left alone - when this is not an order email, the order
     * has no artwork, the artwork files are no longer on disk, or the new
     * upload fails, so resending is never blocked by this step.
     */
    public function refreshOrderEmailForResend(Email $email): bool
    {
        if (!$email->order_id || !$email->order_recipient) {
            return false;
        }

        try {
            $order = OrderModel::with('artworkFiles')->find($email->order_id);
            $authUser = $email->generated_from_user_id ? User::find($email->generated_from_user_id) : null;
            $template = Template::where('template_name', 'Ordering')->first();

            if (!$order || !$authUser || !$template || $order->artworkFiles->isEmpty()) {
                return false;
            }

            $anyOnDisk = $order->artworkFiles->contains(
                fn ($file) => Storage::disk('public')->exists($file->path)
            );

            if (!$anyOnDisk) {
                Log::warning('Order email resend: artwork files are no longer on disk, keeping the old download link.', [
                    'order_id' => $order->id,
                    'email_id' => $email->id,
                ]);

                return false;
            }

            $link = $this->fileTransferService->uploadOrderArtworkLinks($order, 1)[0] ?? null;

            if ($link === null) {
                return false;
            }

            $context = $this->orderEmailContext($order, $authUser, $template);

            $email->email_content = $this->buildOrderEml(
                $context,
                $order,
                $authUser,
                $email->order_recipient,
                (string) $email->email_to,
                $link,
                now()
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Order email resend: could not regenerate the download link.', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sign-off for the print team's email (fixed MSP Resource Centre details).
     */
    protected function buildPrintTeamSignatureHtml(OrderModel $order): string
    {
        $link = 'text-decoration: none; color: #666666; font-weight: 700;';
        $subject = 'MSP%20Artwork/Print%20Request%20-%20' . rawurlencode((string) $order->order_number);

        return 'Regards,<br/>'
            . 'MSP Photography<br/>'
            . 'Resource Centre<br/>'
            . '1800 273 283<br/>'
            . '<a href="mailto:info@msp.com.au?subject=' . $subject . '" style="' . $link . '">info@msp.com.au</a><br/>'
            . '<a href="https://www.msp.com.au" style="' . $link . '">www.msp.com.au</a>';
    }

    /**
     * Sign-off for the requestor's copy: their own franchise's contact details.
     */
    protected function buildRequestorSignatureHtml(OrderModel $order, $franchise): string
    {
        $link = 'text-decoration: none; color: #666666; font-weight: 700;';
        $name = trim((string) ($franchise->name ?? '')) ?: 'MSP Photography';
        $email = trim((string) ($franchise->email ?? ''));
        $web = trim((string) config('app.franchise_web_address', 'www.msp.com.au')) ?: 'www.msp.com.au';
        $webHref = preg_match('#^https?://#i', $web) ? $web : 'https://' . $web;
        $subject = 'MSP%20Artwork/Print%20Request%20-%20' . rawurlencode((string) $order->order_number);

        $html = 'Regards,<br/>' . e($name) . '<br/>';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $html .= '<a href="mailto:' . e($email) . '?subject=' . $subject . '" style="' . $link . '">' . e($email) . '</a><br/>';
        }

        return $html . '<a href="' . e($webHref) . '" style="' . $link . '">' . e(preg_replace('#^https?://#i', '', $web)) . '</a>';
    }

    protected function buildOrderNumberLineHtml(OrderModel $order): string
    {
        return '<p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 11px; color: #999999; letter-spacing: 0.02em; margin: 4px 0 0 0;">'
            . 'Order #' . e($order->order_number)
            . '</p>';
    }

    protected function buildOrderFieldsHtml(OrderModel $order): string
    {
        $rows = $this->orderDetailRow('Product Required', e($order->product->name ?? '—'));

        foreach ($order->fieldValues as $fieldValue) {
            $label = e($fieldValue->customField->label ?? 'Field');
            $value = nl2br(e($this->orderFieldDisplayValue($fieldValue) ?? '—'));
            $rows .= $this->orderDetailRow($label, $value);
        }

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; table-layout: fixed;">' . $rows . '</table>';
    }

    /**
     * Orders store the selected option's option_value; show the option_name
     * instead. A checkbox field stores several values joined by ", ". Text typed
     * for an "Other" option matches no option_value and is shown as typed.
     */
    protected function orderFieldDisplayValue($fieldValue): ?string
    {
        $value = $fieldValue->value;
        $field = $fieldValue->customField;

        if ($value === null || $value === '' || !$field || !$field->hasOptions()) {
            return $value;
        }

        $names = $field->fieldOptions->pluck('option_name', 'option_value');

        if ($names->has($value)) {
            return $names[$value];
        }

        return collect(explode(', ', $value))
            ->map(fn ($part) => $names->get($part, $part))
            ->implode(', ');
    }

    protected function buildOrderArtworkHtml(OrderModel $order): string
    {
        $rows = '';
        $rows .= $this->orderDetailRow('Artwork Required?', e($order->artworkRequired->details ?? '—'));
        $rows .= $this->orderDetailRow('Due Date', e($order->artwork_due_date?->format('d/m/Y') ?: '—'));
        $rows .= $this->orderDetailRow('Additional Comments', nl2br(e($order->additional_comments ?: '—')));
        $rows .= $this->orderDetailRow('Artwork Brief', $order->artwork_brief ? $this->inlineRichTextStyles($order->artwork_brief) : '—');

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; table-layout: fixed;">' . $rows . '</table>';
    }

    protected function buildOrderDownloadButtonHtml(OrderModel $order, ?string $downloadUrl): string
    {
        if ($order->artworkFiles->isEmpty()) {
            return '<p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #999999; margin: 0;">No files were uploaded with this order.</p>';
        }

        if ($downloadUrl === null) {
            return '<p style="font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #999999; margin: 0;">Uploaded files are available in the admin portal.</p>';
        }

        $fileCount = $order->artworkFiles->count();
        $label = $fileCount === 1 ? 'Download File' : "Download Files ({$fileCount})";

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0;">'
            . '<tr>'
            . '<td style="border-radius: 6px; background-color: #00b3e0;">'
            . '<a href="' . e($downloadUrl) . '" target="_blank" style="display: inline-block; padding: 12px 26px; font-family: \'Montserrat\', Helvetica, Arial, sans-serif !important; font-size: 13px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 6px;">'
            . '⬇ ' . e($label)
            . '</a>'
            . '</td>'
            . '</tr>'
            . '</table>';
    }

    protected function orderDetailRow(string $label, string $valueHtml): string
    {
        // Same geometry as the hand-written Order Details rows in the template
        // (190px label column, no side padding) so every section lines up.
        // table-layout:fixed on the parent table makes the label column stick
        // to 190px even when a value (e.g. the rich-text brief) is wide.
        $font = "font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; vertical-align: top; line-height: 1.5; border-bottom: 1px solid #e7ecf2;";

        return '<tr>'
            . '<td width="190" style="padding: 10px 12px 10px 0; ' . $font . ' color: #5b6b82; width: 190px; min-width: 190px;"><strong>' . e($label) . '</strong></td>'
            . '<td style="padding: 10px 0; ' . $font . ' color: #1f2937; word-wrap: break-word;">' . $valueHtml . '</td>'
            . '</tr>';
    }

    protected function inlineRichTextStyles(string $html): string
    {
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;

        static $buttonCss = <<<'CSS'
            .btn {
                display: inline-block;
                font-weight: 400;
                line-height: 1.5;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                cursor: pointer;
                user-select: none;
                background-color: transparent;
                border: 1px solid transparent;
                padding: 6px 12px;
                font-size: 14px;
                border-radius: 6px;
                margin: 2px 4px 2px 0;
                text-decoration: none;
            }
            .btn-primary { color: #ffffff; background-color: #0d6efd; border-color: #0d6efd; }
            .btn-secondary { color: #ffffff; background-color: #6c757d; border-color: #6c757d; }
            .btn-success { color: #ffffff; background-color: #198754; border-color: #198754; }
            .btn-danger { color: #ffffff; background-color: #dc3545; border-color: #dc3545; }
            .btn-warning { color: #000000; background-color: #ffc107; border-color: #ffc107; }
            .btn-info { color: #000000; background-color: #0dcaf0; border-color: #0dcaf0; }
            .btn-light { color: #000000; background-color: #f8f9fa; border-color: #f8f9fa; }
            .btn-dark { color: #ffffff; background-color: #212529; border-color: #212529; }
            p { margin: 0 0 8px 0; line-height: 1.6; }
            ul, ol { margin: 0 0 8px 20px; padding: 0; }
            li { margin: 0 0 3px 0; line-height: 1.6; }
            hr { border: 0; border-top: 1px solid #e7ecf2; margin: 12px 0; }
            td, th { padding: 4px 8px; vertical-align: top; }
            CSS;

        try {
            $inlined = (new CssToInlineStyles())->convert($html, $buttonCss);

            if (preg_match('/<body[^>]*>(.*)<\/body>/is', $inlined, $match)) {
                return trim($match[1]);
            }

            return $inlined;
        } catch (\Throwable $e) {
            Log::warning('Could not inline Artwork Brief rich-text styles for the order email - using it as-is.', [
                'message' => $e->getMessage(),
            ]);

            return $html;
        }
    }

}
