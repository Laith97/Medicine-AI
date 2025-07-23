@extends('layouts.app')

@section('title', $doctor->user->name . ' - Doctor Profile')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('doctors.index') }}" class="inline-flex items-center text-primary-600 hover:text-primary-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Doctors
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Doctor Profile -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-primary-600 to-primary-800 px-6 py-8">
                        <div class="flex items-center">
                            <!-- Profile Image -->
                            <div class="flex-shrink-0">
                                @if($doctor->profile_image)
                                    <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                         alt="{{ $doctor->user->name }}"
                                         class="w-24 h-24 rounded-full border-4 border-white object-cover">
                                @else
                                    <div class="w-24 h-24 rounded-full border-4 border-white bg-white flex items-center justify-center">
                                        <i class="fas fa-user-md text-3xl text-primary-600"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Basic Info -->
                            <div class="ml-6 text-white">
                                <h1 class="text-3xl font-bold">{{ $doctor->user->name }}</h1>
                                <p class="text-xl text-primary-100 mb-2">{{ $doctor->specialty->name }}</p>

                                <!-- Rating -->
                                <div class="flex items-center mb-2">
                                    <div class="flex text-yellow-400">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($doctor->average_rating))
                                                <i class="fas fa-star"></i>
                                            @elseif($i - 0.5 <= $doctor->average_rating)
                                                <i class="fas fa-star-half-alt"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="ml-2 text-primary-100">
                                        {{ number_format($doctor->average_rating, 1) }} ({{ $doctor->total_reviews }} reviews)
                                    </span>
                                </div>

                                <!-- Verification Badge -->
                                @if($doctor->is_verified)
                                    <div class="inline-flex items-center bg-green-500 text-white px-3 py-1 rounded-full text-sm">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Verified Doctor
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Quick Info -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-clock text-2xl text-primary-600 mb-2"></i>
                                <p class="text-sm text-gray-600">Appointment Duration</p>
                                <p class="font-semibold">{{ $doctor->appointment_duration }} minutes</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-dollar-sign text-2xl text-accent-600 mb-2"></i>
                                <p class="text-sm text-gray-600">Consultation Fee</p>
                                <p class="font-semibold">${{ number_format($doctor->consultation_fee / 100, 2) }}</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-language text-2xl text-primary-600 mb-2"></i>
                                <p class="text-sm text-gray-600">Languages</p>
                                <p class="font-semibold">{{ implode(', ', $doctor->languages ?? ['English']) }}</p>
                            </div>
                        </div>

                        <!-- About -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">About Dr. {{ explode(' ', $doctor->user->name)[1] ?? $doctor->user->name }}</h2>
                            <p class="text-gray-700 leading-relaxed">{{ $doctor->bio }}</p>
                        </div>

                        <!-- Contact Information -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">Contact Information</h2>
                            <div class="space-y-2">
                                @if($doctor->phone)
                                    <div class="flex items-center text-gray-700">
                                        <i class="fas fa-phone mr-3 text-primary-600"></i>
                                        <span>{{ $doctor->phone }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-envelope mr-3 text-primary-600"></i>
                                    <span>{{ $doctor->user->email }}</span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <i class="fas fa-map-marker-alt mr-3 text-primary-600"></i>
                                    <span>{{ $doctor->full_address }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Availability -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">Weekly Availability</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $daysOfWeek = [
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday'
                                    ];
                                    $groupedSlots = $doctor->availabilitySlots->groupBy('day_of_week');
                                @endphp

                                @foreach($daysOfWeek as $day => $dayName)
                                    <div class="flex justify-between items-center p-3 border rounded-lg">
                                        <span class="font-medium">{{ $dayName }}</span>
                                        <div class="text-sm text-gray-600">
                                            @if($groupedSlots->has($day))
                                                @foreach($groupedSlots[$day] as $timeSlot)
                                                    <div>{{ date('g:i A', strtotime($timeSlot->start_time)) }} - {{ date('g:i A', strtotime($timeSlot->end_time)) }}</div>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">Not Available</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Recent Reviews -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-900">Recent Reviews</h2>
                                <a href="{{ route('doctors.reviews', $doctor) }}" class="text-primary-600 hover:text-primary-800">
                                    View All Reviews
                                </a>
                            </div>

                            @if($doctor->approvedReviews->count() > 0)
                                <div class="space-y-4">
                                    @foreach($doctor->approvedReviews->take(3) as $review)
                                        <div class="border-l-4 border-primary-500 pl-4 py-2">
                                            <div class="flex items-center mb-2">
                                                <div class="flex text-yellow-400 mr-2">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $review->rating)
                                                            <i class="fas fa-star text-sm"></i>
                                                        @else
                                                            <i class="far fa-star text-sm"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                                <span class="text-sm text-gray-600">
                                                    by {{ $review->is_anonymous ? 'Anonymous' : $review->patient->name }}
                                                    • {{ $review->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            @if($review->comment)
                                                <p class="text-gray-700">{{ $review->comment }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No reviews yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Book an Appointment</h3>

                    @auth
                        @if(count($availableSlots) > 0)
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-3">Available slots for the next 7 days:</p>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    @foreach($availableSlots as $date => $slots)
                                        <div class="border rounded-lg p-3">
                                            <div class="font-medium text-gray-900 mb-2">
                                                {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                                                <span class="text-sm text-gray-500">({{ \Carbon\Carbon::parse($date)->format('l') }})</span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1">
                                                @foreach($slots->take(4) as $timeSlot)
                                                    <button class="text-xs bg-primary-50 text-primary-700 px-2 py-1 rounded hover:bg-primary-100 transition-colors">
                                                        {{ \Carbon\Carbon::parse($timeSlot['start_time'])->format('g:i A') }}
                                                    </button>
                                                @endforeach
                                                @if($slots->count() > 4)
                                                    <span class="text-xs text-gray-500 px-2 py-1">+{{ $slots->count() - 4 }} more</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('appointments.create', $doctor) }}"
                               class="w-full bg-accent-600 text-white text-center py-3 px-4 rounded-lg hover:bg-accent-700 transition-colors font-medium">
                                Book Appointment
                            </a>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times text-3xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500">No available slots in the next 7 days</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center">
                            <p class="text-gray-600 mb-4">Please log in to book an appointment</p>
                            <a href="{{ route('login') }}"
                               class="w-full bg-primary-600 text-white text-center py-3 px-4 rounded-lg hover:bg-primary-700 transition-colors font-medium block">
                                Login to Book
                            </a>
                        </div>
                    @endauth

                    <!-- Quick Actions -->
                    <div class="mt-6 pt-6 border-t">
                        <h4 class="font-medium text-gray-900 mb-3">Quick Actions</h4>
                        <div class="space-y-2">
                            <a href="{{ route('doctors.reviews', $doctor) }}"
                               class="flex items-center text-gray-600 hover:text-primary-600 transition-colors">
                                <i class="fas fa-star mr-2"></i>
                                View All Reviews
                            </a>
                            <a href="mailto:{{ $doctor->user->email }}"
                               class="flex items-center text-gray-600 hover:text-primary-600 transition-colors">
                                <i class="fas fa-envelope mr-2"></i>
                                Send Message
                            </a>
                            @if($doctor->phone)
                                <a href="tel:{{ $doctor->phone }}"
                                   class="flex items-center text-gray-600 hover:text-primary-600 transition-colors">
                                    <i class="fas fa-phone mr-2"></i>
                                    Call Office
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
