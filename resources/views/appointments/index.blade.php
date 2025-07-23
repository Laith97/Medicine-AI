@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Appointments</h1>
                <p class="text-gray-600 mt-2">Manage your upcoming and past appointments</p>
            </div>
            <a href="{{ route('doctors.index') }}"
               class="bg-primary-600 text-white px-6 py-3 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Book New Appointment
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" action="{{ route('appointments.index') }}" class="flex flex-wrap gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>No Show</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Submit Button -->
                <div class="flex items-end">
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>

                <!-- Clear Button -->
                <div class="flex items-end">
                    <a href="{{ route('appointments.index') }}"
                       class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        @if($appointments->count() > 0)
            <div class="space-y-6">
                @foreach($appointments as $appointment)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <!-- Appointment Info -->
                                <div class="flex-1">
                                    <div class="flex items-center mb-4">
                                        <!-- Doctor Image -->
                                        <div class="flex-shrink-0">
                                            @if($appointment->doctor->profile_image)
                                                <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                                     alt="{{ $appointment->doctor->user->name }}"
                                                     class="w-12 h-12 rounded-full object-cover">
                                            @else
                                                <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                                                    <i class="fas fa-user-md text-primary-600"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Doctor Details -->
                                        <div class="ml-4">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ $appointment->doctor->user->name }}
                                            </h3>
                                            <p class="text-primary-600">{{ $appointment->doctor->specialty->name }}</p>
                                        </div>

                                        <!-- Status Badge -->
                                        <div class="ml-auto">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'confirmed' => 'bg-green-100 text-green-800',
                                                    'completed' => 'bg-primary-100 text-primary-800',
                                                    'cancelled' => 'bg-red-100 text-red-800',
                                                    'no_show' => 'bg-gray-100 text-gray-800'
                                                ];
                                            @endphp
                                            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$appointment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Appointment Details -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>{{ $appointment->appointment_date->format('M j, Y') }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span>{{ $appointment->appointment_date->format('g:i A') }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} mr-2"></i>
                                            <span>{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                                        </div>
                                    </div>

                                    <!-- Reason -->
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-600 mb-1">Reason for visit:</p>
                                        <p class="text-gray-900">{{ $appointment->reason }}</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('appointments.show', $appointment) }}"
                                           class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors text-sm">
                                            <i class="fas fa-eye mr-1"></i>View Details
                                        </a>

                                        @if($appointment->canBeCancelled())
                                            <button onclick="cancelAppointment({{ $appointment->id }})"
                                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm">
                                                <i class="fas fa-times mr-1"></i>Cancel
                                            </button>
                                        @endif

                                        @if($appointment->canBeRescheduled())
                                            <button onclick="rescheduleAppointment({{ $appointment->id }})"
                                                    class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors text-sm">
                                                <i class="fas fa-calendar-alt mr-1"></i>Reschedule
                                            </button>
                                        @endif

                                        @if($appointment->status == 'completed' && !$appointment->review)
                                            <a href="{{ route('appointments.review', $appointment) }}"
                                               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                                                <i class="fas fa-star mr-1"></i>Leave Review
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Info for Upcoming Appointments -->
                        @if(in_array($appointment->status, ['pending', 'confirmed']) && $appointment->appointment_date->isFuture())
                            <div class="bg-primary-50 px-6 py-3 border-t">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-primary-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <span class="text-sm">
                                            Appointment in {{ $appointment->appointment_date->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if($appointment->appointment_type == 'video_call')
                                        <button class="bg-primary-600 text-white px-3 py-1 rounded text-sm hover:bg-primary-700 transition-colors">
                                            <i class="fas fa-video mr-1"></i>Join Call
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($appointments->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $appointments->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No appointments found</h3>
                <p class="text-gray-600 mb-6">You haven't booked any appointments yet.</p>
                <a href="{{ route('doctors.index') }}"
                   class="bg-primary-600 text-white px-6 py-3 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Book Your First Appointment
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Appointment</h3>
        <form id="cancelForm" method="POST">
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
function cancelAppointment(appointmentId) {
    const modal = document.getElementById('cancelModal');
    const form = document.getElementById('cancelForm');
    form.action = `/appointments/${appointmentId}/cancel`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function rescheduleAppointment(appointmentId) {
    // For now, redirect to the booking page
    // In a full implementation, you'd show a reschedule modal
    window.location.href = `/appointments/${appointmentId}/reschedule`;
}

// Close modal when clicking outside
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCancelModal();
    }
});
</script>
@endsection
