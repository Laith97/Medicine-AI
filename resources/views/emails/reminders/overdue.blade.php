@extends('emails.layouts.master')

@section('title', 'Payment Required - ' . config('app.name'))
@section('email-title', '⚠️ Payment Required')
@section('email-subtitle', 'Your subscription payment is overdue and needs immediate attention')

@push('email-styles')
<style>
    .urgency-indicator {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 25px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        display: inline-block;
        margin: 20px 0;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3); }
        50% { box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5); }
        100% { box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3); }
    }
    
    .payment-summary {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        border: 2px solid #fc8181;
        border-radius: 15px;
        padding: 25px;
        margin: 25px 0;
        text-align: center;
    }
    
    .amount-due {
        font-size: 36px;
        font-weight: 700;
        color: #dc3545;
        margin: 10px 0;
        text-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
    }
    
    .overdue-days {
        background: #dc3545;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        display: inline-block;
        margin: 10px 0;
    }
    
    .consequences-list {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border-left: 5px solid #dc3545;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .consequences-list h4 {
        color: #721c24;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .consequence-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 10px;
        color: #721c24;
    }
    
    .consequence-icon {
        color: #dc3545;
        margin-right: 10px;
        margin-top: 2px;
    }
    
    .payment-options {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        border-left: 5px solid #2196f3;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .payment-options h4 {
        color: #1976d2;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .contact-support {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        border-left: 5px solid #9c27b0;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
        text-align: center;
    }
    
    .contact-support h4 {
        color: #7b1fa2;
        margin-top: 0;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('content')
    <div class="greeting">Hello {{ $user->name }},</div>
    
    <div class="alert alert-danger">
        <strong>🚨 Urgent Action Required:</strong> Your {{ config('app.name') }} subscription payment is significantly overdue. To maintain uninterrupted access to your medical AI tools, please make your payment immediately.
    </div>
    
    <p class="content-text">
        We understand that managing billing can sometimes be overlooked in a busy medical practice. However, your account requires immediate attention to prevent service interruption.
    </p>
    
    <div style="text-align: center;">
        <div class="urgency-indicator">⏰ Immediate Payment Required</div>
    </div>
    
    <!-- Payment Summary -->
    <div class="payment-summary">
        <h3 style="margin-top: 0; color: #dc3545;">Outstanding Balance</h3>
        <div class="amount-due">${{ number_format($setting->billing_amount ?? 0, 2) }}</div>
        <div class="overdue-days">
            {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->diffInDays(now()) : 0 }} Days Overdue
        </div>
        <p style="margin-bottom: 0; color: #721c24;">
            <strong>Original Due Date:</strong> {{ $setting->subscription_ends_at ? $setting->subscription_ends_at->format('F j, Y') : 'N/A' }}
        </p>
    </div>
    
    <!-- Account Details -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">📋</div>
            Account Information
        </div>
        
        <table class="data-table">
            <tr>
                <td><strong>Account Status</strong></td>
                <td><span class="status-badge status-danger">⚠️ Payment Overdue</span></td>
            </tr>
            <tr>
                <td><strong>Subscription Plan</strong></td>
                <td>{{ $user->monthlyInvoiceSetting ? 'Custom Plan' : 'Standard' }}</td>
            </tr>
            <tr>
                <td><strong>Amount Due</strong></td>
                <td><strong style="color: #dc3545;">${{ number_format($setting->billing_amount ?? 0, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Grace Period</strong></td>
                <td>{{ $setting->grace_period_days ?? 7 }} days ({{ max(0, ($setting->grace_period_days ?? 7) - ($setting->subscription_ends_at ? $setting->subscription_ends_at->diffInDays(now()) : 0)) }} days remaining)</td>
            </tr>
        </table>
    </div>
    
    <!-- Immediate Actions -->
    <div class="btn-container">
        <a href="{{ route('invoices.index') }}" class="btn btn-danger">
            💳 Pay Now - ${{ number_format($setting->billing_amount ?? 0, 2) }}
        </a>
        <a href="{{ route('subscription.manage') }}" class="btn btn-secondary">
            ⚙️ Update Payment Method
        </a>
    </div>
    
    <!-- Service Impact -->
    <div class="consequences-list">
        <h4>🚫 Service Impact if Payment is Not Made:</h4>
        <div class="consequence-item">
            <span class="consequence-icon">•</span>
            <span><strong>Limited Access:</strong> AI diagnosis features will be restricted</span>
        </div>
        <div class="consequence-item">
            <span class="consequence-icon">•</span>
            <span><strong>Data Security:</strong> Your patient data remains safe and accessible</span>
        </div>
        <div class="consequence-item">
            <span class="consequence-icon">•</span>
            <span><strong>Account Status:</strong> Account may be suspended after grace period</span>
        </div>
        <div class="consequence-item">
            <span class="consequence-icon">•</span>
            <span><strong>Additional Fees:</strong> Late payment charges may apply</span>
        </div>
    </div>
    
    <!-- Payment Options -->
    <div class="payment-options">
        <h4>💡 Easy Payment Options:</h4>
        <p style="margin-bottom: 15px;">We've made it simple to get your account current:</p>
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Online Payment:</strong> Pay instantly through your account dashboard</li>
            <li><strong>Auto-Pay Setup:</strong> Prevent future issues with automatic billing</li>
            <li><strong>Payment Plan:</strong> Contact us if you need payment assistance</li>
            <li><strong>Update Card:</strong> Ensure your payment method is current</li>
        </ul>
    </div>
    
    <!-- Support Contact -->
    <div class="contact-support">
        <h4>🤝 Need Help? We're Here for You</h4>
        <p>If you're experiencing financial difficulties or have questions about your bill, our billing team is ready to work with you on a solution.</p>
        <p style="margin-bottom: 0;">
            <strong>Call:</strong> <a href="tel:+1-555-MEDCURA" style="color: #7b1fa2;">(555) MED-CURA</a><br>
            <strong>Email:</strong> <a href="mailto:billing@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #7b1fa2;">billing@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com</a>
        </p>
    </div>
    
    <div class="alert alert-info">
        <strong>💡 Important:</strong> Your patient data and case history remain secure and will not be affected. We're committed to supporting your practice while resolving this billing matter.
    </div>
    
    <p class="content-text">
        We value your partnership and want to ensure uninterrupted access to the AI tools that support your medical practice. Please take action today to resolve this matter.
    </p>
    
    <p class="content-text">
        <strong>Thank you for your immediate attention to this matter.</strong><br>
        The {{ config('app.name') }} Billing Team 💼
    </p>
@endsection

@section('footer-content')
    <p style="margin-top: 15px; font-size: 13px; color: #6c757d;">
        <strong>Questions about this notice?</strong> Contact our billing team at 
        <a href="mailto:billing@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #DE6262;">
            billing@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com
        </a>
    </p>
    <p style="font-size: 12px; color: #6c757d; margin-top: 10px;">
        This is an automated billing notice. Your account security and patient data remain fully protected.
    </p>
@endsection