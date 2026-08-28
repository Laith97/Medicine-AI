@extends('master')

@section('title', 'Edit Availability Slot')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#f59e0b 0%,#d97706 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#fffbeb;border-bottom:1px solid #fde68a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#f59e0b!important;color:#fff!important;border:1px solid #f59e0b!important}
.section-head-modern h5{color:#92400e!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#b45309!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.note-label{font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;margin-bottom:0.35rem;text-transform:uppercase}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-pen-to-square me-2"></i>Edit Availability Slot</h2>
                    <p>Update your availability schedule</p>
                </div>
                <a href="{{ route('doctor.availability.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back to Availability</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-clock"></i></div><div><h5>Edit Slot</h5><p>Modify day, time and capacity</p></div></div></div>
                    @if($errors->any())
                        <div class="alert alert-danger" style="border-radius:10px"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form action="{{ route('doctor.availability.update', $availability) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="day_of_week" class="form-label note-label">Day of Week</label>
                                <select name="day_of_week" id="day_of_week" class="form-select" required style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                                    @foreach($daysOfWeek as $value => $label)
                                        <option value="{{ $value }}" {{ old('day_of_week', $availability->day_of_week) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="start_time" class="form-label note-label">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="form-control" value="{{ old('start_time', $availability->start_time) }}" required style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-3">
                                <label for="end_time" class="form-label note-label">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control" value="{{ old('end_time', $availability->end_time) }}" required style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-6">
                                <label for="slot_duration" class="form-label note-label">Slot Duration</label>
                                <select name="slot_duration" id="slot_duration" class="form-select" required style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                                    <option value="15" {{ old('slot_duration', $availability->slot_duration) == 15 ? 'selected' : '' }}>15 minutes</option>
                                    <option value="30" {{ old('slot_duration', $availability->slot_duration) == 30 ? 'selected' : '' }}>30 minutes</option>
                                    <option value="45" {{ old('slot_duration', $availability->slot_duration) == 45 ? 'selected' : '' }}>45 minutes</option>
                                    <option value="60" {{ old('slot_duration', $availability->slot_duration) == 60 ? 'selected' : '' }}>1 hour</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="max_bookings_per_slot" class="form-label note-label">Max Bookings per Slot</label>
                                <input type="number" name="max_bookings_per_slot" id="max_bookings_per_slot" class="form-control" min="1" max="10" value="{{ old('max_bookings_per_slot', $availability->max_bookings_per_slot) }}" required style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-6">
                                <label for="effective_from" class="form-label note-label">Effective From</label>
                                <input type="date" name="effective_from" id="effective_from" class="form-control" value="{{ old('effective_from') ?: ($availability->effective_from ? $availability->effective_from->format('Y-m-d') : '') }}" style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-6">
                                <label for="effective_until" class="form-label note-label">Effective Until</label>
                                <input type="date" name="effective_until" id="effective_until" class="form-control" value="{{ old('effective_until') ?: ($availability->effective_until ? $availability->effective_until->format('Y-m-d') : '') }}" style="border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-12">
                                <div class="form-check" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.9rem 0.9rem 0.9rem 2.2rem">
                                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $availability->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label" style="font-weight:600;color:#334155">Active</label>
                                    <div style="font-size:0.76rem;color:#64748b">Inactive slots are hidden from patients</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color:#eef2f7!important">
                            <a href="{{ route('doctor.availability.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600"><i class="fas fa-arrow-left me-1"></i>Cancel</a>
                            <button type="submit" class="btn" style="background:#f59e0b;color:#fff;border-radius:10px;padding:0.6rem 1.4rem;font-weight:700;border:1px solid #f59e0b"><i class="fas fa-save me-1"></i>Update Slot</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
