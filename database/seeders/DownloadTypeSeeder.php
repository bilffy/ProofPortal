<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DownloadTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('download_type')->insert([
            [
                'download_type' => 'Portrait'
            ],
            [
                'download_type' => 'Group'
            ],
        ]);
    }
}
