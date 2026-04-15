@extends('layouts.app')

@section('title', 'Real-time Performance Dashboard')

@section('styles')
<style>
.performance-dashboard {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.dashboard-header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.metric-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.metric-card.healthy {
    border-left: 4px solid #28a745;
}

.metric-card.warning {
    border-left: 4px solid #ffc107;
}

.metric-card.critical {
    border-left: 4px solid #dc3545;
}

.metric-card.degraded {
    border-left: 4px solid #fd7e14;
}

.metric-title {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 8px;
    text-transform: uppercase;
    font-weight: 600;
}

.metric-value {
    font-size: 28px;
    font-weight: bold;
    color: #495057;
    margin-bottom: 4px;
}

.metric-subtitle {
    font-size: 12px;
    color: #6c757d;
}

.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alerts-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.alert-item {
    padding: 10px;
    border-left: 4px solid #dc3545;
    background: #f8d7da;
    margin-bottom: 8px;
    border-radius: 4px;
}

.alert-item.warning {
    border-left-color: #ffc107;
    background: #fff3cd;
}

.alert-item.info {
    border-left-color: #17a2b8;
    background: #d1ecf1;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-indicator.healthy {
    background: #28a745;
}

.status-indicator.warning {
    background: #ffc107;
}

.status-indicator.critical {
    background: #dc3545;
}

.status-indicator.degraded {
    background: #fd7e14;
}

.refresh-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 10px;
}

.refresh-btn:hover {
    background: #0056b3;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>
@endsection

@section('content')
<div class="performance-dashboard">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-chart-line me-2"></i>Real-time Performance Dashboard</h1>
                            <p class="text-muted mb-0">Monitor real-time appointment broadcasting performance</p>
                        </div>
                        <div>
                            <span class="status-indicator {{ $healthStatus['status'] }}"></span>
                            <span class="fw-bold">Status: {{ ucfirst($healthStatus['status']) }}</span>
                            <button class="refresh-btn" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <!-- Performance Issues Alert -->
        @if(!empty($healthStatus['issues']))
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Performance Issues Detected</h5>
            <ul class="mb-0">
                @foreach($healthStatus['issues'] as $issue)
                <li>{{ $issue }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <!-- Broadcast Metrics -->
            <div class="metric-card healthy">
                <div class="metric-title">Total Broadcasts</div>
                <div class="metric-value">{{ number_format($metrics['metrics']['broadcasts']['total']) }}</div>
                <div class="metric-subtitle">
                    {{ number_format($metrics['metrics']['broadcasts']['successful']) }} successful,
                    {{ number_format($metrics['metrics']['broadcasts']['failed']) }} failed
                </div>
            </div>

            <div class="metric-card {{ $metrics['metrics']['broadcasts']['avg_latency'] > 1000 ? 'critical' : 'healthy' }}">
                <div class="metric-title">Avg Broadcast Latency</div>
                <div class="metric-value">{{ number_format($metrics['metrics']['broadcasts']['avg_latency'], 1) }}ms</div>
                <div class="metric-subtitle">Target: <1000ms</div>
            </div>

            <div class="metric-card healthy">
                <div class="metric-title">Compression Ratio</div>
                <div class="metric-value">{{ number_format($metrics['metrics']['broadcasts']['avg_compression_ratio'] * 100, 1) }}%</div>
                <div class="metric-subtitle">{{ number_format($metrics['metrics']['broadcasts']['compressed']) }} compressed broadcasts</div>
            </div>

            <!-- Connection Pool Metrics -->
            <div class="metric-card {{ $poolStats['pool_utilization'] > 0.8 ? 'warning' : 'healthy' }}">
                <div class="metric-title">Active Connections</div>
                <div class="metric-value">{{ $poolStats['active_connections'] }}</div>
                <div class="metric-subtitle">{{ number_format($poolStats['pool_utilization'] * 100, 1) }}% pool utilization</div>
            </div>

            <!-- Cache Metrics -->
            <div class="metric-card healthy">
                <div class="metric-title">Cache Hit Rate</div>
                <div class="metric-value">{{ number_format($metrics['metrics']['cache']['hit_rate'] * 100, 1) }}%</div>
                <div class="metric-subtitle">{{ number_format($metrics['metrics']['cache']['hits']) }} hits, {{ number_format($metrics['metrics']['cache']['misses']) }} misses</div>
            </div>

            <!-- Error Rate -->
            <div class="metric-card {{ $metrics['metrics']['broadcasts']['error_rate'] > 0.05 ? 'critical' : 'healthy' }}">
                <div class="metric-title">Error Rate</div>
                <div class="metric-value">{{ number_format($metrics['metrics']['broadcasts']['error_rate'] * 100, 2) }}%</div>
                <div class="metric-subtitle">{{ $metrics['metrics']['errors']['total'] }} total errors</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-container">
            <div class="chart-card">
                <h5><i class="fas fa-chart-bar me-2"></i>Broadcast Performance</h5>
                <canvas id="broadcastChart" width="400" height="200"></canvas>
            </div>

            <div class="chart-card">
                <h5><i class="fas fa-server me-2"></i>Connection Pool Status</h5>
                <canvas id="connectionChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Load Balancer Status -->
        <div class="charts-container">
            <div class="chart-card">
                <h5><i class="fas fa-balance-scale me-2"></i>Load Balancer Distribution</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Server</th>
                                <th>Status</th>
                                <th>Weight</th>
                                <th>Load %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loadStats['servers'] as $server)
                            <tr>
                                <td>{{ $server['id'] }}</td>
                                <td>
                                    <span class="status-indicator {{ isset($loadStats['health_stats'][$server['id']]) && $loadStats['health_stats'][$server['id']]['healthy'] ? 'healthy' : 'critical' }}"></span>
                                    {{ isset($loadStats['health_stats'][$server['id']]) && $loadStats['health_stats'][$server['id']]['healthy'] ? 'Healthy' : 'Unhealthy' }}
                                </td>
                                <td>{{ $server['weight'] }}</td>
                                <td>{{ isset($loadStats['load_stats'][$server['id']]) ? number_format($loadStats['load_stats'][$server['id']]['load_percentage'], 1) : '0.0' }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-card">
                <h5><i class="fas fa-clock me-2"></i>Recent Alerts</h5>
                <div id="alerts-container">
                    @if(empty($metrics['alerts']))
                    <p class="text-muted">No recent alerts</p>
                    @else
                    @foreach(array_slice($metrics['alerts'], 0, 5) as $alert)
                    <div class="alert-item {{ $alert['type'] === 'high_error_rate' ? 'warning' : '' }}">
                        <small class="text-muted">{{ \Carbon\Carbon::parse($alert['timestamp'])->diffForHumans() }}</small>
                        <div class="fw-bold">{{ $alert['message'] }}</div>
                        <div class="small">Value: {{ is_numeric($alert['value']) ? number_format($alert['value'], 2) : $alert['value'] }}</div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="chart-card">
            <h5><i class="fas fa-info-circle me-2"></i>System Information</h5>
            <div class="row">
                <div class="col-md-3">
                    <strong>Uptime:</strong><br>
                    {{ \Carbon\Carbon::now()->subSeconds($metrics['uptime'] ?? 0)->diffForHumans(null, true) }}
                </div>
                <div class="col-md-3">
                    <strong>Last Updated:</strong><br>
                    {{ \Carbon\Carbon::parse($metrics['timestamp'])->format('M j, Y g:i A') }}
                </div>
                <div class="col-md-3">
                    <strong>Healthy Servers:</strong><br>
                    {{ $loadStats['healthy_server_count'] ?? 0 }} / {{ $loadStats['total_servers'] ?? 0 }}
                </div>
                <div class="col-md-3">
                    <strong>Connection Pool:</strong><br>
                    {{ $poolStats['active_connections'] }} / {{ $poolStats['max_connections'] }} active
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let broadcastChart, connectionChart;
let autoRefreshInterval;

function initCharts() {
    // Broadcast Performance Chart
    const broadcastCtx = document.getElementById('broadcastChart').getContext('2d');
    broadcastChart = new Chart(broadcastCtx, {
        type: 'line',
        data: {
            labels: ['Successful', 'Failed', 'Compressed'],
            datasets: [{
                label: 'Broadcasts',
                data: [
                    {{ $metrics['metrics']['broadcasts']['successful'] }},
                    {{ $metrics['metrics']['broadcasts']['failed'] }},
                    {{ $metrics['metrics']['broadcasts']['compressed'] }}
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.2)',
                    'rgba(220, 53, 69, 0.2)',
                    'rgba(0, 123, 255, 0.2)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(0, 123, 255, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Connection Pool Chart
    const connectionCtx = document.getElementById('connectionChart').getContext('2d');
    connectionChart = new Chart(connectionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Available'],
            datasets: [{
                data: [
                    {{ $poolStats['active_connections'] }},
                    {{ $poolStats['max_connections'] - $poolStats['active_connections'] }}
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(108, 117, 125, 0.3)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function refreshDashboard() {
    const dashboard = document.querySelector('.performance-dashboard');
    dashboard.classList.add('loading');

    // Refresh metrics
    fetch('{{ route("realtime-performance.metrics") }}')
        .then(response => response.json())
        .then(data => {
            updateMetrics(data);
            dashboard.classList.remove('loading');
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
            dashboard.classList.remove('loading');
        });
}

function updateMetrics(data) {
    // Update metric values
    document.querySelectorAll('.metric-value').forEach((el, index) => {
        // This would need more specific selectors in a real implementation
        // For now, just log the data
        console.log('Updating metrics:', data);
    });
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(refreshDashboard, 30000); // Refresh every 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    startAutoRefresh();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>
@endsection
