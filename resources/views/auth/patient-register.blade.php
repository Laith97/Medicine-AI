@extends('master')

@section('title', 'Create Patient Account')

@push('styles')
<style>
.auth-page{background:#f8fafc;min-height:calc(100vh - 52px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
.auth-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05),0 4px 12px rgba(0,0,0,0.04);padding:2rem;width:100%;max-width:480px}
.auth-eyebrow{font-size:0.68rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:0.4rem;margin-bottom:0.75rem}
.auth-eyebrow i{color:#3b82f6}
.auth-title{font-size:1.45rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.35rem;text-align:center}
.auth-subtitle{font-size:0.875rem;color:#64748b;margin:0 0 1.5rem;text-align:center}
.form-label{font-size:0.84rem;font-weight:500;color:#334155;margin-bottom:0.35rem}
.auth-input{border:1px solid #e2e8f0 !important;border-radius:8px !important;padding:0.6rem 0.85rem !important;font-size:0.875rem !important;background:#ffffff !important}
.auth-input:focus{border-color:#3b82f6 !important;box-shadow:0 0 0 3px rgba(59,130,246,0.12) !important}
.auth-btn{background:#2563eb;border:1px solid #2563eb;border-radius:8px;padding:0.65rem 1rem;font-weight:600;font-size:0.875rem;color:#ffffff;width:100%}
.auth-btn:hover{background:#1d4ed8;border-color:#1d4ed8;color:#ffffff}
.password-strength-meter{margin-top:0.5rem}
.strength-bars{display:flex;gap:4px;margin-bottom:0.25rem}
.strength-bar{height:4px;flex:1;background:#e2e8f0;border-radius:2px}
.strength-bar.weak{background:#ef4444}.strength-bar.fair{background:#f59e0b}.strength-bar.good{background:#10b981}.strength-bar.strong{background:#059669}
.strength-text small{font-size:0.72rem;color:#94a3b8}
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-eyebrow"><i class="fas fa-user-injured"></i> Patient Portal</div>
        <h1 class="auth-title">Create patient account</h1>
        <p class="auth-subtitle">Book appointments and manage your health</p>

        <form method="POST" action="{{ route('patient.register.store') }}" class="mt-4">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <input id="name" name="name" type="text" required class="form-control auth-input @error('name') is-invalid @enderror" placeholder="Jane Doe" value="{{ old('name') }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" name="email" type="email" required class="form-control auth-input @error('email') is-invalid @enderror" placeholder="you@example.com" value="{{ old('email') }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone number</label>
                <input id="phone" name="phone" type="tel" required class="form-control auth-input @error('phone') is-invalid @enderror" placeholder="+1 234 567 890" value="{{ old('phone') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Date of birth</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" required class="form-control auth-input @error('date_of_birth') is-invalid @enderror" max="{{ date('Y-m-d') }}" value="{{ old('date_of_birth') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" required class="form-select auth-input @error('gender') is-invalid @enderror">
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
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" required class="form-control auth-input @error('password') is-invalid @enderror" placeholder="Create password" onkeyup="checkPasswordStrength(this.value)">
                <div class="password-strength-meter">
                    <div class="strength-bars"><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div></div>
                    <div class="strength-text"><small id="password-strength-text">Enter a password</small></div>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="form-control auth-input" placeholder="Repeat password">
            </div>
            <div class="form-check mb-4">
                <input id="terms" name="terms" type="checkbox" required class="form-check-input">
                <label for="terms" class="form-check-label" style="font-size:0.84rem;color:#475569">I agree to the <a href="#" style="color:#2563eb;text-decoration:none">Terms</a> and <a href="#" style="color:#2563eb;text-decoration:none">Privacy Policy</a></label>
                @error('terms')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="auth-btn">Create account</button>
            <div class="text-center mt-4 pt-3 border-top" style="border-color:#f1f5f9 !important">
                <span style="font-size:0.84rem;color:#64748b">Already have an account?</span> <a href="{{ route('login') }}" style="color:#2563eb;font-weight:600;text-decoration:none;font-size:0.84rem">Sign in</a>
            </div>
        </form>
    </div>
</div>
<script>
function checkPasswordStrength(p){const b=document.querySelectorAll('.strength-bar'),t=document.getElementById('password-strength-text');let s=0;if(p.length>=8)s++;if(p.match(/[a-z]/)&&p.match(/[A-Z]/))s++;if(p.match(/[0-9]/))s++;if(p.match(/[^a-zA-Z0-9]/))s++;const l=['Enter a password','Weak','Fair','Good','Strong'],c=['','weak','fair','good','strong'];b.forEach((e,i)=>{e.className='strength-bar';if(i<s)e.classList.add(c[s])});t.className=c[s];t.textContent=l[s]}
</script>
@endsection
