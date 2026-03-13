<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Outer Container -->
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 480px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 0 40px; text-align: center;">
                            <div style="margin-bottom: 24px;">
                                <div style="display: inline-block; background-color: #4f46e5; padding: 12px; border-radius: 12px;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                </div>
                            </div>
                            <h2 style="margin: 0; color: #1e293b; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;">Verify your purchase</h2>
                            <p style="margin: 12px 0 0 0; color: #64748b; font-size: 15px; line-height: 1.5;">
                                Please use the verification code below to authorize your transaction on <strong>Saby-tinh</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- OTP Code Box -->
                    <tr>
                        <td style="padding: 32px 40px 24px 40px; text-align: center;">
                            <div style="background-color: #f1f5f9; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0;">
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 700; color: #4f46e5; letter-spacing: 6px; text-indent: 6px;">
                                    {{ $otp }}
                                </div>
                            </div>
                            <p style="margin: 12px 0 0 0; font-size: 12px; color: #94a3b8; font-weight: 500;">
                                Tip: Long-press the code to copy it.
                            </p>
                        </td>
                    </tr>

                    <!-- Action Button -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">

                            <p style="margin: 24px 0 0 0; font-size: 13px; color: #64748b;">
                                Code expires in <span style="color: #ef4444; font-weight: 600;">5 minutes</span>.
                            </p>
                        </td>
                    </tr>

                    <!-- Bottom Bar -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #f1f5f9;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                                If you didn't request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 480px; margin-top: 24px;">
                    <tr>
                        <td align="center" style="font-size: 12px; color: #94a3b8;">
                            &copy; {{ date('Y') }} <strong>Saby-tinh</strong>. Modern Components for Builders.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>