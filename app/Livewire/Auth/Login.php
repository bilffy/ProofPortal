<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Str;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    
    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function submit(UserService $userService)
    {
        $this->validate();

        // Validate the user credentials, if valid, send OTP to the user
        // without authenticating the user
        if (Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            /** @var User $user */
            $user = User::where('email', $this->email)->firstOrFail();
            // Send OTP to the user
            $userService->sendOtp($user);
            return redirect()->route(
                'otp.show.form',
                ['token' => Str::random(60)])
                ->with('msp-user', $this->email);
        }

        throw ValidationException::withMessages([
            'email' => ['Invalid username/password.'],
        ]);
    }
    
    public function render()
    {
        return view('guest.auth.login');
    }
}
