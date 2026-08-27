@extends('layouts.admin')
@section('title','User Details')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-user" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">User Details</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Detailed information about {{ $user->name }} · #{{ $user->id }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i>Back</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm" style="background:#fff;color:#0f172a;border-radius:10px;font-weight:700;border:none"><i class="fas fa-pen me-1"></i>Edit User</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-body p-4 text-center" style="background:#fff">
                    <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.4rem;margin:0 auto 14px;box-shadow:0 8px 20px rgba(102,126,234,0.35)">{{ strtoupper(substr($user->name,0,1)) }}</div>
                    <h3 style="font-weight:800;color:#0f172a;margin:0;font-size:1.25rem">{{ $user->name }}</h3>
                    <div class="mt-2"><span class="badge" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;border-radius:20px;padding:6px 12px;font-weight:700">{{ ucfirst($user->role ?? 'Regular User') }}</span> @if($user->email_verified_at)<span class="badge bg-success" style="border-radius:20px">Verified</span>@else<span class="badge bg-light border text-muted" style="border-radius:20px">Not Verified</span>@endif</div>
                    <div class="row g-3 mt-3 text-start">
                        <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><div style="font-size:0.68rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;color:#64748b">Email Address</div><div style="font-weight:700;color:#0f172a;font-size:0.9rem;word-break:break-all">{{ $user->email }}</div></div></div>
                        <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><div style="font-size:0.68rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;color:#64748b">Member Since</div><div style="font-weight:700;color:#0f172a;font-size:0.9rem">{{ $user->created_at->format('F j, Y') }}</div></div></div>
                        <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><div style="font-size:0.68rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;color:#64748b">Last Updated</div><div style="font-weight:700;color:#0f172a;font-size:0.9rem">{{ $user->updated_at->format('F j, Y g:i A') }}</div></div></div>
                        <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><div style="font-size:0.68rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;color:#64748b">Email Verification</div><div>@if($user->email_verified_at)<span class="badge bg-success" style="border-radius:20px"><i class="fas fa-check-circle me-1"></i>Verified</span>@else<span class="badge bg-danger" style="border-radius:20px"><i class="fas fa-times-circle me-1"></i>Not Verified</span>@endif</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-file-medical" style="color:#64748b"></i> Patient Analyses <span class="badge bg-light border text-muted" style="border-radius:20px">{{ $user->patientAnalyses->count() }}</span></h5>
                    @if($user->patientAnalyses->count() > 0)<a href="{{ route('admin.users.patient-analyses', $user) }}" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600">View All</a>@endif
                </div>
                <div class="card-body p-3" style="background:#fff">
                    @if($user->patientAnalyses->count() > 0)
                        <div class="d-flex flex-column gap-2">
                        @foreach($user->patientAnalyses->take(5) as $analysis)
                            <div class="d-flex justify-content-between align-items-start p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px">
                                <div style="min-width:0;flex:1">
                                    <div style="font-weight:700;color:#0f172a;font-size:0.88rem">Patient: {{ $analysis->name }} <span class="badge bg-light border text-muted" style="font-size:0.68rem;border-radius:20px">{{ $analysis->gender }}, {{ $analysis->age }} y/o</span></div>
                                    <div style="font-size:0.78rem;color:#64748b;margin-top:4px;line-height:1.4"><strong>Symptoms:</strong> {{ Str::limit($analysis->symptoms ?? 'No symptoms recorded', 110) }}</div>
                                </div>
                                <small class="text-muted ms-3" style="font-size:0.72rem;white-space:nowrap">{{ $analysis->created_at->diffForHumans() }}</small>
                            </div>
                        @endforeach
                        </div>
                        @if($user->patientAnalyses->count() > 5)
                            <div class="text-center mt-3"><a href="{{ route('admin.users.patient-analyses', $user) }}" class="btn btn-primary btn-sm" style="background:#0f172a;border:none;border-radius:10px;font-weight:700">View All {{ $user->patientAnalyses->count() }} Records</a></div>
                        @endif
                    @else
                        <div class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-file-medical"></i></div><p class="text-muted small mb-0">No patient analyses found.</p></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-chart-bar" style="color:#64748b"></i> Statistics</h5></div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center p-3 mb-2" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><span style="font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.04em">Total Analyses</span><span style="font-weight:800;color:#0f172a;font-size:1.1rem">{{ $user->patientAnalyses->count() }}</span></div>
                    <div class="d-flex justify-content-between align-items-center p-3 mb-2" style="background:#fff;border:1px solid #eef2f7;border-radius:12px"><span style="font-size:0.78rem;font-weight:700;color:#475569">Account Age</span><span style="font-weight:600;color:#0f172a;font-size:0.84rem">{{ $user->created_at->diffForHumans(null, true) }}</span></div>
                    @if($user->setting)<div class="d-flex justify-content-between align-items-center p-3" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px"><span style="font-size:0.78rem;font-weight:700;color:#065f46">Settings</span><span class="badge bg-success" style="border-radius:20px">Configured</span></div>@endif
                </div>
            </div>

            @if($user->id !== auth()->id())
            <div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-bolt" style="color:#f59e0b"></i> Quick Actions</h5></div>
                <div class="card-body p-3">
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" style="border-radius:10px;font-weight:700"><i class="fas fa-trash me-1"></i>Delete User</button>
                    </form>
                    <div class="mt-2 p-2" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:0.72rem;color:#991b1b"><i class="fas fa-exclamation-triangle me-1"></i>Deletion is permanent and will remove all associated data.</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
