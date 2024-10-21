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
}