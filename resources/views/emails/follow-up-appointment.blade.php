<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Follow-up Appointment Scheduled - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
            border-left: 4px solid #17a2b8;
        }
        .original-appointment {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
        }
        .doctor-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .followup-icon {
            font-size: 48px;
            color: #17a2b8;
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
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
        <h1>🔄 Follow-up Appointment Scheduled</h1>
        <p>{{ config('app.name') }} - Healthcare Appointment System</p>
    </div>

    <div class="content">
        <div class="followup-icon">🔄</div>

        <p>Hello {{ $patient->name ?? $followUpAppointment->patient_name }},</p>

        <p>A follow-up appointment has been scheduled for you with Dr. {{ $doctor->user->name }}. Regular follow-up care is important for maintaining your health and monitoring your progress.</p>

        <div class="appointment-card original-appointment">
            <h3>📅 Original Appointment</h3>
            <p><strong>Date & Time:</strong> {{ $originalAppointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</p>
            <p><strong>Duration:</strong> {{ $originalAppointment->appointment_duration ?? 30 }} minutes</p>
            <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $originalAppointment->appointment_type ?? 'general')) }}</p>
            <p><strong>Reason:</strong> {{ $originalAppointment->reason ?? 'General consultation' }}</p>
        </div>

        <div class="appointment-card">
            <h3>📅 Your Follow-up Appointment</h3>
            <p><strong>Date & Time:</strong> {{ $followUpAppointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</p>
            <p><strong>Duration:</strong> {{ $followUpAppointment->appointment_duration ?? 30 }} minutes</p>
            <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $followUpAppointment->appointment_type ?? 'follow-up')) }}</p>
            <p><strong>Reason:</strong> {{ $followUpAppointment->reason ?? 'Follow-up consultation' }}</p>
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

        <h4>📋 What to Prepare:</h4>
        <ul>
            <li>Bring any medications you're currently taking</li>
            <li>Note any symptoms or changes since your last visit</li>
            <li>Prepare questions for your doctor</li>
            <li>Bring your previous medical records if requested</li>
        </ul>

        <h4>🔧 Manage Your Appointments:</h4>
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ route('appointments.show', $followUpAppointment) }}" class="action-button">View Follow-up Details</a>
        </div>

        <p>If you need to reschedule this follow-up appointment, please contact us at least {{ $doctor->cancellation_hours ?? 24 }} hours in advance.</p>

        <p>We look forward to seeing you for your follow-up care!</p>
    </div>

    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}.</p>
        <p>Questions? Contact us at <a href="mailto:support@medcura.ai">support@medcura.ai</a></p>
        <p><small>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</small></p>
    </div>
</body>
</html>