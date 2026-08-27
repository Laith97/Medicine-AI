@extends('layouts.admin')
@section('title', 'Appointment #'.$appointment->id)
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-calendar-check" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.25rem;font-weight:800;color:#fff;margin:0">Appointment #{{ $appointment->id }}</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $appointment->status }} · {{ $appointment->appointment_date?->format('M d, Y H:i') ?? 'No date' }} · Admin view</p>
            </div>
        </div>
        <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i>Back to Appointments</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem"><i class="fas fa-info-circle me-2 text-primary"></i>Details</h5></div>
                <div class="card-body p-4">
                    <div class="row g-3" style="font-size:0.88rem">
                        <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px"><small class="text-muted" style="font-size:0.72rem;text-transform:uppercase;font-weight:700">Date & Time</small><div style="font-weight:700;color:#1e293b">{{ $appointment->appointment_date?->format('M d, Y H:i') ?? '—' }}</div></div></div>
                        <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px"><small class="text-muted" style="font-size:0.72rem;text-transform:uppercase;font-weight:700">Status</small><div><span class="badge {{ $appointment->status=='completed'?'bg-success':($appointment->status=='pending'?'bg-warning text-dark':'bg-secondary') }}" style="border-radius:20px">{{ ucfirst($appointment->status) }}</span></div></div></div>
                        <div class="col-12"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px"><small class="text-muted" style="font-size:0.72rem;text-transform:uppercase;font-weight:700">Reason / Symptoms</small><div style="font-weight:500;color:#334155;white-space:pre-wrap">{{ $appointment->reason ?: ($appointment->symptoms ?? '—') }}</div></div></div>
                        @if($appointment->notes)
                        <div class="col-12"><div class="p-3" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px"><small class="text-muted" style="font-size:0.72rem;text-transform:uppercase;font-weight:700">Notes</small><div style="color:#334155">{{ $appointment->notes }}</div></div></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem">Participants</h5></div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($appointment->patient->name ?? 'P',0,1)) }}</div>
                        <div><div style="font-weight:700;color:#1e293b">{{ $appointment->patient->name ?? $appointment->guest_name ?? 'Guest' }}</div><small class="text-muted">{{ $appointment->patient->email ?? $appointment->guest_email ?? '' }}</small><div><a href="{{ $appointment->patient ? route('admin.users.show',$appointment->patient) : '#' }}" class="small">View patient</a></div></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:38px;height:38px;border-radius:50%;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($appointment->doctor->user->name ?? 'D',0,1)) }}</div>
                        <div><div style="font-weight:700;color:#1e293b">{{ $appointment->doctor->user->name ?? '—' }}</div><small class="text-muted">{{ $appointment->doctor->user->email ?? '' }}</small><div><a href="{{ $appointment->doctor && $appointment->doctor->user ? route('admin.users.show',$appointment->doctor->user) : '#' }}" class="small">View doctor</a></div></div>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.95rem">Meta</h5></div>
                <div class="card-body p-3" style="font-size:0.84rem">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span class="text-muted">ID</span><span style="font-weight:600">#{{ $appointment->id }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span class="text-muted">Created</span><span>{{ $appointment->created_at->format('M d, Y H:i') }}</span></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Type</span><span>{{ $appointment->appointment_type ?? '—' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
