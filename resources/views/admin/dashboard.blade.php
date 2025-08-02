@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@push('styles')
<style>
    /* Page-specific styles if needed */
        margin: 0;
        font-size: 0.9rem;
    }

    .action-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        transition: transform 0.3s ease;
    }

    .action-card:hover {
        transform: translateY(-2px);
    }

    .action-link {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 0.5rem;
    }

    .action-link:hover {
        text-decoration: none;
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
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Admin Dashboard</h1>
                    <p class="mb-0">Manage users and system settings</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <i class="bi bi-people"></i>
                <h3>{{ $stats['total_users'] }}</h3>
                <p>Total Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-shield-check"></i>
                <h3>{{ $stats['admin_users'] }}</h3>
                <p>Admin Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-person"></i>
                <h3>{{ $stats['regular_users'] }}</h3>
                <p>Regular Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="bi bi-clock"></i>
                <h3>{{ $stats['recent_users'] }}</h3>
                <p>New This Week</p>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row g-4">
            <!-- Recent Users -->
            <div class="col-lg-8">
                <div class="action-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Recent Users</h5>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-smbtn-primary-custom">View All</a>
                    </div>

                    @if($recentUsers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentUsers as $user)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-1">{{ $user->name }}</h6>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-people display-4 text-muted"></i>
                            <p class="text-muted mt-2">No users found.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="action-card">
                    <h5 class="mb-4">Quick Actions</h5>

                    <a href="{{ route('admin.users.index') }}" class="action-link" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="bi bi-people me-3"></i>
                        <span>Manage All Users</span>
                    </a>

                    <a href="{{ route('admin.users.create') }}" class="action-link" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                        <i class="bi bi-person-plus me-3"></i>
                        <span>Create New User</span>
                    </a>

                    <a href="{{ route('admin.send-reminders.form') }}" class="action-link" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="bi bi-bell me-3"></i>
                        <span>Send Manual Reminders</span>
                    </a>

                    <a href="{{ route('dashboard') }}" class="action-link" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                        <i class="bi bi-speedometer2 me-3"></i>
                        <span>Main Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
