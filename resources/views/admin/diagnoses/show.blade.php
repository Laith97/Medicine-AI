@extends('layouts.admin')
@section('title', 'Diagnosis #'.$diagnosis->id)
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-stethoscope" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.25rem;font-weight:800;color:#fff;margin:0">Diagnosis #{{ $diagnosis->id }}</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Created {{ $diagnosis->created_at->format('M d, Y H:i') }} · Admin view</p>
            </div>
        </div>
        <a href="{{ route('admin.diagnoses.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i>Back to Diagnoses</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem"><i class="fas fa-file-medical me-2 text-primary"></i>Diagnosis Text</h5></div>
                <div class="card-body p-4" style="background:#f8fafc;border-radius:12px;margin:12px;border:1px solid #eef2f7">
                    <div style="font-size:0.92rem;line-height:1.7;color:#334155;white-space:pre-wrap">{{ $diagnosis->diagnosis_text ?? '—' }}</div>
                </div>
            </div>
            @if($diagnosis->voice_transcripts || $diagnosis->voice_files)
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem"><i class="fas fa-microphone me-2 text-success"></i>Voice Data</h5></div>
                <div class="card-body p-3">
                    @if($diagnosis->voice_transcripts)
                        @foreach((array)$diagnosis->voice_transcripts as $i => $t)
                            <div class="p-3 mb-2" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px"><small class="text-muted" style="font-size:0.72rem">Transcript {{ $i+1 }}</small><div style="font-size:0.85rem;color:#334155">{{ is_string($t) ? $t : json_encode($t) }}</div></div>
                        @endforeach
                    @endif
                    @if($diagnosis->voice_files)
                        <div class="mt-2"><small class="text-muted">Voice files: {{ is_array($diagnosis->voice_files) ? count($diagnosis->voice_files) : 1 }}</small></div>
                    @endif
                </div>
            </div>
            @endif
            @if($diagnosis->patient_data)
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem"><i class="fas fa-notes-medical me-2 text-info"></i>Patient Data</h5></div>
                <div class="card-body p-3">
                    <pre style="background:#f8fafc;padding:1rem;border-radius:10px;font-size:0.8rem;white-space:pre-wrap;word-break:break-word">{{ json_encode($diagnosis->patient_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem">Participants</h5></div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($diagnosis->patient->name ?? 'P',0,1)) }}</div>
                        <div><div style="font-weight:700;color:#1e293b;font-size:0.9rem">{{ $diagnosis->patient->name ?? '—' }}</div><small class="text-muted" style="font-size:0.76rem">{{ $diagnosis->patient->email ?? '' }}</small><div><a href="{{ $diagnosis->patient ? route('admin.users.show',$diagnosis->patient) : '#' }}" class="small">View patient</a></div></div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:38px;height:38px;border-radius:50%;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($diagnosis->doctor->name ?? 'D',0,1)) }}</div>
                        <div><div style="font-weight:700;color:#1e293b;font-size:0.9rem">{{ $diagnosis->doctor->name ?? '—' }}</div><small class="text-muted" style="font-size:0.76rem">{{ $diagnosis->doctor->email ?? '' }}</small><div><a href="{{ $diagnosis->doctor ? route('admin.users.show',$diagnosis->doctor) : '#' }}" class="small">View doctor</a></div></div>
                    </div>
                    @if($diagnosis->appointment)
                    <div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px">
                        <small class="text-muted" style="font-size:0.72rem;text-transform:uppercase;font-weight:700">Appointment</small>
                        <div style="font-weight:600;color:#334155">#{{ $diagnosis->appointment->id }} · {{ $diagnosis->appointment->status }}</div>
                        <small class="text-muted">{{ $diagnosis->appointment->appointment_date?->format('M d, Y H:i') ?? '' }}</small>
                        <div class="mt-2"><a href="{{ route('admin.appointments.show',$diagnosis->appointment) }}" class="btn btn-light border btn-sm" style="border-radius:8px">View appointment</a></div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem">Meta</h5></div>
                <div class="card-body p-3" style="font-size:0.84rem">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span class="text-muted">ID</span><span style="font-weight:600">#{{ $diagnosis->id }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span class="text-muted">Patient key</span><span style="font-family:monospace;font-size:0.76rem">{{ Str::limit($diagnosis->patient_key ?? '—',20) }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span class="text-muted">Follow-ups</span><span>{{ $diagnosis->follow_up_count }}/5</span></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Created</span><span>{{ $diagnosis->created_at->format('M d, Y H:i') }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
