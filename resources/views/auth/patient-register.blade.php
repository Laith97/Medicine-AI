@extends('master')

@section('title', 'Create Patient Account')

@push('styles')
<style>
.auth-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    position: relative;
    overflow: hidden;
}

.auth-info-section {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    position: relative;
}

.auth-info-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.05"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></svg>');
    opacity: 0.3;
}

.auth-info-content {
    position: relative;
    z-index: 2;
    max-width: 500px;
    text-align: center;
}

.auth-form-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    min-height: 100vh;
}

.auth-form-container {
    width: 100%;
    max-width: 450px;
}

.auth-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 2;
}

/* Responsive adjustments for auth layout */
@media (max-width: 991px) {
    .auth-form-section {
        padding: 1rem;
    }

    .auth-card {
        padding: 1.5rem;
    }

    .auth-info-content .display-5 {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .auth-card {
        padding: 1rem;
        margin: 0.5rem;
    }

    .auth-form-container {
        max-width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left side - Information -->
            <div class="col-lg-6 auth-info-section d-none d-lg-flex">
                <div class="auth-info-content">
                    <i class="bi bi-heart-pulse display-1 text-primary mb-4"></i>
                    <h1 class="display-5 fw-bold text-white mb-3">Join Our Patient Portal</h1>
                    <p class="lead text-white-50 mb-4">Take control of your healthcare journey with easy appointment booking and secure health record management.</p>
                    <div class="auth-features">
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Easy Appointment Booking</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Secure Health Records</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Appointment History</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Email Reminders</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Form -->
            <div class="col-lg-6 col-12 auth-form-section">
                <div class="auth-form-container">
                    <!-- Compact header for mobile -->
                    <div class="text-center mb-4 d-lg-none">
                        <i class="bi bi-heart-pulse text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h2 class="h5 text-muted">Join Our Patient Portal</h2>
                    </div>

                    <!-- Main form card -->
                    <div class="auth-card">
                    <form method="POST" action="{{ route('patient.register.store') }}">
                        @csrf

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input id="name" name="name" type="text" required
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Enter your full name" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" name="email" type="email" required
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="Enter your email address" value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input id="phone" name="phone" type="tel" required
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="Enter your phone number" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Date of Birth -->
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" required
                                   class="form-control @error('date_of_birth') is-invalid @enderror"
                                   max="{{ date('Y-m-d') }}" value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Gender -->
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select id="gender" name="gender" required class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Select gender</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" name="password" type="password" required
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Create a secure password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                   class="form-control" placeholder="Confirm your password">
                        </div>

                        <!-- Terms and Privacy -->
                        <div class="mb-3 form-check">
                            <input id="terms" name="terms" type="checkbox" required class="form-check-input">
                            <label for="terms" class="form-check-label">
                                I agree to the <a href="#" class="text-primary">Terms of Service</a>
                                and <a href="#" class="text-primary">Privacy Policy</a>
                            </label>
                            @error('terms')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Account
                            </button>
                        </div>
                        </form>

                        <div class="mt-4 text-center">
                            <hr class="my-3">
                            <p class="text-muted mb-2">Already have an account?</p>
                            <a href="{{ route('login') }}" class="btn btn-outline-primary">
                                Sign in to your account
                            </a>
                        </div>
                    </div>

                    <!-- Footer links -->
                    <div class="text-center mt-4">
                        <small class="text-muted">Need help? <a href="{{ route('contact') }}" class="text-decoration-none">Contact Support</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
