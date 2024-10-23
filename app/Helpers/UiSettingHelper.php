<?php

namespace App\Helpers;

use App\Models\UiSetting;

class UiSettingHelper
{   
    public const UI_SETTING_NAV_COLLAPSED = 'collapse';
    public static function getUiSetting(string $setting): bool
    {
        $settings = UiSetting::find(1)->settings;
        
        if ($setting === self::UI_SETTING_NAV_COLLAPSED) {
            return $settings['navigation']['collapse'];
        }
        
        return false;
    }
}