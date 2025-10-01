@extends('master')

@section('title', 'My Reviews')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header py-2 border-bottom">
            <h2 class="h1 mb-1" style="font-weight: 700;">Patient Reviews</h2>
            <p>Manage and view feedback from your patients</p>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="stats-number">{{ number_format($doctor->average_rating ?? 0, 1) }}</p>
                    <p class="stats-label">Average Rating</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <p class="stats-number">{{ $doctor->total_reviews ?? 0 }}</p>
                    <p class="stats-label">Total Reviews</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <p class="stats-number">{{ $positiveReviews }}</p>
                    <p class="stats-label">Positive Reviews</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <p class="stats-number">{{ $recentReviews }}</p>
                    <p class="stats-label">This Month</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-card mb-4">
            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Reviews</h6>
            <form method="GET" action="{{ route('doctor.reviews.index') }}" class="row g-3">
                <!-- Rating Filter -->
                <div class="col-md-4">
                    <label for="rating" class="form-label">Filter by Rating</label>
                    <select name="rating" id="rating" class="form-select">
                        <option value="">All Ratings</option>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Reviews</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2" style="background-color: #0088cc; border-color: #0088cc;">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['rating', 'status']))
                        <a href="{{ route('doctor.reviews.index') }}" class="btn btn-outline-secondary">
                            Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Reviews List -->
        <div class="table-card">
            @if($reviews->count() > 0)
                @foreach($reviews as $review)
                    <div class="border-bottom pb-4 mb-4">
                        <!-- Review Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <!-- Rating Stars -->
                                <div class="text-warning me-3">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            <i class="fas fa-star"></i>
                                        @else
                                            <i class="star"></i>
                                        @endif
                                    @endfor
                                </div>

                                <!-- Patient Info -->
                                <div>
                                    <h6 class="mb-1">
                                        @if($review->is_anonymous)
                                            Anonymous Patient
                                        @elseif($review->patient)
                                            {{ $review->patient->name }}
                                        @else
                                            {{ $review->guest_name ?? 'Guest Patient' }}
                                        @endif
                                    </h6>
                                    <small class="text-muted">
                                        {{ $review->created_at->format('M j, Y \a\t g:i A') }}
                                    </small>
                                </div>
                            </div>

                            <!-- Status and Source -->
                            <div class="d-flex align-items-center gap-2">
                                <!-- Approval Status -->
                                @if($review->is_approved)
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Approved
                                    </span>
                                @else
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                @endif

                                <!-- Source -->
                                @if($review->source === 'google')
                                    <span class="badge bg-info">
                                        <i class="fab fa-google me-1"></i>Google
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="fas fa-hospital me-1"></i>MedCura
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Review Comment -->
                        @if($review->comment)
                            <div class="mb-3">
                                <p class="text-dark">{{ $review->comment }}</p>
                            </div>
                        @endif

                        <!-- Appointment Info -->
                        @if($review->appointment)
                            <div class="bg-light rounded p-3">
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    <span>
                                        Related to appointment on
                                        {{ $review->appointment->appointment_date->format('M j, Y') }}
                                        @if($review->appointment->appointment_time)
                                            at {{ $review->appointment->appointment_time->format('g:i A') }}
                                        @endif
                                    </span>
                                </div>
                                @if($review->appointment->reason)
                                    <div class="mt-2 text-muted small">
                                        <i class="fas fa-stethoscope me-2"></i>
                                        <span>Reason: {{ $review->appointment->reason }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $reviews->appends(request()->query())->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-star"></i>
                    <h5 class="mb-2">No Reviews Yet</h5>
                    @if(request()->hasAny(['rating', 'status']))
                        <p class="text-muted mb-3">No reviews match your current filters.</p>
                        <a href="{{ route('doctor.reviews.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                    @else
                        <p class="text-muted mb-3">You haven't received any patient reviews yet.</p>
                        <small class="text-muted">Reviews will appear here after patients complete their appointments and leave feedback.</small>
                    @endif
                </div>
            @endif
        </div>

        <!-- Help Section -->
        <div class="table-card">
            <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-info-circle text-primary fs-4"></i>
                </div>
                <div>
                    <h3 class="h5 mb-3">About Patient Reviews</h3>
                    <div class="text-muted">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Patients can leave reviews after completing their appointments</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>All reviews are automatically approved and visible to other patients</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>Reviews help build trust and attract new patients to your practice</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2 text-success"></i>You can view both internal reviews and those synced from Google</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
