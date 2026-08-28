@extends('emails.layouts.master')

@section('title', 'New Contact Message - ' . config('app.name'))
@section('email-title', '✉️ New Contact Message')
@section('email-subtitle', 'Someone contacted you via the website')
@section('preview', 'New message from ' . $contactName . ': ' . \Illuminate\Support\Str::limit($contactSubject, 50))

@section('content')
<div class="greeting">You have a new inquiry:</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">👤</span> Contact Details</div>
    <table class="data-table" style="margin-bottom:0">
        <tr><td style="width:130px"><strong>Full Name</strong></td><td>{{ $contactName }}</td></tr>
        <tr><td><strong>Email</strong></td><td><a href="mailto:{{ $contactEmail }}" style="color:#2563eb;text-decoration:none">{{ $contactEmail }}</a></td></tr>
        @if($contactPhone)<tr><td><strong>Phone</strong></td><td><a href="tel:{{ $contactPhone }}" style="color:#2563eb;text-decoration:none">{{ $contactPhone }}</a></td></tr>@endif
        @if($contactService)<tr><td><strong>Inquiry Type</strong></td><td><span class="badge badge-info">{{ $contactService }}</span></td></tr>@endif
        <tr><td><strong>Subject</strong></td><td>{{ $contactSubject }}</td></tr>
    </table>
</div>

<div class="alert alert-info" style="padding:18px">
    <div style="font-size:11px;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;color:#1e40af;margin-bottom:6px">Message</div>
    <div style="font-size:14px;color:#1e293b;line-height:1.7;white-space:pre-wrap">{{ $messageContent }}</div>
</div>

<p class="content-text" style="font-size:13px;color:#64748b;text-align:center">Reply directly to this email to respond to {{ $contactName }}.</p>
@endsection
