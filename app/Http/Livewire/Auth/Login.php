<?php

namespace App\Http\Livewire\Auth;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\EncryptionHelper;
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
    // public $nonce;
    
    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function mount()
    {
        $this->email = '';
        // Generate a unique nonce for this session
        // $this->nonce = Str::random(16);
    }
    
    public function submit(UserService $userService)
    {
        // TODO: Implement cloudflare-friendly decryption/encryption for login component
        // Decrypt the user credentials
        // $this->email = EncryptionHelper::simpleDecrypt($this->email, $this->nonce);
        // $this->password = EncryptionHelper::simpleDecrypt($this->password, $this->nonce);
        // Validate
        $this->validate();
        // Transfer data to local variables
        $email = $this->email;
        $password = $this->password;
        // Encrypt the user credentials back
        // $this->email = EncryptionHelper::simpleEncrypt($this->email, $this->nonce);
        // $this->password = EncryptionHelper::simpleEncrypt($this->password, $this->nonce);

        // Validate the user credentials, if valid, send OTP to the user
        // without authenticating the user
        if (Auth::validate(['email' => $email, 'password' => $password])) {
            /** @var User $user */
            $user = User::where('email', $email)->firstOrFail();
            
            // check if user is disabled
            if ($user->disabled) {
                throw ValidationException::withMessages([
                    'email' => config('app.dialog_config.invalid_login.message'),
                ]);
            }
            
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
                ->with('msp-user', $email);
        }
        
        throw ValidationException::withMessages([
            'email' => config('app.dialog_config.invalid_login.message'),
        ]);
    }
    
    public function render()
    {   
        // check with error from the OTP verification page
        /*if (session()->has('error')) {
            $this->addError('email', session('error'));
        }*/
        
        return view('guest.auth.login');
    }
}
