@extends('master')

@section('title', 'My Diagnosis')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9">
            <!-- Page Header -->
            <div class="page-header text-center text-md-start mb-4">
                <h2><i class="fas fa-file-medical me-2"></i>My Diagnosis</h2>
                <p class="text-muted">From Dr. {{ $diagnosis->doctor->name }} • {{ $diagnosis->created_at->format('F j, Y \a\t g:i A') }}</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Doctor Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Doctor Information</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">Dr. {{ $diagnosis->doctor->name }}</h6>
                            <p class="text-muted mb-0">{{ $diagnosis->doctor->email }}</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <span class="badge bg-{{ $diagnosis->type === 'ai' ? 'info' : 'success' }} fs-6">
                                <i class="fas fa-{{ $diagnosis->type === 'ai' ? 'robot' : 'user-md' }} me-1"></i>
                                {{ ucfirst($diagnosis->type) }} Diagnosis
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnosis Content -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Diagnosis</h5>
                </div>
                <div class="card-body">
                    <div class="diagnosis-content">
                        {!! nl2br(e($diagnosis->diagnosis_text)) !!}
                    </div>

                    @if($diagnosis->voice_transcript && $diagnosis->voice_transcript !== $diagnosis->diagnosis_text)
                        <hr>
                        <div class="voice-transcript">
                            <h6><i class="fas fa-microphone me-2"></i>Voice Transcript</h6>
                            <div class="bg-light p-3 rounded">
                                {!! nl2br(e($diagnosis->voice_transcript)) !!}
                            </div>
                        </div>
                    @endif

                    @if($diagnosis->ai_response)
                        <hr>
                        <div class="ai-response">
                            <h6><i class="fas fa-robot me-2"></i>AI Analysis</h6>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                {!! nl2br(e($diagnosis->ai_response)) !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Patient Data -->
            @if($diagnosis->patient_data)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($diagnosis->patient_data as $key => $value)
                                @if($value)
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-capitalize">{{ str_replace('_', ' ', $key) }}</h6>
                                        <div class="text-muted">
                                            @if(is_array($value))
                                                <pre>{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Follow-up Questions Section -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Follow-up Questions</h5>
                        <span class="badge bg-secondary">{{ $diagnosis->follow_up_count }}/5 used</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Existing Follow-ups -->
                    <div id="followUpsList">
                        @foreach($diagnosis->followUps as $followUp)
                            <div class="follow-up-item mb-4 p-3 border rounded">
                                <div class="question mb-2">
                                    <strong><i class="fas fa-user me-2"></i>You asked:</strong>
                                    <p class="mb-2">{{ $followUp->question }}</p>
                                    <small class="text-muted">{{ $followUp->created_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                                <div class="answer">
                                    <strong><i class="fas fa-robot me-2 text-info"></i>AI Response:</strong>
                                    <div class="bg-light p-3 rounded mt-2">
                                        {!! nl2br(e($followUp->ai_response)) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- New Follow-up Form -->
                    @if($diagnosis->canAskFollowUp())
                        <div class="follow-up-form">
                            <h6><i class="fas fa-plus me-2"></i>Ask a Follow-up Question</h6>
                            <form id="followUpForm">
                                @csrf
                                <div class="mb-3">
                                    <textarea class="form-control" id="followUpQuestion" name="question" rows="3"
                                              placeholder="Ask a question about your diagnosis..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" id="submitFollowUp">
                                    <i class="fas fa-paper-plane me-2"></i>Ask Question
                                </button>
                                <div class="form-text">
                                    You have {{ 5 - $diagnosis->follow_up_count }} questions remaining.
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You have used all 5 follow-up questions for this diagnosis.
                            Please contact Dr. {{ $diagnosis->doctor->name }} directly for additional questions.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Review Section -->
            @if(!$diagnosis->patient_reviewed)
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Rate This Diagnosis</h5>
                    </div>
                    <div class="card-body">
                        <p>Please rate your experience with this diagnosis from Dr. {{ $diagnosis->doctor->name }}.</p>

                        <form action="{{ route('diagnosis.review.store', $diagnosis) }}" method="POST" id="reviewForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Rating *</label>
                                <div class="rating-stars" id="ratingStars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="star" data-rating="{{ $i }}">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" required>
                                <div class="rating-text mt-2" id="ratingText"></div>
                                @error('rating')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="review_text" class="form-label">Review (Optional)</label>
                                <textarea class="form-control" id="review_text" name="review_text" rows="4"
                                          placeholder="Share your experience with this diagnosis..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" id="submitReview">
                                <i class="fas fa-star me-2"></i>Submit Review
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Thank you for reviewing this diagnosis!
                </div>
            @endif

            <!-- Back Button -->
            <div class="text-center">
                <a href="{{ route('diagnosis.patient.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Diagnoses
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.diagnosis-content {
    font-size: 1.1rem;
    line-height: 1.6;
}

.follow-up-item {
    background-color: #f8f9fa;
}

.rating-stars {
    display: flex;
    gap: 5px;
    margin-bottom: 10px;
}

.rating-stars .star {
    font-size: 1.8rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
}

.rating-stars .star:hover {
    color: #ffc107;
}

.rating-stars .star.active {
    color: #ffc107;
}

.rating-stars .star.hover {
    color: #ffc107;
}

.rating-text {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Star Rating Functionality
    const ratingStars = document.getElementById('ratingStars');
    const ratingInput = document.getElementById('ratingInput');
    const ratingText = document.getElementById('ratingText');

    if (ratingStars) {
        const stars = ratingStars.querySelectorAll('.star');
        let selectedRating = 0;

        const ratingTexts = {
            1: 'Poor',
            2: 'Fair',
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };

        // Handle star hover
        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                if (selectedRating === 0) {
                    highlightStarsOnHover(rating);
                } else {
                    highlightStars(rating);
                }
            });

            star.addEventListener('mouseleave', function() {
                if (selectedRating === 0) {
                    stars.forEach(s => s.classList.remove('hover'));
                } else {
                    highlightStars(selectedRating);
                }
            });

            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                ratingInput.value = selectedRating;
                highlightStars(selectedRating);
                ratingText.textContent = ratingTexts[selectedRating];
            });
        });

        function highlightStars(rating) {
            stars.forEach((star, index) => {
                const starRating = parseInt(star.dataset.rating);
                star.classList.remove('active', 'hover');
                if (starRating <= rating) {
                    star.classList.add('active');
                }
            });
        }

        function highlightStarsOnHover(rating) {
            stars.forEach((star, index) => {
                const starRating = parseInt(star.dataset.rating);
                star.classList.remove('hover');
                if (starRating <= rating) {
                    star.classList.add('hover');
                }
            });
        }
    }

    // Review form validation
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const ratingValue = document.getElementById('ratingInput').value;
            if (!ratingValue || ratingValue === '0') {
                e.preventDefault();
                alert('Please select a rating before submitting your review.');
                return false;
            }
        });
    }

    const followUpForm = document.getElementById('followUpForm');
    const submitBtn = document.getElementById('submitFollowUp');
    const followUpsList = document.getElementById('followUpsList');

    if (followUpForm) {
        followUpForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(followUpForm);
            const question = formData.get('question').trim();

            if (!question) {
                alert('Please enter a question.');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            try {
                const response = await fetch('{{ route("diagnosis.follow-up.store", $diagnosis) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ question: question })
                });

                const data = await response.json();

                if (data.success) {
                    // Add new follow-up to the list
                    const followUpHtml = `
                        <div class="follow-up-item mb-4 p-3 border rounded">
                            <div class="question mb-2">
                                <strong><i class="fas fa-user me-2"></i>You asked:</strong>
                                <p class="mb-2">${question}</p>
                                <small class="text-muted">${data.followUp.created_at}</small>
                            </div>
                            <div class="answer">
                                <strong><i class="fas fa-robot me-2 text-info"></i>AI Response:</strong>
                                <div class="bg-light p-3 rounded mt-2">
                                    ${data.followUp.ai_response.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    `;

                    followUpsList.insertAdjacentHTML('beforeend', followUpHtml);

                    // Clear form
                    document.getElementById('followUpQuestion').value = '';

                    // Update remaining questions count
                    const remainingText = document.querySelector('.form-text');
                    if (remainingText) {
                        remainingText.textContent = `You have ${data.remaining_questions} questions remaining.`;
                    }

                    // Update badge
                    const badge = document.querySelector('.badge.bg-secondary');
                    if (badge) {
                        badge.textContent = `${5 - data.remaining_questions}/5 used`;
                    }

                    // Hide form if no questions remaining
                    if (data.remaining_questions === 0) {
                        document.querySelector('.follow-up-form').style.display = 'none';
                        followUpsList.insertAdjacentHTML('afterend', `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You have used all 5 follow-up questions for this diagnosis.
                                Please contact Dr. {{ $diagnosis->doctor->name }} directly for additional questions.
                            </div>
                        `);
                    }

                } else {
                    alert(data.error || 'Failed to submit question. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to submit question. Please try again.');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Ask Question';
            }
        });
    }
});
</script>
@endsection
