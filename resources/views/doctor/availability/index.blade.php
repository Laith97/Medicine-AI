@extends('master')

@section('title', 'Manage Availability')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.week-tabs{display:flex;gap:0.5rem;overflow-x:auto;padding:0.35rem;background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;scrollbar-width:none}
.week-tabs::-webkit-scrollbar{display:none}
.week-tab{flex:0 0 auto;display:flex;flex-direction:column;align-items:center;gap:0.15rem;padding:0.6rem 1rem;border-radius:10px;border:1px solid transparent;background:transparent;color:#64748b;font-weight:700;font-size:0.78rem;cursor:pointer;transition:all .15s;min-width:78px}
.week-tab small{font-weight:600;font-size:0.68rem;opacity:0.9}
.week-tab.active{background:#1e293b;color:#fff;border-color:#1e293b;box-shadow:0 2px 8px rgba(15,23,42,0.12)}
.week-tab.has-slots:not(.active){background:#fff;border-color:#e2e8f0;color:#334155}
.week-tab.has-slots:not(.active) small{color:#10b981}
.day-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(15,23,42,0.04);scroll-margin-top:1rem}
.day-card.today{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.12)}
.day-card-header{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.day-card-header h6{margin:0;font-weight:800;color:#0f172a;font-size:0.92rem}
.slot-row{display:flex;align-items:center;gap:1rem;padding:0.9rem 1rem;background:#fff;border:1px solid #eef2f7;border-radius:10px;transition:all .15s}
.slot-row:hover{border-color:#cbd5e1;box-shadow:0 4px 12px rgba(15,23,42,0.06);transform:translateY(-1px)}
.slot-time{flex:0 0 150px;text-align:center;border-right:1px solid #f1f5f9;padding-right:1rem}
.slot-time strong{font-size:0.98rem;color:#0f172a;display:block;line-height:1}
.slot-time span{font-size:0.68rem;color:#64748b;letter-spacing:0.04em;text-transform:uppercase;font-weight:700}
.slot-meta{flex:1;min-width:0}
.slot-actions{flex:0 0 auto;display:flex;align-items:center;gap:0.35rem}
@media(max-width:768px){.slot-row{flex-direction:column;align-items:stretch}.slot-time{flex:none;border-right:none;border-bottom:1px solid #f1f5f9;padding:0 0 0.7rem 0;text-align:left}.slot-actions{justify-content:flex-end;width:100%}.week-tab{min-width:68px;padding:0.5rem 0.75rem}}
</style>
@endpush

@section('content')
@php
    $totalSlots = $availabilitySlots->flatten()->count();
    $activeSlots = $availabilitySlots->flatten()->where('is_active', true)->count();
    $daysAvailable = $availabilitySlots->keys()->count();
    $weeklyHours = round($availabilitySlots->flatten()->sum(function($slot){ return \Carbon\Carbon::parse($slot->end_time)->diffInMinutes(\Carbon\Carbon::parse($slot->start_time))/60; }),1);
    $utilization = $daysAvailable ? round($daysAvailable/7*100) : 0;
    $todayKey = strtolower(\Carbon\Carbon::now()->format('l'));
    $todayKeyShort = strtolower(\Carbon\Carbon::now()->format('D'));
@endphp
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-alt me-2"></i>Availability</h2>
                    <p>Manage your weekly schedule and time slots · {{ $daysAvailable }}/7 days · {{ $weeklyHours }}h/week</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.availability.create') }}" class="btn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:700;font-size:0.83rem;box-shadow:0 2px 8px rgba(0,0,0,0.08)"><i class="fas fa-plus me-2"></i>Add Slot</a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert" style="border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46"><i class="fas fa-check-circle"></i><div>{{ session('success') }}</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <!-- Stats premium -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-4">
                <div class="stats-card stats-card--compact" style="position:relative;overflow:hidden">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);"><i class="fas fa-clock"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ number_format($weeklyHours,1) }}<small style="font-size:0.7rem;color:#64748b">h</small></p><p class="stats-label">Weekly Hours</p></div>
                    <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:#e2e8f0"><div style="height:100%;width:{{$utilization}}%;background:linear-gradient(90deg,#0ea5e9,#0284c7)"></div></div>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-calendar-check"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $activeSlots }}<small style="font-size:0.7rem;color:#64748b"> / {{ $totalSlots }}</small></p><p class="stats-label">Active Slots</p></div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);"><i class="fas fa-calendar-day"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $daysAvailable }} <small style="font-size:0.72rem;color:#64748b">/ 7</small></p><p class="stats-label">Days Available</p></div>
                </div>
            </div>
        </div>

        <!-- Week strip tabs -->
        <div class="table-card" style="padding:0.9rem">
            <div class="week-tabs" id="weekTabs">
                @foreach($daysOfWeek as $day => $dayName)
                    @php $cnt = $availabilitySlots->has($day) ? $availabilitySlots[$day]->count() : 0; $isToday = $day === $todayKey; @endphp
                    <button class="week-tab {{ $cnt>0 ? 'has-slots' : '' }} {{ $isToday ? 'active' : '' }}" data-day="{{ $day }}" onclick="scrollToDay('{{ $day }}')">
                        <span>{{ substr($dayName,0,3) }}</span>
                        <small>{{ $cnt }} {{ $cnt===1 ? 'slot' : 'slots' }}</small>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Weekly Schedule -->
        <div class="table-card">
            <div class="section-head-modern">
                <div class="head-left"><div class="head-icon"><i class="fas fa-calendar-week"></i></div><div><h5>Weekly Schedule</h5><p>Manage per-day time slots · {{ $utilization }}% coverage</p></div></div>
                <span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:99px;padding:0.35rem 0.7rem;font-size:0.72rem;font-weight:700">{{ $totalSlots }} total</span>
            </div>

            <div class="d-flex flex-column gap-3" id="daysContainer">
                @foreach($daysOfWeek as $day => $dayName)
                    @php $isToday = $day === $todayKey; @endphp
                    <div class="day-card {{ $isToday ? 'today' : '' }}" id="day-{{ $day }}" data-day="{{ $day }}">
                        <div class="day-card-header">
                            <h6><i class="far fa-calendar me-2" style="color:{{ $isToday ? '#3b82f6' : '#64748b' }}"></i>{{ $dayName }} @if($isToday)<span class="badge ms-2" style="background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:99px;font-size:0.65rem">Today</span>@endif <span class="ms-2" style="font-weight:600;color:#64748b;font-size:0.78rem">{{ $availabilitySlots->has($day) ? $availabilitySlots[$day]->count().' slots' : 'Off' }}</span></h6>
                            <button onclick="showBulkModal('{{ $day }}')" class="doctor-btn doctor-btn-primary doctor-btn-sm"><i class="fas fa-plus me-1"></i>Quick Add</button>
                        </div>
                        <div class="p-3">
                            @if($availabilitySlots->has($day))
                                <div class="d-flex flex-column gap-2">
                                    @foreach($availabilitySlots[$day] as $slot)
                                        @php $durationMins = $slot->slot_duration; $start = date('g:i A', strtotime($slot->start_time)); $end = date('g:i A', strtotime($slot->end_time)); @endphp
                                        <div class="slot-row">
                                            <div class="slot-time">
                                                <strong>{{ $start }} – {{ $end }}</strong>
                                                <span>{{ $durationMins }} min • {{ $slot->max_bookings_per_slot }} /slot</span>
                                            </div>
                                            <div class="slot-meta">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    @if(!$slot->is_active)<span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:99px;font-size:0.68rem;padding:0.2rem 0.5rem">Inactive</span>
                                                    @else <span class="badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;font-size:0.68rem;padding:0.2rem 0.5rem">Active</span>@endif
                                                    <small style="color:#64748b;font-size:0.76rem"><i class="fas fa-users me-1"></i>Max {{ $slot->max_bookings_per_slot }}</small>
                                                </div>
                                                @if($slot->effective_from || $slot->effective_until)
                                                    <small style="color:#94a3b8;font-size:0.72rem;display:block;margin-top:2px">@if($slot->effective_from) From {{ $slot->effective_from->format('M j, Y') }} @endif @if($slot->effective_until) · Until {{ $slot->effective_until->format('M j, Y') }} @endif</small>
                                                @endif
                                            </div>
                                            <div class="slot-actions">
                                                <form method="POST" action="{{ route('doctor.availability.toggle', $slot) }}" class="d-inline">@csrf
                                                    <button type="submit" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;min-width:36px" title="{{ $slot->is_active ? 'Deactivate' : 'Activate' }}"><i class="fas fa-{{ $slot->is_active ? 'pause' : 'play' }}"></i></button>
                                                </form>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;border-radius:8px;min-width:36px" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-h"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border:1px solid #eef2f7;border-radius:10px;min-width:160px">
                                                        <li><a class="dropdown-item" href="{{ route('doctor.availability.edit', $slot) }}"><i class="fas fa-pen me-2 text-muted"></i>Edit</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><form method="POST" action="{{ route('doctor.availability.destroy', $slot) }}" onsubmit="return confirm('Delete this time slot? This cannot be undone.')">@csrf @method('DELETE')<button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Delete</button></form></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:10px">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width:48px;height:48px;background:#fff;border:1px solid #eef2f7;color:#94a3b8"><i class="fas fa-moon"></i></div>
                                    <p class="mb-1" style="font-weight:700;color:#475569;font-size:0.88rem">Day off</p>
                                    <p class="mb-3" style="color:#94a3b8;font-size:0.78rem">No slots — patients can't book on {{ $dayName }}</p>
                                    <button onclick="showBulkModal('{{ $day }}')" class="doctor-btn doctor-btn-primary doctor-btn-sm"><i class="fas fa-plus me-1"></i>Add time slots</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
function scrollToDay(day){
  const el=document.getElementById('day-'+day);
  if(el){el.scrollIntoView({behavior:'smooth',block:'start'}); document.querySelectorAll('.week-tab').forEach(b=>b.classList.remove('active')); document.querySelector(`.week-tab[data-day="${day}"]`)?.classList.add('active');}
}
function showBulkModal(day){document.getElementById('bulkDay').value=day;document.getElementById('bulkModal').style.display='flex';document.body.style.overflow='hidden'; const title=document.getElementById('bulkModalTitle'); if(title) title.textContent='Quick Add — '+day.charAt(0).toUpperCase()+day.slice(1);}
function closeBulkModal(){document.getElementById('bulkModal').style.display='none';document.body.style.overflow='auto';}
document.addEventListener('click',function(e){ const m=document.getElementById('bulkModal'); if(e.target===m) closeBulkModal();});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){ const m=document.getElementById('bulkModal'); if(m && m.style.display==='flex') closeBulkModal();}});
(function(){
  const modal=document.getElementById('bulkModal');
  if(!modal) return;
  const start=modal.querySelector('input[name="start_time"]');
  const end=modal.querySelector('input[name="end_time"]');
  const dur=modal.querySelector('select[name="slot_duration"]');
  let preview=document.getElementById('bulkPreview');
  if(!preview){ preview=document.createElement('div'); preview.id='bulkPreview'; preview.style.cssText='margin-top:1rem;padding:0.7rem 1rem;background:#eff6ff;border:1px solid #dbeafe;border-radius:8px;color:#1e40af;font-size:0.78rem;font-weight:600'; modal.querySelector('form').appendChild(preview); }
  function upd(){
    if(start.value && end.value && dur.value){
      const s=start.value.split(':'), e=end.value.split(':');
      const sm=parseInt(s[0])*60+parseInt(s[1]), em=parseInt(e[0])*60+parseInt(e[1]);
      const mins=em-sm;
      if(mins>0){ const slots=Math.floor(mins/parseInt(dur.value)); preview.textContent=`${slots} slots • ${dur.value} min each • ${mins} min total`; preview.style.display='block'; } else { preview.textContent='End must be after start'; }
    } else preview.style.display='none';
  }
  [start,end,dur].forEach(el=> el && el.addEventListener('input', upd));
})();
</script>
@push('modals')
<div id="bulkModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:9999999;align-items:center;justify-content:center;padding:1rem">
    <div style="background:#fff;border-radius:12px;max-width:520px;width:100%;max-height:90vh;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.15);border:1px solid #eef2f7;display:flex;flex-direction:column">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.5rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
            <h5 id="bulkModalTitle" style="margin:0;font-weight:800;color:#0f172a;font-size:1rem"><i class="fas fa-plus me-2" style="color:#64748b"></i>Quick Add Time Slot</h5>
            <button type="button" onclick="closeBulkModal()" style="background:#fff;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="{{ route('doctor.availability.store') }}" style="padding:1.5rem;overflow:auto">
            @csrf
            <input type="hidden" name="day_of_week" id="bulkDay">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                <div><label style="display:block;margin-bottom:0.35rem;font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Start Time</label><input type="time" name="start_time" required style="width:100%;padding:0.6rem 0.9rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc"></div>
                <div><label style="display:block;margin-bottom:0.35rem;font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">End Time</label><input type="time" name="end_time" required style="width:100%;padding:0.6rem 0.9rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div><label style="display:block;margin-bottom:0.35rem;font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Slot Duration</label><select name="slot_duration" required style="width:100%;padding:0.6rem 0.9rem;border:1px solid #e2e8f0;border-radius:10px;background:#fff"><option value="15">15 minutes</option><option value="30" selected>30 minutes</option><option value="45">45 minutes</option><option value="60">60 minutes</option></select></div>
                <div><label style="display:block;margin-bottom:0.35rem;font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Max Bookings</label><select name="max_bookings_per_slot" required style="width:100%;padding:0.6rem 0.9rem;border:1px solid #e2e8f0;border-radius:10px;background:#fff"><option value="1" selected>1 patient</option><option value="2">2 patients</option><option value="3">3 patients</option></select></div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1.25rem;margin-top:1.25rem;border-top:1px solid #eef2f7">
                <button type="button" onclick="closeBulkModal()" style="padding:0.55rem 1.2rem;background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;font-weight:600;cursor:pointer">Cancel</button>
                <button type="submit" style="padding:0.55rem 1.4rem;background:#1e293b;color:#fff;border:1px solid #1e293b;border-radius:10px;font-weight:700;cursor:pointer"><i class="fas fa-plus me-1"></i>Add Slot</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endsection
