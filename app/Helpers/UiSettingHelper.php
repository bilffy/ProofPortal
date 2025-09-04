<?php

namespace App\Helpers;

use App\Models\UiSetting;
use Auth;

class UiSettingHelper
{   
    public const UI_SETTING_NAV_COLLAPSED = 'collapse';
    public static function getUiSetting(string $setting): bool
    {   
        // Get settings from the login user
        $user = Auth::user();

        if (!$user->getUiSetting()) {
            return false;
        }
        
        $settings = $user->getUiSetting()->settings;
        
        if ($setting === self::UI_SETTING_NAV_COLLAPSED) {
            return $settings['navigation']['collapse'];
        }
        
        return false;
    }
}