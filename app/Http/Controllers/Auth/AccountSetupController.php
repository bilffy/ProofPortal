<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
use Illuminate\Support\Facades\DB;

class AccountSetupController extends Controller
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
    public function create(Request $request): Response|RedirectResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();

        // Retrieve the token creation date from the `password_reset_tokens` table
        $tokenData = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        
        $inviteExpireDays = config('app.invite.expiration_days');
        
        // Check if the token is older than 14 days
        if (!$tokenData || Carbon::parse($tokenData->created_at)->addDays((int)$inviteExpireDays)->isPast()) {
            return redirect()->route('password.request')
                ->with('status', 'The token is invalid or expired.');
        }
        
        return Inertia::render('Auth/AccountSetup', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'firstName' => $user->firstname,
            'lastName' => $user->lastname,
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {   
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        
        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
                
                // Send OTP to the user
                $this->userService->sendOtp($user);
                
                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route(
                'otp.show.form', 
                ['token' => $request->token])
                ->with('msp-user', $request->email);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
