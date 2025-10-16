<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use Carbon\Carbon;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('templates')->insert([
            [
                'template_name' => 'proof_start',
                'template_location' => 'proof_start_template',
                'template_subject' => 'MSP Photography Online Proofing Commencement Notification',
                'template_format' => '.blade.php',
                'email_category_id' => 2,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'proof_warning',
                'template_location' => 'proof_warning_template',
                'template_subject' => 'MSP Photography Online Proofing Reminder Notification',
                'template_format' => '.blade.php',
                'email_category_id' => 2,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'proof_due',
                'template_location' => 'proof_due_template',
                'template_subject' => 'MSP Photography Online Proofing Due Notification',
                'template_format' => '.blade.php',
                'email_category_id' => 2,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'proof_catchup',
                'template_location' => 'proof_catchup_template',
                'template_subject' => 'MSP Photography Online Proofing Catchup Day Notification',
                'template_format' => '.blade.php',
                'email_category_id' => 2,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'job_status_completed',
                'template_location' => 'change_job_status_template',
                'template_subject' => 'Status change for JOB_NAME',
                'template_format' => '.blade.php',
                'email_category_id' => 3,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'job_status_modified',
                'template_location' => 'change_job_status_template',
                'template_subject' => 'Status change for JOB_NAME',
                'template_format' => '.blade.php',
                'email_category_id' => 3,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'job_status_unlocked',
                'template_location' => 'change_job_status_template',
                'template_subject' => 'Status change for JOB_NAME',
                'template_format' => '.blade.php',
                'email_category_id' => 3,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'folder_status_completed',
                'template_location' => 'change_folder_status_template',
                'template_subject' => 'Status change for JOB_NAME - FOLDER_NAMEn',
                'template_format' => '.blade.php',
                'email_category_id' => 4,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'folder_status_modified',
                'template_location' => 'change_folder_status_template',
                'template_subject' => 'Status change for JOB_NAME - FOLDER_NAME',
                'template_format' => '.blade.php',
                'email_category_id' => 4,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'folder_status_unlocked',
                'template_location' => 'change_folder_status_template',
                'template_subject' => 'Status change for JOB_NAME - FOLDER_NAME',
                'template_format' => '.blade.php',
                'email_category_id' => 4,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'photocordinator_invitation',
                'template_location' => 'invite_photocoordinator_reminder',
                'template_subject' => 'Invitation for INVITEE_FIRST_NAME INVITEE_LAST_NAME to join Blueprint',
                'template_format' => '.blade.php',
                'email_category_id' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'template_name' => 'teacher_invitation',
                'template_location' => 'invite_teacher_reminder',
                'template_subject' => 'Invitation for INVITEE_FIRST_NAME INVITEE_LAST_NAME to join Blueprint',
                'template_format' => '.blade.php',
                'email_category_id' => 1,
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
