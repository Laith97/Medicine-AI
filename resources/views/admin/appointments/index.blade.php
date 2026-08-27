@extends('layouts.admin')
@section('title','All Appointments')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-calendar-check" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Appointments</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $stats['total'] }} total · {{ $stats['completed'] }} completed · {{ $stats['pending'] }} pending · {{ $stats['today'] }} today</p>
            </div>
        </div>
        <span class="badge d-none d-md-inline" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:6px 12px;font-weight:700">{{ $stats['total'] }} total</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569"><i class="fas fa-list"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['total'] }}</div><small class="text-muted">Total</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-check"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['completed'] }}</div><small class="text-muted">Completed</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#fffbeb;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-clock"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['pending'] }}</div><small class="text-muted">Pending</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-calendar-day"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['today'] }}</div><small class="text-muted">Today</small></div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div class="card-body p-3" style="background:#fff">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold" style="font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;margin-bottom:4px">Search</label>
                    <div class="position-relative">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.78rem"></i>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search patient, doctor, reason..." style="border-radius:10px;padding-left:34px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;margin-bottom:4px">Status</label>
                    <select name="status" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                        <option value="">All statuses</option>
                        <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                        <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                        <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end" style="padding-bottom:1px">
                    <button class="btn flex-grow-1 text-white" style="border-radius:10px;background:#0f172a;border:none;font-weight:700;height:38px"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-light border d-inline-flex align-items-center justify-content-center" style="border-radius:10px;font-weight:600;height:38px;padding:0 16px">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive" style="border-top:1px solid #f1f5f9">
            <table class="table mb-0" style="font-size:0.84rem;border-collapse:separate;border-spacing:0">
                <thead>
                    <tr style="background:#f8fafc">
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap"><i class="fas fa-hashtag me-1 opacity-50"></i>#</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em"><i class="fas fa-user me-1 opacity-50"></i>Patient</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em"><i class="fas fa-user-md me-1 opacity-50"></i>Doctor</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap"><i class="far fa-calendar me-1 opacity-50"></i>Date</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Status</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Reason</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $a)
                    <tr style="transition:background 0.15s">
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-weight:700;font-size:0.72rem;padding:4px 8px">#{{ $a->id }}</span></td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">
                            <div class="d-flex align-items-center gap-2">
                                @php $nm = $a->patient->name ?? $a->guest_name ?? 'Guest'; $init = strtoupper(substr($nm,0,1)); @endphp
                                <div style="width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.75rem;flex-shrink:0">{{ $init }}</div>
                                <div style="min-width:0"><div style="font-weight:700;color:#0f172a;font-size:0.86rem" class="text-truncate">{{ $nm }}</div><small class="text-muted text-truncate d-block" style="font-size:0.72rem;max-width:140px">{{ $a->patient->email ?? $a->guest_email ?? '' }}</small></div>
                            </div>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">
                            @if($a->doctor && $a->doctor->user)
                                <div class="d-flex align-items-center gap-2">
                                    @php $dn = $a->doctor->user->name; $dinit = strtoupper(substr($dn,0,1)); @endphp
                                    <div style="width:28px;height:28px;border-radius:50%;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.7rem">{{ $dinit }}</div>
                                    <span style="font-weight:600;color:#334155;font-size:0.84rem">{{ $dn }}</span>
                                </div>
                            @else <span class="text-muted" style="font-size:0.84rem">—</span> @endif
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;white-space:nowrap">
                            <div style="font-size:0.82rem;font-weight:600;color:#0f172a;line-height:1">{{ $a->appointment_date?->format('M d, Y') ?? '—' }}</div>
                            <div style="font-size:0.72rem;color:#94a3b8">{{ $a->appointment_date?->format('H:i') ?? '' }}</div>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><span class="badge {{ $a->status=='completed'?'bg-success':($a->status=='pending'?'bg-warning text-dark':($a->status=='confirmed'?'bg-primary':'bg-secondary')) }}" style="border-radius:20px;font-size:0.68rem">{{ ucfirst($a->status) }}</span></td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;max-width:280px"><div style="font-size:0.84rem;color:#334155;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="{{ $a->reason ?: ($a->symptoms ?? '') }}">{{ \Illuminate\Support\Str::limit($a->reason ?: ($a->symptoms ?? '—'), 60) }}</div></td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:right">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('admin.appointments.show', $a->id) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;padding:0" title="Admin View"><i class="fas fa-eye" style="font-size:0.78rem;color:#475569"></i></a>
                                @if($a->doctor && $a->doctor->user)
                                <form method="POST" action="{{ route('admin.login-as', $a->doctor->user) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="redirect" value="{{ route('doctor.appointments.show', $a->id) }}">
                                    <button type="submit" class="btn btn-sm d-inline-flex align-items-center justify-content-center" style="background:#0f172a;color:#fff;border-radius:10px;width:32px;height:32px;padding:0;border:none" title="Login as Dr. {{ $a->doctor->user->name }}"><i class="fas fa-user-md" style="font-size:0.76rem"></i></button>
                                </form>
                                @endif
                                @if($a->patient)<a href="{{ route('admin.users.show', $a->patient) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;padding:0" title="Patient"><i class="fas fa-user" style="font-size:0.78rem;color:#475569"></i></a>@endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5"><div class="d-flex flex-column align-items-center gap-2"><div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-calendar-check"></i></div><span class="text-muted" style="font-size:0.9rem">No appointments found</span><small class="text-muted" style="font-size:0.78rem">Try adjusting search or filter</small></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-center" style="background:#f8fafc;border-top:1px solid #eef2f7">{{ $appointments->links() }}</div>
    </div>
</div>
@endsection
