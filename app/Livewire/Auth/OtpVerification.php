<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use App\Helpers\OTPHelper;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class OtpVerification extends Component
{
    public $otp;
    public $email;
    public $message;
    public $token;

    protected $rules = [
        'otp' => 'required',
        'email' => 'required|email',
    ];

    public function mount()
    {
        $this->email = session('msp-user', request()->email);
        $this->message = session()->has('resend-otp')
            ? 'OTP has been resent to your email.'
            : 'A Verification Code has been sent to your email address.';
        $this->token = request()->route('token');
    }

    public function submit(UserService $userService)
    {
        $this->validate();

        // OTP verification logic
        $user = User::where('email', $this->email)->firstOrFail();
        $recentOtp = $userService->getRecentOtp($user);

        if ($recentOtp === null) {
            $this->message = 'OTP is Invalid!';
            throw ValidationException::withMessages([
                'otp' => [$this->message],
            ]);
        }

        $decryptOtp = OTPHelper::decryptOtp($recentOtp->otp);

        if ($decryptOtp !== (int)$this->otp) {
            $this->message = 'OTP is Invalid!';
            throw ValidationException::withMessages([
                'otp' => [$this->message],
            ]);
        }

        if (Carbon::parse($recentOtp->expire_on)->isPast()) {
            $this->message = 'OTP has expired!';
            throw ValidationException::withMessages([
                'otp' => [$this->message],
            ]);
        }

        // Set the OTP as expired
        $recentOtp->expire_on = now();
        $recentOtp->save();

        // Reset the msp-user session key
        session()->forget('msp-user');

        Auth::loginUsingId($recentOtp->user->id);
        session()->flash('message', 'OTP verified successfully.');
        return redirect()->route('dashboard');
    }
    public function resendOtp(UserService $userService, $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('login');
        }
        
        // Send OTP to the user
        $userService->sendOtp($user);

        // Update the message
        $this->message = 'OTP has been resent to your email.';
        $this->resetErrorBag();
    }
    
    public function render()
    {
        return view('guest.auth.otp-verification');
    }
}
