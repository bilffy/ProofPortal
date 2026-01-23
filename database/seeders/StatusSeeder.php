<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('status')->insert([
            [
                'status_internal_name' => 'NEW',
                'status_external_name' => 'New',
                'colour_code' => '',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'INVITED',
                'status_external_name' => 'Invited',
                'colour_code' => '',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'ACTIVE',
                'status_external_name' => 'Active',
                'colour_code' => '#007bff',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'DELETED',
                'status_external_name' => 'Deleted',
                'colour_code' => '',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'INACTIVE',
                'status_external_name' => 'Inactive',
                'colour_code' => '',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'DISABLED',
                'status_external_name' => 'Disabled',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'SYNC',
                'status_external_name' => 'Sync',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'UNSYNC',
                'status_external_name' => 'Unsync',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'REVIEW',
                'status_external_name' => 'Review',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'SUCCESS',
                'status_external_name' => 'Success',
                'colour_code' => '#28a745',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'DUPLICATE',
                'status_external_name' => 'Duplicate',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'ERROR',
                'status_external_name' => 'Error',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'PENDING',
                'status_external_name' => 'Pending',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'NONE',
                'status_external_name' => 'None',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'COMPLETED',
                'status_external_name' => 'Completed',
                'colour_code' => '#28a745',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'ARCHIVED',
                'status_external_name' => 'Archived',
                'colour_code' => '#6c757d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'MODIFIED',
                'status_external_name' => 'Modified',
                'colour_code' => '#0062cc',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'VIEWED',
                'status_external_name' => 'Viewed',
                'colour_code' => '#007bff',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'HOLD',
                'status_external_name' => 'Hold',
                'colour_code' => '#ffc107',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'LOCKED',
                'status_external_name' => 'Locked',
                'colour_code' => '#1ab9d2',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'UNLOCKED',
                'status_external_name' => 'Unlocked',
                'colour_code' => '#1491a5',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'REJECTED',
                'status_external_name' => 'Rejected',
                'colour_code' => '#dc3545',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'INCOMPLETE',
                'status_external_name' => 'Incomplete',
                'colour_code' => '#ffad1a',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'AUTO APPROVED',
                'status_external_name' => 'Auto Approved',
                'colour_code' => '#595b5d',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'AWAITING APPROVAL',
                'status_external_name' => 'Awaiting Approval',
                'colour_code' => '#fda900',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'APPROVED',
                'status_external_name' => 'Approved',
                'colour_code' => '#15c21e',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'TNJ NOT FOUND',
                'status_external_name' => 'TNJ Not Found',
                'colour_code' => '#d11a2a',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'Download Started',
                'status_external_name' => 'Download Started',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'Download Failed',
                'status_external_name' => 'Download Failed',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'Download Email Failed',
                'status_external_name' => 'Download Email Failed',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'Download Completed',
                'status_external_name' => 'Download Completed',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'Download Email Sent',
                'status_external_name' => 'Download Email Sent',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'Nothing to Download',
                'status_external_name' => 'Nothing to Download',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'EXPIRED',
                'status_external_name' => 'Expired',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'EMAIL SENT',
                'status_external_name' => 'Email Sent',
                'colour_code' => null,
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
