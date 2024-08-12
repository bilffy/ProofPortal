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
                'colour_code' => '',
                'created_at' => Carbon::now(),
            ],
            [
                'status_internal_name' => 'DISABLED',
                'status_external_name' => 'Disabled',
                'colour_code' => '',
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
