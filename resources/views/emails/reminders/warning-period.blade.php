<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Final Warning - Payment Due Soon</title>
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
            background: #dc3545;
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
        <h1>⚠️ Final Warning</h1>
        <p>Immediate Action Required</p>
    </div>
    
    <div class="content">
        <h2>Hello {{ $userName }},</h2>
        
        <div class="alert">
            <strong>URGENT:</strong> Your account will be restricted soon if payment is not received.
        </div>
        
        <p>This is a final warning regarding your overdue MedCura AI subscription payment. Your account is currently in the warning period and will be restricted if payment is not received within the next few days.</p>
        
        <h3>Account Details:</h3>
        <ul>
            <li><strong>Amount Due:</strong> ${{ number_format($billingAmount, 2) }}</li>
            <li><strong>Original Due Date:</strong> {{ $subscriptionEndsAt ? $subscriptionEndsAt->format('M d, Y') : 'N/A' }}</li>
            <li><strong>Days Overdue:</strong> {{ $subscriptionEndsAt ? $subscriptionEndsAt->diffInDays(now()) : 0 }} days</li>
            <li><strong>Warning Period:</strong> {{ $gracePeriodDays }} days</li>
        </ul>
        
        <p><strong>What happens next:</strong></p>
        <ul>
            <li>If payment is not received, your account will be restricted</li>
            <li>You will lose access to AI features and other premium services</li>
            <li>Your account will remain restricted until payment is made</li>
        </ul>
        
        <a href="{{ url('/invoices') }}" class="button">Pay Now to Avoid Restriction</a>
        
        <p>If you're experiencing financial difficulties or have questions about your bill, please contact our support team immediately.</p>
        
        <p>Best regards,<br>
        The MedCura AI Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>© {{ date('Y') }} MedCura AI. All rights reserved.</p>
    </div>
</body>
</html>