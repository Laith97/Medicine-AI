@extends('layouts.app')

@section('title', 'Reviews for Dr. ' . $doctor->user->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('doctors.show', $doctor) }}" class="inline-flex items-center text-primary-600 hover:text-primary-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Doctor Profile
            </a>
        </div>

        <!-- Doctor Header -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-primary-600 to-primary-800 px-6 py-6">
                <div class="flex items-center">
                    <!-- Profile Image -->
                    <div class="flex-shrink-0">
                        @if($doctor->profile_image)
                            <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                 alt="{{ $doctor->user->name }}"
                                 class="w-20 h-20 rounded-full border-4 border-white object-cover">
                        @else
                            <div class="w-20 h-20 rounded-full border-4 border-white bg-white flex items-center justify-center">
                                <i class="fas fa-user-md text-2xl text-primary-600"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Basic Info -->
                    <div class="ml-6 text-white">
                        <h1 class="text-2xl font-bold">Dr. {{ $doctor->user->name }}</h1>
                        <p class="text-lg text-primary-100 mb-2">{{ $doctor->specialty->name }}</p>

                        <!-- Rating Summary -->
                        <div class="flex items-center">
                            <div class="flex text-yellow-400 mr-3">
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
                            <span class="text-primary-100">
                                {{ number_format($ratingStats['average'], 1) }} out of 5
                                ({{ $ratingStats['total'] }} {{ Str::plural('review', $ratingStats['total']) }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Rating Statistics -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Rating Breakdown</h3>

                    @if($ratingStats['total'] > 0)
                        <div class="space-y-3">
                            @for($i = 5; $i >= 1; $i--)
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-700 w-8">{{ $i }}</span>
                                    <i class="fas fa-star text-yellow-400 text-sm mr-2"></i>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-yellow-400 h-2 rounded-full"
                                             style="width: {{ $ratingStats['breakdown'][$i]['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600 w-12">{{ $ratingStats['breakdown'][$i]['count'] }}</span>
                                </div>
                            @endfor
                        </div>

                        <!-- Filter Options -->
                        <div class="mt-6 pt-6 border-t">
                            <h4 class="font-medium text-gray-900 mb-3">Filter Reviews</h4>
                            <div class="space-y-2">
                                <button onclick="filterReviews('all')"
                                        class="filter-btn w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50 active">
                                    All Reviews ({{ $ratingStats['total'] }})
                                </button>
                                @for($i = 5; $i >= 1; $i--)
                                    @if($ratingStats['breakdown'][$i]['count'] > 0)
                                        <button onclick="filterReviews({{ $i }})"
                                                class="filter-btn w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-50">
                                            {{ $i }} Stars ({{ $ratingStats['breakdown'][$i]['count'] }})
                                        </button>
                                    @endif
                                @endfor
                            </div>
                        </div>

                        <!-- Sort Options -->
                        <div class="mt-6 pt-6 border-t">
                            <h4 class="font-medium text-gray-900 mb-3">Sort By</h4>
                            <select id="sortBy" onchange="sortReviews()" class="w-full border-gray-300 rounded-lg text-sm">
                                <option value="latest">Most Recent</option>
                                <option value="oldest">Oldest First</option>
                                <option value="highest_rating">Highest Rating</option>
                                <option value="lowest_rating">Lowest Rating</option>
                            </select>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No reviews yet</p>
                    @endif
                </div>
            </div>

            <!-- Reviews List -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Patient Reviews</h2>
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
        .catch(error => console.error('Error loading reviews:', error));
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
    background-color: rgb(239 246 255);
    color: rgb(29 78 216);
}
</style>
@endpush
@endsection
