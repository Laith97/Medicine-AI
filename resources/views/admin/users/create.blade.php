@extends('master')

@section('title', 'Create New User')

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

    // Add event listeners
    const customInput = document.getElementById('custom_specialty');
    const specialtySelect = document.getElementById('specialty_select');

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
                            <h1 class="h2 mb-2 text-white">Create New User</h1>
                            <p class="mb-0 opacity-75">Add a new user to the system</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Back to Users
                        </a>
                    </div>
                </div>

                <!-- Form -->
                <div class="form-card">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input id="password" type="password" name="password" required
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-bold">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required
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
                                <option value="patient" {{ old('role') == 'patient' ? 'selected' : '' }}>Patient</option>
                                <option value="doctor" {{ old('role') == 'doctor' ? 'selected' : '' }}>Doctor</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Medical Specialty (for doctors only) -->
                        <div class="mb-4" id="specialty-field" style="display: none;">
                            <label for="specialty" class="form-label fw-bold">Medical Specialty</label>
                            <select class="form-control @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialty()">
                                <option value="">-- Select Your Specialty --</option>
                                <option value="custom">✏️ Custom Specialty</option>

                                <optgroup label="🧠 General & Internal Medicine">
                                    <option value="General Practitioner">General Practitioner (GP) / Family Medicine</option>
                                    <option value="Internal Medicine">Internal Medicine (Internist)</option>
                                </optgroup>

                                <optgroup label="🩺 Internal Medicine Subspecialties">
                                    <option value="Cardiology">Cardiology (Heart)</option>
                                    <option value="Pulmonology">Pulmonology (Lungs)</option>
                                    <option value="Gastroenterology">Gastroenterology (Digestive system)</option>
                                    <option value="Nephrology">Nephrology (Kidneys)</option>
                                    <option value="Endocrinology">Endocrinology (Hormones & glands)</option>
                                    <option value="Hematology">Hematology (Blood)</option>
                                    <option value="Hematology-Oncology">Hematology-Oncology (Blood cancers)</option>
                                    <option value="Rheumatology">Rheumatology (Joints & autoimmune diseases)</option>
                                    <option value="Infectious Disease">Infectious Disease</option>
                                    <option value="Dermatology">Dermatology (Skin, hair, nails)</option>
                                    <option value="Allergy & Immunology">Allergy & Immunology</option>
                                    <option value="Reproductive Endocrinology">Reproductive Endocrinology (Fertility hormones)</option>
                                </optgroup>

                                <optgroup label="🧠 Emergency & Critical Care">
                                    <option value="Emergency Medicine">Emergency Medicine</option>
                                    <option value="Critical Care">Critical Care / Intensive Care Medicine</option>
                                </optgroup>

                                <optgroup label="💉 Anesthesia & Pain Management">
                                    <option value="Anesthesiology">Anesthesiology</option>
                                    <option value="Pain Management">Pain Management / Interventional Pain Medicine</option>
                                </optgroup>

                                <optgroup label="🧠 Neurology & Psychiatry">
                                    <option value="Neurology">Neurology (Brain & nerves)</option>
                                    <option value="Neurosurgery">Neurosurgery (Brain & spine surgery)</option>
                                    <option value="Psychiatry">Psychiatry (Mental health)</option>
                                    <option value="Child & Adolescent Psychiatry">Child & Adolescent Psychiatry</option>
                                    <option value="Behavioral & Developmental Pediatrics">Behavioral & Developmental Pediatrics</option>
                                </optgroup>

                                <optgroup label="🦴 Surgical Specialties">
                                    <option value="General Surgery">General Surgery</option>
                                    <option value="Orthopedic Surgery">Orthopedic Surgery (Bones & joints)</option>
                                    <option value="Cardiothoracic Surgery">Cardiothoracic Surgery (Heart & lungs)</option>
                                    <option value="Vascular Surgery">Vascular Surgery (Blood vessels)</option>
                                    <option value="Pediatric Vascular Surgery">Pediatric Vascular Surgery</option>
                                    <option value="Plastic & Reconstructive Surgery">Plastic & Reconstructive Surgery</option>
                                    <option value="Oral & Maxillofacial Surgery">Oral & Maxillofacial Surgery</option>
                                    <option value="Surgical Oncology">Surgical Oncology (Cancer surgery)</option>
                                    <option value="Colorectal Surgery">Colorectal Surgery</option>
                                    <option value="Urology">Urology (Urinary & male reproductive system)</option>
                                    <option value="ENT">ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                    <option value="Ophthalmic Surgery">Ophthalmic Surgery (Eye surgery)</option>
                                    <option value="Pediatric Surgery">Pediatric Surgery</option>
                                    <option value="Hand Surgery">Hand Surgery</option>
                                </optgroup>

                                <optgroup label="👶 Pediatrics & Women's Health">
                                    <option value="Pediatrics">Pediatrics</option>
                                    <option value="Neonatology">Neonatology (Newborn care)</option>
                                    <option value="Pediatric Behavioral Medicine">Pediatric Behavioral Medicine</option>
                                    <option value="Obstetrics & Gynecology">Obstetrics & Gynecology (OB/GYN)</option>
                                    <option value="Gynecologic Oncology">Gynecologic Oncology</option>
                                    <option value="Reproductive Endocrinology & Infertility">Reproductive Endocrinology & Infertility</option>
                                    <option value="Maternal–Fetal Medicine">Maternal–Fetal Medicine</option>
                                </optgroup>

                                <optgroup label="🧬 Diagnostic & Support Specialties">
                                    <option value="Pathology">Pathology (Laboratory medicine)</option>
                                    <option value="Radiology">Radiology (Medical imaging)</option>
                                    <option value="Interventional Radiology">Interventional Radiology</option>
                                    <option value="Nuclear Medicine">Nuclear Medicine</option>
                                    <option value="Endoscopy">Endoscopy / GI Endoscopy</option>
                                    <option value="Electrodiagnostic Medicine">Electrodiagnostic Medicine (EMG, EEG)</option>
                                </optgroup>

                                <optgroup label="🏥 Other Medical Specialties">
                                    <option value="Oncology">Oncology (Medical cancer care)</option>
                                    <option value="Hepatology">Hepatology (Liver diseases)</option>
                                    <option value="Genetic Hematology">Genetic Hematology</option>
                                    <option value="Geriatrics">Geriatrics (Elderly care)</option>
                                    <option value="Physical Medicine & Rehabilitation">Physical Medicine & Rehabilitation</option>
                                    <option value="Occupational & Environmental Medicine">Occupational & Environmental Medicine</option>
                                    <option value="Sports Medicine">Sports Medicine</option>
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
                            <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty') }}">

                            @error('specialty')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Verification Status -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('is_verified') is-invalid @enderror"
                                       type="checkbox" name="is_verified" value="1" id="is_verified"
                                       {{ old('is_verified') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_verified">
                                    Mark user as verified
                                </label>
                            </div>
                            <small class="text-muted">Verified users have confirmed their identity and credentials.</small>
                            @error('is_verified')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Admin Status -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('is_admin') is-invalid @enderror"
                                       type="checkbox" name="is_admin" value="1" id="is_admin"
                                       {{ old('is_admin') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_admin">
                                    Grant admin privileges
                                </label>
                            </div>
                            <small class="text-muted">Admin users can manage all system users and settings.</small>
                            @error('is_admin')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-person-plus me-2"></i>Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
