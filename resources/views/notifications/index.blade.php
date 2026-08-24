@extends('master')

@section('title', 'Notifications')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:0.85rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.nav-pills-premium .nav-link{border-radius:8px;font-size:0.82rem;font-weight:600;color:#64748b;border:1px solid transparent;padding:0.45rem 0.75rem}
.nav-pills-premium .nav-link.active{background:#1e293b;color:#fff;border-color:#1e293b}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-bell me-2"></i>Notifications</h2>
                    <p>All updates · {{ $unreadCount }} unread · stay informed</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn" id="markAllReadBtn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-check-double me-1"></i>Mark All Read</button>
                    <a href="{{ route('notifications.settings') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-cog me-1"></i>Settings</a>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center" style="width:38px;height:38px;background:#1e293b;color:#fff;border-radius:9px"><i class="fas fa-inbox"></i></div>
                    <div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Inbox</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Filter by type · {{ $notifications->total() }} total</p></div>
                </div>
            </div>
            <ul class="nav nav-pills nav-pills-premium mb-3" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">Unread <span class="badge" style="background:#f59e0b;color:#fff;border-radius:99px;font-size:0.68rem;margin-left:0.3rem">{{ $unreadCount }}</span></button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="appointment-tab" data-bs-toggle="tab" data-bs-target="#appointment" type="button" role="tab">Appointments</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="diagnosis-tab" data-bs-toggle="tab" data-bs-target="#diagnosis" type="button" role="tab">Diagnoses</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">System</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    @if($notifications->count() > 0)
                        <div style="border:1px solid #f1f5f9;border-radius:10px;overflow:hidden">
                            @foreach($notifications as $notification) @include('notifications.item', ['notification' => $notification]) @endforeach
                        </div>
                        <div class="d-flex justify-content-center mt-3">{{ $notifications->links() }}</div>
                    @else
                        <div class="text-center py-5" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px"><div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:52px;height:52px;background:#fff;border:1px solid #e2e8f0;color:#94a3b8"><i class="fas fa-bell-slash"></i></div><h6 style="font-weight:700;color:#475569">No notifications</h6><p style="color:#64748b;font-size:0.84rem">You'll see updates here when they arrive.</p></div>
                    @endif
                </div>
                <div class="tab-pane fade" id="unread" role="tabpanel">
                    @if($unreadNotifications->count() > 0)
                        <div style="border:1px solid #f1f5f9;border-radius:10px;overflow:hidden">@foreach($unreadNotifications as $notification) @include('notifications.item', ['notification' => $notification]) @endforeach</div>
                        <div class="d-flex justify-content-center mt-3">{{ $unreadNotifications->links() }}</div>
                    @else
                        <div class="text-center py-5" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px"><i class="fas fa-check-circle" style="color:#10b981;font-size:2rem"></i><h6 style="font-weight:700;color:#065f46;margin-top:0.75rem">All caught up!</h6><p style="color:#047857;font-size:0.84rem">No unread notifications.</p></div>
                    @endif
                </div>
                <div class="tab-pane fade" id="appointment" role="tabpanel">
                    @if($appointmentNotifications->count() > 0)
                        <div style="border:1px solid #f1f5f9;border-radius:10px;overflow:hidden">@foreach($appointmentNotifications as $notification) @include('notifications.item', ['notification' => $notification]) @endforeach</div>
                        <div class="d-flex justify-content-center mt-3">{{ $appointmentNotifications->links() }}</div>
                    @else
                        <div class="text-center py-5" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px"><i class="fas fa-calendar-check" style="color:#94a3b8;font-size:1.8rem"></i><h6 style="font-weight:700;color:#475569;margin-top:0.5rem">No appointment notifications</h6></div>
                    @endif
                </div>
                <div class="tab-pane fade" id="diagnosis" role="tabpanel">
                    @if($diagnosisNotifications->count() > 0)
                        <div style="border:1px solid #f1f5f9;border-radius:10px;overflow:hidden">@foreach($diagnosisNotifications as $notification) @include('notifications.item', ['notification' => $notification]) @endforeach</div>
                        <div class="d-flex justify-content-center mt-3">{{ $diagnosisNotifications->links() }}</div>
                    @else
                        <div class="text-center py-5" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px"><i class="fas fa-stethoscope" style="color:#94a3b8;font-size:1.8rem"></i><h6 style="font-weight:700;color:#475569;margin-top:0.5rem">No diagnosis notifications</h6></div>
                    @endif
                </div>
                <div class="tab-pane fade" id="system" role="tabpanel">
                    @if($systemNotifications->count() > 0)
                        <div style="border:1px solid #f1f5f9;border-radius:10px;overflow:hidden">@foreach($systemNotifications as $notification) @include('notifications.item', ['notification' => $notification]) @endforeach</div>
                        <div class="d-flex justify-content-center mt-3">{{ $systemNotifications->links() }}</div>
                    @else
                        <div class="text-center py-5" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px"><i class="fas fa-shield-alt" style="color:#94a3b8;font-size:1.8rem"></i><h6 style="font-weight:700;color:#475569;margin-top:0.5rem">No system alerts</h6><p style="color:#64748b;font-size:0.84rem">Everything is running smoothly.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#markAllReadBtn').click(function() {
        $.ajax({url: '{{ route("notifications.mark-all-read") }}', method: 'POST', data: {_token: '{{ csrf_token() }}'}, success: function(){ location.reload(); }});
    });
    $('.notification-item').click(function() {
        const notificationId = $(this).data('notification-id');
        const $item = $(this);
        $.ajax({url: '{{ route("notifications.mark-read", ":id") }}'.replace(':id', notificationId), method: 'POST', data: {_token: '{{ csrf_token() }}'}, success: function(){ $item.removeClass('unread').addClass('read'); $item.find('.badge.bg-primary').remove(); }});
    });
});
</script>
@endsection
