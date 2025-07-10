<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #DE6262;
        }
        .content {
            padding: 30px 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #DE6262 0%, #FFB88C 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #eee;
        }
        .note {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <span style="color: #DE6262;">AI</span> Medical Diagnosis
            </div>
        </div>
        
        <div class="content">
            <h2>Reset Your Password</h2>
            
            <p>Hello,</p>
            
            <p>You are receiving this email because we received a password reset request for your account.</p>
            
            <p style="text-align: center;">
                <a href="{{ $url }}" class="button">Reset Password</a>
            </p>
            
            <p>This password reset link will expire in 60 minutes.</p>
            
            <p>If you did not request a password reset, no further action is required.</p>
            
            <div class="note">
                <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
                <p style="word-break: break-all;">{{ $url }}</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} AI Medical Diagnosis. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>