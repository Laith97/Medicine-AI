@extends('master')

@section('title', 'Create Sub-User')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sub-users.index') }}">Sub-Users</a></li>
                        <li class="breadcrumb-item active">Create Sub-User</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-1">Create New Sub-User</h1>
                <p class="text-muted">Add a new team member and configure their access permissions</p>
            </div>

            <!-- Form Card -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('sub-users.store') }}">
                        @csrf

                        <!-- Basic Information -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-user me-2 text-primary"></i>Basic Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sub_user_role" class="form-label">Role/Position <span class="text-danger">*</span></label>
                                    <select class="form-select @error('sub_user_role') is-invalid @enderror" 
                                            id="sub_user_role" name="sub_user_role" required>
                                        <option value="">Select Role</option>
                                        <option value="secretary" {{ old('sub_user_role') == 'secretary' ? 'selected' : '' }}>Secretary</option>
                                        <option value="assistant" {{ old('sub_user_role') == 'assistant' ? 'selected' : '' }}>Assistant</option>
                                        <option value="nurse" {{ old('sub_user_role') == 'nurse' ? 'selected' : '' }}>Nurse</option>
                                        <option value="receptionist" {{ old('sub_user_role') == 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                                        <option value="coordinator" {{ old('sub_user_role') == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                        <option value="other" {{ old('sub_user_role') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('sub_user_role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-lock me-2 text-primary"></i>Login Credentials
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" 
                                           id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions Section -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-shield-alt me-2 text-primary"></i>Access Permissions
                            </h5>
                            <p class="text-muted small mb-3">
                                Select which features and pages this sub-user can access. 
                                <strong>Note:</strong> AI Assistant, Diagnoses, and Voice Assistant are restricted to main users only.
                            </p>

                            @if($availablePermissions->count() > 0)
                                @foreach($availablePermissions as $category => $permissions)
                                    <div class="mb-4">
                                        <h6 class="text-uppercase text-muted small fw-bold mb-2">
                                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                                        </h6>
                                        <div class="row">
                                            @foreach($permissions as $permission)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="permissions[]" value="{{ $permission->id }}" 
                                                               id="permission_{{ $permission->id }}"
                                                               {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ $permission->display_name }}
                                                            @if($permission->description)
                                                                <small class="text-muted d-block">{{ $permission->description }}</small>
                                                            @endif
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Quick Select Options -->
                                <div class="mb-3">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPermissions()">
                                            <i class="fas fa-check-double me-1"></i>Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllPermissions()">
                                            <i class="fas fa-times me-1"></i>Clear All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="selectCorePermissions()">
                                            <i class="fas fa-star me-1"></i>Core Only
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No permissions available for assignment.
                                </div>
                            @endif
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('sub-users.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Create Sub-User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function clearAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function selectCorePermissions() {
    clearAllPermissions();
    // Select core permissions (dashboard, settings, cases)
    const corePermissions = ['dashboard', 'settings', 'cases'];
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        const label = checkbox.nextElementSibling.textContent.toLowerCase();
        if (corePermissions.some(core => label.includes(core))) {
            checkbox.checked = true;
        }
    });
}
</script>
@endsection