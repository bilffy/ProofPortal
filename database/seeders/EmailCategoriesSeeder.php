<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use Carbon\Carbon;

class EmailCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('email_categories')->insert([
            [
                'email_category_name' => 'Invitation',
                'created_at' => Carbon::now(),
            ],
            [
                'email_category_name' => 'Proofing',
                'created_at' => Carbon::now(),
            ],
            [
                'email_category_name' => 'Job Status',
                'created_at' => Carbon::now(),
            ],
            [
                'email_category_name' => 'Folder Status',
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
