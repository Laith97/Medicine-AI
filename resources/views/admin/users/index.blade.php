@extends('master')

@section('title', 'Manage Users')

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
    
    .users-table-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        overflow: hidden;
    }
    
    .table-responsive {
        border-radius: 15px;
    }
    
    .custom-table {
        margin-bottom: 0;
    }
    
    .custom-table thead {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
    }
    
    .custom-table thead th {
        border: none;
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .custom-table tbody tr {
        transition: background-color 0.3s ease;
    }
    
    .custom-table tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }
    
    .custom-table tbody td {
        padding: 1rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: #DE6262;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }
    
    .action-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 20px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .action-btn:hover {
        text-decoration: none;
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">Manage Users</h1>
                    <p class="mb-0 opacity-75">View and manage all system users</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-success">
                        <i class="bi bi-person-plus me-2"></i>Create New User
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Users Table -->
        <div class="users-table-card">
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Cases</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $user->name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $user->email }}</span>
                                </td>
                                <td>
                                    @if($user->isAdmin())
                                        <span class="badge bg-success">
                                            <i class="bi bi-shield-check me-1"></i>Admin
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-person me-1"></i>User
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">{{ $user->created_at->format('M d, Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $user->patientAnalyses->count() }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('admin.users.show', $user) }}" class="action-btn btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="action-btn btn btn-outline-warning btn-sm">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn btn btn-outline-info btn-sm" title="{{ $user->isAdmin() ? 'Remove Admin' : 'Make Admin' }}">
                                                    <i class="bi bi-{{ $user->isAdmin() ? 'shield-x' : 'shield-check' }}"></i>
                                                </button>
                                            </form>
                                            
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge bg-light text-dark">Current User</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-people display-4 text-muted"></i>
                                    <p class="text-muted mt-2">No users found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection