<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

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
        'password_confirmation' => 'required',
    ];

    public function mount($token, $email)
    {
        $this->email = $email;
        $this->token = $token;
        
        $user = User::where('email', $this->email)->firstOrFail();
        
        // Check if the user has already completed the setup
        if ($user->is_setup_complete) {
            return redirect()->route('invite.expired');
        }

        $this->firstName = $user->firstname;
        $this->lastName = $user->lastname;
        $this->password = '';
        
        // Retrieve the token creation date from the `password_reset_tokens` table
        $tokenData = DB::table('password_reset_tokens')->where('email', $this->email)->first();

        $inviteExpireDays = config('app.invite.expiration_days');

        // Check if the token is older than the given days
        if (!$tokenData || Carbon::parse($tokenData->created_at)->addDays((int)$inviteExpireDays)->isPast()) {
            return redirect()->route('invite.expired');
        }
    }

    public function submit()
    {
        $this->validate();

        $user = User::where('email', $this->email)->firstOrFail();

        $status = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'password_confirmation' => $this->password, 'token' => $this->token],
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
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
        return view('guest.account-setup');
    }
}