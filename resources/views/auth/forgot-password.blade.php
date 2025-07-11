@extends('master')

@section('title', 'Reset Password - AI Medical Diagnosis')

@section('content')
<div class="auth-wrapper hero-section d-flex align-items-center" style="background: linear-gradient(135deg, #fbfdff00 0%, #34495e 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="auth-card">
                    <!-- Header -->
                    <div class="auth-header text-center mb-4">
                        <div class="auth-logo mb-3">
                            <i class="bi bi-shield-lock" style="font-size: 3rem; color: #DE6262;"></i>
                        </div>
                        <h2 class="auth-title">Reset Password</h2>
                        <p class="auth-subtitle">Enter your email to receive a password reset link</p>
                    </div>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="alert alert-success mb-4">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Reset Password Form -->
                    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                        @csrf

                        <!-- Email Field -->
                        <div class="form-group mb-4">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-2"></i>Email Address
                            </label>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                class="form-control auth-input @error('email') is-invalid @enderror" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus
                                placeholder="Enter your email"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn auth-btn w-100 mb-3">
                            <i class="bi bi-envelope-paper me-2"></i>
                            Send Reset Link
                        </button>

                        <!-- Back to Login -->
                        <div class="text-center mt-4">
                            <a href="{{ route('login') }}" class="auth-link">
                                <i class="bi bi-arrow-left me-1"></i> Back to Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-wrapper {
    position: relative;
    overflow: hidden;
}

.auth-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></svg>');
    opacity: 0.3;
}

.auth-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
}

.auth-title {
    color: #fff;
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: #6c757d;
    font-size: 1rem;
    margin-bottom: 0;
}

.form-label {
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.auth-input {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
}

.auth-input:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    background: white;
}

.auth-btn {
    background: linear-gradient(135deg, #DE6262 0%, #FFB88C 100%);
    border: none;
    border-radius: 12px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
}

.auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
    background: linear-gradient(135deg, #c44d4d 0%, #e6a373 100%);
}

.auth-link {
    color: #DE6262;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.auth-link:hover {
    color: #c44d4d;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .auth-card {
        padding: 2rem;
        margin: 1rem;
    }
    
    .auth-title {
        font-size: 1.75rem;
    }
}
</style>
@endsection
