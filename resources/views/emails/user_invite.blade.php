@component('mail::message')
    # Hello {{ $user->name }},

    You've been invited to create an account with us. Please click the button below to set up your account or reset your password.

    @component('mail::button', ['url' => $inviteLink])
        Create Account / Reset Password
    @endcomponent

    If you did not request this, no further action is required.

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent