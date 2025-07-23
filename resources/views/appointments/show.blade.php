@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('appointments.index') }}" class="inline-flex items-center text-primary-600 hover:text-primary-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to My Appointments
            </a>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Appointment Details</h1>
                    <p class="text-gray-600">Appointment #{{ $appointment->id }}</p>
                </div>

                <!-- Status Badge -->
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-green-100 text-green-800',
                        'completed' => 'bg-primary-100 text-primary-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        'no_show' => 'bg-gray-100 text-gray-800'
                    ];
                @endphp
                <span class="px-4 py-2 rounded-full text-sm font-medium {{ $statusColors[$appointment->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Doctor Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Doctor Information</h2>

                    <div class="flex items-center">
                        <!-- Doctor Image -->
                        <div class="flex-shrink-0">
                            @if($appointment->doctor->profile_image)
                                <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                     alt="{{ $appointment->doctor->user->name }}"
                                     class="w-16 h-16 rounded-full object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                                    <i class="fas fa-user-md text-2xl text-primary-600"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Doctor Details -->
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $appointment->doctor->user->name }}</h3>
                            <p class="text-primary-600 mb-2">{{ $appointment->doctor->specialty->name }}</p>

                            <!-- Rating -->
                            <div class="flex items-center">
                                <div class="flex text-yellow-400 mr-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($appointment->doctor->average_rating))
                                            <i class="fas fa-star text-sm"></i>
                                        @elseif($i - 0.5 <= $appointment->doctor->average_rating)
                                            <i class="fas fa-star-half-alt text-sm"></i>
                                        @else
                                            <i class="far fa-star text-sm"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span class="text-sm text-gray-600">
                                    {{ number_format($appointment->doctor->average_rating, 1) }} ({{ $appointment->doctor->total_reviews }} reviews)
                                </span>
                            </div>
                        </div>

                        <!-- Contact Actions -->
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('doctors.show', $appointment->doctor) }}"
                               class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors text-sm text-center">
                                View Profile
                            </a>
                            @if($appointment->doctor->phone)
                                <a href="tel:{{ $appointment->doctor->phone }}"
                                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm text-center">
                                    <i class="fas fa-phone mr-1"></i>Call
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Appointment Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Appointment Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Date & Time</h3>
                            <div class="space-y-2">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-calendar mr-2"></i>
                                    <span>{{ $appointment->appointment_date->format('l, F j, Y') }}</span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span>{{ $appointment->appointment_date->format('g:i A') }} - {{ $appointment->appointment_end->format('g:i A') }}</span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-hourglass-half mr-2"></i>
                                    <span>{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }} minutes</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Appointment Type</h3>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} mr-2"></i>
                                <span>{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="font-medium text-gray-900 mb-2">Reason for Visit</h3>
                        <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $appointment->reason }}</p>
                    </div>

                    @if($appointment->symptoms)
                        <div class="mt-4">
                            <h3 class="font-medium text-gray-900 mb-2">Symptoms</h3>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $appointment->symptoms }}</p>
                        </div>
                    @endif

                    @if($appointment->patient_notes)
                        <div class="mt-4">
                            <h3 class="font-medium text-gray-900 mb-2">Additional Notes</h3>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $appointment->patient_notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Doctor's Notes (if completed) -->
                @if($appointment->status == 'completed' && $appointment->doctor_notes)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Doctor's Notes</h2>
                        <div class="bg-primary-50 p-4 rounded-lg">
                            <p class="text-gray-700">{{ $appointment->doctor_notes }}</p>
                        </div>

                        @if($appointment->follow_up_required)
                            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    <span class="text-yellow-800 font-medium">Follow-up appointment recommended</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Review Section -->
                @if($appointment->status == 'completed')
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Review</h2>

                        @if($appointment->review)
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="flex text-yellow-400 mr-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $appointment->review->rating)
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-600">
                                        Reviewed on {{ $appointment->review->created_at->format('M j, Y') }}
                                    </span>
                                </div>
                                @if($appointment->review->comment)
                                    <p class="text-gray-700">{{ $appointment->review->comment }}</p>
                                @endif
                                <div class="mt-3">
                                    <a href="{{ route('reviews.show', $appointment->review) }}"
                                       class="text-primary-600 hover:text-primary-800 text-sm">
                                        View full review
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600 mb-4">How was your appointment?</p>
                                <a href="{{ route('appointments.review', $appointment) }}"
                                   class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-star mr-2"></i>
                                    Leave a Review
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>

                    <div class="space-y-3">
                        @if($appointment->canBeCancelled())
                            <button onclick="cancelAppointment()"
                                    class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel Appointment
                            </button>
                        @endif

                        @if($appointment->canBeRescheduled())
                            <button onclick="rescheduleAppointment()"
                                    class="w-full bg-yellow-600 text-white py-2 px-4 rounded-lg hover:bg-yellow-700 transition-colors">
                                <i class="fas fa-calendar-alt mr-2"></i>Reschedule
                            </button>
                        @endif

                        @if(in_array($appointment->status, ['pending', 'confirmed']) && $appointment->appointment_type == 'video_call')
                            <button class="w-full bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-video mr-2"></i>Join Video Call
                            </button>
                        @endif

                        <a href="{{ route('doctors.show', $appointment->doctor) }}"
                           class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors text-center block">
                            <i class="fas fa-user-md mr-2"></i>View Doctor Profile
                        </a>

                        @if($appointment->doctor->phone)
                            <a href="tel:{{ $appointment->doctor->phone }}"
                               class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-center block">
                                <i class="fas fa-phone mr-2"></i>Call Doctor's Office
                            </a>
                        @endif
                    </div>

                    <!-- Appointment Summary -->
                    <div class="mt-6 pt-6 border-t">
                        <h4 class="font-medium text-gray-900 mb-3">Summary</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Consultation Fee:</span>
                                <span class="font-medium">${{ number_format($appointment->consultation_fee / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Booked on:</span>
                                <span class="font-medium">{{ $appointment->created_at->format('M j, Y') }}</span>
                            </div>
                            @if($appointment->cancelled_at)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Cancelled on:</span>
                                    <span class="font-medium">{{ $appointment->cancelled_at->format('M j, Y') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Important Information -->
                    @if(in_array($appointment->status, ['pending', 'confirmed']))
                        <div class="mt-6 pt-6 border-t">
                            <h4 class="font-medium text-gray-900 mb-3">Important Information</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                @if($appointment->appointment_type == 'in_person')
                                    <li>• Arrive 15 minutes early</li>
                                    <li>• Bring valid ID and insurance card</li>
                                    <li>• Wear a mask if required</li>
                                @elseif($appointment->appointment_type == 'video_call')
                                    <li>• Test your camera and microphone</li>
                                    <li>• Ensure stable internet connection</li>
                                    <li>• Join the call 5 minutes early</li>
                                @else
                                    <li>• Ensure your phone is charged</li>
                                    <li>• Be in a quiet location</li>
                                    <li>• Have your medical history ready</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Appointment</h3>
        <form method="POST" action="{{ route('appointments.cancel', $appointment) }}">
            @csrf
            <div class="mb-4">
                <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for cancellation (optional)
                </label>
                <textarea name="cancellation_reason" id="cancellation_reason" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Please let us know why you're cancelling..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCancelModal()"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Keep Appointment
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Cancel Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function cancelAppointment() {
    const modal = document.getElementById('cancelModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function rescheduleAppointment() {
    // For now, redirect to the booking page
    // In a full implementation, you'd show a reschedule modal
    alert('Reschedule functionality will be implemented in the next phase.');
}

// Close modal when clicking outside
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});
</script>
@endsection
