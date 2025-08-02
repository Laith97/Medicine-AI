@extends('emails.layouts.master')

@section('title', 'Reset Your Password - ' . config('app.name'))
@section('email-title', '🔐 Password Reset Request')
@section('email-subtitle', 'Secure your account with a new password')

@push('email-styles')
<style>
    .security-notice {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border-left: 5px solid #ffc107;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
        text-align: center;
    }
    
    .security-notice h4 {
        color: #856404;
        margin-top: 0;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .expiry-timer {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border: 2px solid #dc3545;
        border-radius: 15px;
        padding: 20px;
        margin: 25px 0;
        text-align: center;
    }
    
    .timer-display {
        font-size: 24px;
        font-weight: 700;
        color: #dc3545;
        margin: 10px 0;
    }
    
    .security-tips {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        border-left: 5px solid #2196f3;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .security-tips h4 {
        color: #1976d2;
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
        color: #0c5460;
    }
    
    .tip-icon {
        color: #2196f3;
        margin-right: 10px;
        margin-top: 2px;
    }
    
    .backup-url {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        word-break: break-all;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #DE6262;
        text-align: center;
    }
    
    .security-contact {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        border-left: 5px solid #9c27b0;
        border-radius: 10px;
        padding: 20px;
        margin: 25px 0;
        text-align: center;
    }
    
    .security-contact h4 {
        color: #7b1fa2;
        margin-top: 0;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('content')
    <div class="greeting">Hello! 👋</div>
    
    <div class="alert alert-info">
        <strong>🔐 Password Reset Requested:</strong> We received a request to reset the password for your {{ config('app.name') }} account. Your account security is our top priority.
    </div>
    
    <p class="content-text">
        Someone (hopefully you!) requested a password reset for your {{ config('app.name') }} account. If this was you, click the button below to create a new secure password.
    </p>
    
    <!-- Security Notice -->
    <div class="security-notice">
        <h4>🛡️ Account Security Notice</h4>
        <p style="margin-bottom: 0; color: #856404;">
            This password reset was requested from a secure connection. Your account data and patient information remain fully protected during this process.
        </p>
    </div>
    
    <!-- Reset Button -->
    <div class="btn-container">
        <a href="{{ $url }}" class="btn btn-primary">
            🔐 Reset My Password
        </a>
    </div>
    
    <!-- Expiry Timer -->
    <div class="expiry-timer">
        <h4 style="margin-top: 0; color: #dc3545;">⏰ Time Sensitive</h4>
        <div class="timer-display">60 Minutes</div>
        <p style="margin-bottom: 0; color: #721c24;">
            This secure link expires in 60 minutes for your protection. If you need a new link after expiration, simply request another password reset.
        </p>
    </div>
    
    <!-- Account Information -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">📋</div>
            Reset Request Details
        </div>
        
        <table class="data-table">
            <tr>
                <td><strong>Request Time</strong></td>
                <td>{{ now()->format('F j, Y \a\t g:i A T') }}</td>
            </tr>
            <tr>
                <td><strong>Account Type</strong></td>
                <td>Medical Professional</td>
            </tr>
            <tr>
                <td><strong>Security Level</strong></td>
                <td><span class="status-badge status-active">🔒 High Security</span></td>
            </tr>
            <tr>
                <td><strong>Link Expires</strong></td>
                <td><strong style="color: #dc3545;">{{ now()->addMinutes(60)->format('g:i A T') }}</strong></td>
            </tr>
        </table>
    </div>
    
    <!-- Security Tips -->
    <div class="security-tips">
        <h4>💡 Password Security Tips:</h4>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Use 12+ characters</strong> with a mix of letters, numbers, and symbols</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Avoid personal information</strong> like names, birthdays, or medical terms</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Consider a passphrase</strong> like "Coffee!Morning@Clinic2024"</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">•</span>
            <span><strong>Use a password manager</strong> to generate and store secure passwords</span>
        </div>
    </div>
    
    <!-- Backup URL -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon">🔗</div>
            Alternative Access
        </div>
        <p>If the button above doesn't work, copy and paste this secure link into your browser:</p>
        <div class="backup-url">{{ $url }}</div>
    </div>
    
    <!-- Security Contact -->
    <div class="security-contact">
        <h4>🚨 Didn't Request This Reset?</h4>
        <p>If you didn't request this password reset, your account may be at risk. Please contact our security team immediately.</p>
        <p style="margin-bottom: 0;">
            <strong>Security Hotline:</strong> <a href="tel:+1-555-SECURITY" style="color: #7b1fa2;">(555) SECURITY</a><br>
            <strong>Email:</strong> <a href="mailto:security@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #7b1fa2;">security@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com</a>
        </p>
    </div>
    
    <div class="alert alert-warning">
        <strong>⚠️ Important:</strong> Never share your password with anyone. {{ config('app.name') }} staff will never ask for your password via email or phone.
    </div>
    
    <p class="content-text">
        After resetting your password, we recommend reviewing your account security settings and enabling two-factor authentication if available.
    </p>
    
    <p class="content-text">
        <strong>Stay secure,</strong><br>
        The {{ config('app.name') }} Security Team 🛡️
    </p>
@endsection

@section('footer-content')
    <p style="margin-top: 15px; font-size: 13px; color: #6c757d;">
        <strong>Security Concerns?</strong> Contact our security team at 
        <a href="mailto:security@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com" style="color: #DE6262;">
            security@{{ strtolower(str_replace(' ', '', config('app.name'))) }}.com
        </a>
    </p>
    <p style="font-size: 12px; color: #6c757d; margin-top: 10px;">
        This is an automated security message. Do not reply to this email.
    </p>
@endsection