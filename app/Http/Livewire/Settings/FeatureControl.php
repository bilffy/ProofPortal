<?php

namespace App\Http\Livewire\Settings;

use App\Models\Setting;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Auth;
use App\Http\Resources\UserResource;

class FeatureControl extends Component
{
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public function mount()
    {
        
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function sortBy($field)
    {
        
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
                'settings' => $settings
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
