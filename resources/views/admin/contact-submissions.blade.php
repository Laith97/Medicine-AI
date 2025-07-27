@extends('layouts.admin')

@section('title', 'Contact Submissions')

@push('styles')
<style>
    /* Compact table styles */
    .table th {
        padding: 0.5rem 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        padding: 0.4rem 0.4rem;
        font-size: 0.8rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
    }

    .table td strong {
        font-size: 0.85rem;
    }

    .table td small {
        font-size: 0.7rem;
    }

    .table .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }

    .table .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    /* Column widths for contact submissions table */
    .table th:nth-child(1), .table td:nth-child(1) { width: 15%; }
    .table th:nth-child(2), .table td:nth-child(2) { width: 20%; }
    .table th:nth-child(3), .table td:nth-child(3) { width: 15%; }
    .table th:nth-child(4), .table td:nth-child(4) { width: 25%; }
    .table th:nth-child(5), .table td:nth-child(5) { width: 15%; }
    .table th:nth-child(6), .table td:nth-child(6) { width: 10%; }

    /* Pagination styling */
    .pagination {
        margin-bottom: 0;
    }

    .pagination .page-link {
        color: #DE6262;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        margin: 0 0.125rem;
    }

    .pagination .page-link:hover {
        color: white;
        background-color: #DE6262;
        border-color: #DE6262;
    }

    .pagination .page-item.active .page-link {
        background-color: #DE6262;
        border-color: #DE6262;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Contact Form Submissions</h4>
                </div>
                <div class="card-body">
                    @if($submissions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Service</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Submitted</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submissions as $submission)
                                    <tr class="{{ $submission->is_read ? '' : 'table-warning' }}">
                                        <td>{{ $submission->name }}</td>
                                        <td>
                                            <a href="mailto:{{ $submission->email }}">{{ $submission->email }}</a>
                                        </td>
                                        <td>{{ $submission->phone }}</td>
                                        <td>{{ $submission->service }}</td>
                                        <td>{{ $submission->subject }}</td>
                                        <td>
                                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                                {{ Str::limit($submission->message, 100) }}
                                            </div>
                                        </td>
                                        <td>{{ $submission->submitted_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            @if($submission->is_read)
                                                <span class="badge bg-success">Read</span>
                                            @else
                                                <span class="badge bg-warning">Unread</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$submission->is_read)
                                                <form method="POST" action="{{ route('admin.contact-submissions.mark-read', $submission) }}" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-success">Mark as Read</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{ $submissions->links() }}
                    @else
                        <div class="alert alert-info">
                            No contact form submissions yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
