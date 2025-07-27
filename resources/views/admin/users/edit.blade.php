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

                        <!-- Phone Number -->
                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required
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

                        <!-- Medical Specialty -->
                        <div class="mb-4">
                            <label for="specialty_select" class="form-label fw-bold">Medical Specialty <span class="text-danger">*</span></label>
                            @php
                                $currentSpecialty = old('specialty', $user->setting->specialty ?? '');
                            @endphp
                            <select class="form-control @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialtyAdminEdit()" required>
                                <option value="" {{ $currentSpecialty == '' ? 'selected' : '' }}>-- Select Specialty --</option>
                                
                                <optgroup label="🧠 General & Internal Medicine">
                                    <option value="General Practitioner" {{ $currentSpecialty == 'General Practitioner' ? 'selected' : '' }}>General Practitioner (GP) / Family Medicine</option>
                                    <option value="Internal Medicine" {{ $currentSpecialty == 'Internal Medicine' ? 'selected' : '' }}>Internal Medicine (Internist)</option>
                                </optgroup>
                                
                                <optgroup label="🩺 Internal Medicine Subspecialties">
                                    <option value="Cardiology" {{ $currentSpecialty == 'Cardiology' ? 'selected' : '' }}>Cardiology (Heart)</option>
                                    <option value="Pulmonology" {{ $currentSpecialty == 'Pulmonology' ? 'selected' : '' }}>Pulmonology (Lungs)</option>
                                    <option value="Gastroenterology" {{ $currentSpecialty == 'Gastroenterology' ? 'selected' : '' }}>Gastroenterology (Digestive system)</option>
                                    <option value="Nephrology" {{ $currentSpecialty == 'Nephrology' ? 'selected' : '' }}>Nephrology (Kidneys)</option>
                                    <option value="Endocrinology" {{ $currentSpecialty == 'Endocrinology' ? 'selected' : '' }}>Endocrinology (Hormones & glands)</option>
                                    <option value="Hematology" {{ $currentSpecialty == 'Hematology' ? 'selected' : '' }}>Hematology (Blood)</option>
                                    <option value="Hematology-Oncology" {{ $currentSpecialty == 'Hematology-Oncology' ? 'selected' : '' }}>Hematology-Oncology (Blood cancers)</option>
                                    <option value="Rheumatology" {{ $currentSpecialty == 'Rheumatology' ? 'selected' : '' }}>Rheumatology (Joints & autoimmune diseases)</option>
                                    <option value="Infectious Disease" {{ $currentSpecialty == 'Infectious Disease' ? 'selected' : '' }}>Infectious Disease</option>
                                    <option value="Dermatology" {{ $currentSpecialty == 'Dermatology' ? 'selected' : '' }}>Dermatology (Skin, hair, nails)</option>
                                    <option value="Allergy & Immunology" {{ $currentSpecialty == 'Allergy & Immunology' ? 'selected' : '' }}>Allergy & Immunology</option>
                                    <option value="Reproductive Endocrinology" {{ $currentSpecialty == 'Reproductive Endocrinology' ? 'selected' : '' }}>Reproductive Endocrinology (Fertility hormones)</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Emergency & Critical Care">
                                    <option value="Emergency Medicine" {{ $currentSpecialty == 'Emergency Medicine' ? 'selected' : '' }}>Emergency Medicine</option>
                                    <option value="Critical Care" {{ $currentSpecialty == 'Critical Care' ? 'selected' : '' }}>Critical Care / Intensive Care Medicine</option>
                                </optgroup>
                                
                                <optgroup label="💉 Anesthesia & Pain Management">
                                    <option value="Anesthesiology" {{ $currentSpecialty == 'Anesthesiology' ? 'selected' : '' }}>Anesthesiology</option>
                                    <option value="Pain Management" {{ $currentSpecialty == 'Pain Management' ? 'selected' : '' }}>Pain Management / Interventional Pain Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Neurology & Psychiatry">
                                    <option value="Neurology" {{ $currentSpecialty == 'Neurology' ? 'selected' : '' }}>Neurology (Brain & nerves)</option>
                                    <option value="Neurosurgery" {{ $currentSpecialty == 'Neurosurgery' ? 'selected' : '' }}>Neurosurgery (Brain & spine surgery)</option>
                                    <option value="Psychiatry" {{ $currentSpecialty == 'Psychiatry' ? 'selected' : '' }}>Psychiatry (Mental health)</option>
                                    <option value="Child & Adolescent Psychiatry" {{ $currentSpecialty == 'Child & Adolescent Psychiatry' ? 'selected' : '' }}>Child & Adolescent Psychiatry</option>
                                    <option value="Behavioral & Developmental Pediatrics" {{ $currentSpecialty == 'Behavioral & Developmental Pediatrics' ? 'selected' : '' }}>Behavioral & Developmental Pediatrics</option>
                                </optgroup>
                                
                                <optgroup label="🦴 Surgical Specialties">
                                    <option value="General Surgery" {{ $currentSpecialty == 'General Surgery' ? 'selected' : '' }}>General Surgery</option>
                                    <option value="Orthopedic Surgery" {{ $currentSpecialty == 'Orthopedic Surgery' ? 'selected' : '' }}>Orthopedic Surgery (Bones & joints)</option>
                                    <option value="Cardiothoracic Surgery" {{ $currentSpecialty == 'Cardiothoracic Surgery' ? 'selected' : '' }}>Cardiothoracic Surgery (Heart & lungs)</option>
                                    <option value="Vascular Surgery" {{ $currentSpecialty == 'Vascular Surgery' ? 'selected' : '' }}>Vascular Surgery (Blood vessels)</option>
                                    <option value="Pediatric Vascular Surgery" {{ $currentSpecialty == 'Pediatric Vascular Surgery' ? 'selected' : '' }}>Pediatric Vascular Surgery</option>
                                    <option value="Plastic & Reconstructive Surgery" {{ $currentSpecialty == 'Plastic & Reconstructive Surgery' ? 'selected' : '' }}>Plastic & Reconstructive Surgery</option>
                                    <option value="Oral & Maxillofacial Surgery" {{ $currentSpecialty == 'Oral & Maxillofacial Surgery' ? 'selected' : '' }}>Oral & Maxillofacial Surgery</option>
                                    <option value="Surgical Oncology" {{ $currentSpecialty == 'Surgical Oncology' ? 'selected' : '' }}>Surgical Oncology (Cancer surgery)</option>
                                    <option value="Colorectal Surgery" {{ $currentSpecialty == 'Colorectal Surgery' ? 'selected' : '' }}>Colorectal Surgery</option>
                                    <option value="Urology" {{ $currentSpecialty == 'Urology' ? 'selected' : '' }}>Urology (Urinary & male reproductive system)</option>
                                    <option value="ENT" {{ $currentSpecialty == 'ENT' ? 'selected' : '' }}>ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                    <option value="Ophthalmic Surgery" {{ $currentSpecialty == 'Ophthalmic Surgery' ? 'selected' : '' }}>Ophthalmic Surgery (Eye surgery)</option>
                                    <option value="Pediatric Surgery" {{ $currentSpecialty == 'Pediatric Surgery' ? 'selected' : '' }}>Pediatric Surgery</option>
                                    <option value="Hand Surgery" {{ $currentSpecialty == 'Hand Surgery' ? 'selected' : '' }}>Hand Surgery</option>
                                </optgroup>
                                
                                <optgroup label="👶 Pediatrics & Women's Health">
                                    <option value="Pediatrics" {{ $currentSpecialty == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                    <option value="Neonatology" {{ $currentSpecialty == 'Neonatology' ? 'selected' : '' }}>Neonatology (Newborn care)</option>
                                    <option value="Pediatric Behavioral Medicine" {{ $currentSpecialty == 'Pediatric Behavioral Medicine' ? 'selected' : '' }}>Pediatric Behavioral Medicine</option>
                                    <option value="Obstetrics & Gynecology" {{ $currentSpecialty == 'Obstetrics & Gynecology' ? 'selected' : '' }}>Obstetrics & Gynecology (OB/GYN)</option>
                                    <option value="Gynecologic Oncology" {{ $currentSpecialty == 'Gynecologic Oncology' ? 'selected' : '' }}>Gynecologic Oncology</option>
                                    <option value="Reproductive Endocrinology & Infertility" {{ $currentSpecialty == 'Reproductive Endocrinology & Infertility' ? 'selected' : '' }}>Reproductive Endocrinology & Infertility</option>
                                    <option value="Maternal–Fetal Medicine" {{ $currentSpecialty == 'Maternal–Fetal Medicine' ? 'selected' : '' }}>Maternal–Fetal Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🧬 Diagnostic & Support Specialties">
                                    <option value="Pathology" {{ $currentSpecialty == 'Pathology' ? 'selected' : '' }}>Pathology (Laboratory medicine)</option>
                                    <option value="Radiology" {{ $currentSpecialty == 'Radiology' ? 'selected' : '' }}>Radiology (Medical imaging)</option>
                                    <option value="Interventional Radiology" {{ $currentSpecialty == 'Interventional Radiology' ? 'selected' : '' }}>Interventional Radiology</option>
                                    <option value="Nuclear Medicine" {{ $currentSpecialty == 'Nuclear Medicine' ? 'selected' : '' }}>Nuclear Medicine</option>
                                    <option value="Endoscopy" {{ $currentSpecialty == 'Endoscopy' ? 'selected' : '' }}>Endoscopy / GI Endoscopy</option>
                                    <option value="Electrodiagnostic Medicine" {{ $currentSpecialty == 'Electrodiagnostic Medicine' ? 'selected' : '' }}>Electrodiagnostic Medicine (EMG, EEG)</option>
                                </optgroup>
                                
                                <optgroup label="🏥 Other Medical Specialties">
                                    <option value="Oncology" {{ $currentSpecialty == 'Oncology' ? 'selected' : '' }}>Oncology (Medical cancer care)</option>
                                    <option value="Hepatology" {{ $currentSpecialty == 'Hepatology' ? 'selected' : '' }}>Hepatology (Liver diseases)</option>
                                    <option value="Genetic Hematology" {{ $currentSpecialty == 'Genetic Hematology' ? 'selected' : '' }}>Genetic Hematology</option>
                                    <option value="Geriatrics" {{ $currentSpecialty == 'Geriatrics' ? 'selected' : '' }}>Geriatrics (Elderly care)</option>
                                    <option value="Physical Medicine & Rehabilitation" {{ $currentSpecialty == 'Physical Medicine & Rehabilitation' ? 'selected' : '' }}>Physical Medicine & Rehabilitation</option>
                                    <option value="Occupational & Environmental Medicine" {{ $currentSpecialty == 'Occupational & Environmental Medicine' ? 'selected' : '' }}>Occupational & Environmental Medicine</option>
                                    <option value="Sports Medicine" {{ $currentSpecialty == 'Sports Medicine' ? 'selected' : '' }}>Sports Medicine</option>
                                    <option value="Maternal Health Specialist" {{ $currentSpecialty == 'Maternal Health Specialist' ? 'selected' : '' }}>Maternal Health Specialist</option>
                                    <option value="Clinical Nutrition" {{ $currentSpecialty == 'Clinical Nutrition' ? 'selected' : '' }}>Clinical Nutrition / Dietetics</option>
                                    <option value="Neuro-rehabilitation" {{ $currentSpecialty == 'Neuro-rehabilitation' ? 'selected' : '' }}>Neuro-rehabilitation</option>
                                </optgroup>
                                
                                <optgroup label="🧪 Specialized & Advanced Fields">
                                    <option value="Medical Genetics" {{ $currentSpecialty == 'Medical Genetics' ? 'selected' : '' }}>Medical Genetics</option>
                                    <option value="Hematologic Oncology" {{ $currentSpecialty == 'Hematologic Oncology' ? 'selected' : '' }}>Hematologic Oncology</option>
                                    <option value="Transplant Medicine" {{ $currentSpecialty == 'Transplant Medicine' ? 'selected' : '' }}>Transplant Medicine / Surgery</option>
                                    <option value="Tropical Medicine" {{ $currentSpecialty == 'Tropical Medicine' ? 'selected' : '' }}>Tropical Medicine</option>
                                    <option value="Pre-hospital Emergency" {{ $currentSpecialty == 'Pre-hospital Emergency' ? 'selected' : '' }}>Pre-hospital Emergency / EMS</option>
                                </optgroup>
                                
                                <optgroup label="✏️ Custom">
                                    <option value="other">Other (Please specify)</option>
                                </optgroup>
                            </select>
                            
                            <!-- Custom Specialty Input (Hidden by default) -->
                            <div id="custom_specialty_container_admin_edit" style="display: none;" class="mt-2">
                                <input 
                                    type="text" 
                                    name="custom_specialty" 
                                    id="custom_specialty_admin_edit" 
                                    class="form-control"
                                    placeholder="Please enter your medical specialty"
                                    value="{{ $currentSpecialty }}"
                                >
                            </div>
                            
                            <!-- Hidden field to store the final specialty value -->
                            <input type="hidden" name="specialty" id="specialty_admin_edit" value="{{ $currentSpecialty }}">
                            
                            @error('specialty')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Monthly Invoice Settings -->
                        <div class="card mb-4" style="border: 2px solid #e9ecef; border-radius: 10px;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bi bi-credit-card me-2"></i>Monthly Invoice Settings
                                </h6>
                                <small class="text-muted">Configure monthly billing for this user</small>
                            </div>
                            <div class="card-body">
                                @php
                                    $monthlySettings = $user->monthlyInvoiceSetting;
                                @endphp
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="billing_amount" class="form-label fw-bold">Billing Amount ($)</label>
                                        <input id="billing_amount" type="number" name="billing_amount" 
                                               value="{{ old('billing_amount', $monthlySettings->billing_amount ?? '') }}" 
                                               step="0.01" min="0" max="99999.99"
                                               class="form-control @error('billing_amount') is-invalid @enderror"
                                               placeholder="e.g., 199.99">
                                        <small class="text-muted">Amount charged per billing period</small>
                                        @error('billing_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="grace_period_days" class="form-label fw-bold">Grace Period (Days)</label>
                                        <input id="grace_period_days" type="number" name="grace_period_days" 
                                               value="{{ old('grace_period_days', $monthlySettings->grace_period_days ?? 7) }}" 
                                               min="1" max="30"
                                               class="form-control @error('grace_period_days') is-invalid @enderror">
                                        <small class="text-muted">Days after due date before restrictions</small>
                                        @error('grace_period_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="reminder_frequency_days" class="form-label fw-bold">Reminder Frequency (Days)</label>
                                        <input id="reminder_frequency_days" type="number" name="reminder_frequency_days" 
                                               value="{{ old('reminder_frequency_days', $monthlySettings->reminder_frequency_days ?? 3) }}" 
                                               min="1" max="30"
                                               class="form-control @error('reminder_frequency_days') is-invalid @enderror">
                                        <small class="text-muted">Days between reminder notifications</small>
                                        @error('reminder_frequency_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                @if($monthlySettings)
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <strong>Current Status:</strong> 
                                                @if($monthlySettings->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                    Billing is enabled for {{ $monthlySettings->getAmountWithPeriod() }}
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                    Monthly billing is disabled
                                                @endif
                                                
                                                @if($monthlySettings->is_restricted)
                                                    <br><span class="badge bg-danger mt-1">Restricted</span>
                                                    User access is currently restricted due to unpaid invoices
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Note: Admin privileges are managed through the separate Admin system -->
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

                        <!-- Monthly Cost Limit -->
                        <div class="mb-4">
                            <label for="monthly_cost_limit" class="form-label fw-bold">Monthly Cost Limit (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input id="monthly_cost_limit" type="number" name="monthly_cost_limit" 
                                       value="{{ old('monthly_cost_limit', $user->monthly_cost_limit) }}" 
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

@push('scripts')
<script>
function toggleCustomSpecialtyAdminEdit() {
    const select = document.getElementById('specialty_select');
    const customContainer = document.getElementById('custom_specialty_container_admin_edit');
    const customInput = document.getElementById('custom_specialty_admin_edit');
    const hiddenInput = document.getElementById('specialty_admin_edit');
    
    if (select.value === 'other') {
        customContainer.style.display = 'block';
        customInput.required = true;
        customInput.focus();
        hiddenInput.value = ''; // Clear hidden field when showing custom input
    } else {
        customContainer.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
        hiddenInput.value = select.value; // Set hidden field to selected value
    }
}

// Initialize admin edit page functionality
document.addEventListener('DOMContentLoaded', function() {
    const customInput = document.getElementById('custom_specialty_admin_edit');
    const hiddenInput = document.getElementById('specialty_admin_edit');
    const select = document.getElementById('specialty_select');
    
    // Handle custom input changes
    customInput.addEventListener('input', function() {
        if (select.value === 'other') {
            hiddenInput.value = this.value;
        }
    });
    
    // Handle form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const select = document.getElementById('specialty_select');
        const customInput = document.getElementById('custom_specialty_admin_edit');
        const hiddenInput = document.getElementById('specialty_admin_edit');
        
        if (select.value === 'other') {
            if (!customInput.value.trim()) {
                e.preventDefault();
                customInput.focus();
                customInput.style.borderColor = '#dc3545';
                return false;
            }
            hiddenInput.value = customInput.value.trim();
        } else {
            hiddenInput.value = select.value;
            // Clear custom specialty when not using "other"
            customInput.value = '';
        }
    });
    
    // Initialize on page load - check if current specialty exists in dropdown
    const currentSpecialty = '{{ $user->setting->specialty ?? "" }}';
    
    if (currentSpecialty) {
        // Check if current specialty exists in dropdown options
        const selectOptions = Array.from(document.getElementById('specialty_select').options);
        const optionExists = selectOptions.some(option => option.value === currentSpecialty);
        
        if (optionExists) {
            document.getElementById('specialty_select').value = currentSpecialty;
        } else {
            // If specialty doesn't exist in dropdown, treat as custom
            document.getElementById('specialty_select').value = 'other';
            toggleCustomSpecialtyAdminEdit();
            document.getElementById('custom_specialty_admin_edit').value = currentSpecialty;
        }
        document.getElementById('specialty_admin_edit').value = currentSpecialty;
    }
});
</script>

<style>
/* Custom specialty input styling for admin edit page */
#custom_specialty_container_admin_edit {
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

#custom_specialty_admin_edit {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s ease;
}

#custom_specialty_admin_edit:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
}
</style>
@endpush