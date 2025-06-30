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
        font-size: 1rem;
        color: #2c3e50;
        line-height: 1.8;
    }
    
    /* Apply AI summary styling to response text */
    .response-text h1, .response-text h2, .response-text h3, .response-text h4 {
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }
    
    /* Simple styling for section headers like A) POSSIBLE DIAGNOSIS */
    .response-text p strong, .ai-content p strong {
        display: block;
        font-size: 1.1rem;
        color: #2c3e50;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
    }
    
    /* Simple styling for specific section headers - no special effects */
    .section-diagnosis, .section-recommendations, .section-treatment, .section-warnings {
        /* No special styling - keep it simple like the summary */
    }
    
    .response-text ul, .response-text ol {
        padding-left: 20px;
        margin-bottom: 15px;
    }
    
    .response-text li {
        margin-bottom: 8px;
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

    @media (max-width: 768px) {
        .cases-header h5 {
            font-size: 1.5rem;
        }
        
        .cases-card-body {
            padding: 1rem;
        }
    }
</style>
@endpush

<div class="cases-container">
    <div class="container-fluid">
        <!-- Cases Header -->
        <div class="cases-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5>Patient Records</h5>
                    <p class="mb-0 opacity-75">Manage and view all patient cases</p>
                </div>
                <a href="{{ route('ask-openai') }}" class="btn-add-patient">
                    <i class="fas fa-plus me-2"></i>Add New Patient
                </a>
            </div>
        </div>
        
        <!-- Cases Card -->
        <div class="cases-card">
            <div class="cases-card-body">
                @php
                    $hasRecords = $records->count() > 0;
                @endphp

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
                                            <button class="btn btn-view-response view-response-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#responseModal"
                                                    data-response="{{ htmlentities($record->ai_response) }}"
                                                    data-patient-name="{{ $record->name }}"
                                                    data-visit-number="{{ $record->visit_number ?? 1 }}"
                                                    data-record-id="{{ $record->id }}"
                                                    data-patient-key="{{ $record->patient_key }}">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                            <button class="btn btn-info patient-summary-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#summaryModal"
                                                    data-patient-name="{{ $record->name }}"
                                                    data-patient-age="{{ $record->age }}"
                                                    data-patient-gender="{{ $record->gender }}"
                                                    data-patient-key="{{ $record->patient_key }}">
                                                <i class="fas fa-history me-1"></i>Summary
                                            </button>
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
                        <a href="{{ route('ask-openai') }}" class="btn-add-patient mt-3">
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <div class="ai-summary">
                        <div id="openaiReply" class="response-text"></div>
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                pageLength: 10,
                language: {
                    search: "🔍 Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_",
                    paginate: {
                        previous: "← Prev",
                        next: "Next →"
                    },
                    emptyTable: "No records available",
                    zeroRecords: "No matching records found"
                },
                responsive: true,
                autoWidth: false
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
        
        $('.view-response-btn').on('click', function () {
            const raw = $(this).data('response') || 'No response';
            const patientName = $(this).data('patient-name');
            const visitNumber = $(this).data('visit-number');
            const recordId = $(this).data('record-id');
            
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
            
            // Get the patient_key from the current record
            const patientKey = $(this).data('patient-key');
            const patientAge = parseInt($(this).closest('tr').find('td:eq(2)').text());
            const patientGender = $(this).closest('tr').find('td:eq(3)').text().trim().toLowerCase();
            
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
        });
        
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
            
            // Split the text into lines
            let lines = text.split('\n');
            let formatted = '';
            let inList = false;
            let listType = '';
            
            // Process each line
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                
                // Check for headers (# Header)
                if (/^#{1,6}\s+(.+)$/.test(line)) {
                    if (inList) {
                        formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        inList = false;
                    }
                    const headerText = line.replace(/^#{1,6}\s+(.+)$/, '$1');
                    formatted += `<h4>${headerText}</h4>`;
                }
                // Check for bullet points (* Item or - Item)
                else if (/^[\s]*[\*\-]\s+(.+)$/.test(line)) {
                    if (!inList || listType !== 'ul') {
                        if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        formatted += '<ul>';
                        inList = true;
                        listType = 'ul';
                    }
                    const itemText = line.replace(/^[\s]*[\*\-]\s+(.+)$/, '$1');
                    formatted += `<li>${itemText}</li>`;
                }
                // Check for numbered lists (1. Item)
                else if (/^[\s]*\d+\.\s+(.+)$/.test(line)) {
                    if (!inList || listType !== 'ol') {
                        if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                        formatted += '<ol>';
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
                    if (/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(line) || 
                        /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(line) ||
                        /^[A-Z]\)\s+(POSSIBLE\s+DIAGNOS[IE]S|RECOMMENDATIONS\s+FOR\s+TESTS|TREATMENT\s+RECOMMENDATIONS|WARNINGS)$/i.test(line)) {
                        let className = '';
                        if (/DIAGNOS[IE]S/i.test(line)) {
                            className = 'section-diagnosis';
                        } else if (/RECOMMENDATIONS/i.test(line)) {
                            className = 'section-recommendations';
                        } else if (/TREATMENT/i.test(line)) {
                            className = 'section-treatment';
                        } else if (/WARNINGS/i.test(line)) {
                            className = 'section-warnings';
                        }
                        
                        formatted += `<p><strong class="${className}">${line}</strong></p>`;
                    } else {
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
            
            // Section headers are now handled during line processing
            
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
                            
                            // Remove Patient Information section using regex
                            const patientInfoRegex = /<p>Patient Information:[\s\S]*?<p>---<\/p>/i;
                            aiContentText = aiContentText.replace(patientInfoRegex, '');
                            
                            // Update the AI content div
                            aiContentDiv.innerHTML = aiContentText;
                            
                            // Get the updated HTML
                            summaryHtml = tempDiv.innerHTML;
                        }
                        
                        $('#aiSummaryContainer').html(summaryHtml);
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
            
            return text;
        }
        
        /**
         * Remove introduction and conclusion sections from the AI response
         */
        function removeIntroAndConclusion(text) {
            // First remove Patient Information section
            text = removePatientInfoSection(text);
            
            // Split the text into lines
            const lines = text.split('\n');
            let startIndex = 0;
            let endIndex = lines.length - 1;
            
            // Find the first section header (likely A) DIAGNOSIS)
            for (let i = 0; i < lines.length; i++) {
                if (/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i]) || 
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i])) {
                    startIndex = i;
                    break;
                }
            }
            
            // Find the last section header and include all content after it
            for (let i = lines.length - 1; i >= 0; i--) {
                if (/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i]) || 
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS).*?$/i.test(lines[i])) {
                    // Find the end of this section (next empty line or end of text)
                    for (let j = i + 1; j < lines.length; j++) {
                        if (j === lines.length - 1 || (lines[j].trim() === '' && j > i + 5)) {
                            endIndex = j;
                            break;
                        }
                    }
                    break;
                }
            }
            
            // Return only the content between the first section header and the end of the last section
            return lines.slice(startIndex, endIndex + 1).join('\n');
        }
    });
</script>

@endpush
