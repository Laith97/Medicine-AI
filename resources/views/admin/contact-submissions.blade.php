@extends('master')

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
