<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use Carbon\Carbon;

class FolderTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('folder_tags')->insert([
            [
                'tag' => 'sp',
                'external_name' => 'Speciality Group',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'f',
                'external_name' => 'Family',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'families',
                'external_name' => 'Family',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'siblings',
                'external_name' => 'Family',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'student',
                'external_name' => 'Student',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'c',
                'external_name' => 'Student',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'p',
                'external_name' => 'Student',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'y',
                'external_name' => 'Student',
                'created_at' => Carbon::now(),
            ],
            [
                'tag' => 'staff',
                'external_name' => 'Staff',
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
