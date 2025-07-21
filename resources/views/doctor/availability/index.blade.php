@extends('layouts.app')

@section('title', 'Manage Availability')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Availability</h1>
                <p class="text-gray-600 mt-2">Set your weekly schedule and appointment slots</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('doctor.dashboard') }}"
                   class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                <a href="{{ route('doctor.availability.create') }}"
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Add Time Slot
                </a>
            </div>
        </div>

        <!-- Weekly Schedule -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Weekly Schedule</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    @foreach($daysOfWeek as $day => $dayName)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ $dayName }}</h3>
                                <button onclick="showBulkModal('{{ $day }}')"
                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-plus mr-1"></i>Quick Add
                                </button>
                            </div>

                            @if($availabilitySlots->has($day))
                                <div class="space-y-3">
                                    @foreach($availabilitySlots[$day] as $slot)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <span class="font-medium text-gray-900">
                                                        {{ date('g:i A', strtotime($slot->start_time)) }} - {{ date('g:i A', strtotime($slot->end_time)) }}
                                                    </span>
                                                    @if(!$slot->is_active)
                                                        <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                                            Inactive
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    {{ $slot->slot_duration }} min slots • Max {{ $slot->max_bookings_per_slot }} booking(s) per slot
                                                </div>
                                                @if($slot->effective_from || $slot->effective_until)
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        @if($slot->effective_from)
                                                            From {{ $slot->effective_from->format('M j, Y') }}
                                                        @endif
                                                        @if($slot->effective_until)
                                                            Until {{ $slot->effective_until->format('M j, Y') }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <!-- Toggle Active/Inactive -->
                                                <form method="POST" action="{{ route('doctor.availability.toggle', $slot) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-{{ $slot->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $slot->is_active ? 'yellow' : 'green' }}-800"
                                                            title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="fas fa-{{ $slot->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit -->
                                                <a href="{{ route('doctor.availability.edit', $slot) }}"
                                                   class="text-blue-600 hover:text-blue-800" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <!-- Delete -->
                                                <form method="POST" action="{{ route('doctor.availability.destroy', $slot) }}"
                                                      class="inline" onsubmit="return confirm('Are you sure you want to delete this time slot?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                                    <p>No availability set for {{ $dayName }}</p>
                                    <button onclick="showBulkModal('{{ $day }}')"
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm">
                                        Add time slots
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Weekly Hours</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $availabilitySlots->flatten()->sum(function($slot) {
                                return \Carbon\Carbon::parse($slot->end_time)->diffInHours(\Carbon\Carbon::parse($slot->start_time));
                            }) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Time Slots</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $availabilitySlots->flatten()->where('is_active', true)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Days Available</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $availabilitySlots->keys()->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Add Modal -->
<div id="bulkModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Add Time Slot</h3>
        <form method="POST" action="{{ route('doctor.availability.store') }}">
            @csrf
            <input type="hidden" name="day_of_week" id="bulkDay">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                    <input type="time" name="start_time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                    <input type="time" name="end_time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slot Duration (minutes)</label>
                    <select name="slot_duration" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="15">15 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">60 minutes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Bookings per Slot</label>
                    <select name="max_bookings_per_slot" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="1" selected>1 patient</option>
                        <option value="2">2 patients</option>
                        <option value="3">3 patients</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeBulkModal()"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Add Time Slot
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showBulkModal(day) {
    document.getElementById('bulkDay').value = day;
    const modal = document.getElementById('bulkModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeBulkModal() {
    const modal = document.getElementById('bulkModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('bulkModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkModal();
    }
});
</script>
@endsection
