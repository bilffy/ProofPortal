<?php

namespace App\Helpers;

use App\Models\Setting;

class AppSettingsHelper
{   
    public static function getByPropertyKey(string $key): Setting|null
    {
        return Setting::where('property_key', $key)->first();
    }
}