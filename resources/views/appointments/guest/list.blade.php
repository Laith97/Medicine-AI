@extends('master')

@section('title', 'Your Appointments')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Your Appointments</h1>
            <p class="text-gray-600">Manage your appointments and leave reviews</p>
        </div>

        <div class="space-y-6">
            @foreach($appointments as $appointment)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-4">
                                @if($appointment->doctor->profile_image)
                                    <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                         alt="{{ $appointment->doctor->user->name }}"
                                         class="w-12 h-12 rounded-full object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                                        <i class="fas fa-user-md text-primary-600"></i>
                                    </div>
                                @endif
                                <div class="ml-3">
                                    <h3 class="font-semibold text-gray-900">{{ $appointment->doctor->user->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $appointment->doctor->specialty->name }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Appointment #</p>
                                    <p class="font-medium">{{ $appointment->appointment_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Date & Time</p>
                                    <p class="font-medium">{{ $appointment->appointment_date->format('M j, Y') }}</p>
                                    <p class="text-sm text-gray-600">{{ $appointment->appointment_date->format('g:i A') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($appointment->status === 'confirmed') bg-green-100 text-green-800
                                        @elseif($appointment->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($appointment->status === 'cancelled') bg-red-100 text-red-800
                                        @elseif($appointment->status === 'completed') bg-primary-100 text-primary-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Reason for Visit</p>
                                <p class="text-gray-900">{{ $appointment->reason }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row lg:flex-col gap-2 lg:ml-6">
                            <a href="{{ route('appointments.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                               class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                            </a>

                            @if($appointment->status === 'completed' && !$appointment->review)
                                <a href="{{ route('reviews.guest.create', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                                   class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-star mr-2"></i>
                                    Leave Review
                                </a>
                            @elseif($appointment->review)
                                <a href="{{ route('reviews.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                                   class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-star mr-2"></i>
                                    View Review
                                </a>
                            @endif

                            @if($appointment->canBeCancelled())
                                <form method="POST" action="{{ route('appointments.guest.cancel', $appointment->appointment_number) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="email" value="{{ $appointment->guest_email }}">
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"
                                            onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancel
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($appointments->isEmpty())
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-calendar-times text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No appointments found</h3>
                <p class="text-gray-600 mb-6">You don't have any appointments yet.</p>
                <a href="{{ route('doctors.index') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Find a Doctor
                </a>
            </div>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('appointments.guest.lookup') }}" class="text-primary-600 hover:text-primary-800">
                ← Search with different email
            </a>
        </div>
    </div>
</div>
@endsection
