<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed portal feature flags (settings table).
     */
    public function run(): void
    {
        $booleanOptions = ['options' => ['true', 'false']];

        $settings = [
            [
                'name' => 'High Res Download Option',
                'description' => 'High res images can be downloaded',
                'property_group' => 'portal_feature',
                'property_key' => 'high_res_download_option',
                'property_value' => 'true',
            ],
            [
                'name' => 'Low Res Download Option',
                'description' => 'Low res images can be downloaded',
                'property_group' => 'portal_feature',
                'property_key' => 'low_res_download_option',
                'property_value' => 'false',
            ],
            [
                'name' => 'Groups Tab',
                'description' => 'Show Photography Groups tab',
                'property_group' => 'portal_feature',
                'property_key' => 'groups_tab',
                'property_value' => 'false',
            ],
            [
                'name' => 'Other Tab',
                'description' => 'Show Photography Other tab',
                'property_group' => 'portal_feature',
                'property_key' => 'other_tab',
                'property_value' => 'false',
            ],
            [
                'name' => 'Configure Tab v1',
                'description' => 'Show Configure tab v1',
                'property_group' => 'portal_feature',
                'property_key' => 'configure_tab_v1',
                'property_value' => 'true',
            ],
            [
                'name' => 'Configure Tab v2',
                'description' => 'Show Configure tab v2',
                'property_group' => 'portal_feature',
                'property_key' => 'configure_tab_v2',
                'property_value' => 'false',
            ],
            [
                'name' => 'Proofing Menu',
                'description' => 'Show Proofing in left nav menu',
                'property_group' => 'portal_feature',
                'property_key' => 'proofing_menu',
                'property_value' => 'true',
            ],
            [
                'name' => 'Screen Quality Download Option',
                'description' => 'Screen Quality images can be downloaded',
                'property_group' => 'portal_feature',
                'property_key' => 'screen_quality_download_option',
                'property_value' => 'true',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['property_key' => $setting['property_key']],
                array_merge($setting, ['selections' => $booleanOptions])
            );
        }
    }
}
