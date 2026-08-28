@extends('emails.layouts.master')

@section('title', 'System Alert - ' . config('app.name'))
@section('email-title', '🚨 System Alert')
@section('email-subtitle', 'Email system monitoring — ' . count($issues) . ' issue(s) detected')

@section('content')
<div class="alert alert-danger">
    <strong>⚠️ Email System Issues Detected</strong><br>
    Automated monitoring found <strong>{{ count($issues) }} issue(s)</strong>.
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon" style="background:#dc2626">!</span> Issues Detected</div>
    @foreach($issues as $issue)
        <div style="background:#fff;border:1px solid #eef2f7;border-left:4px solid #dc2626;border-radius:8px;padding:12px;margin:8px 0;font-size:13px;color:#334155">• {{ $issue }}</div>
    @endforeach
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon">🖥️</span> System Information</div>
    <table class="data-table" style="margin-bottom:0">
        <tr><td><strong>Timestamp</strong></td><td style="font-family:ui-monospace;font-size:12px">{{ $timestamp }}</td></tr>
        <tr><td><strong>Server</strong></td><td>{{ $server }}</td></tr>
        <tr><td><strong>Environment</strong></td><td><span class="badge badge-info">{{ config('app.env') }}</span></td></tr>
        <tr><td><strong>Application</strong></td><td>{{ config('app.name') }}</td></tr>
    </table>
</div>

<div class="info-card">
    <div class="info-card-header"><span class="info-card-icon" style="background:#f59e0b">🛠️</span> Recommended Actions</div>
    <ol style="margin:0;padding-left:18px;font-size:13px;line-height:1.8;color:#334155">
        <li>Check logs: <code style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:2px 6px;font-size:12px">storage/logs/laravel.log</code></li>
        <li>Verify <code>.env</code> mail config</li>
        <li>Run <code style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:2px 6px;font-size:12px">php artisan email:health-check</code></li>
        <li>Check SMTP credentials &amp; DNS (SPF, DMARC, MX)</li>
    </ol>
</div>
@endsection
