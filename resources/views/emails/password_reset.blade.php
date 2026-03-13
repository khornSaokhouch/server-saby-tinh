<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; background-color: #f8fafc; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; border: 1px solid #e2e8f0; }
        h3 { color: #0f172a; margin-top: 0; }
        p { color: #475569; line-height: 1.6; font-size: 14px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .footer { margin-top: 30px; font-size: 13px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <h3>Hi {{ $userName }},</h3>
        <p>You recently made a request to reset your Saby-Tinh account password. Please click on the button below to continue.</p>
        
        <a href="{{ $resetUrl }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0;">Reset Password</a>
        
        <p>Team Saby-Tinh</p>
        
        <div class="footer">
            P.S We are always just an email away. Feel free to reply to this email if you have any questions or want to say hello.
        </div>
    </div>
</body>
</html>
