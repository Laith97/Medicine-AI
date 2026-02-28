@extends('master')

@section('title', 'Sub-Users Management')

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '👥';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-header">
    <h2>Sub Users</h2>
    <p>Manage sub users</p>
</div>
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Sub-Users Management</h1>
                    <p class="text-muted">Manage your team members and their access permissions</p>
                </div>
                <a href="{{ route('sub-users.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Sub-User
                </a>
            </div>

            <!-- Sub-Users List -->
            @if($subUsers->count() > 0)
                <div class="row">
                    @foreach($subUsers as $subUser)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-circle bg-primary text-white me-3">
                                            {{ strtoupper(substr($subUser->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">{{ $subUser->name }}</h5>
                                            <p class="text-muted small mb-0">{{ ucfirst($subUser->sub_user_role) }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Email:</small>
                                        <p class="mb-1">{{ $subUser->email }}</p>
                                        
                                        @if($subUser->phone)
                                            <small class="text-muted">Phone:</small>
                                            <p class="mb-1">{{ $subUser->phone }}</p>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted">Permissions ({{ $subUser->permissions->count() }}):</small>
                                        <div class="mt-1">
                                            @forelse($subUser->permissions->take(3) as $permission)
                                                <span class="badge bg-light text-dark me-1 mb-1">{{ $permission->display_name }}</span>
                                            @empty
                                                <span class="text-muted small">No permissions assigned</span>
                                            @endforelse
                                            @if($subUser->permissions->count() > 3)
                                                <span class="badge bg-secondary">+{{ $subUser->permissions->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="{{ route('sub-users.show', $subUser) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <a href="{{ route('sub-users.edit', $subUser) }}" class="btn btn-sm btn-outline-secondary flex-fill">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $subUser->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Created {{ $subUser->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-users fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted">No Sub-Users Yet</h4>
                    <p class="text-muted mb-4">Create sub-users to help manage your practice more efficiently.</p>
                    <a href="{{ route('sub-users.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Your First Sub-User
                    </a>
                </div>
            @endif
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
                <p>Are you sure you want to delete this sub-user? This action cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> The sub-user will lose access to all assigned permissions and data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Sub-User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}
</style>

<script>
function confirmDelete(subUserId) {
    const form = document.getElementById('deleteForm');
    form.action = `/sub-users/${subUserId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endsection