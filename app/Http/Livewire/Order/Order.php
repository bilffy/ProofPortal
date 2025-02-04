<?php

namespace App\Http\Livewire\Order;

use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Livewire\Component;

class Order extends Component
{
    public $school;
    
    public function mount()
    {
        if (!Auth::user()->isSchoolAdmin()) {
            return redirect()->route('dashboard');
        }
    }

    public function render()
    {   
        /** @var User $user */
        $user = Auth::user();
        
        return view('livewire.order',
            [
                'user' => $user
                
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
