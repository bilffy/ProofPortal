@props(['franchiseName'=>'', 'franchisePhone'=>'', 'franchiseEmail'=>''])

<table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" class="document">
    <tr>
        <td valign="top">
            <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center"
                width="750" class="container">
                <tr>
                    <td bgcolor="#ffffff">
                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0"
                            align="center" width="100%">
                            <tr>
                                <td colspan="2">
                                    {{-- <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_header.png" width="750" style="width: 100%; max-width: 750px; height: auto; display: block; border: 0;"> --}}
                                </td>
                            </tr>
                            {{ $slot }}
                            <tr>
                                <th style="padding: 20px 10px 20px 40px; width: 30%;" class="stack">
                                    <p style="font-size: 14px; color: #666666; line-height: 1.4; text-align: left;">
                                        Regards,
                                        <br/>
                                        <strong>{{ $franchiseName }}</strong>
                                        @if (!empty($franchisePhone))
                                            <br/>
                                            {{ $franchisePhone }}
                                        @endif
                                        <br/><br/>
                                    </p>
                                </th>
                                <th valign="bottom" style="text-align: right; padding: 0px 0px 30px 20px;"
                                    class="stack">
                                    <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_services.png">
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2" bgcolor="#00b3e0" style="padding: 20px 40px 20px 40px;">
                                    <p style="font-size: 12px; color: #ffffff; line-height: 1.3; text-align: center;">
                                        <strong>Please note:</strong> This is an automated email, and the mailbox is
                                        unable to receive replies. We're happy to help you with any questions or
                                        concerns you may have, please contact our team directly:
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" bgcolor="#00b3e0"
                                    style="text-align: center; padding: 0px 0px 20px 0px;">
                                    <a href="mailto:{{ $franchiseEmail }}?subject={{ rawurlencode('MSP Photography Portal Enquiry') }}"><img
                                            src="https://www.msp.com.au/wp-content/uploads/2020/02/msp_op_button_email.png"
                                            width="225"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


