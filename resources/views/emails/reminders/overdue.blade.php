<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Overdue - Immediate Action Required</title>
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #e74c3c;
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
        <h1>🚨 Invoice Overdue</h1>
        <p>Immediate Payment Required</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }},</h2>
        
        <div class="alert">
            <strong>CRITICAL:</strong> Your invoice is significantly overdue and requires immediate attention.
        </div>
        
        <p>Your MedCura AI invoice is now significantly overdue. Immediate payment is required to maintain your account in good standing and avoid service restrictions.</p>
        
        <h3>Invoice Details:</h3>
        <ul>
            <li><strong>Amount Due:</strong> ${{ number_format($setting->billing_amount, 2) }}</li>
            <li><strong>Original Due Date:</strong> {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->format('M d, Y') : 'N/A' }}</li>
            <li><strong>Days Overdue:</strong> {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->diffInDays(now()) : 0 }} days</li>
        </ul>
        
        <p><strong>Immediate Actions Required:</strong></p>
        <ul>
            <li>Pay your outstanding invoice immediately</li>
            <li>Contact support if you need payment assistance</li>
            <li>Update your payment method if necessary</li>
        </ul>
        
        <p><strong>Consequences of non-payment:</strong></p>
        <ul>
            <li>Account restrictions and service limitations</li>
            <li>Loss of access to premium features</li>
            <li>Potential account suspension</li>
            <li>Additional late fees may apply</li>
        </ul>
        
        <a href="{{ url('/invoices') }}" class="button">Pay Immediately</a>
        
        <p>If you believe this notice is in error or need to discuss payment options, please contact our billing department immediately.</p>
        
        <p>Best regards,<br>
        The MedCura AI Billing Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>© {{ date('Y') }} MedCura AI. All rights reserved.</p>
    </div>
</body>
</html>