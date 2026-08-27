@extends('layouts.admin')
@section('title','Edit User')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-user-edit" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Edit User</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Update information for {{ $user->name }} · {{ ucfirst($user->role ?? 'user') }}</p>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i>Back to Users</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-id-card" style="color:#64748b"></i> User Information</h5>
                </div>
                <div class="card-body p-4" style="background:#fff">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Name <span class="text-danger">*</span></label>
                                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus class="form-control @error('name') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control @error('email') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="phone" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Phone Number <span class="text-danger">*</span></label>
                                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required class="form-control @error('phone') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="+1234567890" pattern="^\+?[1-9]\d{6,14}$">
                                <div class="form-text" style="font-size:0.76rem;color:#64748b"><i class="fas fa-info-circle me-1"></i>Required for SMS invoice reminders. Include country code.</div>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Password</label>
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="Leave blank to keep current">
                                <small style="font-size:0.72rem;color:#64748b">Leave blank to keep current password</small>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Confirm Password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="Confirm new password">
                                @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Date of Birth</label>
                                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}" class="form-control @error('date_of_birth') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Gender</label>
                                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                    <option value="">-- Select Gender --</option>
                                    <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="specialty_select" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Medical Specialty</label>
                            @php $currentSpecialty = old('specialty', $user->setting->specialty ?? ''); @endphp
                            <select class="form-select @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialtyAdminEdit()" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
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
                            <div id="custom_specialty_container_admin_edit" style="display: none;" class="mt-2">
                                <input type="text" name="custom_specialty" id="custom_specialty_admin_edit" class="form-control" placeholder="Please enter your medical specialty" value="{{ $currentSpecialty }}" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                            </div>
                            <input type="hidden" name="specialty" id="specialty_admin_edit" value="{{ $currentSpecialty }}">
                            @error('specialty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <!-- SaaS fields removed — system is not SaaS -->

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid #eef2f7">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-light border" style="border-radius:10px;font-weight:600;padding:0.55rem 1.1rem">Cancel</a>
                            <button type="submit" class="btn text-white" style="background:#0f172a;border:none;border-radius:10px;font-weight:700;padding:0.55rem 1.2rem"><i class="fas fa-check me-1"></i>Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function toggleSubscriptionPricing(){const e=document.getElementById('subscription-pricing');if(!e)return;const t='{{ $user->role }}';e.style.display=t==='doctor'?'block':'none'}
function toggleCustomSpecialtyAdminEdit(){const e=document.getElementById('specialty_select'),t=document.getElementById('custom_specialty_container_admin_edit'),s=document.getElementById('custom_specialty_admin_edit'),a=document.getElementById('specialty_admin_edit');if(!e||!t)return;if(e.value==='other'){t.style.display='block',s&&(s.required=!0,s.focus()),a&&(a.value='')}else{t.style.display='none',s&&(s.required=!1,s.value=''),a&&(a.value=e.value)}}
document.addEventListener('DOMContentLoaded',function(){toggleSubscriptionPricing();const e=document.getElementById('custom_specialty_admin_edit'),t=document.getElementById('specialty_admin_edit'),s=document.getElementById('specialty_select');if(s){e&&e.addEventListener('input',function(){s.value==='other'&&t&&(t.value=this.value)});const a=document.querySelector('form');a&&a.addEventListener('submit',function(a){if(s.value==='other'&&e){if(!e.value.trim()){a.preventDefault(),e.focus(),e.style.borderColor='#dc3545';return!1}t&&(t.value=e.value.trim())}else t&&(t.value=s.value,e&&(e.value=''))});const n='{{ $user->setting->specialty ?? "" }}';if(n){const o=Array.from(s.options),r=o.some(e=>e.value===n);r?s.value=n:(s.value='other',toggleCustomSpecialtyAdminEdit(),e&&(e.value=n)),t&&(t.value=n)}}});
</script>
@endpush
@endsection
