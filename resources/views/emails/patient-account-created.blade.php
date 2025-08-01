<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Medical Account Has Been Created</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #DE6262;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .credentials-box {
            background-color: #fff;
            border: 2px solid #DE6262;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .login-button {
            display: inline-block;
            background-color: #DE6262;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .diagnosis-info {
            background-color: #e8f4f8;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 Medical Account Created</h1>
        <p>Your healthcare journey starts here</p>
    </div>

    <div class="content">
        <h2>Hello {{ $patient->name }},</h2>

        <p>Great news! Dr. {{ $doctor->name }} has created a medical account for you and provided a diagnosis. You can now access your medical information online.</p>

        <div class="credentials-box">
            <h3>🔐 Your Login Credentials</h3>
            <p><strong>Email:</strong> {{ $patient->email }}</p>
            <p><strong>Temporary Password:</strong> <code style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 3px; font-size: 16px; font-weight: bold;">{{ $tempPassword }}</code></p>
            <p style="color: #666; font-size: 14px;">Please change your password after your first login for security.</p>
        </div>

        <div class="diagnosis-info">
            <h3>📋 Your Diagnosis Information</h3>
            <p><strong>Doctor:</strong> Dr. {{ $doctor->name }}</p>
            <p><strong>Date:</strong> {{ $diagnosis->created_at->format('F j, Y \a\t g:i A') }}</p>
            <p><strong>Type:</strong> {{ ucfirst($diagnosis->type) }} Diagnosis</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="login-button">🚀 Login to View Your Diagnosis</a>
        </div>

        <h3>What you can do with your account:</h3>
        <ul>
            <li>✅ View your complete diagnosis</li>
            <li>✅ Ask up to 5 follow-up questions using AI</li>
            <li>✅ Rate and review your doctor's diagnosis</li>
            <li>✅ Access your medical history</li>
            <li>✅ Receive future diagnoses from any doctor</li>
        </ul>

        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
            <h4>🔒 Security Notice</h4>
            <p>For your security, please:</p>
            <ul>
                <li>Change your password after first login</li>
                <li>Keep your login credentials confidential</li>
                <li>Log out after each session</li>
            </ul>
        </div>

        <p>If you have any questions or need assistance, please contact Dr. {{ $doctor->name }} or our support team.</p>

        <p>Best regards,<br>
        <strong>Medical AI Assistant Team</strong></p>
    </div>

    <div class="footer">
        <p>This email was sent because Dr. {{ $doctor->name }} created a medical account for you.</p>
        <p>If you believe this was sent in error, please contact us immediately.</p>
    </div>
</body>
</html>
