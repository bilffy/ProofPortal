<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [ 
                'name' => 'Super Admin',
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Admin',
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Franchise',
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Photo Coordinator',
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'School Admin',
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Teacher',
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
