<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Grace Period - Action Required</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .field {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #DE6262;
        }
        .field-label {
            font-weight: bold;
            color: #DE6262;
            margin-bottom: 5px;
        }
        .field-value {
            color: #333;
            word-wrap: break-word;
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
            background: #DE6262;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Reminder</h1>
        <p>MedCura AI - Account Update</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <div class="alert">
            <strong>Friendly Reminder:</strong> We wanted to let you know that your subscription payment is due. No worries - you have a grace period to complete your payment.
        </div>
        
        <div class="field">
            <div class="field-label">Account Email:</div>
            <div class="field-value">{{ $userEmail }}</div>
        </div>
        
        <div class="field">
            <div class="field-label">Amount Due:</div>
            <div class="field-value">${{ number_format($billingAmount, 2) }}</div>
        </div>
        
        <div class="field">
            <div class="field-label">Grace Period:</div>
            <div class="field-value">{{ $gracePeriodDays }} days</div>
        </div>
        
        @if($subscriptionEndsAt)
        <div class="field">
            <div class="field-label">Payment Was Due:</div>
            <div class="field-value">{{ $subscriptionEndsAt->format('M d, Y') }}</div>
        </div>
        
        <div class="field">
            <div class="field-label">Days Remaining in Grace Period:</div>
            <div class="field-value">{{ max(0, $gracePeriodDays - $subscriptionEndsAt->diffInDays(now())) }} days</div>
        </div>
        @endif
        
        <p>To continue enjoying uninterrupted access to MedCura AI, please complete your payment at your convenience.</p>
        
        <a href="{{ url('/invoices') }}" class="button">View Invoice & Pay</a>
        
        <p>If you have any questions or need assistance, our support team is here to help.</p>
        
        <p>Thank you for using MedCura AI.</p>
        
        <p>Best regards,<br>
        The MedCura AI Team</p>
    </div>
    
    <div class="footer">
        <p>This message was sent from MedCura AI regarding your account.</p>
        <p>For support, please contact us at info@medcuraai.com</p>
        <p><small>© {{ date('Y') }} MedCura AI. All rights reserved.</small></p>
    </div>
</body>
</html>