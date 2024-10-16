<?php

namespace App\Http\Livewire\Auth;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class AccountSetup extends Component
{
    public $email;
    public $token;
    public $password;
    public $password_confirmation;
    public $firstName;
    public $lastName;

    protected $rules = [
        'password' => ['required', 'confirmed', 'min:12', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        'firstName' => 'required',
        'lastName' => 'required',
        'password_confirmation' => 'required',
    ];

    public function mount($token, $email)
    {
        $this->email = $email;
        $this->token = $token;
        
        $user = User::where('email', $this->email)->firstOrFail();
        
        // Check if the user has already completed the setup
        if ($user->is_setup_complete) {
            $this->linkExpired = true;
            return;
        }

        $this->firstName = $user->firstname;
        $this->lastName = $user->lastname;
        $this->password = '';
        
        // Retrieve the token creation date from the `password_reset_tokens` table
        $tokenData = DB::table('password_reset_tokens')->where('email', $this->email)->first();

        $inviteExpireDays = config('app.invite.expiration_days');

        // Check if the token is older than the given days
        if (!$tokenData || Carbon::parse($tokenData->created_at)->addDays((int)$inviteExpireDays)->isPast()) {
            $this->linkExpired = true;
            return;
        }
    }

    public function submit()
    {
        $this->validate();
        
        // Check if the user exists otherwise throw an exception
        User::where('email', $this->email)->firstOrFail();

        $status = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'password_confirmation' => $this->password, 'token' => $this->token],
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'firstname' => $this->firstName,
                    'lastname' => $this->lastName,
                    'remember_token' => Str::random(60),
                    'is_setup_complete' => true,
                    'status' => User::STATUS_ACTIVE,
                ])->save();

                // Send OTP to the user
                app(UserService::class)->sendOtp($user);

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('otp.show.form', ['token' => $this->token])->with('msp-user', $this->email);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function render()
    {
        if (isset($this->linkExpired) && $this->linkExpired) {
            return view('guest.auth.invite-link-expired');
        }
        
        return view('guest.auth.account-setup');
    }
}