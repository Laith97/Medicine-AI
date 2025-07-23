@extends('layouts.app')

@section('title', 'Doctor Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, Dr. {{ explode(' ', $doctor->user->name)[1] ?? $doctor->user->name }}</h1>
            <p class="text-gray-600 mt-2">Here's what's happening with your practice today</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-primary-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Today's Appointments</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['today_appointments'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Approval</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_appointments'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Average Rating -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-star text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Average Rating</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['average_rating'], 1) }}</p>
                    </div>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">This Month</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($stats['revenue_this_month'], 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Today's Schedule -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Today's Schedule</h2>
                            <span class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</span>
                        </div>
                    </div>

                    <div class="p-6">
                        @if($todayAppointments->count() > 0)
                            <div class="space-y-4">
                                @foreach($todayAppointments as $appointment)
                                    <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <!-- Time -->
                                        <div class="flex-shrink-0 w-20">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $appointment->appointment_date->format('g:i A') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}min
                                            </div>
                                        </div>

                                        <!-- Patient Info -->
                                        <div class="flex-1 ml-4">
                                            <div class="flex items-center">
                                                <h3 class="text-sm font-medium text-gray-900">{{ $appointment->patient->name }}</h3>
                                                <span class="ml-2 px-2 py-1 text-xs rounded-full {{ $appointment->status == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $appointment->reason }}</p>
                                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                                <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} mr-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex-shrink-0 ml-4">
                                            <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                               class="text-primary-600 hover:text-primary-800 text-sm">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-check text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No appointments scheduled for today</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('doctor.appointments.index') }}"
                           class="w-full bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition-colors text-center block">
                            <i class="fas fa-calendar mr-2"></i>View All Appointments
                        </a>
                        <a href="{{ route('doctor.availability.index') }}"
                           class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-center block">
                            <i class="fas fa-clock mr-2"></i>Manage Availability
                        </a>
                        <a href="{{ route('doctor.reviews.index') }}"
                           class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors text-center block">
                            <i class="fas fa-star mr-2"></i>View Reviews
                        </a>
                    </div>
                </div>

                <!-- Pending Appointments -->
                @if($pendingAppointments->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Appointments</h3>
                        <div class="space-y-3">
                            @foreach($pendingAppointments as $appointment)
                                <div class="p-3 border border-yellow-200 bg-yellow-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $appointment->patient->name }}</p>
                                            <p class="text-xs text-gray-600">{{ $appointment->appointment_date->format('M j, g:i A') }}</p>
                                        </div>
                                        <div class="flex gap-1">
                                            <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-xs">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                               class="text-primary-600 hover:text-primary-800 text-xs ml-2">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('doctor.appointments.index', ['status' => 'pending']) }}"
                               class="text-primary-600 hover:text-primary-800 text-sm">
                                View all pending →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Reviews -->
                @if($recentReviews->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Reviews</h3>
                        <div class="space-y-3">
                            @foreach($recentReviews as $review)
                                <div class="p-3 border border-gray-200 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <div class="flex text-yellow-400 mr-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    <i class="fas fa-star text-xs"></i>
                                                @else
                                                    <i class="far fa-star text-xs"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span class="text-xs text-gray-600">
                                            {{ $review->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if($review->comment)
                                        <p class="text-sm text-gray-700">{{ Str::limit($review->comment, 80) }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 mt-1">
                                        by {{ $review->is_anonymous ? 'Anonymous' : $review->patient->name }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('doctor.reviews.index') }}"
                               class="text-primary-600 hover:text-primary-800 text-sm">
                                View all reviews →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Upcoming Appointments -->
                @if($upcomingAppointments->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming This Week</h3>
                        <div class="space-y-3">
                            @foreach($upcomingAppointments as $appointment)
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $appointment->patient->name }}</p>
                                        <p class="text-xs text-gray-600">
                                            {{ $appointment->appointment_date->format('M j, g:i A') }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full {{ $appointment->status == 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
