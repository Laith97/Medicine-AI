@extends('emails.layouts.master')

@section('title', 'Verify Your Review - ' . config('app.name'))
@section('email-title', '⭐ Verify Your Review')
@section('email-subtitle', 'Confirm your feedback for Dr. ' . $doctor->user->name)

@section('content')
<div class="greeting">Hello,</div>

<p class="content-text">Thank you for reviewing <strong>Dr. {{ $doctor->user->name }}</strong>. Your feedback helps other patients make informed decisions.</p>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">⭐</span> Your Review</div>
    <table class="data-table" style="margin-bottom:0">
        <tr><td><strong>Doctor</strong></td><td>{{ $doctor->user->name }} @if($doctor->specialty) — {{ $doctor->specialty->name }}@endif</td></tr>
        <tr><td><strong>Rating</strong></td><td><span style="color:#f59e0b">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span> ({{ $review->rating }}/5)</td></tr>
        @if($review->comment)<tr><td><strong>Comment</strong></td><td>"{{ $review->comment }}"</td></tr>@endif
        <tr><td><strong>Submitted</strong></td><td>{{ $review->created_at->format('M j, Y g:i A') }}</td></tr>
    </table>
</div>

<div class="alert alert-warning">
    <strong>⚠️ Verification Required:</strong> Please verify your email to publish your review.
</div>

<div style="text-align:center;margin:20px 0;">
    <a href="{{ $verificationUrl }}" class="btn btn-primary">✅ Verify &amp; Publish My Review</a>
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0">🔑</span> Alternative — Token</div>
    <p style="font-size:13px;color:#64748b;margin:0 0 8px">If the button doesn't work, enter this token manually:</p>
    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:12px;text-align:center;font-family:ui-monospace;font-weight:800;letter-spacing:0.06em;color:#0f172a">{{ $token }}</div>
</div>

<p class="content-text" style="font-size:13px;color:#64748b"><strong>What happens next?</strong> Your review will be published on Dr. {{ $doctor->user->name }}'s profile and you'll receive a confirmation. Link expires in 24 hours.</p>
@endsection
