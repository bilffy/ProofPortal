<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UiSetting;

class UiSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Storing settings
        $settings = [
            'navigation' => [
                'collapse' => false,
            ],
        ];

        UiSetting::create(['settings' => $settings, 'user_id' => 1]);
    }
}
