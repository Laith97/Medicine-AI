@extends('master')

@section('title', 'Sub-User Details')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">{{ $subUser->name }}</h1>
                        <p class="text-muted">{{ ucfirst($subUser->sub_user_role) }} • Created {{ $subUser->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('sub-users.edit', $subUser) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash me-2"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Full Name</label>
                                <p class="mb-0">{{ $subUser->name }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small">Email Address</label>
                                <p class="mb-0">
                                    <a href="mailto:{{ $subUser->email }}" class="text-decoration-none">
                                        {{ $subUser->email }}
                                    </a>
                                </p>
                            </div>

                            @if($subUser->phone)
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Phone Number</label>
                                    <p class="mb-0">
                                        <a href="tel:{{ $subUser->phone }}" class="text-decoration-none">
                                            {{ $subUser->phone }}
                                        </a>
                                    </p>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label text-muted small">Role/Position</label>
                                <p class="mb-0">
                                    <span class="badge bg-primary">{{ ucfirst($subUser->sub_user_role) }}</span>
                                </p>
                            </div>

                            <div class="mb-0">
                                <label class="form-label text-muted small">Account Status</label>
                                <p class="mb-0">
                                    <span class="badge bg-success">Active</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2 text-primary"></i>Access Permissions
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($subUser->permissions->count() > 0)
                                @php
                                    $permissionsByCategory = $subUser->permissions->groupBy('category');
                                @endphp
                                
                                @foreach($permissionsByCategory as $category => $permissions)
                                    <div class="mb-3">
                                        <h6 class="text-uppercase text-muted small fw-bold mb-2">
                                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                                        </h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($permissions as $permission)
                                                <span class="badge bg-light text-dark">
                                                    {{ $permission->display_name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-3">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    <p class="text-muted mb-0">No permissions assigned</p>
                                    <small class="text-muted">This user cannot access any features</small>
                                </div>
                            @endif
                        </div>
                        @if($subUser->permissions->count() > 0)
                            <div class="card-footer bg-light">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Total: {{ $subUser->permissions->count() }} permission{{ $subUser->permissions->count() !== 1 ? 's' : '' }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>Account Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="border-end">
                                <h4 class="text-primary mb-1">{{ $subUser->permissions->count() }}</h4>
                                <small class="text-muted">Permissions</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-end">
                                <h4 class="text-success mb-1">Active</h4>
                                <small class="text-muted">Status</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-end">
                                <h4 class="text-info mb-1">{{ $subUser->created_at->diffInDays() }}</h4>
                                <small class="text-muted">Days Active</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h4 class="text-warning mb-1">{{ $subUser->updated_at->diffForHumans() }}</h4>
                            <small class="text-muted">Last Updated</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('sub-users.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Sub-Users
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('sub-users.edit', $subUser) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Sub-User
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong>{{ $subUser->name }}</strong>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. The sub-user will lose access to all assigned permissions and data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('sub-users.destroy', $subUser) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Sub-User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endsection