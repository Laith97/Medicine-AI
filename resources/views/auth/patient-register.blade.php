@extends('master')

@section('title', 'Create Patient Account')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center mb-4">
                    <h2>Create Your Patient Account</h2>
                    <p class="text-muted">Join our platform to easily manage your appointments and health records</p>
                </div>

                <div class="table-card">
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
            </div>
        </div>
        <!-- Benefits Section -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Benefits of Creating an Account</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-center">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    Easy appointment booking and management
                </li>
                <li class="flex items-center">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    Access to your appointment history
                </li>
                <li class="flex items-center">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    Personalized doctor recommendations
                </li>
                <li class="flex items-center">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    Secure storage of your health information
                </li>
                <li class="flex items-center">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    Email reminders for upcoming appointments
                </li>
            </ul>
        </div>
        <!-- Guest Option -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't want to create an account?
                <a href="{{ route('doctors.index') }}" class="text-primary-600 hover:text-primary-500">
                    Continue as guest
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
