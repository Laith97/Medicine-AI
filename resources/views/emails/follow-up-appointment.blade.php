@extends('emails.layouts.master')

@section('title', 'Follow-up Scheduled - ' . config('app.name'))
@section('email-title', '🔄 Follow-up Scheduled')
@section('email-subtitle', 'Dr. ' . $doctor->user->name . ' scheduled your follow-up')

@section('content')
<div class="greeting">Hello {{ $patient->name ?? $followUpAppointment->patient_name }},</div>

<p class="content-text">A follow-up appointment has been scheduled for you. Regular follow-up is important to monitor your progress.</p>

<div class="info-card" style="opacity:0.85">
    <div class="info-card-header"><span class="info-card-icon" style="background:#64748b">📅</span> Original Appointment</div>
    <table class="data-table" style="margin-bottom:0">
        <tr><td><strong>Date</strong></td><td>{{ $originalAppointment->appointment_date->format('l, F j, Y g:i A') }}</td></tr>
        <tr><td><strong>Type</strong></td><td>{{ ucfirst(str_replace('_',' ', $originalAppointment->appointment_type ?? 'general')) }}</td></tr>
    </table>
</div>

<div class="info-card" style="border-color:#0ea5e9">
    <div class="info-card-header"><span class="info-card-icon" style="background:#0ea5e9">📅</span> Your Follow-up</div>
    <table class="data-table" style="margin-bottom:0">
        <tr><td><strong>Date &amp; Time</strong></td><td><strong>{{ $followUpAppointment->appointment_date->format('l, F j, Y g:i A') }}</strong></td></tr>
        <tr><td><strong>Duration</strong></td><td>{{ $followUpAppointment->appointment_duration ?? 30 }} min</td></tr>
        <tr><td><strong>Type</strong></td><td>{{ ucfirst(str_replace('_',' ', $followUpAppointment->appointment_type ?? 'follow-up')) }}</td></tr>
        <tr><td><strong>Reason</strong></td><td>{{ $followUpAppointment->reason ?? 'Follow-up consultation' }}</td></tr>
    </table>
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">👨‍⚕️</span> Doctor</div>
    <p style="margin:0"><strong>Dr. {{ $doctor->user->name }}</strong> @if($doctor->specialty)— {{ $doctor->specialty->name }}@endif</p>
</div>

<div style="text-align:center;margin:18px 0;">
    <a href="{{ route('appointments.show', $followUpAppointment) }}" class="btn btn-primary">View Follow-up Details</a>
</div>

<p class="content-text" style="font-size:13px;color:#64748b">Need to reschedule? Please contact us at least {{ $doctor->cancellation_hours ?? 24 }} hours in advance.</p>
@endsection
