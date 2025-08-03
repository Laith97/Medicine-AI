@extends('layouts.admin')

@section('title', 'Contact Submissions')

@push('styles')
<style>
    .contact-submissions-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 1rem 0;
    }

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
                        <div class="table-responsive contact-submissions-table">
                            <table class="table custom-table">
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

                        @if($submissions->hasPages())
                            <div class="pagination-wrapper">
                                {{ $submissions->links() }}
                                <div class="pagination-info">
                                    Showing {{ $submissions->firstItem() }} to {{ $submissions->lastItem() }} of {{ $submissions->total() }} submissions
                                </div>
                            </div>
                        @endif
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
