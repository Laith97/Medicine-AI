@extends('master')

@section('title', 'Reviews for Dr. ' . $doctor->user->name)

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-primary-custom">
                <i class="fas fa-arrow-left me-2"></i>Back to Doctor Profile
            </a>
        </div>

        <!-- Doctor Header -->
        <div class="table-card mb-4 overflow-hidden">
            <div class="dashboard-header py-2 border-bottom">
                <div class="d-flex align-items-center">
                    <!-- Profile Image -->
                    <div class="me-3">
                        @if($doctor->profile_image)
                            <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                 alt="{{ $doctor->user->name }}"
                                 class="rounded-circle border border-4 border-white"
                                 style="width: 96px; height: 96px; object-fit: cover;">
                        @else
                            <div class="rounded-circle border border-4 border-white bg-white d-flex align-items-center justify-content-center"
                                 style="width: 96px; height: 96px;">
                                <i class="fas fa-user-md text-primary fs-2"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Basic Info -->
                    <div class="flex-grow-1 text-white">
                        <h1>Dr. {{ $doctor->user->name }}</h1>
                        <p class="fs-5 mb-2">{{ $doctor->specialty->name }}</p>

                        <!-- Rating Summary -->
                        <div class="d-flex align-items-center mb-2">
                            <div class="text-warning me-2">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($ratingStats['average']))
                                        <i class="fas fa-star"></i>
                                    @elseif($i - 0.5 <= $ratingStats['average'])
                                        <i class="fas fa-star-half-alt"></i>
                                    @else
                                        <i class="far fa-star"></i>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-white-50">
                                {{ number_format($ratingStats['average'], 1) }} out of 5
                                ({{ $ratingStats['total'] }} {{ Str::plural('review', $ratingStats['total']) }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Rating Statistics -->
            <div class="col-lg-4">
                <div class="table-card sticky-top" style="top: 20px;">
                    <h3 class="h5 mb-4">Rating Breakdown</h3>

                    @if($ratingStats['total'] > 0)
                        <div class="d-flex flex-column gap-3 mb-4">
                            @for($i = 5; $i >= 1; $i--)
                                <div class="d-flex align-items-center">
                                    <span class="small fw-medium me-2" style="width: 20px;">{{ $i }}</span>
                                    <i class="fas fa-star text-warning small me-2"></i>
                                    <div class="flex-grow-1 bg-light rounded-pill me-2" style="height: 8px;">
                                        <div class="bg-warning rounded-pill h-100"
                                             style="width: {{ $ratingStats['breakdown'][$i]['percentage'] }}%"></div>
                                    </div>
                                    <span class="small text-muted" style="width: 30px;">{{ $ratingStats['breakdown'][$i]['count'] }}</span>
                                </div>
                            @endfor
                        </div>

                        <!-- Filter Options -->
                        <div class="mt-4 pt-4 border-top">
                            <h4 class="fw-medium mb-3">Filter Reviews</h4>
                            <div class="d-flex flex-column gap-2">
                                <button onclick="filterReviews('all')"
                                        class="filter-btn btn btn-primary-custom w-100 text-start py-2 active">
                                    All Reviews ({{ $ratingStats['total'] }})
                                </button>
                                @for($i = 5; $i >= 1; $i--)
                                    @if($ratingStats['breakdown'][$i]['count'] > 0)
                                        <button onclick="filterReviews({{ $i }})"
                                                class="filter-btn btn btn-outline-secondary w-100 text-start py-2">
                                            {{ $i }} Stars ({{ $ratingStats['breakdown'][$i]['count'] }})
                                        </button>
                                    @endif
                                @endfor
                            </div>
                        </div>

                        <!-- Sort Options -->
                        <div class="mt-4 pt-4 border-top">
                            <h4 class="fw-medium mb-3">Sort By</h4>
                            <select id="sortBy" onchange="sortReviews()" class="form-select">
                                <option value="latest">Most Recent</option>
                                <option value="oldest">Oldest First</option>
                                <option value="highest_rating">Highest Rating</option>
                                <option value="lowest_rating">Lowest Rating</option>
                            </select>
                        </div>
                    @else
                        <p class="text-muted text-center py-4">No reviews yet</p>
                    @endif
                </div>
            </div>

            <!-- Reviews List -->
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="mb-4 border-bottom pb-3">
                        <h2 class="h4 mb-0">Patient Reviews</h2>
                    </div>

                    <div id="reviews-container">
                        @if($reviews->count() > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($reviews as $review)
                                    <div class="p-6 review-item" data-rating="{{ $review->rating }}">
                                        <!-- Review Header -->
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-center">
                                                <!-- Rating Stars -->
                                                <div class="flex text-yellow-400 mr-3">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $review->rating)
                                                            <i class="fas fa-star"></i>
                                                        @else
                                                            <i class="far fa-star"></i>
                                                        @endif
                                                    @endfor
                                                </div>

                                                <!-- Reviewer Info -->
                                                <div>
                                                    <p class="font-medium text-gray-900">
                                                        @if($review->is_anonymous)
                                                            Anonymous Patient
                                                        @elseif($review->patient)
                                                            {{ $review->patient->name }}
                                                        @else
                                                            {{ $review->guest_name ?? 'Guest Patient' }}
                                                        @endif
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $review->created_at->format('M j, Y') }} •
                                                        {{ $review->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Source Badge -->
                                            <div class="flex items-center">
                                                @if($review->source === 'google')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fab fa-google mr-1"></i>
                                                        Google
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                                        <i class="fas fa-hospital mr-1"></i>
                                                        MedCura
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Review Comment -->
                                        @if($review->comment)
                                            <div class="mt-3">
                                                <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                                            </div>
                                        @endif

                                        <!-- Appointment Info -->
                                        @if($review->appointment)
                                            <div class="mt-4 pt-4 border-t border-gray-100">
                                                <p class="text-sm text-gray-500">
                                                    <i class="fas fa-calendar-check mr-1"></i>
                                                    Appointment: {{ $review->appointment->appointment_date->format('M j, Y') }}
                                                    at {{ $review->appointment->appointment_time->format('g:i A') }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="px-6 py-4 border-t border-gray-200">
                                {{ $reviews->links() }}
                            </div>
                        @else
                            <div class="p-12 text-center">
                                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Reviews Yet</h3>
                                <p class="text-gray-500">This doctor hasn't received any reviews yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentFilter = 'all';
let currentSort = 'latest';

function filterReviews(rating) {
    currentFilter = rating;
    loadReviews();

    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active', 'bg-primary-100', 'text-primary-800'));
    event.target.classList.add('active', 'bg-primary-100', 'text-primary-800');
}

function sortReviews() {
    currentSort = document.getElementById('sortBy').value;
    loadReviews();
}

function loadReviews() {
    const url = new URL('{{ route("doctors.reviews.ajax", $doctor) }}');

    if (currentFilter !== 'all') {
        url.searchParams.set('rating', currentFilter);
    }
    url.searchParams.set('sort_by', currentSort);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReviewsContainer(data.reviews);
                updatePagination(data.pagination);
            }
        })
        .catch(error => // console.error('Error loading reviews:', error));
}

function updateReviewsContainer(reviews) {
    const container = document.getElementById('reviews-container');

    if (reviews.length === 0) {
        container.innerHTML = `
            <div class="p-12 text-center">
                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Reviews Found</h3>
                <p class="text-gray-500">No reviews match your current filter.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="divide-y divide-gray-200">';

    reviews.forEach(review => {
        html += `
            <div class="p-6 review-item" data-rating="${review.rating}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <div class="flex text-yellow-400 mr-3">
                            ${generateStars(review.rating)}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                ${review.is_anonymous ? 'Anonymous Patient' : (review.patient ? review.patient.name : (review.guest_name || 'Guest Patient'))}
                            </p>
                            <p class="text-sm text-gray-500">
                                ${formatDate(review.created_at)} • ${formatRelativeTime(review.created_at)}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${review.source === 'google' ? 'bg-blue-100 text-blue-800' : 'bg-primary-100 text-primary-800'}">
                            <i class="${review.source === 'google' ? 'fab fa-google' : 'fas fa-hospital'} mr-1"></i>
                            ${review.source === 'google' ? 'Google' : 'MedCura'}
                        </span>
                    </div>
                </div>
                ${review.comment ? `<div class="mt-3"><p class="text-gray-700 leading-relaxed">${review.comment}</p></div>` : ''}
                ${review.appointment ? `
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-calendar-check mr-1"></i>
                            Appointment: ${formatDate(review.appointment.appointment_date)} at ${formatTime(review.appointment.appointment_time)}
                        </p>
                    </div>
                ` : ''}
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function generateStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    return stars;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
    if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + ' months ago';
    return Math.floor(diffInSeconds / 31536000) + ' years ago';
}

function updatePagination(pagination) {
    // Simple pagination update - you can enhance this based on your needs
    // For now, we'll just show basic info
}

// Initialize active filter button
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.filter-btn').classList.add('active', 'bg-primary-100', 'text-primary-800');
});
</script>
@endpush

@push('styles')
<style>
.filter-btn.active {
    background-color: var(--bs-primary);
    color: white;
}
</style>
@endpush
@endsection
