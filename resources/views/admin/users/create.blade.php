@extends('layouts.admin')

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
    const roleElement = document.getElementById('role');
    const subscriptionSettings = document.getElementById('subscription-settings');
    const dobField = document.getElementById('date-of-birth-field');
    const genderField = document.getElementById('gender-field');

    if (!roleElement) return;

    const userType = roleElement.value;

    // Toggle subscription settings (only for doctors)
    if (subscriptionSettings) {
        if (userType === 'doctor') {
            subscriptionSettings.style.display = 'block';
        } else {
            subscriptionSettings.style.display = 'none';
        }
    }

    // Toggle patient fields (DOB, Gender)
    if (userType === 'patient') {
        if (dobField) dobField.style.display = 'block';
        if (genderField) genderField.style.display = 'block';
    } else {
        if (dobField) dobField.style.display = 'none';
        if (genderField) genderField.style.display = 'none';
    }
}

function toggleHospitalField() {
    const roleElement = document.getElementById('role');
    const hospitalAdminNote = document.getElementById('hospital-admin-note');

    if (!roleElement || !hospitalAdminNote) {
        return; // Elements don't exist, skip
    }

    const userType = roleElement.value;

    if (userType === 'hospital_admin') {
        hospitalAdminNote.style.display = 'block';
    } else {
        hospitalAdminNote.style.display = 'none';
    }
}

function toggleCustomSpecialtyAdmin() {
    const select = document.getElementById('specialty_select');
    const customContainer = document.getElementById('custom_specialty_container_admin');
    const customInput = document.getElementById('custom_specialty_admin');
    const hiddenInput = document.getElementById('specialty_admin');

    if (!select || !customContainer) return;

    if (select.value === 'other') {
        customContainer.style.display = 'block';
        if (customInput) {
            customInput.required = true;
            customInput.focus();
        }
        if (hiddenInput) hiddenInput.value = '';
    } else {
        customContainer.style.display = 'none';
        if (customInput) {
            customInput.required = false;
            customInput.value = '';
        }
        if (hiddenInput) hiddenInput.value = select.value;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleMedicalSpecialty();
    toggleHospitalField();

    // Also call it immediately in case no role is selected yet
    const roleElement = document.getElementById('role');
    if (roleElement && roleElement.value) {
        toggleMedicalSpecialty();
    }

    const customInput = document.getElementById('custom_specialty_admin');
    const hiddenInput = document.getElementById('specialty_admin');
    const select = document.getElementById('specialty_select');

    if (select) {
        // Handle custom input changes
        if (customInput) {
            customInput.addEventListener('input', function() {
                if (select.value === 'other' && hiddenInput) {
                    hiddenInput.value = this.value;
                }
            });
        }

        select.addEventListener('change', function() {
            toggleCustomSpecialtyAdmin();
        });

        // Handle form submission
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (select.value === 'other' && customInput) {
                    if (!customInput.value.trim()) {
                        e.preventDefault();
                        customInput.focus();
                        customInput.style.borderColor = '#dc3545';
                        return false;
                    }
                    if (hiddenInput) hiddenInput.value = customInput.value.trim();
                } else if (hiddenInput) {
                    hiddenInput.value = select.value;
                    if (customInput) customInput.value = '';
                }
            });
        }
    }

    // Add event listener to role dropdown
    const roleElement = document.getElementById('role');
    if (roleElement) {
        roleElement.addEventListener('change', function() {
            toggleMedicalSpecialty();
            toggleHospitalField();
        });
    }
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

                        <!-- Phone Number -->
                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="Enter phone number (e.g., +1234567890)"
                                   pattern="^\+?[1-9]\d{6,14}$">
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Required for SMS invoice reminders. Include country code (e.g., +1 for US)
                                </small>
                            </div>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- User Role -->
                        <div class="mb-4">
                            <label for="role" class="form-label fw-bold">User Role <span class="text-danger">*</span></label>
                            <select id="role" name="role" class="form-control @error('role') is-invalid @enderror" 
                                    onchange="toggleMedicalSpecialty(); toggleHospitalField();" required>
                                <option value="">-- Select Role --</option>
                                <option value="doctor" {{ old('role') == 'doctor' ? 'selected' : '' }}>Doctor</option>
                                <option value="hospital_admin" {{ old('role') == 'hospital_admin' ? 'selected' : '' }}>Hospital Admin</option>
                                <option value="patient" {{ old('role') == 'patient' ? 'selected' : '' }}>Patient</option>
                            </select>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Doctor:</strong> Individual medical practitioner<br>
                                    <strong>Hospital Admin:</strong> Manages doctors in a hospital/clinic<br>
                                    <strong>Patient:</strong> Regular patient user
                                </small>
                            </div>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Note for Hospital Admin -->
                        <div class="mb-4" id="hospital-admin-note" style="display: {{ old('role') == 'hospital_admin' ? 'block' : 'none' }};">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Hospital Admin Account:</strong> Hospital admins will manage their own hospital information after account creation, similar to how individual doctors manage their clinic information.
                            </div>
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

                        <!-- Date of Birth - Only for Patients -->
                        <div class="mb-4" id="date-of-birth-field" style="display: {{ old('role') == 'patient' ? 'block' : 'none' }}">
                            <label for="date_of_birth" class="form-label fw-bold">Date of Birth</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="form-control @error('date_of_birth') is-invalid @enderror">
                            <small class="text-muted">Required for patient identification</small>
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Gender - Only for Patients -->
                        <div class="mb-4" id="gender-field" style="display: {{ old('role') == 'patient' ? 'block' : 'none' }}">
                            <label for="gender" class="form-label fw-bold">Gender</label>
                            <select id="gender" name="gender" class="form-control @error('gender') is-invalid @enderror">
                                <option value="">-- Select Gender --</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <small class="text-muted">Required for patient identification</small>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Medical Specialty -->
                        <div class="mb-4" id="specialty-field">
                            <label for="specialty_select" class="form-label fw-bold">Medical Specialty</label>
                            <select class="form-control @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialtyAdmin()">
                                <option value="" {{ old('specialty_select') == '' ? 'selected' : '' }}>-- Select Specialty --</option>
                                
                                <optgroup label="🧠 General & Internal Medicine">
                                    <option value="General Practitioner" {{ old('specialty_select') == 'General Practitioner' ? 'selected' : '' }}>General Practitioner (GP) / Family Medicine</option>
                                    <option value="Internal Medicine" {{ old('specialty_select') == 'Internal Medicine' ? 'selected' : '' }}>Internal Medicine (Internist)</option>
                                </optgroup>
                                
                                <optgroup label="🩺 Internal Medicine Subspecialties">
                                    <option value="Cardiology" {{ old('specialty_select') == 'Cardiology' ? 'selected' : '' }}>Cardiology (Heart)</option>
                                    <option value="Pulmonology" {{ old('specialty_select') == 'Pulmonology' ? 'selected' : '' }}>Pulmonology (Lungs)</option>
                                    <option value="Gastroenterology" {{ old('specialty_select') == 'Gastroenterology' ? 'selected' : '' }}>Gastroenterology (Digestive system)</option>
                                    <option value="Nephrology" {{ old('specialty_select') == 'Nephrology' ? 'selected' : '' }}>Nephrology (Kidneys)</option>
                                    <option value="Endocrinology" {{ old('specialty_select') == 'Endocrinology' ? 'selected' : '' }}>Endocrinology (Hormones & glands)</option>
                                    <option value="Hematology" {{ old('specialty_select') == 'Hematology' ? 'selected' : '' }}>Hematology (Blood)</option>
                                    <option value="Hematology-Oncology" {{ old('specialty_select') == 'Hematology-Oncology' ? 'selected' : '' }}>Hematology-Oncology (Blood cancers)</option>
                                    <option value="Rheumatology" {{ old('specialty_select') == 'Rheumatology' ? 'selected' : '' }}>Rheumatology (Joints & autoimmune diseases)</option>
                                    <option value="Infectious Disease" {{ old('specialty_select') == 'Infectious Disease' ? 'selected' : '' }}>Infectious Disease</option>
                                    <option value="Dermatology" {{ old('specialty_select') == 'Dermatology' ? 'selected' : '' }}>Dermatology (Skin, hair, nails)</option>
                                    <option value="Allergy & Immunology" {{ old('specialty_select') == 'Allergy & Immunology' ? 'selected' : '' }}>Allergy & Immunology</option>
                                    <option value="Reproductive Endocrinology" {{ old('specialty_select') == 'Reproductive Endocrinology' ? 'selected' : '' }}>Reproductive Endocrinology (Fertility hormones)</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Emergency & Critical Care">
                                    <option value="Emergency Medicine" {{ old('specialty_select') == 'Emergency Medicine' ? 'selected' : '' }}>Emergency Medicine</option>
                                    <option value="Critical Care" {{ old('specialty_select') == 'Critical Care' ? 'selected' : '' }}>Critical Care / Intensive Care Medicine</option>
                                </optgroup>
                                
                                <optgroup label="💉 Anesthesia & Pain Management">
                                    <option value="Anesthesiology" {{ old('specialty_select') == 'Anesthesiology' ? 'selected' : '' }}>Anesthesiology</option>
                                    <option value="Pain Management" {{ old('specialty_select') == 'Pain Management' ? 'selected' : '' }}>Pain Management / Interventional Pain Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Neurology & Psychiatry">
                                    <option value="Neurology" {{ old('specialty_select') == 'Neurology' ? 'selected' : '' }}>Neurology (Brain & nerves)</option>
                                    <option value="Neurosurgery" {{ old('specialty_select') == 'Neurosurgery' ? 'selected' : '' }}>Neurosurgery (Brain & spine surgery)</option>
                                    <option value="Psychiatry" {{ old('specialty_select') == 'Psychiatry' ? 'selected' : '' }}>Psychiatry (Mental health)</option>
                                    <option value="Child & Adolescent Psychiatry" {{ old('specialty_select') == 'Child & Adolescent Psychiatry' ? 'selected' : '' }}>Child & Adolescent Psychiatry</option>
                                    <option value="Behavioral & Developmental Pediatrics" {{ old('specialty_select') == 'Behavioral & Developmental Pediatrics' ? 'selected' : '' }}>Behavioral & Developmental Pediatrics</option>
                                </optgroup>
                                
                                <optgroup label="🦴 Surgical Specialties">
                                    <option value="General Surgery" {{ old('specialty_select') == 'General Surgery' ? 'selected' : '' }}>General Surgery</option>
                                    <option value="Orthopedic Surgery" {{ old('specialty_select') == 'Orthopedic Surgery' ? 'selected' : '' }}>Orthopedic Surgery (Bones & joints)</option>
                                    <option value="Cardiothoracic Surgery" {{ old('specialty_select') == 'Cardiothoracic Surgery' ? 'selected' : '' }}>Cardiothoracic Surgery (Heart & lungs)</option>
                                    <option value="Vascular Surgery" {{ old('specialty_select') == 'Vascular Surgery' ? 'selected' : '' }}>Vascular Surgery (Blood vessels)</option>
                                    <option value="Pediatric Vascular Surgery" {{ old('specialty_select') == 'Pediatric Vascular Surgery' ? 'selected' : '' }}>Pediatric Vascular Surgery</option>
                                    <option value="Plastic & Reconstructive Surgery" {{ old('specialty_select') == 'Plastic & Reconstructive Surgery' ? 'selected' : '' }}>Plastic & Reconstructive Surgery</option>
                                    <option value="Oral & Maxillofacial Surgery" {{ old('specialty_select') == 'Oral & Maxillofacial Surgery' ? 'selected' : '' }}>Oral & Maxillofacial Surgery</option>
                                    <option value="Surgical Oncology" {{ old('specialty_select') == 'Surgical Oncology' ? 'selected' : '' }}>Surgical Oncology (Cancer surgery)</option>
                                    <option value="Colorectal Surgery" {{ old('specialty_select') == 'Colorectal Surgery' ? 'selected' : '' }}>Colorectal Surgery</option>
                                    <option value="Urology" {{ old('specialty_select') == 'Urology' ? 'selected' : '' }}>Urology (Urinary & male reproductive system)</option>
                                    <option value="ENT" {{ old('specialty_select') == 'ENT' ? 'selected' : '' }}>ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                    <option value="Ophthalmic Surgery" {{ old('specialty_select') == 'Ophthalmic Surgery' ? 'selected' : '' }}>Ophthalmic Surgery (Eye surgery)</option>
                                    <option value="Pediatric Surgery" {{ old('specialty_select') == 'Pediatric Surgery' ? 'selected' : '' }}>Pediatric Surgery</option>
                                    <option value="Hand Surgery" {{ old('specialty_select') == 'Hand Surgery' ? 'selected' : '' }}>Hand Surgery</option>
                                </optgroup>
                                
                                <optgroup label="👶 Pediatrics & Women's Health">
                                    <option value="Pediatrics" {{ old('specialty_select') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                    <option value="Neonatology" {{ old('specialty_select') == 'Neonatology' ? 'selected' : '' }}>Neonatology (Newborn care)</option>
                                    <option value="Pediatric Behavioral Medicine" {{ old('specialty_select') == 'Pediatric Behavioral Medicine' ? 'selected' : '' }}>Pediatric Behavioral Medicine</option>
                                    <option value="Obstetrics & Gynecology" {{ old('specialty_select') == 'Obstetrics & Gynecology' ? 'selected' : '' }}>Obstetrics & Gynecology (OB/GYN)</option>
                                    <option value="Gynecologic Oncology" {{ old('specialty_select') == 'Gynecologic Oncology' ? 'selected' : '' }}>Gynecologic Oncology</option>
                                    <option value="Reproductive Endocrinology & Infertility" {{ old('specialty_select') == 'Reproductive Endocrinology & Infertility' ? 'selected' : '' }}>Reproductive Endocrinology & Infertility</option>
                                    <option value="Maternal–Fetal Medicine" {{ old('specialty_select') == 'Maternal–Fetal Medicine' ? 'selected' : '' }}>Maternal–Fetal Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🧬 Diagnostic & Support Specialties">
                                    <option value="Pathology" {{ old('specialty_select') == 'Pathology' ? 'selected' : '' }}>Pathology (Laboratory medicine)</option>
                                    <option value="Radiology" {{ old('specialty_select') == 'Radiology' ? 'selected' : '' }}>Radiology (Medical imaging)</option>
                                    <option value="Interventional Radiology" {{ old('specialty_select') == 'Interventional Radiology' ? 'selected' : '' }}>Interventional Radiology</option>
                                    <option value="Nuclear Medicine" {{ old('specialty_select') == 'Nuclear Medicine' ? 'selected' : '' }}>Nuclear Medicine</option>
                                    <option value="Endoscopy" {{ old('specialty_select') == 'Endoscopy' ? 'selected' : '' }}>Endoscopy / GI Endoscopy</option>
                                    <option value="Electrodiagnostic Medicine" {{ old('specialty_select') == 'Electrodiagnostic Medicine' ? 'selected' : '' }}>Electrodiagnostic Medicine (EMG, EEG)</option>
                                </optgroup>
                                
                                <optgroup label="🏥 Other Medical Specialties">
                                    <option value="Oncology" {{ old('specialty_select') == 'Oncology' ? 'selected' : '' }}>Oncology (Medical cancer care)</option>
                                    <option value="Hepatology" {{ old('specialty_select') == 'Hepatology' ? 'selected' : '' }}>Hepatology (Liver diseases)</option>
                                    <option value="Genetic Hematology" {{ old('specialty_select') == 'Genetic Hematology' ? 'selected' : '' }}>Genetic Hematology</option>
                                    <option value="Geriatrics" {{ old('specialty_select') == 'Geriatrics' ? 'selected' : '' }}>Geriatrics (Elderly care)</option>
                                    <option value="Physical Medicine & Rehabilitation" {{ old('specialty_select') == 'Physical Medicine & Rehabilitation' ? 'selected' : '' }}>Physical Medicine & Rehabilitation</option>
                                    <option value="Occupational & Environmental Medicine" {{ old('specialty_select') == 'Occupational & Environmental Medicine' ? 'selected' : '' }}>Occupational & Environmental Medicine</option>
                                    <option value="Sports Medicine" {{ old('specialty_select') == 'Sports Medicine' ? 'selected' : '' }}>Sports Medicine</option>
                                    <option value="Maternal Health Specialist" {{ old('specialty_select') == 'Maternal Health Specialist' ? 'selected' : '' }}>Maternal Health Specialist</option>
                                    <option value="Clinical Nutrition" {{ old('specialty_select') == 'Clinical Nutrition' ? 'selected' : '' }}>Clinical Nutrition / Dietetics</option>
                                    <option value="Neuro-rehabilitation" {{ old('specialty_select') == 'Neuro-rehabilitation' ? 'selected' : '' }}>Neuro-rehabilitation</option>
                                </optgroup>
                                
                                <optgroup label="🧪 Specialized & Advanced Fields">
                                    <option value="Medical Genetics" {{ old('specialty_select') == 'Medical Genetics' ? 'selected' : '' }}>Medical Genetics</option>
                                    <option value="Hematologic Oncology" {{ old('specialty_select') == 'Hematologic Oncology' ? 'selected' : '' }}>Hematologic Oncology</option>
                                    <option value="Transplant Medicine" {{ old('specialty_select') == 'Transplant Medicine' ? 'selected' : '' }}>Transplant Medicine / Surgery</option>
                                    <option value="Tropical Medicine" {{ old('specialty_select') == 'Tropical Medicine' ? 'selected' : '' }}>Tropical Medicine</option>
                                    <option value="Pre-hospital Emergency" {{ old('specialty_select') == 'Pre-hospital Emergency' ? 'selected' : '' }}>Pre-hospital Emergency / EMS</option>
                                </optgroup>
                                
                                <optgroup label="✏️ Custom">
                                    <option value="other">Other (Please specify)</option>
                                </optgroup>
                            </select>
                            
                            <!-- Custom Specialty Input (Hidden by default) -->
                            <div id="custom_specialty_container_admin" style="display: none;" class="mt-2">
                                <input 
                                    type="text" 
                                    name="custom_specialty" 
                                    id="custom_specialty_admin" 
                                    class="form-control"
                                    placeholder="Please enter your medical specialty"
                                >
                            </div>
                            
                            <!-- Hidden field to store the final specialty value -->
                            <input type="hidden" name="specialty" id="specialty_admin" value="{{ old('specialty') }}">
                            
                            @error('specialty')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subscription Pricing - Only for Doctors -->
                        <div class="card mb-4" id="subscription-settings" style="display: {{ old('role') == 'doctor' ? 'block' : 'none' }}; border: 2px solid #e9ecef; border-radius: 10px;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-credit-card me-2"></i>Subscription Pricing
                                </h6>
                                <small class="text-muted">Update monthly and yearly subscription prices</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="monthly_price" class="form-label fw-bold">Monthly Price ($)</label>
                                        <input id="monthly_price" type="number" name="monthly_price"
                                               value="{{ old('monthly_price', 99.00) }}"
                                               step="0.01" min="0" max="99999.99"
                                               class="form-control @error('monthly_price') is-invalid @enderror"
                                               placeholder="99.00">
                                        <small class="text-muted">Default: $99.00</small>
                                        @error('monthly_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="yearly_price" class="form-label fw-bold">Yearly Price ($)</label>
                                        <input id="yearly_price" type="number" name="yearly_price"
                                               value="{{ old('yearly_price', 950.00) }}"
                                               step="0.01" min="0" max="99999.99"
                                               class="form-control @error('yearly_price') is-invalid @enderror"
                                               placeholder="950.00">
                                        <small class="text-muted">Default: $950.00</small>
                                        @error('yearly_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Note:</strong> These prices are specific to this user only and will not affect other users.
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label for="grace_period_days" class="form-label fw-bold">Grace Period (Days)</label>
                                        <input id="grace_period_days" type="number" name="grace_period_days"
                                               value="{{ old('grace_period_days', 7) }}" min="1" max="30"
                                               class="form-control @error('grace_period_days') is-invalid @enderror">
                                        <small class="text-muted">Days after due date before restrictions</small>
                                        @error('grace_period_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="reminder_frequency_days" class="form-label fw-bold">Reminder Frequency (Days)</label>
                                        <input id="reminder_frequency_days" type="number" name="reminder_frequency_days"
                                               value="{{ old('reminder_frequency_days', 3) }}" min="1" max="30"
                                               class="form-control @error('reminder_frequency_days') is-invalid @enderror">
                                        <small class="text-muted">Days between reminder notifications</small>
                                        @error('reminder_frequency_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Cost Limit -->
                        <div class="mb-4">
                            <label for="monthly_cost_limit" class="form-label fw-bold">Monthly Cost Limit (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input id="monthly_cost_limit" type="number" name="monthly_cost_limit" 
                                       value="{{ old('monthly_cost_limit', 0) }}" 
                                       step="0.01" min="0"
                                       class="form-control @error('monthly_cost_limit') is-invalid @enderror"
                                       placeholder="0.00">
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Set to 0 for no limit. Excess costs will be added to monthly invoices.
                            </small>
                            @error('monthly_cost_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
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

@push('styles')
<style>
/* Custom specialty input styling for admin create page */
#custom_specialty_container_admin {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#custom_specialty_admin {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s ease;
}

#custom_specialty_admin:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
}
</style>
@endpush