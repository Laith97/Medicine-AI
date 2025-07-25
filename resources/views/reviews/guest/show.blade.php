@extends('master')

@section('title', 'Your Review')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="mb-8">
            <a href="{{ route('appointments.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}" class="text-primary-600 hover:text-primary-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Appointment
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Your Review</h1>
            <p class="text-gray-600">Review for Dr. {{ $appointment->doctor->user->name }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Doctor Info -->
            <div class="flex items-center mb-8 pb-6 border-b border-gray-200">
                @if($appointment->doctor->profile_image)
                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                         alt="{{ $appointment->doctor->user->name }}"
                         class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                        <i class="fas fa-user-md text-primary-600 text-2xl"></i>
                    </div>
                @endif
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $appointment->doctor->user->name }}</h3>
                    <p class="text-gray-600">{{ $appointment->doctor->specialty->name }}</p>
                    <p class="text-sm text-gray-500">Appointment: {{ $appointment->appointment_date->format('M j, Y') }}</p>
                </div>
            </div>

            <!-- Review Content -->
            <div class="space-y-6">
                <!-- Rating -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Your Rating</h4>
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star text-2xl {{ $i <= $appointment->review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                            @endfor
                        </div>
                        <span class="text-lg font-medium text-gray-700 ml-3">
                            {{ $appointment->review->rating }}/5
                            @if($appointment->review->rating == 5) - Excellent
                            @elseif($appointment->review->rating == 4) - Very Good
                            @elseif($appointment->review->rating == 3) - Good
                            @elseif($appointment->review->rating == 2) - Fair
                            @else - Poor
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Comment -->
                @if($appointment->review->comment)
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-3">Your Review</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-800 leading-relaxed">{{ $appointment->review->comment }}</p>
                        </div>
                    </div>
                @endif

                <!-- Review Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Reviewer</h4>
                        <p class="text-gray-900">
                            @if($appointment->review->is_anonymous)
                                Anonymous
                            @else
                                {{ $appointment->review->guest_name ?: 'Guest User' }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Review Date</h4>
                        <p class="text-gray-900">{{ $appointment->review->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Status</h4>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @if($appointment->review->is_verified) bg-green-100 text-green-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            @if($appointment->review->is_verified)
                                Verified & Published
                            @else
                                Pending Verification
                            @endif
                        </span>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-1">Source</h4>
                        <p class="text-gray-900">{{ ucfirst($appointment->review->source) }} Review</p>
                    </div>
                </div>

                @if(!$appointment->review->is_verified)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                            <div>
                                <h3 class="font-medium text-yellow-900 mb-1">Verification Required</h3>
                                <p class="text-sm text-yellow-800 mb-3">
                                    Your review is pending verification. Please check your email for a verification link to publish your review.
                                </p>
                                <p class="text-xs text-yellow-700">
                                    Didn't receive the email? Check your spam folder or contact support.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($appointment->review->posted_to_google)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fab fa-google text-green-600 mt-1 mr-3"></i>
                            <div>
                                <h3 class="font-medium text-green-900 mb-1">Posted to Google Reviews</h3>
                                <p class="text-sm text-green-800">
                                    Your review has been successfully posted to Google Reviews on {{ $appointment->review->google_posted_at->format('M j, Y') }}.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('appointments.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                       class="flex-1 bg-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-400 transition-colors font-medium text-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Appointment
                    </a>
                    <a href="{{ route('appointments.guest.search') }}?email={{ $appointment->guest_email }}"
                       class="flex-1 bg-primary-600 text-white py-3 px-6 rounded-lg hover:bg-primary-700 transition-colors font-medium text-center">
                        <i class="fas fa-list mr-2"></i>
                        View All Appointments
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-primary-50 rounded-lg">
            <h3 class="font-medium text-primary-900 mb-2">Thank You!</h3>
            <p class="text-sm text-primary-800">
                Your feedback helps other patients make informed decisions and helps doctors improve their services.
            </p>
        </div>
    </div>
</div>
@endsection
