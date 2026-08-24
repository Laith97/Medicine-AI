@extends('master')

@section('title', 'Create Follow-up Appointment')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.btn-back{background:rgba(255,255,255,0.15)!important;border:1px solid rgba(255,255,255,0.32)!important;color:#fff!important;border-radius:10px!important;padding:0.5rem 1rem!important;font-weight:600!important;font-size:0.83rem!important}
.btn-back:hover{background:#fff!important;color:#1e3a8a!important;border-color:#fff!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#64748b!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.form-label{font-size:0.82rem;font-weight:600;color:#1e293b}
.form-control,.form-select{border-radius:10px!important;border:1px solid #e2e8f0!important;font-size:0.88rem!important}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-plus me-2"></i>Create Follow-up Appointment</h2>
                    <p>For {{ e($appointment->patient_name) }} · Original {{ $appointment->appointment_date->format('M j, Y g:i A') }} · {{ ucfirst(str_replace('_',' ', $appointment->appointment_type)) }}</p>
                </div>
                <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i>Back to Appointment</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="section-head-modern">
                        <div class="d-flex align-items-center gap-3">
                            <div class="head-icon"><i class="fas fa-calendar-plus"></i></div>
                            <div><h5>Follow-up Details</h5><p>Schedule next visit linked to #{{ $appointment->appointment_number ?? $appointment->id }}</p></div>
                        </div>
                        <span class="badge bg-light text-muted border" style="font-size:0.70rem"><i class="fas fa-link me-1"></i>Linked to #{{ $appointment->id }}</span>
                    </div>

                    <div class="alert mb-4" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;padding:0.85rem 1rem;font-size:0.84rem">
                        <i class="fas fa-info-circle me-2"></i>Original completed {{ $appointment->completed_at ? $appointment->completed_at->format('M j, Y g:i A') : $appointment->appointment_date->format('M j, Y') }} · Patient will be notified by email.
                    </div>

                    <form method="POST" action="{{ route('doctor.follow-ups.store', $appointment) }}">
                        @csrf
                        <div class="p-3 mb-4" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px">
                            <div class="row g-3">
                                <div class="col-md-6"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Patient</small><div style="font-weight:600;color:#0f172a;font-size:0.92rem">{{ e($appointment->patient_name) }}</div><small style="color:#64748b">{{ e($appointment->patient_email) }} @if($appointment->patient_phone) · {{ e($appointment->patient_phone) }}@endif</small></div>
                                <div class="col-md-6 text-md-end"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Original</small><div style="font-weight:600;color:#0f172a">{{ $appointment->appointment_date->format('M j, Y g:i A') }}</div><span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;font-size:0.70rem">{{ ucfirst($appointment->status) }}</span></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2"><div class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;font-size:0.8rem"><i class="fas fa-calendar-alt"></i></div><label class="form-label mb-0">Schedule</label></div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="appointment_date" class="form-label">Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('appointment_date') is-invalid @enderror" id="appointment_date" name="appointment_date" required min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" value="{{ old('appointment_date') }}">
                                    @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="duration" class="form-label">Duration</label>
                                    <select class="form-select" id="duration" name="duration">
                                        <option value="15">15 min</option><option value="30" selected>30 min</option><option value="45">45 min</option><option value="60">60 min</option><option value="90">90 min</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2"><div class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;font-size:0.8rem"><i class="fas fa-video"></i></div><label class="form-label mb-0">Type</label></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <select class="form-select @error('appointment_type') is-invalid @enderror" id="appointment_type" name="appointment_type" required>
                                        <option value="">Select type</option><option value="video_call">Video Call</option><option value="phone_call">Phone Call</option><option value="in_person">In-Person</option><option value="follow_up" selected>Follow-up</option>
                                    </select>
                                    @error('appointment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 d-flex align-items-end"><small style="color:#64748b;font-size:0.78rem"><i class="fas fa-shield-alt me-1" style="color:#10b981"></i>Confirmed on create · Email sent</small></div>
                            </div>
                            <input type="hidden" name="consultation_fee" value="{{ $appointment->consultation_fee / 100 }}">
                        </div>

                        <div class="mb-4">
                            <label for="reason" class="form-label">Reason for Follow-up <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="4" required placeholder="Review treatment progress, test results, medication adjustment...">{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small style="color:#64748b;font-size:0.72rem">Auto-fills by type if empty - you can edit.</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3" style="border-top:1px solid #f1f5f9">
                            <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.1rem;font-weight:500;font-size:0.88rem"><i class="fas fa-times me-2"></i>Cancel</a>
                            <button type="submit" class="btn" style="background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%);color:#fff;border:none;border-radius:10px;padding:0.65rem 1.4rem;font-weight:600;font-size:0.88rem;box-shadow:0 4px 14px rgba(37,99,235,0.25)"><i class="fas fa-save me-2"></i>Create Follow-up</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-2"><div class="head-icon" style="width:32px;height:32px;font-size:0.85rem"><i class="fas fa-history"></i></div><h5 style="font-size:0.95rem">Original Appointment</h5></div></div>
                    <div style="font-size:0.84rem;color:#334155">
                        <div class="mb-2"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">DATE & TIME</small><div style="font-weight:600;color:#0f172a">{{ $appointment->appointment_date->format('M j, Y g:i A') }}</div></div>
                        <div class="mb-2"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">TYPE</small><div style="font-weight:600">{{ ucfirst(str_replace('_',' ', $appointment->appointment_type)) }}</div></div>
                        <div class="mb-3"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">STATUS</small><div><span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;font-size:0.72rem"><i class="fas fa-check-circle me-1"></i>{{ ucfirst($appointment->status) }}</span></div></div>
                        @if($appointment->doctor_notes)<div><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">DOCTOR NOTES</small><div class="p-2 mt-1" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;font-size:0.82rem;color:#334155">{{ Str::limit($appointment->doctor_notes, 200) }}</div></div>@endif
                    </div>
                </div>
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-2"><div class="head-icon" style="background:#ecfdf5!important;color:#059669!important;border-color:#a7f3d0!important;width:32px;height:32px;font-size:0.85rem"><i class="fas fa-lightbulb"></i></div><h5 style="font-size:0.95rem">Follow-up Tips</h5></div></div>
                    <ul style="list-style:none;padding:0;margin:0;font-size:0.82rem;color:#475569">
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Schedule 1-4 weeks for optimal care</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Consider patient availability & urgency</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Match type to care needed (video/in-person)</li>
                        <li><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Patient receives automatic notification</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reasonField = document.getElementById('reason');
    const appointmentTypeField = document.getElementById('appointment_type');
    appointmentTypeField.addEventListener('change', function() {
        if (!reasonField.value.trim()) {
            const map = {'follow_up':'Follow-up appointment to review treatment progress and patient response.','video_call':'Video consultation to discuss test results and treatment plan.','phone_call':'Phone consultation for medication review and patient concerns.','in_person':'In-person appointment for physical examination and treatment adjustment.'};
            if(map[this.value]) reasonField.value = map[this.value];
        }
    });
    const appointmentDateField = document.getElementById('appointment_date');
    const now = new Date(); now.setHours(now.getHours()+1);
    appointmentDateField.min = now.toISOString().slice(0,16);
});
</script>
@endpush
