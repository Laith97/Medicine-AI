@extends('emails.layouts.master')

@section('title', 'Appointment Completed - ' . config('app.name'))
@section('email-title', '✅ Appointment Completed')
@section('email-subtitle', 'Thank you for visiting Dr. ' . $doctor->user->name)

@section('content')
<div class="greeting">Hello {{ $patient->name ?? $appointment->patient_name }},</div>

<div class="alert alert-info">
    Your appointment has been <strong>successfully completed</strong>. Thank you for choosing {{ config('app.name') }}.
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon" style="background:#2563eb">📅</span> Completed Appointment</div>
    <table class="data-table">
        <tr><td><strong>Date &amp; Time</strong></td><td>{{ $appointment->appointment_date->format('l, F j, Y \a\t g:i A') }}</td></tr>
        <tr><td><strong>Duration</strong></td><td>{{ $appointment->appointment_duration ?? 30 }} minutes</td></tr>
        <tr><td><strong>Type</strong></td><td>{{ ucfirst(str_replace('_',' ', $appointment->appointment_type ?? 'general')) }}</td></tr>
        <tr><td><strong>Reason</strong></td><td>{{ $appointment->reason ?? 'General consultation' }}</td></tr>
    </table>
</div>

@if($diagnosis)
<div class="alert alert-info">
    <strong>🏥 Diagnosis Summary</strong><br>
    <span style="font-size:14px"><strong>{{ $diagnosis->name }}</strong> @if($diagnosis->description) — {{ $diagnosis->description }} @endif</span>
    @if($diagnosis->treatment)<div style="margin-top:6px;font-size:13px"><strong>Treatment:</strong> {{ $diagnosis->treatment }}</div>@endif
</div>
@endif

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">👨‍⚕️</span> Your Doctor</div>
    <p style="margin:0"><strong>Dr. {{ $doctor->user->name }}</strong> @if($doctor->specialty)— {{ $doctor->specialty->name }}@endif</p>
</div>

<div class="info-card" style="background:#f8fafc">
    <div class="info-card-header"><span class="info-card-icon" style="background:#1e293b">📋</span> Follow-up Care</div>
    <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#334155">
        <li>Follow your doctor's instructions</li>
        <li>Take prescribed medications as directed</li>
        <li>Schedule follow-ups if recommended</li>
        <li>Contact us if symptoms worsen</li>
    </ul>
</div>

<div style="text-align:center;margin:20px 0;">
    <a href="{{ route('appointments.index') }}" class="btn btn-primary">View Your Records</a>
</div>
@endsection
