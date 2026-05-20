@component('mail::message', ['franchise' => $franchise])
<div style="display: none; max-height: 0px; overflow: hidden;">
Invitation to MSP Portal
</div>
<x-email-wrapper :franchiseName="$franchise->name" :franchisePhone="$franchise->phone" :franchiseEmail="$franchise->email">
<tr>
<td colspan="2" style="padding: 0px 40px 0px 40px">
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 21px; color: #00b3e0;">
Welcome to the MSP Portal
<br/>
</p>
@php
    $userSchoolName = '';
    if ($user->isSchoolLevel()) {
        $school = $user->getSchool();
        $userSchoolName = $school ? $school->name : '';
    }
@endphp
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #808080; line-height: 1.4;">
Hi <span style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700;">{{ $user->firstname }}</span>,
<br/><br/>
@if($userSchoolName)
{{ $sender->name }} from the <strong>{{ $senderOrgName }}</strong> has invited you to access the <strong>MSP&nbsp;Portal</strong> as a {{ $userRole }} for <strong>{{ $userSchoolName }}</strong>.
@else
{{ $sender->name }} from the <strong>{{ $senderOrgName }}</strong> has invited you to access the <strong>MSP&nbsp;Portal</strong> as a member of {{ $userRole }}.
@endif
</p>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 14px; color: #00b3e0; line-height: 1.4;">
To get started, click the button to setup your account:
</p>
</td>
</tr>
<tr>
<td colspan="2" style="text-align: center;">
@component('mail::button', ['url' => $inviteLink])
<img
src="https://www.msp.com.au/wp-content/uploads/2025/02/msp_portal_email_setup_button.png"
width="225">
@endcomponent
</td>
</tr>
<tr>
<td colspan="2" style="padding: 0px 40px 0px 40px">
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #808080; line-height: 1.4;">
During the setup process, you'll confirm your name, create a password and then be directed to the login page.
<br/><br/>
This invitation expires after 14 days.
</p>
</td>
</tr>
</x-email-wrapper>
@endcomponent
