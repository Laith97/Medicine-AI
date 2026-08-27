@extends('layouts.admin')
@section('title','Users')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-users" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Users</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $stats['total_doctors'] + $stats['total_patients'] }} total · {{ $stats['total_doctors'] }} doctors · {{ $stats['total_patients'] }} patients</p>
            </div>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> New User</a>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;margin:0 auto 10px"><i class="fas fa-user-md"></i></div><div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $stats['total_doctors'] }}</div><div style="font-size:.72rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Doctors</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:44px;height:44px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;margin:0 auto 10px"><i class="fas fa-users"></i></div><div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $stats['total_patients'] }}</div><div style="font-size:.72rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Patients</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:44px;height:44px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 10px"><i class="fas fa-check-circle"></i></div><div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $doctors->total() }}</div><div style="font-size:.72rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Doctors Page</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:44px;height:44px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center;margin:0 auto 10px"><i class="fas fa-clock"></i></div><div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $patients->total() }}</div><div style="font-size:.72rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Patients Page</div></div></div></div>
    </div>
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
        <div class="card-header bg-white" style="padding:0;border-bottom:1px solid #f1f5f9">
            <ul class="nav nav-pills p-2 gap-1" id="userTabs" role="tablist" style="background:#f8fafc;border-radius:12px;margin:8px">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="doctors-tab" data-bs-toggle="tab" data-bs-target="#doctors" type="button" role="tab" style="border-radius:10px;font-weight:700"><i class="fas fa-user-md me-2"></i>Doctors <span class="badge bg-dark ms-2" style="border-radius:20px">{{ $stats['total_doctors'] }}</span></button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="patients-tab" data-bs-toggle="tab" data-bs-target="#patients" type="button" role="tab" style="border-radius:10px;font-weight:700"><i class="fas fa-users me-2"></i>Patients <span class="badge bg-light border text-muted ms-2" style="border-radius:20px">{{ $stats['total_patients'] }}</span></button></li>
            </ul>
        </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="doctors" role="tabpanel">
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:.84rem">
                        <thead><tr style="background:#f8fafc"><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">#</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Doctor</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Hospital</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Status</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;text-align:right">Actions</th></tr></thead>
                        <tbody>
                            @forelse($doctors as $i=>$doctor)
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px">{{ $doctors->firstItem()+$i }}</span></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><div class="d-flex align-items-center gap-3"><div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">{{ strtoupper(substr($doctor->name,0,1)) }}</div><div><div style="font-weight:700;color:#0f172a">{{ $doctor->name }}</div><div style="font-size:.76rem;color:#64748b">{{ $doctor->email }}</div></div></div></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.82rem;color:#334155">{{ $doctor->hospital->name ?? 'Independent' }}</span></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">@if($doctor->doctor && $doctor->doctor->is_active)<span class="badge bg-success" style="border-radius:20px">Active</span>@else<span class="badge bg-secondary" style="border-radius:20px">Inactive</span>@endif</td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right"><div class="d-inline-flex gap-1"><a href="{{ route('admin.users.show',$doctor) }}" class="btn btn-light border btn-sm" style="border-radius:10px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-eye" style="font-size:.78rem"></i></a><a href="{{ route('admin.users.edit',$doctor) }}" class="btn btn-light border btn-sm" style="border-radius:10px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-pen" style="font-size:.76rem"></i></a><form method="POST" action="{{ route('admin.login-as',$doctor) }}" class="d-inline">@csrf<button class="btn btn-sm" style="background:#0f172a;color:#fff;border-radius:10px;width:32px;height:32px"><i class="fas fa-sign-in-alt" style="font-size:.76rem"></i></button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">No doctors</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($doctors->hasPages())<div class="p-3 d-flex justify-content-center" style="background:#f8fafc;border-top:1px solid #f1f5f9">{{ $doctors->links() }}</div>@endif
            </div>
            <div class="tab-pane fade" id="patients" role="tabpanel">
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:.84rem">
                        <thead><tr style="background:#f8fafc"><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">#</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Patient</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Doctor</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Joined</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;text-align:right">Actions</th></tr></thead>
                        <tbody>
                            @forelse($patients as $i=>$patient)
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px">{{ $patients->firstItem()+$i }}</span></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><div class="d-flex align-items-center gap-3"><div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">{{ strtoupper(substr($patient->name,0,1)) }}</div><div><div style="font-weight:700;color:#0f172a">{{ $patient->name }}</div><div style="font-size:.76rem;color:#64748b">{{ $patient->email }}</div></div></div></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.82rem;color:#334155">{{ $patient->primaryDoctor->name ?? '—' }}</span></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.78rem;color:#64748b">{{ $patient->created_at->format('M d, Y') }}</span></td>
                                    <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right"><div class="d-inline-flex gap-1"><a href="{{ route('admin.users.show',$patient) }}" class="btn btn-light border btn-sm" style="border-radius:10px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-eye"></i></a><a href="{{ route('admin.users.edit',$patient) }}" class="btn btn-light border btn-sm" style="border-radius:10px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-pen"></i></a></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">No patients</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($patients->hasPages())<div class="p-3 d-flex justify-content-center" style="background:#f8fafc;border-top:1px solid #f1f5f9">{{ $patients->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
