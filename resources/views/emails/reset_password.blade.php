@component('mail::message')
# Hi {{ $firstname }}!

Click on the link below to reset your password.

@component('mail::button', ['url' => $resetUrl])
    Reset Password
@endcomponent

This password reset link will expire in {{ $expiration }} minutes.



If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: [{{ $resetUrl }}]({{ $resetUrl }})
@endcomponent