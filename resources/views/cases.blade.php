@extends('master')

@section('title', 'Patients Page')

@section('content')
@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>

    .dataTables_filter input {
        border-radius: 0.25rem;
        border: 1px solid #DE6262;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #DE6262 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #DE6262 !important;
        color: white !important;
        border-radius: 0.25rem;
    }
    .btn-outline-danger {
        border-color: #DE6262;
        color: #DE6262;
    }
    .btn-outline-danger:hover {
        background-color: #DE6262;
        color: white;
    }
    /* Make sure the style applies by using more specific selectors and !important */
    div.dataTables_wrapper div.dataTables_paginate ul.pagination {
        margin-top: 10px;
    }

    div.dataTables_wrapper div.dataTables_paginate ul.pagination li a {
        color: #DE6262 !important;
    }

    div.dataTables_wrapper div.dataTables_paginate ul.pagination li a:hover,
    div.dataTables_wrapper div.dataTables_paginate ul.pagination li.active a {
        background-color: #DE6262 !important;
        color: white !important;
        border: none !important;
        border-radius: 4px;
    }

    /* Full-width and centered modal */
.modal-xl {
    max-width: 95vw;
}

/* Modal content styling */
.response-modal-content {
    border-radius: 18px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

/* Modal header with gradient and padding */
.response-modal-header {
    background: linear-gradient(135deg, #DE6262, #FFB88C);
    color: #fff;
    padding: 1.5rem 2rem;
    border-bottom: none;
}

/* Modal body for long responses */
.response-modal-body {
    background-color: #f9f9fb;
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
    font-size: 1rem;
    line-height: 1.8;
    letter-spacing: 0.3px;
}

</style>
@endpush

<div class="container-fluid mt-4">
    <div class="card shadow rounded">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #DE6262;">
            <h5 class="mb-0" style="color: aliceblue">Patient Records</h5>
            <a href="{{ route('ask-openai') }}" class="btn btn-sm" style="background-color: #ffffff; color: #DE6262;">
                + Add Patient
            </a>
        </div>
        
        <div class="card-body">

            <div class="table-responsive">
                <table id="recordsTable" class="table table-hover align-middle w-100">
                    <thead style="background-color: #f8d7da; color: #a94442;">
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
                        @forelse ($records as $record)
                            <tr class="text-center">
                                <td>{{ $record->id }}</td>
                                <td>{{ $record->name }}</td>
                                <td>{{ $record->age }}</td>
                                <td>{{ ucfirst($record->gender) }}</td>
                                <td>{{ $record->height ?? 'N/A' }}</td>
                                <td>{{ $record->weight ?? 'N/A' }}</td>
                                <td>{{ $record->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger view-response-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#responseModal"
                                            data-response="{{ htmlentities($record->ai_response) }}">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: aliceblue">{{ $record->name ?? 'OpenAI' }} Recommendations</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body response-modal-body">
                <div class="response-block">
                    <pre id="openaiReply" class="response-text" style="white-space: pre-wrap;"></pre>
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
                emptyTable: "No records available"
            }
        });

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
