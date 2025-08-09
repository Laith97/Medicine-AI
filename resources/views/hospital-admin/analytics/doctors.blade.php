@extends('layouts.app')

@section('page-title', 'Doctor Analytics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Doctor Analytics</h1>
                <div class="btn-group" role="group">
                    <a href="{{ route('hospital-admin.analytics.overview') }}" class="btn btn-outline-primary">Overview</a>
                    <button type="button" class="btn btn-outline-primary active">Doctors</button>
                    <a href="{{ route('hospital-admin.analytics.financial') }}" class="btn btn-outline-primary">Financial</a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Doctor Performance Cards -->
            <div class="row mb-4">
                @foreach($doctorPerformance as $performance)
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">{{ $performance['doctor']->name }}</h6>
                                <span class="badge bg-{{ $performance['doctor']->doctor && $performance['doctor']->doctor->is_active ? 'success' : 'secondary' }}">
                                    {{ $performance['doctor']->doctor && $performance['doctor']->doctor->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-primary">{{ $performance['appointments_total'] }}</h4>
                                            <small class="text-muted">Total Appointments</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-success">{{ $performance['appointments_this_month'] }}</h4>
                                            <small class="text-muted">This Month</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info">{{ $performance['appointments_completed'] }}</h4>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h5 class="text-warning">{{ $performance['reviews_count'] }}</h5>
                                            <small class="text-muted">Reviews</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h5 class="text-warning">
                                                {{ number_format($performance['average_rating'], 1) }}/5
                                            </h5>
                                            <small class="text-muted">Rating</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h5 class="text-success">${{ number_format($performance['revenue_this_month'], 2) }}</h5>
                                        <small class="text-muted">Revenue</small>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Completion Rate</small>
                                        <small class="text-muted">
                                            {{ $performance['appointments_total'] > 0 ? number_format(($performance['appointments_completed'] / $performance['appointments_total']) * 100, 1) : 0 }}%
                                        </small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" 
                                             style="width: {{ $performance['appointments_total'] > 0 ? ($performance['appointments_completed'] / $performance['appointments_total']) * 100 : 0 }}%">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <a href="{{ route('hospital-admin.doctors.show', $performance['doctor']) }}" 
                                       class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <a href="{{ route('hospital-admin.doctors.edit', $performance['doctor']) }}" 
                                       class="btn btn-sm btn-outline-secondary flex-fill">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(empty($doctorPerformance))
                <div class="text-center py-5">
                    <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Doctors Found</h5>
                    <p class="text-muted">Add doctors to your hospital to see their analytics here.</p>
                    <a href="{{ route('hospital-admin.doctors.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add First Doctor
                    </a>
                </div>
            @endif

            <!-- Charts Section -->
            @if(!empty($doctorPerformance))
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Doctor Performance Comparison</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="doctorComparisonChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top Performers</h5>
                            </div>
                            <div class="card-body">
                                @foreach(array_slice($doctorPerformance, 0, 5) as $index => $performance)
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                                            <div>
                                                <div class="fw-bold">{{ $performance['doctor']->name }}</div>
                                                <small class="text-muted">{{ $performance['appointments_this_month'] }} appointments</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success">${{ number_format($performance['revenue_this_month'], 0) }}</div>
                                            <small class="text-muted">{{ number_format($performance['average_rating'], 1) }}★</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if(!empty($doctorPerformance))
    // Doctor Comparison Chart
    const doctorComparisonCtx = document.getElementById('doctorComparisonChart').getContext('2d');
    new Chart(doctorComparisonCtx, {
        type: 'bar',
        data: {
            labels: @json(array_map(function($p) { return $p['doctor']->name; }, $doctorPerformance)),
            datasets: [
                {
                    label: 'This Month Appointments',
                    data: @json(array_column($doctorPerformance, 'appointments_this_month')),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Completed Appointments',
                    data: @json(array_column($doctorPerformance, 'appointments_completed')),
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
    @endif
</script>
@endpush