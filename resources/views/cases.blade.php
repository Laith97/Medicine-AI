@extends('master')

@section('title', 'Patients Page')

@section('content')
@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
    .cases-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .cases-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
        position: relative;
        overflow: hidden;
    }

    .cases-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        opacity: 0.1;
        transform: skewX(-15deg);
    }

    .cases-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 2rem;
        color: white;
    }

    .cases-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 25px;
        box-shadow: 0 15px 50px rgba(44, 62, 80, 0.1);
        border: none;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .cases-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 60px rgba(44, 62, 80, 0.15);
    }

    .cases-card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-bottom: none;
        position: relative;
    }

    .cases-card-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
    }

    .cases-card-body {
        padding: 2rem;
    }

    .btn-add-patient {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.6rem 1.5rem;
        border-radius: 25px;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-add-patient:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
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
        transition: all 0.3s ease;
        background: white;
    }

    .custom-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(222, 98, 98, 0.05) 0%, rgba(222, 98, 98, 0.02) 100%);
        transform: scale(1.01);
    }

    .custom-table tbody td {
        padding: 1rem;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }

    .btn-view-response {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
        border: none;
        color: white;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3);
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }

    .btn-view-response:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(222, 98, 98, 0.4);
        background: linear-gradient(135deg, #c55252 0%, #b04848 100%);
        color: white;
    }

    /* DataTables Styling */
    .dataTables_filter input {
        border-radius: 12px !important;
        border: 2px solid #e9ecef !important;
        padding: 0.5rem 1rem !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s ease !important;
    }

    .dataTables_filter input:focus {
        border-color: #DE6262 !important;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.15) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #DE6262 !important;
        border-radius: 8px !important;
        margin: 0 2px !important;
        transition: all 0.3s ease !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%) !important;
        color: white !important;
        border: none !important;
    }

    .dataTables_wrapper .dataTables_length select {
        border-radius: 8px !important;
        border: 2px solid #e9ecef !important;
        padding: 0.25rem 0.5rem !important;
    }

    .dataTables_wrapper .dataTables_info {
        color: #6c757d !important;
        font-weight: 500 !important;
    }

    /* Modal Styling */
    .modal-xl {
        max-width: 95vw;
    }

    .response-modal-content {
        border-radius: 25px;
        box-shadow: 0 20px 60px rgba(44, 62, 80, 0.2);
        overflow: hidden;
        border: none;
    }

    .response-modal-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: #fff;
        padding: 2rem 2.5rem;
        border-bottom: none;
        position: relative;
    }

    .response-modal-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);
    }

    .response-modal-body {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        padding: 2.5rem;
        max-height: 70vh;
        overflow-y: auto;
        font-size: 1rem;
        line-height: 1.8;
        letter-spacing: 0.3px;
    }
    
    /* Enhanced styling for the entire popup content */
    .response-modal-body p {
        margin-bottom: 1rem;
        color: #2c3e50;
        font-size: 1.05rem;
    }
    
    /* No special styling for notes or medications - keep it simple like the summary */
    
    /* Patient Summary Styles */
    .ai-summary {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .ai-summary h4 {
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }
    
    .ai-summary ul {
        padding-left: 20px;
        margin-bottom: 15px;
    }
    
    .ai-summary li {
        margin-bottom: 8px;
    }
    
    .patient-summary-btn {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }
    
    .patient-summary-btn:hover {
        background-color: #138496;
        border-color: #117a8b;
        color: white;
    }

    .response-text {
        white-space: pre-wrap;
        word-break: break-word;
        font-family: "Segoe UI", Roboto, sans-serif;
        font-size: 1.05rem;
        color: #2c3e50;
        line-height: 1.8;
        padding: 10px;
    }
    
    /* Apply AI summary styling to response text */
    .response-text h1, .response-text h2, .response-text h3, .response-text h4 {
        color: #2c3e50;
        margin-top: 25px;
        margin-bottom: 15px;
        font-weight: 600;
        border-bottom: 2px solid #DE6262;
        padding-bottom: 10px;
        font-size: 1.4rem;
    }
    
    /* Simple styling for section headers like A) POSSIBLE DIAGNOSIS */
    .response-text p strong, .ai-content p strong {
        display: block;
        font-size: 1.25rem;
        color: #2c3e50;
        margin-top: 25px;
        margin-bottom: 15px;
        font-weight: 600;
        border-left: 4px solid #DE6262;
        padding: 8px 0 8px 15px;
        background-color: rgba(222, 98, 98, 0.05);
        border-radius: 0 5px 5px 0;
    }
    
    /* Specific styling for different section headers */
    .section-diagnosis {
        border-left-color: #DE6262; /* Red for diagnosis */
        background-color: rgba(222, 98, 98, 0.05);
    }
    
    .section-recommendations {
        border-left-color: #3498db; /* Blue for recommendations */
        background-color: rgba(52, 152, 219, 0.05);
    }
    
    .section-treatment {
        border-left-color: #2ecc71; /* Green for treatment */
        background-color: rgba(46, 204, 113, 0.05);
    }
    
    .section-warnings {
        border-left-color: #f39c12; /* Orange for warnings */
        background-color: rgba(243, 156, 18, 0.05);
    }
    
    .response-text p {
        margin-bottom: 12px;
        padding: 0 5px;
    }
    
    .response-text ul, .response-text ol {
        padding-left: 25px;
        margin-bottom: 20px;
        background-color: rgba(236, 240, 241, 0.3);
        padding-top: 10px;
        padding-bottom: 10px;
        border-radius: 5px;
    }
    
    .response-text li {
        margin-bottom: 10px;
        padding-left: 5px;
    }
    
    .response-text li:last-child {
        margin-bottom: 0;
    }
    
    /* Enhanced AI content styling */
    .ai-content {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        border: 1px solid rgba(0,0,0,0.05);
        line-height: 1.8;
    }
    
    /* Style for headings in AI content */
    .ai-content h1, .ai-content h2, .ai-content h3, .ai-content h4 {
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }
    
    /* Style for lists in AI content */
    .ai-content ul, .ai-content ol {
        padding-left: 20px;
        margin-bottom: 15px;
    }
    
    .ai-content li {
        margin-bottom: 8px;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 5rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
    }

    .empty-state h5 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    /* Recent Patients Section */
    .recent-patients-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(44, 62, 80, 0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .recent-patients-card .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        border-radius: 15px 15px 0 0;
    }
    
    .recent-patients-card .badge {
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 30px;
    }
    
    .recent-patient-item {
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .recent-patient-item:hover {
        background-color: rgba(222, 98, 98, 0.03);
    }
    
    .col-lg-2-4 {
        flex: 0 0 auto;
        width: 20%;
    }
    
    .btn-sm.btn-view-response {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Improved DataTables styling */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        min-width: 250px;
    }
    
    .dataTables_wrapper .dataTables_length select {
        min-width: 80px;
    }
    
    .dataTables_processing {
        background: rgba(255,255,255,0.9) !important;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        border-radius: 10px;
        padding: 15px !important;
        z-index: 100;
    }

    @media (max-width: 992px) {
        .col-lg-2-4 {
            width: 33.33%;
        }
    }

    @media (max-width: 768px) {
        .cases-header h5 {
            font-size: 1.5rem;
        }
        
        .cases-card-body {
            padding: 1rem;
        }
        
        .col-lg-2-4 {
            width: 50%;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            min-width: 180px;
        }
    }
    
    @media (max-width: 576px) {
        .col-lg-2-4 {
            width: 100%;
        }
    }
</style>
@endpush

<div class="cases-container">
    <div class="container-fluid">
        @php
            $hasRecords = $records->count() > 0;
        @endphp
        
        <!-- Cases Header -->
        <div class="cases-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5>Patient Records</h5>
                    <p class="mb-0 opacity-75">Manage and view all patient cases</p>
                </div>
                <a href="{{ route('ask-ai') }}" class="btn-add-patient">
                    <i class="fas fa-plus me-2"></i>Add New Patient
                </a>
            </div>
        </div>
        
        <!-- Recent Patients Section -->
        @if($hasRecords)
        <div class="recent-patients-card mb-4">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Patients</h6>
                <span class="badge bg-primary">Last 5 patients</span>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    @foreach($records->sortByDesc('created_at')->take(5) as $recentRecord)
                    <div class="col-md-4 col-lg-2-4 border-end border-bottom">
                        <div class="recent-patient-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 text-truncate" style="max-width: 150px;">{{ $recentRecord->name }}</h6>
                                <span class="badge bg-light text-dark">{{ $recentRecord->gender }}</span>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="fas fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($recentRecord->created_at)->format('M d, Y') }}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark">{{ $recentRecord->age }} years</span>
                                <button class="btn btn-sm btn-view-response" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#responseModal" 
                                        data-record-id="{{ $recentRecord->id }}"
                                        data-patient-name="{{ $recentRecord->name }}"
                                        data-patient-key="{{ $recentRecord->patient_key }}"
                                        style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%); border: none; color: white; font-weight: 500; padding: 0.25rem 0.75rem; border-radius: 15px; box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3); font-size: 0.75rem;">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        
        <!-- Cases Card -->
        <div class="cases-card">
            <div class="cases-card-body">
                @if($hasRecords)
                    <div class="table-responsive">
                        <table id="recordsTable" class="table custom-table align-middle w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Patient Name</th>
                                    <th class="text-center">Age</th>
                                    <th class="text-center">Gender</th>
                                    <th class="text-center">Height</th>
                                    <th class="text-center">Weight</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Visit #</th>
                                    <th class="text-center">Recommendations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($records as $record)
                                <tr class="text-center">
                                    <td><strong>#{{ $record->id }}</strong></td>
                                    <td>
                                        {{ $record->name }}
                                        @if($record->total_visits > 1)
                                            <span class="badge bg-info ms-1" title="This patient has multiple visits">
                                                <i class="fas fa-history me-1"></i>{{ $record->total_visits }} visits
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $record->age }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $record->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white; border-radius: 15px; padding: 0.4rem 0.8rem;">
                                            {{ ucfirst($record->gender) }}
                                        </span>
                                    </td>
                                    <td>{{ $record->height ?? 'N/A' }}</td>
                                    <td>{{ $record->weight ?? 'N/A' }}</td>
                                    <td>{{ $record->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">Visit #{{ $record->visit_number ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn view-response-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#responseModal"
                                                    data-response="{{ htmlentities($record->ai_response) }}"
                                                    data-patient-name="{{ $record->name }}"
                                                    data-visit-number="{{ $record->visit_number ?? 1 }}"
                                                    data-record-id="{{ $record->id }}"
                                                    data-patient-key="{{ $record->patient_key }}"
                                                    style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(222, 98, 98, 0.3); font-size: 0.85rem; margin-right: 5px;">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                            <button class="btn patient-summary-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#summaryModal"
                                                    data-patient-name="{{ $record->name }}"
                                                    data-patient-age="{{ $record->age }}"
                                                    data-patient-gender="{{ $record->gender }}"
                                                    data-patient-key="{{ $record->patient_key }}"
                                                    style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3); font-size: 0.85rem; margin-right: 5px;">
                                                <i class="fas fa-history me-1"></i>Summary
                                            </button>
                                            <a href="{{ route('ask-ai', ['edit_patient' => $record->id]) }}" class="btn" 
                                               style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border: none; color: white; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px; box-shadow: 0 2px 8px rgba(243, 156, 18, 0.3); font-size: 0.85rem;">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-user-md"></i>
                        <h5>No Patient Records Found</h5>
                        <p>Start building your patient database by adding your first case</p>
                        <a href="{{ route('ask-ai') }}" class="btn-add-patient mt-3">
                            <i class="fas fa-plus me-2"></i>Add First Patient
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: #fff">
                    <i class="fas fa-stethoscope me-2"></i><span id="patientNameTitle">Medical Recommendations</span>
                    <span id="visitBadge" class="badge bg-light text-dark ms-2" style="display: none;">Visit #<span id="visitNumber"></span></span>
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printResponseBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <div id="patientHistorySection" class="mb-4" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-history me-2"></i>Patient Visit History</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="patientHistoryList" class="d-flex flex-wrap gap-2 mb-3">
                        <!-- Visit history buttons will be inserted here -->
                    </div>
                </div>
                
                <div class="response-block">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-robot me-2"></i>AI Recommendations</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div class="ai-summary" style="background-color: #f8f9fa; border-radius: 15px; padding: 20px; box-shadow: 0 3px 15px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05);">
                        <div id="openaiReply" class="response-text"></div>
                        
                        <!-- Sources Section -->
                        <div id="sourcesCitation" class="mt-4" style="display: none;">
                            <div class="d-flex align-items-center mb-3">
                                <h6 class="mb-0 me-2"><i class="fas fa-book me-2"></i>Referenced From</h6>
                                <hr class="flex-grow-1 ms-2">
                            </div>
                            <div id="sourcesContent" class="sources-list">
                                <!-- Source logos will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Patient Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="summaryModalLabel" style="color: #fff">
                    <i class="fas fa-user-md me-2"></i><span id="patientSummaryTitle">Patient Summary</span>
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printSummaryBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <!-- Patient Info Section -->
                <div class="patient-info-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-id-card me-2"></i>Patient Information</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Name:</strong> <span id="summaryPatientName"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Age:</strong> <span id="summaryPatientAge"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Gender:</strong> <span id="summaryPatientGender"></span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Visit Summary Section -->
                <div class="visit-summary-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-clipboard-list me-2"></i>Visit Summary</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="visitSummaryContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading patient history...</p>
                        </div>
                    </div>
                </div>
                
                <!-- AI Generated Summary Section -->
                <div class="ai-summary-section">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-robot me-2"></i>AI Generated Summary</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="aiSummaryContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Generating AI summary...</p>
                        </div>
                    </div>
                    
                    <!-- Sources Section for Summary -->
                    <div id="summarySourcesCitation" class="mt-4" style="display: none;">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0 me-2"><i class="fas fa-book me-2"></i>Sources</h6>
                            <hr class="flex-grow-1 ms-2">
                        </div>
                        <div id="summarySourcesContent" class="sources-list p-3 bg-light border rounded">
                            <!-- Sources will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        const hasRecords = @json($hasRecords);

        if (hasRecords) {
            $('#recordsTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                deferRender: true,
                stateSave: true,
                stateDuration: 60 * 60 * 24, // 1 day
                language: {
                    search: "🔍 Search:",
                    lengthMenu: "Show _MENU_ patients",
                    info: "Showing _START_ to _END_ of _TOTAL_ patients",
                    paginate: {
                        previous: "← Prev",
                        next: "Next →"
                    },
                    emptyTable: "No records available",
                    zeroRecords: "No matching records found",
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                },
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex"f>>rtip',
                order: [[6, 'desc']], // Sort by date column (index 6) by default
                responsive: true,
                autoWidth: false,
                // No custom initialization needed
                initComplete: function() {
                    // Gender filter has been removed
                }
            });
        }

        // Store all records for quick access
        const allRecords = @json($records);
        
        // Debug: Log all records to check patient_key values
        console.log('All patient records:', allRecords.map(r => ({ 
            id: r.id, 
            name: r.name, 
            patient_key: r.patient_key,
            visit_number: r.visit_number
        })));
        
        // Common function to handle both Recent Patients and main table view buttons
        function handleViewResponse(element) {
            const raw = $(element).data('response') || '';
            const patientName = $(element).data('patient-name');
            const visitNumber = $(element).data('visit-number') || 1;
            const recordId = $(element).data('record-id');
            const patientKey = $(element).data('patient-key');
            
            // For Recent Patients buttons, we need to get the response from the record
            if (!raw) {
                // Find the record by ID
                const record = allRecords.find(r => r.id === recordId);
                if (record && record.ai_response) {
                    // Use the response from the record
                    processResponse(record.ai_response, patientName, visitNumber, recordId, patientKey);
                    return;
                }
            }
            
            // For main table buttons with response data
            processResponse(raw, patientName, visitNumber, recordId, patientKey);
        }
        
        // Process and display the response
        function processResponse(raw, patientName, visitNumber, recordId, patientKey) {
            // Update the modal title and content
            $('#patientNameTitle').text(patientName);
            $('#visitNumber').text(visitNumber);
            $('#visitBadge').show();
            
            // Format the AI response with proper HTML
            let decodedResponse = decodeHtml(raw);
            
            // Remove intro and conclusion sections
            decodedResponse = removeIntroAndConclusion(decodedResponse);
            console.log('Raw AI response (after removing intro/conclusion):', decodedResponse);
            
            // Check if the response contains section headers
            const diagnosisMatch = decodedResponse.match(/[A-Z]\)?\s+.*?DIAGNOSIS.*?/i);
            const recommendationsMatch = decodedResponse.match(/[A-Z]\)?\s+.*?RECOMMENDATIONS.*?/i);
            const treatmentMatch = decodedResponse.match(/[A-Z]\)?\s+.*?TREATMENT.*?/i);
            const warningsMatch = decodedResponse.match(/[A-Z]\)?\s+.*?WARNINGS.*?/i);
            
            // Check for exact format mentioned
            const exactFormatMatch = decodedResponse.match(/A\)\s+POSSIBLE\s+DIAGNOSIS|B\)\s+RECOMMENDATIONS\s+FOR\s+TESTS|C\)\s+TREATMENT\s+RECOMMENDATIONS|D\)\s+WARNINGS/i);
            
            console.log('Section headers found:', {
                diagnosis: diagnosisMatch ? diagnosisMatch[0] : null,
                recommendations: recommendationsMatch ? recommendationsMatch[0] : null,
                treatment: treatmentMatch ? treatmentMatch[0] : null,
                warnings: warningsMatch ? warningsMatch[0] : null,
                exactFormat: exactFormatMatch ? exactFormatMatch[0] : null
            });
            
            const formattedResponse = formatAIResponse(decodedResponse);
            console.log('Formatted response:', formattedResponse);
            
            $('#openaiReply').html(formattedResponse);
            
            // Extract and display sources if they exist
            const sourcesMatch = decodedResponse.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
            if (sourcesMatch && sourcesMatch[1].trim()) {
                const sourcesContent = sourcesMatch[1].trim();
                $('#sourcesContent').html(formatSources(sourcesContent));
                $('#sourcesCitation').show();
            } else {
                $('#sourcesCitation').hide();
            }
            
            // Get patient age and gender for history
            let patientAge, patientGender;
            
            // Try to find the record in the table
            const tableRow = $(`#recordsTable tr td:contains(${recordId})`).closest('tr');
            if (tableRow.length) {
                patientAge = parseInt(tableRow.find('td:eq(2)').text());
                patientGender = tableRow.find('td:eq(3)').text().trim().toLowerCase();
            } else {
                // If not found in table, try to find in allRecords
                const record = allRecords.find(r => r.id === recordId);
                if (record) {
                    patientAge = record.age;
                    patientGender = record.gender;
                }
            }
            
            console.log('Looking for patient records with:', { patientName, patientAge, patientGender, patientKey });
            
            // Find all records for this patient using multiple methods
            let patientRecords = [];
            
            // Try using patient_key first if available
            if (patientKey) {
                patientRecords = allRecords.filter(record => record.patient_key === patientKey);
                console.log(`Found ${patientRecords.length} records using patient_key`);
            }
            
            // If no records found or patient_key not available, fall back to name-age-gender
            if (patientRecords.length === 0) {
                patientRecords = allRecords.filter(record => 
                    record.name === patientName && 
                    record.age === patientAge &&
                    record.gender === patientGender
                );
                console.log(`Found ${patientRecords.length} records using name-age-gender`);
            }
            
            // If there are multiple visits, show the history section
            if (patientRecords.length > 1) {
                $('#patientHistorySection').show();
                $('#patientHistoryList').empty();
                
                // Sort records by visit number (ascending) or created_at if visit_number is not available
                patientRecords.sort((a, b) => {
                    // If both records have visit_number, use that
                    if (a.visit_number && b.visit_number) {
                        return a.visit_number - b.visit_number;
                    }
                    // Otherwise fall back to created_at date
                    return new Date(a.created_at) - new Date(b.created_at);
                });
                
                // Add buttons for each visit
                patientRecords.forEach(record => {
                    const isActive = record.id === recordId;
                    const visitDate = new Date(record.created_at).toLocaleDateString('en-US', {
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric'
                    });
                    
                    // Sort visits chronologically (visit #1 should be the oldest)
                    
                    const button = $(`
                        <button class="btn ${isActive ? 'btn-primary' : 'btn-outline-secondary'} btn-sm history-btn" 
                                data-record-id="${record.id}"
                                data-response="${htmlEntities(record.ai_response)}"
                                data-visit-number="${record.visit_number}"
                                data-patient-key="${record.patient_key}">
                            <i class="fas ${isActive ? 'fa-calendar-check' : 'fa-calendar'} me-1"></i>
                            Visit #${record.visit_number || '?'} (${visitDate})
                            ${isActive ? '<span class="ms-1 badge bg-light text-dark">Current</span>' : ''}
                        </button>
                    `);
                    
                    $('#patientHistoryList').append(button);
                });
            } else {
                $('#patientHistorySection').hide();
            }
        }
        
        // Attach event handler to both Recent Patients and main table view buttons
        $('.view-response-btn, .btn-sm.btn-view-response').on('click', function() {
            handleViewResponse(this);
        });
        
        // We've replaced the legacy handler with the unified one above
        // No need for duplicate code here
        
        // Handle clicks on history buttons
        $(document).on('click', '.history-btn', function() {
            const recordId = $(this).data('record-id');
            const response = $(this).data('response');
            const visitNumber = $(this).data('visit-number');
            
            // Update the content with formatted HTML
            let rawResponse = decodeHtml(response);
            
            // Remove intro and conclusion sections
            rawResponse = removeIntroAndConclusion(rawResponse);
            console.log('History button - Raw response (after removing intro/conclusion):', rawResponse);
            
            // Check if the response contains section headers
            const diagnosisMatch = rawResponse.match(/[A-Z]\)?\s+.*?DIAGNOSIS.*?/i);
            const recommendationsMatch = rawResponse.match(/[A-Z]\)?\s+.*?RECOMMENDATIONS.*?/i);
            const treatmentMatch = rawResponse.match(/[A-Z]\)?\s+.*?TREATMENT.*?/i);
            const warningsMatch = rawResponse.match(/[A-Z]\)?\s+.*?WARNINGS.*?/i);
            
            // Check for exact format mentioned
            const exactFormatMatch = rawResponse.match(/A\)\s+POSSIBLE\s+DIAGNOSIS|B\)\s+RECOMMENDATIONS\s+FOR\s+TESTS|C\)\s+TREATMENT\s+RECOMMENDATIONS|D\)\s+WARNINGS/i);
            
            console.log('History button - Section headers found:', {
                diagnosis: diagnosisMatch ? diagnosisMatch[0] : null,
                recommendations: recommendationsMatch ? recommendationsMatch[0] : null,
                treatment: treatmentMatch ? treatmentMatch[0] : null,
                warnings: warningsMatch ? warningsMatch[0] : null,
                exactFormat: exactFormatMatch ? exactFormatMatch[0] : null
            });
            
            const formattedResponse = formatAIResponse(rawResponse);
            console.log('History button - Formatted response:', formattedResponse);
            
            $('#openaiReply').html(formattedResponse);
            $('#visitNumber').text(visitNumber);
            
            // Update active button
            $('.history-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
            $('.history-btn .badge').remove();
            $('.history-btn i').removeClass('fa-calendar-check').addClass('fa-calendar');
            
            $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
            $(this).find('i').removeClass('fa-calendar').addClass('fa-calendar-check');
            if (!$(this).find('.badge').length) {
                $(this).append('<span class="ms-1 badge bg-light text-dark">Current</span>');
            }
        });

        function decodeHtml(html) {
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }
        
        function htmlEntities(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
        
        /**
         * Format AI response text with proper HTML formatting
         */
        function formatAIResponse(text) {
            if (!text) return '';
            
            // Remove the Sources section from the text before formatting
            const sourcesMatch = text.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
            let cleanedText = text;
            
            if (sourcesMatch) {
                cleanedText = text.replace(sourcesMatch[0], '').trim();
            }
            
            // First, enhance the main section headers to make them more prominent
            // This will convert A) POSSIBLE DIAGNOSIS: etc. to proper h4 headers
            const enhancedText = cleanedText
                .replace(/^(A\)\s*POSSIBLE\s*DIAGNOSIS:.*$)/gm, '<h4 class="mt-4 section-diagnosis">$1</h4>')
                .replace(/^(B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS\s*OR\s*IMAGING:.*$)/gm, '<h4 class="mt-4 section-recommendations">$1</h4>')
                .replace(/^(C\)\s*TREATMENT\s*RECOMMENDATIONS:.*$)/gm, '<h4 class="mt-4 section-treatment">$1</h4>')
                .replace(/^(D\)\s*WARNING\s*SIGNS:.*$)/gm, '<h4 class="mt-4 section-warnings">$1</h4>');
            
            // Split the text into lines
            let lines = enhancedText.split('\n');
            let formatted = '';
            let inList = false;
            let listType = '';
            
            // Process each line
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                
                // Skip processing if line is already an HTML header (from our replacement above)
                if (line.startsWith('<h4')) {
                    if (inList) {
                        formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        inList = false;
                    }
                    formatted += line;
                    continue;
                }
                
                // Check for headers (# Header)
                if (/^#{1,6}\s+(.+)$/.test(line)) {
                    if (inList) {
                        formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        inList = false;
                    }
                    const headerLevel = line.match(/^(#{1,6})\s+/)[1].length;
                    const headerText = line.replace(/^#{1,6}\s+(.+)$/, '$1');
                    formatted += `<h${headerLevel}>${headerText}</h${headerLevel}>`;
                }
                // Check for bullet points (* Item or - Item or • Item)
                else if (/^[\s]*[\*\-•]\s+(.+)$/.test(line)) {
                    if (!inList || listType !== 'ul') {
                        if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        formatted += '<ul class="mb-3">';
                        inList = true;
                        listType = 'ul';
                    }
                    const itemText = line.replace(/^[\s]*[\*\-•]\s+(.+)$/, '$1');
                    formatted += `<li>${itemText}</li>`;
                }
                // Check for numbered lists (1. Item)
                else if (/^[\s]*\d+\.\s+(.+)$/.test(line)) {
                    if (!inList || listType !== 'ol') {
                        if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        formatted += '<ol class="mb-3">';
                        inList = true;
                        listType = 'ol';
                    }
                    const itemText = line.replace(/^[\s]*\d+\.\s+(.+)$/, '$1');
                    formatted += `<li>${itemText}</li>`;
                }
                // Regular text
                else {
                    if (inList) {
                        formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        inList = false;
                    }
                    
                    // Skip empty lines
                    if (line.trim() === '') {
                        formatted += '<br>';
                        continue;
                    }
                    
                    // Check for section headers with multiple patterns
                    const diagnosisPattern = /(DIAGNOS[IE]S|POSSIBLE\s+DIAGNOS[IE]S|DIFFERENTIAL\s+DIAGNOS[IE]S)/i;
                    const recommendationsPattern = /(RECOMMENDATIONS|RECOMMENDATIONS\s+FOR\s+TESTS|SUGGESTED\s+TESTS)/i;
                    const treatmentPattern = /(TREATMENT|TREATMENT\s+RECOMMENDATIONS|TREATMENT\s+PLAN|MANAGEMENT)/i;
                    const warningsPattern = /(WARNINGS|PRECAUTIONS|RED\s+FLAGS|FOLLOW\-UP)/i;
                    
                    if (/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS|PRECAUTIONS|MANAGEMENT|FOLLOW).*?$/i.test(line) || 
                        /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS|PRECAUTIONS|MANAGEMENT|FOLLOW).*?$/i.test(line) ||
                        /^[A-Z]\)\s+(POSSIBLE\s+DIAGNOS[IE]S|RECOMMENDATIONS\s+FOR\s+TESTS|TREATMENT\s+RECOMMENDATIONS|WARNINGS|PRECAUTIONS)$/i.test(line)) {
                        
                        let className = '';
                        
                        if (diagnosisPattern.test(line)) {
                            className = 'section-diagnosis';
                        } else if (recommendationsPattern.test(line)) {
                            className = 'section-recommendations';
                        } else if (treatmentPattern.test(line)) {
                            className = 'section-treatment';
                        } else if (warningsPattern.test(line)) {
                            className = 'section-warnings';
                        }
                        
                        formatted += `<h4 class="mt-4 ${className}">${line}</h4>`;
                    } 
                    // Check for subsection headers (often in ALL CAPS or with trailing colon)
                    else if (/^[A-Z][A-Z\s\d\-\(\)]{5,}:?$/.test(line)) {
                        formatted += `<p><strong style="font-size: 1.15rem; color: #34495e;">${line}</strong></p>`;
                    }
                    else {
                        // All other text is formatted as regular paragraphs
                        formatted += `<p>${line}</p>`;
                    }
                }
            }
            
            // Close any open lists
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
            }
            
            // Process inline formatting
            
            // Bold text between ** or __
            formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            formatted = formatted.replace(/__(.+?)__/g, '<strong>$1</strong>');
            
            // Italic text between * or _
            formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>');
            
            // Highlight important information
            formatted = formatted.replace(/\!\!(.+?)\!\!/g, '<span style="background-color: #ffffcc; padding: 0 3px;">$1</span>');
            
            // Add some spacing between sections for better readability
            formatted = formatted.replace(/<\/h[1-6]>/g, '$&<div style="height: 10px;"></div>');
            
            // Enhance the styling of the main sections
            formatted = formatted.replace(/<h4 class="mt-4 section-diagnosis">/g, 
                '<h4 class="mt-4 section-diagnosis" style="color: #DE6262; border-left: 4px solid #DE6262; padding: 8px 0 8px 15px; background-color: rgba(222, 98, 98, 0.05); border-radius: 0 5px 5px 0;">');
                
            formatted = formatted.replace(/<h4 class="mt-4 section-recommendations">/g, 
                '<h4 class="mt-4 section-recommendations" style="color: #3498db; border-left: 4px solid #3498db; padding: 8px 0 8px 15px; background-color: rgba(52, 152, 219, 0.05); border-radius: 0 5px 5px 0;">');
                
            formatted = formatted.replace(/<h4 class="mt-4 section-treatment">/g, 
                '<h4 class="mt-4 section-treatment" style="color: #2ecc71; border-left: 4px solid #2ecc71; padding: 8px 0 8px 15px; background-color: rgba(46, 204, 113, 0.05); border-radius: 0 5px 5px 0;">');
                
            formatted = formatted.replace(/<h4 class="mt-4 section-warnings">/g, 
                '<h4 class="mt-4 section-warnings" style="color: #f39c12; border-left: 4px solid #f39c12; padding: 8px 0 8px 15px; background-color: rgba(243, 156, 18, 0.05); border-radius: 0 5px 5px 0;">');
            
            return formatted;
        }
        
        // Handle patient summary button click
        $('.patient-summary-btn').on('click', function() {
            const patientName = $(this).data('patient-name');
            const patientAge = $(this).data('patient-age');
            const patientGender = $(this).data('patient-gender');
            const patientKey = $(this).data('patient-key');
            
            // Update patient info in the summary modal
            $('#summaryPatientName').text(patientName);
            $('#summaryPatientAge').text(patientAge);
            $('#summaryPatientGender').text(patientGender.charAt(0).toUpperCase() + patientGender.slice(1));
            $('#patientSummaryTitle').text(`${patientName}'s Medical Summary`);
            
            // Reset containers
            $('#visitSummaryContainer').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading patient history...</p>
                </div>
            `);
            
            $('#aiSummaryContainer').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Generating AI summary...</p>
                </div>
            `);
            
            // Find all records for this patient
            let patientRecords = [];
            
            // Try using patient_key first if available
            if (patientKey) {
                patientRecords = allRecords.filter(record => record.patient_key === patientKey);
                console.log(`Found ${patientRecords.length} records using patient_key`);
            }
            
            // If no records found or patient_key not available, fall back to name-age-gender
            if (patientRecords.length === 0) {
                patientRecords = allRecords.filter(record => 
                    record.name === patientName && 
                    record.age === patientAge &&
                    record.gender === patientGender
                );
                console.log(`Found ${patientRecords.length} records using name-age-gender`);
            }
            
            // Sort records by visit number or date
            patientRecords.sort((a, b) => {
                if (a.visit_number && b.visit_number) {
                    return a.visit_number - b.visit_number;
                }
                return new Date(a.created_at) - new Date(b.created_at);
            });
            
            // Generate visit summary HTML
            if (patientRecords.length > 0) {
                let visitSummaryHtml = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Visit #</th>
                                    <th>Date</th>
                                    <th>Key Findings</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                patientRecords.forEach(record => {
                    const visitDate = new Date(record.created_at).toLocaleDateString('en-US', {
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric'
                    });
                    
                    // Extract first 100 characters of AI response as summary
                    const responseSummary = record.ai_response ? 
                        record.ai_response.substring(0, 100) + (record.ai_response.length > 100 ? '...' : '') : 
                        'No response available';
                    
                    visitSummaryHtml += `
                        <tr>
                            <td>Visit #${record.visit_number || '?'}</td>
                            <td>${visitDate}</td>
                            <td>${responseSummary}</td>
                        </tr>
                    `;
                });
                
                visitSummaryHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#visitSummaryContainer').html(visitSummaryHtml);
                
                // Generate AI summary
                generateAISummary(patientRecords);
            } else {
                $('#visitSummaryContainer').html('<div class="alert alert-info">No visit history found for this patient.</div>');
                $('#aiSummaryContainer').html('<div class="alert alert-info">Cannot generate summary without patient history.</div>');
            }
        });
        
        // Function to generate AI summary from patient records
        function generateAISummary(patientRecords) {
            // Prepare the data for the AI summary
            const summaryData = {
                patient_name: $('#summaryPatientName').text(),
                patient_age: $('#summaryPatientAge').text(),
                patient_gender: $('#summaryPatientGender').text().toLowerCase(),
                visit_count: patientRecords.length,
                visits: patientRecords.map(record => ({
                    visit_number: record.visit_number || 'unknown',
                    date: new Date(record.created_at).toLocaleDateString('en-US', {
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric'
                    }),
                    ai_response: record.ai_response || 'No response available'
                }))
            };
            
            // Make AJAX request to generate summary
            $.ajax({
                url: '{{ route("patient.summary") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    summary_data: JSON.stringify(summaryData)
                },
                success: function(response) {
                    if (response.success) {
                        // Process the summary to remove Patient Information section
                        let summaryHtml = response.summary;
                        
                        // Extract the HTML content
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = summaryHtml;
                        
                        // Get the AI content div
                        const aiContentDiv = tempDiv.querySelector('.ai-content');
                        if (aiContentDiv) {
                            // Get the HTML content as text
                            let aiContentText = aiContentDiv.innerHTML;
                            
                            // Check if there's a Current Symptoms section before removing Patient Information
                            let currentSymptoms = null;
                            const currentSymptomsRegex = /<p>Current\s+Symptoms:[\s\S]*?(?=<p>A\)\s*POSSIBLE\s*DIAGNOSIS|<\/div>)/i;
                            const currentSymptomsMatch = aiContentText.match(currentSymptomsRegex);
                            
                            if (currentSymptomsMatch) {
                                currentSymptoms = currentSymptomsMatch[0];
                                console.log('Found Current Symptoms in summary:', currentSymptoms);
                            }
                            
                            // Remove Patient Information section using regex
                            const patientInfoRegex = /<p>Patient Information:[\s\S]*?<p>---<\/p>/i;
                            aiContentText = aiContentText.replace(patientInfoRegex, '');
                            
                            // Also check for the specific format with Age, Gender, Total Visits
                            const patientDetailsRegex = /<p>Age:[\s\S]*?<p>Gender:[\s\S]*?<p>Total Visits:[\s\S]*?<\/p>/i;
                            aiContentText = aiContentText.replace(patientDetailsRegex, '');
                            
                            // If we found Current Symptoms and it was removed, add it back at the beginning
                            if (currentSymptoms && !aiContentText.includes(currentSymptoms)) {
                                aiContentText = currentSymptoms + aiContentText;
                            }
                            
                            // Update the AI content div
                            aiContentDiv.innerHTML = aiContentText;
                            
                            // Get the updated HTML
                            summaryHtml = tempDiv.innerHTML;
                        }
                        
                        $('#aiSummaryContainer').html(summaryHtml);
                        
                        // Extract and display sources if they exist
                        const sourcesMatch = response.summary.match(/Sources:([\s\S]*?)(?:$|(?=\n\n\w))/i);
                        if (sourcesMatch && sourcesMatch[1].trim()) {
                            const sourcesContent = sourcesMatch[1].trim();
                            $('#summarySourcesContent').html(formatSources(sourcesContent));
                            $('#summarySourcesCitation').show();
                        } else {
                            $('#summarySourcesCitation').hide();
                        }
                    } else {
                        $('#aiSummaryContainer').html(`
                            <div class="alert alert-warning">
                                ${response.message || 'Failed to generate summary.'}
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    console.error('Error generating summary:', xhr);
                    $('#aiSummaryContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error generating summary. Please try again later.
                        </div>
                    `);
                }
            });
        }
        
        /**
         * Remove Patient Information section from the AI response
         */
        function removePatientInfoSection(text) {
            // Check if the text contains a Patient Information section
            const patientInfoRegex = /Patient Information:[\s\S]*?---/i;
            const match = text.match(patientInfoRegex);
            
            if (match) {
                // Remove the entire section including the separator line
                text = text.replace(match[0], '');
                
                // Clean up any extra newlines that might be left
                text = text.replace(/\n{3,}/g, '\n\n');
            }
            
            // Also check for the specific format with Age, Gender, Total Visits
            const patientDetailsRegex = /Age:\s*\d+\s*\n+Gender:\s*[a-zA-Z]+\s*\n+Total Visits:\s*\d+/i;
            const detailsMatch = text.match(patientDetailsRegex);
            
            if (detailsMatch) {
                // Remove this section as well
                text = text.replace(detailsMatch[0], '');
                
                // Clean up any extra newlines that might be left
                text = text.replace(/\n{3,}/g, '\n\n');
            }
            
            return text;
        }
        
        /**
         * Remove introduction and conclusion sections from the AI response
         */
        function removeIntroAndConclusion(text) {
            // First remove Patient Information section
            text = removePatientInfoSection(text);
            
            // Check if there's a Current Symptoms section before processing
            let currentSymptoms = null;
            const currentSymptomsRegex = /Current\s+Symptoms:.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS:?|$)/is;
            const currentSymptomsMatch = text.match(currentSymptomsRegex);
            
            if (currentSymptomsMatch) {
                currentSymptoms = currentSymptomsMatch[0].trim();
                console.log('Found Current Symptoms section:', currentSymptoms);
            }
            
            // Split the text into lines
            const lines = text.split('\n');
            let startIndex = 0;
            let endIndex = lines.length - 1;
            
            // Find the first section header (likely A) DIAGNOSIS)
            for (let i = 0; i < lines.length; i++) {
                if (/^A\)\s*POSSIBLE\s*DIAGNOSIS/i.test(lines[i]) || 
                    /^A\)\s*DIAGNOS[IE]S/i.test(lines[i]) ||
                    /^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i]) || 
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i])) {
                    startIndex = i;
                    break;
                }
            }
            
            // Find the last section header and include all content after it
            for (let i = lines.length - 1; i >= 0; i--) {
                if (/^D\)\s*WARNING\s*SIGNS/i.test(lines[i]) || 
                    /^D\)\s*WARNINGS/i.test(lines[i]) ||
                    /^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i]) || 
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i])) {
                    // Find the end of this section (next empty line or end of text)
                    for (let j = i + 1; j < lines.length; j++) {
                        // Stop at conclusion or summary
                        if (j === lines.length - 1 || 
                            (lines[j].trim() === '' && j > i + 5) ||
                            /^In\s+summary/i.test(lines[j]) ||
                            /^Summary/i.test(lines[j]) ||
                            /^Conclusion/i.test(lines[j])) {
                            endIndex = j;
                            // If we found a conclusion/summary, don't include it
                            if (/^In\s+summary/i.test(lines[j]) || /^Summary/i.test(lines[j]) || /^Conclusion/i.test(lines[j])) {
                                endIndex = j - 1;
                            }
                            break;
                        }
                    }
                    break;
                }
            }
            
            // Get the content between the first section header and the end of the last section
            let result = lines.slice(startIndex, endIndex + 1).join('\n');
            
            // Do one final check for any patient information that might be in the result
            result = removePatientInfoSection(result);
            
            // If we found Current Symptoms, add it back at the beginning
            if (currentSymptoms) {
                result = currentSymptoms + '\n\n' + result;
            }
            
            return result;
        }
        
        // Sources section has been removed
        
        // Print functionality for response modal
        $('#printResponseBtn').on('click', function() {
            const patientName = $('#patientNameTitle').text();
            const visitNumber = $('#visitNumber').text();
            let responseContent = $('#openaiReply').html();
            const sourcesContent = $('#sourcesCitation').is(':visible') ? $('#sourcesContent').html() : '';
            
            // Improve formatting for print by adding spacing between sections
            responseContent = responseContent
                .replace(/(A\)\s*POSSIBLE\s*DIAGNOSIS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS\s*OR\s*IMAGING:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(C\)\s*TREATMENT\s*RECOMMENDATIONS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(D\)\s*WARNING\s*SIGNS:)/g, '<h4 class="mt-4">$1</h4>');
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Add content to the print window
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Medical Recommendations - ${patientName}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .content { margin-bottom: 30px; line-height: 1.6; }
                        .sources { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                        h4 { color: #2c3e50; margin-top: 25px; margin-bottom: 15px; }
                        ul, ol { margin-bottom: 20px; }
                        li { margin-bottom: 8px; }
                        @media print {
                            .no-print { display: none; }
                            a { text-decoration: none; color: #000; }
                            h4 { page-break-after: avoid; }
                            ul, ol { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Medical Recommendations</h2>
                        <h4>${patientName}</h4>
                        ${visitNumber ? `<p>Visit #${visitNumber}</p>` : ''}
                        <p>${new Date().toLocaleDateString()}</p>
                    </div>
                    
                    <div class="content">
                        ${responseContent}
                    </div>
                    
                    ${sourcesContent ? `
                    <div class="sources">
                        <h5>Sources</h5>
                        ${sourcesContent}
                    </div>
                    ` : ''}
                    
                    <div class="text-center mt-4 no-print">
                        <button class="btn btn-primary" onclick="window.print()">Print</button>
                        <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
                    </div>
                </body>
                </html>
            `);
            
            // Focus the new window
            printWindow.document.close();
            printWindow.focus();
        });
        
        // Print functionality for summary modal
        $('#printSummaryBtn').on('click', function() {
            const patientName = $('#patientSummaryTitle').text();
            const patientInfo = {
                name: $('#summaryPatientName').text(),
                age: $('#summaryPatientAge').text(),
                gender: $('#summaryPatientGender').text(),
                height: $('#summaryPatientHeight').text(),
                weight: $('#summaryPatientWeight').text()
            };
            
            let summaryContent = $('#aiSummaryContainer').html();
            const sourcesContent = $('#summarySourcesCitation').is(':visible') ? $('#summarySourcesContent').html() : '';
            const visitHistoryContent = $('#visitSummaryContainer').html();
            
            // Improve formatting for print by adding spacing between sections
            summaryContent = summaryContent
                .replace(/(A\)\s*POSSIBLE\s*DIAGNOSIS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(B\)\s*RECOMMENDATIONS\s*FOR\s*TESTS\s*OR\s*IMAGING:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(C\)\s*TREATMENT\s*RECOMMENDATIONS:)/g, '<h4 class="mt-4">$1</h4>')
                .replace(/(D\)\s*WARNING\s*SIGNS:)/g, '<h4 class="mt-4">$1</h4>');
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Add content to the print window
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Patient Summary - ${patientInfo.name}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .patient-info { margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
                        .content { margin-bottom: 30px; line-height: 1.6; }
                        .sources { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                        h4 { color: #2c3e50; margin-top: 25px; margin-bottom: 15px; }
                        h5 { color: #2c3e50; margin-top: 30px; margin-bottom: 15px; font-weight: 600; }
                        ul, ol { margin-bottom: 20px; }
                        li { margin-bottom: 8px; }
                        .table { margin-top: 15px; }
                        @media print {
                            .no-print { display: none; }
                            a { text-decoration: none; color: #000; }
                            h4, h5 { page-break-after: avoid; }
                            ul, ol { page-break-inside: avoid; }
                            .table { border-collapse: collapse; }
                            .table td, .table th { border: 1px solid #ddd; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Patient Summary</h2>
                        <h4>${patientInfo.name}</h4>
                        <p>${new Date().toLocaleDateString()}</p>
                    </div>
                    
                    <div class="patient-info">
                        <div class="row">
                            <div class="col-md-4"><strong>Name:</strong> ${patientInfo.name}</div>
                            <div class="col-md-4"><strong>Age:</strong> ${patientInfo.age}</div>
                            <div class="col-md-4"><strong>Gender:</strong> ${patientInfo.gender}</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4"><strong>Height:</strong> ${patientInfo.height}</div>
                            <div class="col-md-4"><strong>Weight:</strong> ${patientInfo.weight}</div>
                        </div>
                    </div>
                    
                    <div class="content">
                        <h5>Visit History</h5>
                        ${visitHistoryContent}
                    </div>
                    
                    <div class="content">
                        <h5>AI Generated Summary</h5>
                        ${summaryContent}
                    </div>
                    
                    <div class="text-center mt-4 no-print">
                        <button class="btn btn-primary" onclick="window.print()">Print</button>
                        <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
                    </div>
                </body>
                </html>
            `);
            
            // Focus the new window
            printWindow.document.close();
            printWindow.focus();
        });
    });
</script>

@endpush
