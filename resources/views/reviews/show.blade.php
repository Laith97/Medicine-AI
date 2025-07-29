@extends('master')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <h4 class="mb-0">Your Review</h4>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Doctor Info -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <img src="{{ $review->doctor->user->profile_photo_url ?? asset('images/default-doctor.png') }}"
                                 alt="Dr. {{ $review->doctor->user->name }}"
                                 class="rounded-circle mb-2"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <h5 class="fw-bold mb-1">Dr. {{ $review->doctor->user->name }}</h5>
                            <p class="text-muted mb-2">{{ $review->doctor->specialty->name ?? 'General Practice' }}</p>
                            <small class="text-muted">
                                Appointment: {{ $review->appointment->appointment_date->format('M j, Y \a\t g:i A') }}
                            </small>
                        </div>
                    </div>

                    <!-- Review Display -->
                    <div class="bg-light rounded-3 p-4 mb-4">
                        <!-- Rating -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <div class="text-warning me-3">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= (int)$review->rating)
                                            <i class="fas fa-star"></i>
                                        @else
                                            <i class="star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span class="fw-semibold">{{ $review->rating }}/5 stars</span>
                            </div>
                        </div>

                        <!-- Comment -->
                        @if($review->comment)
                            <div class="mb-3">
                                <p class="mb-0">{{ $review->comment }}</p>
                            </div>
                        @endif

                        <!-- Review Info -->
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span>
                                @if($review->is_anonymous)
                                    <i class="fas fa-user-secret me-1"></i>Posted anonymously
                                @else
                                    <i class="fas fa-user me-1"></i>Posted by {{ $review->patient->name ?? 'Unknown Patient' }}
                                @endif
                            </span>
                            <span>{{ $review->created_at->format('M j, Y') }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('appointments.show', $review->appointment) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Appointment
                        </a>

                        <div>
                            @if($review->created_at->diffInHours(now()) <= 24)
                                <a href="{{ route('reviews.edit', $review) }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-edit me-1"></i>Edit Review
                                </a>
                                <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this review?')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            @else
                                <small class="text-muted">Reviews can only be edited or deleted within 24 hours</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
