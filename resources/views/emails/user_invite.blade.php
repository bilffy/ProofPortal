@component('mail::message')
# Hello {{ $user->name }},

You've been invited to create an account with us. Please click the button below to set up your your password.

@component('mail::button', ['url' => $inviteLink])
    Setup Password
@endcomponent

If you did not request this, no further action is required.

Regards,

**MSP Team**

@endcomponent