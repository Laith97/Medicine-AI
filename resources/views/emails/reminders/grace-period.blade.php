@extends('emails.layouts.master')

@section('title', 'Grace Period Notice - ' . config('app.name'))
@section('email-title', '⏰ Grace Period Active')
@section('email-subtitle', 'Your payment is overdue, but your account remains active')

@push('email-styles')
<style>
    .grace-period-indicator {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        color: #212529;
        padding: 15px 25px;
        border-radius: 25px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        display: inline-block;
        margin: 20px 0;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
    }
    
    .countdown-timer {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 2px solid #ffc107;
        border-radius: 15px;
        padding: 25px;
        margin: 25px 0;
        text-align: center;
    }
    
    .days-remaining {
        font-size: 48px;
        font-weight: 700;
        color: #856404;
        margin: 10px 0;
        text-shadow: 0 2px 4px rgba(133, 100, 4, 0.2);
    }
    
    .timer-label {
        font-size: 16px;
        color: #856404;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 10px;
    }
    
    .payment-summary {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        border-left: 5px solid #2196f3;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .payment-summary h4 {
        color: #1976d2;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .grace-benefits {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-left: 5px solid #28a745;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .grace-benefits h4 {
        color: #155724;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .benefit-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 10px;
        color: #155724;
    }
    
    .benefit-icon {
        color: #28a745;
        margin-right: 10px;
        margin-top: 2px;
    }
    
    .next-steps {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        border-left: 5px solid #9c27b0;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .next-steps h4 {
        color: #7b1fa2;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .step-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 12px;
        color: #7b1fa2;
    }
    
    .step-number {
        width: 24px;
        height: 24px;
        background: #9c27b0;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        margin-right: 12px;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
    <div class="greeting">Hello {{ $user->name }},</div>
    
    <div class="alert alert-warning">
        <strong>⏰ Grace Period Active:</strong> Your {{ config('app.name') }} subscription payment is overdue, but don't worry - your account remains fully active during this grace period.
    </div>
    
    <p class="content-text">
        We understand that sometimes payments can be missed due to busy schedules or payment method issues. That's why we provide a grace period to ensure uninterrupted access to your medical AI tools.
    </p>
    
    <div style="text-align: center;">
        <div class="grace-period-indicator">⏰ Grace Period Active</div>
    </div>
    
    <!-- Countdown Timer -->
    <div class="countdown-timer">
        <h3 style="margin-top: 0; color: #856404;">Time Remaining in Grace Period</h3>
        <div class="days-remaining">
            {{ $setting->subscription_ends_at ? max(0, $setting->grace_period_days - $setting->subscription_ends_at->diffInDays(now())) : $setting->grace_period_days }}
        </div>
        <div class="timer-label">Days Remaining</div>
        <p style="margin-bottom: 0; color: #856404; margin-top: 15px;">
            <strong>Grace period ends:</strong> 
            {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->addDays($setting->grace_period_days)->format('F j, Y \a\t g:i A') : 'N/A' }}
        </p>
    </div>
    
    <!-- Payment Summary -->
    <div class="payment-summary">
        <h4>💳 Payment Information</h4>
        <table class="data-table">
            <tr>
                <td><strong>Amount Due</strong></td>
                <td><strong style="color: #1976d2;">${{ number_format($setting->billing_amount ?? 0, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Original Due Date</strong></td>
                <td>{{ $setting->subscription_ends_at ? $setting->subscription_ends_at->format('F j, Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Days Overdue</strong></td>
                <td>{{ $setting->subscription_ends_at ? $setting->subscription_ends_at->diffInDays(now()) : 0 }} days</td>
            </tr>
            <tr>
                <td><strong>Grace Period</strong></td>
                <td>{{ $setting->grace_period_days ?? 7 }} days total</td>
            </tr>
        </table>
    </div>
    
    <!-- Grace Period Benefits -->
    <div class="grace-benefits">
        <h4>✅ What's Protected During Grace Period:</h4>
        <div class="benefit-item">
            <span class="benefit-icon">•</span>
            <span><strong>Full Access:</strong> All AI diagnosis features remain available</span>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">•</span>
            <span><strong>Data Security:</strong> Your patient data and case history are safe</span>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">•</span>
            <span><strong>Account Status:</strong> No service interruptions or restrictions</span>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">•</span>
            <span><strong>Support Access:</strong> Full customer support remains available</span>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="btn-container">
        <a href="{{ route('invoices.index') }}" class="btn btn-primary">
            💳 Pay Now - ${{ number_format($setting->billing_amount ?? 0, 2) }}
        </a>
        <a href="{{ route('subscription.manage') }}" class="btn btn-secondary">
            ⚙️ Update Payment Method
        </a>
    </div>
    
    <!-- Next Steps -->
    <div class="next-steps">
        <h4>📋 Easy Payment Options:</h4>
        <div class="step-item">
            <div class="step-number">1</div>
            <div>
                <strong>Online Payment:</strong> Pay instantly through your account dashboard<br>
                <small>Most convenient - takes less than 2 minutes</small>
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">2</div>
            <div>
                <strong>Update Payment Method:</strong> Fix any card issues or expired cards<br>
                <small>Prevent future payment problems</small>
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">3</div>
            <div>
                <strong>Contact Support:</strong> Need help or have payment questions?<br>
                <small>Our billing team is ready to assist</small>
            </div>
        </div>
    </div>
    
    <!-- Account Details -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">📋</div>
            Account Status
        </div>
        
        <table class="data-table">
            <tr>
                <td><strong>Current Status</strong></td>
                <td><span class="status-badge status-warning">⏰ Grace Period</span></td>
            </tr>
            <tr>
                <td><strong>Subscription Plan</strong></td>
                <td>{{ $user->monthlyInvoiceSetting ? 'Custom Plan' : 'Standard' }}</td>
            </tr>
            <tr>
                <td><strong>Service Level</strong></td>
                <td><span class="status-badge status-active">✅ Full Access</span></td>
            </tr>
            <tr>
                <td><strong>Next Action</strong></td>
                <td>Payment required within {{ $setting->subscription_ends_at ? max(0, $setting->grace_period_days - $setting->subscription_ends_at->diffInDays(now())) : $setting->grace_period_days }} days</td>
            </tr>
        </table>
    </div>
    
    <div class="alert alert-info">
        <strong>💡 Good News:</strong> Your medical practice continues uninterrupted during this grace period. We're committed to supporting your patient care while resolving this billing matter.
    </div>
    
    <p class="content-text">
        We appreciate your business and want to make payment as convenient as possible. If you're experiencing any issues or need assistance, our billing team is here to help.
    </p>
    
    <p class="content-text">
        <strong>Thank you for your prompt attention to this matter.</strong><br>
        The {{ config('app.name') }} Billing Team 💼
    </p>
@endsection

@section('footer-content')
    <p style="margin-top: 15px; font-size: 13px; color: #6c757d;">
        <strong>Payment Questions?</strong> Contact our billing team at 
        <a href="mailto:billing@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #DE6262;">
            billing@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com
        </a>
    </p>
    <p style="font-size: 12px; color: #6c757d; margin-top: 10px;">
        This is an automated billing notice. Your account and patient data remain fully secure.
    </p>
@endsection