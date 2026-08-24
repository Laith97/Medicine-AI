@extends('master')

@section('title', 'Login - MedCura Clinical Platform')

@push('styles')
<style>
.auth-page{
    background:#f8fafc;
    min-height:calc(100vh - 52px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2.5rem 1rem;
}
.auth-card{
    background:#ffffff;
    border:1px solid #e2e8f0;
    border-radius:12px;
    box-shadow:0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.04);
    padding:2rem;
    width:100%;
    max-width:420px;
}
.auth-header{margin-bottom:1.75rem}
.auth-eyebrow{
    font-size:0.68rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;
    display:flex;align-items:center;justify-content:center;gap:0.4rem;margin-bottom:0.75rem;
}
.auth-eyebrow i{color:#DE6262}
.auth-title{font-size:1.5rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.35rem}
.auth-subtitle{font-size:0.875rem;color:#64748b;margin:0}
.form-label{font-size:0.84rem;font-weight:500;color:#334155;margin-bottom:0.35rem}
.auth-input{
    border:1px solid #e2e8f0 !important;
    border-radius:8px !important;
    padding:0.6rem 0.85rem !important;
    font-size:0.875rem !important;
    background:#ffffff !important;
    box-shadow:none !important;
    transition:border-color .15s, box-shadow .15s !important;
}
.auth-input:focus{border-color:#DE6262 !important;box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important}
.auth-input::placeholder{color:#94a3b8}
.password-input-wrapper{position:relative}
.password-toggle{
    position:absolute;right:10px;top:50%;transform:translateY(-50%);
    background:none;border:none;color:#94a3b8;cursor:pointer;padding:4px;font-size:1rem;line-height:1
}
.password-toggle:hover{color:#334155}
.auth-btn{
    background:#DE6262;border:1px solid #DE6262;border-radius:8px;
    padding:0.65rem 1rem;font-weight:600;font-size:0.875rem;color:#ffffff;
    box-shadow:none;transition:background .15s, border-color .15s;
    width:100%;
}
.auth-btn:hover{background:#c55050;border-color:#c55050;color:#ffffff}
.auth-link{color:#64748b;text-decoration:none;font-size:0.84rem}
.auth-link:hover{color:#0f172a;text-decoration:underline}
.auth-link-primary{color:#DE6262;text-decoration:none;font-weight:600;font-size:0.875rem}
.auth-link-primary:hover{color:#c55050;text-decoration:underline}
.auth-divider{display:flex;align-items:center;gap:0.75rem;margin:1.25rem 0;color:#94a3b8;font-size:0.78rem}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
.form-check-input{border-radius:4px !important}
.form-check-input:checked{background-color:#DE6262 !important;border-color:#DE6262 !important}
.form-check-input:focus{box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important;border-color:#DE6262 !important}
.form-check-label{font-size:0.84rem;color:#475569}
@media(max-width:576px){.auth-card{padding:1.5rem}}
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header text-center">
            <div class="auth-eyebrow"><i class="bi bi-shield-check"></i> Secure Access</div>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to your MedCura account</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" type="email" name="email" class="form-control auth-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus placeholder="you@clinic.com">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-wrapper">
                    <input id="password" type="password" name="password" class="form-control auth-input @error('password') is-invalid @enderror" required placeholder="Enter your password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" tabindex="-1"><i class="bi bi-eye" id="password-eye"></i></button>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                @endif
            </div>
            <button type="submit" class="auth-btn mb-3">Sign in</button>
            <div class="auth-divider">or</div>
            <div class="text-center">
                <span class="auth-link" style="color:#64748b">New patient?</span>
                <a href="{{ route('patient.register') }}" class="auth-link-primary ms-1">Create account</a>
            </div>
        </form>
        <div class="text-center mt-4 pt-3 border-top" style="border-color:#f1f5f9 !important">
            <small style="color:#94a3b8;font-size:0.78rem">Need help? <a href="{{ route('contact') }}" style="color:#475569;text-decoration:none;font-weight:500">Contact support</a></small>
        </div>
    </div>
</div>
<script>
function togglePassword(id){
    const input=document.getElementById(id),eye=document.getElementById(id+'-eye');
    if(input.type==='password'){input.type='text';eye.className='bi bi-eye-slash';}
    else{input.type='password';eye.className='bi bi-eye';}
}
</script>
@endsection
