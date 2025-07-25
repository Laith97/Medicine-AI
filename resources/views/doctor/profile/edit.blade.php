@extends('master')

@section('title', 'Edit Doctor Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
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
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel"
                               name="phone"
                               id="phone"
                               value="{{ old('phone', $doctor->phone) }}"
                               class="form-control">
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
