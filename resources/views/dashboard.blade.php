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
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        height: 100%;
    }
    
    .stats-card:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
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
        box-shadow: 0 8px 20px rgba(222, 98, 98, 0.3);
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
        transition: box-shadow 0.3s ease;
    }
    
    .chart-card:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
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
        transition: box-shadow 0.3s ease;
    }
    
    .table-card:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
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
    
    /* Custom button sizing for dashboard */
    .btn-primary-custom.btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }
    
    .btn-secondary-custom.btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }
    
    /* Filter card styles */
    .filter-card {
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: none;
        background-color: #f8f9fa;
        transition: box-shadow 0.3s ease;
    }
    
    .filter-card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .filter-card .card-body {
        padding: 1.5rem;
    }
    
    .filter-card h6 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    /* Table sorting styles */
    .sort-link {
        color: inherit;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .sort-link:hover {
        color: #DE6262;
    }
    
    .sort-link i {
        margin-left: 0.5rem;
        font-size: 0.8rem;
    }
    
    /* Pagination styles */
    .table-pagination {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .showing-entries {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    /* Chart container styles */
    .stats-card h6 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }
    
    .stats-card h6 i {
        margin-right: 0.5rem;
        color: #DE6262;
    }
    
    /* Animation for stats numbers */
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stats-number {
        animation: countUp 1s ease-out forwards;
    }
    
    /* Tooltip styling */
    .tooltip-inner {
        background-color: #2c3e50;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before, 
    .bs-tooltip-top .tooltip-arrow::before {
        border-top-color: #2c3e50;
    }
    
    /* AI Analysis Styling */
    .ai-response {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
        font-size: 0.95rem;
        line-height: 1.6;
        white-space: pre-line; /* Preserve line breaks */
    }
    
    .analysis-section-header {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.1rem;
        margin-top: 1.2rem;
        margin-bottom: 0.8rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #DE6262;
        display: block; /* Ensure it's a block element */
        width: 100%; /* Full width */
    }
    
    .analysis-section-header:first-child {
        margin-top: 0;
    }
    
    .analysis-content {
        padding: 0.5rem 0 1rem 1rem;
        border-left: 3px solid #f0f0f0;
        margin-bottom: 1rem;
        display: block; /* Ensure it's a block element */
        width: 100%; /* Full width */
    }
    
    .analysis-content p {
        margin-bottom: 0.5rem;
    }
    
    .analysis-percentage {
        font-weight: 700;
        color: #DE6262;
        display: inline-block; /* Keep it inline */
    }
    
    /* Symptom Tags Styling */
    .symptom-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .symptom-tag {
        display: inline-block;
        background-color: rgba(222, 98, 98, 0.1);
        color: #DE6262;
        border: 1px solid rgba(222, 98, 98, 0.2);
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* Remove any potential infinite loop causing styles */
    * {
        box-sizing: border-box;
    }
    
    .chart-card canvas {
        max-height: 300px !important;
        height: 300px !important;
    }
    
    /* Responsive styles */
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
        
        .filter-card .row {
            row-gap: 1rem;
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
            <a href="{{ route('ask-ai') }}" class="btn-primary-custom">
                <i class="fas fa-user-plus me-2"></i> Add New Patient
            </a>
            <a href="{{ route('cases') }}" class="btn-secondary-custom">
                <i class="fas fa-list me-2"></i> View All Cases
            </a>
        </div>

        <!-- Statistics Section -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="stats-number">{{ count($records) }}</p>
                    <p class="stats-label">Total Patients</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
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
            
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-venus-mars"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            $maleCount = $records->where('gender', 'male')->count();
                            $femaleCount = $records->where('gender', 'female')->count();
                            $ratio = $records->count() > 0 ? round(($maleCount / $records->count()) * 100) : 0;
                        @endphp
                        {{ $ratio }}%
                    </p>
                    <p class="stats-label">Male Patients</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            $avgAge = $records->count() > 0 ? round($records->avg('age')) : 0;
                        @endphp
                        {{ $avgAge }}
                    </p>
                    <p class="stats-label">Avg. Patient Age</p>
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

        <!-- Advanced Statistics & Filters -->
        <div class="chart-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="chart-title mb-0">Advanced Statistics</h6>
                <div class="filter-controls">
                    <button class="btn btn-sm btn-outline-secondary me-2" id="refresh-stats">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" id="export-csv"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export-pdf"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Data</h6>
                            <form id="stats-filter-form" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <select class="form-select" id="date-range-select">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 3 months</option>
                                        <option value="180">Last 6 months</option>
                                        <option value="365">Last year</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3 custom-date-range" style="display: none;">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" id="date-from">
                                </div>
                                <div class="col-md-3 custom-date-range" style="display: none;">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="date-to">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="gender-filter">
                                        <option value="all" selected>All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Age Group</label>
                                    <select class="form-select" id="age-filter">
                                        <option value="all" selected>All</option>
                                        <option value="0-18">0-18</option>
                                        <option value="19-35">19-35</option>
                                        <option value="36-50">36-50</option>
                                        <option value="51-65">51-65</option>
                                        <option value="66+">66+</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="reset" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Patient Demographics</h6>
                        <div style="height: 250px;">
                            <canvas id="demographicsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Age Distribution</h6>
                        <div style="height: 250px;">
                            <canvas id="ageDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Patient Visits Over Time</h6>
                        <div style="height: 250px;">
                            <canvas id="visitsTimelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="stats-number" id="new-patients-count">{{ $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count() }}</h3>
                        <p class="stats-label">New Patients (30 days)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-redo"></i>
                        </div>
                        <h3 class="stats-number" id="return-visits-count">
                            @php
                                $returnVisits = $records->where('created_at', '>=', now()->subDays(30))->count() - $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count();
                                echo $returnVisits > 0 ? $returnVisits : 0;
                            @endphp
                        </h3>
                        <p class="stats-label">Return Visits (30 days)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="stats-number" id="growth-rate">
                            @php
                                $currentMonth = $records->where('created_at', '>=', now()->startOfMonth())->count();
                                $lastMonth = $records->where('created_at', '>=', now()->subMonth()->startOfMonth())
                                    ->where('created_at', '<', now()->startOfMonth())->count();
                                $growthRate = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100) : 0;
                                echo $growthRate > 0 ? '+'.$growthRate : $growthRate;
                            @endphp%
                        </h3>
                        <p class="stats-label">Monthly Growth Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consolidated Patient List with Advanced Features -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="table-title mb-0">Patient List</h6>
                <div>
                    <div class="input-group input-group-sm me-2 d-inline-flex" style="width: 200px;">
                        <input type="text" class="form-control" id="patient-search" placeholder="Search patients...">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <a href="{{ route('cases') }}" class="btn-secondary-custom btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> View All
                    </a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Patients</h6>
                            <form id="patient-filter-form" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <select class="form-select" id="patient-date-range">
                                        <option value="all" selected>All Time</option>
                                        <option value="7">Last 7 days</option>
                                        <option value="30">Last 30 days</option>
                                        <option value="90">Last 3 months</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3 patient-custom-date" style="display: none;">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" id="patient-date-from">
                                </div>
                                <div class="col-md-3 patient-custom-date" style="display: none;">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="patient-date-to">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="patient-gender-filter">
                                        <option value="all" selected>All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Age Group</label>
                                    <select class="form-select" id="patient-age-filter">
                                        <option value="all" selected>All</option>
                                        <option value="0-18">0-18</option>
                                        <option value="19-35">19-35</option>
                                        <option value="36-50">36-50</option>
                                        <option value="51-65">51-65</option>
                                        <option value="66+">66+</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Visit Count</label>
                                    <select class="form-select" id="patient-visit-filter">
                                        <option value="all" selected>All</option>
                                        <option value="1">Single Visit</option>
                                        <option value="multiple">Multiple Visits</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="reset" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            @if(count($records) > 0)
                @php
                    // Group patients by patient_key to avoid duplication
                    $patientGroups = [];
                    
                    foreach ($records as $record) {
                        $key = $record->patient_key ?? ($record->name . '-' . $record->age . '-' . $record->gender);
                        
                        if (!isset($patientGroups[$key])) {
                            // Initialize with the first record
                            $patientGroups[$key] = [
                                'patient' => $record,
                                'visits' => [],
                                'visit_count' => 0,
                                'last_visit' => $record->created_at
                            ];
                        }
                        
                        // Add this record to the visits array
                        $patientGroups[$key]['visits'][] = $record;
                        $patientGroups[$key]['visit_count']++;
                        
                        // Update last visit date if this record is more recent
                        if ($record->created_at > $patientGroups[$key]['last_visit']) {
                            $patientGroups[$key]['last_visit'] = $record->created_at;
                        }
                    }
                    
                    // Sort by most recent visit
                    uasort($patientGroups, function($a, $b) {
                        return $b['last_visit'] <=> $a['last_visit'];
                    });
                    
                    // Take only the first 10 for display
                    $patientGroups = array_slice($patientGroups, 0, 10, true);
                @endphp
                
                <div class="table-responsive">
                    <table class="table custom-table mb-0" id="patients-table">
                        <thead>
                            <tr>
                                <th><a href="#" class="sort-link" data-sort="name">Patient Name <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="age">Age <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="gender">Gender <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="visits">Total Visits <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="last-visit">Last Visit <i class="fas fa-sort"></i></a></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patientGroups as $key => $group)
                                <tr data-patient-key="{{ $key }}" data-visits="{{ count($group['visits']) }}" data-last-visit="{{ $group['last_visit']->timestamp }}">
                                    <td>{{ $group['patient']->name ?? 'N/A' }}</td>
                                    <td>{{ $group['patient']->age ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $group['patient']->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white;">
                                            {{ ucfirst($group['patient']->gender ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $group['visit_count'] }}</span>
                                    </td>
                                    <td data-date="{{ $group['last_visit']->timestamp }}">{{ $group['last_visit']->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-view-patient" 
                                                    style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3); font-size: 0.85rem;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#patientModal" 
                                                    data-patient-key="{{ $key }}"
                                                    data-patient-name="{{ $group['patient']->name }}"
                                                    data-patient-age="{{ $group['patient']->age }}"
                                                    data-patient-gender="{{ $group['patient']->gender }}">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                            
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="showing-entries">
                        Showing <span id="showing-count">{{ count($patientGroups) }}</span> of {{ count(array_unique($records->pluck('patient_key')->toArray())) }} patients
                    </div>
                    <div class="table-pagination">
                        <button class="btn btn-sm btn-outline-secondary me-1" id="prev-page" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="current-page">1</span> / <span id="total-pages">1</span>
                        <button class="btn btn-sm btn-outline-secondary ms-1" id="next-page" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Patient Details Modal -->
                <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white;">
                                <h5 class="modal-title" id="patientModalLabel" style="color: #fff">Patient Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Patient Name</h6>
                                            <p class="patient-name fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Age</h6>
                                            <p class="patient-age fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Gender</h6>
                                            <p class="patient-gender fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6 class="mb-3 border-bottom pb-2">Visit History</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="visit-history-table">
                                        <thead>
                                            <tr>
                                                <th>Visit #</th>
                                                <th>Date</th>
                                                <th>Symptoms</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="visit-history-body">
                                            <!-- Visit history will be populated dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div id="visit-details-section" class="mt-4" style="display: none;">
                                    <h6 class="mb-3 border-bottom pb-2">Visit Details</h6>
                                    <div id="visit-details-content">
                                        <!-- Visit details will be populated dynamically -->
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <a href="{{ route('ask-ai') }}" id="new-visit-btn" class="btn btn-primary-custom">
                                    <i class="fas fa-plus me-1"></i> New Visit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-user-md"></i>
                    <h5>No patients yet</h5>
                    <p>Start by adding your first patient</p>
                    <a href="{{ route('ask-ai') }}" class="btn-primary-custom mt-3">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all charts
    initializeCharts();
    
    // Set up event listeners for filters
    setupFilters();
    
    // Set up table sorting and pagination
    setupTableFunctionality();
    
    // Set up export functionality
    setupExportFunctionality();
});

// Main chart initialization function
function initializeCharts() {
    try {
        // Use pre-calculated chart data from controller
        const chartLabels = @json($chartLabels ?? []);
        const chartData = @json($chartData ?? []);
        const records = @json($records ?? []);
        
        // Initialize the main charts
        initializeVisitsTimelineChart(chartLabels, chartData);
        initializeDemographicsChart(records);
        initializeAgeDistributionChart(records);
        
        // Only render original chart if canvas element exists (for backward compatibility)
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
        console.error('Error rendering dashboard charts:', error);
        // Hide chart container if there's an error
        const chartContainers = document.querySelectorAll('.chart-card');
        chartContainers.forEach(container => {
            container.style.display = 'none';
        });
    }
}

// Initialize the visits timeline chart
function initializeVisitsTimelineChart(labels, data) {
    const canvas = document.getElementById('visitsTimelineChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Destroy any existing chart instance
    if (window.visitsTimelineChart) {
        window.visitsTimelineChart.destroy();
    }
    
    // Create new chart
    window.visitsTimelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'New Patients',
                data: data.map((val, i) => Math.round(val * 0.7)), // Simulate new vs. returning
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3498db',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                borderWidth: 2
            }, {
                label: 'Return Visits',
                data: data.map((val, i) => Math.round(val * 0.3)), // Simulate new vs. returning
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#e74c3c',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#DE6262',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + ' patients';
                        }
                    }
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
                    },
                    title: {
                        display: true,
                        text: 'Number of Patients',
                        color: '#6c757d'
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Initialize the demographics pie chart
function initializeDemographicsChart(records) {
    const canvas = document.getElementById('demographicsChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Count male and female patients
    const maleCount = records.filter(r => r.gender === 'male').length;
    const femaleCount = records.filter(r => r.gender === 'female').length;
    
    // Destroy any existing chart instance
    if (window.demographicsChart) {
        window.demographicsChart.destroy();
    }
    
    // Create new chart
    window.demographicsChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [maleCount, femaleCount],
                backgroundColor: ['#3498db', '#e74c3c'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            const total = maleCount + femaleCount;
                            const percentage = Math.round((context.raw / total) * 100);
                            return context.label + ': ' + context.raw + ' patients (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

// Initialize the age distribution chart
function initializeAgeDistributionChart(records) {
    const canvas = document.getElementById('ageDistributionChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Group patients by age ranges
    const ageGroups = {
        '0-18': 0,
        '19-35': 0,
        '36-50': 0,
        '51-65': 0,
        '66+': 0
    };
    
    records.forEach(record => {
        const age = parseInt(record.age);
        if (age <= 18) ageGroups['0-18']++;
        else if (age <= 35) ageGroups['19-35']++;
        else if (age <= 50) ageGroups['36-50']++;
        else if (age <= 65) ageGroups['51-65']++;
        else ageGroups['66+']++;
    });
    
    // Destroy any existing chart instance
    if (window.ageDistributionChart) {
        window.ageDistributionChart.destroy();
    }
    
    // Create new chart
    window.ageDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(ageGroups),
            datasets: [{
                label: 'Patients',
                data: Object.values(ageGroups),
                backgroundColor: [
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(231, 76, 60, 0.7)'
                ],
                borderColor: [
                    'rgba(46, 204, 113, 1)',
                    'rgba(52, 152, 219, 1)',
                    'rgba(155, 89, 182, 1)',
                    'rgba(241, 196, 15, 1)',
                    'rgba(231, 76, 60, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return 'Patients: ' + context.raw;
                        }
                    }
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
                    },
                    title: {
                        display: true,
                        text: 'Number of Patients',
                        color: '#6c757d'
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    title: {
                        display: true,
                        text: 'Age Groups',
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Set up filter functionality
function setupFilters() {
    const dateRangeSelect = document.getElementById('date-range-select');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const customDateRangeFields = document.querySelectorAll('.custom-date-range');
    const filterForm = document.getElementById('stats-filter-form');
    
    // Show/hide custom date range fields based on selection
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRangeFields.forEach(field => field.style.display = 'block');
            } else {
                customDateRangeFields.forEach(field => field.style.display = 'none');
            }
        });
    }
    
    // Set default dates for custom range
    if (dateFrom && dateTo) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        dateTo.valueAsDate = today;
        dateFrom.valueAsDate = thirtyDaysAgo;
    }
    
    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Here you would typically make an AJAX request to get filtered data
            // For this demo, we'll just show a success message
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Applied!</strong> Data has been updated.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
        
        // Handle filter form reset
        filterForm.addEventListener('reset', function() {
            // Reset custom date range visibility
            customDateRangeFields.forEach(field => field.style.display = 'none');
            dateRangeSelect.value = '30';
            
            // Show reset message
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Reset!</strong> Showing default data.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
    }
    
    // Set up refresh stats button
    const refreshStatsBtn = document.getElementById('refresh-stats');
    if (refreshStatsBtn) {
        refreshStatsBtn.addEventListener('click', function() {
            // Show loading spinner
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...';
            this.disabled = true;
            
            // Simulate refresh delay
            setTimeout(() => {
                // Reset button
                this.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Refresh';
                this.disabled = false;
                
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <strong>Data Refreshed!</strong> Statistics are now up-to-date.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(toast);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(toast);
                    bsAlert.close();
                }, 3000);
            }, 1500);
        });
    }
}

// Set up table sorting and pagination
function setupTableFunctionality() {
    const table = document.getElementById('patients-table');
    const searchInput = document.getElementById('patient-search');
    const searchBtn = document.getElementById('search-btn');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const currentPageSpan = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const showingCountSpan = document.getElementById('showing-count');
    
    if (!table) return;
    
    // Variables for pagination
    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredRows = Array.from(table.querySelectorAll('tbody tr'));
    let totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    
    // Update pagination display
    function updatePagination() {
        if (!currentPageSpan || !totalPagesSpan || !showingCountSpan) return;
        
        currentPageSpan.textContent = currentPage;
        totalPagesSpan.textContent = totalPages;
        
        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, filteredRows.length);
        showingCountSpan.textContent = filteredRows.length > 0 ? `${startIdx + 1}-${endIdx}` : '0';
        
        // Enable/disable pagination buttons
        if (prevPageBtn) prevPageBtn.disabled = currentPage === 1;
        if (nextPageBtn) nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
    }
    
    // Display rows for current page
    function displayRows() {
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));
        
        // Hide all rows
        allRows.forEach(row => row.style.display = 'none');
        
        // Show only rows for current page
        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, filteredRows.length);
        
        for (let i = startIdx; i < endIdx; i++) {
            filteredRows[i].style.display = '';
        }
        
        updatePagination();
    }
    
    // Filter rows based on search input
    function filterRows(searchTerm) {
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));
        
        if (!searchTerm) {
            filteredRows = allRows;
        } else {
            searchTerm = searchTerm.toLowerCase();
            filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
        }
        
        totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        currentPage = 1; // Reset to first page after filtering
        
        displayRows();
    }
    
    // Sort table by column
    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Sort rows
        rows.sort((a, b) => {
            let aValue, bValue;
            
            switch (column) {
                case 'name':
                    aValue = a.cells[0].textContent;
                    bValue = b.cells[0].textContent;
                    break;
                case 'age':
                    aValue = parseInt(a.cells[1].textContent) || 0;
                    bValue = parseInt(b.cells[1].textContent) || 0;
                    break;
                case 'gender':
                    aValue = a.cells[2].textContent.trim();
                    bValue = b.cells[2].textContent.trim();
                    break;
                case 'visits':
                    aValue = parseInt(a.getAttribute('data-visits')) || 0;
                    bValue = parseInt(b.getAttribute('data-visits')) || 0;
                    break;
                case 'last-visit':
                    aValue = parseInt(a.getAttribute('data-last-visit')) || 0;
                    bValue = parseInt(b.getAttribute('data-last-visit')) || 0;
                    break;
                default:
                    return 0;
            }
            
            if (direction === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        // Reappend rows in sorted order
        rows.forEach(row => tbody.appendChild(row));
        
        // Update filtered rows and display
        filteredRows = Array.from(tbody.querySelectorAll('tr'));
        displayRows();
    }
    
    // Set up event listeners
    
    // Search functionality
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            filterRows(searchInput.value);
        });
        
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                filterRows(this.value);
            }
        });
    }
    
    // Pagination
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayRows();
            }
        });
    }
    
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                displayRows();
            }
        });
    }
    
    // Sorting
    const sortLinks = table.querySelectorAll('.sort-link');
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const column = this.getAttribute('data-sort');
            const currentDirection = this.getAttribute('data-direction') || 'desc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            
            // Update direction attribute
            sortLinks.forEach(l => l.setAttribute('data-direction', ''));
            this.setAttribute('data-direction', newDirection);
            
            // Update sort icons
            sortLinks.forEach(l => {
                l.querySelector('i').className = 'fas fa-sort';
            });
            this.querySelector('i').className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
            
            // Sort the table
            sortTable(column, newDirection);
        });
    });
    
    // Initialize display
    updatePagination();
    displayRows();
    
    // Set up patient filter functionality
    setupPatientFilters();
    
    // Set up patient modal functionality
    setupPatientModal();
}

// Set up patient filters
function setupPatientFilters() {
    const dateRangeSelect = document.getElementById('patient-date-range');
    const dateFrom = document.getElementById('patient-date-from');
    const dateTo = document.getElementById('patient-date-to');
    const customDateFields = document.querySelectorAll('.patient-custom-date');
    const filterForm = document.getElementById('patient-filter-form');
    const genderFilter = document.getElementById('patient-gender-filter');
    const ageFilter = document.getElementById('patient-age-filter');
    const visitFilter = document.getElementById('patient-visit-filter');
    
    // Show/hide custom date range fields based on selection
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateFields.forEach(field => field.style.display = 'block');
            } else {
                customDateFields.forEach(field => field.style.display = 'none');
            }
        });
    }
    
    // Set default dates for custom range
    if (dateFrom && dateTo) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        dateTo.valueAsDate = today;
        dateFrom.valueAsDate = thirtyDaysAgo;
    }
    
    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const table = document.getElementById('patients-table');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Apply filters
            rows.forEach(row => {
                let showRow = true;
                
                // Gender filter
                if (genderFilter && genderFilter.value !== 'all') {
                    const gender = row.cells[2].textContent.trim().toLowerCase();
                    if (!gender.includes(genderFilter.value.toLowerCase())) {
                        showRow = false;
                    }
                }
                
                // Age filter
                if (showRow && ageFilter && ageFilter.value !== 'all') {
                    const age = parseInt(row.cells[1].textContent) || 0;
                    const [minAge, maxAge] = ageFilter.value.split('-');
                    
                    if (minAge && maxAge) {
                        if (age < parseInt(minAge) || age > parseInt(maxAge)) {
                            showRow = false;
                        }
                    } else if (minAge && minAge.includes('+')) {
                        const min = parseInt(minAge);
                        if (age < min) {
                            showRow = false;
                        }
                    }
                }
                
                // Visit count filter
                if (showRow && visitFilter && visitFilter.value !== 'all') {
                    const visitCount = parseInt(row.getAttribute('data-visits')) || 0;
                    
                    if (visitFilter.value === '1' && visitCount !== 1) {
                        showRow = false;
                    } else if (visitFilter.value === 'multiple' && visitCount <= 1) {
                        showRow = false;
                    }
                }
                
                // Date range filter
                if (showRow && dateRangeSelect) {
                    const lastVisitTimestamp = parseInt(row.getAttribute('data-last-visit')) || 0;
                    const lastVisitDate = new Date(lastVisitTimestamp * 1000);
                    const today = new Date();
                    
                    if (dateRangeSelect.value === 'custom') {
                        // Custom date range
                        if (dateFrom && dateTo) {
                            const fromDate = new Date(dateFrom.value);
                            const toDate = new Date(dateTo.value);
                            toDate.setHours(23, 59, 59, 999); // End of day
                            
                            if (lastVisitDate < fromDate || lastVisitDate > toDate) {
                                showRow = false;
                            }
                        }
                    } else if (dateRangeSelect.value !== 'all') {
                        // Predefined date range
                        const days = parseInt(dateRangeSelect.value);
                        const cutoffDate = new Date();
                        cutoffDate.setDate(today.getDate() - days);
                        
                        if (lastVisitDate < cutoffDate) {
                            showRow = false;
                        }
                    }
                }
                
                // Show or hide row
                row.style.display = showRow ? '' : 'none';
            });
            
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Applied!</strong> Patient list has been filtered.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
        
        // Handle filter form reset
        filterForm.addEventListener('reset', function() {
            // Reset custom date range visibility
            customDateFields.forEach(field => field.style.display = 'none');
            dateRangeSelect.value = 'all';
            
            // Reset table to show all rows
            const table = document.getElementById('patients-table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => row.style.display = '');
            }
            
            // Show reset message
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Reset!</strong> Showing all patients.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
    }
}

// Set up patient modal functionality
function setupPatientModal() {
    // Get all patient records from PHP
    const allRecords = @json($records ?? []);
    
    // Patient modal elements
    const patientModal = document.getElementById('patientModal');
    const patientNameEl = document.querySelector('.patient-name');
    const patientAgeEl = document.querySelector('.patient-age');
    const patientGenderEl = document.querySelector('.patient-gender');
    const visitHistoryBody = document.getElementById('visit-history-body');
    const visitDetailsSection = document.getElementById('visit-details-section');
    const visitDetailsContent = document.getElementById('visit-details-content');
    const newVisitBtn = document.getElementById('new-visit-btn');
    
    // View patient buttons
    const viewPatientBtns = document.querySelectorAll('.btn-view-patient');
    
    if (!patientModal) return;
    
    // Handle patient modal opening
    viewPatientBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const patientKey = this.getAttribute('data-patient-key');
            const patientName = this.getAttribute('data-patient-name');
            const patientAge = this.getAttribute('data-patient-age');
            const patientGender = this.getAttribute('data-patient-gender');
            
            // Set patient info in modal
            if (patientNameEl) patientNameEl.textContent = patientName;
            if (patientAgeEl) patientAgeEl.textContent = patientAge;
            if (patientGenderEl) patientGenderEl.textContent = patientGender.charAt(0).toUpperCase() + patientGender.slice(1);
            
            // Set new visit button link with the correct patient key
            if (newVisitBtn) {
                newVisitBtn.href = `{{ route('ask-ai') }}?patient_key=${encodeURIComponent(patientKey)}`;
            }
            
            // Clear previous visit history
            if (visitHistoryBody) visitHistoryBody.innerHTML = '';
            
            // Hide visit details section
            if (visitDetailsSection) visitDetailsSection.style.display = 'none';
            
            // Find all visits for this patient
            const patientVisits = allRecords.filter(record => {
                const recordKey = record.patient_key || (record.name + '-' + record.age + '-' + record.gender);
                return recordKey === patientKey;
            });
            
            // Sort visits by date (newest first)
            patientVisits.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            
            // Populate visit history table
            patientVisits.forEach((visit, index) => {
                const visitNumber = visit.visit_number || (patientVisits.length - index);
                const visitDate = new Date(visit.created_at);
                const formattedDate = visitDate.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Get symptoms (if available)
                let symptomsText = 'N/A';
                if (visit.symptoms) {
                    try {
                        const symptoms = JSON.parse(visit.symptoms);
                        if (Array.isArray(symptoms) && symptoms.length > 0) {
                            symptomsText = symptoms.join(', ');
                        } else if (typeof symptoms === 'string') {
                            symptomsText = symptoms;
                        }
                    } catch (e) {
                        symptomsText = visit.symptoms;
                    }
                }
                
                // Truncate symptoms if too long
                if (symptomsText.length > 50) {
                    symptomsText = symptomsText.substring(0, 50) + '...';
                }
                
                // Create row
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${visitNumber}</td>
                    <td>${formattedDate}</td>
                    <td>${symptomsText}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary view-visit-details" data-visit-id="${visit.id}">
                            <i class="fas fa-file-medical me-1"></i> Details
                        </button>
                    </td>
                `;
                
                visitHistoryBody.appendChild(row);
            });
            
            // Add event listeners to view visit details buttons
            const viewVisitDetailsBtns = visitHistoryBody.querySelectorAll('.view-visit-details');
            viewVisitDetailsBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const visitId = this.getAttribute('data-visit-id');
                    const visit = allRecords.find(record => record.id == visitId);
                    
                    if (!visit) return;
                    
                    // Show visit details section
                    if (visitDetailsSection) visitDetailsSection.style.display = 'block';
                    
                    // Format visit details
                    let detailsHTML = `
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong>Visit #${visit.visit_number || '1'} - ${new Date(visit.created_at).toLocaleDateString()}</strong>
                            </div>
                            <div class="card-body">
                    `;
                    
                    // Add symptoms with better formatting
                    detailsHTML += '<h6 class="mb-2">Symptoms:</h6>';
                    if (visit.symptoms) {
                        try {
                            let symptoms = JSON.parse(visit.symptoms);
                            
                            // Handle different formats of symptoms data
                            if (Array.isArray(symptoms) && symptoms.length > 0) {
                                // Check if symptoms are IDs or actual symptom names
                                const areNumeric = symptoms.every(s => !isNaN(parseInt(s)));
                                
                                if (areNumeric) {
                                    // These are likely symptom IDs - we need to convert them to names
                                    // Since we don't have direct access to the symptom names here,
                                    // we'll display a more user-friendly message
                                    detailsHTML += `
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            ${symptoms.length} symptom(s) recorded. View full details in patient record.
                                        </div>
                                    `;
                                } else {
                                    // These are actual symptom names
                                    detailsHTML += '<div class="symptom-tags mb-3">';
                                    symptoms.forEach(symptom => {
                                        detailsHTML += `<span class="symptom-tag">${symptom}</span>`;
                                    });
                                    detailsHTML += '</div>';
                                }
                            } else if (typeof symptoms === 'string') {
                                detailsHTML += `<p class="mb-3">${symptoms}</p>`;
                            } else {
                                detailsHTML += '<p class="mb-3">No symptoms recorded</p>';
                            }
                        } catch (e) {
                            // If we can't parse the JSON, display as plain text
                            // But first check if it looks like a list of numbers
                            if (/^\d+(\s*,\s*\d+)*$/.test(visit.symptoms)) {
                                detailsHTML += `
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Multiple symptoms recorded. View full details in patient record.
                                    </div>
                                `;
                            } else {
                                detailsHTML += `<p class="mb-3">${visit.symptoms}</p>`;
                            }
                        }
                    } else {
                        detailsHTML += '<p class="mb-3">No symptoms recorded</p>';
                    }
                    
                    // Add test results if available
                    if (visit.test_results) {
                        detailsHTML += '<h6 class="mb-2">Test Results:</h6>';
                        detailsHTML += `<p class="mb-3">${visit.test_results}</p>`;
                    }
                    
                    // Add AI analysis if available with better formatting
                    if (visit.ai_response) {
                        detailsHTML += '<h6 class="mb-2">AI Analysis:</h6>';
                        
                        // Instead of using regex which can be unreliable for medical text,
                        // we'll use a more structured approach with DOM manipulation
                        
                        // Create a container for the AI response
                        detailsHTML += '<div class="ai-response mb-4">';
                        
                        // Split the response by sections (A, B, C, D)
                        const sections = visit.ai_response.split(/([A-D]\)\s*[A-Z\s]+:)/g);
                        
                        // Process each section
                        for (let i = 0; i < sections.length; i++) {
                            const section = sections[i].trim();
                            
                            if (!section) continue;
                            
                            // Check if this is a section header (A, B, C, D)
                            if (/^[A-D]\)\s*[A-Z\s]+:/.test(section)) {
                                detailsHTML += `<div class="analysis-section-header">${section}</div>`;
                            } else {
                                // This is section content
                                // Format percentages
                                let formattedContent = section.replace(/(\d+)%/g, '<span class="analysis-percentage">$1%</span>');
                                
                                // Add the content
                                detailsHTML += `<div class="analysis-content">${formattedContent}</div>`;
                            }
                        }
                        
                        // Close the AI response container
                        detailsHTML += '</div>';
                    }
                    
                    // Add notes if available
                    if (visit.notes) {
                        detailsHTML += '<h6 class="mb-2">Notes:</h6>';
                        detailsHTML += `<p class="mb-3">${visit.notes}</p>`;
                    }
                    
                    // Close card
                    detailsHTML += `
                            </div>
                        </div>
                    `;
                    
                    // Set visit details content
                    if (visitDetailsContent) visitDetailsContent.innerHTML = detailsHTML;
                    
                    // Scroll to visit details
                    visitDetailsSection.scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    });
}

// Set up export functionality
function setupExportFunctionality() {
    const exportCsvBtn = document.getElementById('export-csv');
    const exportPdfBtn = document.getElementById('export-pdf');
    
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportTableToCSV('patient_data.csv');
        });
    }
    
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportToPDF();
        });
    }
    
    // Export table to CSV
    function exportTableToCSV(filename) {
        const table = document.getElementById('cases-table');
        if (!table) return;
        
        const rows = table.querySelectorAll('tr');
        const csvContent = [];
        
        // Get headers
        const headers = [];
        const headerCells = rows[0].querySelectorAll('th');
        headerCells.forEach(cell => {
            // Get text without the sort icon
            let headerText = cell.textContent.replace(/[▲▼]/g, '').trim();
            if (headerText.includes('ID')) headerText = 'ID';
            if (headerText.includes('Patient Name')) headerText = 'Patient Name';
            if (headerText.includes('Age')) headerText = 'Age';
            if (headerText.includes('Gender')) headerText = 'Gender';
            if (headerText.includes('Visit')) headerText = 'Visit Number';
            if (headerText.includes('Date')) headerText = 'Date';
            
            headers.push(headerText);
        });
        
        // Remove the Actions column
        headers.pop();
        csvContent.push(headers.join(','));
        
        // Get data rows
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.querySelectorAll('td');
            const rowData = [];
            
            // Skip the Actions column
            for (let j = 0; j < cells.length - 1; j++) {
                let cellText = cells[j].textContent.trim();
                
                // Clean up ID column
                if (j === 0) cellText = cellText.replace('#', '');
                
                // Clean up Gender column
                if (j === 3) cellText = cellText.replace(/\s+/g, '');
                
                // Add quotes if the cell contains commas
                if (cellText.includes(',')) {
                    cellText = `"${cellText}"`;
                }
                
                rowData.push(cellText);
            }
            
            csvContent.push(rowData.join(','));
        }
        
        // Create and download CSV file
        const csvData = csvContent.join('\n');
        const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Export dashboard to PDF
    function exportToPDF() {
        // Show loading message
        const loadingToast = document.createElement('div');
        loadingToast.className = 'alert alert-info position-fixed';
        loadingToast.style.top = '20px';
        loadingToast.style.right = '20px';
        loadingToast.style.zIndex = '9999';
        loadingToast.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';
        document.body.appendChild(loadingToast);
        
        setTimeout(() => {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                
                // Add title
                doc.setFontSize(18);
                doc.text('Medical Dashboard Report', 105, 15, { align: 'center' });
                
                // Add date
                doc.setFontSize(10);
                doc.text('Generated on: ' + new Date().toLocaleDateString(), 105, 22, { align: 'center' });
                
                // Add statistics
                doc.setFontSize(14);
                doc.text('Patient Statistics', 14, 35);
                
                doc.setFontSize(10);
                doc.text('Total Patients: ' + document.querySelector('.stats-number').textContent, 14, 45);
                
                // Add recent cases table
                doc.setFontSize(14);
                doc.text('Recent Patient Cases', 14, 60);
                
                // Convert table to image and add to PDF
                const table = document.getElementById('cases-table');
                if (table) {
                    html2canvas(table).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const imgWidth = 180;
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        
                        doc.addImage(imgData, 'PNG', 14, 70, imgWidth, imgHeight);
                        
                        // Save the PDF
                        doc.save('medical_dashboard_report.pdf');
                        
                        // Remove loading message
                        document.body.removeChild(loadingToast);
                        
                        // Show success message
                        const successToast = document.createElement('div');
                        successToast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                        successToast.style.top = '20px';
                        successToast.style.right = '20px';
                        successToast.style.zIndex = '9999';
                        successToast.innerHTML = `
                            <strong>PDF Generated!</strong> Your report has been downloaded.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.body.appendChild(successToast);
                        
                        // Auto-dismiss after 3 seconds
                        setTimeout(() => {
                            const bsAlert = new bootstrap.Alert(successToast);
                            bsAlert.close();
                        }, 3000);
                    });
                } else {
                    // If table doesn't exist, just save the PDF with statistics
                    doc.save('medical_dashboard_report.pdf');
                    
                    // Remove loading message
                    document.body.removeChild(loadingToast);
                    
                    // Show success message
                    const successToast = document.createElement('div');
                    successToast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    successToast.style.top = '20px';
                    successToast.style.right = '20px';
                    successToast.style.zIndex = '9999';
                    successToast.innerHTML = `
                        <strong>PDF Generated!</strong> Your report has been downloaded.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.body.appendChild(successToast);
                    
                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        const bsAlert = new bootstrap.Alert(successToast);
                        bsAlert.close();
                    }, 3000);
                }
            } catch (error) {
                console.error('Error generating PDF:', error);
                
                // Remove loading message
                document.body.removeChild(loadingToast);
                
                // Show error message
                const errorToast = document.createElement('div');
                errorToast.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                errorToast.style.top = '20px';
                errorToast.style.right = '20px';
                errorToast.style.zIndex = '9999';
                errorToast.innerHTML = `
                    <strong>Error!</strong> Failed to generate PDF. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(errorToast);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(errorToast);
                    bsAlert.close();
                }, 3000);
            }
        }, 500);
    }
}
</script>
@endpush