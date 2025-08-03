@extends('emails.layouts.master')

@section('title', 'Usage Alert - ' . config('app.name'))
@section('email-title', '📊 Usage Alert')
@section('email-subtitle', 'You\'re approaching your monthly usage limit')

@push('email-styles')
<style>
    .usage-circle-container {
        text-align: center;
        margin: 30px 0;
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
        font-weight: 700;
        color: #DE6262;
        position: relative;
        z-index: 1;
    }
    
    .usage-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        margin: 25px 0;
    }
    
    .usage-stat {
        text-align: center;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 15px;
        border: 1px solid rgba(222, 98, 98, 0.1);
    }
    
    .usage-number {
        font-size: 24px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .usage-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .upgrade-section {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        border-radius: 15px;
        padding: 25px;
        margin: 30px 0;
        border-left: 5px solid #2196f3;
        text-align: center;
    }
    
    .upgrade-section h3 {
        color: #1976d2;
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .plan-option {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin: 15px 0;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .plan-option:hover {
        border-color: #2196f3;
        box-shadow: 0 4px 15px rgba(33, 150, 243, 0.1);
    }
    
    .plan-name {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .plan-details {
        color: #6c757d;
        font-size: 14px;
    }
    
    .tips-section {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border-left: 5px solid #ffc107;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .tips-section h4 {
        color: #856404;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tip-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 10px;
        color: #856404;
    }
    
    .tip-icon {
        color: #ffc107;
        margin-right: 10px;
        margin-top: 2px;
    }
</style>
@endpush

@section('content')
    <div class="greeting">Hello {{ $user->name }}! 👋</div>
    
    @if($usagePercentage >= 90)
        <div class="alert alert-danger">
            <strong>🚨 Critical Usage Alert:</strong> You've used {{ $usagePercentage }}% of your monthly token allowance. Your account may be limited very soon to prevent overages.
        </div>
    @else
        <div class="alert alert-warning">
            <strong>⚠️ Usage Warning:</strong> You've used {{ $usagePercentage }}% of your monthly token allowance. Time to monitor your usage more closely.
        </div>
    @endif
    
    <p class="content-text">
        We're reaching out to help you manage your {{ config('app.name') }} usage effectively. Your current usage pattern suggests you may reach your monthly limit soon.
    </p>
    
    <!-- Usage Visualization -->
    <div class="usage-circle-container">
        <div class="usage-circle">
            <div class="usage-percentage">{{ $usagePercentage }}%</div>
        </div>
        <p><strong>{{ $usagePercentage }}% of Monthly Allowance Used</strong></p>
    </div>
    
    <!-- Usage Statistics -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">📊</div>
            Current Usage Statistics
        </div>
        
        <div class="usage-stats-grid">
            <div class="usage-stat">
                <div class="usage-number">{{ number_format($currentUsage) }}</div>
                <div class="usage-label">Tokens Used</div>
            </div>
            <div class="usage-stat">
                <div class="usage-number">{{ $tokenLimit === -1 ? '∞' : number_format($tokenLimit) }}</div>
                <div class="usage-label">Monthly Limit</div>
            </div>
            <div class="usage-stat">
                <div class="usage-number">{{ $tokenLimit === -1 ? '∞' : number_format(max(0, $tokenLimit - $currentUsage)) }}</div>
                <div class="usage-label">Remaining</div>
            </div>
        </div>
    </div>
    
    <!-- Current Plan Info -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">📋</div>
            Account Information
        </div>
        
        <table class="data-table">
            <tr>
                <td><strong>Current Plan</strong></td>
                <td>{{ $user->monthlyInvoiceSetting ? 'Custom Plan' : 'Setup Pending' }}</td>
            </tr>
            <tr>
                <td><strong>Usage Status</strong></td>
                <td>
                    @if($usagePercentage >= 90)
                        <span class="status-badge status-danger">🚨 Critical</span>
                    @elseif($usagePercentage >= 75)
                        <span class="status-badge status-warning">⚠️ High Usage</span>
                    @else
                        <span class="status-badge status-active">✅ Normal</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Billing Cycle</strong></td>
                <td>{{ now()->format('F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Reset Date</strong></td>
                <td>{{ now()->endOfMonth()->format('F j, Y') }}</td>
            </tr>
        </table>
    </div>
    
    @if($usagePercentage >= 80)
    <!-- Cost Limit Recommendations -->
    <div class="upgrade-section">
        <h3>💡 Manage Your Usage</h3>
        <p>You're approaching your monthly cost limit. Here are your options:</p>
        
        <div class="plan-option">
            <div class="plan-name">Increase Cost Limit</div>
            <div class="plan-details">Contact support to increase your monthly cost allowance • Flexible pricing • No service interruption</div>
        </div>
        
        <div class="plan-option">
            <div class="plan-name">Monitor Usage</div>
            <div class="plan-details">Track your API usage in real-time • Set up alerts • Optimize your requests</div>
        </div>
        
        <div class="btn-container">
            <a href="{{ route('subscription.manage') }}" class="btn btn-primary">
                📊 Manage Subscription
            </a>
        </div>
    </div>
    @endif
    
    <!-- Usage Management Tips -->
    <div class="tips-section">
        <h4>💡 Smart Usage Tips:</h4>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Optimize queries:</strong> Use concise, specific questions for better token efficiency</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Review patterns:</strong> Check which requests use the most tokens in your dashboard</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Batch requests:</strong> Combine related questions when possible</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Monitor regularly:</strong> Check your usage weekly to avoid surprises</span>
        </div>
    </div>
    
    <!-- What Happens Next -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">❓</div>
            What Happens When You Reach Your Limit?
        </div>
        
        <ul style="margin: 0; padding-left: 20px; color: #34495e;">
            <li><strong>AI requests pause:</strong> New diagnosis requests will be temporarily unavailable</li>
            <li><strong>Data remains safe:</strong> All your patient data and case history stay accessible</li>
            <li><strong>Account active:</strong> Your account remains active with basic features</li>
            <li><strong>Next month reset:</strong> Your token allowance resets on {{ now()->addMonth()->startOfMonth()->format('F j, Y') }}</li>
        </ul>
    </div>
    
    <!-- Action Buttons -->
    <div class="btn-container">
        <a href="{{ route('subscription.manage') }}" class="btn btn-primary">
            📊 View Detailed Usage
        </a>
        @if($usagePercentage >= 80)
        <a href="{{ route('subscription.pricing') }}" class="btn btn-secondary">
            🚀 Upgrade Plan
        </a>
        @endif
    </div>
    
    <div class="alert alert-info">
        <strong>💡 Pro Tip:</strong> Set up usage alerts in your dashboard to get notified at 50%, 75%, and 90% usage levels. This helps you plan your AI requests better.
    </div>
    
    <p class="content-text">
        We're here to help you get the most value from {{ config('app.name') }}. If you have questions about optimizing your usage or need help choosing the right plan, our support team is ready to assist.
    </p>
    
    <p class="content-text">
        <strong>Best regards,</strong><br>
        The {{ config('app.name') }} Team 📊
    </p>
@endsection

@section('footer-content')
    <p style="margin-top: 15px; font-size: 13px; color: #6c757d;">
        <strong>Usage Questions?</strong> Contact our support team at 
        <a href="mailto:support@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #DE6262;">
            support@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com
        </a>
    </p>
@endsection