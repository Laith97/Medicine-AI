<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your MedCura AI Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f8fa;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 0;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .header {
            text-align: center;
            padding: 30px 0;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .logo-accent {
            color: #DE6262;
        }
        .content {
            padding: 40px 30px;
        }
        .title {
            color: #2c3e50;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #DE6262 0%, #c44a4a 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(222, 98, 98, 0.3);
            transition: all 0.3s ease;
        }
        .button:hover {
            background: linear-gradient(135deg, #c44a4a 0%, #b03c3c 100%);
            box-shadow: 0 6px 15px rgba(222, 98, 98, 0.4);
        }
        .footer {
            text-align: center;
            padding: 25px 20px;
            color: #7f8c8d;
            font-size: 13px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }
        .note {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 14px;
            color: #7f8c8d;
            border-left: 4px solid #DE6262;
        }
        .expiry-notice {
            font-size: 14px;
            color: #e74c3c;
            text-align: center;
            margin: 20px 0;
        }
        p {
            margin: 16px 0;
            color: #34495e;
        }
        .social-links {
            margin-top: 15px;
        }
        .social-link {
            display: inline-block;
            margin: 0 8px;
            color: #DE6262;
            text-decoration: none;
        }
        @media only screen and (max-width: 620px) {
            .container {
                width: 100%;
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <span class="logo-accent">Med</span>Cura AI
            </div>
        </div>
        
        <div class="content">
            <h2 class="title">Reset Your Password</h2>
            
            <p>Hello,</p>
            
            <p>We received a request to reset your password for your MedCura AI account. To proceed with the password reset, please click the button below:</p>
            
            <div class="button-container">
                <a href="{{ $url }}" class="button">Reset Password</a>
            </div>
            
            <p class="expiry-notice">This password reset link will expire in 60 minutes.</p>
            
            <p>If you did not request a password reset, please ignore this email or contact our support team if you have concerns about your account security.</p>
            
            <div class="note">
                <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
                <p style="word-break: break-all; font-size: 13px; color: #DE6262;">{{ $url }}</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} MedCura AI. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
            <div class="social-links">
                <a href="#" class="social-link">Privacy Policy</a> | 
                <a href="#" class="social-link">Terms of Service</a> | 
                <a href="#" class="social-link">Contact Us</a>
            </div>
        </div>
    </div>
</body>
</html>