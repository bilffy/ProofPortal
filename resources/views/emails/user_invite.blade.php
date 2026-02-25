@component('mail::message', ['franchise' => $franchise])
    <div style="display: none; max-height: 0px; overflow: hidden;">
        Invitation to MSP Photography Portal
    </div>
    <x-email-wrapper :franchiseName="$franchise->getBusinessName()" :franchisePhone="$franchise->phone" :franchiseEmail="$franchise->email">
        <tr>
            <td colspan="2" style="padding: 0px 40px 0px 40px">
                <p style="font-weight: 700; font-size: 21px; color: #00b3e0;">
                    Welcome to the MSP Photography Portal
                    <br/>
                </p>
                <p style="font-size: 14px; color: #6d6e71; line-height: 1.3;">
                    Hi {{ $user->firstname }},
                    <br/><br/>
                    {{ $sender->name }} from {{ $senderOrgName }}
                    has invited you to access the MSP&nbsp;Photography&nbsp;Portal for <b>{{ $userOrgName }}</b>.
                    </p>
                </p>
                <p style="font-weight: 700; font-size: 14px; color: #00b3e0; line-height: 1.3;">
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
                <p style="font-size: 14px; color: #6d6e71; line-height: 1.3;">
                    During the setup process, you'll confirm your name, create a password and then be directed to the login page.
                    <br/><br/>
                    This single-use invitation expires after 14 days.
                </p>
            </td>
        </tr>
    </x-email-wrapper>
@endcomponent
