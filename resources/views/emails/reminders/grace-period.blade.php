<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Grace Period - Action Required</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Grace Period</h1>
        <p>Action Required - Your Account Status</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }},</h2>
        
        <div class="alert">
            <strong>Important:</strong> Your subscription payment is overdue, but you're currently in a grace period.
        </div>
        
        <p>We wanted to let you know that your MedCura AI subscription payment was due on <strong>{{ $setting->subscription_ends_at ? $setting->subscription_ends_at->format('M d, Y') : 'N/A' }}</strong>, but your account is currently in a grace period.</p>
        
        <h3>Account Details:</h3>
        <ul>
            <li><strong>Amount Due:</strong> ${{ number_format($setting->billing_amount, 2) }}</li>
            <li><strong>Grace Period:</strong> {{ $setting->grace_period_days }} days</li>
            <li><strong>Days Remaining:</strong> {{ $setting->subscription_ends_at ? max(0, $setting->grace_period_days - $setting->subscription_ends_at->diffInDays(now())) : $setting->grace_period_days }} days</li>
        </ul>
        
        <p>To avoid any service interruption, please make your payment as soon as possible.</p>
        
        <a href="{{ url('/invoices') }}" class="button">View & Pay Invoice</a>
        
        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        
        <p>Thank you for using MedCura AI.</p>
        
        <p>Best regards,<br>
        The MedCura AI Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>© {{ date('Y') }} MedCura AI. All rights reserved.</p>
    </div>
</body>
</html>