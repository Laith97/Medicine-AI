@extends('layouts.admin')

@section('title', 'Edit User')

@push('styles')
<style>
    .admin-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .admin-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }

    .form-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .form-control:focus {
        border-color: #DE6262;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    }

    .form-check-input:checked {
        background-color: #DE6262;
        border-color: #DE6262;
    }

    .form-check-input:focus {
        border-color: #DE6262;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    }

    select.form-control {
        -webkit-appearance: menulist;
        -moz-appearance: menulist;
        appearance: menulist;
    }
</style>
@endpush

@push('scripts')
<script>
function toggleMedicalSpecialty() {
    const userType = document.getElementById('role').value;
    const specialtyField = document.getElementById('specialty-field');
    const specialtySelect = document.getElementById('specialty_select');

    if (userType === 'doctor') {
        specialtyField.style.display = 'block';
        specialtySelect.required = true;
    } else {
        specialtyField.style.display = 'none';
        specialtySelect.required = false;
        specialtySelect.value = '';
        document.getElementById('custom-specialty-field').style.display = 'none';
    }
}

function toggleCustomSpecialty() {
    const specialtySelect = document.getElementById('specialty_select');
    const customField = document.getElementById('custom-specialty-field');
    const customInput = document.getElementById('custom_specialty');
    const hiddenSpecialty = document.getElementById('specialty');

    if (specialtySelect.value === 'custom') {
        customField.style.display = 'block';
        customInput.required = true;
        customInput.value = hiddenSpecialty.value;
    } else {
        customField.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
        hiddenSpecialty.value = specialtySelect.value;
    }
}

function updateSpecialtyValue() {
    const specialtySelect = document.getElementById('specialty_select');
    const customInput = document.getElementById('custom_specialty');
    const hiddenSpecialty = document.getElementById('specialty');

    if (specialtySelect.value === 'custom') {
        hiddenSpecialty.value = customInput.value;
    } else {
        hiddenSpecialty.value = specialtySelect.value;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleMedicalSpecialty();

    // Set initial values
    const currentSpecialty = document.getElementById('specialty').value;
    const specialtySelect = document.getElementById('specialty_select');
    const customInput = document.getElementById('custom_specialty');

    // Check if current specialty exists in options
    let found = false;
    for (let option of specialtySelect.options) {
        if (option.value === currentSpecialty) {
            specialtySelect.value = currentSpecialty;
            found = true;
            break;
        }
    }

    // If not found, set to custom
    if (!found && currentSpecialty) {
        specialtySelect.value = 'custom';
        customInput.value = currentSpecialty;
        toggleCustomSpecialty();
    }

    // Add event listeners
    customInput.addEventListener('input', updateSpecialtyValue);
    specialtySelect.addEventListener('change', updateSpecialtyValue);
});
</script>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="admin-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2 text-white">Edit User</h1>
                            <p class="mb-0 opacity-75">Update user information for {{ $user->name }}</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Back to Users
                        </a>
                    </div>
                </div>

                <!-- Form -->
                <div class="form-card">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input id="password" type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror">
                            <small class="text-muted">Leave blank to keep current password</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-bold">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   class="form-control @error('password_confirmation') is-invalid @enderror">
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- User Type -->
                        <div class="mb-4">
                            <label for="role" class="form-label fw-bold">User Type</label>
                            <select id="role" name="role" required
                                    class="form-control @error('role') is-invalid @enderror"
                                    onchange="toggleMedicalSpecialty()">
                                <option value="">-- Select User Type --</option>
                                <option value="patient" {{ old('role', $user->role) == 'patient' ? 'selected' : '' }}>Patient</option>
                                <option value="doctor" {{ old('role', $user->role) == 'doctor' ? 'selected' : '' }}>Doctor</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Medical Specialty (for doctors only) -->
                        <div class="mb-4" id="specialty-field" style="display: {{ old('role', $user->role) == 'doctor' ? 'block' : 'none' }};">
                            <label for="specialty" class="form-label fw-bold">Medical Specialty</label>
                            <select class="form-control @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialty()">
                                <option value="">-- Select Your Specialty --</option>
                                <option value="custom">✏️ Custom Specialty</option>

                                <optgroup label="🧠 General & Internal Medicine">
                                    <option value="General Practitioner" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'General Practitioner' ? 'selected' : '' }}>General Practitioner (GP) / Family Medicine</option>
                                    <option value="Internal Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Internal Medicine' ? 'selected' : '' }}>Internal Medicine (Internist)</option>
                                </optgroup>

                                <optgroup label="🩺 Internal Medicine Subspecialties">
                                    <option value="Cardiology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Cardiology' ? 'selected' : '' }}>Cardiology (Heart)</option>
                                    <option value="Pulmonology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pulmonology' ? 'selected' : '' }}>Pulmonology (Lungs)</option>
                                    <option value="Gastroenterology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Gastroenterology' ? 'selected' : '' }}>Gastroenterology (Digestive system)</option>
                                    <option value="Nephrology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Nephrology' ? 'selected' : '' }}>Nephrology (Kidneys)</option>
                                    <option value="Endocrinology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Endocrinology' ? 'selected' : '' }}>Endocrinology (Hormones & glands)</option>
                                    <option value="Hematology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Hematology' ? 'selected' : '' }}>Hematology (Blood)</option>
                                    <option value="Hematology-Oncology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Hematology-Oncology' ? 'selected' : '' }}>Hematology-Oncology (Blood cancers)</option>
                                    <option value="Rheumatology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Rheumatology' ? 'selected' : '' }}>Rheumatology (Joints & autoimmune diseases)</option>
                                    <option value="Infectious Disease" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Infectious Disease' ? 'selected' : '' }}>Infectious Disease</option>
                                    <option value="Dermatology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Dermatology' ? 'selected' : '' }}>Dermatology (Skin, hair, nails)</option>
                                    <option value="Allergy & Immunology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Allergy & Immunology' ? 'selected' : '' }}>Allergy & Immunology</option>
                                    <option value="Reproductive Endocrinology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Reproductive Endocrinology' ? 'selected' : '' }}>Reproductive Endocrinology (Fertility hormones)</option>
                                </optgroup>

                                <optgroup label="🧠 Emergency & Critical Care">
                                    <option value="Emergency Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Emergency Medicine' ? 'selected' : '' }}>Emergency Medicine</option>
                                    <option value="Critical Care" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Critical Care' ? 'selected' : '' }}>Critical Care / Intensive Care Medicine</option>
                                </optgroup>

                                <optgroup label="💉 Anesthesia & Pain Management">
                                    <option value="Anesthesiology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Anesthesiology' ? 'selected' : '' }}>Anesthesiology</option>
                                    <option value="Pain Management" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pain Management' ? 'selected' : '' }}>Pain Management / Interventional Pain Medicine</option>
                                </optgroup>

                                <optgroup label="🧠 Neurology & Psychiatry">
                                    <option value="Neurology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Neurology' ? 'selected' : '' }}>Neurology (Brain & nerves)</option>
                                    <option value="Neurosurgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Neurosurgery' ? 'selected' : '' }}>Neurosurgery (Brain & spine surgery)</option>
                                    <option value="Psychiatry" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Psychiatry' ? 'selected' : '' }}>Psychiatry (Mental health)</option>
                                    <option value="Child & Adolescent Psychiatry" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Child & Adolescent Psychiatry' ? 'selected' : '' }}>Child & Adolescent Psychiatry</option>
                                    <option value="Behavioral & Developmental Pediatrics" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Behavioral & Developmental Pediatrics' ? 'selected' : '' }}>Behavioral & Developmental Pediatrics</option>
                                </optgroup>

                                <optgroup label="🦴 Surgical Specialties">
                                    <option value="General Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'General Surgery' ? 'selected' : '' }}>General Surgery</option>
                                    <option value="Orthopedic Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Orthopedic Surgery' ? 'selected' : '' }}>Orthopedic Surgery (Bones & joints)</option>
                                    <option value="Cardiothoracic Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Cardiothoracic Surgery' ? 'selected' : '' }}>Cardiothoracic Surgery (Heart & lungs)</option>
                                    <option value="Vascular Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Vascular Surgery' ? 'selected' : '' }}>Vascular Surgery (Blood vessels)</option>
                                    <option value="Pediatric Vascular Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pediatric Vascular Surgery' ? 'selected' : '' }}>Pediatric Vascular Surgery</option>
                                    <option value="Plastic & Reconstructive Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Plastic & Reconstructive Surgery' ? 'selected' : '' }}>Plastic & Reconstructive Surgery</option>
                                    <option value="Oral & Maxillofacial Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Oral & Maxillofacial Surgery' ? 'selected' : '' }}>Oral & Maxillofacial Surgery</option>
                                    <option value="Surgical Oncology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Surgical Oncology' ? 'selected' : '' }}>Surgical Oncology (Cancer surgery)</option>
                                    <option value="Colorectal Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Colorectal Surgery' ? 'selected' : '' }}>Colorectal Surgery</option>
                                    <option value="Urology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Urology' ? 'selected' : '' }}>Urology (Urinary & male reproductive system)</option>
                                    <option value="ENT" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'ENT' ? 'selected' : '' }}>ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                    <option value="Ophthalmic Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Ophthalmic Surgery' ? 'selected' : '' }}>Ophthalmic Surgery (Eye surgery)</option>
                                    <option value="Pediatric Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pediatric Surgery' ? 'selected' : '' }}>Pediatric Surgery</option>
                                    <option value="Hand Surgery" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Hand Surgery' ? 'selected' : '' }}>Hand Surgery</option>
                                </optgroup>

                                <optgroup label="👶 Pediatrics & Women's Health">
                                    <option value="Pediatrics" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                    <option value="Neonatology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Neonatology' ? 'selected' : '' }}>Neonatology (Newborn care)</option>
                                    <option value="Pediatric Behavioral Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pediatric Behavioral Medicine' ? 'selected' : '' }}>Pediatric Behavioral Medicine</option>
                                    <option value="Obstetrics & Gynecology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Obstetrics & Gynecology' ? 'selected' : '' }}>Obstetrics & Gynecology (OB/GYN)</option>
                                    <option value="Gynecologic Oncology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Gynecologic Oncology' ? 'selected' : '' }}>Gynecologic Oncology</option>
                                    <option value="Reproductive Endocrinology & Infertility" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Reproductive Endocrinology & Infertility' ? 'selected' : '' }}>Reproductive Endocrinology & Infertility</option>
                                    <option value="Maternal–Fetal Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Maternal–Fetal Medicine' ? 'selected' : '' }}>Maternal–Fetal Medicine</option>
                                </optgroup>

                                <optgroup label="🧬 Diagnostic & Support Specialties">
                                    <option value="Pathology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Pathology' ? 'selected' : '' }}>Pathology (Laboratory medicine)</option>
                                    <option value="Radiology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Radiology' ? 'selected' : '' }}>Radiology (Medical imaging)</option>
                                    <option value="Interventional Radiology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Interventional Radiology' ? 'selected' : '' }}>Interventional Radiology</option>
                                    <option value="Nuclear Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Nuclear Medicine' ? 'selected' : '' }}>Nuclear Medicine</option>
                                    <option value="Endoscopy" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Endoscopy' ? 'selected' : '' }}>Endoscopy / GI Endoscopy</option>
                                    <option value="Electrodiagnostic Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Electrodiagnostic Medicine' ? 'selected' : '' }}>Electrodiagnostic Medicine (EMG, EEG)</option>
                                </optgroup>

                                <optgroup label="🏥 Other Medical Specialties">
                                    <option value="Oncology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Oncology' ? 'selected' : '' }}>Oncology (Medical cancer care)</option>
                                    <option value="Hepatology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Hepatology' ? 'selected' : '' }}>Hepatology (Liver diseases)</option>
                                    <option value="Genetic Hematology" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Genetic Hematology' ? 'selected' : '' }}>Genetic Hematology</option>
                                    <option value="Geriatrics" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Geriatrics' ? 'selected' : '' }}>Geriatrics (Elderly care)</option>
                                    <option value="Physical Medicine & Rehabilitation" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Physical Medicine & Rehabilitation' ? 'selected' : '' }}>Physical Medicine & Rehabilitation</option>
                                    <option value="Occupational & Environmental Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Occupational & Environmental Medicine' ? 'selected' : '' }}>Occupational & Environmental Medicine</option>
                                    <option value="Sports Medicine" {{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') == 'Sports Medicine' ? 'selected' : '' }}>Sports Medicine</option>
                                </optgroup>
                            </select>

                            <!-- Custom Specialty Input -->
                            <div class="mt-3" id="custom-specialty-field" style="display: none;">
                                <input type="text" name="custom_specialty" id="custom_specialty"
                                       class="form-control @error('specialty') is-invalid @enderror"
                                       placeholder="Enter custom specialty">
                                @error('specialty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Hidden field for actual specialty value -->
                            <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty', $user->specialty ?? $user->setting->specialty ?? '') }}">

                            @error('specialty')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Verification Status -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('is_verified') is-invalid @enderror"
                                       type="checkbox" name="is_verified" value="1" id="is_verified"
                                       {{ old('is_verified', $user->email_verified_at ? true : false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_verified">
                                    Mark user as verified
                                </label>
                            </div>
                            <small class="text-muted">Verified users have confirmed their identity and credentials.</small>
                            @error('is_verified')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>



                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
