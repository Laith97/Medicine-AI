@extends('master')

@section('title', 'Manage Availability')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header py-2 border-bottom">
            <h2 class="h1 mb-1" style="font-weight: 700;">Doctor Availability</h2>
            <p>Set your availability</p>
        </div>

        <!-- Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Weekly Schedule -->
        <div class="table-card">
            <h6 class="mb-4"><i class="fas fa-calendar-week me-2"></i>Weekly Schedule</h6>

            <div class="row g-4">
                @foreach($daysOfWeek as $day => $dayName)
                    <div class="col-12">
                        <div class="border rounded p-4 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">{{ $dayName }}</h5>
                                <button onclick="showBulkModal('{{ $day }}')" class="btn btn-primary-custom btn-sm">
                                    <i class="fas fa-plus me-1"></i>Quick Add
                                </button>
                            </div>

                            @if($availabilitySlots->has($day))
                                <div class="d-flex flex-column gap-3">
                                    @foreach($availabilitySlots[$day] as $slot)
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="fw-medium">
                                                        {{ date('g:i A', strtotime($slot->start_time)) }} - {{ date('g:i A', strtotime($slot->end_time)) }}
                                                    </span>
                                                    @if(!$slot->is_active)
                                                        <span class="badge bg-danger ms-2">Inactive</span>
                                                    @endif
                                                </div>
                                                <small class="text-muted">
                                                    {{ $slot->slot_duration }} min slots • Max {{ $slot->max_bookings_per_slot }} booking(s) per slot
                                                </small>
                                                @if($slot->effective_from || $slot->effective_until)
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            @if($slot->effective_from)
                                                                From {{ $slot->effective_from->format('M j, Y') }}
                                                            @endif
                                                            @if($slot->effective_until)
                                                                Until {{ $slot->effective_until->format('M j, Y') }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="align-items-center gap-2">
                                                <!-- Toggle Active/Inactive -->
                                                <form method="POST" action="{{ route('doctor.availability.toggle', $slot) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-{{ $slot->is_active ? 'warning' : 'success' }}"
                                                            title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="fas fa-{{ $slot->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit -->
                                                <a href="{{ route('doctor.availability.edit', $slot) }}"
                                                   class="btn btn-smbtn-primary-custom" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <!-- Delete -->
                                                <form method="POST" action="{{ route('doctor.availability.destroy', $slot) }}"
                                                      class="d-inline" onsubmit="return confirm('Are you sure you want to delete this time slot?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p class="mb-2">No availability set for {{ $dayName }}</p>
                                    <button onclick="showBulkModal('{{ $day }}')" class="btn btn-primary-custom btn-sm">
                                        Add time slots
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <p class="stats-number">
                        {{ $availabilitySlots->flatten()->sum(function($slot) {
                            return \Carbon\Carbon::parse($slot->end_time)->diffInHours(\Carbon\Carbon::parse($slot->start_time));
                        }) }}
                    </p>
                    <p class="stats-label">Total Weekly Hours</p>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <p class="stats-number">{{ $availabilitySlots->flatten()->where('is_active', true)->count() }}</p>
                    <p class="stats-label">Active Time Slots</p>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ $availabilitySlots->keys()->count() }}</p>
                    <p class="stats-label">Days Available</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Add Modal -->
<div class="modal fade" id="bulkModal" tabindex="-1" aria-labelledby="bulkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkModalLabel">Quick Add Time Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('doctor.availability.store') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="day_of_week" id="bulkDay">

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" required class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" required class="form-control">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Slot Duration (minutes)</label>
                            <select name="slot_duration" required class="form-select">
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Max Bookings per Slot</label>
                            <select name="max_bookings_per_slot" required class="form-select">
                                <option value="1" selected>1 patient</option>
                                <option value="2">2 patients</option>
                                <option value="3">3 patients</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Time Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showBulkModal(day) {
    document.getElementById('bulkDay').value = day;
    const modal = new bootstrap.Modal(document.getElementById('bulkModal'));
    modal.show();
}
</script>
@endsection
