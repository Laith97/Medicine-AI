@extends('layouts.admin')
@section('title','Create New User')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-user-plus" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Create New User</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Add a new user to the system · Doctors, hospital admins or patients</p>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i>Back to Users</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-user-edit" style="color:#64748b"></i> User Information</h5>
                </div>
                <div class="card-body p-4" style="background:#fff">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Name <span class="text-danger">*</span></label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="form-control @error('name') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="Full name">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="email@example.com">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="phone" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Phone Number <span class="text-danger">*</span></label>
                                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required class="form-control @error('phone') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="+1234567890" pattern="^\+?[1-9]\d{6,14}$">
                                <div class="form-text" style="font-size:0.76rem;color:#64748b"><i class="fas fa-info-circle me-1"></i>Required for SMS invoice reminders. Include country code (e.g., +1)</div>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="role" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">User Role <span class="text-danger">*</span></label>
                                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" onchange="toggleMedicalSpecialty(); toggleHospitalField();" required style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                    <option value="">-- Select Role --</option>
                                    <option value="doctor" {{ old('role') == 'doctor' ? 'selected' : '' }}>Doctor</option>
                                    <option value="hospital_admin" {{ old('role') == 'hospital_admin' ? 'selected' : '' }}>Hospital Admin</option>
                                    <option value="patient" {{ old('role') == 'patient' ? 'selected' : '' }}>Patient</option>
                                </select>
                                <div class="mt-2 p-2" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;font-size:0.76rem;color:#475569;line-height:1.5"><strong>Doctor:</strong> Individual practitioner · <strong>Hospital Admin:</strong> Manages doctors in a hospital · <strong>Patient:</strong> Regular patient</div>
                                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div id="hospital-admin-note" class="mt-3" style="display: {{ old('role') == 'hospital_admin' ? 'block' : 'none' }};">
                            <div class="alert d-flex align-items-start gap-2 mb-0" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;font-size:0.84rem"><i class="fas fa-info-circle mt-1"></i><div><strong>Hospital Admin Account:</strong> Hospital admins will manage their own hospital information after creation.</div></div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label for="password" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Password <span class="text-danger">*</span></label>
                                <input id="password" type="password" name="password" required class="form-control @error('password') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="••••••••">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Confirm Password <span class="text-danger">*</span></label>
                                <input id="password_confirmation" type="password" name="password_confirmation" required class="form-control @error('password_confirmation') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="••••••••">
                                @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6" id="date-of-birth-field" style="display: {{ old('role') == 'patient' ? 'block' : 'none' }}">
                                <label for="date_of_birth" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Date of Birth</label>
                                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ date('Y-m-d') }}" class="form-control @error('date_of_birth') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                <small style="font-size:0.72rem;color:#64748b">Required for patient identification</small>
                                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6" id="gender-field" style="display: {{ old('role') == 'patient' ? 'block' : 'none' }}">
                                <label for="gender" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Gender</label>
                                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                    <option value="">-- Select Gender --</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mt-3" id="specialty-field" style="display: {{ old('role')=='doctor' ? 'block' : 'none' }}">
                            <label for="specialty_select" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Medical Specialty <span class="text-danger">*</span></label>
                            <select class="form-select @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialtyAdmin()" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
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
                            <div id="custom_specialty_container_admin" style="display: none;" class="mt-2">
                                <input type="text" name="custom_specialty" id="custom_specialty_admin" class="form-control" placeholder="Please enter your medical specialty" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                            </div>
                            <input type="hidden" name="specialty" id="specialty_admin" value="{{ old('specialty') }}">
                            @error('specialty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <!-- SaaS fields removed — system is not SaaS -->

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid #eef2f7">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-light border" style="border-radius:10px;font-weight:600;padding:0.55rem 1.1rem">Cancel</a>
                            <button type="submit" class="btn text-white" style="background:#0f172a;border:none;border-radius:10px;font-weight:700;padding:0.55rem 1.2rem"><i class="fas fa-user-plus me-1"></i>Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function toggleMedicalSpecialty(){const e=document.getElementById('role'),t=document.getElementById('subscription-settings'),s=document.getElementById('date-of-birth-field'),a=document.getElementById('gender-field'),o=document.getElementById('specialty-field');if(!e)return;const n=e.value;t&&(t.style.display=n==='doctor'?'block':'none'),o&&(o.style.display=n==='doctor'?'block':'none'),n==='patient'?(s&&(s.style.display='block'),a&&(a.style.display='block')):(s&&(s.style.display='none'),a&&(a.style.display='none'))}
function toggleHospitalField(){const e=document.getElementById('role'),t=document.getElementById('hospital-admin-note');if(!e||!t)return;e.value==='hospital_admin'?t.style.display='block':t.style.display='none'}
function toggleCustomSpecialtyAdmin(){const e=document.getElementById('specialty_select'),t=document.getElementById('custom_specialty_container_admin'),s=document.getElementById('custom_specialty_admin'),a=document.getElementById('specialty_admin');if(!e||!t)return;if(e.value==='other'){t.style.display='block',s&&(s.required=!0,s.focus()),a&&(a.value='')}else{t.style.display='none',s&&(s.required=!1,s.value=''),a&&(a.value=e.value)}}
document.addEventListener('DOMContentLoaded',function(){toggleMedicalSpecialty();toggleHospitalField();const e=document.getElementById('role');e&&e.value&&toggleMedicalSpecialty();const t=document.getElementById('custom_specialty_admin'),s=document.getElementById('specialty_admin'),a=document.getElementById('specialty_select');if(a){t&&t.addEventListener('input',function(){a.value==='other'&&s&&(s.value=this.value)}),a.addEventListener('change',function(){toggleCustomSpecialtyAdmin()});const n=document.querySelector('form');n&&n.addEventListener('submit',function(n){if(a.value==='other'&&t){if(!t.value.trim()){n.preventDefault(),t.focus(),t.style.borderColor='#dc3545';return!1}s&&(s.value=t.value.trim())}else s&&(s.value=a.value,t&&(t.value=''))})}const o=document.getElementById('role');o&&o.addEventListener('change',function(){toggleMedicalSpecialty();toggleHospitalField()})});
</script>
@endpush
@endsection
