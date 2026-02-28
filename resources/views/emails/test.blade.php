@extends('emails.layouts.master')

@section('content')
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333;">Email System Test</h2>

    <p>Hello,</p>

    <p>This is a test email to verify that the email system is working correctly.</p>

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>Test Details:</strong><br>
        Configuration: {{ $config ?? 'Default' }}<br>
        Timestamp: {{ $timestamp ?? now() }}<br>
        Environment: {{ config('app.env') }}
    </div>

    <p>If you received this email, the email templates and delivery system are working properly!</p>

    <p>Best regards,<br>
    MedCura AI Team</p>
</div>
@endsection