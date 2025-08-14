@extends('layouts.admin')

@section('title', 'Billing Dashboard')

@push('styles')
<style>
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

</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white"><i class="fas fa-credit-card me-2"></i>Billing Dashboard</h1>
                    <p class="mb-0">Monitor user subscriptions, token usage, and revenue</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="admin-card-body" style="padding: 1rem 1.5rem;">
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
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i>Export CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <i class="fas fa-users"></i>
                <h3>{{ number_format($totals['total_users']) }}</h3>
                <p>Total Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-user-check"></i>
                <h3>{{ number_format($totals['active_subscribers']) }}</h3>
                <p>Active Subscribers</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-user-lock"></i>
                <h3>{{ number_format($totals['restricted_users']) }}</h3>
                <p>Restricted Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-paper-plane"></i>
                <h3>{{ number_format($totals['total_requests']) }}</h3>
                <p>Total Requests</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-coins"></i>
                <h3>{{ number_format($totals['total_tokens']) }}</h3>
                <p>Total Tokens</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-dollar-sign"></i>
                <h3>${{ number_format($totals['total_cost'], 2) }}</h3>
                <p>Token Costs</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-chart-line"></i>
                <h3>${{ number_format($totals['total_revenue'], 2) }}</h3>
                <p>Revenue</p>
            </div>
        </div>

        <!-- Users Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="mb-0">User Billing Details</h5>
                <small class="text-muted">{{ $users->count() }} users</small>
            </div>
            <div class="admin-card-body">
                <div class="admin-table-container">
                    <table class="admin-table billing-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Pricing (M/Y)</th>
                                <th>Status</th>
                                <th>Subscription</th>
                                <th>Usage</th>
                                <th>Tokens</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <h6>{{ $user['name'] }}</h6>
                                        <small class="text-muted">{{ $user['email'] }}</small>
                                        @if($user['phone'])
                                            <br><small class="text-muted">{{ $user['phone'] }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="admin-badge {{ $user['monthly_price'] > 0 ? 'success' : 'secondary' }} mb-1">
                                            <i class="bi bi-calendar-month"></i>${{ number_format($user['monthly_price'], 0) }}/mo
                                        </span>
                                        <span class="admin-badge {{ $user['yearly_price'] > 0 ? 'info' : 'secondary' }}">
                                            <i class="bi bi-calendar-year"></i>${{ number_format($user['yearly_price'], 0) }}/yr
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $status = $user['subscription_status'];
                                        $badgeClass = match($status) {
                                            'active' => 'success',
                                            'unlimited' => 'primary',
                                            'grace_period' => 'warning',
                                            'warning_period' => 'warning',
                                            'restricted' => 'danger',
                                            'setup_pending' => 'secondary',
                                            default => 'secondary'
                                        };
                                        $statusText = match($status) {
                                            'active' => 'Active',
                                            'unlimited' => 'Unlimited',
                                            'grace_period' => 'Grace Period',
                                            'warning_period' => 'Warning',
                                            'restricted' => 'Restricted',
                                            'setup_pending' => 'Setup Pending',
                                            default => 'Unknown'
                                        };
                                    @endphp
                                    <span class="admin-badge {{ $badgeClass }}">{{ $statusText }}</span>
                                    @if($user['is_restricted'])
                                        <br><small class="admin-badge danger mt-1">Access Restricted</small>
                                    @endif
                                </td>
                                <td>
                                    @if($user['subscription_starts_at'])
                                        <small class="text-muted">Started: {{ \Carbon\Carbon::parse($user['subscription_starts_at'])->format('M j, Y') }}</small>
                                        @if($user['subscription_ends_at'])
                                            <br><small class="text-muted">Ends: {{ \Carbon\Carbon::parse($user['subscription_ends_at'])->format('M j, Y') }}</small>
                                            @if($user['days_remaining'] !== null)
                                                <br><small class="text-{{ $user['days_remaining'] <= 7 ? 'danger' : 'muted' }}">{{ $user['days_remaining'] }} days left</small>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted">Not started</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $percentage = $user['cost_usage_percentage'];
                                        $barClass = $percentage >= 90 ? 'high' : ($percentage >= 70 ? 'medium' : 'low');
                                    @endphp
                                    <div class="usage-bar mb-1">
                                        <div class="usage-fill {{ $barClass }}" style="width: {{ min($percentage, 100) }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                        {{ number_format($percentage, 1) }}% 
                                        ({{ $user['monthly_cost_limit'] > 0 ? '$' . number_format($user['monthly_cost_limit'], 2) : 'No limit' }})
                                    </small>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <h6>{{ number_format($user['total_tokens']) }}</h6>
                                        <small class="text-muted">{{ number_format($user['total_requests']) }} requests</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-badge info">${{ number_format($user['total_cost'], 4) }}</span>
                                    @if($user['billing_amount'] > 0)
                                        <br><small class="text-muted">Billing: ${{ number_format($user['billing_amount'], 2) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="{{ route('admin.users.edit', $user['id']) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        @if($user['stripe_customer_id'])
                                            <button class="btn btn-outline-info btn-sm" title="Stripe ID: {{ $user['stripe_customer_id'] }}">
                                                <i class="fab fa-stripe"></i> Stripe
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="admin-empty-state">
                                        <i class="fas fa-users"></i>
                                        <p>No users found for the selected period.</p>
                                    </div>
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