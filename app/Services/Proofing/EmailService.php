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
            ->from(new Address('noreply@msp.com.au', 'MSP Photography Blueprint - Do Not Reply'))
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
    
    public function updateEmailSend($field, $decryptedJobKey)
    {
        $authUser = Auth::user();
    
        $template = Template::where('template_name', $field)->firstOrFail();
    
        $columnsToSelect = [
            'id',
            'notifications_matrix',
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
    
        $sentDate = $selectedJob->$field;
    
        if (empty($sentDate)) {
            return;
        }
    
        $sentDateCarbon = Carbon::parse($sentDate);
    
        $allJobFolders = $selectedJob->folders->pluck('ts_foldername')->toArray();
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
            ->where(function ($query) use ($selectedJob) {
                $query->whereHas('schools', fn ($q) =>
                    $q->where('schoolkey', $selectedJob->ts_schoolkey)
                )->orWhereHas('franchises', fn ($q) =>
                    $q->where('ts_account_id', $selectedJob->ts_account_id)
                );
            })
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
        foreach ($users as $user) {
            $alreadyExists = Email::where('template_id', $template->id)
                ->where('ts_jobkey', $decryptedJobKey)
                ->where('status_id', $this->statusService->pending)
                ->whereDate('sentdate', $sentDateCarbon)
                ->where('email_from', $authUser->email)
                ->where('email_to', $user->email)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $userFolders = $selectedJob->folders
                ->filter(fn ($folder) =>
                    DB::table('folder_users')
                        ->where('ts_folder_id', $folder->ts_folder_id)
                        ->where('user_id', $user->id)
                        ->exists()
                )
                ->pluck('ts_foldername')
                ->toArray();
    
            $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
    
            if (!File::exists($templatePath)) {
                throw new \Exception("Email template not found: {$templatePath}");
            }
    
            $templateContent = File::get($templatePath);
            $statusModel = Status::find($selectedJob->job_status_id);
    
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
    
            $emailMessage = $this->generateEmail($authUser, $user, $template->template_subject, $processedContent, $sentDate);

            $emlContent = MessageConverter::toEmail($emailMessage)->toString();
    
            $this->storeEmailRecord($authUser, $selectedJob, $user, $template, $sentDate, $emlContent, $decryptedJobKey);
        }
    }
    

    public function saveEmailContent($tsJobKey, $field, $date, $status = null)
    {
        $authUser = Auth::user();
    
        // Fetch the template
        $template = Template::where('template_name', $field)->firstOrFail();
    
        // Fetch the job data
        $columnsToSelect = ['id', 'notifications_matrix', 'notifications_enabled', 'ts_schoolkey', 'ts_account_id', 'ts_job_id', 'ts_jobname', 'proof_due'];
        if (Schema::hasColumn('jobs', $field)) {
            $columnsToSelect[] = $field;
        }
    
        $selectedJob = Job::with(['franchises','folders'])
            ->where('ts_jobkey', $tsJobKey)
            ->select($columnsToSelect)
            ->firstOrFail();

        if (!$selectedJob) {
            abort(404); 
        }
    
        $allJobFolders = $selectedJob->folders->pluck('ts_foldername')->toArray();
        $notificationsMatrix = json_decode($selectedJob->notifications_matrix, true);
    
        $rolesWithFieldTrue = $notificationsMatrix['schools'][$field] ?? [];
        $rolesWithFieldTrue = array_keys(array_filter($rolesWithFieldTrue, fn($v) => $v === true));
        \Log::info('saveEmailContent roles check', ['field' => $field, 'roles' => $rolesWithFieldTrue, 'notifications_enabled' => $selectedJob->notifications_enabled]);
   
        // Prepare template content
        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        $templateContent = File::exists($templatePath) ? File::get($templatePath) : '';
        $statusModel = $status ? Status::find($status) : null;
    
        if (!empty($rolesWithFieldTrue) && $selectedJob->notifications_enabled === 1) {

            $roleNames = array_map(fn($role) => str_replace(
                ['franchise', 'photocoordinator', 'teacher'],
                ['Franchise', 'Photo Coordinator', 'Teacher'],
                $role // <-- only the single role
            ), $rolesWithFieldTrue);
            
    
            $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
    
            $userIds = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
                ->where(function ($query) use ($selectedJob) {
                    $query->whereHas('schools', fn($q) => $q->where('schoolkey', $selectedJob->ts_schoolkey))
                          ->orWhereHas('franchises', fn($q) => $q->where('ts_account_id', $selectedJob->ts_account_id));
                })
                ->pluck('id');
    
            $users = User::whereIn('id', $userIds)->select('id','name','email','firstname','lastname')->get();
            \Log::info('saveEmailContent users found', ['count' => $users->count(), 'job' => $tsJobKey]);
    
            foreach ($users as $user) {
                $userFolders = $selectedJob->folders
                    ->filter(fn($f) => DB::table('folder_users')->where('ts_folder_id', $f->ts_folder_id)->where('user_id', $user->id)->exists())
                    ->pluck('ts_foldername')
                    ->toArray();

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
                $emailMessage = $this->generateEmail($authUser, $user, $template->template_subject, $processedContent, $date);
    
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

        $folderNamesArray = $selectedFolders->pluck('ts_foldername')->toArray();
        $folderNames = implode(', ', $folderNamesArray);
    
        $selectedFolder = $selectedFolders->first();
        if (!$selectedFolder || !$selectedFolder->job) {
            return response()->json(['error' => 'No folder or job found'], 404);
        }
    
        $notificationsMatrix = json_decode($selectedFolder->job->notifications_matrix, true) ?? [];
        $rolesWithFieldTrue = $notificationsMatrix['folders'][$field] ?? [];
        $roleNames = array_keys(array_filter($rolesWithFieldTrue, fn($value) => $value === true));
    
        $mappedRoleNames = array_map(fn($role) => str_replace(
            ['franchise', 'photocoordinator', 'teacher'],
            ['Franchise', 'Photo Coordinator', 'Teacher'],
            $role
        ), $roleNames);
        \Log::info('saveEmailFolderContent mapped roles', ['field' => $field, 'mappedRoles' => $mappedRoleNames]);
    
        $roleIds = Role::whereIn('name', $mappedRoleNames)->pluck('id')->toArray();

        $users = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
            ->where(function ($query) use ($selectedFolder) {
                $query->whereHas('schools', fn($q) => $q->where('schoolkey', $selectedFolder->job->ts_schoolkey))
                      ->orWhereHas('franchises', fn($q) => $q->where('ts_account_id', $selectedFolder->job->ts_account_id));
            })
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();

        \Log::info('saveEmailFolderContent users found', ['count' => $users->count(), 'job' => $selectedFolder->job->ts_jobkey]);

        $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
        if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
            throw new \Exception("Template file not found at {$templatePath}");
        }
        $templateContent = File::get($templatePath);
    
        $statusName = Status::where('id', $status)->value('status_external_name') ?? '';
        $constantData = [
            'JOB_NAME' => $selectedFolder->job->ts_jobname ?? '',
            'FOLDER_NAME' => $folderNames ?? '',
            'FOLDER_STATUS_NAME' => $statusName,
            'APP_URL' => Config::get('app.url'),
            'FRANCHISE_WEB_ADDRESS' => 'www.msp.com.au',
        ];

        foreach ($users as $user) {
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
    
            $data = array_merge($constantData, $userData);
            $processedContent = $this->replaceTemplateVariables($templateContent, $data);

            $templateSubject = $template->template_subject;

            // Safely replace JOB_NAME
            if (strpos($templateSubject, 'JOB_NAME') !== false) {
                $jobName = $selectedFolder->job->ts_jobname ?? 'Unknown Job'; // Fallback if job name is null
                $templateSubject = str_replace('JOB_NAME', $jobName, $templateSubject);
            }

            // Safely replace FOLDER_NAME
            if (strpos($templateSubject, 'FOLDER_NAME') !== false) {
                $folderName = $folderNames ?? 'Unknown Folder'; // Fallback if folder name is null
                $templateSubject = str_replace('FOLDER_NAME', $folderName, $templateSubject);
            }

            $emailMessage = $this->generateEmail($authUser, $user, $templateSubject, $processedContent, $date);
            $emlContent = MessageConverter::toEmail($emailMessage)->toString();
    


            $this->storeEmailRecord($authUser, $selectedFolder->job, $user, $template, $date, $emlContent, $selectedFolder->job->ts_jobkey);
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
        $template = Template::where('template_name', $field)->first();
        $inviteUser = User::find($user);
        $folderUsers = FolderUser::where('user_id', $user)
        ->whereHas('folder.job', function ($query) use ($jobkey) {
            $query->where('ts_jobkey', $jobkey);
        })
        ->with('folder.job') // Ensure the job relationship is eager-loaded
        ->get();
        $selectedFolders = $folderUsers->pluck('folder.ts_foldername')->toArray();

        $data = [
                'INVITEE_FIRST_NAME' => $inviteUser->firstname ?? '',
                'INVITEE_LAST_NAME' => $inviteUser->lastname ?? '',
                'SENDER_FIRST_NAME' => $authUser->firstname ?? '',
                'SENDER_LAST_NAME' => $authUser->lastname ?? '',
                'JOB_NAME' => $folderUsers->first()->folder->job->ts_jobname ?? '',
                'FOLDERS' => $selectedFolders ?? '',
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

        $emailMessage = $this->generateEmail($authUser, $inviteUser, $templateSubject, $processedContent, $date);

        // Convert to RFC822 .eml
        $emlContent = MessageConverter::toEmail($emailMessage)->toString();

        // $filePath = public_path("$field.eml");
        // file_put_contents($filePath, $emlContent);

        $this->storeEmailRecord($authUser, $folderUsers->first()->folder->job, $inviteUser, $template, $date, $emlContent, $jobkey);
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
                $folderHtml .= "<b>- {$folder}</b><br/>";
            }
            $content = str_replace("{{#FOLDERS}}", $folderHtml, $content);
        }
        
        if (isset($data['ALLFOLDERS'])) {
            $folderHtml = '';
            foreach ($data['ALLFOLDERS'] as $folder) {
                $folderHtml .= "<b>- {$folder}</b><br/>";
            }
            $content = str_replace("{{#ALLFOLDERS}}", $folderHtml, $content);
        }
    
        return $content;
    }
    
}
