<?php

namespace App\Livewire\Profile;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Auth;

//#[Layout('layouts.guest')]


class ResetMyPassword extends Component
{
    public $user;
    public $password;
    public $password_confirmation;
    
    protected $rules = [
        'password' => ['required', 'confirmed', 'min:12', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        'password_confirmation' => 'required',
    ];
    
    public function mount()
    {
        $this->password = '';
    }

    public function submit()
    {
        $this->validate();
        
        $user = auth()->user();

        $user->forceFill([
            'password' => Hash::make($this->password),
            'remember_token' => Str::random(60)
        ])->save();

        return redirect()->route('dashboard')->with('success', 'Password reset successfully.');
    }

    public function render()
    {
        return view('partials.users.forms.reset-my-password')
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}