@extends('master')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">My Reviews</h2>
                <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                    <i class="fas fa-calendar me-1"></i>View Appointments
                </a>
            </div>

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

            @if($reviews->count() > 0)
                <div class="row">
                    @foreach($reviews as $review)
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <!-- Doctor Info -->
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="{{ $review->doctor->user->profile_photo_url ?? asset('images/default-doctor.png') }}"
                                             alt="Dr. {{ $review->doctor->user->name }}"
                                             class="rounded-circle me-3"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <h6 class="fw-bold mb-0">Dr. {{ $review->doctor->user->name }}</h6>
                                            <small class="text-muted">{{ $review->doctor->specialty->name ?? 'General Practice' }}</small>
                                        </div>
                                    </div>

                                    <!-- Rating -->
                                    <div class="mb-3">
                                        <div class="text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= (int)$review->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <small class="text-muted">{{ $review->rating }}/5 stars</small>
                                    </div>

                                    <!-- Comment -->
                                    @if($review->comment)
                                        <p class="text-muted mb-3">
                                            {{ Str::limit($review->comment, 100) }}
                                        </p>
                                    @endif

                                    <!-- Review Info -->
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                        <span>
                                            @if($review->is_anonymous)
                                                <i class="fas fa-user-secret me-1"></i>Anonymous
                                            @else
                                                <i class="fas fa-user me-1"></i>Public
                                            @endif
                                        </span>
                                        <span>{{ $review->created_at->format('M j, Y') }}</span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('reviews.show', $review) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <a href="{{ route('appointments.show', $review->appointment) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-calendar me-1"></i>Appointment
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $reviews->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 100px; height: 100px;">
                        <i class="fas fa-star text-muted fs-1"></i>
                    </div>
                    <h4 class="fw-bold mb-2">No Reviews Yet</h4>
                    <p class="text-muted mb-4">You haven't left any reviews for your appointments.</p>
                    <a href="{{ route('appointments.index') }}" class="btn btn-primary">
                        <i class="fas fa-calendar me-2"></i>View Your Appointments
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
