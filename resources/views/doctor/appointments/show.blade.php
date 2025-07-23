@extends('master')

@section('title', 'Appointment Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('doctor.appointments.index') }}" class="inline-flex items-center text-primary-600 hover:text-primary-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Appointments
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
                <!-- Patient Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Patient Information</h2>

                    <div class="flex items-center mb-6">
                        <!-- Patient Avatar -->
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-xl font-medium text-primary-600">
                                    {{ substr($appointment->patient->name, 0, 1) }}
                                </span>
                            </div>
                        </div>

                        <!-- Patient Details -->
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $appointment->patient->name }}</h3>
                            <p class="text-gray-600">{{ $appointment->patient->email }}</p>
                            @if($appointment->patient->phone)
                                <p class="text-gray-600">{{ $appointment->patient->phone }}</p>
                            @endif
                        </div>

                        <!-- Contact Actions -->
                        <div class="flex flex-col gap-2">
                            <a href="mailto:{{ $appointment->patient->email }}"
                               class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors text-sm text-center">
                                <i class="fas fa-envelope mr-1"></i>Email
                            </a>
                            @if($appointment->patient->phone)
                                <a href="tel:{{ $appointment->patient->phone }}"
                                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm text-center">
                                    <i class="fas fa-phone mr-1"></i>Call
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Patient Additional Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($appointment->patient->date_of_birth)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Date of Birth</label>
                                <p class="text-gray-900">{{ $appointment->patient->date_of_birth->format('M j, Y') }}</p>
                            </div>
                        @endif
                        @if($appointment->patient->address)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Address</label>
                                <p class="text-gray-900">{{ $appointment->patient->getFullAddress() }}</p>
                            </div>
                        @endif
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
                            <h3 class="font-medium text-gray-900 mb-2">Patient Notes</h3>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $appointment->patient_notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Doctor's Notes -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Doctor's Notes</h2>

                    @if($appointment->doctor_notes)
                        <div class="bg-primary-50 p-4 rounded-lg mb-4">
                            <p class="text-gray-700">{{ $appointment->doctor_notes }}</p>
                        </div>

                        @if($appointment->follow_up_required)
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    <span class="text-yellow-800 font-medium">Follow-up appointment recommended</span>
                                </div>
                            </div>
                        @endif
                    @else
                        @if($appointment->status == 'completed')
                            <p class="text-gray-500 italic">No notes added for this appointment.</p>
                        @else
                            <p class="text-gray-500 italic">Notes will be added when the appointment is completed.</p>
                        @endif
                    @endif
                </div>

                <!-- Review Section -->
                @if($appointment->status == 'completed' && $appointment->review)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Patient Review</h2>

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
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>

                    <div class="space-y-3">
                        @if($appointment->status == 'pending')
                            <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-check mr-2"></i>Confirm Appointment
                                </button>
                            </form>
                        @endif

                        @if($appointment->status == 'confirmed')
                            <button onclick="completeAppointment()"
                                    class="w-full bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-check-circle mr-2"></i>Complete Appointment
                            </button>

                            <button onclick="markNoShow()"
                                    class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-user-times mr-2"></i>Mark as No Show
                            </button>

                            @if($appointment->appointment_type == 'video_call')
                                <button class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-video mr-2"></i>Start Video Call
                                </button>
                            @endif
                        @endif

                        @if(in_array($appointment->status, ['pending', 'confirmed']))
                            <button onclick="cancelAppointment()"
                                    class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-times mr-2"></i>Cancel Appointment
                            </button>
                        @endif

                        <a href="mailto:{{ $appointment->patient->email }}"
                           class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors text-center block">
                            <i class="fas fa-envelope mr-2"></i>Email Patient
                        </a>

                        @if($appointment->patient->phone)
                            <a href="tel:{{ $appointment->patient->phone }}"
                               class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-center block">
                                <i class="fas fa-phone mr-2"></i>Call Patient
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
                                @if($appointment->cancellation_reason)
                                    <div class="mt-2">
                                        <span class="text-gray-600 text-xs">Cancellation reason:</span>
                                        <p class="text-gray-700 text-xs mt-1">{{ $appointment->cancellation_reason }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Appointment Modal -->
<div id="completeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Complete Appointment</h3>
        <form method="POST" action="{{ route('doctor.appointments.complete', $appointment) }}">
            @csrf
            <div class="mb-4">
                <label for="doctor_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Doctor's Notes (optional)
                </label>
                <textarea name="doctor_notes" id="doctor_notes" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Add any notes about the appointment...">{{ $appointment->doctor_notes }}</textarea>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="follow_up_required" {{ $appointment->follow_up_required ? 'checked' : '' }} class="mr-2">
                    <span class="text-sm text-gray-700">Follow-up appointment recommended</span>
                </label>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCompleteModal()"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    Complete Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Appointment</h3>
        <form method="POST" action="{{ route('doctor.appointments.cancel', $appointment) }}">
            @csrf
            <div class="mb-4">
                <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for cancellation <span class="text-red-500">*</span>
                </label>
                <textarea name="cancellation_reason" id="cancellation_reason" rows="3" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Please provide a reason for cancelling..."></textarea>
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
function completeAppointment() {
    const modal = document.getElementById('completeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCompleteModal() {
    const modal = document.getElementById('completeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

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

function markNoShow() {
    if (confirm('Are you sure you want to mark this appointment as no show?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("doctor.appointments.no-show", $appointment) }}';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals when clicking outside
document.getElementById('completeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCompleteModal();
    }
});

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});
</script>
@endsection
