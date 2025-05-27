<?php

namespace App\Http\Livewire\Auth;

use App\Models\User;
use App\Models\Status;
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
class ResetPassword extends Component
{
    public $email;
    public $token;
    public $password;
    public $password_confirmation;
    
    protected $rules = [
        'password' => ['required', 'confirmed', 'min:13', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        'password_confirmation' => 'required',
    ];

    public function mount($token)
    {
        $this->email = request()->email;
        $this->token = $token;
        
        // Check if the user exists otherwise throw an exception
        User::where('email', $this->email)->firstOrFail();
        
        $this->password = '';
        
        // Retrieve the token creation date from the `password_reset_tokens` table
        $tokenData = DB::table('password_reset_tokens')->where('email', $this->email)->first();

        $resetExpireMinutes = config('auth.passwords.users.expire');

        // Check if the token is older than the given days
        if (!$tokenData || Carbon::parse($tokenData->created_at)->addMinutes((int)$resetExpireMinutes)->isPast()) {
            $this->linkExpired = true;
            return;
        }
    }

    public function submit()
    {
        $this->validate();
        
        // Check if the user exists otherwise throw an exception
        $user = User::where('email', $this->email)->firstOrFail();

        $status = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'password_confirmation' => $this->password, 'token' => $this->token],
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60)
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            // Update the user status to active if the user is new or invited
            if ($user->status == User::STATUS_NEW || $user->status == User::STATUS_INVITED) {
                $user->status = User::STATUS_ACTIVE;

                $status = Status::where('status_external_name', 'active')->first();
                $user->active_status_id = $status->id;
                
                $user->save();
            }
            return redirect()->route('login')->with('status', 'Password updated, please sign in');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function render()
    {
        if (isset($this->linkExpired) && $this->linkExpired) {
            return view('guest.auth.reset-password-expired');
        }
        
        return view('guest.auth.reset-password');
    }
}