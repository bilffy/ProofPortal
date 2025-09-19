<?php

namespace App\Http\Livewire\Auth;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{   
    public bool $resetLinkStatus = false;
    public $email;
    public $nonce;
    // public $pwNonce;
    
    protected $rules = [
        'email' => [
            'required',
            'string',
            'lowercase',
            'max:255',
            'email:rfc', // Basic check for email format
            'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Ensures a dot + valid TLD
        ],
    ];

    public function mount()
    {
        // Generate a unique nonce for this session
        $this->nonce = Str::random(40);
        // $this->pwNonce = Str::random(16);
    }
    
    public function submit()
    {
        // TODO: Implement cloudflare-friendly decryption for forgot password
        // $this->email = EncryptionHelper::simpleDecrypt($this->email, $this->pwNonce);
        
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if ($user) {

            // Prevent replay attacks by checking if the nonce was already used
            if (session()->has("forgot-password-nonce-{$this->nonce}")) {
                return redirect()->route('login');
                /*throw ValidationException::withMessages([
                    'email' => ['This request has already been processed. Reload the page and try again.'],
                ]);*/
            }
            
            $token = Password::createToken($user);
            $user->notify(new ResetPassword($token, $user));
            $status = Password::RESET_LINK_SENT;
        } else {
            $status = Password::INVALID_USER;

            $key = 'forgot-password:' . Request::ip(); 

            if (RateLimiter::tooManyAttempts($key, 5)) { // 5 attempts per minute per IP
                return redirect()->route('login');
                /*throw ValidationException::withMessages([
                    'email' => ['Too many attempts. Please try again later.'],
                ]);*/
            }

            RateLimiter::hit($key, 60); // Store attempt with 60 seconds expiration
        }

        if ($status == Password::RESET_LINK_SENT) {
            $this->resetLinkStatus = true;
        }

        // Store the nonce to prevent re-use
        session(["forgot-password-nonce-{$this->nonce}" => true]);
        
        throw ValidationException::withMessages([
            'email' => [trans(config('app.dialog_config.invalid_email_forgot_password.message'))],
        ]);
    }
    
    public function render()
    {
        if ($this->resetLinkStatus) {
            return view('guest.auth.reset-password-link-sent');
        }
        
        return view('guest.auth.send-reset-password-link');
    }
}
