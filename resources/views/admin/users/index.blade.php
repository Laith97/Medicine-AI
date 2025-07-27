@extends('layouts.admin')

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
        padding: 0.75rem 0.5rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .custom-table tbody tr {
        transition: background-color 0.3s ease;
    }

    .custom-table tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }

    .custom-table tbody td {
        padding: 0.5rem 0.5rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        background: #DE6262;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .action-btn {
        padding: 0.2rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    /* Compact table styles */
    .custom-table .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .custom-table h6 {
        font-size: 0.85rem;
        margin-bottom: 0;
    }

    .custom-table small {
        font-size: 0.7rem;
    }

    /* Responsive column widths */
    .custom-table th:nth-child(1), .custom-table td:nth-child(1) { width: 15%; }
    .custom-table th:nth-child(2), .custom-table td:nth-child(2) { width: 18%; }
    .custom-table th:nth-child(3), .custom-table td:nth-child(3) { width: 12%; }
    .custom-table th:nth-child(4), .custom-table td:nth-child(4) { width: 10%; }
    .custom-table th:nth-child(5), .custom-table td:nth-child(5) { width: 12%; }
    .custom-table th:nth-child(6), .custom-table td:nth-child(6) { width: 10%; }
    .custom-table th:nth-child(7), .custom-table td:nth-child(7) { width: 8%; }
    .custom-table th:nth-child(8), .custom-table td:nth-child(8) { width: 5%; }
    .custom-table th:nth-child(9), .custom-table td:nth-child(9) { width: 10%; }

    /* Pagination styling */
    .pagination {
        margin-bottom: 0;
    }

    .pagination .page-link {
        color: #DE6262;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        margin: 0 0.125rem;
    }

    .pagination .page-link:hover {
        color: white;
        background-color: #DE6262;
        border-color: #DE6262;
    }

    .pagination .page-item.active .page-link {
        background-color: #DE6262;
        border-color: #DE6262;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
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
                    <h1 class="h2 mb-2 text-white">Manage Users</h1>
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
                            <th>Phone</th>
                            <th>Specialty</th>
                            <th>Monthly Amount</th>
                            <th>Cost Limit</th>
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
                                        <div class="user-avatar me-2">
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
                                    <span class="text-muted">
                                        @if($user->phone)
                                            <i class="bi bi-telephone me-1"></i>{{ $user->phone }}
                                        @else
                                            <span class="text-danger">Not provided</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        @if($user->setting && $user->setting->specialty)
                                            {{ $user->setting->specialty }}
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if($user->monthlyInvoiceSetting && $user->monthlyInvoiceSetting->is_active)
                                        <span class="badge bg-success">
                                            <i class="bi bi-credit-card me-1"></i>{{ $user->monthlyInvoiceSetting->getAmountWithPeriod() }}
                                        </span>
                                        @if($user->monthlyInvoiceSetting->is_restricted)
                                            <br><small class="badge bg-danger mt-1">Restricted</small>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->monthly_cost_limit > 0)
                                        <span class="badge bg-info">
                                            <i class="bi bi-currency-dollar me-1"></i>${{ number_format($user->monthly_cost_limit, 2) }}
                                        </span>
                                        @php
                                            $usagePercentage = $user->getCostUsagePercentage();
                                            $monthlyCost = $user->getMonthlyCostEstimate();
                                        @endphp
                                        @if($monthlyCost > 0)
                                            <br><small class="badge {{ $usagePercentage >= 90 ? 'bg-danger' : ($usagePercentage >= 70 ? 'bg-warning' : 'bg-success') }} mt-1">
                                                {{ number_format($usagePercentage, 1) }}% used
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">No limit</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">{{ $user->created_at->format('M d, Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $user->patientAnalyses->count() }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-nowrap">
                                        <a href="{{ route('admin.users.show', $user) }}" class="action-btn btn btn-primary-custom btn-sm" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="action-btn btn btn-outline-warning btn-sm" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn btn btn-outline-danger btn-sm" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge bg-light text-dark" style="font-size: 0.6rem;">You</span>
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
