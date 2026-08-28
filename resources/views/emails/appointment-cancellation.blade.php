@extends('emails.layouts.master')

@section('title', 'Appointment Cancelled - ' . config('app.name'))
@section('email-title', '❌ Appointment Cancelled')
@section('email-subtitle', 'Your appointment on ' . $appointment->appointment_date->format('M j, Y') . ' was cancelled')

@section('content')
<div class="greeting">Hello {{ $patient->name ?? $appointment->patient_name }},</div>

<div class="alert alert-danger">
    <strong>Your appointment was cancelled.</strong> See details below.
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon" style="background:#dc2626">📅</span> Cancelled Appointment</div>
    <table class="data-table">
        <tr><td><strong>Date &amp; Time</strong></td><td>{{ $appointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</td></tr>
        <tr><td><strong>Duration</strong></td><td>{{ $appointment->appointment_duration ?? 30 }} minutes</td></tr>
        <tr><td><strong>Type</strong></td><td>{{ ucfirst(str_replace('_',' ', $appointment->appointment_type ?? 'general')) }}</td></tr>
        <tr><td><strong>Reason</strong></td><td>{{ $appointment->reason ?? 'General consultation' }}</td></tr>
    </table>
</div>

@if($reason)
<div class="alert alert-warning">
    <strong>📝 Cancellation Reason:</strong><br>{{ $reason }}
</div>
@endif

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">👨‍⚕️</span> Doctor</div>
    <p style="margin:0"><strong>Dr. {{ $doctor->user->name }}</strong> @if($doctor->specialty)— {{ $doctor->specialty->name }}@endif</p>
    @if($doctor->phone)<p style="margin:4px 0 0;color:#64748b;font-size:13px">📞 {{ $doctor->phone }}</p>@endif
</div>

<div style="text-align:center;margin:20px 0;">
    <a href="{{ url('/doctors') }}" class="btn btn-primary">Find a Doctor &amp; Rebook</a>
</div>

<p class="content-text" style="font-size:13px;color:#64748b">We apologize for any inconvenience. You can schedule a new appointment at any time.</p>
@endsection
