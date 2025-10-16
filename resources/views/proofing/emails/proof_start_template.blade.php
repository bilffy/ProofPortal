
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
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,700&display=swap" rel="stylesheet">
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

        html, body {
            width: 100% !important;
            height: 100% !important;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', Helvetica, Arial, sans-serif;
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
<div style="display: none; max-height: 0px; overflow: hidden;">
    MSP Photography Online Proofing Commencement Notification
</div>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" class="document">
    <tr>
        <td valign="top">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container">
                <tr>
                    <td bgcolor="#ffffff">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
                            <tr>
                                <td colspan="2">
                                    <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_header.png" alt="Header">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0px 40px 0px 40px">
                                    <p style="font-weight: 700; font-size: 21px; color: #00b3e0;">
                                        Proofing Commencement Notification
                                        <br/>
                                    </p>
                                    <p style="font-size: 12px; color: #6d6e71; line-height: 1.3;">
                                        Hi {INVITEE_FIRST_NAME},
                                        <br/>
                                        <br/>
                                    </p>
                                    <p style="font-size: 12px; color: #6d6e71; line-height: 1.3;">
                                        Proofing of <b>{JOB_NAME}</b> has commenced and must be completed by
                                        <b>{REVIEW_DUE}</b>.
                                        <br/>
                                        <br/>
                                        Please complete your proofs for the following classes:
                                        <br/>
                                        {{#FOLDERS}}
                                        <br/>
                                        <br/>
                                        To start proofing your classes please click here:
                                        <b><a href="{APP_URL}">{APP_URL}</a></b>
                                        <br/>
                                        <br/>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th style="padding: 20px 40px 20px 40px;" class="stack">
                                    <p style="font-size: 12px; color: #6d6e71; line-height: 1.4; text-align: left;">
                                        Regards,
                                        <br/>
                                        <strong>{FRANCHISE_NAME}</strong>
                                        <br/>
                                            {FRANCHISE_PHONE}
                                        <br/>
                                        <a href="mailto:{FRANCHISE_EMAIL}" style="text-decoration: none; color: #6d6e71;">
                                            {FRANCHISE_EMAIL}
                                        </a>
                                        <br/>
                                        <a href="https://{FRANCHISE_WEB_ADDRESS}" style="text-decoration: none; color: #6d6e71;">
                                            {FRANCHISE_WEB_ADDRESS}
                                        </a>
                                    </p>
                                </th>
                                <th valign="bottom" style="text-align: right; padding: 0px 0px 30px 20px;" class="stack">
                                    <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_services.png" alt="Services">
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2" bgcolor="#00b3e0" style="padding: 20px 40px 20px 40px;">
                                    <p style="font-size: 12px; color: #ffffff; line-height: 1.3; text-align: center;">
                                        <strong>Please note:</strong> This is an automated email, and the mailbox is
                                        unable to receive replies. We're happy to help you with any questions or
                                        concerns you may have. Please contact our team directly:
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" bgcolor="#00b3e0" style="text-align: center; padding: 0px;">
                                    <a href="mailto:{FRANCHISE_EMAIL}">
                                        <img src="https://www.msp.com.au/wp-content/uploads/2020/02/msp_op_button_email.png" width="225" alt="Contact Us">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container">
                <tr>
                    <td colspan="2" style="padding: 10px 40px 20px 40px; text-align: center;">
                        <p style="font-size: 12px; color: #6d6e71; line-height: 1.3;">
                            {FRANCHISE_NAME}
                            <br/>
                            {FRANCHISE_ADDRESS1}, {FRANCHISE_SUBURB}, {FRANCHISE_STATE} {FRANCHISE_POSTCODE}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
