<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class IssueCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('issue_categories')->insert([
            [
                'category_name' => 'Folder'
            ],
            [
                'category_name' => 'Subject'
            ],
            [
                'category_name' => 'Group'
            ],
            [
                'category_name' => 'General'
            ],
        ]);
    }
}
