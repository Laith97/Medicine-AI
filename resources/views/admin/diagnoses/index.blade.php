@extends('layouts.admin')
@section('title','All Diagnoses')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-file-medical" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Diagnoses</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $stats['total'] }} total · {{ $stats['voice'] }} voice · {{ $stats['text'] }} text · {{ $stats['today'] }} today</p>
            </div>
        </div>
        <span class="badge d-none d-md-inline" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:6px 12px;font-weight:700">{{ $stats['total'] }} total</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569"><i class="fas fa-list"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['total'] }}</div><small class="text-muted">Total</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-microphone"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['voice'] }}</div><small class="text-muted">Voice</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#f5f3ff;color:#7c3aed;display:flex;align-items:center;justify-content:center"><i class="fas fa-file-medical"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['text'] }}</div><small class="text-muted">Text</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-calendar-day"></i></div><div><div style="font-weight:800;color:#1e293b">{{ $stats['today'] }}</div><small class="text-muted">Today</small></div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div class="card-body p-3" style="background:#fff">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold" style="font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;margin-bottom:4px">Search</label>
                    <div class="position-relative">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.78rem"></i>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search diagnosis, patient, doctor..." style="border-radius:10px;padding-left:34px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;margin-bottom:4px">Type</label>
                    <select name="type" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                        <option value="">All types</option>
                        <option value="voice_assistant" {{ request('type')=='voice_assistant'?'selected':'' }}>Voice</option>
                        <option value="text" {{ request('type')=='text'?'selected':'' }}>Text</option>
                        <option value="ai" {{ request('type')=='ai'?'selected':'' }}>AI</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end" style="padding-bottom:1px">
                    <button class="btn flex-grow-1 text-white" style="border-radius:10px;background:#0f172a;border:none;font-weight:700;height:38px"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="{{ route('admin.diagnoses.index') }}" class="btn btn-light border d-inline-flex align-items-center justify-content-center" style="border-radius:10px;font-weight:600;height:38px;padding:0 16px">Reset</a>
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
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:center">Type</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Diagnosis</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap"><i class="far fa-calendar me-1 opacity-50"></i>Date</th>
                        <th style="padding:0.9rem 1.1rem;border:none;border-bottom:1px solid #e2e8f0;font-size:0.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($diagnoses as $d)
                    <tr style="transition:background 0.15s">
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-weight:700;font-size:0.72rem;padding:4px 8px">#{{ $d->id }}</span></td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#e0e7ff 0%,#c7d2fe 100%);color:#3730a3;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.82rem;flex-shrink:0">{{ strtoupper(substr($d->patient->name ?? 'P',0,1)) }}</div>
                                <div style="min-width:0">
                                    <div style="font-weight:700;color:#0f172a;font-size:0.88rem;line-height:1" class="text-truncate">{{ $d->patient->name ?? '—' }}</div>
                                    <div style="font-size:0.74rem;color:#64748b;line-height:1.2" class="text-truncate">{{ $d->patient->email ?? 'No email' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:28px;height:28px;border-radius:50%;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.7rem;flex-shrink:0">{{ strtoupper(substr($d->doctor->name ?? 'D',0,1)) }}</div>
                                <span style="font-weight:600;color:#334155;font-size:0.84rem" class="text-truncate">{{ $d->doctor->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:center">
                            @php $t = strtolower($d->type ?? 'text'); @endphp
                            <span class="badge" style="border-radius:20px;font-size:0.66rem;font-weight:800;letter-spacing:0.03em;padding:4px 10px;border:1px solid {{ $t==='voice_assistant'?'#bfdbfe':'#e2e8f0' }};background:{{ $t==='voice_assistant'?'#eff6ff':'#f8fafc' }};color:{{ $t==='voice_assistant'?'#1d4ed8':'#64748b' }}">{{ $t==='voice_assistant' ? 'VOICE' : strtoupper($t) }}</span>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;max-width:380px">
                            <div style="font-size:0.86rem;color:#334155;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="{{ $d->diagnosis_text }}">{{ \Illuminate\Support\Str::limit($d->diagnosis_text ?? '—', 110) }}</div>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;white-space:nowrap">
                            <div style="font-size:0.82rem;font-weight:600;color:#0f172a;line-height:1">{{ $d->created_at->format('M d, Y') }}</div>
                            <div style="font-size:0.72rem;color:#94a3b8">{{ $d->created_at->format('H:i') }}</div>
                        </td>
                        <td style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;text-align:right">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a href="{{ route('admin.diagnoses.show', $d->id) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;padding:0" title="Admin View"><i class="fas fa-eye" style="font-size:0.78rem;color:#475569"></i></a>
                                @if($d->doctor)
                                <form method="POST" action="{{ route('admin.login-as', $d->doctor) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="redirect" value="{{ route('diagnosis.show', $d->id) }}">
                                    <button type="submit" class="btn btn-sm d-inline-flex align-items-center justify-content-center" style="background:#0f172a;color:#fff;border-radius:10px;width:32px;height:32px;padding:0;border:none" title="Login as Dr. {{ $d->doctor->name }}"><i class="fas fa-user-md" style="font-size:0.76rem"></i></button>
                                </form>
                                @endif
                                @if($d->appointment_id && $d->doctor)
                                <form method="POST" action="{{ route('admin.login-as', $d->doctor) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="redirect" value="{{ route('doctor.appointments.show', $d->appointment_id) }}">
                                    <button type="submit" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;padding:0" title="Login as doctor to view appointment"><i class="fas fa-calendar" style="font-size:0.76rem;color:#475569"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5"><div class="d-flex flex-column align-items-center gap-2"><div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-stethoscope"></i></div><span class="text-muted" style="font-size:0.9rem">No diagnoses found</span><small class="text-muted" style="font-size:0.78rem">Try adjusting search or filter</small></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-center" style="background:#f8fafc;border-top:1px solid #eef2f7">{{ $diagnoses->links() }}</div>
    </div>
</div>
@endsection
