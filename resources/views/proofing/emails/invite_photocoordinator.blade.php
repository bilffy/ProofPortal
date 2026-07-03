<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--[if !mso]>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <![endif]-->
    <meta name="x-apple-disable-message-reformatting">
    <title></title>
    <!--[if mso]>
    <style>
        * {
            font-family: sans-serif !important;
        }
    </style>
    <![endif]-->
    <!-- Google Fonts for non-Outlook clients -->
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700&display=swap" rel="stylesheet">
    <!--<![endif]-->
    <style>
        * {
            box-sizing: border-box;
        }
        html, body, table, td, p, a {
            font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;
        }
        /* Add your styles here */
    </style>
</head>
<body bgcolor="#e8ecf1">
<!-- <div style="display: none; max-height: 0px; overflow: hidden;">
    Welcome to MSP Portal Proofing
</div> -->
<!-- bcn -->
<table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" class="document">
    <tr>
        <td valign="top">
            <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container">
                <tr>
                    <td bgcolor="#ffffff">
                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
                            <tr>
                                <td colspan="2">
                                    <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_header.png">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0px 40px 0px 40px">
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 21px; color: #00b3e0;">
                                        Welcome to MSP Portal Proofing
                                    </p>
                                    <br/>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        Hi <span style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;">INVITEE_FIRST_NAME</span>,
                                    </p>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        <strong>SENDER_FIRST_NAME SENDER_LAST_NAME</strong> from FRANCHISE_NAME
                                        has assigned you to use the MSP Portal Proofing system to manage the
                                        distribution and collection of proofs for <strong style="font-weight: 700;">JOB_NAME</strong>.
                                    </p>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        You have been allocated to proof the following Folders:
                                        <br/><br/>
                                        @foreach($FOLDERS as $FOLDER)
                                            <strong style="font-weight: 700;">- FOLDER_NAME</strong><br/>
                                        @endforeach
                                    </p>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        Please note that these proofs must be completed by <strong style="font-weight: 700;">REVIEW_DUE</strong>.
                                    </p>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        MSP Portal Proofing allows you to assign Photo Co-ordinators and
                                        Teachers to review their proofs and monitor each school's progress to ensure
                                        they meet the due date.
                                    </p>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 14px; color: #00b3e0; line-height: 1.4;">
                                        To get started, click the button to setup your account:
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
                                    <a href=""><img
                                            src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_button_setup.png"
                                            width="225"></a>
                                </td>
                            </tr>

                            @if ($FRANCHISE_NAME === 'MSP Sydney West')
                                <tr>
                                    <td colspan="2" style="text-align: center; padding: 30px 0px 0px 0px;">
                                        <strong style="font-weight: 700;">Download Instructions for</strong><br>
                                        <a href="https://www.msp.com.au/wp-content/uploads/blueprint/Online%20Proofing%20Guide%20Photo%20Coordinators.pdf"
                                           target="_blank">
                                            <img src="https://www.msp.com.au/wp-content/uploads/2019/10/photoco_btn.png"
                                                 width="225">
                                        </a>
                                    </td>
                                </tr>
                            @endif

                            <tr>
                                <th width="30%" style="padding: 20px 40px 20px 40px;" class="stack">
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; font-weight: 700; color: #666666; line-height: 1.4; text-align: left;">
                                        Regards,
                                        <br/>
                                        {FRANCHISE_NAME}
                                        <br/>
                                        <a href="mailto:FRANCHISE_EMAIL?subject=MSP%20Photography%20Online%20Proofing%20Enquiry"
                                           style="text-decoration: none; color: #666666; font-weight: 700;">FRANCHISE_EMAIL</a>
                                        <br/>
                                        <a href="https://FRANCHISE_WEB_ADDRESS"
                                           style="text-decoration: none; color: #666666; font-weight: 700;">FRANCHISE_WEB_ADDRESS</a>
                                    </p>
                                </th>
                                <th valign="bottom" style="text-align: right; padding: 0px 0px 30px 20px;" class="stack">
                                    <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_services.png">
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2" bgcolor="#00b3e0" style="padding: 20px 40px 20px 40px; text-align: center;">
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #ffffff; line-height: 1.4; text-align: center; margin: 0 0 15px 0;">
                                         <strong>Please note:</strong> This is an automated email, and the mailbox is
                                         unable to receive replies. We're happy to help you with any questions or
                                         concerns you may have, please contact your <a href="https://www.msp.com.au/find-your-local-expert/" style="color: #ffffff; text-decoration: underline; font-weight: 700;">local MSP expert</a>:
                                     </p>
                                    <a href="mailto:FRANCHISE_EMAIL?subject=MSP%20Photography%20Online%20Proofing%20Enquiry"><img
                                            src="https://www.msp.com.au/wp-content/uploads/2020/02/msp_op_button_email.png"
                                            width="225" style="display: block; margin: 0 auto; border: 0;"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container">
                <tr>
                    <td colspan="2" style="padding: 10px 40px 20px 40px; text-align: center;">
                        <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 12px; color: #666666; line-height: 1.4;">
                            MSP Photography Pty Ltd
                            <br/>
                            Copyright ⓒ 2026 MSP Photography. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>


