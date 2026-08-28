@extends('emails.layouts.master')

@section('title', 'Your Medical Account Created - ' . config('app.name'))
@section('email-title', '🏥 Medical Account Created')
@section('email-subtitle', 'Dr. ' . $doctor->name . ' created your secure account')

@section('content')
<div class="greeting">Hello {{ $patient->name }},</div>

<p class="content-text">Great news! Your medical account has been created and you can now access your diagnosis online.</p>

<div class="alert alert-success" style="text-align:center">
    <div style="font-weight:800;font-size:16px;margin-bottom:10px">🔐 Your Login Credentials</div>
    <div style="background:#fff;border:1px solid #a7f3d0;border-radius:8px;padding:12px;margin:12px 0">
        <div style="font-size:13px;color:#065f46">Email: <strong>{{ $patient->email }}</strong></div>
        <div style="margin-top:6px;font-family:ui-monospace;font-size:16px;font-weight:800;letter-spacing:0.04em;color:#0f172a;background:#f0fdf4;padding:6px 10px;border-radius:6px;display:inline-block">{{ $tempPassword }}</div>
        <div style="font-size:11px;color:#64748b;margin-top:6px">Temporary password — please change after first login</div>
    </div>
    <a href="{{ $loginUrl }}" class="btn btn-primary">🚀 Login to View Diagnosis</a>
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">📋</span> Diagnosis Information</div>
    <table class="data-table" style="margin-bottom:0">
        <tr><td><strong>Doctor</strong></td><td>Dr. {{ $doctor->name }}</td></tr>
        <tr><td><strong>Date</strong></td><td>{{ $diagnosis->created_at->format('F j, Y g:i A') }}</td></tr>
        <tr><td><strong>Type</strong></td><td><span class="badge badge-info">{{ ucfirst($diagnosis->type) }}</span></td></tr>
    </table>
</div>

<div class="info-card" style="background:#f8fafc">
    <div class="info-card-header"><span class="info-card-icon" style="background:#1e293b">✨</span> What you can do</div>
    <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#334155">
        <li>View your complete diagnosis</li>
        <li>Ask up to 5 follow-up questions with AI</li>
        <li>Rate and review your doctor</li>
        <li>Access medical history</li>
    </ul>
</div>

<div class="alert alert-warning" style="font-size:13px">
    <strong>🔒 Security:</strong> Please change your password after first login, keep credentials confidential, and log out after each session.
</div>
@endsection
