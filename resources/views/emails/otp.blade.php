@component('mail::message')
    # Hello {{ $user->name }},

    Your OTP is: {{ $otp  }}

    If you did not request this, no further action is required.

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent