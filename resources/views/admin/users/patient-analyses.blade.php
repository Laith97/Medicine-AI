@extends('layouts.admin')

@section('title', 'Patient Analyses for ' . $user->name)

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    .admin-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .admin-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }

    .info-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
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

    .btn-view-details {
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

    .btn-view-details:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(222, 98, 98, 0.4);
        background: linear-gradient(135deg, #c55252 0%, #b04848 100%);
        color: white;
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

    .patient-info-section {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .patient-info-section h4 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 10px;
    }

    .patient-info-section .row {
        margin-bottom: 15px;
    }

    .patient-info-section .col-form-label {
        font-weight: 600;
        color: #6c757d;
    }

    .patient-info-section .form-control-plaintext {
        padding: 0.375rem 0;
        color: #2c3e50;
    }

    .ai-response-section {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
    }

    .ai-response-section h4 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 10px;
    }

    .ai-response-content {
        white-space: pre-wrap;
        word-break: break-word;
        font-family: "Segoe UI", Roboto, sans-serif;
        font-size: 1.05rem;
        color: #2c3e50;
        line-height: 1.8;
        padding: 10px;
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

    /* Responsive Modal Styles */
    @media (max-width: 768px) {
        .modal-dialog.modal-xl {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .response-modal-header {
            padding: 1rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .response-modal-header .modal-title {
            font-size: 1.1rem;
            word-break: break-word;
            hyphens: auto;
            line-height: 1.3;
        }

        .response-modal-header > div {
            align-self: flex-end;
        }

        .response-modal-body {
            padding: 1rem;
        }

        /* Fix text display issues in modal body */
        .response-modal-body .patient-info-section,
        .response-modal-body .ai-response-section,
        .response-modal-body .ai-response-content {
            font-size: 0.9rem !important;
            line-height: 1.5 !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
        }

        .response-modal-body p {
            margin-bottom: 0.8rem !important;
            text-align: left !important;
        }

        .response-modal-body h1,
        .response-modal-body h2,
        .response-modal-body h3,
        .response-modal-body h4,
        .response-modal-body h5,
        .response-modal-body h6 {
            font-size: 1rem !important;
            line-height: 1.3 !important;
            word-break: break-word !important;
            margin-top: 1rem !important;
            margin-bottom: 0.5rem !important;
        }

        .response-modal-body ul,
        .response-modal-body ol {
            padding-left: 1.2rem !important;
            margin-bottom: 1rem !important;
        }

        .response-modal-body li {
            margin-bottom: 0.5rem !important;
            line-height: 1.4 !important;
            word-break: break-word !important;
        }

        .response-modal-body .form-control-plaintext {
            font-size: 0.9rem !important;
            word-break: break-word !important;
        }

        .response-modal-body .col-form-label {
            font-size: 0.9rem !important;
            word-break: break-word !important;
        }
    }

    /* Very small screens */
    @media (max-width: 576px) {
        .modal-dialog.modal-xl {
            margin: 0.25rem;
            max-width: calc(100% - 0.5rem);
        }

        .response-modal-header {
            padding: 0.75rem;
        }

        .response-modal-header .modal-title {
            font-size: 1rem !important;
        }

        .response-modal-body {
            padding: 0.75rem;
        }

        /* Extra small screen text fixes */
        .response-modal-body .patient-info-section,
        .response-modal-body .ai-response-section,
        .response-modal-body .ai-response-content {
            font-size: 0.8rem !important;
            line-height: 1.4 !important;
        }

        .response-modal-body h1,
        .response-modal-body h2,
        .response-modal-body h3,
        .response-modal-body h4,
        .response-modal-body h5,
        .response-modal-body h6 {
            font-size: 0.9rem !important;
        }

        .response-modal-body .form-control-plaintext {
            font-size: 0.8rem !important;
        }

        .response-modal-body .col-form-label {
            font-size: 0.8rem !important;
        }
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
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2 text-white">Patient Analyses for {{ $user->name }}</h1>
                    <p class="mb-0 opacity-75">Viewing all patient data entered by this user</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to User
                    </a>
                </div>
            </div>
        </div>

        <!-- Patient Analyses Table -->
        <div class="info-card">
            <div class="table-responsive">
                <table id="patientAnalysesTable" class="table custom-table align-middle w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Visit #</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patientAnalyses as $analysis)
                        <tr>
                            <td><strong>#{{ $analysis->id }}</strong></td>
                            <td>
                                {{ $analysis->name }}
                            </td>
                            <td>{{ $analysis->age }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $analysis->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white;">
                                    {{ ucfirst($analysis->gender) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">Visit #{{ $analysis->visit_number ?? 1 }}</span>
                            </td>
                            <td>{{ $analysis->created_at->format('M d, Y') }}</td>
                            <td>
                                <button class="btn btn-view-details"
                                        data-bs-toggle="modal"
                                        data-bs-target="#patientDetailsModal"
                                        data-analysis-id="{{ $analysis->id }}">
                                    <i class="bi bi-search me-1"></i>View Details
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Patient Details Modal -->
<div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-labelledby="patientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="patientDetailsModalLabel">
                    <i class="bi bi-person-vcard me-2"></i>Patient Details
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printDetailsBtn">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <div id="patientDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading patient details...</p>
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
    $(document).ready(function() {
        // Initialize DataTable
        $('#patientAnalysesTable').DataTable({
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, "All"]],
            order: [[5, 'desc']], // Sort by date column by default
            language: {
                search: "🔍 Search:",
                lengthMenu: "Show _MENU_ records",
                info: "Showing _START_ to _END_ of _TOTAL_ records",
                paginate: {
                    previous: "← Prev",
                    next: "Next →"
                }
            }
        });

        // Store all patient analyses for quick access
        const patientAnalyses = @json($patientAnalyses);

        // Handle view details button click
        $('#patientDetailsModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const analysisId = button.data('analysis-id');

            // Find the analysis in the collection
            const analysis = patientAnalyses.data.find(a => a.id === analysisId);

            if (analysis) {
                // Format the patient details
                let detailsHtml = `
                    <div class="patient-info-section">
                        <h4><i class="bi bi-person me-2"></i>Patient Information</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Name:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.name}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Age:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.age} years</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Gender:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.gender}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Weight:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.weight || 'Not recorded'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Height:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.height || 'Not recorded'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Visit #:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.visit_number || '1'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Temperature:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.temperature || 'Not recorded'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Blood Pressure:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.blood_pressure || 'Not recorded'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 row">
                                    <label class="col-sm-4 col-form-label">Blood Sugar:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${analysis.blood_sugar || 'Not recorded'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3 row">
                                    <label class="col-sm-2 col-form-label">Symptoms:</label>
                                    <div class="col-sm-10">
                                        <p class="form-control-plaintext">${analysis.symptoms || 'No symptoms recorded'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3 row">
                                    <label class="col-sm-2 col-form-label">Test Results:</label>
                                    <div class="col-sm-10">
                                        <p class="form-control-plaintext">${analysis.test_results || 'No test results recorded'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3 row">
                                    <label class="col-sm-2 col-form-label">Preliminary Diagnosis:</label>
                                    <div class="col-sm-10">
                                        <p class="form-control-plaintext">${analysis.preliminary_diagnosis || 'No preliminary diagnosis recorded'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Add AI response section if available
                if (analysis.ai_response) {
                    detailsHtml += `
                        <div class="ai-response-section">
                            <h4><i class="bi bi-robot me-2"></i>AI Analysis</h4>
                            <div class="ai-response-content">${analysis.ai_response}</div>
                        </div>
                    `;
                }

                // Update the modal content
                $('#patientDetailsContent').html(detailsHtml);
            } else {
                $('#patientDetailsContent').html('<div class="alert alert-danger">Patient analysis not found.</div>');
            }
        });

        // Handle print button click
        $('#printDetailsBtn').click(function() {
            const printContents = document.getElementById('patientDetailsContent').innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h1 style="text-align: center; margin-bottom: 20px;">Patient Analysis Details</h1>
                    ${printContents}
                </div>
            `;

            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        });
    });
</script>
@endpush
