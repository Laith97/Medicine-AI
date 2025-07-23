<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #DE6262;
        }
        .logo {
            color: #DE6262;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .plan-badge {
            background: linear-gradient(135deg, #DE6262, #E87A7A);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            display: inline-block;
            margin: 20px 0;
        }
        .subscription-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #2c3e50;
        }
        .features-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .features-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .features-list li:last-child {
            border-bottom: none;
        }
        .check-icon {
            color: #28a745;
            margin-right: 10px;
        }
        .cta-button {
            background: linear-gradient(135deg, #DE6262, #E87A7A);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .support-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">
                🏥 {{ config('app.name') }}
            </div>
            <h1>Welcome to Your New Plan!</h1>
            <p>Your subscription has been successfully activated.</p>
        </div>

        <div class="content">
            <p>Hi {{ $user->name }},</p>
            
            <p>Thank you for subscribing to {{ config('app.name') }}! Your <strong>{{ ucfirst($subscription->plan) }}</strong> plan is now active and ready to use.</p>

            <div class="plan-badge">{{ ucfirst($subscription->plan) }} Plan</div>

            <div class="subscription-details">
                <h3 style="margin-top: 0; color: #2c3e50;">Subscription Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Plan:</span>
                    <span class="detail-value">{{ $planConfig['name'] ?? ucfirst($subscription->plan) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Billing Cycle:</span>
                    <span class="detail-value">{{ ucfirst($subscription->billing_cycle) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value">${{ number_format($subscription->amount, 2) }}/{{ $subscription->billing_cycle === 'yearly' ? 'year' : 'month' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Next Billing Date:</span>
                    <span class="detail-value">{{ $subscription->ends_at->format('F j, Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: 600;">Active</span>
                </div>
            </div>

            @if(isset($planConfig['features']))
            <h3 style="color: #2c3e50;">What's Included in Your Plan</h3>
            <ul class="features-list">
                @foreach($planConfig['features'] as $feature)
                <li>
                    <span class="check-icon">✓</span>{{ $feature }}
                </li>
                @endforeach
            </ul>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('dashboard') }}" class="cta-button">
                    Get Started Now
                </a>
            </div>

            <div class="support-info">
                <h4 style="margin-top: 0; color: #1976d2;">Need Help Getting Started?</h4>
                <p style="margin-bottom: 0;">Our support team is here to help you make the most of your subscription. Visit your dashboard to start using AI-powered medical diagnosis tools right away.</p>
            </div>

            <p>You can manage your subscription, view usage statistics, and update your billing information anytime from your <a href="{{ route('subscription.manage') }}" style="color: #DE6262;">subscription dashboard</a>.</p>

            <p>Thank you for choosing {{ config('app.name') }} to enhance your medical practice!</p>

            <p>Best regards,<br>
            The {{ config('app.name') }} Team</p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $user->email }} because you subscribed to {{ config('app.name') }}.</p>
            <p>If you have any questions, please contact us at <a href="mailto:support@medcuraai.com" style="color: #DE6262;">support@medcuraai.com</a></p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>