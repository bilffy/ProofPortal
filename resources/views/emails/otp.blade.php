@component('mail::message', ['franchise' => $franchise])
    <div style="display: none; max-height: 0px; overflow: hidden;">
        Your MSP Portal Security Code
    </div>
    <x-email-wrapper :franchiseName="$franchise->getBusinessName()" :franchisePhone="$franchise->phone" :franchiseEmail="$franchise->email">
        <tr>
            <td colspan="2" style="padding: 0px 40px 0px 40px">
                <p style="font-weight: 700; font-size: 21px; color: #00b3e0;">
                    Your MSP Portal Security Code
                    <br/>
                </p>
                <p style="font-size: 14px; color: #666666; line-height: 1.3;">
                    Hi {{ $user->firstname }},
                    <br/>
                    <br/>
                    To finish logging in to your MSP Portal account, please enter this security code:
                    <br/>
                    <br/>
                </p>
                <p style="letter-spacing: 2px; font-size: 24px; line-height: 1.3;">
                    <strong>{{ $otp }}</strong></p>
                    <p style="font-size: 14px; color: #666666; line-height: 1.3;">
                    <br/>
                    This code will expire in {{ $expiration }} minutes. Do not share it with anyone. 
                </p>
                <p style="font-weight: 400; font-size: 14px; color: #8a8a8a; line-height: 1.3;">
                    <br/>
                    <em>Code expired? Click Resend Code on the Security Code screen, or start the login process again. You will receive a new email containing a new security code.</em>
                    <br/>
                    <br/>
                </p>
            </td>
        </tr>
    </x-email-wrapper>
@endcomponent
