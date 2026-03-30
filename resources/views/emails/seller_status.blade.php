<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $message_title }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 520px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;">
                    
                    <!-- Header Banner -->
                    <tr>
                        <td style="background-color: {{ $status_color }}; padding: 30px; text-align: center;">
                            <div style="display: inline-block; background-color: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%;">
                                @if($status === 'approved')
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                @else
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0; color: #1e293b; font-size: 26px; font-weight: 800; letter-spacing: -0.03em; line-height: 1.2;">
                                {{ $message_title }}
                            </h2>
                            
                            <p style="margin: 20px 0 0 0; color: #475569; font-size: 16px; line-height: 1.6; font-weight: 500;">
                                Hello {{ $seller->name }},
                            </p>
                            
                            <p style="margin: 12px 0 0 0; color: #64748b; font-size: 15px; line-height: 1.6;">
                                {{ $message_content }}
                            </p>

                            @if($status === 'approved')
                                <div style="margin-top: 32px; padding: 20px; background-color: #f0fdf4; border-radius: 12px; border: 1px solid #bbf7d0;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="color: #166534; font-size: 14px; line-height: 1.5;">
                                                <strong>Next Step:</strong> You can now access your owner dashboard to set up your store profile and start listing products.
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @endif
                        </td>
                    </tr>

                    <!-- Action -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            @if($status === 'approved')
                                <a href="{{ env('FRONTEND_URL') }}/auth/login" style="display: block; text-align: center; background-color: {{ $status_color }}; color: #ffffff; padding: 16px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 16px; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2);">
                                    Access Owner Dashboard
                                </a>
                            @else
                                <a href="mailto:support@saby-tinh.com" style="display: block; text-align: center; background-color: #f1f5f9; color: #475569; padding: 16px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 16px; border: 1px solid #e2e8f0;">
                                    Contact Support
                                </a>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #f1f5f9;">
                            <p style="margin: 0; font-size: 13px; color: #94a3b8; font-weight: 500;">
                                Thank you for choosing <strong>Saby-tinh</strong>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
