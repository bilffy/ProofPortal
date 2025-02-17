@component('mail::message')
# Hello {{ $user->name }},

Your security code is: {{ $otp  }}

Please note that this code will expire in {{ $expiration }} minutes. 

If you did not request this code, you can safely ignore this message.

Regards,

**MSP Photography**
@endcomponent