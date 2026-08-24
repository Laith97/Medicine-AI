@extends('master')

@section('title', 'My Appointments')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.section-head-modern h5{color:#fff!important}
.section-head-modern p{color:rgba(255,255,255,0.75)!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#1e293b;border-bottom:1px solid #0f172a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:rgba(255,255,255,0.12)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.18)!important}
.badge-pending{background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700}
.badge-confirmed{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700}
.badge-completed{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700}
.badge-cancelled{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-alt me-2"></i>My Appointments</h2>
                    <p>View and manage your appointments · {{ $appointments->total() }} total</p>
                </div>
                <a href="{{ route('doctors.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-plus me-2"></i>Book Appointment</a>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon"><i class="fas fa-filter"></i></div>
                    <div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Filters</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Status & date range</p></div>
                </div>
            </div>
            <form method="GET" action="{{ route('appointments.index') }}">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label" style="font-size:0.82rem;font-weight:600;color:#1e293b">Status</label><select name="status" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.88rem"><option value="">All Statuses</option><option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option><option value="confirmed" {{ request('status')=='confirmed'?'selected':'' }}>Confirmed</option><option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option><option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option><option value="no_show" {{ request('status')=='no_show'?'selected':'' }}>No Show</option></select></div>
                    <div class="col-md-3"><label class="form-label" style="font-size:0.82rem;font-weight:600;color:#1e293b">From Date</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0"></div>
                    <div class="col-md-3"><label class="form-label" style="font-size:0.82rem;font-weight:600;color:#1e293b">To Date</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0"></div>
                    <div class="col-md-3 d-flex align-items-end gap-2"><button type="submit" class="btn" style="background:#1e293b;color:#fff;border:1px solid #1e293b;border-radius:10px;padding:0.6rem 1rem;font-weight:600;font-size:0.84rem;flex:1"><i class="fas fa-filter me-1"></i>Filter</button><a href="{{ route('appointments.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1rem;font-weight:500;font-size:0.84rem">Clear</a></div>
                </div>
            </form>
        </div>

        @if($appointments->count() > 0)
            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-calendar-check"></i></div><div><h5>Appointments</h5><p>{{ $appointments->total() }} records · Page {{ $appointments->currentPage() }}</p></div></div>
                </div>
                @foreach($appointments as $appointment)
                    <div class="d-flex flex-wrap gap-3 align-items-center p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:48px;height:48px;background:#fff;border:1px solid #e2e8f0;color:#475569;flex-shrink:0">@if($appointment->doctor->profile_image)<img src="{{ asset('storage/'.$appointment->doctor->profile_image) }}" alt="" class="rounded-circle" style="width:48px;height:48px;object-fit:cover">@else<i class="fas fa-user-md"></i>@endif</div>
                            <div class="flex-grow-1" style="min-width:0">
                                <div style="font-weight:700;color:#0f172a;font-size:0.92rem">{{ e($appointment->doctor->user->name) }}</div>
                                <small style="color:#2563eb;font-size:0.78rem">{{ e($appointment->doctor->specialty->name ?? 'General') }}</small>
                                <div class="d-flex gap-3 mt-1 flex-wrap" style="font-size:0.78rem;color:#64748b"><span><i class="fas fa-calendar me-1"></i>{{ $appointment->appointment_date->format('M j, Y') }}</span><span><i class="fas fa-clock me-1"></i>{{ $appointment->appointment_date->format('g:i A') }}</span><span><i class="fas fa-{{ $appointment->appointment_type=='video_call'?'video':($appointment->appointment_type=='phone_call'?'phone':'hospital') }} me-1"></i>{{ ucfirst(str_replace('_',' ', $appointment->appointment_type)) }}</span></div>
                                <div style="font-size:0.82rem;color:#334155;margin-top:0.25rem"><i class="fas fa-stethoscope me-1" style="color:#94a3b8"></i>{{ Str::limit($appointment->reason, 60) }}</div>
                            </div>
                        </div>
                        <div class="text-end" style="min-width:140px">
                            @php $cls=['pending'=>'badge-pending','confirmed'=>'badge-confirmed','completed'=>'badge-completed','cancelled'=>'badge-cancelled','no_show'=>'badge-cancelled'][$appointment->status]??'badge-pending' @endphp
                            <span class="badge {{ $cls }}">{{ ucfirst(str_replace('_',' ', $appointment->status)) }}</span>
                            <div class="d-flex gap-2 justify-content-end mt-2">
                                <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm" style="background:#1e293b;color:#fff;border-radius:8px;padding:0.35rem 0.6rem;font-size:0.78rem;font-weight:600"><i class="fas fa-eye me-1"></i>View</a>
                                @if($appointment->canBeCancelled())<button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-sm" style="background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:0.35rem 0.6rem;font-size:0.78rem;font-weight:600"><i class="fas fa-times me-1"></i>Cancel</button>@endif
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($appointments->hasPages())<div class="d-flex justify-content-center mt-3">{{ $appointments->links() }}</div>@endif
            </div>
        @else
            <div class="table-card text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:56px;height:56px;background:#f8fafc;border:1px solid #e2e8f0;color:#94a3b8"><i class="fas fa-calendar-times" style="font-size:1.5rem"></i></div>
                <h5 style="font-weight:700;color:#475569">No Appointments Found</h5><p style="color:#64748b;font-size:0.88rem">You haven't booked any appointments yet.</p>
                <a href="{{ route('doctors.index') }}" class="btn" style="background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%);color:#fff;border:none;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600"><i class="fas fa-plus me-2"></i>Book Your First Appointment</a>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:12px;overflow:hidden"><div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0"><h5 class="modal-title" style="font-weight:700;color:#0f172a"><i class="fas fa-times-circle me-2" style="color:#dc2626"></i>Cancel Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p style="color:#334155">Are you sure you want to cancel this appointment?</p><form id="cancelForm" method="POST">@csrf<div class="mb-3"><label class="form-label" style="font-weight:600;font-size:0.82rem">Reason (optional)</label><textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Please provide a reason..." style="border-radius:10px;border:1px solid #e2e8f0"></textarea></div></form></div><div class="modal-footer" style="border-top:1px solid #f1f5f9"><button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px" data-bs-dismiss="modal">Keep Appointment</button><button type="button" class="btn" style="background:#dc2626;color:#fff;border-radius:8px" onclick="submitCancellation()"><i class="fas fa-times me-1"></i>Cancel Appointment</button></div></div></div></div>
@endsection

@push('scripts')
<script>
function cancelAppointment(appointmentId){document.getElementById('cancelForm').action='/appointments/'+appointmentId+'/cancel'; new bootstrap.Modal(document.getElementById('cancelModal')).show();}
function submitCancellation(){document.getElementById('cancelForm').submit();}
</script>
@endpush
