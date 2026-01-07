<?php

namespace App\Services\Proofing;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Swift_Mime_ContentEncoder_Base64ContentEncoder;
use App\Models\EmailCategory;
use App\Models\FolderUser;
use App\Models\Template;
use App\Models\Folder;
use App\Models\Status;
use App\Models\Email;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;
use Swift_Message;
use Auth;
use DB;


class EmailService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function saveEmailContent($tsJobKey, $field, $date, $status = null)
    {
        $authUser = Auth::user();
    
        // Fetch the template
        $template = Template::where('template_name', $field)->first();
        if (!$template) {
            throw new \Exception('Template not found');
        }
    
        // Check if the dynamic column exists in the `jobs` table
        $columnsToSelect = ['id', 'notifications_matrix', 'ts_schoolkey', 'ts_account_id', 'ts_job_id', 'ts_jobname', 'proof_due'];
        if (Schema::hasColumn('jobs', $field)) {
            $columnsToSelect[] = $field;
        }
    
        // Fetch the job data
        $selectedJob = Job::with(['franchises','folders'])
            ->where('ts_jobkey', $tsJobKey)
            ->whereNotNull('notifications_matrix')
            ->when(Schema::hasColumn('jobs', $field), function ($query) use ($field) {
                return $query->whereNotNull($field);
            })
            ->select($columnsToSelect)
            ->first();
    
        if ($selectedJob) {

            $allJobFolders = $selectedJob->folders->pluck('ts_foldername')->toArray();
            // Decode notifications matrix and filter roles
            $notificationsMatrix = json_decode($selectedJob->notifications_matrix, true);
            $rolesWithFieldTrue = isset($notificationsMatrix['schools'][$field])
                ? array_keys(array_filter($notificationsMatrix['schools'][$field], fn($value) => $value === true))
                : [];
        
            if (empty($rolesWithFieldTrue)) {
                throw new \Exception("No roles found for field {$field} in notifications matrix.");
            }
        
            // Map role names and fetch corresponding user IDs
            $roleNames = array_map(fn($role) => str_replace(
                ['franchise', 'photocoordinator', 'teacher'],
                ['Franchise', 'Photo Coordinator', 'Teacher'],
                $role
            ), (array)$rolesWithFieldTrue);
        
            $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
        
            $userIds = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
            ->where(function ($query) use ($selectedJob) {
                $query->whereHas('schools', fn($q) => $q->where('schoolkey', $selectedJob->ts_schoolkey))
                    ->orWhereHas('franchises', fn($q) => $q->where('ts_account_id', $selectedJob->ts_account_id));
            })
            ->pluck('id');
        
        
            // Fetch user details
            $users = User::whereIn('id', $userIds)->select('id', 'name', 'email', 'firstname', 'lastname')->get();
        
            if ($users->isEmpty()) {
                throw new \Exception('No users found to send the email.');
            }
        
            foreach ($users as $user) {
                // Fetch folders associated with the job and user
                $userFolders = DB::table('jobs')
                    ->join('folders', 'folders.ts_job_id', '=', 'jobs.ts_job_id')
                    ->join('folder_users', 'folder_users.ts_folder_id', '=', 'folders.ts_folder_id')
                    ->where([['folder_users.user_id', $user->id], ['jobs.ts_jobkey', $tsJobKey]])
                    ->pluck('ts_foldername')->toArray();  // Get the results

                // Load template content
                $templatePath = resource_path("views/proofing/emails/{$template->template_location}{$template->template_format}");
                if (!File::exists($templatePath) || empty($template->template_location) || empty($template->template_format)) {
                    throw new \Exception("Template file not found at {$templatePath}");
                }
                $templateContent = File::get($templatePath);
        
                // Replace variables in the template
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
                    'JOB_STATUS_NAME' => Status::find($status)->status_external_name ?? '',
                ];
        
                $processedContent = $this->replaceTemplateVariables($templateContent, $data);

                $templateSubject = $template->template_subject;
        
                // Safely replace JOB_NAME
                if (strpos($templateSubject, 'JOB_NAME') !== false) {
                    $jobName = $selectedJob->ts_jobname ?? 'Unknown Job'; // Fallback if job name is null
                    $templateSubject = str_replace('JOB_NAME', $jobName, $templateSubject);
                }

                $message = new Swift_Message();
                $message->setFrom([$authUser->email => $authUser->name])
                        ->setTo([$user->email => $user->name])
                        ->setSubject('=?UTF-8?B?' . base64_encode($templateSubject) . '?=');

                $message->setBody($processedContent, 'text/html'); // Set the body content
                $message->setEncoder(new Swift_Mime_ContentEncoder_Base64ContentEncoder()); // Set base64 encoding  
        
                $emlContent = $message->toString();
        
                // $filePath = public_path("$field.eml");
                // file_put_contents($filePath, $emlContent);
        
                // Save email record in the database
                Email::create([
                    'generated_from_user_id' => $authUser->id,
                    'alphacode' => $selectedJob->franchises->alphacode,
                    'ts_jobkey' => $tsJobKey,
                    'ts_schoolkey' => $selectedJob->ts_schoolkey,
                    'sentdate' => $date,
                    'email_from' => $authUser->email,
                    'email_to' => $user->email,
                    'email_content' => $emlContent,
                    'template_id' => $template->id
                ]);
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
    
        $roleIds = Role::whereIn('name', $mappedRoleNames)->pluck('id');

        $query = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
            ->select('users.id')
            ->distinct();
    
        $schoolQuery = $query->clone()
            ->join('school_users', 'school_users.user_id', '=', 'users.id')
            ->join('schools', 'school_users.school_id', '=', 'schools.id')
            ->where('schools.schoolkey', $selectedFolder->job->ts_schoolkey);
    
        $franchiseQuery = $query->clone()
            ->join('franchise_users', 'franchise_users.user_id', '=', 'users.id')
            ->join('franchises', 'franchise_users.franchise_id', '=', 'franchises.id')
            ->where('franchises.ts_account_id', $selectedFolder->job->ts_account_id);
    
        $finalUserIds = $schoolQuery->union($franchiseQuery)->pluck('id');
    
        $users = User::whereIn('id', $finalUserIds)
            ->select('id', 'name', 'email', 'firstname', 'lastname')
            ->get();

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

            $message = new Swift_Message();
            $message->setFrom([$authUser->email => $authUser->name])
                    ->setTo([$user->email => $user->name])
                    ->setSubject('=?UTF-8?B?' . base64_encode($templateSubject) . '?=');

            $message->setBody($processedContent, 'text/html'); // Set the body content
            $message->setEncoder(new Swift_Mime_ContentEncoder_Base64ContentEncoder()); // Set base64 encoding  
    
            $emlContent = $message->toString();
    
            // $filePath = public_path("$field.eml");
            // file_put_contents($filePath, $emlContent);
    
            Email::create([
                'generated_from_user_id' => $authUser->id,
                'alphacode' => $selectedFolder->job->franchises->alphacode ?? null,
                'ts_jobkey' => $selectedFolder->job->ts_jobkey,
                'ts_schoolkey' => $selectedFolder->job->ts_schoolkey,
                'sentdate' => $date,
                'email_from' => $authUser->email,
                'email_to' => $user->email,
                'email_content' => $emlContent,
                'template_id' => $template->id
            ]);
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

        $processedContent = $this->replaceTemplateVariables($templateContent, $data);

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
        
        $processedContent = str_replace("{DOWNLOAD_INSTRUCTIONS}", $downloadInstructions, $processedContent); 

        $templateSubject = $template->template_subject;

        // Safely replace INVITEE_FIRST_NAME, INVITEE_LAST_NAME
        if (strpos($templateSubject, 'INVITEE_FIRST_NAME') !== false) {
            $templateSubject = str_replace('INVITEE_FIRST_NAME', $inviteUser->firstname, $templateSubject);
        }

        if (strpos($templateSubject, 'INVITEE_LAST_NAME') !== false) {
            $templateSubject = str_replace('INVITEE_LAST_NAME', $inviteUser->lastname, $templateSubject);
        }

        $message = new Swift_Message();
        $message->setFrom([$authUser->email => $authUser->name])
                ->setTo([$inviteUser->email => $inviteUser->name])
                ->setSubject('=?UTF-8?B?' . base64_encode($templateSubject) . '?=');

        $message->setBody($processedContent, 'text/html'); // Set the body content
        $message->setEncoder(new Swift_Mime_ContentEncoder_Base64ContentEncoder()); // Set base64 encoding  

        $emlContent = $message->toString();

        // $filePath = public_path("$field.eml");
        // file_put_contents($filePath, $emlContent);

        Email::create([
            'generated_from_user_id' => $authUser->id,
            'alphacode' => $folderUsers->first()->folder->job->franchises->alphacode ?? null,
            'ts_jobkey' => $jobkey,
            'ts_schoolkey' => $folderUsers->first()->folder->job->ts_schoolkey,
            'sentdate' => $date,
            'email_from' => $authUser->email,
            'email_to' => $inviteUser->email,
            'email_content' => $emlContent,
            'template_id' => $template->id
        ]);
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
