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

    .response-text {
        white-space: pre-wrap;
        word-break: break-word;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        padding: 2rem;
        border-left: 6px solid #DE6262;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        font-family: "Segoe UI", Roboto, sans-serif;
        font-size: 1rem;
        color: #2c3e50;
        border: 1px solid rgba(222, 98, 98, 0.1);
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
                                    <th class="text-center">Recommendations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($records as $record)
                                <tr class="text-center">
                                    <td><strong>#{{ $record->id }}</strong></td>
                                    <td>{{ $record->name }}</td>
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
                                        <button class="btn btn-view-response view-response-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#responseModal"
                                                data-response="{{ htmlentities($record->ai_response) }}">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
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
                    <i class="fas fa-stethoscope me-2"></i>Medical Recommendations
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body response-modal-body">
                <div class="response-block">
                    <pre id="openaiReply" class="response-text"></pre>
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

        $('.view-response-btn').on('click', function () {
            const raw = $(this).data('response') || 'No response';
            $('#openaiReply').text(decodeHtml(raw));
        });

        function decodeHtml(html) {
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }
    });
</script>

@endpush
