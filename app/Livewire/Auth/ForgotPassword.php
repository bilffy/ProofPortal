<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public $email;

    protected $rules = [
        'email' => 'required|email',
    ];

    public function submit()
    {
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if ($user) {
            $token = Password::createToken($user);
            $user->notify(new ResetPassword($token));
            $status = Password::RESET_LINK_SENT;
        } else {
            $status = Password::INVALID_USER;
        }

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
    
    public function render()
    {
        return view('guest.auth.reset-password');
    }
}
