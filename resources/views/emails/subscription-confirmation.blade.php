@extends('emails.layouts.master')

@section('title', 'Subscription Confirmed - ' . config('app.name'))
@section('email-title', '🎉 Welcome to Your New Plan!')
@section('email-subtitle', 'Your subscription has been successfully activated and is ready to use')

@push('email-styles')
<style>
    .plan-badge {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        display: inline-block;
        margin: 20px 0;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin: 25px 0;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(222, 98, 98, 0.1);
    }
    
    .feature-item:last-child {
        border-bottom: none;
    }
    
    .feature-icon {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    
    .next-steps {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        border-radius: 15px;
        padding: 25px;
        margin: 30px 0;
        border-left: 5px solid #2196f3;
    }
    
    .next-steps h3 {
        color: #1976d2;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .step-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .step-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 12px;
        padding: 8px 0;
    }
    
    .step-number {
        width: 24px;
        height: 24px;
        background: #1976d2;
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
    <div class="greeting">Hello {{ $user->name }}! 👋</div>
    
    <div class="alert alert-success">
        <strong>🎉 Congratulations!</strong> Your {{ ucfirst($subscription->plan) }} subscription is now active and ready to use. Welcome to the future of AI-powered medical assistance!
    </div>
    
    <p class="content-text">
        Thank you for choosing {{ config('app.name') }} to enhance your medical practice. Your subscription gives you access to cutting-edge AI technology designed specifically for healthcare professionals.
    </p>
    
    <div style="text-align: center;">
        <div class="plan-badge">{{ ucfirst($subscription->plan) }} Plan Active</div>
    </div>
    
    <!-- Subscription Details -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">📋</div>
            Subscription Details
        </div>
        
        <table class="data-table">
            <tr>
                <td><strong>Plan Name</strong></td>
                <td>{{ $planConfig['name'] ?? ucfirst($subscription->plan) }}</td>
            </tr>
            <tr>
                <td><strong>Billing Cycle</strong></td>
                <td>{{ ucfirst($subscription->billing_cycle) }}</td>
            </tr>
            <tr>
                <td><strong>Amount</strong></td>
                <td><strong>${{ number_format($subscription->amount, 2) }}</strong>/{{ $subscription->billing_cycle === 'yearly' ? 'year' : 'month' }}</td>
            </tr>
            <tr>
                <td><strong>Next Billing Date</strong></td>
                <td>{{ $subscription->ends_at->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td><span class="status-badge status-active">✅ Active</span></td>
            </tr>
        </table>
    </div>
    
    @if(isset($planConfig['features']) && count($planConfig['features']) > 0)
    <!-- Plan Features -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">⭐</div>
            What's Included in Your Plan
        </div>
        
        <div class="features-grid">
            @foreach($planConfig['features'] as $feature)
            <div class="feature-item">
                <div class="feature-icon">✓</div>
                <span>{{ $feature }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Next Steps -->
    <div class="next-steps">
        <h3>🚀 Ready to Get Started?</h3>
        <p>Here's how to make the most of your new subscription:</p>
        
        <ol class="step-list">
            <li class="step-item">
                <div class="step-number">1</div>
                <div>
                    <strong>Access Your Dashboard</strong><br>
                    <small>Start using AI-powered medical diagnosis tools right away</small>
                </div>
            </li>
            <li class="step-item">
                <div class="step-number">2</div>
                <div>
                    <strong>Explore Features</strong><br>
                    <small>Discover all the tools available in your plan</small>
                </div>
            </li>
            <li class="step-item">
                <div class="step-number">3</div>
                <div>
                    <strong>Get Support</strong><br>
                    <small>Our team is here to help you succeed</small>
                </div>
            </li>
        </ol>
    </div>
    
    <!-- Call to Action -->
    <div class="btn-container">
        <a href="{{ route('dashboard') }}" class="btn btn-primary">
            🏥 Access Your Dashboard
        </a>
        <a href="{{ route('subscription.manage') }}" class="btn btn-secondary">
            ⚙️ Manage Subscription
        </a>
    </div>
    
    <div class="alert alert-info">
        <strong>💡 Pro Tip:</strong> Bookmark your dashboard and subscription management page for quick access. You can monitor your usage, update billing information, and access support anytime.
    </div>
    
    <p class="content-text">
        Our support team is standing by to help you get the most out of your subscription. If you have any questions or need assistance getting started, don't hesitate to reach out.
    </p>
    
    <p class="content-text">
        Thank you for trusting {{ config('app.name') }} with your medical practice. We're excited to be part of your journey toward more efficient and effective patient care.
    </p>
    
    <p class="content-text">
        <strong>Best regards,</strong><br>
        The {{ config('app.name') }} Team 🏥
    </p>
@endsection

@section('footer-content')
    <p style="margin-top: 15px; font-size: 13px; color: #6c757d;">
        <strong>Need Help?</strong> Contact our support team at 
        <a href="mailto:support@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #DE6262;">
            support@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com
        </a>
    </p>
@endsection