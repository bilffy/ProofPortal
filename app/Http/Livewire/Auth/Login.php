<?php

namespace App\Http\Livewire\Auth;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

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

    public function mount()
    {
        $this->email = '';
    }
    
    public function submit(UserService $userService)
    {   
        $this->validate();

        // Validate the user credentials, if valid, send OTP to the user
        // without authenticating the user
        if (Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            /** @var User $user */
            $user = User::where('email', $this->email)->firstOrFail();
            
            //check if OTP is disabled
            if (config('app.otp.disable')) {
                // Log LOGIN activity
                Auth::loginUsingId($user->id);
                ActivityLogHelper::log(LogConstants::LOGIN, []);
                return redirect()->route('dashboard');    
            }
            
            // Send OTP to the user
            $userService->sendOtp($user);
            return redirect()->route(
                'otp.show.form',
                ['token' => Str::random(60)])
                ->with('msp-user', $this->email);
        }
        
        throw ValidationException::withMessages([
            'email' => config('app.dialog_config.invalid_login.message'),
        ]);
    }
    
    public function render()
    {
        return view('guest.auth.login');
    }
}
