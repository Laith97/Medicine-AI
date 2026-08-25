@extends('master')

@section('title', 'Ambient Listening Session Details')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#1e293b;border-bottom:1px solid #0f172a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:rgba(255,255,255,0.12)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.18)!important}
.section-head-modern h5{color:#fff!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:rgba(255,255,255,0.75)!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.info-row{display:flex;align-items:center;gap:0.75rem;padding:0.85rem 0;border-bottom:1px solid #f1f5f9}
.info-row:last-child{border-bottom:none}
.info-row-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #eef2f7;color:#64748b;font-size:0.8rem;flex-shrink:0}
.badge-soft{padding:0.35rem 0.6rem;border-radius:99px;font-size:0.70rem;font-weight:700;border:1px solid transparent}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-file-medical me-2"></i>Ambient Listening Session</h2>
                    <p>{{ $transcription->patient ? e($transcription->patient->name) : 'Unknown Patient' }} · {{ $transcription->session_started_at ? $transcription->session_started_at->format('M d, Y g:i A') : 'Date not available' }} @if($transcription->session_id) · <span style="font-family:ui-monospace;font-size:0.78rem;opacity:0.85">{{ $transcription->session_id }}</span>@endif</p>
                </div>
                @php $prev = url()->previous(); $isRecorded = str_contains($prev, 'recorded-voices'); $backUrl = $prev !== url()->current() && (str_contains($prev, '/ai/ambient') || str_contains($prev, '/doctor/')) ? $prev : route('ai.ambient-listening.history'); $backLabel = $isRecorded ? 'Back to Recorded Voices' : 'Back to History'; @endphp
                <div class="d-flex gap-2">
                    <a href="{{ $backUrl }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>{{ $backLabel }}</a>
                    <a href="{{ route('ai.ambient-listening.index') }}" class="btn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-microphone me-2"></i>New Session</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-info-circle"></i></div><div><h5>Session Information</h5><p>Patient · doctor · timing · status</p></div></div></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-user"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">PATIENT</small><div style="font-weight:600;color:#0f172a">{{ $transcription->patient ? e($transcription->patient->name) : 'Unknown Patient' }}</div></div><small style="color:#64748b">{{ $transcription->patient ? e($transcription->patient->email ?? '') : '' }}</small></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-user-md"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">DOCTOR</small><div style="font-weight:600;color:#0f172a">{{ $transcription->doctor ? e($transcription->doctor->name) : 'Unknown Doctor' }}</div></div></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-clock"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">SESSION STARTED</small><div style="font-weight:600;color:#0f172a">{{ $transcription->session_started_at ? $transcription->session_started_at->format('M d, Y g:i A') : 'Not available' }}</div></div></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-hourglass-end"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">SESSION ENDED</small><div style="font-weight:600;color:#0f172a">{{ $transcription->session_ended_at ? $transcription->session_ended_at->format('M d, Y g:i A') : 'Not available' }}</div></div></div>
                    <div class="info-row">
                        <div class="info-row-icon"><i class="fas fa-tag"></i></div>
                        <div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">STATUS</small><div class="mt-1">@if($transcription->status==='completed')<span class="badge-soft" style="background:#d1fae5;color:#065f46;border-color:#a7f3d0"><i class="fas fa-check me-1"></i>Completed</span>@elseif($transcription->status==='active')<span class="badge-soft" style="background:#fef3c7;color:#92400e;border-color:#fde68a"><i class="fas fa-clock me-1"></i>Active</span>@else<span class="badge-soft" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0">{{ ucfirst($transcription->status) }}</span>@endif</div></div>
                        <div class="text-end"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">SESSION ID</small><div style="font-family:ui-monospace;font-size:0.74rem;color:#475569">{{ $transcription->session_id }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-chart-line"></i></div><div><h5>Session Statistics</h5><p>Transcription · AI · chart</p></div></div></div>
                    <div class="row g-3">
                        <div class="col-6"><div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">TRANSCRIPTION</small><div style="font-weight:700;color:#0f172a;font-size:1.1rem">{{ $transcription->raw_transcription ? strlen($transcription->raw_transcription) : 0 }} <small style="font-weight:400;color:#64748b;font-size:0.78rem">chars</small></div><small style="color:#475569">{{ $transcription->raw_transcription ? str_word_count($transcription->raw_transcription) : 0 }} words</small></div></div>
                        <div class="col-6"><div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">AI ANALYSIS</small><div style="font-weight:700;color:{{ $transcription->ai_analysis ? '#065f46' : '#64748b' }}">{{ $transcription->ai_analysis ? 'Generated' : 'Not generated' }}</div><small style="color:#475569">{{ $transcription->structured_chart ? 'Chart available' : 'No chart' }}</small></div></div>
                        <div class="col-12"><div class="d-flex gap-2 flex-wrap"><span class="badge-soft" style="background:#eff6ff;color:#2563eb;border-color:#dbeafe"><i class="fas fa-robot me-1"></i>AI: {{ $transcription->ai_analysis ? 'Yes' : 'No' }}</span><span class="badge-soft" style="background:#f8fafc;color:#475569;border-color:#e2e8f0"><i class="fas fa-clipboard me-1"></i>Chart: {{ $transcription->structured_chart ? 'Yes' : 'No' }}</span></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-volume-up"></i></div><div><h5>Session Audio</h5><p>Recording · format · download</p></div></div></div>
            @if($transcription->audio_file)
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8"><audio controls class="w-100" style="border-radius:8px"><source src="{{ asset('storage/'.$transcription->audio_file) }}" type="audio/{{ $transcription->audio_format ?? 'webm' }}">Your browser does not support audio.</audio></div>
                    <div class="col-lg-4">
                        <div class="row g-2" style="font-size:0.82rem">
                            <div class="col-6"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b">FORMAT</small><div><span class="badge-soft" style="background:#f1f5f9;color:#475569">{{ strtoupper($transcription->audio_format ?? 'WEBM') }}</span></div></div>
                            <div class="col-6"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b">SIZE</small><div style="font-weight:600;color:#0f172a">{{ $transcription->audio_file_size ? number_format($transcription->audio_file_size/1024,1).' KB' : 'Unknown' }}</div></div>
                            @if($transcription->audio_duration)<div class="col-6"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b">DURATION</small><div style="font-weight:600;color:#0f172a">{{ number_format($transcription->audio_duration,1) }} sec</div></div>@endif
                            <div class="col-6"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b">DOWNLOAD</small><div><a href="{{ asset('storage/'.$transcription->audio_file) }}" download class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#2563eb;border-radius:8px;font-size:0.78rem;font-weight:600"><i class="fas fa-download me-1"></i>Download</a></div></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:10px"><div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width:48px;height:48px;background:#fff;border:1px solid #e2e8f0;color:#94a3b8"><i class="fas fa-microphone-slash"></i></div><h6 style="font-weight:700;color:#475569">No Session Audio Available</h6><p style="color:#64748b;font-size:0.84rem;margin:0">Live transcription only · Audio not recorded or not enabled.</p></div>
            @endif
        </div>

        @if($transcription->raw_transcription)
            <div class="table-card">
                <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-microphone-alt"></i></div><div><h5>Raw Transcription</h5><p>{{ str_word_count($transcription->raw_transcription) }} words · scrollable</p></div></div></div>
                <div class="p-3" style="max-height:300px;overflow-y:auto;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><p style="white-space:pre-wrap;margin:0;font-size:0.92rem;color:#1e293b;line-height:1.6">{{ $transcription->raw_transcription }}</p></div>
            </div>
        @endif

        @if($transcription->structured_chart)
            <div class="table-card">
                <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-clipboard-list"></i></div><div><h5>Structured Medical Chart</h5><p>Symptoms · history · plan</p></div></div></div>
                @php $chartData = is_string($transcription->structured_chart) ? json_decode($transcription->structured_chart, true) : $transcription->structured_chart; @endphp
                @if($chartData)
                    <div class="row g-3">
                        @if(isset($chartData['symptoms']) && $chartData['symptoms'])<div class="col-md-6"><div class="p-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px"><h6 style="font-weight:700;color:#1e40af;font-size:0.84rem"><i class="fas fa-stethoscope me-1"></i>Symptoms</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['symptoms'] }}</p></div></div>@endif
                        @if(isset($chartData['medical_history']) && $chartData['medical_history'])<div class="col-md-6"><div class="p-3" style="background:#f0fdfa;border:1px solid #ccfbf1;border-radius:10px"><h6 style="font-weight:700;color:#0f766e;font-size:0.84rem">Medical History</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['medical_history'] }}</p></div></div>@endif
                        @if(isset($chartData['physical_findings']) && $chartData['physical_findings'])<div class="col-md-6"><div class="p-3" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px"><h6 style="font-weight:700;color:#92400e;font-size:0.84rem">Physical Findings</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['physical_findings'] }}</p></div></div>@endif
                        @if(isset($chartData['medications']) && $chartData['medications'])<div class="col-md-6"><div class="p-3" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px"><h6 style="font-weight:700;color:#065f46;font-size:0.84rem">Medications</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['medications'] }}</p></div></div>@endif
                        @if(isset($chartData['vital_signs']) && $chartData['vital_signs'])<div class="col-md-6"><div class="p-3" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px"><h6 style="font-weight:700;color:#991b1b;font-size:0.84rem">Vital Signs</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['vital_signs'] }}</p></div></div>@endif
                        @if(isset($chartData['diagnosis']) && $chartData['diagnosis'])<div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px"><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem">Diagnosis</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['diagnosis'] }}</p></div></div>@endif
                        @if(isset($chartData['care_plan']) && $chartData['care_plan'])<div class="col-12"><div class="p-3" style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px"><h6 style="font-weight:700;color:#6d28d9;font-size:0.84rem">Care Plan</h6><p style="font-size:0.88rem;color:#1e293b;margin:0">{{ $chartData['care_plan'] }}</p></div></div>@endif
                    </div>
                @else
                    <div class="text-center py-3" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:10px;color:#64748b"><i class="fas fa-exclamation-circle me-1"></i>No structured chart data available</div>
                @endif
            </div>
        @endif

        @if($transcription->ai_analysis)
            <div class="table-card">
                <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-robot"></i></div><div><h5>AI Clinical Analysis</h5><p>Generated analysis · scrollable</p></div></div></div>
                <div class="p-3" style="max-height:600px;overflow-y:auto;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div style="white-space:pre-wrap;font-size:0.92rem;color:#1e293b;line-height:1.6">{{ $transcription->ai_analysis }}</div></div>
            </div>
        @endif

        <div class="table-card">
            <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Actions</h5><p>Next steps</p></div></div></div>
            <div class="d-flex flex-wrap gap-2">
                @php $prev2 = url()->previous(); $isRecorded2 = str_contains($prev2, 'recorded-voices'); $backUrl2 = $prev2 !== url()->current() && (str_contains($prev2, '/ai/ambient') || str_contains($prev2, '/doctor/')) ? $prev2 : route('ai.ambient-listening.history'); $backLabel2 = $isRecorded2 ? 'Back to Recorded Voices' : 'Back to History'; @endphp
                <a href="{{ $backUrl2 }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1rem;font-weight:500;font-size:0.88rem"><i class="fas fa-arrow-left me-2"></i>{{ $backLabel2 }}</a>
                <a href="{{ route('ai.ambient-listening.index') }}" class="btn" style="background:#1e293b;color:#fff;border-radius:10px;padding:0.6rem 1rem;font-weight:600;font-size:0.88rem"><i class="fas fa-microphone me-2"></i>New Ambient Listening Session</a>
                @if($transcription->patient)<a href="{{ route('doctor.patients.show', $transcription->patient->id) }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#2563eb;border-radius:10px;padding:0.6rem 1rem;font-weight:600;font-size:0.88rem"><i class="fas fa-user me-2"></i>View Patient Profile</a>@endif
            </div>
        </div>
    </div>
</div>
@endsection
