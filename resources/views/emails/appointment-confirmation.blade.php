@extends('emails.layouts.master')

@section('title', 'Appointment Confirmed - ' . config('app.name'))
@section('email-title', '✅ Appointment Confirmed')
@section('email-subtitle', 'Your appointment with Dr. ' . $doctor->user->name . ' is confirmed')
@section('preview', 'Your appointment on ' . $appointment->appointment_date->format('M j, Y g:i A') . ' is confirmed')

@section('content')
<div class="greeting">Hello {{ $patient->name ?? $appointment->patient_name }},</div>

<div class="alert alert-success">
    <strong>Great news!</strong> Your appointment has been confirmed. Please arrive 15 minutes early.
</div>

<p class="content-text">Here are your appointment details:</p>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">📅</span> Appointment Details</div>
    <table class="data-table">
        <tr><td><strong>Date &amp; Time</strong></td><td>{{ $appointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</td></tr>
        <tr><td><strong>Duration</strong></td><td>{{ $appointment->appointment_duration ?? 30 }} minutes</td></tr>
        <tr><td><strong>Type</strong></td><td><span class="badge badge-info">{{ ucfirst(str_replace('_',' ', $appointment->appointment_type ?? 'general')) }}</span></td></tr>
        @if($appointment->reason)<tr><td><strong>Reason</strong></td><td>{{ $appointment->reason }}</td></tr>@endif
    </table>
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">👨‍⚕️</span> Your Doctor</div>
    <p style="margin:0 0 6px"><strong>Dr. {{ $doctor->user->name }}</strong>@if($doctor->specialty) — {{ $doctor->specialty->name }}@endif</p>
    @if($doctor->phone)<p style="margin:0;color:#64748b;font-size:14px;">📞 {{ $doctor->phone }}</p>@endif
</div>

<div class="info-card" style="background:#f8fafc">
    <div class="info-card-header"><span class="info-card-icon" style="background:#1e293b">📋</span> What to Expect</div>
    <ul style="margin:0;padding-left:18px;color:#334155;font-size:14px;line-height:1.7">
        <li>Please arrive 15 minutes early for check-in</li>
        <li>Bring medical records / test results</li>
        <li>Have insurance info ready</li>
        <li>Prepare questions for your doctor</li>
    </ul>
</div>

<div style="text-align:center;margin:24px 0;">
    @if($appointment->isGuestAppointment())
        <a href="{{ route('appointments.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}" class="btn btn-primary">Manage Your Appointment</a>
        <p style="font-size:12px;color:#64748b;margin-top:8px">Save this link — linked to {{ $appointment->guest_email }}</p>
    @else
        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-primary">View Appointment</a>
    @endif
</div>

<p class="content-text" style="font-size:13px;color:#64748b">Need to cancel or reschedule? Please do so at least {{ $doctor->cancellation_hours ?? 24 }} hours in advance.</p>
@endsection
