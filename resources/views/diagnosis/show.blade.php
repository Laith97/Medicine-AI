@extends('master')

@section('title', 'Diagnosis Details')

@push('styles')
<style>
.page-hero{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.75rem 0}
.page-hero h2{font-size:1.45rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0}
.page-hero p{font-size:0.875rem;color:#64748b;margin:0.25rem 0 0}
.card-modern{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.card-modern-header{padding:0.85rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
.card-modern-header h5{font-size:0.95rem;font-weight:600;color:#0f172a;margin:0;display:flex;align-items:center;gap:0.5rem}
.card-modern-body{padding:1.25rem}
.badge-soft{font-size:0.72rem;font-weight:600;padding:0.3rem 0.6rem;border-radius:99px;border:1px solid #e2e8f0;background:#f1f5f9;color:#475569}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background:#f8fafc">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0"><i class="fas fa-clipboard-check me-2" style="color:#DE6262"></i>Diagnosis Details</h2>
                <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0">Created on {{ $diagnosis->created_at->format('F j, Y \a\t g:i A') }} · Type {{ ucfirst($diagnosis->type ?? 'Text') }}</p>
            </div>
            @php
                $prevDiag = url()->previous();
                $diagBackUrl = $prevDiag !== url()->current() ? $prevDiag : route('doctor.cases.overview');
            @endphp
            <a href="{{ $diagBackUrl }}" class="btn" style="background:#ffffff;border:1px solid #e2e8f0;color:#334155;border-radius:8px;padding:0.55rem 1rem;font-weight:500;font-size:0.84rem"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9">
            <!-- Patient Information -->
            <div class="card-modern mb-4">
                <div class="card-modern-header">
                    <h5><i class="fas fa-user" style="color:#6366f1"></i> Patient Information</h5>
                    <span class="badge-soft">ID {{ $diagnosis->patient->id }}</span>
                </div>
                <div class="card-modern-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:48px;height:48px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b"><i class="fas fa-user"></i></span>
                        <div>
                            <div style="font-weight:600;color:#0f172a">{{ $diagnosis->patient->name }}</div>
                            <div style="font-size:0.84rem;color:#64748b">{{ $diagnosis->patient->email }} @if($diagnosis->patient->phone) · {{ $diagnosis->patient->phone }} @endif</div>
                        </div>
                        <div class="ms-auto d-flex gap-2">
                            <span class="badge-soft">Age {{ $diagnosis->patient->age ?? 'N/A' }}</span>
                            <span class="badge-soft">{{ ucfirst($diagnosis->patient->gender ?? 'N/A') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnosis -->
            <div class="card-modern mb-4">
                <div class="card-modern-header">
                    <h5><i class="fas fa-stethoscope" style="color:#0ea5e9"></i> Diagnosis</h5>
                    <span class="badge-soft" style="background:{{ ($diagnosis->type ?? 'text') === 'ai' ? 'rgba(124,58,237,0.08)' : 'rgba(16,185,129,0.08)' }};color:{{ ($diagnosis->type ?? 'text') === 'ai' ? '#7c3aed' : '#059669' }};border-color:{{ ($diagnosis->type ?? 'text') === 'ai' ? 'rgba(124,58,237,0.15)' : 'rgba(16,185,129,0.15)' }}"><i class="fas fa-{{ ($diagnosis->type ?? 'text') === 'ai' ? 'robot' : 'user-md' }} me-1"></i> {{ ucfirst($diagnosis->type ?? 'Text') }}</span>
                </div>
                <div class="card-modern-body">
                    <h6 style="font-size:0.84rem;font-weight:600;color:#334155;margin:0 0 0.5rem">Diagnosis Text</h6>
                    <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:1rem;font-size:0.9rem;color:#334155;line-height:1.6">{!! nl2br(e($diagnosis->diagnosis_text ?? 'No diagnosis text')) !!}</div>

                    <div class="mt-4">
                        <h6 style="font-size:0.84rem;font-weight:600;color:#334155"><i class="fas fa-microphone me-2" style="color:#f59e0b"></i>Voice Notes ({{ count($diagnosis->voice_transcripts ?? []) }})</h6>
                        @if($diagnosis->voice_transcripts && count($diagnosis->voice_transcripts) > 0)
                            @foreach($diagnosis->voice_transcripts as $index => $transcript)
                                @if($transcript && $transcript !== $diagnosis->diagnosis_text)
                                    <div class="p-3 mb-2" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span style="font-size:0.78rem;font-weight:600;color:#92400e">Voice Note {{ $index + 1 }}</span>
                                            @if(isset($diagnosis->voice_files[$index]) && (!empty(trim($diagnosis->voice_files[$index])) || $diagnosis->voice_transcripts[$index]))
                                                <button class="btn btn-sm" style="background:#ffffff;border:1px solid #e2e8f0;color:#334155;border-radius:8px;font-size:0.72rem" onclick="playVoiceFile({{ $index }})"><i class="fas fa-play me-1"></i>Play</button>
                                            @else
                                                <span style="font-size:0.72rem;color:#94a3b8">Audio not available</span>
                                            @endif
                                        </div>
                                        <div style="font-size:0.875rem;color:#475569">{!! nl2br(e($transcript)) !!}</div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="text-center py-3" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;font-size:0.84rem;color:#94a3b8">No voice notes — Type: {{ ucfirst($diagnosis->type ?? 'Text') }}</div>
                        @endif
                    </div>

                    <div class="mt-4">
                        <h6 style="font-size:0.84rem;font-weight:600;color:#334155"><i class="fas fa-robot me-2" style="color:#7c3aed"></i>AI Analysis</h6>
                        @if($diagnosis->ai_response)
                            <div style="background:#f5f3ff;border:1px solid #ede9fe;border-radius:8px;padding:1rem;font-size:0.875rem;color:#475569">{!! nl2br(e($diagnosis->ai_response)) !!}</div>
                        @else
                            <div class="text-center py-3" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;font-size:0.84rem;color:#94a3b8">No AI analysis yet — will appear after AI processing</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Patient Data -->
            <div class="card-modern mb-4">
                <div class="card-modern-header">
                    <h5><i class="fas fa-notes-medical" style="color:#0ea5e9"></i> Additional Patient Data</h5>
                    <span class="badge-soft">{{ count($diagnosis->patient_data ?? []) }} fields</span>
                </div>
                <div class="card-modern-body">
                    @php
                        $expectedKeys = ['symptoms','past_medical_history','past_medications','allergies','clinical_notes','vitals','heart_rate','blood_pressure','temperature'];
                    @endphp
                    <div class="row g-3">
                        @foreach($expectedKeys as $key)
                            <div class="col-md-6">
                                <div class="p-3 h-100" style="background:#ffffff;border:1px solid #f1f5f9;border-radius:8px">
                                    <h6 style="font-size:0.78rem;font-weight:600;color:#0f172a;text-transform:capitalize;margin:0 0 0.5rem">{{ str_replace('_',' ', $key) }}</h6>
                                    <div style="font-size:0.84rem;color:#475569;min-height:24px">
                                        @if(isset($diagnosis->patient_data[$key]) && $diagnosis->patient_data[$key])
                                            @if(is_array($diagnosis->patient_data[$key]))
                                                {{ implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $diagnosis->patient_data[$key])) }}
                                            @else
                                                {{ $diagnosis->patient_data[$key] }}
                                            @endif
                                        @else
                                            <span style="color:#94a3b8;font-style:italic">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Activity -->
            <div class="card-modern mb-4">
                <div class="card-modern-header">
                    <h5><i class="fas fa-chart-line" style="color:#10b981"></i> Patient Activity</h5>
                </div>
                <div class="card-modern-body">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px">
                                <i class="fas fa-eye fa-lg mb-2" style="color:{{ $diagnosis->patient_viewed_at ? '#10b981' : '#94a3b8' }}"></i>
                                <div style="font-size:0.78rem;font-weight:600;color:#0f172a">Viewed</div>
                                <small style="font-size:0.72rem;color:{{ $diagnosis->patient_viewed_at ? '#10b981' : '#94a3b8' }}">{{ $diagnosis->patient_viewed_at ? $diagnosis->patient_viewed_at->format('M j, g:i A') : 'Not viewed yet' }}</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px">
                                <i class="fas fa-comments fa-lg mb-2" style="color:{{ $diagnosis->follow_up_count > 0 ? '#0ea5e9' : '#94a3b8' }}"></i>
                                <div style="font-size:0.78rem;font-weight:600;color:#0f172a">Follow-ups</div>
                                <small style="font-size:0.72rem;color:#64748b">{{ $diagnosis->follow_up_count }}/5 asked</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px">
                                <i class="fas fa-star fa-lg mb-2" style="color:{{ $diagnosis->patient_reviewed ? '#f59e0b' : '#94a3b8' }}"></i>
                                <div style="font-size:0.78rem;font-weight:600;color:#0f172a">Review</div>
                                <small style="font-size:0.72rem;color:{{ $diagnosis->patient_reviewed ? '#10b981' : '#94a3b8' }}">{{ $diagnosis->patient_reviewed ? 'Reviewed' : 'Not reviewed' }}</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px">
                                <i class="fas fa-bell fa-lg mb-2" style="color:{{ $diagnosis->patient_notified ? '#10b981' : '#94a3b8' }}"></i>
                                <div style="font-size:0.78rem;font-weight:600;color:#0f172a">Notified</div>
                                <small style="font-size:0.72rem;color:{{ $diagnosis->patient_notified ? '#10b981' : '#94a3b8' }}">{{ $diagnosis->patient_notified ? 'Patient notified' : 'Not notified' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card-modern">
                <div class="card-modern-body d-flex flex-wrap gap-2 justify-content-center">
                    @php
                        $prevDiag2 = url()->previous();
                        $diagBackUrl2 = $prevDiag2 !== url()->current() ? $prevDiag2 : route('doctor.cases.overview');
                        $diagBackLabel2 = 'Back';
                    @endphp
                    <a href="{{ $diagBackUrl2 }}" class="btn" style="background:#ffffff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.6rem 1rem;font-weight:500;font-size:0.84rem"><i class="fas fa-arrow-left me-2"></i>{{ $diagBackLabel2 }}</a>
                    <button class="btn" style="background:#ffffff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.6rem 1rem;font-weight:500;font-size:0.84rem" onclick="copyDiagnosisLink()"><i class="fas fa-link me-2"></i>Copy Patient Link</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function playVoiceFile(index = 0) {
    const audio = new Audio(`/diagnosis/{{ $diagnosis->id }}/voice?file=${index}`);
    const btn = document.querySelector(`button[onclick="playVoiceFile(${index})"]`);
    if(btn){ const orig=btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Loading...'; btn.disabled=true; const reset=()=>{btn.innerHTML=orig;btn.disabled=false}; audio.addEventListener('ended',reset); audio.addEventListener('error',()=>{reset();alert('Error playing voice file')}); audio.play().catch(()=>{reset();alert('Could not play voice file')}); }
}
function copyDiagnosisLink(){ const link='{{ route("diagnosis.patient.view", $diagnosis) }}'; navigator.clipboard.writeText(link).then(()=>alert('Patient link copied!'),()=>alert('Failed: '+link)); }
</script>
@endsection