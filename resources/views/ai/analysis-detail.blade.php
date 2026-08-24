@extends('master')

@section('title', 'AI Analysis Details')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.btn-back{background:rgba(255,255,255,0.15)!important;border:1px solid rgba(255,255,255,0.32)!important;color:#fff!important;border-radius:10px!important;padding:0.5rem 1rem!important;font-weight:600!important;font-size:0.83rem!important}
.btn-back:hover{background:#fff!important;color:#1e3a8a!important;border-color:#fff!important}
.status-badge{background:#fff!important;color:#1e293b!important;border:1px solid #e2e8f0!important;border-radius:99px!important;padding:0.38rem 0.85rem!important;font-size:0.73rem!important;font-weight:700!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h4,.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0}
.section-head-modern p{color:#475569!important;font-weight:500!important;margin:2px 0 0;font-size:0.78rem}
.info-rows{display:flex;flex-direction:column;gap:0}
.info-row{display:flex;align-items:center;gap:0.75rem;padding:0.85rem 0;border-bottom:1px solid #f1f5f9}
.info-row:last-child{border-bottom:none}
.info-row-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #eef2f7;color:#64748b;font-size:0.8rem;flex-shrink:0}
.info-row-label{font-size:0.72rem;font-weight:600;color:#64748b;letter-spacing:0.04em;text-transform:uppercase}
.info-row-value{font-size:0.88rem;font-weight:600;color:#0f172a}
.compliance-footer{background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:1rem}
</style>
@endpush

@section('content')
@php
    $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : json_decode($analysis->analysis_data, true);
    $patient = $analysis->patient ?? ($analysis->appointment ? $analysis->appointment->patient : null);
    $guestName = $analysis->guest_name ?? $analysis->appointment?->guest_name;
    $guestEmail = $analysis->guest_email ?? $analysis->appointment?->guest_email;
    $statusMap = [
        'active'   => ['bg'=>'#fef3c7','color'=>'#92400e','border'=>'#fde68a'],
        'reviewed' => ['bg'=>'#ecfdf5','color'=>'#065f46','border'=>'#a7f3d0'],
        'archived' => ['bg'=>'#f1f5f9','color'=>'#475569','border'=>'#e2e8f0'],
    ];
    $statusStyle = $statusMap[$analysis->status] ?? $statusMap['archived'];
@endphp

<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-brain me-2"></i>AI Analysis Details</h2>
                    <p><i class="fas fa-hashtag me-1"></i>{{ $analysis->appointment?->appointment_number ?? '#'.$analysis->id }} · {{ $analysis->generated_at->format('M j, Y \a\t g:i A') }} @if($patient) · {{ e($patient->name) }} @elseif($guestName) · {{ e($guestName) }} @endif</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="status-badge" style="background:{{ $statusStyle['bg'] }}!important;color:{{ $statusStyle['color'] }}!important;border:1px solid {{ $statusStyle['border'] }}!important">{{ ucfirst($analysis->status) }}</span>
                </div>
            </div>
        </div>

        <!-- Overview -->
        <div class="table-card">
            <div class="section-head-modern">
                <div class="head-left">
                    <div class="head-icon"><i class="fas fa-brain"></i></div>
                    <div><h5>AI Medical Copilot Analysis</h5><p>Clinical decision support · Generated {{ $analysis->generated_at->format('M j, Y g:i A') }}</p></div>
                </div>
                @if($analysis->appointment)
                    <a href="{{ route('doctor.appointments.show', $analysis->appointment->id) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.78rem;font-weight:600"><i class="fas fa-calendar me-1"></i>View Appointment</a>
                @endif
            </div>
            <div class="info-rows">
                <div class="info-row">
                    <div class="info-row-icon"><i class="fas fa-user-md"></i></div>
                    <div class="flex-grow-1"><span class="info-row-label">Generated By</span><br><span class="info-row-value">{{ e($analysis->doctor ? $analysis->doctor->name : 'Unknown Doctor') }}</span></div>
                    <small style="color:#64748b;font-size:0.78rem">{{ $analysis->generated_at->diffForHumans() }}</small>
                </div>
                <div class="info-row">
                    <div class="info-row-icon"><i class="fas fa-user"></i></div>
                    <div class="flex-grow-1"><span class="info-row-label">Patient</span><br><span class="info-row-value">@if($patient){{ e($patient->name) }} @if($patient->date_of_birth) <span style="font-weight:400;color:#64748b;font-size:0.82rem">({{ $patient->date_of_birth->age }}y)</span>@endif @elseif($guestName){{ e($guestName) }} <span style="font-weight:400;color:#64748b;font-size:0.82rem">Guest @if($guestEmail) · {{ e($guestEmail) }}@endif</span> @else Unknown @endif</span></div>
                    @if($patient)<a href="{{ route('doctor.patients.show', $patient->id) }}" class="btn btn-sm" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.74rem">View Patient</a>@endif
                </div>
                @if($analysis->appointment)
                <div class="info-row">
                    <div class="info-row-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="flex-grow-1"><span class="info-row-label">Appointment</span><br><span class="info-row-value" style="font-size:0.84rem">{{ $analysis->appointment->appointment_date->format('M j, Y g:i A') }} · {{ e($analysis->appointment->reason ?? '—') }}</span></div>
                    <span class="badge bg-light text-muted border" style="font-size:0.70rem">{{ ucfirst($analysis->appointment->status ?? '') }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Summary -->
        @if(isset($analysisData['medical_case_summary']) && !empty($analysisData['medical_case_summary']))
        <div class="table-card">
            <div class="section-head-modern">
                <div class="head-left"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-file-medical"></i></div><div><h5>Medical Case Summary</h5><p>AI-generated draft · Physician verified</p></div></div>
                <span class="badge bg-light text-muted border" style="font-size:0.70rem">AI</span>
            </div>
            <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:1rem;font-size:0.92rem;color:#1e293b;line-height:1.6">{{ e($analysisData['medical_case_summary']) }}</div>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                @if(isset($analysisData['differential_considerations']) && !empty($analysisData['differential_considerations']))
                <div class="table-card h-100">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#fffbeb!important;color:#92400e!important;border-color:#fde68a!important"><i class="fas fa-list-check"></i></div><div><h5>Differential Considerations</h5><p>Not diagnoses · Physician judgment required</p></div></div></div>
                    <div class="p-0">
                        <p class="small mb-3" style="color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:0.6rem 0.75rem;font-size:0.78rem"><i class="fas fa-exclamation-triangle me-1"></i> Suggestions only — not diagnoses.</p>
                        <ul class="mb-0" style="list-style:none;padding:0">
                            @foreach($analysisData['differential_considerations'] as $consideration)
                                @if(is_array($consideration) && isset($consideration['consideration']))
                                    <li class="mb-3 p-2" style="background:#fff;border:1px solid #f1f5f9;border-radius:8px"><strong style="font-size:0.88rem;color:#0f172a">{{ e($consideration['consideration']) }}</strong>@if(isset($consideration['rationale']))<br><small style="color:#64748b">{{ e($consideration['rationale']) }}</small>@endif</li>
                                @else
                                    <li class="mb-2" style="font-size:0.88rem;color:#334155"><i class="fas fa-circle me-2" style="font-size:0.4rem;color:#f59e0b;vertical-align:middle"></i>{{ e($consideration) }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-lg-6">
                @if(isset($analysisData['red_flags']) && !empty($analysisData['red_flags']))
                    <div class="table-card h-100">
                        <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#fef2f2!important;color:#dc2626!important;border-color:#fecaca!important"><i class="fas fa-flag"></i></div><div><h5>Red Flags</h5><p>Consider urgent evaluation if indicated</p></div></div></div>
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:0.9rem">
                            <ul class="mb-0" style="list-style:none;padding:0">
                                @foreach($analysisData['red_flags'] as $flag)
                                    <li class="mb-2 text-danger" style="font-size:0.88rem"><i class="fas fa-exclamation-triangle me-2"></i>{{ e($flag) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <div class="table-card h-100">
                        <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#ecfdf5!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-check-circle"></i></div><div><h5>Red Flags</h5><p>No immediate flags detected</p></div></div></div>
                        <div class="text-center py-3" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px"><i class="fas fa-check-circle text-success fs-4 mb-2"></i><p class="mb-0 fw-semibold" style="color:#065f46;font-size:0.88rem">No immediate red flags</p><small style="color:#64748b">Based on available data</small></div>
                    </div>
                @endif
            </div>
        </div>

        @if(isset($analysisData['follow_up_questions']) && !empty($analysisData['follow_up_questions']))
        <div class="table-card">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#f8fafc!important;color:#0e7490!important;border-color:#e2e8f0!important"><i class="fas fa-question-circle"></i></div><div><h5>Follow-up Questions</h5><p>To complete clinical picture & reduce oversight</p></div></div></div>
            <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:1rem">
                <ul class="mb-0" style="list-style:none;padding:0">
                    @foreach($analysisData['follow_up_questions'] as $question)
                        <li class="mb-2" style="font-size:0.88rem;color:#334155"><i class="fas fa-question-circle me-2" style="color:#0e7490"></i>{{ e($question) }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(isset($analysisData['patient_history']) && !empty($analysisData['patient_history']))
        <div class="table-card">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-history"></i></div><div><h5>Patient History Considered</h5><p>Context used in AI analysis</p></div></div></div>
            @php $history = $analysisData['patient_history']; @endphp
            @if(isset($history['chronic_conditions']) && !empty($history['chronic_conditions']))
                <div class="mb-3"><h6 style="font-size:0.82rem;font-weight:700;color:#1e293b"><i class="fas fa-heartbeat me-1" style="color:#475569"></i>Chronic Conditions</h6>
                    @foreach($history['chronic_conditions'] as $condition)<span class="badge bg-light text-muted border me-1 mb-1" style="font-size:0.72rem">{{ $condition }}</span>@endforeach
                </div>
            @endif
            @if(isset($history['previous_diagnoses']) && !empty($history['previous_diagnoses']))
                <div class="mb-3"><h6 style="font-size:0.82rem;font-weight:700;color:#1e293b"><i class="fas fa-stethoscope me-1" style="color:#475569"></i>Previous Diagnoses</h6>
                    <ul style="font-size:0.84rem;color:#334155">@foreach($history['previous_diagnoses'] as $d)<li>{{ $d }}</li>@endforeach</ul>
                </div>
            @endif
            @if(isset($history['previous_ai_analyses']) && !empty($history['previous_ai_analyses']))
                <div><h6 style="font-size:0.82rem;font-weight:700;color:#1e293b"><i class="fas fa-brain me-1" style="color:#475569"></i>Previous AI Analyses</h6>
                    @foreach($history['previous_ai_analyses'] as $prevAnalysis)
                        <div class="p-2 mb-2" style="background:#f8fafc;border-left:3px solid #e2e8f0;border-radius:8px"><small style="color:#64748b">{{ $prevAnalysis['generated_at'] ?? 'Unknown date' }}</small><p class="mb-1" style="font-size:0.84rem;color:#334155">{{ $prevAnalysis['summary'] ?? 'No summary' }}</p>@if(isset($prevAnalysis['red_flags']) && !empty($prevAnalysis['red_flags']))<small class="text-danger">⚠️ {{ implode(', ', $prevAnalysis['red_flags']) }}</small>@else<small class="text-success">✅ No red flags</small>@endif</div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        @if($analysis->reviewed_at && $analysis->reviewer)
        <div class="table-card" style="border-left:3px solid #10b981">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#ecfdf5!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-user-md"></i></div><div><h5>Physician Review</h5><p>Reviewed {{ $analysis->reviewed_at->format('M j, Y g:i A') }}</p></div></div></div>
            <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:1rem">
                <div style="font-size:0.84rem;color:#334155"><strong>Reviewed by:</strong> {{ e($analysis->reviewer->name) }} <small style="color:#64748b">on {{ $analysis->reviewed_at->format('M j, Y g:i A') }}</small></div>
                @if($analysis->doctor_notes)<div class="mt-2" style="font-size:0.88rem;color:#0f172a">{{ e($analysis->doctor_notes) }}</div>@else<em style="color:#94a3b8;font-size:0.84rem">No additional notes.</em>@endif
            </div>
        </div>
        @endif

        <div class="table-card">
            <div class="compliance-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;color:#2563eb"><i class="fas fa-shield-alt"></i></div>
                    <div><h6 style="font-size:0.84rem;font-weight:700;color:#1e293b;margin:0">Compliance</h6><p style="font-size:0.74rem;color:#64748b;margin:0">AI for decision support only - Physician judgment required. No patient data leaves environment.</p></div>
                </div>
                <small style="color:#94a3b8;font-size:0.72rem;text-align:right">Generated {{ $analysis->generated_at->format('M j, Y g:i A') }}<br>AI-copilot-clinical-v1.1</small>
            </div>
        </div>
    </div>
</div>
@endsection

