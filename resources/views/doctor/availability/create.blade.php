@extends('master')

@section('title', 'Add Availability Slot')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.note-label{font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;margin-bottom:0.35rem;text-transform:uppercase}
.form-control,.form-select{border:1px solid #e2e8f0;border-radius:10px;padding:0.6rem 0.9rem;font-size:0.92rem;background:#f8fafc}
.form-control:focus,.form-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.12);background:#fff}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-plus me-2"></i>Add Availability Slot</h2>
                    <p>Create a new time slot for patient appointments</p>
                </div>
                <a href="{{ route('doctor.availability.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back to Availability</a>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if(session('error'))<div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if($errors->any())<div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

        <div class="table-card">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-plus"></i></div><div><h5>New Time Slot</h5><p>Day · time · duration · capacity</p></div></div></div>
            <form method="POST" action="{{ route('doctor.availability.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label note-label">Day of Week <span class="text-danger">*</span></label>
                        <select name="day_of_week" required class="form-select">
                            <option value="">Select a day</option>
                            @foreach($daysOfWeek as $day => $dayName)
                                <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>{{ $dayName }}</option>
                            @endforeach
                        </select>
                        @error('day_of_week')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label note-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required class="form-control">
                        @error('start_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label note-label">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required class="form-control">
                        @error('end_time')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label note-label">Slot Duration <span class="text-danger">*</span></label>
                        <select name="slot_duration" required class="form-select">
                            <option value="15" {{ old('slot_duration') == '15' ? 'selected' : '' }}>15 minutes</option>
                            <option value="30" {{ old('slot_duration', '30') == '30' ? 'selected' : '' }}>30 minutes</option>
                            <option value="45" {{ old('slot_duration') == '45' ? 'selected' : '' }}>45 minutes</option>
                            <option value="60" {{ old('slot_duration') == '60' ? 'selected' : '' }}>60 minutes</option>
                            <option value="90" {{ old('slot_duration') == '90' ? 'selected' : '' }}>90 minutes</option>
                            <option value="120" {{ old('slot_duration') == '120' ? 'selected' : '' }}>120 minutes</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label note-label">Max Bookings per Slot <span class="text-danger">*</span></label>
                        <select name="max_bookings_per_slot" required class="form-select">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('max_bookings_per_slot', '1') == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'patient' : 'patients' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label note-label">Effective From</label>
                        <input type="date" name="effective_from" value="{{ old('effective_from') }}" min="{{ date('Y-m-d') }}" class="form-control">
                        <small style="color:#64748b;font-size:0.72rem">Leave blank to start immediately</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label note-label">Effective Until</label>
                        <input type="date" name="effective_until" value="{{ old('effective_until') }}" min="{{ date('Y-m-d') }}" class="form-control">
                        <small style="color:#64748b;font-size:0.72rem">Leave blank for no end date</small>
                    </div>
                    <div class="col-md-8">
                        <div class="alert mb-0 d-flex gap-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;color:#1e40af">
                            <i class="fas fa-eye mt-1"></i>
                            <div><strong style="font-size:0.82rem">Preview</strong><div id="preview" style="font-size:0.82rem;color:#475569">Select day and time to see preview</div></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color:#eef2f7!important">
                            <a href="{{ route('doctor.availability.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600">Cancel</a>
                            <button type="submit" class="btn" style="background:#1e293b;color:#fff;border-radius:10px;padding:0.6rem 1.4rem;font-weight:700"><i class="fas fa-save me-2"></i>Create Slot</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border:1px solid #e2e8f0!important"><i class="fas fa-layer-group"></i></div><div><h5>Quick Setup</h5><p>Same hours for multiple days</p></div></div></div>
            <form method="POST" action="{{ route('doctor.availability.bulk') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label note-label">Select Days</label>
                    <div class="row g-2">
                        @foreach($daysOfWeek as $day => $dayName)
                            <div class="col-md-3 col-6"><div class="form-check" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:8px;padding:0.6rem 0.6rem 0.6rem 2rem"><input type="checkbox" name="days[]" value="{{ $day }}" class="form-check-input" id="bulk_{{ $day }}"><label class="form-check-label" for="bulk_{{ $day }}" style="font-size:0.84rem;font-weight:600;color:#334155">{{ $dayName }}</label></div></div>
                        @endforeach
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><label class="form-label note-label">Start Time</label><input type="time" name="start_time" value="09:00" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label note-label">End Time</label><input type="time" name="end_time" value="17:00" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label note-label">Duration</label><select name="slot_duration" class="form-select"><option value="30">30 min</option><option value="60">60 min</option></select></div>
                    <div class="col-md-3"><label class="form-label note-label">Max Bookings</label><select name="max_bookings_per_slot" class="form-select"><option value="1">1 patient</option><option value="2">2 patients</option></select></div>
                </div>
                <div class="d-flex justify-content-end"><button type="submit" class="btn" style="background:#0ea5e9;color:#fff;border-radius:10px;padding:0.6rem 1.2rem;font-weight:700"><i class="fas fa-calendar-plus me-2"></i>Create Multiple Slots</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daySelect = document.querySelector('select[name="day_of_week"]');
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    const duration = document.querySelector('select[name="slot_duration"]');
    const maxBookings = document.querySelector('select[name="max_bookings_per_slot"]');
    const preview = document.getElementById('preview');
    function updatePreview() {
        const day = daySelect.value; const start = startTime.value; const end = endTime.value; const dur = duration.value; const max = maxBookings.value;
        if (day && start && end && dur && max) {
            const dayName = daySelect.options[daySelect.selectedIndex].text;
            const startMinutes = parseInt(start.split(':')[0])*60+parseInt(start.split(':')[1]);
            const endMinutes = parseInt(end.split(':')[0])*60+parseInt(end.split(':')[1]);
            const slots = Math.floor((endMinutes-startMinutes)/parseInt(dur));
            preview.innerHTML = `<strong>${dayName}</strong> • ${start} – ${end} • ${slots} slots × ${dur} min • ${max} patient(s)/slot`;
        } else preview.innerHTML = 'Select day and time to see preview';
    }
    [daySelect, startTime, endTime, duration, maxBookings].forEach(el=> el.addEventListener('change', updatePreview));
    updatePreview();
});
</script>
@endsection
