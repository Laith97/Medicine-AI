@extends('layouts.app')

@section('page-title', 'Doctor Statistics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Doctor Statistics</h1>
                <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Doctors
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <i class="fas fa-user-md fa-2x text-primary mb-2"></i>
                            <h3 class="text-primary">{{ $statistics['total_doctors'] }}</h3>
                            <p class="mb-0">Total Doctors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h3 class="text-success">{{ $statistics['active_doctors'] }}</h3>
                            <p class="mb-0">Active Doctors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <i class="fas fa-calendar-check fa-2x text-info mb-2"></i>
                            <h3 class="text-info">{{ $statistics['total_appointments'] }}</h3>
                            <p class="mb-0">Total Appointments</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <h3 class="text-warning">{{ number_format($statistics['average_rating'], 1) }}</h3>
                            <p class="mb-0">Average Rating</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctor Performance Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Doctor Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Status</th>
                                    <th>Total Appointments</th>
                                    <th>This Month</th>
                                    <th>Completed</th>
                                    <th>Reviews</th>
                                    <th>Rating</th>
                                    <th>Revenue (Est.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($doctorStats as $stat)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    @if($stat['doctor']->doctor && $stat['doctor']->doctor->is_active)
                                                        <div class="icon-circle bg-success">
                                                            <i class="fas fa-check text-white"></i>
                                                        </div>
                                                    @else
                                                        <div class="icon-circle bg-secondary">
                                                            <i class="fas fa-pause text-white"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $stat['doctor']->name }}</div>
                                                    <div class="text-muted small">{{ $stat['doctor']->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $stat['doctor']->doctor->specialty->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($stat['doctor']->doctor && $stat['doctor']->doctor->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $stat['total_appointments'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $stat['this_month_appointments'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $stat['completed_appointments'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ $stat['total_reviews'] }}</span>
                                        </td>
                                        <td>
                                            @if($stat['average_rating'] > 0)
                                                <div class="d-flex align-items-center">
                                                    <span class="me-1">{{ number_format($stat['average_rating'], 1) }}</span>
                                                    <i class="fas fa-star text-warning"></i>
                                                </div>
                                            @else
                                                <span class="text-muted">No ratings</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-success fw-bold">
                                                ${{ number_format($stat['estimated_revenue'], 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            No doctors found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Appointments by Specialty</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="specialtyChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Monthly Performance</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    height: 2rem;
    width: 2rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Specialty Chart
    const specialtyCtx = document.getElementById('specialtyChart').getContext('2d');
    new Chart(specialtyCtx, {
        type: 'doughnut',
        data: {
            labels: @json($chartData['specialty_labels'] ?? ['No Data']),
            datasets: [{
                data: @json($chartData['specialty_data'] ?? [1]),
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

    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: @json($chartData['doctor_names'] ?? ['No Data']),
            datasets: [{
                label: 'Appointments This Month',
                data: @json($chartData['monthly_appointments'] ?? [0]),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endpush