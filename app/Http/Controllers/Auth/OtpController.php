<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\OTPHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOtp;
use App\Services\UserService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    protected UserService $userService;
    
    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    /**
     * Display the account/password setup view.
     */
    public function showForm(Request $request): Response
    {
        $email = $request->session()->get('msp-user');

        return Inertia::render('Auth/Verification', [
            'token' => $request->route('token'),
            'otp' => '',
            'email' => $email
        ]);
    }

    /**
     * Verify the OTP
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verify(Request $request): RedirectResponse
    {   
        $request->validate([
            'otp' => 'required',
            'email' => 'required|email',
        ]);
        
        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();
        
        $recentOtp = $this->userService->getRecentOtp($user);

        if ($recentOtp === null) {
            throw ValidationException::withMessages([
                'otp' => ['OTP is Invalid!'],
            ]);
        }

        $decryptOtp = OTPHelper::decryptOtp($recentOtp->otp);
        
        if ($decryptOtp !== (int)$request->otp) {
            throw ValidationException::withMessages([
                'otp' => ['OTP is Invalid!'],
            ]);
        }

        if (Carbon::parse($recentOtp->expire_on)->isPast()) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired!'],
            ]);
        }

        // Set the OTP as expired
        $recentOtp->expire_on = now();
        $recentOtp->save();
        
        Auth::loginUsingId($recentOtp->user->id);
        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resendOtp(Request $request)
    {
        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();

        // Send OTP to the user
        $this->userService->sendOtp($user);

        return Inertia::render('Auth/Verification', [
            'message' => 'Verification code resent successfully.',
            'otp' => '',
            'email' => $request->email
        ]);
    }
}
