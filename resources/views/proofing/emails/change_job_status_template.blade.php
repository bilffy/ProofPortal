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
    
    <!-- Google Fonts for non-Outlook clients -->
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700&display=swap" rel="stylesheet">
    <!--<![endif]-->

    <style>
        *,
        *:after,
        *:before {
            box-sizing: border-box;
        }

        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        html, body, table, td, p, a {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;
            font-weight: 300;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
            table-layout: auto;
            margin: 0 auto;
        }

        img {
            -ms-interpolation-mode: bicubic;
            max-width: 100%;
            border: 0;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .btn {
            transition: all 200ms ease;
        }

        .btn:hover {
            background-color: dodgerblue;
        }

        /* Styles for mobile devices */
        @media screen and (max-width: 750px) {
            .container {
                width: 100%;
                margin: auto;
            }

            .stack {
                display: block;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body bgcolor="#e8ecf1">
<!-- <div style="display: none; max-height: 0px; overflow: hidden;">
    MSP Portal Proofing - Change in School Status
</div> -->
<!-- bcn -->
<table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center"
       class="document">
    <tr>
        <td valign="top">
            <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center"
                   width="750" class="container">
                <tr>
                    <td bgcolor="#ffffff">

                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container" style="border-collapse: collapse;">
                            <tr>
                                <td colspan="2">
                                    <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_header.png">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0px 40px 0px 40px">
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 21px; color: #00b3e0;">
                                        MSP Portal Proofing school status change notiifcation
                                    </p>
                                    <br/>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        Hi <span style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;">{INVITEE_FIRST_NAME}</span>,
                                        <br/>
                                        <br/>
                                    </p>
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4;">
                                        The status of School <strong style="font-weight: 700;">{JOB_NAME}</strong> has been changed.<br/>
                                        The status has changed to <strong style="font-weight: 700;">{JOB_STATUS_NAME}</strong>.
                                        <br/>
                                        <br/>
                                        The following Folders are part of the School:
                                        <br/>
                                        {#ALLFOLDERS}
                                        <br/>
                                        <br/>
                                        To review these changes, please visit
                                        <strong style="font-weight: 700;"><a href="{APP_URL}">{APP_URL}</a></strong>
                                        <br/>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th width="30%" style="padding: 20px 40px 20px 40px;" class="stack">
                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; font-weight: 700; color: #666666; line-height: 1.4; text-align: left;">
                                        Regards,
                                        <br/>
                                        {FRANCHISE_NAME}
                                        <br/>
                                        <a href="mailto:{FRANCHISE_EMAIL}?subject=MSP%20Photography%20Online%20Proofing%20Enquiry"
                                           style="text-decoration: none; color: #666666; font-weight: 700;">{FRANCHISE_EMAIL}</a>
                                        <br/>
                                        <a href="https://{FRANCHISE_WEB_ADDRESS}"
                                           style="text-decoration: none; color: #666666; font-weight: 700;">{FRANCHISE_WEB_ADDRESS}</a>
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
                                    <a href="mailto:{FRANCHISE_EMAIL}?subject=MSP%20Photography%20Online%20Proofing%20Enquiry">
                                        <img src="https://www.msp.com.au/wp-content/uploads/2020/02/msp_op_button_email.png"
                                             width="225" style="display: block; margin: 0 auto; border: 0;">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center"
                   width="750" class="container">
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


