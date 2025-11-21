@extends('master')

@section('title', 'Patient Management')

@section('content')
<div class="dashboard-header">
    <h2>Patient Management</h2>
    <p>Manage patient records and appointments</p>
</div>

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '👥';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .page-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }

    .page-header h1 {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stats-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        height: 100%;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }

    .category-tabs {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .nav-tabs .nav-link {
        border: none;
        border-radius: 10px;
        margin-right: 0.5rem;
        color: #6c757d;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        background-color: rgba(222, 98, 98, 0.1);
        color: #DE6262;
    }

    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
    }

    .table-custom {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        background: white;
    }

    .table-custom thead th {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem 0.75rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-custom tbody td {
        padding: 1rem 0.75rem;
        border-color: #f1f3f4;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .table-custom tbody tr:hover {
        background-color: rgba(222, 98, 98, 0.03);
    }

    .patient-row {
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .patient-row:hover {
        background-color: rgba(222, 98, 98, 0.05);
    }

    .badge {
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
    }

    .badge-diagnosed {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .badge-pending {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        color: white;
    }

    .badge-scheduled {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
    }

    .btn-custom-primary {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .btn-custom-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
    }

    .btn-custom-secondary {
        background: white;
        border: 2px solid #e9ecef;
        color: #6c757d;
        font-weight: 600;
        padding: 0.4rem 0.8rem;
        border-radius: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 0.8rem;
    }

    .btn-custom-secondary:hover {
        border-color: #DE6262;
        color: #DE6262;
        transform: translateY(-1px);
        text-decoration: none;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
        background: white;
        border-radius: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin: 2rem 0;
    }

    .empty-state i {
        font-size: 5rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-state h5 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .visits-row {
        background-color: #f8f9fa;
    }

    .visits-container {
        padding: 0;
        border-top: 1px solid #e9ecef;
    }

    .visits-section {
        padding: 1.5rem;
        background: white;
        border-radius: 0 0 12px 12px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }

    .visits-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }

    .visits-header h6 {
        color: #2c3e50;
        font-weight: 600;
        margin: 0;
    }

    .visit-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        background: white;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }

    .visit-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .visit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 1px solid #e9ecef;
    }

    .visit-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .visit-number {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
    }

    .visit-date {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .visit-details {
        padding: 1rem;
        background: white;
    }

    .expand-icon {
        transition: transform 0.3s ease;
        font-size: 0.8rem;
    }

    .expand-icon.rotated {
        transform: rotate(180deg);
    }

    .table-pagination {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .table-pagination button {
        padding: 0.25rem 0.5rem;
        border: 1px solid #dee2e6;
        background: white;
        color: #6c757d;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .table-pagination button:hover:not(:disabled) {
        background: #DE6262;
        color: white;
        border-color: #DE6262;
    }

    .table-pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .showing-entries {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 1rem;
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem 0;
        }

        .page-header,
        .category-tabs,
        .stats-card {
            margin: 1rem;
            padding: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }

        .table-custom {
            font-size: 0.85rem;
        }

        .btn-custom-primary,
        .btn-custom-secondary {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .nav-tabs .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .visits-section {
            padding: 1rem;
        }

        .visit-header {
            padding: 0.75rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .visit-info {
            width: 100%;
        }

        .visit-details {
            padding: 0.75rem;
        }
    }
</style>
@endpush

<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                @php
                    $hasRecords = $records->count() > 0;
                @endphp

                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-user-injured me-2"></i>Patient Management</h1>
                            <p class="text-muted mb-0">Manage and view all patient medical records</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn-custom-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="fas fa-filter"></i> Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Subscription Status Notifications -->
                @include('partials.subscription-notifications')

                @if($hasRecords)
                <!-- Patient Categories Overview -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stat-number">
                                @php
                                    // Count distinct patients from combined records
                                    $patientKeys = [];
                                    foreach ($records as $record) {
                                        if (isset($record->patient_key) && $record->patient_key) {
                                            $patientKeys[$record->patient_key] = true;
                                        } elseif (isset($record->patient_id)) {
                                            $patientKeys['diagnosis_' . $record->patient_id] = true;
                                        }
                                    }
                                    echo count($patientKeys);
                                @endphp
                            </div>
                            <div class="stat-label">Total Patients</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stat-number">
                                @php
                                    // Count diagnosed patients (those with AI responses)
                                    $diagnosedCount = 0;
                                    foreach ($records as $record) {
                                        if (!empty($record->ai_response) && $record->ai_response !== 'No diagnosis available') {
                                            $diagnosedCount++;
                                        }
                                    }
                                    echo $diagnosedCount;
                                @endphp
                            </div>
                            <div class="stat-label">Diagnosed</div>
                            <div class="badge badge-diagnosed mt-2">Active Cases</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stat-number">
                                @php
                                    // Count pending diagnosis (records without AI responses or with default text)
                                    $pendingCount = 0;
                                    foreach ($records as $record) {
                                        if (empty($record->ai_response) || $record->ai_response === 'No diagnosis available') {
                                            $pendingCount++;
                                        }
                                    }
                                    echo $pendingCount;
                                @endphp
                            </div>
                            <div class="stat-label">Pending Diagnosis</div>
                            <div class="badge badge-pending mt-2">Awaiting Review</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stat-number">{{ $records->where('created_at', '>=', now()->subDays(7))->count() }}</div>
                            <div class="stat-label">Recent Activity</div>
                            <div class="badge badge-scheduled mt-2">This Week</div>
                        </div>
                    </div>
                </div>

                <!-- Category Tabs -->
                <div class="category-tabs">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Patient Categories</h5>
                    </div>
                    <ul class="nav nav-tabs" id="patientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-patients" type="button" role="tab" aria-controls="all-patients" aria-selected="true">
                                <i class="fas fa-users me-1"></i>All Patients
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="diagnosed-tab" data-bs-toggle="tab" data-bs-target="#diagnosed-patients" type="button" role="tab" aria-controls="diagnosed-patients" aria-selected="false">
                                <i class="fas fa-check-circle me-1"></i>Diagnosed
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-patients" type="button" role="tab" aria-controls="pending-patients" aria-selected="false">
                                <i class="fas fa-clock me-1"></i>Pending Diagnosis
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduled-patients" type="button" role="tab" aria-controls="scheduled-patients" aria-selected="false">
                                <i class="fas fa-calendar-alt me-1"></i>Scheduled
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Patient Tables -->
                <div class="tab-content" id="patientTabContent">
                    <!-- All Patients Tab -->
                    <div class="tab-pane fade show active" id="all-patients" role="tabpanel" aria-labelledby="all-tab">
                        @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'all'])
                    </div>

                    <!-- Diagnosed Patients Tab -->
                    <div class="tab-pane fade" id="diagnosed-patients" role="tabpanel" aria-labelledby="diagnosed-tab">
                        @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'diagnosed'])
                    </div>

                    <!-- Pending Diagnosis Tab -->
                    <div class="tab-pane fade" id="pending-patients" role="tabpanel" aria-labelledby="pending-tab">
                        @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'pending'])
                    </div>

                    <!-- Scheduled Patients Tab -->
                    <div class="tab-pane fade" id="scheduled-patients" role="tabpanel" aria-labelledby="scheduled-tab">
                        @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'scheduled'])
                    </div>
                </div>
                @else
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-user-injured"></i>
                    <h5>No Patient Records Found</h5>
                    <p>You haven't created any patient records yet. Start by adding a new patient analysis or diagnosis.</p>
                    <a href="{{ route('openai.form') }}" class="btn-custom-primary">
                        <i class="fas fa-plus me-2"></i>Add New Patient
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('cases.partials.modals')

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    // Tab filtering functionality
    $('.nav-link').on('click', function() {
        const category = $(this).attr('id').replace('-tab', '');
        filterPatientsByCategory(category);
    });

    // Patient row expansion
    $(document).on('click', '.btn-expand-visits', function() {
        const patientKey = $(this).data('patient-key');
        const visitsRow = $(`.visits-row[data-patient-key="${patientKey}"]`);
        const expandIcon = $(this).find('.expand-icon');

        if (visitsRow.is(':visible')) {
            visitsRow.slideUp(300);
            expandIcon.removeClass('rotated');
        } else {
            visitsRow.slideDown(300);
            expandIcon.addClass('rotated');
        }
    });

    // Visit details expansion
    $(document).on('click', '.btn-expand-visit', function() {
        const visitId = $(this).data('visit-id');
        const visitDetails = $(`.visit-item[data-visit-id="${visitId}"] .visit-details`);
        const expandIcon = $(this).find('.visit-expand-icon');

        if (visitDetails.is(':visible')) {
            visitDetails.slideUp(300);
            expandIcon.removeClass('rotated');
        } else {
            visitDetails.slideDown(300);
            expandIcon.addClass('rotated');
            loadVisitDetails(visitId, $(this).data());
        }
    });

    // Schedule appointment functionality
    $(document).on('click', '.btn-schedule-appointment', function() {
        const patientData = {
            name: $(this).data('patient-name'),
            age: $(this).data('patient-age'),
            gender: $(this).data('patient-gender'),
            key: $(this).data('patient-key')
        };

        alert(`Schedule appointment for ${patientData.name} (${patientData.age} years old, ${patientData.gender})`);
    });

    // Show summary functionality
    $(document).on('click', '.btn-show-summary', function() {
        const patientData = {
            name: $(this).data('patient-name'),
            age: $(this).data('patient-age'),
            gender: $(this).data('patient-gender'),
            key: $(this).data('patient-key')
        };

        showPatientSummary(patientData);
    });

    // Search functionality
    $('#patient-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterPatients(searchTerm);
    });

    // Sorting functionality
    $('.sort-link').on('click', function(e) {
        e.preventDefault();
        const sortBy = $(this).data('sort');
        sortPatients(sortBy);
    });
});

function filterPatientsByCategory(category) {
    const rows = $('.patient-row');
    let visibleCount = 0;

    rows.each(function() {
        const rowCategory = $(this).data('category');
        if (category === 'all' || rowCategory === category) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });

    updateShowingCount(visibleCount);
}

function filterPatients(searchTerm) {
    const rows = $('.patient-row:visible');
    let visibleCount = 0;

    rows.each(function() {
        const patientName = $(this).find('td:first').text().toLowerCase();
        if (patientName.includes(searchTerm)) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });

    updateShowingCount(visibleCount);
}

function sortPatients(sortBy) {
    const table = $('.table-custom tbody');
    const rows = table.find('.patient-row').get();

    rows.sort((a, b) => {
        let aVal, bVal;

        switch(sortBy) {
            case 'name':
                aVal = $(a).find('td:first').text().toLowerCase();
                bVal = $(b).find('td:first').text().toLowerCase();
                break;
            case 'age':
                aVal = parseInt($(a).find('td:nth-child(2)').text()) || 0;
                bVal = parseInt($(b).find('td:nth-child(2)').text()) || 0;
                break;
            case 'gender':
                aVal = $(a).find('td:nth-child(3)').text().toLowerCase();
                bVal = $(b).find('td:nth-child(3)').text().toLowerCase();
                break;
            case 'visits':
                aVal = parseInt($(a).data('visits')) || 0;
                bVal = parseInt($(b).data('visits')) || 0;
                break;
            case 'last-visit':
                aVal = parseInt($(a).data('last-visit')) || 0;
                bVal = parseInt($(b).data('last-visit')) || 0;
                break;
            default:
                return 0;
        }

        if (aVal < bVal) return -1;
        if (aVal > bVal) return 1;
        return 0;
    });

    // Re-append sorted rows
    $.each(rows, function(index, row) {
        table.append(row);
    });
}

function updateShowingCount(count) {
    $('.showing-entries').text(`Showing ${count} patients`);
}

function loadVisitDetails(visitId, buttonData) {
    const visitDetailsContent = $(`.visit-item[data-visit-id="${visitId}"] .visit-details-content`);

    // Check if already loaded
    if (visitDetailsContent.find('.diagnosis-content').length > 0) {
        return;
    }

    // Show loading state
    visitDetailsContent.html(`
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading visit details...</p>
        </div>
    `);

    // Make AJAX call to get visit details
    $.ajax({
        url: `/api/doctor/patient-management/visit-history/${visitId}`,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                const diagnosisText = response.visit.diagnosis || 'No diagnosis available';
                const formattedContent = formatAIResponse(diagnosisText);

                visitDetailsContent.html(`
                    <div class="diagnosis-content">
                        <div class="visit-diagnosis-header mb-3">
                            <h6 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Diagnosis Details</h6>
                        </div>
                        <div class="response-text">
                            ${formattedContent}
                        </div>
                    </div>
                `);
            } else {
                visitDetailsContent.html('<div class="alert alert-warning">Failed to load visit details.</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading visit details:', error);
            visitDetailsContent.html('<div class="alert alert-danger">Error loading visit details. Please try again.</div>');
        }
    });
}

function showPatientSummary(patientData) {
    // Show loading modal
    $('#summaryModal').modal('show');

    // Update modal header
    $('#summaryModalLabel').html(`<i class="fas fa-user-doctor me-2"></i>${patientData.name}'s Medical Summary`);

    // Update patient info
    $('#summaryPatientName').text(patientData.name);
    $('#summaryPatientAge').text(patientData.age);
    $('#summaryPatientGender').text(patientData.gender.charAt(0).toUpperCase() + patientData.gender.slice(1));

    // Load summary data
    loadPatientSummary(patientData);
}

function loadPatientSummary(patientData) {
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
            <p class="mt-2">Generating summary...</p>
        </div>
    `);

    // Find patient records
    const allRecords = @json($records);
    let patientRecords = [];

    if (patientData.key) {
        patientRecords = allRecords.filter(record => record.patient_key === patientData.key);
    }
    if (patientRecords.length === 0) {
        patientRecords = allRecords.filter(record =>
            record.name === patientData.name &&
            record.age == patientData.age &&
            record.gender === patientData.gender
        );
    }

    // Sort records by date
    patientRecords.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

    // Generate visit summary
    if (patientRecords.length > 0) {
        let visitHtml = `
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Visit #</th>
                            <th>Date</th>
                            <th>Diagnosis Summary</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        patientRecords.forEach((record, index) => {
            const visitDate = new Date(record.created_at);
            const diagnosisText = record.ai_response || record.diagnosis_text || 'No diagnosis available';
            const diagnosisSummary = diagnosisText.length > 80 ?
                diagnosisText.substring(0, 80) + '...' :
                diagnosisText;

            visitHtml += `
                <tr>
                    <td><span class="badge bg-light text-dark">Visit #${index + 1}</span></td>
                    <td>${visitDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    })}</td>
                    <td class="small">${diagnosisSummary}</td>
                </tr>
            `;
        });

        visitHtml += `
                    </tbody>
                </table>
            </div>
        `;

        $('#visitSummaryContainer').html(visitHtml);

        // Generate AI-powered patient summary
        generatePatientSummary(patientRecords);
    } else {
        $('#visitSummaryContainer').html('<div class="alert alert-info">No visit history found for this patient.</div>');
        $('#aiSummaryContainer').html('<div class="alert alert-info">Cannot generate summary without patient history.</div>');
    }
}

function generatePatientSummary(patientRecords) {
    // Prepare data for AI summary
    const summaryData = {
        patient_id: patientRecords.length > 0 ? patientRecords[0].id : 0,
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
            diagnosis: record.ai_response || record.diagnosis_text || 'No diagnosis available'
        }))
    };

    // Show loading state
    $('#aiSummaryContainer').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Generating AI-powered patient summary...</p>
            <div class="progress mt-3" style="height: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                      role="progressbar" style="width: 100%"></div>
            </div>
        </div>
    `);

    // Call AI summary generation API
    $.ajax({
        url: '/ai/patient-summary',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(summaryData),
        success: function(response) {
            if (response.success) {
                const formattedSummary = formatAIResponse(response.summary);
                $('#aiSummaryContainer').html(`<div class="response-text">${formattedSummary}</div>`);
            } else {
                $('#aiSummaryContainer').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${response.message || 'Failed to generate summary'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            $('#aiSummaryContainer').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Failed to generate AI summary. Please try again.
                </div>
            `);
        }
    });
}

// AI Response formatting function (simplified version)
function formatAIResponse(text) {
    if (!text) return '';

    // Basic formatting for common patterns
    let formatted = text
        .replace(/^📋\s*PATIENT CASE SUMMARY:?$/gm, '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')
        .replace(/^🔬\s*KEY MEDICAL ISSUES IDENTIFIED:?$/gm, '</div></div><div class="medcura-section key-medical-issues"><h4 class="section-header">🔬 KEY MEDICAL ISSUES IDENTIFIED</h4><div class="section-content">')
        .replace(/^📈\s*IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS:?$/gm, '</div></div><div class="medcura-section symptom-trends"><h4 class="section-header">📈 IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS</h4><div class="section-content">')
        .replace(/^💊\s*TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION:?$/gm, '</div></div><div class="medcura-section treatment-effectiveness"><h4 class="section-header">💊 TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION</h4><div class="section-content">')
        .replace(/^🩺\s*RECOMMENDATIONS FOR FUTURE CARE:?$/gm, '</div></div><div class="medcura-section future-care"><h4 class="section-header">🩺 RECOMMENDATIONS FOR FUTURE CARE</h4><div class="section-content">')
        .replace(/^- /gm, '<li class="bullet-item">')
        .replace(/\n\n/g, '</p><p>')
        .replace(/\n/g, '<br>');

    // Close sections
    formatted += '</div></div>';

    return formatted;
}
</script>
@endpush
