@extends('layouts.app')

@section('title', 'System Monitoring Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> System Monitoring Dashboard
                    </h4>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- System Status Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon {{ $systemStatus['overall_status'] === 'healthy' ? 'bg-success' : ($systemStatus['overall_status'] === 'warning' ? 'bg-warning' : 'bg-danger') }}">
                                    <i class="fas fa-server"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">System Status</span>
                                    <span class="info-box-number">{{ ucfirst($systemStatus['overall_status']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Uptime</span>
                                    <span class="info-box-number">{{ $systemStatus['uptime'] ? round($systemStatus['uptime'] / 3600, 1) . 'h' : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Active Users</span>
                                    <span class="info-box-number">{{ $metrics['summary']['active_users'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-secondary">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Active Alerts</span>
                                    <span class="info-box-number">{{ count($alerts) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service Health Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Service Health</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                            <th>Last Check</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($systemStatus['services'] as $service => $status)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $service)) }}</td>
                                            <td>
                                                <span class="badge badge-{{ $status['status'] === 'healthy' ? 'success' : ($status['status'] === 'warning' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($status['status']) }}
                                                </span>
                                            </td>
                                            <td>{{ $status['message'] ?? 'No message' }}</td>
                                            <td>{{ $status['timestamp'] ? \Carbon\Carbon::parse($status['timestamp'])->diffForHumans() : 'Never' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Response Time (P95)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="responseTimeChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Error Rate</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="errorRateChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Alerts -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Active Alerts</h5>
                                </div>
                                <div class="card-body">
                                    @if(count($alerts) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Severity</th>
                                                    <th>Title</th>
                                                    <th>Message</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($alerts as $alert)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-{{ $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'warning' : 'info') }}">
                                                            {{ ucfirst($alert['severity']) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $alert['title'] }}</td>
                                                    <td>{{ Str::limit($alert['message'], 100) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($alert['created_at'])->diffForHumans() }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning acknowledge-alert"
                                                                data-alert-id="{{ $alert['id'] }}"
                                                                data-toggle="modal"
                                                                data-target="#acknowledgeModal">
                                                            Acknowledge
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> No active alerts. System is healthy!
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Acknowledge Alert Modal -->
<div class="modal fade" id="acknowledgeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acknowledge Alert</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="acknowledgeForm">
                <div class="modal-body">
                    <input type="hidden" id="alertId" name="alert_id">
                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes about this alert..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Acknowledge Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Response Time Chart
    const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
    new Chart(responseTimeCtx, {
        type: 'line',
        data: {
            labels: {{ Js::from($metrics['charts']['response_time_trend']->pluck('timestamp')->map(function($timestamp) {
                return \Carbon\Carbon::parse($timestamp)->format('H:i');
            })) }},
            datasets: [{
                label: 'P95 Response Time (ms)',
                data: {{ Js::from($metrics['charts']['response_time_trend']->pluck('value')) }},
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Error Rate Chart
    const errorRateCtx = document.getElementById('errorRateChart').getContext('2d');
    new Chart(errorRateCtx, {
        type: 'line',
        data: {
            labels: {{ Js::from($metrics['charts']['error_rate_trend']->pluck('timestamp')->map(function($timestamp) {
                return \Carbon\Carbon::parse($timestamp)->format('H:i');
            })) }},
            datasets: [{
                label: 'Error Rate (%)',
                data: {{ Js::from($metrics['charts']['error_rate_trend']->pluck('value')->map(function($value) {
                    return $value * 100;
                })) }},
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });

    // Handle alert acknowledgement
    $('.acknowledge-alert').on('click', function() {
        const alertId = $(this).data('alert-id');
        $('#alertId').val(alertId);
    });

    $('#acknowledgeForm').on('submit', function(e) {
        e.preventDefault();

        const alertId = $('#alertId').val();
        const notes = $('#notes').val();

        $.ajax({
            url: `/api/monitoring/alerts/${alertId}/acknowledge`,
            method: 'POST',
            data: {
                notes: notes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#acknowledgeModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                alert('Failed to acknowledge alert: ' + xhr.responseJSON?.message);
            }
        });
    });
});
</script>
@endsection
