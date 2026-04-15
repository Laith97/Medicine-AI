@extends('master')

@section('title', 'Ambient Listening Performance')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-chart-line me-2"></i>
                        Ambient Listening Performance Analytics
                    </h4>
                    <div class="card-tools">
                        <select id="timeRange" class="form-select form-select-sm" style="width: auto;">
                            <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Success Rates Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Overall Success Rate</h5>
                                    <h2>{{ number_format($successRates['overall_success_rate'], 1) }}%</h2>
                                    <small>{{ $successRates['total_sessions'] }} sessions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Live Transcription</h5>
                                    <h2>{{ number_format($successRates['live_transcription_success_rate'], 1) }}%</h2>
                                    <small>Real-time accuracy</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Server Processing</h5>
                                    <h2>{{ number_format($successRates['server_processing_success_rate'], 1) }}%</h2>
                                    <small>AI-enhanced accuracy</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Server Improvement</h5>
                                    <h2>{{ number_format($successRates['server_improvement_rate'], 1) }}%</h2>
                                    <small>Better than live</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Trends Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Performance Trends</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="performanceTrendsChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Statistics -->
                    @if(!empty($errorStatistics))
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Error Analysis</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Error Type</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($errorStatistics as $error)
                                                <tr>
                                                    <td>{{ ucfirst(str_replace('_', ' ', $error['error_type'])) }}</td>
                                                    <td>{{ $error['count'] }}</td>
                                                    <td>{{ number_format(($error['count'] / $successRates['total_sessions']) * 100, 1) }}%</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Recent Sessions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Recent Sessions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Processing Type</th>
                                                    <th>Success</th>
                                                    <th>Processing Time</th>
                                                    <th>Live Length</th>
                                                    <th>Server Length</th>
                                                    <th>Improvement</th>
                                                    <th>Device</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($recentSessions as $session)
                                                <tr>
                                                    <td>{{ $session->created_at->format('M j, Y H:i') }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $session->processing_type === 'hybrid' ? 'primary' : 'secondary' }}">
                                                            {{ ucfirst($session->processing_type) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $session->overall_success ? 'success' : 'danger' }}">
                                                            {{ $session->overall_success ? 'Success' : 'Failed' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $session->total_processing_time ? number_format($session->total_processing_time, 0) . 'ms' : 'N/A' }}</td>
                                                    <td>{{ $session->live_transcript_length ?? 'N/A' }}</td>
                                                    <td>{{ $session->server_transcript_length ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($session->server_better_than_live)
                                                            <span class="badge bg-success">Improved</span>
                                                        @else
                                                            <span class="badge bg-secondary">Same/Live</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ ucfirst($session->device_type ?? 'unknown') }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="8" class="text-center">No sessions recorded yet.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Performance Trends Chart
    const performanceTrendsData = @json($performanceTrends);
    const ctx = document.getElementById('performanceTrendsChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: performanceTrendsData.map(item => item.date),
            datasets: [{
                label: 'Success Rate (%)',
                data: performanceTrendsData.map(item => item.success_rate),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Average Processing Time (ms)',
                data: performanceTrendsData.map(item => item.avg_processing_time),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                yAxisID: 'y1',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Success Rate (%)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Processing Time (ms)'
                    }
                }
            }
        }
    });

    // Time range selector
    document.getElementById('timeRange').addEventListener('change', function() {
        const days = this.value;
        window.location.href = '{{ route("ai.ambient-listening.performance") }}?days=' + days;
    });
});
</script>
@endsection