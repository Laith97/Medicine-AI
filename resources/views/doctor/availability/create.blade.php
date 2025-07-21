@extends('layouts.app')

@section('title', 'Add Availability Slot')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('doctor.availability.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Availability
            </a>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Add Availability Slot</h1>
            <p class="text-gray-600 mt-2">Create a new time slot for patient appointments</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" action="{{ route('doctor.availability.store') }}">
                @csrf

                <!-- Day of Week -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Day of Week <span class="text-red-500">*</span>
                    </label>
                    <select name="day_of_week" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select a day</option>
                        @foreach($daysOfWeek as $day => $dayName)
                            <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>
                                {{ $dayName }}
                            </option>
                        @endforeach
                    </select>
                    @error('day_of_week')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('start_time')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('end_time')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Slot Configuration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Slot Duration (minutes) <span class="text-red-500">*</span>
                        </label>
                        <select name="slot_duration" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="15" {{ old('slot_duration') == '15' ? 'selected' : '' }}>15 minutes</option>
                            <option value="30" {{ old('slot_duration', '30') == '30' ? 'selected' : '' }}>30 minutes</option>
                            <option value="45" {{ old('slot_duration') == '45' ? 'selected' : '' }}>45 minutes</option>
                            <option value="60" {{ old('slot_duration') == '60' ? 'selected' : '' }}>60 minutes</option>
                            <option value="90" {{ old('slot_duration') == '90' ? 'selected' : '' }}>90 minutes</option>
                            <option value="120" {{ old('slot_duration') == '120' ? 'selected' : '' }}>120 minutes</option>
                        </select>
                        @error('slot_duration')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Max Bookings per Slot <span class="text-red-500">*</span>
                        </label>
                        <select name="max_bookings_per_slot" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('max_bookings_per_slot', '1') == $i ? 'selected' : '' }}>
                                    {{ $i }} {{ $i == 1 ? 'patient' : 'patients' }}
                                </option>
                            @endfor
                        </select>
                        @error('max_bookings_per_slot')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Effective Dates (Optional) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Effective From (optional)
                        </label>
                        <input type="date" name="effective_from" value="{{ old('effective_from') }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to start immediately</p>
                        @error('effective_from')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Effective Until (optional)
                        </label>
                        <input type="date" name="effective_until" value="{{ old('effective_until') }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave blank for no end date</p>
                        @error('effective_until')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Preview -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="font-medium text-blue-900 mb-2">Preview</h3>
                    <div id="preview" class="text-sm text-blue-800">
                        Select day and time to see preview
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('doctor.availability.index') }}"
                       class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Create Availability Slot
                    </button>
                </div>
            </form>
        </div>

        <!-- Bulk Create Option -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Setup</h2>
            <p class="text-gray-600 mb-4">Want to set the same hours for multiple days?</p>

            <form method="POST" action="{{ route('doctor.availability.bulk') }}">
                @csrf

                <!-- Multiple Days Selection -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Days</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($daysOfWeek as $day => $dayName)
                            <label class="flex items-center">
                                <input type="checkbox" name="days[]" value="{{ $day }}" class="mr-2">
                                <span class="text-sm">{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                        <input type="time" name="start_time" value="09:00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                        <input type="time" name="end_time" value="17:00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                        <select name="slot_duration"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="30">30 min</option>
                            <option value="60">60 min</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Bookings</label>
                        <select name="max_bookings_per_slot"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="1">1 patient</option>
                            <option value="2">2 patients</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit"
                            class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Create Multiple Slots
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daySelect = document.querySelector('select[name="day_of_week"]');
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    const duration = document.querySelector('select[name="slot_duration"]');
    const maxBookings = document.querySelector('select[name="max_bookings_per_slot"]');
    const preview = document.getElementById('preview');

    function updatePreview() {
        const day = daySelect.value;
        const start = startTime.value;
        const end = endTime.value;
        const dur = duration.value;
        const max = maxBookings.value;

        if (day && start && end && dur && max) {
            const dayName = daySelect.options[daySelect.selectedIndex].text;
            const startFormatted = formatTime(start);
            const endFormatted = formatTime(end);

            // Calculate number of slots
            const startMinutes = timeToMinutes(start);
            const endMinutes = timeToMinutes(end);
            const totalMinutes = endMinutes - startMinutes;
            const slots = Math.floor(totalMinutes / parseInt(dur));

            preview.innerHTML = `
                <strong>${dayName}</strong><br>
                ${startFormatted} - ${endFormatted}<br>
                ${slots} slots of ${dur} minutes each<br>
                Up to ${max} patient(s) per slot
            `;
        } else {
            preview.innerHTML = 'Select day and time to see preview';
        }
    }

    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const date = new Date();
        date.setHours(parseInt(hours), parseInt(minutes));
        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function timeToMinutes(time) {
        const [hours, minutes] = time.split(':');
        return parseInt(hours) * 60 + parseInt(minutes);
    }

    // Add event listeners
    [daySelect, startTime, endTime, duration, maxBookings].forEach(element => {
        element.addEventListener('change', updatePreview);
    });

    // Initial preview update
    updatePreview();
});
</script>
@endsection
