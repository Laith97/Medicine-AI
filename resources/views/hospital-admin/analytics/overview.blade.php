@extends('layouts.app')

@section('page-title', 'Analytics Overview')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Analytics Overview</h1>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary active">Overview</button>
                    <a href="{{ route('hospital-admin.analytics.doctors') }}" class="btn btn-outline-primary">Doctors</a>
                    <a href="{{ route('hospital-admin.analytics.financial') }}" class="btn btn-outline-primary">Financial</a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Key Metrics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <i class="fas fa-user-md fa-2x text-primary mb-2"></i>
                            <h3 class="text-primary">{{ $metrics['total_doctors'] ?? 0 }}</h3>
                            <p class="mb-0">Total Doctors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                            <h3 class="text-success">{{ $metrics['total_appointments'] ?? 0 }}</h3>
                            <p class="mb-0">Total Appointments</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-info mb-2"></i>
                            <h3 class="text-info">{{ $metrics['total_patients'] ?? 0 }}</h3>
                            <p class="mb-0">Total Patients</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <h3 class="text-warning">{{ number_format($metrics['average_rating'] ?? 0, 1) }}</h3>
                            <p class="mb-0">Average Rating</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Monthly Appointments Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="appointmentsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Doctor Specialties</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="specialtiesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Activity</th>
                                            <th>Doctor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentActivity ?? [] as $activity)
                                            <tr>
                                                <td>{{ $activity['date'] }}</td>
                                                <td>{{ $activity['description'] }}</td>
                                                <td>{{ $activity['doctor'] }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $activity['status_color'] }}">
                                                        {{ $activity['status'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    No recent activity to display
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
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Safe JSON parsing with fallbacks
    const appointmentsData = {!! json_encode($chartData['appointments'] ?? [0,0,0,0,0,0,0,0,0,0,0,0]) !!};
    const specialtyLabels = {!! json_encode($chartData['specialty_labels'] ?? ['No Data']) !!};
    const specialtyData = {!! json_encode($chartData['specialty_data'] ?? [1]) !!};

    // Appointments Chart
    const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
    new Chart(appointmentsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Appointments',
                data: appointmentsData,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
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

    // Specialties Chart
    const specialtiesCtx = document.getElementById('specialtiesChart').getContext('2d');
    new Chart(specialtiesCtx, {
        type: 'doughnut',
        data: {
            labels: specialtyLabels,
            datasets: [{
                data: specialtyData,
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
            maintainAspectRatio: true
        }
    });
</script>
@endpush