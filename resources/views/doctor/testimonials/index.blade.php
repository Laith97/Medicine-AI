@extends('master')

@section('title', 'Testimonials Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Testimonials</h1>
                <div class="text-muted">
                    <small>Manage which reviews appear publicly on your landing page</small>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    @if($reviews->count() > 0)
                        <div class="row">
                            @foreach($reviews as $review)
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="card h-100 {{ $review->is_public ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                     style="width: 40px; height: 40px; font-weight: bold; font-size: 0.9rem;">
                                                    @if($review->is_anonymous)
                                                        A
                                                    @elseif($review->patient_name)
                                                        @php
                                                            $names = explode(' ', trim($review->patient_name));
                                                            echo count($names) >= 2 ?
                                                                strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                                strtoupper(substr($names[0], 0, 1)) . '.';
                                                        @endphp
                                                    @elseif($review->user && $review->user->name)
                                                        @php
                                                            $names = explode(' ', trim($review->user->name));
                                                            echo count($names) >= 2 ?
                                                                strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                                strtoupper(substr($names[0], 0, 1)) . '.';
                                                        @endphp
                                                    @else
                                                        P.
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="star-rating mb-1">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <i class="fas fa-star{{ $i <= $review->rating ? ' text-warning' : ' text-muted' }}"></i>
                                                        @endfor
                                                    </div>
                                                    <small class="text-muted">{{ $review->created_at->format('M j, Y') }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-{{ $review->is_public ? 'success' : 'secondary' }}">
                                                {{ $review->is_public ? 'Public' : 'Private' }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">"{{ $review->comment }}"</p>

                                            <!-- Case Study Section -->
                                            <div class="mt-3">
                                                <label class="form-label small text-muted">Case Study (Optional)</label>
                                                <textarea class="form-control form-control-sm case-study-input"
                                                          data-review-id="{{ $review->id }}"
                                                          rows="3"
                                                          maxlength="1000"
                                                          placeholder="Add a case study or additional context for this testimonial...">{{ $review->case_study }}</textarea>
                                                <div class="form-text">This will be displayed below the testimonial on your landing page</div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-{{ $review->is_public ? 'warning' : 'success' }} toggle-public-btn"
                                                        data-review-id="{{ $review->id }}"
                                                        data-current-status="{{ $review->is_public ? 'public' : 'private' }}">
                                                    <i class="fas fa-{{ $review->is_public ? 'eye-slash' : 'eye' }}"></i>
                                                    {{ $review->is_public ? 'Make Private' : 'Make Public' }}
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary save-case-study-btn"
                                                        data-review-id="{{ $review->id }}">
                                                    <i class="fas fa-save"></i>
                                                    Save Case Study
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $reviews->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No reviews yet</h5>
                            <p class="text-muted">Patient reviews will appear here once they start leaving feedback.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Public Testimonials Preview -->
            @if($reviews->where('is_public', true)->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-eye text-success me-2"></i>
                            Public Testimonials Preview
                        </h5>
                        <small class="text-muted">This is how your testimonials will appear on your landing page</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($reviews->where('is_public', true)->take(3) as $review)
                                <div class="col-lg-4 mb-3">
                                    <div class="card review-card h-100" style="background: #f8f9fa; border-left: 4px solid var(--bs-success);">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                     style="width: 50px; height: 50px; font-weight: bold;">
                                                    @if($review->is_anonymous)
                                                        A
                                                    @elseif($review->patient_name)
                                                        @php
                                                            $names = explode(' ', trim($review->patient_name));
                                                            echo count($names) >= 2 ?
                                                                strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                                strtoupper(substr($names[0], 0, 1)) . '.';
                                                        @endphp
                                                    @elseif($review->user && $review->user->name)
                                                        @php
                                                            $names = explode(' ', trim($review->user->name));
                                                            echo count($names) >= 2 ?
                                                                strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                                strtoupper(substr($names[0], 0, 1)) . '.';
                                                        @endphp
                                                    @else
                                                        P.
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="star-rating mb-1" style="color: #ffc107;">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <i class="fas fa-star{{ $i <= $review->rating ? '' : ' text-muted' }}"></i>
                                                        @endfor
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ $review->created_at->format('M Y') }}
                                                        <span class="badge bg-success ms-2">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            Verified
                                                        </span>
                                                    </small>
                                                </div>
                                            </div>
                                            <p class="card-text">"{{ $review->comment }}"</p>
                                            @if($review->case_study)
                                                <div class="mt-3 p-3 bg-light rounded">
                                                    <h6 class="text-primary mb-2">
                                                        <i class="fas fa-notes-medical me-1"></i>
                                                        Case Study
                                                    </h6>
                                                    <p class="mb-0 small">{{ $review->case_study }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($reviews->where('is_public', true)->count() > 3)
                            <div class="text-center">
                                <small class="text-muted">
                                    And {{ $reviews->where('is_public', true)->count() - 3 }} more public testimonials...
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle public status
    $('.toggle-public-btn').click(function() {
        const btn = $(this);
        const reviewId = btn.data('review-id');
        const currentStatus = btn.data('current-status');

        $.ajax({
            url: `/doctor/testimonials/${reviewId}/toggle-public`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                btn.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Update button appearance
                    if (response.is_public) {
                        btn.removeClass('btn-success')
                           .addClass('btn-warning')
                           .data('current-status', 'public')
                           .html('<i class="fas fa-eye-slash"></i> Make Private');

                        // Update card border and badge
                        btn.closest('.card').removeClass('border-secondary').addClass('border-success');
                        btn.closest('.card').find('.badge').removeClass('bg-secondary').addClass('bg-success').text('Public');
                    } else {
                        btn.removeClass('btn-warning')
                           .addClass('btn-success')
                           .data('current-status', 'private')
                           .html('<i class="fas fa-eye"></i> Make Public');

                        // Update card border and badge
                        btn.closest('.card').removeClass('border-success').addClass('border-secondary');
                        btn.closest('.card').find('.badge').removeClass('bg-success').addClass('bg-secondary').text('Private');
                    }

                    // Show success message
                    showAlert('success', response.message);

                    // Refresh page after 2 seconds to update preview
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while updating the testimonial status.');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Save case study
    $('.save-case-study-btn').click(function() {
        const btn = $(this);
        const reviewId = btn.data('review-id');
        const caseStudy = $(`.case-study-input[data-review-id="${reviewId}"]`).val();

        $.ajax({
            url: `/doctor/testimonials/${reviewId}/case-study`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                case_study: caseStudy
            },
            beforeSend: function() {
                btn.prop('disabled', true);
                btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    btn.html('<i class="fas fa-check"></i> Saved');

                    // Reset button after 2 seconds
                    setTimeout(function() {
                        btn.html('<i class="fas fa-save"></i> Save Case Study');
                    }, 2000);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while saving the case study.');
                btn.html('<i class="fas fa-save"></i> Save Case Study');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    function showAlert(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alert);

        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
</script>
@endpush
