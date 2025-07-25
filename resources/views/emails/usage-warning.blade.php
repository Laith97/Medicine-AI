<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usage Warning</title>
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
        .warning-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            display: inline-block;
            margin: 20px 0;
        }
        .usage-stats {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .usage-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            position: relative;
            background: conic-gradient(
                #DE6262 0deg {{ $usagePercentage * 3.6 }}deg,
                #e9ecef {{ $usagePercentage * 3.6 }}deg 360deg
            );
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .usage-circle::before {
            content: '';
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }
        .usage-percentage {
            font-size: 24px;
            font-weight: bold;
            color: #DE6262;
            position: relative;
            z-index: 1;
        }
        .usage-details {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .usage-item {
            text-align: center;
            flex: 1;
        }
        .usage-number {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        .usage-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-top: 5px;
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
        .upgrade-section {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .alert-box.critical {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">
                ⚠️ {{ config('app.name') }}
            </div>
            <h1>Usage Alert</h1>
            <p>You're approaching your monthly token limit</p>
        </div>

        <div class="content">
            <p>Hi {{ $user->name }},</p>
            
            @if($usagePercentage >= 90)
                <div class="alert-box critical">
                    <strong>Critical:</strong> You've used {{ $usagePercentage }}% of your monthly token allowance. Your account may be limited soon.
                </div>
            @else
                <div class="alert-box">
                    <strong>Warning:</strong> You've used {{ $usagePercentage }}% of your monthly token allowance.
                </div>
            @endif

            <div class="usage-stats">
                <div class="usage-circle">
                    <div class="usage-percentage">{{ $usagePercentage }}%</div>
                </div>
                
                <div class="usage-details">
                    <div class="usage-item">
                        <div class="usage-number">{{ number_format($currentUsage) }}</div>
                        <div class="usage-label">Tokens Used</div>
                    </div>
                    <div class="usage-item">
                        <div class="usage-number">{{ $tokenLimit === -1 ? '∞' : number_format($tokenLimit) }}</div>
                        <div class="usage-label">Monthly Limit</div>
                    </div>
                    <div class="usage-item">
                        <div class="usage-number">{{ $tokenLimit === -1 ? '∞' : number_format($tokenLimit - $currentUsage) }}</div>
                        <div class="usage-label">Remaining</div>
                    </div>
                </div>
            </div>

            <p><strong>Current Plan:</strong> {{ $planConfig['name'] ?? ucfirst($user->current_plan) }}</p>

            @if($usagePercentage >= 80 && $user->current_plan !== 'enterprise')
                <div class="upgrade-section">
                    <h3 style="margin-top: 0; color: #2c3e50;">Consider Upgrading Your Plan</h3>
                    <p>To avoid service interruptions, consider upgrading to a higher plan with more tokens.</p>
                    
                    @if($user->current_plan === 'free' || $user->current_plan === 'basic')
                        <p><strong>Professional Plan:</strong> 250,000 tokens/month for just $79/month</p>
                    @endif
                    
                    @if($user->current_plan !== 'enterprise')
                        <p><strong>Enterprise Plan:</strong> Unlimited tokens for $199/month</p>
                    @endif
                    
                    <a href="{{ route('subscription.pricing') }}" class="cta-button">
                        Upgrade Now
                    </a>
                </div>
            @endif

            <h3 style="color: #2c3e50;">What happens when you reach your limit?</h3>
            <ul>
                <li>AI diagnosis requests will be temporarily paused</li>
                <li>You'll need to wait until next month or upgrade your plan</li>
                <li>All your existing data and cases remain accessible</li>
            </ul>

            <h3 style="color: #2c3e50;">Tips to manage your usage:</h3>
            <ul>
                <li>Review your most token-intensive requests</li>
                <li>Consider shorter, more focused queries</li>
                <li>Monitor your usage in the <a href="{{ route('subscription.manage') }}" style="color: #DE6262;">subscription dashboard</a></li>
            </ul>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('subscription.manage') }}" class="cta-button">
                    View Usage Details
                </a>
            </div>

            <p>If you have any questions about your usage or need help optimizing your requests, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br>
            The {{ config('app.name') }} Team</p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $user->email }} because you're approaching your usage limit.</p>
            <p>You can manage your subscription and view detailed usage statistics in your <a href="{{ route('subscription.manage') }}" style="color: #DE6262;">account dashboard</a>.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>