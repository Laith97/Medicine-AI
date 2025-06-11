@extends('master')

@section('title', 'Admin Dashboard')

@push('styles')
<style>
    .admin-dashboard {
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
    
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }
    
    .stats-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: white;
        font-size: 1.2rem;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    
    .stats-label {
        color: #6c757d;
        font-weight: 500;
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
<div class="admin-dashboard">
    <div class="container">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1 class="h2 mb-2">Admin Dashboard</h1>
            <p class="mb-0 opacity-75">Manage users and system settings</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="stats-number">{{ $stats['total_users'] }}</h3>
                    <p class="stats-label">Total Users</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #27ae60, #229954);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="stats-number">{{ $stats['admin_users'] }}</h3>
                    <p class="stats-label">Admin Users</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <i class="bi bi-person"></i>
                    </div>
                    <h3 class="stats-number">{{ $stats['regular_users'] }}</h3>
                    <p class="stats-label">Regular Users</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3 class="stats-number">{{ $stats['recent_users'] }}</h3>
                    <p class="stats-label">New This Week</p>
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
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
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
                                            @if($user->isAdmin())
                                                <span class="badge bg-success me-2">Admin</span>
                                            @endif
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