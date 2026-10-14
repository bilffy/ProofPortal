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
        MSP Portal Proofing - Change in Folder Status
    </div> -->
    <!-- bcn -->
    <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" class="document">
        <tr>
            <td valign="top">
                <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container">
                    <tr>
                        <td bgcolor="#ffffff">
                            <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" align="center" width="750" class="container" style="border-collapse: collapse;">
                                <tr>
                                    <td colspan="2">
                                        <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_header.png" width="750" alt="MSP Photography" style="display: block; width: 100%; max-width: 750px; height: auto;">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding: 28px 40px 10px 40px;">
                                        <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-weight: 700; font-size: 22px; color: #00b3e0; margin: 0;">
                                            {HEADING_TEXT}
                                        </p>
                                        {ORDER_NUMBER_LINE_HTML}

                                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 18px 0 20px 0;">
                                            <tr>
                                                <td style="border-top: 1px solid #eef2f6; font-size: 0; line-height: 0;">&nbsp;</td>
                                            </tr>
                                        </table>

                                        <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.5; margin: 0 0 20px 0;">
                                            Hi <span style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important;">{RECIPIENT_NAME}</span>,
                                            <br/><br/>
                                            {INTRO_TEXT}
                                        </p>

                                        <!-- Order Details -->
                                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 18px 0;">
                                            <tr>
                                                <td>
                                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #00b3e0; margin: 0 0 10px 0;">
                                                        Order Details
                                                    </p>
                                                    <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" width="100%" style="table-layout: fixed;">
                                                        <tr>
                                                            <td style="padding: 10px 12px 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #5b6b82; width: 190px; min-width: 190px; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;"><strong>Franchise Name</strong></td>
                                                            <td style="padding: 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #1f2937; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;">{FRANCHISE_NAME}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 10px 12px 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #5b6b82; width: 190px; min-width: 190px; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;"><strong>Contact Name</strong></td>
                                                            <td style="padding: 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #1f2937; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;">{SUBMITTED_BY}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 10px 12px 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #5b6b82; width: 190px; min-width: 190px; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;"><strong>Contact Number</strong></td>
                                                            <td style="padding: 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #1f2937; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;">{SUBMITTED_CONTACT}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 10px 12px 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #5b6b82; width: 190px; min-width: 190px; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;"><strong>E-mail</strong></td>
                                                            <td style="padding: 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #1f2937; vertical-align: top; line-height: 1.5; word-wrap: break-word; border-bottom: 1px solid #e7ecf2;">{SUBMITTED_EMAIL}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 10px 12px 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #5b6b82; width: 190px; min-width: 190px; vertical-align: top; line-height: 1.5; word-wrap: break-word;"><strong>School Name</strong></td>
                                                            <td style="padding: 10px 0; font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 13px; color: #1f2937; vertical-align: top; line-height: 1.5; word-wrap: break-word;">{SCHOOL_NAME}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Product Details (what was ordered, plus that product's own fields) -->
                                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 18px 0;">
                                            <tr>
                                                <td>
                                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #00b3e0; margin: 0 0 10px 0;">
                                                        Product Details
                                                    </p>
                                                    {ORDER_FIELDS_HTML}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Artwork Details -->
                                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 18px 0;">
                                            <tr>
                                                <td>
                                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #00b3e0; margin: 0 0 10px 0;">
                                                        Artwork Details
                                                    </p>
                                                    {ARTWORK_DETAILS_HTML}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Uploaded Files -->
                                        <table role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0 0 6px 0;">
                                            <tr>
                                                <td>
                                                    <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #00b3e0; margin: 0 0 10px 0;">
                                                        Uploaded Files
                                                    </p>
                                                    {ARTWORK_DOWNLOAD_BUTTON_HTML}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <!-- <tr>
                                    <th valign="bottom" style="text-align: right; padding: 0px 0px 30px 20px;" class="stack">
                                        <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_services.png" width="730" alt="MSP Photography services" style="display: block; width: 100%; max-width: 730px; height: auto;">
                                    </th>
                                </tr> -->
                                <tr>
                                    <th width="30%" style="padding: 20px 40px 20px 40px;" class="stack">
                                        <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; font-weight: 700; color: #666666; line-height: 1.4; text-align: left;">
                                            {SIGNATURE_HTML}
                                        </p>
                                    </th>
                                    <th valign="bottom" style="text-align: right; padding: 0px 0px 30px 20px;" class="stack">
                                        <img src="https://www.msp.com.au/wp-content/uploads/2019/10/msp_op_services.png" width="505" alt="MSP Photography services" style="display: block; width: 100%; max-width: 505px; height: auto;">
                                    </th>
                                </tr>
                                <tr>
                                    <td colspan="2" bgcolor="#00b3e0" style="padding: 20px 40px 20px 40px; text-align: center;">
                                        <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #ffffff; line-height: 1.4; text-align: center; margin: 0 0 15px 0;">
                                            <strong>Please note:</strong> This is an automated email, and the mailbox is unable to receive replies.
                                        </p>
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


