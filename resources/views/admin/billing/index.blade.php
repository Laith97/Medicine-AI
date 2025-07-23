@extends('layouts.admin')

@section('title', 'Billing Dashboard')

@push('styles')
<style>
    .billing-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    .stat-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stat-card p {
        margin: 0;
        opacity: 0.9;
    }

    .usage-bar {
        background-color: #e9ecef;
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
    }

    .usage-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .usage-fill.low { background-color: #28a745; }
    .usage-fill.medium { background-color: #ffc107; }
    .usage-fill.high { background-color: #dc3545; }

    .plan-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .plan-free { background-color: #6c757d; color: white; }
    .plan-basic { background-color: #17a2b8; color: white; }
    .plan-pro { background-color: #DE6262; color: white; }
    .plan-enterprise { background-color: #6f42c1; color: white; }

    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .table th {
        background-color: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 1rem 0.75rem;
    }

    .table td {
        border: none;
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .table tbody tr {
        border-bottom: 1px solid #e9ecef;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Page Header -->
            <div class="page-header text-center text-md-start mb-4">
                <h2><i class="fas fa-credit-card me-2"></i>Billing Dashboard</h2>
                <p>Monitor user subscriptions, token usage, and revenue</p>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="{{ route('admin.billing') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date_range" class="form-label">Date Range</label>
                        <select name="date_range" id="date_range" class="form-select" onchange="this.form.submit()">
                            <option value="current_month" {{ $dateRange === 'current_month' ? 'selected' : '' }}>Current Month</option>
                            <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="last_3_months" {{ $dateRange === 'last_3_months' ? 'selected' : '' }}>Last 3 Months</option>
                            <option value="current_year" {{ $dateRange === 'current_year' ? 'selected' : '' }}>Current Year</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">
                            {{ $startDate->format('M j, Y') }} - {{ $endDate->format('M j, Y') }}
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('admin.billing.export', ['date_range' => $dateRange]) }}" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Export CSV
                        </a>
                    </div>
                </form>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3>{{ number_format($totals['total_users']) }}</h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3>{{ number_format($totals['active_subscribers']) }}</h3>
                        <p>Active Subscribers</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3>{{ number_format($totals['total_requests']) }}</h3>
                        <p>Total Requests</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3>{{ number_format($totals['total_tokens']) }}</h3>
                        <p>Total Tokens</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3>${{ number_format($totals['total_cost'], 2) }}</h3>
                        <p>Token Costs</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3>${{ number_format($totals['total_revenue'], 2) }}</h3>
                        <p>Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="billing-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">User Billing Details</h5>
                    <small class="text-muted">{{ $users->count() }} users</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Subscription</th>
                                <th>Usage</th>
                                <th>Tokens</th>
                                <th>Cost</th>
                                <th>Stripe ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $user['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $user['email'] }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="plan-badge plan-{{ $user['current_plan'] }}">
                                        {{ ucfirst($user['current_plan']) }}
                                    </span>
                                </td>
                                <td>
                                    @if($user['subscription_active'])
                                        <span class="badge bg-success">Active</span>
                                        @if($user['subscription_ends_at'])
                                            <br><small class="text-muted">Until {{ $user['subscription_ends_at']->format('M j, Y') }}</small>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $percentage = $user['usage_percentage'];
                                        $barClass = $percentage >= 90 ? 'high' : ($percentage >= 70 ? 'medium' : 'low');
                                    @endphp
                                    <div class="usage-bar mb-1">
                                        <div class="usage-fill {{ $barClass }}" style="width: {{ min($percentage, 100) }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                        {{ number_format($percentage, 1) }}% 
                                        ({{ number_format($user['monthly_usage']) }}/{{ $user['token_limit'] === -1 ? '∞' : number_format($user['token_limit']) }})
                                    </small>
                                </td>
                                <td>
                                    <strong>{{ number_format($user['total_tokens']) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ number_format($user['total_requests']) }} requests</small>
                                </td>
                                <td>
                                    <strong>${{ number_format($user['total_cost'], 4) }}</strong>
                                </td>
                                <td>
                                    @if($user['stripe_customer_id'])
                                        <code class="small">{{ substr($user['stripe_customer_id'], 0, 12) }}...</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No users found for the selected period.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh every 5 minutes
    setTimeout(function() {
        window.location.reload();
    }, 300000);
</script>
@endpush