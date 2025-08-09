@extends('layouts.app')

@section('page-title', 'Financial Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Financial Analytics</h1>
                <div class="btn-group" role="group">
                    <a href="{{ route('hospital-admin.analytics.overview') }}" class="btn btn-outline-primary">Overview</a>
                    <a href="{{ route('hospital-admin.analytics.doctors') }}" class="btn btn-outline-primary">Doctors</a>
                    <button type="button" class="btn btn-outline-primary active">Financial</button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Financial Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                            <h3 class="text-success">${{ number_format($monthlyRevenue, 2) }}</h3>
                            <p class="mb-0">Monthly Revenue</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
                            <h3 class="text-primary">${{ number_format($yearlyRevenue, 2) }}</h3>
                            <p class="mb-0">Yearly Revenue</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                            <h3 class="text-warning">${{ number_format($subscriptionCosts['monthly_cost'], 2) }}</h3>
                            <p class="mb-0">Monthly Subscription</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <i class="fas fa-calculator fa-2x text-info mb-2"></i>
                            <h3 class="text-info">${{ number_format($monthlyRevenue - $subscriptionCosts['monthly_cost'], 2) }}</h3>
                            <p class="mb-0">Net Profit</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue by Doctor -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Revenue by Doctor</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Doctor</th>
                                            <th>Monthly Revenue</th>
                                            <th>Yearly Revenue</th>
                                            <th>Appointments</th>
                                            <th>Avg. Fee</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($revenueByDoctor as $revenue)
                                            <tr>
                                                <td>{{ $revenue['doctor_name'] }}</td>
                                                <td class="text-success fw-bold">${{ number_format($revenue['monthly_revenue'], 2) }}</td>
                                                <td class="text-primary fw-bold">${{ number_format($revenue['yearly_revenue'], 2) }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $revenue['appointments_count'] ?? 0 }}</span>
                                                </td>
                                                <td>${{ number_format($revenue['avg_fee'] ?? 0, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    No revenue data available
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Revenue Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Monthly Revenue Trends</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyTrendsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cost Breakdown -->
            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Cost Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Monthly Subscription</span>
                                <span class="fw-bold text-danger">${{ number_format($subscriptionCosts['monthly_cost'], 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Yearly Subscription</span>
                                <span class="fw-bold text-info">${{ number_format($subscriptionCosts['yearly_cost'], 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Cost per Doctor</span>
                                <span class="fw-bold text-warning">${{ number_format($subscriptionCosts['per_doctor_cost'], 2) }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Profit Margin</span>
                                <span class="fw-bold text-success">
                                    {{ $monthlyRevenue > 0 ? number_format((($monthlyRevenue - $subscriptionCosts['monthly_cost']) / $monthlyRevenue) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Financial Insights</h5>
                        </div>
                        <div class="card-body">
                            @if($monthlyRevenue > $subscriptionCosts['monthly_cost'])
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Profitable:</strong> Your hospital is generating positive cash flow.
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Break-even needed:</strong> Consider increasing appointments or fees.
                                </div>
                            @endif

                            <div class="mt-3">
                                <h6>Recommendations:</h6>
                                <ul class="list-unstyled">
                                    @if(count($revenueByDoctor) > 0)
                                        <li class="mb-2">
                                            <i class="fas fa-lightbulb text-warning me-2"></i>
                                            Top performer: {{ $revenueByDoctor[0]['doctor_name'] ?? 'N/A' }}
                                        </li>
                                    @endif
                                    <li class="mb-2">
                                        <i class="fas fa-chart-line text-info me-2"></i>
                                        Track monthly trends for better planning
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-users text-primary me-2"></i>
                                        Consider adding more doctors to increase capacity
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Safe JSON parsing with fallbacks
    const revenueLabels = {!! json_encode(array_column($revenueByDoctor ?? [], 'doctor_name')) !!};
    const revenueData = {!! json_encode(array_column($revenueByDoctor ?? [], 'monthly_revenue')) !!};
    const monthlyRevenueData = {!! json_encode($monthlyTrends['revenue'] ?? [0,0,0,0,0,0,0,0,0,0,0,0]) !!};
    const monthlyCostsData = {!! json_encode($monthlyTrends['costs'] ?? array_fill(0, 12, $subscriptionCosts['monthly_cost'] ?? 0)) !!};

    // Revenue Distribution Chart
    const revenueDistributionCtx = document.getElementById('revenueDistributionChart').getContext('2d');
    new Chart(revenueDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: revenueLabels.length > 0 ? revenueLabels : ['No Data'],
            datasets: [{
                data: revenueData.length > 0 ? revenueData : [1],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Monthly Trends Chart
    const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
    new Chart(monthlyTrendsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Revenue',
                    data: monthlyRevenueData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1
                },
                {
                    label: 'Costs',
                    data: monthlyCostsData,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
</script>
@endpush