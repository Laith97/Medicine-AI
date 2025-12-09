<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appointment Confirmed - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            border-left: 4px solid #28a745;
        }
        .doctor-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success-icon {
            font-size: 48px;
            color: #28a745;
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Appointment Confirmed</h1>
        <p>{{ config('app.name') }} - Healthcare Appointment System</p>
    </div>

    <div class="content">
        <div class="success-icon">✓</div>

        <p>Hello {{ $patient->name ?? $appointment->patient_name }},</p>

        <p>Great news! Your appointment has been confirmed by Dr. {{ $doctor->user->name }}. Here are the details:</p>

        <div class="appointment-card">
            <h3>📅 Appointment Details</h3>
            <p><strong>Date & Time:</strong> {{ $appointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</p>
            <p><strong>Duration:</strong> {{ $appointment->appointment_duration ?? 30 }} minutes</p>
            <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type ?? 'general')) }}</p>
            <p><strong>Reason:</strong> {{ $appointment->reason ?? 'General consultation' }}</p>
            @if($appointment->consultation_fee)
            <p><strong>Fee:</strong> ${{ number_format($appointment->consultation_fee / 100, 2) }}</p>
            @endif
        </div>

        <div class="doctor-info">
            <h4>👨‍⚕️ Your Doctor</h4>
            <p><strong>Dr. {{ $doctor->user->name }}</strong></p>
            @if($doctor->specialty)
            <p><strong>Specialty:</strong> {{ $doctor->specialty->name }}</p>
            @endif
            @if($doctor->phone)
            <p><strong>Phone:</strong> {{ $doctor->phone }}</p>
            @endif
        </div>

        <h4>📋 What to Expect:</h4>
        <ul>
            <li>Please arrive 15 minutes early for check-in</li>
            <li>Bring any relevant medical records or test results</li>
            <li>Have your insurance information ready if applicable</li>
            <li>Prepare any questions you have for your doctor</li>
        </ul>

        <h4>🔧 Manage Your Appointment:</h4>
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ route('appointments.show', $appointment) }}" class="action-button">View Appointment</a>
        </div>

        <p>If you need to cancel or reschedule this appointment, please do so at least {{ $doctor->cancellation_hours ?? 24 }} hours in advance.</p>

        <p>We look forward to seeing you!</p>
    </div>

    <div class="footer">
        <p>This is an automated confirmation from {{ config('app.name') }}.</p>
        <p>Questions? Contact us at <a href="mailto:support@medcura.ai">support@medcura.ai</a></p>
        <p><small>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</small></p>
    </div>
</body>
</html>