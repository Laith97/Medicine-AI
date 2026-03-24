@extends('master')

@section('title', 'Edit Doctor Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<style>
.appointment-type-preference-card {
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
}

.appointment-type-preference-card.enabled {
    border-color: #28a745;
    background-color: #f8fff9;
}

.appointment-type-preference-card.disabled {
    border-color: #e9ecef;
    background-color: #f8f9fa;
}

.appointment-type-preference-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.appointment-type-toggle:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.appointment-type-toggle:focus {
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h2>Doctor Profile</h2>
            <p>Manage your professional profile and settings</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <!-- Error Messages -->
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('doctor.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <!-- Basic Information -->
            <div class="table-card">
                <h6 class="mb-4"><i class="fas fa-user me-2"></i>Basic Information</h6>

                <div class="row g-4">
                    <!-- Profile Image -->
                    <div class="col-12">
                        <label class="form-label">Profile Image</label>
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                @if($doctor->profile_image)
                                    <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                         alt="Current profile image"
                                         class="rounded-circle border"
                                         style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center"
                                         style="width: 80px; height: 80px;">
                                        <i class="fas fa-user-md fs-2 text-muted"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <input type="file"
                                       name="profile_image"
                                       id="profile_image"
                                       accept="image/*"
                                       class="form-control">
                                <small class="form-text text-muted">JPG, PNG, GIF up to 2MB</small>
                            </div>
                        </div>
                    </div>

                    <!-- Specialty -->
                    <div class="col-md-6">
                        <label for="specialty_id" class="form-label">Specialty *</label>
                        <select name="specialty_id" id="specialty_id" required class="form-select">
                            <option value="">Select Specialty</option>
                            @foreach($specialties as $specialty)
                                <option value="{{ $specialty->id }}"
                                        {{ old('specialty_id', $doctor->specialty_id) == $specialty->id ? 'selected' : '' }}>
                                    {{ $specialty->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel"
                               name="phone"
                               id="phone"
                               value="{{ old('phone', $doctor->phone) }}"
                               class="form-control"
                               required
                               placeholder="+1234567890">
                        <small class="form-text text-muted">Required for phone call appointments</small>
                    </div>

                    <!-- Consultation Fee -->
                    <div class="col-md-6">
                        <label for="consultation_fee" class="form-label">Consultation Fee ($) *</label>
                        <input type="number"
                               name="consultation_fee"
                               id="consultation_fee"
                               step="0.01"
                               min="0"
                               value="{{ old('consultation_fee', $doctor->consultation_fee / 100) }}"
                               required
                               class="form-control">
                    </div>

                    <!-- Appointment Duration -->
                    <div class="col-md-6">
                        <label for="appointment_duration" class="form-label">Appointment Duration (minutes) *</label>
                        <select name="appointment_duration" id="appointment_duration" required class="form-select">
                            @foreach([15, 30, 45, 60, 90, 120] as $duration)
                                <option value="{{ $duration }}"
                                        {{ old('appointment_duration', $doctor->appointment_duration) == $duration ? 'selected' : '' }}>
                                    {{ $duration }} minutes
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bio -->
                    <div class="col-12">
                        <label for="bio" class="form-label">Professional Bio</label>
                        <textarea name="bio"
                                  id="bio"
                                  rows="4"
                                  placeholder="Tell patients about your experience, qualifications, and approach to healthcare..."
                                  class="form-control">{{ old('bio', $doctor->bio) }}</textarea>
                        <small class="form-text text-muted">This will be displayed on your public profile</small>
                    </div>

                    <!-- Languages -->
                    <div class="col-12">
                        <label class="form-label">Languages Spoken</label>
                        <div class="row g-2">
                            @php
                                $commonLanguages = ['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Chinese', 'Japanese', 'Korean', 'Arabic', 'Hindi', 'Russian'];
                                $doctorLanguages = old('languages', $doctor->languages ?? []);
                            @endphp
                            @foreach($commonLanguages as $language)
                                <div class="col-md-3 col-6">
                                    <div class="form-check">
                                        <input type="checkbox"
                                               name="languages[]"
                                               value="{{ $language }}"
                                               {{ in_array($language, $doctorLanguages) ? 'checked' : '' }}
                                               class="form-check-input"
                                               id="lang_{{ $loop->index }}">
                                        <label class="form-check-label" for="lang_{{ $loop->index }}">
                                            {{ $language }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="table-card">
                <h6 class="mb-4"><i class="fas fa-map-marker-alt me-2"></i>Practice Address</h6>

                <div class="row g-4">
                    <!-- Address -->
                    <div class="col-12">
                        <label for="address" class="form-label">Street Address</label>
                        <input type="text"
                               name="address"
                               id="address"
                               value="{{ old('address', $doctor->address) }}"
                               class="form-control">
                    </div>

                    <!-- City -->
                    <div class="col-md-6">
                        <label for="city" class="form-label">City</label>
                        <input type="text"
                               name="city"
                               id="city"
                               value="{{ old('city', $doctor->city) }}"
                               class="form-control">
            </div>

            <!-- Google Integration -->
            <div class="table-card">
                <h6 class="mb-4"><i class="fab fa-google me-2"></i>Google Integration</h6>

                       @if($doctor->googleAccount)
                           <div class="alert alert-success mb-4">
                               <div class="d-flex justify-content-between align-items-center">
                                   <div>
                                       <i class="fas fa-check-circle me-2"></i>
                                       <strong>Google Account Connected</strong>
                                       <p class="mb-0 mt-1">Your Google account is connected and reviews can be posted to Google.</p>
                                   </div>
                                   <form method="POST" action="{{ route('doctor.google.disconnect') }}">
                                       @csrf
                                       <button type="submit" class="btn btn-outline-danger btn-sm">
                                           <i class="fas fa-unlink me-1"></i>Disconnect
                                       </button>
                                   </form>
                               </div>
                           </div>

                           @if($doctor->googleAccount->business_account_id && $doctor->googleAccount->location_id)
                               <div class="alert alert-info">
                                   <i class="fas fa-info-circle me-2"></i>
                                   <strong>Google My Business Configured</strong>
                                   <p class="mb-0">Reviews will be posted to your Google My Business location.</p>
                               </div>
                           @else
                               <div class="alert alert-warning">
                                   <i class="fas fa-exclamation-triangle me-2"></i>
                                   <strong>Google My Business Not Configured</strong>
                                   <p class="mb-0">Please select your Google My Business account and location below.</p>
                               </div>

                               <div class="row g-4">
                                   <div class="col-12">
                                       <label class="form-label">Google My Business Accounts</label>
                                       <div id="google-accounts-container">
                                           <button type="button" class="btn btn-outline-primary" id="fetch-accounts-btn">
                                               <i class="fas fa-sync me-2"></i>Fetch Google Accounts
                                           </button>
                                       </div>
                                   </div>

                                   <div class="col-12">
                                       <label for="google_account_id" class="form-label">Select Account</label>
                                       <select name="google_account_id" id="google_account_id" class="form-select" disabled>
                                           <option value="">Select an account first</option>
                                       </select>
                                   </div>

                                   <div class="col-12">
                                       <label for="google_location_id" class="form-label">Select Location</label>
                                       <select name="google_location_id" id="google_location_id" class="form-select" disabled>
                                           <option value="">Select an account first</option>
                                       </select>
                                   </div>

                                   <div class="col-12">
                                       <button type="button" class="btn btn-primary" id="save-google-config-btn" disabled>
                                           <i class="fas fa-save me-2"></i>Save Configuration
                                       </button>
                                   </div>
                               </div>
                           @endif
                       @else
                           <div class="alert alert-info">
                               <div class="d-flex justify-content-between align-items-center">
                                   <div>
                                       <i class="fab fa-google me-2"></i>
                                       <strong>Connect Google Account</strong>
                                       <p class="mb-0">Connect your Google account to enable posting reviews to Google.</p>
                                   </div>
                                   <a href="{{ route('doctor.google.redirect') }}" class="btn btn-outline-primary">
                                       <i class="fab fa-google me-1"></i>Connect Google
                                   </a>
                               </div>
                           </div>
                       @endif
                   </div>

                    <!-- State -->
                    <div class="col-md-6">
                        <label for="state" class="form-label">State/Province</label>
                        <input type="text"
                               name="state"
                               id="state"
                               value="{{ old('state', $doctor->state) }}"
                               class="form-control">
                    </div>

                    <!-- ZIP Code -->
                    <div class="col-md-6">
                        <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                        <input type="text"
                               name="zip_code"
                               id="zip_code"
                               value="{{ old('zip_code', $doctor->zip_code) }}"
                               class="form-control">
                    </div>

                    <!-- Country -->
                    <div class="col-md-6">
                        <label for="country" class="form-label">Country</label>
                        <input type="text"
                               name="country"
                               id="country"
                               value="{{ old('country', $doctor->country) }}"
                               class="form-control">
                    </div>
                </div>
            </div>

            <!-- Appointment Settings -->
            <div class="table-card">
                <h6 class="mb-4"><i class="fas fa-cog me-2"></i>Appointment Settings</h6>

                <div class="row g-4">
                    <!-- Auto Approve -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label for="auto_approve_appointments" class="form-label mb-0">Auto-approve appointments</label>
                                <small class="form-text text-muted d-block">Automatically confirm new appointment requests</small>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       name="auto_approve_appointments"
                                       id="auto_approve_appointments"
                                       value="1"
                                       {{ old('auto_approve_appointments', $doctor->auto_approve_appointments) ? 'checked' : '' }}
                                       class="form-check-input">
                            </div>
                        </div>
                    </div>

                    <!-- Allow Cancellation -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label for="allow_cancellation" class="form-label mb-0">Allow patient cancellations</label>
                                <small class="form-text text-muted d-block">Let patients cancel their own appointments</small>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       name="allow_cancellation"
                                       id="allow_cancellation"
                                       value="1"
                                       {{ old('allow_cancellation', $doctor->allow_cancellation) ? 'checked' : '' }}
                                       class="form-check-input">
                            </div>
                        </div>
                    </div>

                    <!-- Allow Rescheduling -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label for="allow_rescheduling" class="form-label mb-0">Allow patient rescheduling</label>
                                <small class="form-text text-muted d-block">Let patients reschedule their own appointments</small>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox"
                                       name="allow_rescheduling"
                                       id="allow_rescheduling"
                                       value="1"
                                       {{ old('allow_rescheduling', $doctor->allow_rescheduling) ? 'checked' : '' }}
                                       class="form-check-input">
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Hours -->
                    <div class="col-md-6">
                        <label for="cancellation_hours" class="form-label">Minimum cancellation notice (hours) *</label>
                        <select name="cancellation_hours" id="cancellation_hours" required class="form-select">
                            @foreach([1, 2, 4, 6, 12, 24, 48, 72] as $hours)
                                <option value="{{ $hours }}"
                                        {{ old('cancellation_hours', $doctor->cancellation_hours) == $hours ? 'selected' : '' }}>
                                    {{ $hours }} {{ $hours == 1 ? 'hour' : 'hours' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Patients must cancel at least this many hours before their appointment</small>
                    </div>
                    <!-- Appointment Settings -->

                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label">Available Appointment Types</label>
                        <p class="text-muted small mb-3">Choose which appointment types you want to offer to your patients. Only enabled types will appear as options when patients book appointments.</p>

                        @php
                            $appointmentTypes = [
                                'in_person' => [
                                    'label' => 'In-Person Consultation',
                                    'description' => 'Face-to-face consultations at your clinic',
                                    'icon' => 'fas fa-hospital',
                                    'color' => 'text-primary'
                                ],
                                'video_call' => [
                                    'label' => 'Video Call',
                                    'description' => 'Online video consultations',
                                    'icon' => 'fas fa-video',
                                    'color' => 'text-success'
                                ],
                                'phone_call' => [
                                    'label' => 'Phone Call',
                                    'description' => 'Phone call consultations',
                                    'icon' => 'fas fa-phone',
                                    'color' => 'text-info'
                                ]
                            ];
                        @endphp

                        <div class="row g-3">
                            @foreach($appointmentTypes as $type => $config)
                                <div class="col-md-4">
                                    <div class="card h-100 appointment-type-preference-card {{ $doctor->isAppointmentTypeEnabled($type) ? 'enabled' : 'disabled' }}">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="{{ $config['icon'] }} fs-2 {{ $config['color'] }}"></i>
                                            </div>
                                            <h6 class="card-title mb-2">{{ $config['label'] }}</h6>
                                            <p class="card-text small text-muted mb-3">{{ $config['description'] }}</p>

                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input appointment-type-toggle"
                                                    type="checkbox"
                                                    name="appointment_types[]"
                                                    value="{{ $type }}"
                                                    id="appointment_type_{{ $type }}"
                                                    {{ $doctor->isAppointmentTypeEnabled($type) ? 'checked' : '' }}>
                                                <label class="form-check-label ms-2" for="appointment_type_{{ $type }}">
                                                    <span class="status-text">{{ $doctor->isAppointmentTypeEnabled($type) ? 'Enabled' : 'Disabled' }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                At least one appointment type must be enabled. Changes are saved automatically.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end gap-3">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary-custom">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary-custom">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </form>

        <!-- Landing Page Section -->
        <div class="table-card">
            <h6 class="mb-4"><i class="fas fa-globe me-2"></i>Your Landing Page</h6>
            <p class="text-muted mb-3">Create a personalized landing page to showcase your practice and attract patients.</p>
            <div class="d-flex gap-3">
                <a href="{{ route('doctor.landing-page.index') }}" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Manage Landing Page
                </a>
                @if($doctor->landingPage && $doctor->landingPage->is_published)
                    <a href="{{ $doctor->landingPage->url }}" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-2"></i>View Public Page
                    </a>
                @endif
            </div>
        </div>

        <!-- Preview Section -->
        <div class="table-card">
            <h6 class="mb-4"><i class="fas fa-eye me-2"></i>Profile Preview</h6>
            <p class="text-muted mb-4">This is how your profile will appear to patients:</p>

            <div class="border rounded p-4 bg-light">
                <div class="d-flex align-items-center mb-4">
                    @if($doctor->profile_image)
                        <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                             alt="Doctor profile"
                             class="rounded-circle me-4"
                             style="width: 64px; height: 64px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-4"
                             style="width: 64px; height: 64px;">
                            <i class="fas fa-user-md fs-4 text-white"></i>
                        </div>
                    @endif
                    <div>
                        <h5 class="mb-1">Dr. {{ $doctor->user->name }}</h5>
                        <p class="text-muted mb-1">{{ $doctor->specialty->name ?? 'Specialty not set' }}</p>
                        <div class="d-flex align-items-center">
                            <div class="text-warning me-2">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($doctor->average_rating))
                                        <i class="fas fa-star small"></i>
                                    @elseif($i - 0.5 <= $doctor->average_rating)
                                        <i class="fas fa-star-half-alt small"></i>
                                    @else
                                        <i class="far fa-star small"></i>
                                    @endif
                                @endfor
                            </div>
                            <small class="text-muted">
                                {{ number_format($doctor->average_rating, 1) }} ({{ $doctor->total_reviews }} reviews)
                            </small>
                        </div>
                    </div>
                </div>

                @if($doctor->bio)
                    <p class="text-dark mb-3">{{ $doctor->bio }}</p>
                @endif

                <div class="row g-3 small">
                    <div class="col-md-6">
                        <strong>Consultation Fee:</strong>
                        ${{ number_format($doctor->consultation_fee / 100, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Duration:</strong>
                        {{ $doctor->appointment_duration }} minutes
                    </div>
                    @if($doctor->languages)
                        <div class="col-12">
                            <strong>Languages:</strong>
                            {{ implode(', ', $doctor->languages) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
   const fetchAccountsBtn = document.getElementById('fetch-accounts-btn');
   const accountSelect = document.getElementById('google_account_id');
   const locationSelect = document.getElementById('google_location_id');
   const saveConfigBtn = document.getElementById('save-google-config-btn');

   if (fetchAccountsBtn) {
       fetchAccountsBtn.addEventListener('click', function() {
           fetch('{{ route('doctor.google.accounts') }}')
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       accountSelect.innerHTML = '<option value="">Select an account</option>';
                       data.accounts.forEach(account => {
                           const option = document.createElement('option');
                           option.value = account.name;
                           option.textContent = account.accountName;
                           accountSelect.appendChild(option);
                       });
                       accountSelect.disabled = false;
                   } else {
                       alert('Error fetching accounts: ' + data.error);
                   }
               })
               .catch(error => {
                   console.error('Error:', error);
                   alert('Error fetching accounts');
               });
       });
   }

   if (accountSelect) {
       accountSelect.addEventListener('change', function() {
           const accountId = this.value;
           if (accountId) {
               fetch('{{ route('doctor.google.locations') }}?account_id=' + encodeURIComponent(accountId))
                   .then(response => response.json())
                   .then(data => {
                       if (data.success) {
                           locationSelect.innerHTML = '<option value="">Select a location</option>';
                           data.locations.forEach(location => {
                               const option = document.createElement('option');
                               option.value = location.name;
                               option.textContent = location.locationName;
                               locationSelect.appendChild(option);
                           });
                           locationSelect.disabled = false;
                       } else {
                           alert('Error fetching locations: ' + data.error);
                       }
                   })
                   .catch(error => {
                       console.error('Error:', error);
                       alert('Error fetching locations');
                   });
           } else {
               locationSelect.innerHTML = '<option value="">Select an account first</option>';
               locationSelect.disabled = true;
           }
       });
   }

   if (locationSelect) {
       locationSelect.addEventListener('change', function() {
           saveConfigBtn.disabled = !this.value;
       });
   }

   if (saveConfigBtn) {
       saveConfigBtn.addEventListener('click', function() {
           const accountId = accountSelect.value;
           const locationId = locationSelect.value;

           if (!accountId || !locationId) {
               alert('Please select both account and location');
               return;
           }

           fetch('{{ route('doctor.google.account-location') }}', {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
               },
               body: JSON.stringify({
                   account_id: accountId,
                   location_id: locationId
               })
           })
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   alert(data.message);
                   location.reload();
               } else {
                   alert('Error saving configuration: ' + data.error);
               }
           })
           .catch(error => {
               console.error('Error:', error);
               alert('Error saving configuration');
           });
       });
   }
});

// Appointment Type Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const appointmentTypeToggles = document.querySelectorAll('.appointment-type-toggle');

    appointmentTypeToggles.forEach(function(toggle) {
        const card = toggle.closest('.appointment-type-preference-card');
        const statusText = toggle.parentElement.querySelector('.status-text');

        // Handle toggle changes
        toggle.addEventListener('change', function() {
            const isChecked = this.checked;
            const appointmentType = this.value;

            // Update card appearance
            updateCardAppearance(card, statusText, isChecked);

            // Check if at least one type is enabled
            const enabledToggles = document.querySelectorAll('.appointment-type-toggle:checked');
            if (enabledToggles.length === 0) {
                // Prevent disabling all types
                this.checked = true;
                updateCardAppearance(card, statusText, true);

                // Show warning
                showNotification('At least one appointment type must be enabled.', 'warning');
                return;
            }

            // Save changes via AJAX
            saveAppointmentTypePreferences();
        });
    });

    function updateCardAppearance(card, statusText, isEnabled) {
        if (isEnabled) {
            card.classList.remove('disabled');
            card.classList.add('enabled');
            statusText.textContent = 'Enabled';
        } else {
            card.classList.remove('enabled');
            card.classList.add('disabled');
            statusText.textContent = 'Disabled';
        }
    }

    function saveAppointmentTypePreferences() {
        const enabledTypes = [];
        document.querySelectorAll('.appointment-type-toggle:checked').forEach(function(toggle) {
            enabledTypes.push(toggle.value);
        });

        // Show loading state
        const loadingToast = showNotification('Saving appointment preferences...', 'info', false);

        // Make AJAX request
        fetch('{{ route("doctor.settings.appointments.update") }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                appointment_types: enabledTypes
            })
        })
        .then(response => response.json())
        .then(data => {
            // Hide loading toast
            if (loadingToast) loadingToast.remove();

            if (data.success || data.message) {
                showNotification(data.message || 'Appointment preferences updated successfully!', 'success');
            } else {
                throw new Error(data.error || 'Failed to update preferences');
            }
        })
        .catch(error => {
            // Hide loading toast
            if (loadingToast) loadingToast.remove();

            console.error('Error:', error);
            showNotification('Failed to update appointment preferences. Please try again.', 'error');
        });
    }

    function showNotification(message, type = 'info', autoHide = true) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.appointment-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} appointment-notification`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;

        const icon = type === 'success' ? 'check-circle' :
                    type === 'warning' ? 'exclamation-triangle' :
                    type === 'error' ? 'exclamation-circle' : 'info-circle';

        notification.innerHTML = `
            <i class="fas fa-${icon} me-2"></i>${message}
            <button type="button" class="btn-close" aria-label="Close"></button>
        `;

        // Add close functionality
        notification.querySelector('.btn-close').addEventListener('click', function() {
            notification.remove();
        });

        // Add to page
        document.body.appendChild(notification);

        // Auto-hide after 3 seconds (except for loading notifications)
        if (autoHide) {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        return notification;
    }
});
</script>
@endpush
