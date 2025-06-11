@extends('master')

@section('title', 'Edit User')

@push('styles')
<style>
    .admin-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .admin-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }
    
    .form-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
    }
    
    .form-control:focus {
        border-color: #DE6262;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    }
    
    .form-check-input:checked {
        background-color: #DE6262;
        border-color: #DE6262;
    }
    
    .form-check-input:focus {
        border-color: #DE6262;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="admin-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2">Edit User</h1>
                            <p class="mb-0 opacity-75">Update user information for {{ $user->name }}</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Back to Users
                        </a>
                    </div>
                </div>

                <!-- Form -->
                <div class="form-card">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input id="password" type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror">
                            <small class="text-muted">Leave blank to keep current password</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-bold">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   class="form-control @error('password_confirmation') is-invalid @enderror">
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Admin Status -->
                        @if($user->id !== auth()->id())
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('is_admin') is-invalid @enderror" 
                                           type="checkbox" name="is_admin" value="1" id="is_admin" 
                                           {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="is_admin">
                                        Grant admin privileges
                                    </label>
                                </div>
                                <small class="text-muted">Admin users can manage all system users and settings.</small>
                                @error('is_admin')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Note:</strong> You cannot modify your own admin status.
                            </div>
                            <input type="hidden" name="is_admin" value="{{ $user->is_admin ? '1' : '0' }}">
                        @endif

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection