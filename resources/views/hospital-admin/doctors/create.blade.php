@extends('layouts.app')

@section('page-title', 'Add Doctor')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0">Add New Doctor</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hospital-admin.doctors.store') }}">
                        @csrf

                        <div class="form-group">
                            <label for="name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="specialty">Medical Specialty <span class="text-danger">*</span></label>
                            <select class="form-control @error('specialty') is-invalid @enderror" 
                                    name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialty()" required>
                                <option value="">-- Select Medical Specialty --</option>
                                
                                <optgroup label="🧠 General & Internal Medicine">
                                    <option value="General Practitioner" {{ old('specialty') == 'General Practitioner' ? 'selected' : '' }}>General Practitioner (GP) / Family Medicine</option>
                                    <option value="Internal Medicine" {{ old('specialty') == 'Internal Medicine' ? 'selected' : '' }}>Internal Medicine (Internist)</option>
                                </optgroup>
                                
                                <optgroup label="🩺 Internal Medicine Subspecialties">
                                    <option value="Cardiology" {{ old('specialty') == 'Cardiology' ? 'selected' : '' }}>Cardiology (Heart)</option>
                                    <option value="Pulmonology" {{ old('specialty') == 'Pulmonology' ? 'selected' : '' }}>Pulmonology (Lungs)</option>
                                    <option value="Gastroenterology" {{ old('specialty') == 'Gastroenterology' ? 'selected' : '' }}>Gastroenterology (Digestive system)</option>
                                    <option value="Nephrology" {{ old('specialty') == 'Nephrology' ? 'selected' : '' }}>Nephrology (Kidneys)</option>
                                    <option value="Endocrinology" {{ old('specialty') == 'Endocrinology' ? 'selected' : '' }}>Endocrinology (Hormones & glands)</option>
                                    <option value="Hematology" {{ old('specialty') == 'Hematology' ? 'selected' : '' }}>Hematology (Blood)</option>
                                    <option value="Hematology-Oncology" {{ old('specialty') == 'Hematology-Oncology' ? 'selected' : '' }}>Hematology-Oncology (Blood cancers)</option>
                                    <option value="Rheumatology" {{ old('specialty') == 'Rheumatology' ? 'selected' : '' }}>Rheumatology (Joints & autoimmune diseases)</option>
                                    <option value="Infectious Disease" {{ old('specialty') == 'Infectious Disease' ? 'selected' : '' }}>Infectious Disease</option>
                                    <option value="Dermatology" {{ old('specialty') == 'Dermatology' ? 'selected' : '' }}>Dermatology (Skin, hair, nails)</option>
                                    <option value="Allergy & Immunology" {{ old('specialty') == 'Allergy & Immunology' ? 'selected' : '' }}>Allergy & Immunology</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Emergency & Critical Care">
                                    <option value="Emergency Medicine" {{ old('specialty') == 'Emergency Medicine' ? 'selected' : '' }}>Emergency Medicine</option>
                                    <option value="Critical Care" {{ old('specialty') == 'Critical Care' ? 'selected' : '' }}>Critical Care / Intensive Care Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🦴 Surgical Specialties">
                                    <option value="General Surgery" {{ old('specialty') == 'General Surgery' ? 'selected' : '' }}>General Surgery</option>
                                    <option value="Orthopedic Surgery" {{ old('specialty') == 'Orthopedic Surgery' ? 'selected' : '' }}>Orthopedic Surgery (Bones & joints)</option>
                                    <option value="Cardiothoracic Surgery" {{ old('specialty') == 'Cardiothoracic Surgery' ? 'selected' : '' }}>Cardiothoracic Surgery (Heart & lungs)</option>
                                    <option value="Neurosurgery" {{ old('specialty') == 'Neurosurgery' ? 'selected' : '' }}>Neurosurgery (Brain & spine surgery)</option>
                                    <option value="Plastic & Reconstructive Surgery" {{ old('specialty') == 'Plastic & Reconstructive Surgery' ? 'selected' : '' }}>Plastic & Reconstructive Surgery</option>
                                    <option value="Urology" {{ old('specialty') == 'Urology' ? 'selected' : '' }}>Urology (Urinary & male reproductive system)</option>
                                    <option value="ENT" {{ old('specialty') == 'ENT' ? 'selected' : '' }}>ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                </optgroup>
                                
                                <optgroup label="👶 Pediatrics & Women's Health">
                                    <option value="Pediatrics" {{ old('specialty') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                    <option value="Neonatology" {{ old('specialty') == 'Neonatology' ? 'selected' : '' }}>Neonatology (Newborn care)</option>
                                    <option value="Obstetrics & Gynecology" {{ old('specialty') == 'Obstetrics & Gynecology' ? 'selected' : '' }}>Obstetrics & Gynecology (OB/GYN)</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Neurology & Psychiatry">
                                    <option value="Neurology" {{ old('specialty') == 'Neurology' ? 'selected' : '' }}>Neurology (Brain & nerves)</option>
                                    <option value="Psychiatry" {{ old('specialty') == 'Psychiatry' ? 'selected' : '' }}>Psychiatry (Mental health)</option>
                                </optgroup>
                                
                                <optgroup label="🧬 Diagnostic & Support Specialties">
                                    <option value="Pathology" {{ old('specialty') == 'Pathology' ? 'selected' : '' }}>Pathology (Laboratory medicine)</option>
                                    <option value="Radiology" {{ old('specialty') == 'Radiology' ? 'selected' : '' }}>Radiology (Medical imaging)</option>
                                    <option value="Anesthesiology" {{ old('specialty') == 'Anesthesiology' ? 'selected' : '' }}>Anesthesiology</option>
                                </optgroup>
                                
                                <optgroup label="🏥 Other Medical Specialties">
                                    <option value="Oncology" {{ old('specialty') == 'Oncology' ? 'selected' : '' }}>Oncology (Medical cancer care)</option>
                                    <option value="Geriatrics" {{ old('specialty') == 'Geriatrics' ? 'selected' : '' }}>Geriatrics (Elderly care)</option>
                                    <option value="Physical Medicine & Rehabilitation" {{ old('specialty') == 'Physical Medicine & Rehabilitation' ? 'selected' : '' }}>Physical Medicine & Rehabilitation</option>
                                    <option value="Sports Medicine" {{ old('specialty') == 'Sports Medicine' ? 'selected' : '' }}>Sports Medicine</option>
                                </optgroup>
                                
                                <optgroup label="✏️ Custom">
                                    <option value="other">Other (Please specify)</option>
                                </optgroup>
                            </select>
                            
                            <!-- Custom Specialty Input (Hidden by default) -->
                            <div id="custom_specialty_container" style="display: none;" class="mt-2">
                                <input 
                                    type="text" 
                                    name="custom_specialty" 
                                    id="custom_specialty" 
                                    class="form-control @error('custom_specialty') is-invalid @enderror"
                                    placeholder="Please enter the medical specialty"
                                    value="{{ old('custom_specialty') }}"
                                >
                                @error('custom_specialty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Hidden field to store the final specialty value -->
                            <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty') }}">
                            
                            @error('specialty')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Add Doctor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomSpecialty() {
    const select = document.getElementById('specialty_select');
    const customContainer = document.getElementById('custom_specialty_container');
    const customInput = document.getElementById('custom_specialty');
    const hiddenSpecialty = document.getElementById('specialty');
    
    if (select.value === 'other') {
        customContainer.style.display = 'block';
        customInput.required = true;
        hiddenSpecialty.value = '';
    } else {
        customContainer.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
        hiddenSpecialty.value = select.value;
    }
}

// Handle form submission to set the correct specialty value
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const specialtySelect = document.getElementById('specialty_select');
    const customSpecialty = document.getElementById('custom_specialty');
    const hiddenSpecialty = document.getElementById('specialty');
    
    form.addEventListener('submit', function(e) {
        if (specialtySelect.value === 'other') {
            if (customSpecialty.value.trim() === '') {
                e.preventDefault();
                alert('Please enter your medical specialty.');
                customSpecialty.focus();
                return false;
            }
            hiddenSpecialty.value = customSpecialty.value.trim();
        } else {
            hiddenSpecialty.value = specialtySelect.value;
        }
    });
    
    // Initialize on page load
    toggleCustomSpecialty();
});
</script>
@endsection