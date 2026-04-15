@extends('master')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-clock me-2"></i>Edit Availability Slot</h2>
                        <p class="text-muted mb-0">Update your availability schedule</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-edit me-2"></i>
                        <h4 class="mb-0">Edit Availability Slot</h4>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('doctor.availability.update', $availability) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="day_of_week" class="form-label">Day of Week</label>
                                <select name="day_of_week" id="day_of_week" class="form-select" required>
                                    @foreach($daysOfWeek as $value => $label)
                                        <option value="{{ $value }}" {{ old('day_of_week', $availability->day_of_week) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('day_of_week')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="form-control"
                                       value="{{ old('start_time', $availability->start_time) }}" required>
                                @error('start_time')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control"
                                       value="{{ old('end_time', $availability->end_time) }}" required>
                                @error('end_time')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="slot_duration" class="form-label">Slot Duration (minutes)</label>
                                <select name="slot_duration" id="slot_duration" class="form-select" required>
                                    <option value="15" {{ old('slot_duration', $availability->slot_duration) == 15 ? 'selected' : '' }}>15 minutes</option>
                                    <option value="30" {{ old('slot_duration', $availability->slot_duration) == 30 ? 'selected' : '' }}>30 minutes</option>
                                    <option value="45" {{ old('slot_duration', $availability->slot_duration) == 45 ? 'selected' : '' }}>45 minutes</option>
                                    <option value="60" {{ old('slot_duration', $availability->slot_duration) == 60 ? 'selected' : '' }}>1 hour</option>
                                </select>
                                @error('slot_duration')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="max_bookings_per_slot" class="form-label">Max Bookings per Slot</label>
                                <input type="number" name="max_bookings_per_slot" id="max_bookings_per_slot"
                                       class="form-control" min="1" max="10"
                                       value="{{ old('max_bookings_per_slot', $availability->max_bookings_per_slot) }}" required>
                                @error('max_bookings_per_slot')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="effective_from" class="form-label">Effective From (Optional)</label>
                                <input type="date" name="effective_from" id="effective_from" class="form-control"
                                       value="{{ old('effective_from') ?: ($availability->effective_from ? $availability->effective_from->format('Y-m-d') : '') }}">
                                @error('effective_from')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="effective_until" class="form-label">Effective Until (Optional)</label>
                                <input type="date" name="effective_until" id="effective_until" class="form-control"
                                       value="{{ old('effective_until') ?: ($availability->effective_until ? $availability->effective_until->format('Y-m-d') : '') }}">
                                @error('effective_until')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                                           {{ old('is_active', $availability->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('doctor.availability.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Update Slot
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    </div></div>
@endsection
