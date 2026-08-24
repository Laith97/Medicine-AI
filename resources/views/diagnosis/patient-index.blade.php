@extends('master')

@section('title', 'My Diagnoses')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#1e293b;border-bottom:1px solid #0f172a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:rgba(255,255,255,0.12)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.18)!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern h5{color:#fff!important}
.section-head-modern p{color:rgba(255,255,255,0.75)!important}
.diagnosis-row{display:flex;align-items:center;gap:1rem;padding:1rem 0;border-bottom:1px solid #f1f5f9}
.diagnosis-row:last-child{border-bottom:none}
.doctor-avatar{width:42px;height:42px;border-radius:50%;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569;font-weight:700;font-size:0.95rem;flex-shrink:0}
.badge-muted{padding:0.35rem 0.6rem;border-radius:99px;font-size:0.70rem;font-weight:700;border:1px solid #e2e8f0;background:#f8fafc;color:#475569}
.badge-new{background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700}
.badge-reviewed{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-clipboard-list me-2"></i>My Diagnoses</h2>
                    <p>View your medical diagnoses and track your health history · {{ $diagnoses->total() }} records</p>
                </div>
                <span class="badge" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:99px;padding:0.4rem 0.85rem;font-size:0.74rem;font-weight:600"><i class="fas fa-file-medical me-1"></i>{{ $diagnoses->count() }} shown</span>
            </div>
        </div>

        @if (session('success'))
            <div class="alert d-flex align-items-center mb-3" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
        @endif

        @if($diagnoses->count() > 0)
            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3">
                        <div class="head-icon"><i class="fas fa-file-medical"></i></div>
                        <div><h5>Diagnoses History</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Doctor's diagnoses · AI assisted where available</p></div>
                    </div>
                </div>
                @foreach($diagnoses as $diagnosis)
                    <div class="diagnosis-row {{ !$diagnosis->patient_viewed_at ? 'position-relative' : '' }}" style="{{ !$diagnosis->patient_viewed_at ? 'background:#fffbeb0a;border-left:3px solid #f59e0b;border-radius:8px;padding-left:0.75rem;margin-left:-0.75rem' : '' }}">
                        <div class="doctor-avatar"><i class="fas fa-user-md"></i></div>
                        <div style="min-width:160px">
                            <div style="font-weight:700;color:#0f172a;font-size:0.88rem">Dr. {{ e($diagnosis->doctor->name) }}</div>
                            <small style="color:#64748b;font-size:0.74rem">{{ Str::limit($diagnosis->doctor->email, 25) }}</small>
                        </div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div style="font-size:0.88rem;color:#334155;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><i class="fas fa-file-medical me-1" style="color:#64748b"></i>{{ Str::limit($diagnosis->diagnosis_text, 80) }}</div>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                <span class="badge-muted"><i class="fas fa-user-md me-1"></i>Doctor</span>
                                @if($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0)<span class="badge-muted" style="background:#eff6ff;color:#2563eb;border-color:#dbeafe"><i class="fas fa-robot me-1"></i>AI</span>@endif
                                @if($diagnosis->follow_up_count > 0)<span class="badge-muted"><i class="fas fa-comments me-1"></i>{{ $diagnosis->follow_up_count }} follow-ups</span>@endif
                            </div>
                        </div>
                        <div class="text-center" style="min-width:110px">
                            <div style="font-weight:700;color:#0f172a;font-size:0.84rem">{{ $diagnosis->created_at->format('M j, Y') }}</div>
                            <small style="color:#64748b">{{ $diagnosis->created_at->format('g:i A') }}</small>
                            <div class="d-flex gap-1 justify-content-center mt-1 flex-wrap">
                                @if(!$diagnosis->patient_viewed_at)<span class="badge-new"><i class="fas fa-eye-slash me-1"></i>New</span>@endif
                                @if($diagnosis->patient_reviewed)<span class="badge-reviewed"><i class="fas fa-star me-1"></i>Reviewed</span>@elseif($diagnosis->patient_viewed_at)<span class="badge-muted" style="background:#dbeafe;color:#1e40af;border-color:#bfdbfe"><i class="fas fa-star-half-alt me-1"></i>Pending</span>@endif
                            </div>
                        </div>
                        <div class="text-end" style="min-width:110px">
                            <a href="{{ route('diagnosis.patient.view', $diagnosis) }}" class="btn btn-sm" style="background:#1e293b;color:#fff;border:1px solid #1e293b;border-radius:8px;padding:0.4rem 0.75rem;font-weight:600;font-size:0.78rem"><i class="fas fa-eye me-1"></i>View</a>
                            @if($diagnosis->canAskFollowUp())<div class="mt-1" style="font-size:0.70rem;color:#64748b"><i class="fas fa-question-circle me-1"></i>{{ 5 - $diagnosis->follow_up_count }} left</div>@endif
                        </div>
                    </div>
                    @if($diagnosis->followUps->count() > 0)
                        @php $lastFollowUp = $diagnosis->followUps->last(); @endphp
                        <div class="mb-3 p-2" style="background:#f8fafc;border:1px solid #f1f5f9;border-left:3px solid #e2e8f0;border-radius:8px;margin-left:58px">
                            <small style="font-weight:700;color:#475569;font-size:0.72rem"><i class="fas fa-comments me-1"></i>Recent Follow-up</small>
                            <div style="font-size:0.82rem;color:#334155;margin-top:0.25rem"><strong>Q:</strong> {{ Str::limit($lastFollowUp->question, 80) }}</div>
                            <div style="font-size:0.82rem;color:#475569"><strong>A:</strong> {{ Str::limit($lastFollowUp->ai_response, 100) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            @if($diagnoses->hasPages())
                <div class="d-flex justify-content-center mt-3">{{ $diagnoses->links() }}</div>
            @endif
        @else
            <div class="table-card text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:56px;height:56px;background:#f8fafc;border:1px solid #e2e8f0;color:#94a3b8"><i class="fas fa-file-medical" style="font-size:1.5rem"></i></div>
                <h5 style="font-weight:700;color:#475569">No Diagnoses Yet</h5>
                <p style="color:#64748b;font-size:0.88rem;margin:0">You haven't received any diagnoses from doctors yet.</p>
                <p style="color:#94a3b8;font-size:0.78rem">When a doctor creates a diagnosis, it will appear here.</p>
            </div>
        @endif

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-route"></i></div><div><h5>How it works</h5><p>Your diagnosis journey · 3 clear steps</p></div></div>
                <span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700">Patient Guide</span>
            </div>
            <div class="position-relative">
                <div class="d-none d-md-block" style="position:absolute;top:38px;left:16%;right:16%;height:2px;background:linear-gradient(90deg,#e2e8f0 0%,#cbd5e1 50%,#e2e8f0 100%);border-radius:99px"></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-center p-3 position-relative" style="background:#fff;border:1px solid #eef2f7;border-radius:12px">
                            <div class="d-inline-flex align-items-center justify-content-center position-relative" style="width:56px;height:56px;background:#eff6ff;border:1px solid #dbeafe;border-radius:50%;color:#2563eb"><i class="fas fa-bell" style="font-size:1.3rem"></i><span class="position-absolute d-flex align-items-center justify-content-center" style="top:-6px;right:-6px;width:22px;height:22px;background:#2563eb;color:#fff;border-radius:50%;font-size:0.70rem;font-weight:800;border:2px solid #fff">1</span></div>
                            <h6 style="font-weight:800;color:#0f172a;margin:0.85rem 0 0.3rem">You Receive Diagnosis</h6>
                            <p style="font-size:0.82rem;color:#475569;margin:0;line-height:1.5">Dr. <strong>creates</strong> your diagnosis after the visit. You get an <strong>in-app + email</strong> notification instantly.</p>
                            <small class="d-inline-flex align-items-center gap-1 mt-2" style="background:#eff6ff;color:#2563eb;border:1px solid #dbeafe;border-radius:99px;padding:0.2rem 0.5rem;font-size:0.70rem;font-weight:600"><i class="fas fa-envelope"></i> Check notifications</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 position-relative" style="background:#fff;border:1px solid #eef2f7;border-radius:12px">
                            <div class="d-inline-flex align-items-center justify-content-center position-relative" style="width:56px;height:56px;background:#f0fdfa;border:1px solid #ccfbf1;border-radius:50%;color:#0d9488"><i class="fas fa-comments" style="font-size:1.3rem"></i><span class="position-absolute d-flex align-items-center justify-content-center" style="top:-6px;right:-6px;width:22px;height:22px;background:#0d9488;color:#fff;border-radius:50%;font-size:0.70rem;font-weight:800;border:2px solid #fff">2</span></div>
                            <h6 style="font-weight:800;color:#0f172a;margin:0.85rem 0 0.3rem">View & Ask AI</h6>
                            <p style="font-size:0.82rem;color:#475569;margin:0;line-height:1.5">Tap <strong>View</strong> to read full details, then ask up to <strong>5 follow-up questions</strong> - AI answers instantly.</p>
                            <small class="d-inline-flex align-items-center gap-1 mt-2" style="background:#f0fdfa;color:#0f766e;border:1px solid #ccfbf1;border-radius:99px;padding:0.2rem 0.5rem;font-size:0.70rem;font-weight:600"><i class="fas fa-robot"></i> AI answers in seconds</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 position-relative" style="background:#fff;border:1px solid #eef2f7;border-radius:12px">
                            <div class="d-inline-flex align-items-center justify-content-center position-relative" style="width:56px;height:56px;background:#fffbeb;border:1px solid #fde68a;border-radius:50%;color:#d97706"><i class="fas fa-star" style="font-size:1.3rem"></i><span class="position-absolute d-flex align-items-center justify-content-center" style="top:-6px;right:-6px;width:22px;height:22px;background:#f59e0b;color:#fff;border-radius:50%;font-size:0.70rem;font-weight:800;border:2px solid #fff">3</span></div>
                            <h6 style="font-weight:800;color:#0f172a;margin:0.85rem 0 0.3rem">Rate Your Experience</h6>
                            <p style="font-size:0.82rem;color:#475569;margin:0;line-height:1.5">Tap stars to <strong>rate 1-5</strong>. Your review helps others and builds the doctor's reputation.</p>
                            <small class="d-inline-flex align-items-center gap-1 mt-2" style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:99px;padding:0.2rem 0.5rem;font-size:0.70rem;font-weight:600"><i class="fas fa-heart"></i> Help other patients</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
