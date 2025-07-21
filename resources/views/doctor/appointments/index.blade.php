@extends('layouts.app')

@section('title', 'Manage Appointments')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Appointments</h1>
                <p class="text-gray-600 mt-2">View and manage your patient appointments</p>
            </div>
            <a href="{{ route('doctor.dashboard') }}"
               class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" action="{{ route('doctor.appointments.index') }}" class="flex flex-wrap gap-4">
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
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>

                <!-- Clear Button -->
                <div class="flex items-end">
                    <a href="{{ route('doctor.appointments.index') }}"
                       class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        @if($appointments->count() > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Patient
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date & Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reason
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($appointments as $appointment)
                                <tr class="hover:bg-gray-50">
                                    <!-- Patient -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-blue-600">
                                                        {{ substr($appointment->patient->name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $appointment->patient->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $appointment->patient->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Date & Time -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $appointment->appointment_date->format('M j, Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $appointment->appointment_date->format('g:i A') }} - {{ $appointment->appointment_end->format('g:i A') }}
                                        </div>
                                    </td>

                                    <!-- Type -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} mr-2 text-gray-400"></i>
                                            <span class="text-sm text-gray-900">
                                                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'confirmed' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-blue-100 text-blue-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'no_show' => 'bg-gray-100 text-gray-800'
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$appointment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                        </span>
                                    </td>

                                    <!-- Reason -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            {{ $appointment->reason }}
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if($appointment->status == 'pending')
                                                <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($appointment->status == 'confirmed')
                                                <button onclick="completeAppointment({{ $appointment->id }})"
                                                        class="text-blue-600 hover:text-blue-900" title="Complete">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <button onclick="markNoShow({{ $appointment->id }})"
                                                        class="text-gray-600 hover:text-gray-900" title="No Show">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                            @endif

                                            @if(in_array($appointment->status, ['pending', 'confirmed']))
                                                <button onclick="cancelAppointment({{ $appointment->id }})"
                                                        class="text-red-600 hover:text-red-900" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                <p class="text-gray-600 mb-6">No appointments match your current filters.</p>
                <a href="{{ route('doctor.appointments.index') }}"
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    Clear Filters
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Complete Appointment Modal -->
<div id="completeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Complete Appointment</h3>
        <form id="completeForm" method="POST">
            @csrf
            <div class="mb-4">
                <label for="doctor_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Doctor's Notes (optional)
                </label>
                <textarea name="doctor_notes" id="doctor_notes" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Add any notes about the appointment..."></textarea>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="follow_up_required" class="mr-2">
                    <span class="text-sm text-gray-700">Follow-up appointment recommended</span>
                </label>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCompleteModal()"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
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
        <form id="cancelForm" method="POST">
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
function completeAppointment(appointmentId) {
    const modal = document.getElementById('completeModal');
    const form = document.getElementById('completeForm');
    form.action = `/doctor/appointments/${appointmentId}/complete`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCompleteModal() {
    const modal = document.getElementById('completeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function cancelAppointment(appointmentId) {
    const modal = document.getElementById('cancelModal');
    const form = document.getElementById('cancelForm');
    form.action = `/doctor/appointments/${appointmentId}/cancel`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function markNoShow(appointmentId) {
    if (confirm('Are you sure you want to mark this appointment as no show?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/doctor/appointments/${appointmentId}/no-show`;

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
