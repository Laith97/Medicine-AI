@extends('master')

@section('title', 'Register - MedCura Clinical Platform')

@push('styles')
<style>
.auth-page{background:#f8fafc;min-height:calc(100vh - 52px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
.auth-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05),0 4px 12px rgba(0,0,0,0.04);padding:2rem;width:100%;max-width:540px}
.auth-eyebrow{font-size:0.68rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:0.4rem;margin-bottom:0.75rem}
.auth-eyebrow i{color:#DE6262}
.auth-title{font-size:1.45rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.35rem;text-align:center}
.auth-subtitle{font-size:0.875rem;color:#64748b;margin:0 0 1.5rem;text-align:center}
.form-label{font-size:0.84rem;font-weight:500;color:#334155;margin-bottom:0.35rem}
.auth-input{border:1px solid #e2e8f0 !important;border-radius:8px !important;padding:0.6rem 0.85rem !important;font-size:0.875rem !important;background:#ffffff !important;box-shadow:none !important}
.auth-input:focus{border-color:#DE6262 !important;box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important}
.password-input-wrapper{position:relative}
.password-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:4px;font-size:1rem}
.password-toggle:hover{color:#334155}
.auth-btn{background:#DE6262;border:1px solid #DE6262;border-radius:8px;padding:0.65rem 1rem;font-weight:600;font-size:0.875rem;color:#ffffff;width:100%;transition:background .15s}
.auth-btn:hover{background:#c55050;border-color:#c55050;color:#ffffff}
.auth-link-primary{color:#DE6262;text-decoration:none;font-weight:600;font-size:0.875rem}
.auth-link-primary:hover{color:#c55050;text-decoration:underline}
.form-check-input:checked{background-color:#DE6262 !important;border-color:#DE6262 !important}
.form-check-input:focus{box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important}
.password-strength-meter{margin-top:0.5rem}
.strength-bars{display:flex;gap:4px;margin-bottom:0.25rem}
.strength-bar{height:4px;flex:1;background:#e2e8f0;border-radius:2px;transition:background .2s}
.strength-bar.weak{background:#ef4444}.strength-bar.fair{background:#f59e0b}.strength-bar.good{background:#10b981}.strength-bar.strong{background:#059669}
.strength-text small{font-size:0.72rem;color:#94a3b8}
.strength-text small.weak{color:#ef4444}.strength-text small.fair{color:#f59e0b}.strength-text small.good{color:#10b981}.strength-text small.strong{color:#059669}
select.auth-input{height:38px}
@media(max-width:576px){.auth-card{padding:1.5rem}}
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-eyebrow"><i class="bi bi-heart-pulse"></i> Healthcare Professional</div>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">Join MedCura to access clinical tools</p>

        <form method="POST" action="{{ route('register') }}" class="mt-4">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <input id="name" type="text" name="name" class="form-control auth-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus placeholder="Dr. Jane Smith">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" type="email" name="email" class="form-control auth-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="you@clinic.com">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone number <span style="color:#ef4444">*</span></label>
                <input id="phone" type="tel" name="phone" class="form-control auth-input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required placeholder="+1 234 567 890" pattern="^\+?[1-9]\d{1,14}$">
                <small style="font-size:0.72rem;color:#94a3b8">Include country code for SMS reminders</small>
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-wrapper">
                    <input id="password" type="password" name="password" class="form-control auth-input @error('password') is-invalid @enderror" required placeholder="Create a strong password" onkeyup="checkPasswordStrength(this.value)">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" tabindex="-1"><i class="bi bi-eye" id="password-eye"></i></button>
                </div>
                <div class="password-strength-meter">
                    <div class="strength-bars"><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div></div>
                    <div class="strength-text"><small id="password-strength-text">Enter a password</small></div>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <div class="password-input-wrapper">
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control auth-input" required placeholder="Repeat password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')" tabindex="-1"><i class="bi bi-eye" id="password_confirmation-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label for="specialty_select" class="form-label">Medical specialty <span style="color:#ef4444">*</span></label>
                <select class="form-control auth-input" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialty()">
                    <option value="">Select your specialty</option>
                    <optgroup label="General & Internal Medicine">
                        <option value="General Practitioner">General Practitioner / Family Medicine</option>
                        <option value="Internal Medicine">Internal Medicine</option>
                    </optgroup>
                    <optgroup label="Cardiology & Subspecialties">
                        <option value="Cardiology">Cardiology</option>
                        <option value="Pulmonology">Pulmonology</option>
                        <option value="Gastroenterology">Gastroenterology</option>
                        <option value="Nephrology">Nephrology</option>
                        <option value="Endocrinology">Endocrinology</option>
                        <option value="Dermatology">Dermatology</option>
                    </optgroup>
                    <optgroup label="Surgery">
                        <option value="General Surgery">General Surgery</option>
                        <option value="Orthopedic Surgery">Orthopedic Surgery</option>
                        <option value="Urology">Urology</option>
                        <option value="ENT">ENT / Otolaryngology</option>
                        <option value="Ophthalmic Surgery">Ophthalmic Surgery</option>
                    </optgroup>
                    <optgroup label="Pediatrics & Women's Health">
                        <option value="Pediatrics">Pediatrics</option>
                        <option value="Obstetrics & Gynecology">Obstetrics & Gynecology</option>
                    </optgroup>
                    <optgroup label="Other">
                        <option value="other">Other (specify)</option>
                    </optgroup>
                </select>
                <div id="custom_specialty_container" style="display:none" class="mt-2">
                    <input type="text" name="custom_specialty" id="custom_specialty" class="form-control auth-input" placeholder="Enter specialty" value="{{ old('custom_specialty') }}">
                </div>
                <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty') }}">
                @error('specialty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <input type="hidden" name="selected_plan" value="free">
            <input type="hidden" name="selected_billing" value="monthly">
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms" style="font-size:0.84rem;color:#475569">I agree to the <a href="#" style="color:#DE6262;text-decoration:none">Terms</a> and <a href="#" style="color:#DE6262;text-decoration:none">Privacy Policy</a></label>
            </div>
            <button type="submit" class="auth-btn">Create account</button>
            <div class="text-center mt-4 pt-3 border-top" style="border-color:#f1f5f9 !important">
                <span style="font-size:0.84rem;color:#64748b">Already have an account?</span> <a href="{{ route('login') }}" class="auth-link-primary">Sign in</a>
            </div>
        </form>
    </div>
</div>
<script>
function togglePassword(id){const i=document.getElementById(id),e=document.getElementById(id+'-eye');if(i.type==='password'){i.type='text';e.className='bi bi-eye-slash'}else{i.type='password';e.className='bi bi-eye'}}
function checkPasswordStrength(p){const b=document.querySelectorAll('.strength-bar'),t=document.getElementById('password-strength-text');let s=0;if(p.length>=8)s++;if(p.match(/[a-z]/)&&p.match(/[A-Z]/))s++;if(p.match(/[0-9]/))s++;if(p.match(/[^a-zA-Z0-9]/))s++;const l=['Enter a password','Weak','Fair','Good','Strong'],c=['','weak','fair','good','strong'];b.forEach((e,i)=>{e.className='strength-bar';if(i<s)e.classList.add(c[s])});t.className=c[s];t.textContent=l[s]}
function toggleCustomSpecialty(){const s=document.getElementById('specialty_select'),c=document.getElementById('custom_specialty_container'),i=document.getElementById('custom_specialty'),h=document.getElementById('specialty');if(s.value==='other'){c.style.display='block';i.required=true;h.value=''}else{c.style.display='none';i.required=false;h.value=s.value}}
document.addEventListener('DOMContentLoaded',function(){
    const s=document.getElementById('specialty_select'),c=document.getElementById('custom_specialty'),h=document.getElementById('specialty');
    if(c) c.addEventListener('input',()=>{if(s.value==='other')h.value=c.value});
    const f=document.querySelector('form'); if(f) f.addEventListener('submit',e=>{
        if(s.value==='other'&&!c.value.trim()){e.preventDefault();c.focus();c.classList.add('is-invalid')}
        else h.value=s.value==='other'?c.value.trim():s.value;
    });
});
</script>
@endsection
