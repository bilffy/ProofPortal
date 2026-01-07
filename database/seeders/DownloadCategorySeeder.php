<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DownloadCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('download_category')->insert([
            [
                'category_name' => 'Individual'
            ],
            [
                'category_name' => 'Bulk'
            ],
        ]);
    }
}
