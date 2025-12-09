<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appointment Cancelled - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .appointment-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .doctor-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .cancel-icon {
            font-size: 48px;
            color: #dc3545;
            text-align: center;
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
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .reason-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>❌ Appointment Cancelled</h1>
        <p>{{ config('app.name') }} - Healthcare Appointment System</p>
    </div>

    <div class="content">
        <div class="cancel-icon">✗</div>

        <p>Hello {{ $patient->name ?? $appointment->patient_name }},</p>

        <p>We're sorry to inform you that your appointment has been cancelled. Here are the details:</p>

        <div class="appointment-card">
            <h3>📅 Cancelled Appointment Details</h3>
            <p><strong>Date & Time:</strong> {{ $appointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</p>
            <p><strong>Duration:</strong> {{ $appointment->appointment_duration ?? 30 }} minutes</p>
            <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type ?? 'general')) }}</p>
            <p><strong>Reason:</strong> {{ $appointment->reason ?? 'General consultation' }}</p>
            @if($appointment->consultation_fee)
            <p><strong>Fee:</strong> ${{ number_format($appointment->consultation_fee / 100, 2) }}</p>
            @endif
        </div>

        @if($reason)
        <div class="reason-box">
            <h4>📝 Cancellation Reason:</h4>
            <p>{{ $reason }}</p>
        </div>
        @endif

        <div class="doctor-info">
            <h4>👨‍⚕️ Doctor Information</h4>
            <p><strong>Dr. {{ $doctor->user->name }}</strong></p>
            @if($doctor->specialty)
            <p><strong>Specialty:</strong> {{ $doctor->specialty->name }}</p>
            @endif
            @if($doctor->phone)
            <p><strong>Phone:</strong> {{ $doctor->phone }}</p>
            @endif
        </div>

        <h4>🔄 Next Steps:</h4>
        <ul>
            <li>You can schedule a new appointment at your convenience</li>
            <li>If this cancellation was unexpected, please contact us</li>
            <li>Check your email for any refund information if applicable</li>
        </ul>

        <h4>📅 Schedule New Appointment:</h4>
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ url('/doctors') }}" class="action-button">Find a Doctor</a>
        </div>

        <p>We apologize for any inconvenience this may have caused.</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}.</p>
        <p>Questions? Contact us at <a href="mailto:support@medcura.ai">support@medcura.ai</a></p>
        <p><small>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</small></p>
    </div>
</body>
</html>