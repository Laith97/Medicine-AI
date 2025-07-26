@extends('master')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-star me-2"></i>
                        <h4 class="mb-0">Leave a Review</h4>
                    </div>
                </div>
                <div class="card-body">
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

                    <!-- Doctor Info -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <img src="{{ $appointment->doctor->user->profile_photo_url ?? asset('images/default-doctor.png') }}"
                                 alt="Dr. {{ $appointment->doctor->user->name }}"
                                 class="rounded-circle mb-2"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <h5 class="fw-bold mb-1">Dr. {{ $appointment->doctor->user->name }}</h5>
                            <p class="text-muted mb-2">{{ $appointment->doctor->specialty->name ?? 'General Practice' }}</p>
                            <small class="text-muted">
                                Appointment: {{ $appointment->appointment_date->format('M j, Y \a\t g:i A') }}
                            </small>
                        </div>
                    </div>

                    <!-- Review Form -->
                    <form action="{{ route('reviews.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">

                        <!-- Rating -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Rating *</label>
                            <div class="rating-input">
                                @for($i = 1; $i <= 5; $i++)
                                    <input type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}" {{ old('rating') == $i ? 'checked' : '' }}>
                                    <label for="star{{ $i }}" class="star">
                                        <i class="fas fa-star"></i>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Comment -->
                        <div class="mb-4">
                            <label for="comment" class="form-label fw-semibold">Your Review</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4"
                                      placeholder="Share your experience with Dr. {{ $appointment->doctor->user->name }}...">{{ old('comment') }}</textarea>
                            @error('comment')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Anonymous Option -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="is_anonymous" value="1" id="is_anonymous" class="form-check-input" {{ old('is_anonymous') ? 'checked' : '' }}>
                                <label for="is_anonymous" class="form-check-label">
                                    Post this review anonymously
                                </label>
                            </div>
                        </div>

                        <!-- Google Consent -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="consent_google_posting" value="1" id="consent_google_posting" class="form-check-input" {{ old('consent_google_posting') ? 'checked' : '' }}>
                                <label for="consent_google_posting" class="form-check-label">
                                    <i class="fab fa-google me-1"></i>I consent to have this review posted to Google
                                </label>
                                <small class="form-text text-muted d-block mt-1">
                                    By checking this box, you agree to have your review posted to Google Reviews for this doctor's business.
                                </small>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Appointment
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-star me-1"></i>Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input .star {
    cursor: pointer;
    font-size: 2rem;
    color: #ddd;
    transition: color 0.2s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-input .star');

    stars.forEach((star, index) => {
        star.addEventListener('mouseover', function() {
            highlightStars(index + 1);
        });

        star.addEventListener('mouseout', function() {
            const checkedInput = document.querySelector('.rating-input input[type="radio"]:checked');
            if (checkedInput) {
                highlightStars(parseInt(checkedInput.value));
            } else {
                highlightStars(0);
            }
        });
    });

    function highlightStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#ffc107';
            } else {
                star.style.color = '#ddd';
            }
        });
    }

    // Initialize with current selection
    const checkedInput = document.querySelector('.rating-input input[type="radio"]:checked');
    if (checkedInput) {
        highlightStars(parseInt(checkedInput.value));
    }
});
</script>
@endsection
