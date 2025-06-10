<?php

namespace App\Http\Livewire\Settings;

use App\Http\Resources\UserResource;
use App\Models\Setting;
use App\Models\User;
use Auth;
use Livewire\Component;

class FeatureControl extends Component
{
    public $settingsStates = [];
    public function mount()
    {
        $this->settingsStates = Setting::pluck('property_value', 'id')
            ->map(fn($v) => $v === 'true')
            ->toArray();    
    }

    public function updateSettingValue($key, $value)
    {
        $setting = Setting::find($key);
        if ($setting) {
            $setting->property_value = $value === 'true' ? 'false' : 'true';
            $setting->save();
        }
    }
    
    public function render()
    {   
        /** @var User $user */
        $user = Auth::user();
        
        // Query the Setting model to check if the user is an admin
        $settings = Setting::query()->get();
        
        return view('livewire.settings.feature-control',
            [
                'isAdmin' => $user->isAdmin(),
                'settings' => $settings,
                'settingsStates' => $this->settingsStates,
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
