@extends('layouts.admin')

@section('title', 'Usage Analytics')

@push('styles')
<style>
    .analytics-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin: 1rem 0;
    }

    .metric-card {
        background: linear-gradient(135deg, #DE6262 0%, #E87A7A 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    .metric-card h4 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .metric-card p {
        margin: 0;
        opacity: 0.9;
    }

    .user-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .user-item {
        display: flex;
        justify-content-between;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }

    .user-item:last-child {
        border-bottom: none;
    }

    .user-info {
        flex-grow: 1;
    }

    .user-stats {
        text-align: right;
        font-size: 0.9rem;
    }

    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
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
</style>
@endpush

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Page Header -->
            <div class="page-header text-center text-md-start mb-4">
                <h2><i class="fas fa-chart-line me-2"></i>Usage Analytics</h2>
                <p>Detailed insights into OpenAI API usage patterns</p>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="{{ route('admin.usage-analytics') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="period" class="form-label">Time Period</label>
                        <select name="period" id="period" class="form-select" onchange="this.form.submit()">
                            <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="90_days" {{ $period === '90_days' ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="1_year" {{ $period === '1_year' ? 'selected' : '' }}>Last Year</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <small class="text-muted">
                            {{ $startDate->format('M j, Y') }} - {{ $endDate->format('M j, Y') }}
                        </small>
                    </div>
                </form>
            </div>

            <!-- Daily Usage Chart -->
            <div class="analytics-card">
                <h5 class="mb-3">Daily Usage Trends</h5>
                <div class="chart-container">
                    <canvas id="dailyUsageChart"></canvas>
                </div>
            </div>

            <div class="row">
                <!-- Usage by Type -->
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5 class="mb-3">Usage by Request Type</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Requests</th>
                                        <th>Tokens</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($usageByType as $type)
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary">{{ ucfirst($type->request_type) }}</span>
                                        </td>
                                        <td>{{ number_format($type->requests) }}</td>
                                        <td>{{ number_format($type->tokens) }}</td>
                                        <td>${{ number_format($type->cost, 4) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No data available</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Model Usage -->
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5 class="mb-3">Model Usage Statistics</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Model</th>
                                        <th>Requests</th>
                                        <th>Avg Tokens</th>
                                        <th>Total Tokens</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($modelUsage as $model)
                                    <tr>
                                        <td>
                                            <code>{{ $model->model_used ?: 'Unknown' }}</code>
                                        </td>
                                        <td>{{ number_format($model->requests) }}</td>
                                        <td>{{ number_format($model->avg_tokens) }}</td>
                                        <td>{{ number_format($model->tokens) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No data available</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Users -->
            <div class="analytics-card">
                <h5 class="mb-3">Top Users by Usage</h5>
                <div class="user-list">
                    @forelse($topUsers as $user)
                    <div class="user-item">
                        <div class="user-info">
                            <strong>{{ $user->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $user->email }}</small>
                        </div>
                        <div class="user-stats">
                            <div><strong>{{ number_format($user->total_requests) }}</strong> requests</div>
                            <div class="text-muted">
                                @php
                                    $totalTokens = $user->openaiUsages->sum('total_tokens');
                                    $totalCost = $user->openaiUsages->sum('total_cost');
                                @endphp
                                {{ number_format($totalTokens) }} tokens
                                <br>
                                ${{ number_format($totalCost, 4) }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No usage data available for the selected period.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Usage Chart
    const dailyUsageCtx = document.getElementById('dailyUsageChart').getContext('2d');
    const dailyUsageData = @json($dailyUsage);
    
    new Chart(dailyUsageCtx, {
        type: 'line',
        data: {
            labels: dailyUsageData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Requests',
                    data: dailyUsageData.map(item => item.requests),
                    borderColor: '#DE6262',
                    backgroundColor: 'rgba(222, 98, 98, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Tokens (thousands)',
                    data: dailyUsageData.map(item => item.tokens / 1000),
                    borderColor: '#2c3e50',
                    backgroundColor: 'rgba(44, 62, 80, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Requests'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Tokens (thousands)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            if (context.datasetIndex === 0) {
                                const dataPoint = dailyUsageData[context.dataIndex];
                                return `Cost: $${parseFloat(dataPoint.cost).toFixed(4)}`;
                            }
                            return '';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush