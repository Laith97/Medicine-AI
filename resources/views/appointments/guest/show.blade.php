@extends('master')

@section('title', 'Appointment Details')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="mb-8">
            <a href="{{ route('appointments.guest.search') }}?email={{ $appointment->guest_email }}" class="text-primary-600 hover:text-primary-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Appointments
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Appointment Details</h1>
            <p class="text-gray-600">Appointment #{{ $appointment->appointment_number }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Doctor Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Doctor Information</h2>
                    <div class="flex items-center">
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
                            @if($appointment->doctor->bio)
                                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($appointment->doctor->bio, 100) }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Appointment Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Appointment Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Date & Time</p>
                            <p class="font-medium text-gray-900">{{ $appointment->appointment_date->format('l, F j, Y') }}</p>
                            <p class="text-gray-600">{{ $appointment->appointment_date->format('g:i A') }} - {{ $appointment->appointment_end->format('g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Type</p>
                            <p class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Status</p>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                @if($appointment->status === 'confirmed') bg-green-100 text-green-800
                                @elseif($appointment->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($appointment->status === 'cancelled') bg-red-100 text-red-800
                                @elseif($appointment->status === 'completed') bg-primary-100 text-primary-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($appointment->status) }}
                            </span>
                        </div>
                        {{-- Consultation Fee hidden for clinic SaaS --}}
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Patient Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Name</p>
                            <p class="font-medium text-gray-900">{{ $appointment->guest_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Email</p>
                            <p class="font-medium text-gray-900">{{ $appointment->guest_email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Phone</p>
                            <p class="font-medium text-gray-900">{{ $appointment->guest_phone }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Date of Birth</p>
                            <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($appointment->guest_date_of_birth)->format('F j, Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Reason & Notes -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Visit Information</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Reason for Visit</p>
                            <p class="text-gray-900">{{ $appointment->reason }}</p>
                        </div>
                        @if($appointment->symptoms)
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Symptoms</p>
                                <p class="text-gray-900">{{ $appointment->symptoms }}</p>
                            </div>
                        @endif
                        @if($appointment->patient_notes)
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Additional Notes</p>
                                <p class="text-gray-900">{{ $appointment->patient_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        @if($appointment->status === 'completed' && !$appointment->review)
                            <a href="{{ route('reviews.guest.create', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-star mr-2"></i>
                                Leave Review
                            </a>
                        @elseif($appointment->review)
                            <a href="{{ route('reviews.guest.show', ['appointment' => $appointment->appointment_number, 'email' => $appointment->guest_email]) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-star mr-2"></i>
                                View Review
                            </a>
                        @endif

                        @if($appointment->canBeCancelled())
                            <form method="POST" action="{{ route('appointments.guest.cancel', $appointment->appointment_number) }}">
                                @csrf
                                <input type="hidden" name="email" value="{{ $appointment->guest_email }}">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
                                        onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel Appointment
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Important Information -->
                <div class="bg-primary-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-medium text-primary-900 mb-2">Important Information</h3>
                    <ul class="text-sm text-primary-800 space-y-1">
                        @if($appointment->appointment_type === 'in_person')
                            <li>• Please arrive 15 minutes early</li>
                            <li>• Bring a valid ID</li>
                        @elseif($appointment->appointment_type === 'video_call')
                            <li>• You'll receive a video call link via email</li>
                            <li>• Test your camera and microphone beforehand</li>
                        @else
                            <li>• The doctor will call you at the scheduled time</li>
                            <li>• Ensure your phone is available</li>
                        @endif
                        <li>• Contact the clinic if you need to reschedule</li>
                    </ul>
                </div>

                @if(!$appointment->is_verified)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-medium text-yellow-900 mb-2">Verification Required</h3>
                        <p class="text-sm text-yellow-800 mb-3">Please verify your appointment using the token sent to your email.</p>
                        <form method="POST" action="{{ route('appointments.guest.verify', $appointment->appointment_number) }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $appointment->guest_email }}">
                            <div class="mb-3">
                                <input type="text" name="token" placeholder="Enter verification token"
                                       class="w-full px-3 py-2 text-sm border border-yellow-300 rounded focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                            </div>
                            <button type="submit" class="w-full bg-yellow-600 text-white py-2 px-3 rounded text-sm font-medium hover:bg-yellow-700 transition-colors">
                                Verify Appointment
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
