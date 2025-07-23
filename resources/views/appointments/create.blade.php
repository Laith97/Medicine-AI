@extends('layouts.app')

@section('title', 'Book Appointment with ' . $doctor->user->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('doctors.show', $doctor) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Doctor Profile
            </a>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center">
                <!-- Doctor Image -->
                <div class="flex-shrink-0">
                    @if($doctor->profile_image)
                        <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                             alt="{{ $doctor->user->name }}"
                             class="w-16 h-16 rounded-full object-cover">
                    @else
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user-md text-2xl text-blue-600"></i>
                        </div>
                    @endif
                </div>

                <!-- Doctor Info -->
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900">Book Appointment</h1>
                    <p class="text-lg text-gray-600">with {{ $doctor->user->name }}</p>
                    <p class="text-blue-600">{{ $doctor->specialty->name }}</p>
                </div>

                <!-- Consultation Fee -->
                <div class="ml-auto text-right">
                    <p class="text-sm text-gray-600">Consultation Fee</p>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($doctor->consultation_fee / 100, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Account Options -->
        @guest
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Booking Options</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 cursor-pointer booking-option" data-type="guest">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-user-clock text-blue-600 mr-2"></i>
                            <h3 class="font-medium text-gray-900">Book as Guest</h3>
                        </div>
                        <p class="text-sm text-gray-600">Quick booking without creating an account. You'll receive appointment details via email.</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-green-500 cursor-pointer booking-option" data-type="register">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-user-plus text-green-600 mr-2"></i>
                            <h3 class="font-medium text-gray-900">Create Account & Book</h3>
                        </div>
                        <p class="text-sm text-gray-600">Create an account to manage appointments, view history, and get personalized features.</p>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500">Already have an account?
                        <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="text-blue-600 hover:text-blue-800">Login here</a>
                    </p>
                </div>
            </div>
        @endguest

        <!-- Booking Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" action="{{ route('appointments.store') }}" id="appointmentForm">
                @csrf
                <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
                @guest
                    <input type="hidden" name="booking_type" id="bookingType" value="guest">
                @endguest

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column - Patient Info & Appointment Details -->
                    <div>
                        @guest
                            <!-- Guest Patient Information -->
                            <div id="guestInfo" class="mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-6">Your Information</h2>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="guest_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Full Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="guest_name" id="guest_name" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter your full name" value="{{ old('guest_name') }}">
                                        @error('guest_name')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="guest_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" name="guest_email" id="guest_email" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter your email" value="{{ old('guest_email') }}">
                                        @error('guest_email')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="guest_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Phone Number <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" name="guest_phone" id="guest_phone" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter your phone number" value="{{ old('guest_phone') }}">
                                        @error('guest_phone')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="guest_date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                                            Date of Birth <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="guest_date_of_birth" id="guest_date_of_birth" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               max="{{ date('Y-m-d') }}" value="{{ old('guest_date_of_birth') }}">
                                        @error('guest_date_of_birth')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label for="guest_gender" class="block text-sm font-medium text-gray-700 mb-2">
                                            Gender <span class="text-red-500">*</span>
                                        </label>
                                        <select name="guest_gender" id="guest_gender" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">Select gender</option>
                                            <option value="male" {{ old('guest_gender') == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('guest_gender') == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('guest_gender') == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('guest_gender')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="guest_address" class="block text-sm font-medium text-gray-700 mb-2">
                                            Address (Optional)
                                        </label>
                                        <input type="text" name="guest_address" id="guest_address"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter your address" value="{{ old('guest_address') }}">
                                        @error('guest_address')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Form (Hidden by default) -->
                            <div id="registrationInfo" class="mb-8" style="display: none;">
                                <h2 class="text-xl font-semibold text-gray-900 mb-6">Create Your Account</h2>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="reg_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Full Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="reg_name" id="reg_name"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter your full name">
                                    </div>

                                    <div>
                                        <label for="reg_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" name="reg_email" id="reg_email"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter your email">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label for="reg_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Password <span class="text-red-500">*</span>
                                        </label>
                                        <input type="password" name="reg_password" id="reg_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Create a password">
                                    </div>

                                    <div>
                                        <label for="reg_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm Password <span class="text-red-500">*</span>
                                        </label>
                                        <input type="password" name="reg_password_confirmation" id="reg_password_confirmation"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Confirm your password">
                                    </div>
                                </div>
                            </div>
                        @endguest

                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Appointment Details</h2>

                        <!-- Date Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
                            <div class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto border rounded-lg p-4">
                                @forelse($availableSlots as $date => $slots)
                                    <div class="border rounded-lg p-3 hover:bg-gray-50 cursor-pointer date-option"
                                         data-date="{{ $date }}">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($date)->format('l') }}
                                                </div>
                                            </div>
                                            <div class="text-sm text-blue-600">
                                                {{ $slots->count() }} slots available
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-gray-500">No available slots in the next 30 days</p>
                                        <p class="text-sm text-gray-400 mt-2">Please contact the doctor's office directly</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Time Selection -->
                        <div class="mb-6" id="timeSelection" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Time</label>
                            <div class="grid grid-cols-3 gap-2" id="timeSlots">
                                <!-- Time slots will be populated by JavaScript -->
                            </div>
                            <input type="hidden" name="appointment_date" id="selectedDateTime">
                        </div>

                        <!-- Appointment Type -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Type</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="appointment_type" value="in_person" class="mr-2" checked>
                                    <span>In-Person Visit</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="appointment_type" value="video_call" class="mr-2">
                                    <span>Video Call</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="appointment_type" value="phone_call" class="mr-2">
                                    <span>Phone Call</span>
                                </label>
                            </div>
                        </div>

                        <!-- Reason for Visit -->
                        <div class="mb-6">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                                Reason for Visit <span class="text-red-500">*</span>
                            </label>
                            <textarea name="reason" id="reason" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Please describe the reason for your visit...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Symptoms -->
                        <div class="mb-6">
                            <label for="symptoms" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Symptoms (Optional)
                            </label>
                            <textarea name="symptoms" id="symptoms" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Describe any symptoms you're experiencing...">{{ old('symptoms') }}</textarea>
                            @error('symptoms')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-6">
                            <label for="patient_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Additional Notes (Optional)
                            </label>
                            <textarea name="patient_notes" id="patient_notes" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Any additional information you'd like the doctor to know...">{{ old('patient_notes') }}</textarea>
                            @error('patient_notes')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column - Summary -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Appointment Summary</h2>

                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <!-- Doctor Info -->
                            <div class="flex items-center mb-4">
                                @if($doctor->profile_image)
                                    <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                         alt="{{ $doctor->user->name }}"
                                         class="w-12 h-12 rounded-full object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-user-md text-blue-600"></i>
                                    </div>
                                @endif
                                <div class="ml-3">
                                    <p class="font-medium text-gray-900">{{ $doctor->user->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $doctor->specialty->name }}</p>
                                </div>
                            </div>

                            <!-- Appointment Details -->
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-medium" id="summaryDate">Not selected</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Time:</span>
                                    <span class="font-medium" id="summaryTime">Not selected</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-medium">{{ $doctor->appointment_duration }} minutes</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Type:</span>
                                    <span class="font-medium" id="summaryType">In-Person Visit</span>
                                </div>
                                <hr class="my-3">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span>Consultation Fee:</span>
                                    <span class="text-green-600">${{ number_format($doctor->consultation_fee / 100, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Important Information -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h3 class="font-medium text-blue-900 mb-2">Important Information</h3>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Please arrive 15 minutes early for in-person appointments</li>
                                <li>• Bring a valid ID and insurance card</li>
                                <li>• You will receive a confirmation email with appointment details</li>
                                @if(!$doctor->auto_approve_appointments)
                                    <li>• Your appointment is subject to doctor's approval</li>
                                @endif
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium disabled:bg-gray-400 disabled:cursor-not-allowed"
                                id="submitButton" disabled>
                            <i class="fas fa-calendar-check mr-2"></i>
                            Book Appointment
                        </button>

                        <p class="text-xs text-gray-500 mt-3 text-center">
                            By booking this appointment, you agree to our terms of service and privacy policy.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableSlots = @json($availableSlots);
    const dateOptions = document.querySelectorAll('.date-option');
    const timeSelection = document.getElementById('timeSelection');
    const timeSlots = document.getElementById('timeSlots');
    const selectedDateTimeInput = document.getElementById('selectedDateTime');
    const submitButton = document.getElementById('submitButton');

    // Summary elements
    const summaryDate = document.getElementById('summaryDate');
    const summaryTime = document.getElementById('summaryTime');
    const summaryType = document.getElementById('summaryType');

    let selectedDate = null;
    let selectedTime = null;

    @guest
    // Booking option selection
    const bookingOptions = document.querySelectorAll('.booking-option');
    const guestInfo = document.getElementById('guestInfo');
    const registrationInfo = document.getElementById('registrationInfo');
    const bookingTypeInput = document.getElementById('bookingType');

    bookingOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove previous selection
            bookingOptions.forEach(opt => {
                opt.classList.remove('border-blue-500', 'bg-blue-50', 'border-green-500', 'bg-green-50');
            });

            const type = this.dataset.type;
            bookingTypeInput.value = type;

            if (type === 'guest') {
                this.classList.add('border-blue-500', 'bg-blue-50');
                guestInfo.style.display = 'block';
                registrationInfo.style.display = 'none';

                // Make guest fields required
                document.querySelectorAll('#guestInfo input[required], #guestInfo select[required]').forEach(field => {
                    field.required = true;
                });

                // Make registration fields not required
                document.querySelectorAll('#registrationInfo input').forEach(field => {
                    field.required = false;
                });
            } else {
                this.classList.add('border-green-500', 'bg-green-50');
                guestInfo.style.display = 'none';
                registrationInfo.style.display = 'block';

                // Make registration fields required
                document.querySelectorAll('#registrationInfo input').forEach(field => {
                    if (field.id !== 'reg_password_confirmation') {
                        field.required = true;
                    }
                });

                // Make guest fields not required
                document.querySelectorAll('#guestInfo input, #guestInfo select').forEach(field => {
                    field.required = false;
                });
            }
        });
    });

    // Set default selection to guest
    if (bookingOptions.length > 0) {
        bookingOptions[0].click();
    }
    @endguest

    // Date selection
    dateOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove previous selection
            dateOptions.forEach(opt => opt.classList.remove('bg-blue-50', 'border-blue-500'));

            // Add selection to current
            this.classList.add('bg-blue-50', 'border-blue-500');

            selectedDate = this.dataset.date;
            selectedTime = null;

            // Update summary
            const dateObj = new Date(selectedDate);
            summaryDate.textContent = dateObj.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            summaryTime.textContent = 'Not selected';

            // Show time selection
            showTimeSlots(selectedDate);
            updateSubmitButton();
        });
    });

    // Appointment type change
    document.querySelectorAll('input[name="appointment_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const typeMap = {
                'in_person': 'In-Person Visit',
                'video_call': 'Video Call',
                'phone_call': 'Phone Call'
            };
            summaryType.textContent = typeMap[this.value];
        });
    });

    function showTimeSlots(date) {
        const slots = availableSlots[date] || [];
        timeSlots.innerHTML = '';

        if (slots.length === 0) {
            timeSlots.innerHTML = '<p class="col-span-3 text-center text-gray-500">No available slots</p>';
            timeSelection.style.display = 'block';
            return;
        }

        slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-500 transition-colors time-slot';
            button.textContent = formatTime(slot.start_time);
            button.dataset.datetime = `${date} ${slot.start_time}`;
            button.dataset.time = slot.start_time;

            button.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.time-slot').forEach(btn => {
                    btn.classList.remove('bg-blue-500', 'text-white');
                    btn.classList.add('border-gray-300');
                });

                // Add selection to current
                this.classList.add('bg-blue-500', 'text-white');
                this.classList.remove('border-gray-300');

                selectedTime = this.dataset.time;
                selectedDateTimeInput.value = this.dataset.datetime;

                // Update summary
                summaryTime.textContent = formatTime(selectedTime);
                updateSubmitButton();
            });

            timeSlots.appendChild(button);
        });

        timeSelection.style.display = 'block';
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

    function updateSubmitButton() {
        if (selectedDate && selectedTime) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }
    }
});
</script>
@endsection
