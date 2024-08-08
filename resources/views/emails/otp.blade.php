@component('mail::message')
# Hello {{ $user->name }},

Your OTP is: {{ $otp  }}

Please note that this code will expire in {{ $expiration }} minutes. 

If you did not request this code, please disregard this message.

Regards,

**MSP Team**
@endcomponent