<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentCount = User::all()->count();
        $processData = function (Sequence $sequence) use ( $currentCount ) {
            $code = $currentCount + $sequence->index;
            return [
                'email' => "test{$code}@example.com",
                'username' => "test{$code}@example.com",
            ];
        };
        User::factory()
            ->count(10)
            ->sequence($processData)
            ->create();
    }
}
