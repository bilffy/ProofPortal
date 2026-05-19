@component('mail::message', ['franchise' => $franchise])
<div style="display: none; max-height: 0px; overflow: hidden;">
Your MSP Portal Security Code
</div>
<x-email-wrapper :franchiseName="$franchise->getBusinessName()" :franchisePhone="$franchise->phone" :franchiseEmail="$franchise->email">
<tr>
<td colspan="2" style="padding: 0px 40px 0px 40px">
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 21px; color: #00b3e0;">
Your MSP Portal Security Code
<br/>
</p>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 500; font-size: 14px; color: #444444; line-height: 1.5;">
Hi <span style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700;">{{ $user->firstname }}</span>,
<br/>
<br/>
To finish logging in to your MSP Portal account, please enter this security code:
<br/>
<br/>
</p>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; letter-spacing: 2px; font-size: 28px; line-height: 1.3; color: #444444;">
<strong style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;">{{ $otp }}</strong></p>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #7f8184; line-height: 1.3;">
<br/>
This code will expire in {{ $expiration }} minutes. 
</p>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 400; font-size: 14px; color: #8a8a8a; line-height: 1.3;">
<br/>
<em style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;">Code expired? Click Resend Code on the Security Code screen, or start the login process again. You will receive a new email containing a new security code.</em>
<br/>
<br/>
</p>
</td>
</tr>
</x-email-wrapper>
@endcomponent
