@extends('layouts.app')

@section('title', 'Leave a Review')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="mb-8">
            <a href="{{ route('appointments.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Appointment
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Leave a Review</h1>
            <p class="text-gray-600">Share your experience with Dr. {{ $appointment->doctor->user->name }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Doctor Info -->
            <div class="flex items-center mb-8 pb-6 border-b border-gray-200">
                @if($appointment->doctor->profile_image)
                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                         alt="{{ $appointment->doctor->user->name }}"
                         class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user-md text-blue-600 text-2xl"></i>
                    </div>
                @endif
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $appointment->doctor->user->name }}</h3>
                    <p class="text-gray-600">{{ $appointment->doctor->specialty->name }}</p>
                    <p class="text-sm text-gray-500">Appointment: {{ $appointment->appointment_date->format('M j, Y') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('reviews.guest.store') }}">
                @csrf
                <input type="hidden" name="appointment_number" value="{{ $appointment->appointment_number }}">
                <input type="hidden" name="guest_email" value="{{ $appointment->guest_email }}">

                <!-- Rating -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Overall Rating <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1" id="rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="rating-star text-3xl text-gray-300 hover:text-yellow-400 focus:outline-none transition-colors" data-rating="{{ $i }}">
                                    <i class="fas fa-star"></i>
                                </button>
                            @endfor
                        </div>
                        <span id="rating-text" class="text-sm text-gray-600 ml-4"></span>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" required>
                    @error('rating')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Review Comment -->
                <div class="mb-6">
                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                        Your Review (Optional)
                    </label>
                    <textarea name="comment" id="comment" rows="5"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Share your experience with this doctor...">{{ old('comment') }}</textarea>
                    <p class="text-sm text-gray-500 mt-1">Help other patients by sharing details about your visit.</p>
                    @error('comment')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reviewer Name -->
                <div class="mb-6">
                    <label for="guest_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Your Name
                    </label>
                    <input type="text" name="guest_name" id="guest_name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter your name" value="{{ old('guest_name', $appointment->guest_name) }}">
                    <p class="text-sm text-gray-500 mt-1">This will be displayed with your review unless you choose to remain anonymous.</p>
                    @error('guest_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Anonymous Option -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_anonymous" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ old('is_anonymous') ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700">Post this review anonymously</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Your name will not be displayed if you choose this option.</p>
                </div>

                <!-- Google Reviews Consent -->
                <div class="mb-8">
                    <label class="flex items-start">
                        <input type="checkbox" name="consent_google_posting" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-1" {{ old('consent_google_posting') ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700">
                            I consent to having this review posted on Google Reviews to help other patients find quality healthcare.
                            <span class="block text-gray-500 mt-1">This helps improve the doctor's online presence and assists other patients in making informed decisions.</span>
                        </span>
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Review
                    </button>
                    <a href="{{ route('appointments.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                       class="flex-1 bg-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-400 transition-colors font-medium text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-medium text-blue-900 mb-2">Review Guidelines</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>• Be honest and constructive in your feedback</li>
                <li>• Focus on your experience with the doctor and treatment</li>
                <li>• Avoid sharing personal medical information</li>
                <li>• Guest reviews require email verification before being published</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating-input');
    const ratingText = document.getElementById('rating-text');
    const anonymousCheckbox = document.querySelector('input[name="is_anonymous"]');
    const nameInput = document.getElementById('guest_name');

    const ratingLabels = {
        1: 'Poor',
        2: 'Fair',
        3: 'Good',
        4: 'Very Good',
        5: 'Excellent'
    };

    // Handle star rating
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingInput.value = rating;
            ratingText.textContent = ratingLabels[rating];

            // Update star colors
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.remove('text-gray-300');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-300');
                }
            });
        });

        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                }
            });
        });
    });

    document.getElementById('rating-stars').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingInput.value) || 0;
        stars.forEach((s, i) => {
            if (i < currentRating) {
                s.classList.add('text-yellow-400');
                s.classList.remove('text-gray-300');
            } else {
                s.classList.remove('text-yellow-400');
                s.classList.add('text-gray-300');
            }
        });
    });

    // Handle anonymous checkbox
    anonymousCheckbox.addEventListener('change', function() {
        if (this.checked) {
            nameInput.disabled = true;
            nameInput.classList.add('bg-gray-100');
        } else {
            nameInput.disabled = false;
            nameInput.classList.remove('bg-gray-100');
        }
    });
});
</script>
@endsection
