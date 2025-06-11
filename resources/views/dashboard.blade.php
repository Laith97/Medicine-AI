@extends('master')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }
    
    .dashboard-header h2 {
        margin: 0;
        font-weight: 700;
        font-size: 2.5rem;
        color: white;
    }
    
    .dashboard-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #2c3e50 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        transition: box-shadow 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-size: 1rem;
    }
    
    .btn-primary-custom:hover {
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary-custom {
        background: white;
        border: 2px solid #DE6262;
        color: #DE6262;
        font-weight: 600;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-size: 1rem;
    }
    
    .btn-secondary-custom:hover {
        background: #DE6262;
        color: white;
        text-decoration: none;
    }
    
    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        transition: box-shadow 0.3s ease;
        height: 100%;
    }
    
    .stats-card:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: white;
        font-size: 1.5rem;
    }
    
    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    
    .stats-label {
        color: #6c757d;
        font-weight: 500;
        margin: 0;
        font-size: 1rem;
    }
    
    .chart-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
    }
    
    .chart-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #DE6262;
        display: inline-block;
    }
    
    .table-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
    }
    
    .table-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 3px solid #DE6262;
        display: inline-block;
    }
    
    .custom-table {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .custom-table thead {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
    }
    
    .custom-table thead th {
        border: none;
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .custom-table tbody tr {
        transition: background-color 0.3s ease;
    }
    
    .custom-table tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }
    
    .custom-table tbody td {
        padding: 1rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #DE6262;
        margin-bottom: 1rem;
    }
    
    /* Remove any potential infinite loop causing styles */
    * {
        box-sizing: border-box;
    }
    
    .chart-card canvas {
        max-height: 300px !important;
        height: 300px !important;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }
        
        .dashboard-header h2 {
            font-size: 2rem;
        }
        
        .stats-number {
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            @if(Auth::user())
                <h2>Welcome back, {{ Auth::user()->name }}!</h2>
                <p>Here's an overview of your medical practice</p>
            @else
                <h2>Medical Dashboard</h2>
                <p>Manage your patients and cases efficiently</p>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="action-buttons">
            <a href="{{ route('ask-openai') }}" class="btn-primary-custom">
                <i class="fas fa-user-plus me-2"></i> Add New Patient
            </a>
            <a href="{{ route('cases') }}" class="btn-secondary-custom">
                <i class="fas fa-list me-2"></i> View All Cases
            </a>
        </div>

        <!-- Statistics Section -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="stats-number">{{ count($records) }}</p>
                    <p class="stats-label">Total Patients</p>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <p class="stats-number">
                        @if(count($records) > 0)
                            {{ $records->first()->created_at->format('M d') }}
                        @else
                            N/A
                        @endif
                    </p>
                    <p class="stats-label">Latest Case</p>
                </div>
            </div>
        </div>

        <!-- Cases Over Time Chart -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="chart-card">
                    <h6 class="chart-title">Cases Over Time</h6>
                    <div style="position: relative; height: 300px;">
                        <canvas id="casesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <p class="stats-number">{{ $weeklyCount }}</p>
                    <p class="stats-label">Cases This Week</p>
                </div>
            </div>
        </div>

        <!-- Recent Cases Table -->
        <div class="table-card">
            <h6 class="table-title">Recent Cases</h6>
            @if(count($records) > 0)
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Date Added</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records->take(10) as $record)
                                <tr>
                                    <td><strong>#{{ $record->id }}</strong></td>
                                    <td>{{ $record->name ?? 'N/A' }}</td>
                                    <td>{{ $record->age ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $record->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white;">
                                            {{ ucfirst($record->gender ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ $record->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge" style="background-color: #27ae60; color: white;">
                                            Completed
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-user-md"></i>
                    <h5>No cases yet</h5>
                    <p>Start by adding your first patient case</p>
                    <a href="{{ route('ask-openai') }}" class="btn-primary-custom mt-3">
                        <i class="fas fa-plus me-2"></i> Add First Patient
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        // Use pre-calculated chart data from controller
        const chartLabels = @json($chartLabels ?? []);
        const chartData = @json($chartData ?? []);

        // Only render chart if canvas element exists
        const chartCanvas = document.getElementById('casesChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            
            // Destroy any existing chart instance
            if (window.dashboardChart) {
                window.dashboardChart.destroy();
            }
            
            // Create new chart
            window.dashboardChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Cases',
                        data: chartData,
                        borderColor: '#DE6262',
                        backgroundColor: 'rgba(222, 98, 98, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#DE6262',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable animations to prevent loops
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(44, 62, 80, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#DE6262',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { 
                                stepSize: 1,
                                color: '#6c757d'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6c757d'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error rendering dashboard chart:', error);
        // Hide chart container if there's an error
        const chartContainer = document.querySelector('.chart-card');
        if (chartContainer) {
            chartContainer.style.display = 'none';
        }
    }
});
</script>
@endpush