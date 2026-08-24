@extends('master')

@section('title', 'Create Account - MedCura')

@push('styles')
<style>
.auth-page{background:#f8fafc;min-height:calc(100vh - 52px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
.auth-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05),0 4px 12px rgba(0,0,0,0.04);padding:2rem;width:100%;max-width:580px}
.auth-eyebrow{font-size:0.68rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:0.4rem;margin-bottom:0.75rem}
.auth-eyebrow i{color:#DE6262}
.auth-title{font-size:1.45rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.35rem;text-align:center}
.auth-subtitle{font-size:0.875rem;color:#64748b;margin:0 0 1.5rem;text-align:center}
.role-toggle{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:4px;display:flex;gap:4px;margin-bottom:1.5rem}
.role-btn{flex:1;border:1px solid transparent;background:transparent;border-radius:8px;padding:0.6rem 1rem;font-weight:600;font-size:0.875rem;color:#64748b;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:0.5rem}
.role-btn.active{background:#ffffff;border-color:#e2e8f0;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,0.08)}
.role-btn.active.doctor{color:#DE6262}
.role-btn.active.patient{color:#2563eb}
.form-label{font-size:0.84rem;font-weight:500;color:#334155;margin-bottom:0.35rem}
.auth-input{border:1px solid #e2e8f0 !important;border-radius:8px !important;padding:0.6rem 0.85rem !important;font-size:0.875rem !important;background:#ffffff !important}
.auth-input:focus{border-color:#DE6262 !important;box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important}
.auth-input.patient-focus:focus{border-color:#2563eb !important;box-shadow:0 0 0 3px rgba(37,99,235,0.12) !important}
.auth-btn{border-radius:8px;padding:0.65rem 1rem;font-weight:600;font-size:0.875rem;width:100%;transition:background .15s}
.auth-btn.doctor{background:#DE6262;border:1px solid #DE6262;color:#ffffff}
.auth-btn.doctor:hover{background:#c55050;border-color:#c55050}
.auth-btn.patient{background:#2563eb;border:1px solid #2563eb;color:#ffffff}
.auth-btn.patient:hover{background:#1d4ed8;border-color:#1d4ed8}
.password-input-wrapper{position:relative}
.password-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:4px}
.strength-bars{display:flex;gap:4px;margin-top:0.5rem}
.strength-bar{height:4px;flex:1;background:#e2e8f0;border-radius:2px}
.strength-bar.weak{background:#ef4444}.strength-bar.fair{background:#f59e0b}.strength-bar.good{background:#10b981}.strength-bar.strong{background:#059669}
.form-section{display:none}
.form-section.active{display:block}
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-eyebrow"><i class="fas fa-shield-halved"></i> HIPAA Compliant · Secure</div>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">Choose your role to get started</p>

        <div class="role-toggle" role="tablist">
            <button type="button" class="role-btn doctor active" data-role="doctor" onclick="switchRole('doctor')"><i class="fas fa-user-doctor"></i> Healthcare Professional</button>
            <button type="button" class="role-btn patient" data-role="patient" onclick="switchRole('patient')"><i class="fas fa-user-injured"></i> Patient</button>
        </div>

        <!-- Doctor Form -->
        <div id="doctor-form" class="form-section active">
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="name" class="form-control auth-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Dr. Jane Smith">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control auth-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="you@clinic.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone <span style="color:#ef4444">*</span></label>
                    <input type="tel" name="phone" class="form-control auth-input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required placeholder="+1 234 567 890" pattern="^\+?[1-9]\d{1,14}$">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="doctor_password" class="form-control auth-input @error('password') is-invalid @enderror" required placeholder="Create a strong password" onkeyup="checkStrength(this.value,'doctor')">
                        <button type="button" class="password-toggle" onclick="togglePw('doctor_password')"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="strength-bars"><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div></div>
                    <small id="doctor-strength" style="font-size:0.72rem;color:#94a3b8">Enter a password</small>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control auth-input" required placeholder="Repeat password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Medical specialty <span style="color:#ef4444">*</span></label>
                    <select class="form-control auth-input" name="specialty_select" id="specialty_select" onchange="toggleCustom()">
                        <option value="">Select specialty</option>
                        <option value="General Practitioner">General Practitioner</option>
                        <option value="Internal Medicine">Internal Medicine</option>
                        <option value="Cardiology">Cardiology</option>
                        <option value="Pediatrics">Pediatrics</option>
                        <option value="General Surgery">General Surgery</option>
                        <option value="other">Other (specify)</option>
                    </select>
                    <div id="custom_specialty_container" style="display:none" class="mt-2">
                        <input type="text" name="custom_specialty" id="custom_specialty" class="form-control auth-input" placeholder="Enter specialty">
                    </div>
                    <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty') }}">
                    @error('specialty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <input type="hidden" name="selected_plan" value="free">
                <input type="hidden" name="selected_billing" value="monthly">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="terms_doctor" required>
                    <label class="form-check-label" for="terms_doctor" style="font-size:0.84rem;color:#475569">I agree to <a href="#" style="color:#DE6262">Terms</a> and <a href="#" style="color:#DE6262">Privacy</a></label>
                </div>
                <button type="submit" class="auth-btn doctor">Create professional account</button>
            </form>
        </div>

        <!-- Patient Form -->
        <div id="patient-form" class="form-section">
            <form method="POST" action="{{ route('patient.register.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="name" class="form-control auth-input patient-focus @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Jane Doe">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control auth-input patient-focus @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="you@example.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone number</label>
                    <input type="tel" name="phone" class="form-control auth-input patient-focus" value="{{ old('phone') }}" required placeholder="+1 234 567 890">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Date of birth</label>
                            <input type="date" name="date_of_birth" class="form-control auth-input patient-focus @error('date_of_birth') is-invalid @enderror" max="{{ date('Y-m-d') }}" value="{{ old('date_of_birth') }}" required>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select auth-input patient-focus @error('gender') is-invalid @enderror" required>
                                <option value="">Select</option>
                                <option value="male" {{ old('gender')=='male'?'selected':'' }}>Male</option>
                                <option value="female" {{ old('gender')=='female'?'selected':'' }}>Female</option>
                                <option value="other" {{ old('gender')=='other'?'selected':'' }}>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="patient_password" class="form-control auth-input patient-focus @error('password') is-invalid @enderror" required placeholder="Create password" onkeyup="checkStrength(this.value,'patient')">
                    <div class="strength-bars"><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div></div>
                    <small id="patient-strength" style="font-size:0.72rem;color:#94a3b8">Enter a password</small>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control auth-input patient-focus" required placeholder="Repeat password">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="terms" id="terms_patient" class="form-check-input" required>
                    <label for="terms_patient" class="form-check-label" style="font-size:0.84rem;color:#475569">I agree to <a href="#" style="color:#2563eb">Terms</a> and <a href="#" style="color:#2563eb">Privacy</a></label>
                </div>
                <button type="submit" class="auth-btn patient">Create patient account</button>
            </form>
        </div>

        <div class="text-center mt-4 pt-3 border-top" style="border-color:#f1f5f9 !important">
            <span style="font-size:0.84rem;color:#64748b">Already have an account?</span> <a href="{{ route('login') }}" style="color:#DE6262;font-weight:600;text-decoration:none">Sign in</a>
        </div>
    </div>
</div>
<script>
function switchRole(role){
    document.querySelectorAll('.role-btn').forEach(b=>b.classList.remove('active'));
    document.querySelector('.role-btn[data-role="'+role+'"]').classList.add('active');
    document.querySelectorAll('.form-section').forEach(s=>s.classList.remove('active'));
    document.getElementById(role+'-form').classList.add('active');
    history.replaceState(null,'','/register?role='+role);
}
(function(){
    const params=new URLSearchParams(window.location.search);
    const role=params.get('role');
    if(role==='patient') switchRole('patient');
    const oldRole="{{ old('gender') ? 'patient' : (old('specialty') ? 'doctor' : '') }}";
    if(oldRole) switchRole(oldRole);
})();
function togglePw(id){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password'}
function checkStrength(p,role){
    const container=document.getElementById(role+'-form');
    const bars=container.querySelectorAll('.strength-bar');
    const text=document.getElementById(role+'-strength');
    let s=0;if(p.length>=8)s++;if(p.match(/[a-z]/)&&p.match(/[A-Z]/))s++;if(p.match(/[0-9]/))s++;if(p.match(/[^a-zA-Z0-9]/))s++;
    const labels=['Enter a password','Weak','Fair','Good','Strong'],cls=['','weak','fair','good','strong'];
    bars.forEach((b,i)=>{b.className='strength-bar';if(i<s)b.classList.add(cls[s])});
    text.textContent=labels[s];text.className=cls[s];
}
function toggleCustom(){
    const s=document.getElementById('specialty_select'),c=document.getElementById('custom_specialty_container'),i=document.getElementById('custom_specialty'),h=document.getElementById('specialty');
    if(s.value==='other'){c.style.display='block';h.value=''}else{c.style.display='none';h.value=s.value}
}
document.getElementById('custom_specialty')?.addEventListener('input',function(){document.getElementById('specialty').value=this.value});
</script>
@endsection
