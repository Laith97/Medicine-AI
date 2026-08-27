@extends('layouts.admin')
@section('title','Contact Submissions')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-inbox" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Contact Submissions</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $submissions->total() }} total · {{ $submissions->where('is_read',false)->count() }} unread on this page</p>
            </div>
        </div>
        <span class="badge d-none d-md-inline" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:6px 12px;font-weight:700">{{ $submissions->total() }} submissions</span>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
            <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-envelope" style="color:#64748b"></i> Inbox</h5>
            <span class="badge bg-light border text-muted" style="border-radius:20px">{{ $submissions->count() }} on page</span>
        </div>
        @if($submissions->count() > 0)
            <div class="table-responsive" style="border-top:1px solid #f1f5f9">
                <table class="table mb-0" style="font-size:0.84rem;border-collapse:separate;border-spacing:0">
                    <thead>
                        <tr style="background:#f8fafc">
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Name</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Email</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Phone</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Service</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Subject</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Message</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap">Submitted</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:center">Status</th>
                            <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                        <tr style="{{ !$submission->is_read ? 'background:#fffbeb' : '' }}">
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:800;color:#475569;font-size:0.75rem">{{ strtoupper(substr($submission->name,0,1)) }}</div>
                                    <span style="font-weight:700;color:#0f172a;font-size:0.86rem">{{ $submission->name }}</span>
                                </div>
                            </td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><a href="mailto:{{ $submission->email }}" style="font-size:0.82rem;color:#2563eb;text-decoration:none;font-weight:500">{{ $submission->email }}</a></td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:0.82rem;color:#334155">{{ $submission->phone ?: '—' }}</td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><span class="badge" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;border-radius:20px;font-size:0.68rem">{{ $submission->service ?: '—' }}</span></td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:0.82rem;color:#334155;max-width:160px"><div class="text-truncate" title="{{ $submission->subject }}">{{ $submission->subject ?: '—' }}</div></td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;max-width:280px"><div style="font-size:0.82rem;color:#475569;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="{{ $submission->message }}">{{ Str::limit($submission->message, 90) }}</div></td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;white-space:nowrap">
                                <div style="font-size:0.82rem;font-weight:600;color:#0f172a">{{ $submission->submitted_at->format('M d, Y') }}</div>
                                <div style="font-size:0.72rem;color:#94a3b8">{{ $submission->submitted_at->format('H:i') }}</div>
                            </td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:center">@if($submission->is_read)<span class="badge bg-success" style="border-radius:20px;font-size:0.68rem">Read</span>@else<span class="badge bg-warning text-dark" style="border-radius:20px;font-size:0.68rem">Unread</span>@endif</td>
                            <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:right">
                                @if(!$submission->is_read)
                                    <form method="POST" action="{{ route('admin.contact-submissions.mark-read', $submission) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm text-white" style="background:#0f172a;border:none;border-radius:10px;font-weight:700;font-size:0.75rem;padding:6px 10px">Mark as Read</button>
                                    </form>
                                @else <span class="text-muted" style="font-size:0.75rem">—</span> @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($submissions->hasPages())
                <div class="p-3 d-flex flex-column align-items-center gap-2" style="background:#f8fafc;border-top:1px solid #eef2f7">
                    {{ $submissions->links() }}
                    <div style="font-size:0.78rem;color:#64748b">Showing {{ $submissions->firstItem() }} to {{ $submissions->lastItem() }} of {{ $submissions->total() }} submissions</div>
                </div>
            @endif
        @else
            <div class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-inbox"></i></div><p class="text-muted small mb-0">No contact form submissions yet.</p></div>
        @endif
    </div>
</div>
@endsection
