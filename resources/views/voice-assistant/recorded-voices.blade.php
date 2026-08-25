@extends('master')

@section('title', 'Consultation History')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-history me-2"></i>Consultation History</h2>
                    <p>{{ $transcriptions->total() }} recorded sessions • Ambient listening</p>
                </div>
                <a href="{{ route('ai.ambient-listening.index') }}" class="doctor-btn doctor-btn-primary doctor-btn-sm">
                    <i class="fas fa-plus me-1"></i>New Session
                </a>
            </div>
        </div>

        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);"><i class="fas fa-microphone"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $transcriptions->total() }}</p><p class="stats-label">Total Sessions</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-check-circle"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $transcriptions->where('status','completed')->count() }}</p><p class="stats-label">Completed</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);"><i class="fas fa-brain"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $transcriptions->where('status','ai_analysis_complete')->count() }}</p><p class="stats-label">AI Analyzed</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-file-medical"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $transcriptions->whereNotNull('diagnosis')->count() }}</p><p class="stats-label">Diagnosed</p></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container pb-4">
        <div class="card border-0 shadow-sm cases-panel" style="overflow:hidden">
            @if($transcriptions->count() > 0)
                <div class="doctor-table-container" style="overflow:hidden">
                    <div style="overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none;">
                        <style>.doctor-table-container div::-webkit-scrollbar{display:none}</style>
                        <table class="doctor-table table-hover mb-0" style="width:100%">
                            <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);">
                                <tr>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="fas fa-user me-1 opacity-60"></i> Patient</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;max-width:260px">Transcription</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Status</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:center;width:90px">Duration</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="far fa-calendar me-1 opacity-60"></i> Recorded At</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:right;width:140px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transcriptions as $transcription)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $g = strtolower($transcription->patient->gender ?? '');
                                                    $cls = $g==='male' ? 'patient-avatar-male' : ($g==='female' ? 'patient-avatar-female' : 'patient-avatar-default');
                                                    $nm = $transcription->patient->name ?? 'Unknown';
                                                    $init = collect(explode(' ', $nm))->map(fn($w)=>substr($w,0,1))->take(2)->join('');
                                                    if(strlen($init)<2) $init = substr($nm,0,2);
                                                    $init = strtoupper($init);
                                                @endphp
                                                <div class="patient-avatar {{ $cls }} me-3" style="width:38px; height:38px; font-size:0.85rem;">{{ $init }}</div>
                                                <div class="min-w-0">
                                                    <div class="fw-medium text-dark text-truncate" style="max-width:160px;">{{ $transcription->patient->name ?? 'Unknown Patient' }}</div>
                                                    <small class="text-muted text-truncate d-block" style="max-width:160px;">{{ $transcription->patient->email ?? '' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><div class="text-truncate" style="max-width: 260px;" title="{{ $transcription->raw_transcription }}">{{ Str::limit($transcription->raw_transcription, 60) }}</div></td>
                                        <td>
                                            @switch($transcription->status)
                                                @case('active')<span class="doctor-badge doctor-badge-success">Active</span>@break
                                                @case('completed')<span class="doctor-badge doctor-badge-primary">Completed</span>@break
                                                @case('ai_analysis_complete')<span class="doctor-badge" style="background:#cff4fc; color:#055160; border:1px solid #b6effb;">AI Analyzed</span>@break
                                                @case('diagnosis_created')<span class="doctor-badge doctor-badge-success">Diagnosed</span>@break
                                                @default<span class="doctor-badge doctor-badge-secondary">{{ ucfirst($transcription->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td class="text-center">
                                            @if($transcription->session_started_at && $transcription->session_ended_at)
                                                <span class="doctor-badge doctor-badge-secondary">{{ $transcription->session_started_at->diffInSeconds($transcription->session_ended_at) }}s</span>
                                            @else<span class="text-muted">—</span>@endif
                                        </td>
                                        <td><small class="text-muted">{{ $transcription->created_at->format('M d, Y H:i') }}</small></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('ai.ambient-listening.show', $transcription) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                @if($transcription->diagnosis)
                                                    <a href="{{ route('diagnosis.show', $transcription->diagnosis) }}" class="doctor-btn doctor-btn-success doctor-btn-sm" title="Diagnosis"><i class="fas fa-file-medical"></i></a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer d-flex justify-content-center p-3" style="background:#f8fafc; border-top:1px solid #eef0f3;">
                        {{ $transcriptions->links() }}
                    </div>
                </div>
            @else
                <div class="doctor-empty-state">
                    <i class="fas fa-microphone-slash"></i>
                    <h5>No Session Recordings Yet</h5>
                    <p>Start ambient listening sessions to see them here.</p>
                    <a href="{{ route('ai.ambient-listening.index') }}" class="doctor-btn doctor-btn-primary"><i class="fas fa-microphone me-1"></i> Start Session</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
<style>
.patient-avatar { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:0.85rem; flex-shrink:0; }
.patient-avatar-male { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.patient-avatar-female { background: linear-gradient(135deg, #e83e8c 0%, #c21e56 100%); }
.patient-avatar-default { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
</style>