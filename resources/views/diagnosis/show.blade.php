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
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-clipboard-check" style="color:#fff;font-size:1.1rem"></i></div>
                <div>
                    <h2 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Diagnosis Details</h2>
                    <p style="font-size:0.78rem;color:rgba(255,255,255,0.78);margin:2px 0 0">Created {{ $diagnosis->created_at->format('M d, Y H:i') }} · Type {{ ucfirst($diagnosis->type ?? 'Text') }}</p>
                </div>
            </div>
            @php
                $prevDiag = url()->previous();
                $diagBackUrl = $prevDiag !== url()->current() ? $prevDiag : route('doctor.cases.overview');
            @endphp
            <a href="{{ $diagBackUrl }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;padding:0.55rem 1rem;font-weight:700"><i class="fas fa-arrow-left me-2"></i>Back</a>
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

                    @php
                        $ambientVt = \App\Models\VoiceTranscription::where(function($q) use ($diagnosis){ $q->where('diagnosis_id', $diagnosis->id); if($diagnosis->patient_id) $q->orWhere('patient_id', $diagnosis->patient_id); })->where(function($q){ $q->whereNotNull('ai_analysis')->orWhereNotNull('raw_transcription'); })->latest()->first();
                        $isVoiceType = ($diagnosis->type ?? '') === 'voice_assistant';
                        $hasVoiceTranscript = !empty(trim((string)($diagnosis->voice_transcript ?? ''))) || ($ambientVt && !empty(trim((string)($ambientVt->raw_transcription ?? ''))));
                        $transcriptText = trim((string)($diagnosis->voice_transcript ?? ($ambientVt->raw_transcription ?? '')));
                        $aiText = trim((string)($diagnosis->ai_response ?? ($ambientVt->ai_analysis ?? '')));
                        $hasLegacyVoice = $diagnosis->voice_transcripts && count($diagnosis->voice_transcripts) > 0;
                    @endphp
                    @if($isVoiceType || $hasVoiceTranscript || $hasLegacyVoice || !empty($aiText))
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        @if($hasVoiceTranscript || $hasLegacyVoice)
                            <button type="button" class="btn btn-light border btn-sm" data-bs-toggle="modal" data-bs-target="#diagConversationModal" style="border-radius:10px;font-weight:700;font-size:0.82rem"><i class="fas fa-comments me-1 text-primary"></i>View Conversation</button>
                        @endif
                        @if(!empty($aiText))
                            <button type="button" class="btn btn-sm text-white" data-bs-toggle="modal" data-bs-target="#diagAnalysisModal" style="background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%);border:none;border-radius:10px;font-weight:700;font-size:0.82rem"><i class="fas fa-brain me-1"></i>View AI Analysis</button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Conversation Modal -->
            @if($hasVoiceTranscript || $hasLegacyVoice)
            <div class="modal fade modal-premium" id="diagConversationModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="head-icon" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color:#2563eb; border:1px solid #bfdbfe;"><i class="fas fa-comments"></i></div>
                                <div><h5 class="modal-title mb-0" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">Conversation Transcript</h5><div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">Diarized Clinician / Patient chat</div></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if($hasVoiceTranscript)
                                <textarea id="diagConversationRaw" style="display:none">{{ $transcriptText }}</textarea>
                                @php $diagLines = preg_split('/\n+/', trim((string)$transcriptText)); @endphp
                                @foreach($diagLines as $line) @continue(!trim($line))
                                    @php
                                        $isClinician = preg_match('/^\s*\[?(Clinician|Doctor|Speaker 0|Speaker 1|دكتور|الطبيب)\]?\s*[:：]/iu', $line) && !preg_match('/^\s*\[?Speaker 2/i', $line);
                                        $isPatient = preg_match('/^\s*\[?(Patient|Speaker 2|مريض|المريض)\]?\s*[:：]/iu', $line);
                                        if(preg_match('/^\s*\[Speaker 1\]/i', $line) && !preg_match('/Patient/i',$line)) $isClinician=true;
                                        $cleanLine = preg_replace('/^\s*\[?(Clinician|Doctor|Patient|Speaker \d)\]?\s*[:：]\s*/iu', '', $line);
                                        $bg = $isClinician ? '#eff6ff' : ($isPatient ? '#ecfdf5' : '#fff');
                                        $border = $isClinician ? '#dbeafe' : ($isPatient ? '#a7f3d0' : '#e2e8f0');
                                        $labelBg = $isClinician ? '#2563eb' : ($isPatient ? '#059669' : '#64748b');
                                        $label = $isClinician ? 'Clinician' : ($isPatient ? 'Patient' : 'Note');
                                    @endphp
                                    <div class="mb-2 p-3" style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:12px">
                                        <span style="background:{{ $labelBg }};color:#fff;border-radius:12px;padding:1px 8px;font-size:0.68rem;font-weight:800">{{ $label }}</span>
                                        <p class="mb-0 mt-1" style="font-size:0.86rem;line-height:1.6;color:#1e293b;word-break:break-word">{{ $cleanLine }}</p>
                                    </div>
                                @endforeach
                            @elseif($hasLegacyVoice)
                                @foreach($diagnosis->voice_transcripts as $index => $transcript)
                                    @if($transcript && $transcript !== $diagnosis->diagnosis_text)
                                        <div class="p-3 mb-2" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px">
                                            <div class="d-flex justify-content-between align-items-center mb-2"><span style="font-size:0.78rem;font-weight:600;color:#92400e">Voice Note {{ $index + 1 }}</span>
                                            @if(isset($diagnosis->voice_files[$index]) && (!empty(trim($diagnosis->voice_files[$index])) || $diagnosis->voice_transcripts[$index]))
                                                <button class="btn btn-sm" style="background:#ffffff;border:1px solid #e2e8f0;color:#334155;border-radius:8px;font-size:0.72rem" onclick="playVoiceFile({{ $index }})"><i class="fas fa-play me-1"></i>Play</button>
                                            @else <span style="font-size:0.72rem;color:#94a3b8">Audio not available</span> @endif</div>
                                            <div style="font-size:0.875rem;color:#475569">{!! nl2br(e($transcript)) !!}</div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Close</button>
                            @if($hasVoiceTranscript)<button type="button" class="btn btn-primary" onclick="const t=document.getElementById('diagConversationRaw').value; navigator.clipboard.writeText(t).then(()=>{const b=this;const o=b.innerHTML;b.innerHTML='<i class=&quot;fas fa-check me-1&quot;></i>Copied!';setTimeout(()=>b.innerHTML=o,2000)}).catch(()=>alert('Copy failed'))" style="border-radius:8px; background:#2563eb; border-color:#2563eb;"><i class="fas fa-copy me-1"></i>Copy</button>@endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @if(!empty($aiText))
            <div class="modal fade modal-premium" id="diagAnalysisModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="head-icon" style="background: linear-gradient(135deg, #ede9ff 0%, #ddd6fe 100%); color:#7c3aed; border:1px solid #ddd6fe;"><i class="fas fa-brain"></i></div>
                                <div><h5 class="modal-title mb-0" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">AI Clinical Analysis</h5><div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">GPT-4o • Level 1 + Level 2</div></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @php
                                $fmt2 = e($aiText);
                                $fmt2 = str_replace(['&#039;','&quot;','&amp;'], ["'",'"','&'], $fmt2);
                                $fmt2 = preg_replace('/^🟢 (.+)$/m', '<div class="alert alert-success py-2 px-3 mb-2" style="border-radius:8px;font-size:0.82rem"><i class="fas fa-check-circle me-2"></i>$1</div>', $fmt2);
                                $fmt2 = preg_replace('/^🔵 (.+)$/m', '<div class="alert alert-info py-2 px-3 mb-2" style="border-radius:8px;font-size:0.82rem;background:#eff6ff;border-color:#dbeafe;color:#1e40af"><i class="fas fa-info-circle me-2"></i>$1</div>', $fmt2);
                                $fmt2 = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $fmt2);
                                $fmt2 = nl2br($fmt2);
                                $fmt2 = preg_replace('/(<\/(div)>)\s*<br\s*\/?>/i', '$1', $fmt2);
                            @endphp
                            <div style="background:#f5f3ff;border:1px solid #ede9fe;border-radius:8px;padding:1rem;font-size:0.85rem;color:#475569;line-height:1.6">{!! $fmt2 !!}</div>
                            <textarea id="diagAnalysisRaw" style="display:none">{{ $aiText }}</textarea>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Close</button>
                            <button type="button" class="btn btn-primary" onclick="const t=document.getElementById('diagAnalysisRaw').value; navigator.clipboard.writeText(t).then(()=>{const b=this;const o=b.innerHTML;b.innerHTML='<i class=&quot;fas fa-check me-1&quot;></i>Copied!';setTimeout(()=>b.innerHTML=o,2000)}).catch(()=>alert('Copy failed'))" style="border-radius:8px; background:#7c3aed; border-color:#7c3aed;"><i class="fas fa-copy me-1"></i>Copy Analysis</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @php
                $visiblePatientData = array_filter($diagnosis->patient_data ?? [], function($v){ return is_array($v) ? count($v)>0 : trim((string)$v) !== '' && trim((string)$v) !== 'N/A'; });
            @endphp
            @if(count($visiblePatientData) > 0)
            <div class="card-modern mb-4">
                <div class="card-modern-header">
                    <h5><i class="fas fa-notes-medical" style="color:#0ea5e9"></i> Additional Patient Data</h5>
                    <span class="badge-soft">{{ count($visiblePatientData) }} fields</span>
                </div>
                <div class="card-modern-body">
                    <div class="row g-3">
                        @foreach($visiblePatientData as $key => $val)
                            <div class="col-md-6">
                                <div class="p-3 h-100" style="background:#ffffff;border:1px solid #f1f5f9;border-radius:8px">
                                    <h6 style="font-size:0.78rem;font-weight:600;color:#0f172a;text-transform:capitalize;margin:0 0 0.5rem">{{ str_replace('_',' ', $key) }}</h6>
                                    <div style="font-size:0.84rem;color:#475569;min-height:24px">
                                        @if(is_array($val))
                                            {{ implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $val)) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

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