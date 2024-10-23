<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UiSetting extends Model
{
    use HasFactory;

    protected $table = 'ui_settings';

    protected $fillable = [
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Set the navigation collapse setting
     * @param $collapsed
     * @return void
     */
    public static function setNavCollapsed($collapsed)
    {
        $setting = self::firstOrCreate([]);
        
        $settings = $setting->settings;
        $settings['navigation']['collapse'] = $collapsed;
        $setting->settings = $settings;
        $setting->save();
    }

    /**
     * Check if the navigation is collapsed
     * @return bool
     */
    public static function isNavCollapsed(): bool
    {
        $setting = self::first();
        return $setting ? ($setting->settings['navigation']['collapse'] ?? false) : false;
    }
}