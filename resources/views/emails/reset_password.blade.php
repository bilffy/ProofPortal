@component('mail::message', ['franchise' => $franchise])
<!-- <div style="display: none; max-height: 0px; overflow: hidden;">
Password Reset for MSP Portal
</div> -->
<x-email-wrapper :franchiseName="$franchise->name" :franchisePhone="$franchise->phone" :franchiseEmail="$franchise->email">
<tr>
<td colspan="2" style="padding: 0px 40px 0px 40px">
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 21px; color: #00b3e0;">
Password Reset for MSP Portal
<br/>
</p>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif; font-size: 14px; color: #808080; line-height: 1.3;">
Hi <span style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700;">{{ $firstname }}</span>,
<br/>
<br/>
We received a request to reset your password. Click the link below to set a new one:
</p>
</td>
</tr>
<tr>
<td colspan="2" style="text-align: center;">
@component('mail::button', ['url' => $resetUrl])
<img
src="https://www.msp.com.au/wp-content/uploads/2025/02/msp_portal_email_reset_button.png"
width="225">
@endcomponent
</td>
</tr>
<tr>
<td colspan="2" style="padding: 0px 40px 0px 40px;">
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif; font-size: 14px; color: #808080; line-height: 1.3;">
This password reset link will expire in {{ $expiration }} minutes. 
</p>
</td>
</tr>
</x-email-wrapper>
@endcomponent
