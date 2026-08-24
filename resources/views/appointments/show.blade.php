@extends('master')

@section('title', 'Appointment Details')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.dashboard-header .btn-back{background:rgba(255,255,255,0.15)!important;border:1px solid rgba(255,255,255,0.32)!important;color:#fff!important;border-radius:10px!important;padding:0.5rem 1rem!important;font-weight:600!important;font-size:0.83rem!important}
.dashboard-header .btn-back:hover{background:#fff!important;color:#1e3a8a!important;border-color:#fff!important}
.section-head-modern h5{color:#fff!important}
.section-head-modern p{color:rgba(255,255,255,0.75)!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#1e293b;border-bottom:1px solid #0f172a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:rgba(255,255,255,0.12)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.18)!important}
.status-badge{padding:0.35rem 0.65rem;border-radius:99px;font-size:0.70rem;font-weight:700;border:1px solid transparent}
.status-badge.pending{background:#fef3c7;color:#92400e;border-color:#fde68a}
.status-badge.confirmed{background:#dbeafe;color:#1e40af;border-color:#bfdbfe}
.status-badge.completed{background:#d1fae5;color:#065f46;border-color:#a7f3d0}
.status-badge.cancelled{background:#fee2e2;color:#991b1b;border-color:#fecaca}
</style>
@endpush

@section('content')
@php $patient = $appointment->patient; @endphp
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-calendar-check me-2"></i>Appointment Details</h2>
                    <p>{{ $appointment->appointment_date->format('l, F j, Y g:i A') }} · {{ ucfirst(str_replace('_',' ', $appointment->appointment_type)) }} @if($appointment->appointment_number) · {{ $appointment->appointment_number }} @endif</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @php $cls=['pending'=>'pending','confirmed'=>'confirmed','completed'=>'completed','cancelled'=>'cancelled','no_show'=>'cancelled'][$appointment->status]??'pending' @endphp
                    <span class="status-badge {{ $cls }}">{{ ucfirst(str_replace('_',' ', $appointment->status)) }}</span>
                    <a href="{{ route('appointments.index') }}" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="section-head-modern">
                        <div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-calendar-alt"></i></div><div><h5>Appointment Overview</h5><p>{{ $appointment->appointment_date->format('l, F j, Y') }} · {{ $appointment->appointment_date->format('g:i A') }} - {{ $appointment->appointment_end->format('g:i A') }}</p></div></div>
                        <span class="badge bg-light text-muted border" style="font-size:0.70rem">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }} min</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:#475569"><i class="fas fa-calendar"></i></div>
                        <div><div style="font-weight:700;color:#0f172a">{{ ucfirst(str_replace('_',' ', $appointment->appointment_type)) }}</div><small style="color:#64748b">{{ $appointment->appointment_date->format('M j, Y g:i A') }}</small></div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-head-modern">
                        <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-user-md"></i></div><div><h5>Your Doctor</h5><p>{{ $appointment->doctor->specialty->name ?? 'General' }}</p></div></div>
                        <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.78rem;font-weight:600"><i class="fas fa-user me-1"></i>View Profile</a>
                    </div>
                    <div class="d-flex gap-3">
                        @if($appointment->doctor->profile_image)<img src="{{ asset('storage/'.$appointment->doctor->profile_image) }}" alt="" class="rounded-3 border" style="width:64px;height:64px;object-fit:cover">@else<div class="d-flex align-items-center justify-content-center rounded-3" style="width:64px;height:64px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b"><i class="fas fa-user-md"></i></div>@endif
                        <div class="flex-grow-1">
                            <div style="font-weight:700;color:#0f172a">{{ e($appointment->doctor->user->name) }}</div>
                            <small style="color:#2563eb;font-weight:600">{{ e($appointment->doctor->specialty->name ?? '') }}</small>
                            <div class="d-flex align-items-center gap-1 mt-1" style="font-size:0.82rem;color:#f59e0b">@for($i=1;$i<=5;$i++)@if($i <= floor($appointment->doctor->average_rating))<i class="fas fa-star"></i>@elseif($i-0.5 <= $appointment->doctor->average_rating)<i class="fas fa-star-half-alt"></i>@else<i class="far fa-star"></i>@endif @endfor <span style="color:#64748b;font-size:0.74rem">{{ number_format($appointment->doctor->average_rating,1) }} ({{ $appointment->doctor->total_reviews }})</span></div>
                            <div class="d-flex gap-2 mt-2">@if($appointment->doctor->phone)<a href="tel:{{ $appointment->doctor->phone }}" class="btn btn-sm" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:8px;font-size:0.78rem"><i class="fas fa-phone me-1"></i>Call</a>@endif</div>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-clipboard-list"></i></div><div><h5>Appointment Information</h5><p>Reason · symptoms · notes</p></div></div></div>
                    <div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b;font-size:0.70rem">REASON</small><div style="font-size:0.88rem;color:#0f172a;margin-top:0.25rem">{{ e($appointment->reason) }}</div></div>
                    @if($appointment->symptoms)<div class="p-3 mb-3" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px"><small style="font-weight:700;letter-spacing:0.06em;color:#92400e;font-size:0.70rem">SYMPTOMS</small><div style="font-size:0.88rem;color:#334155;margin-top:0.25rem">{{ e($appointment->symptoms) }}</div></div>@endif
                    @if($appointment->patient_notes)<div class="p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b;font-size:0.70rem">YOUR NOTES</small><div style="font-size:0.88rem;color:#334155;margin-top:0.25rem">{{ e($appointment->patient_notes) }}</div></div>@endif
                </div>

                @if($appointment->status=='completed' && $appointment->doctor_notes)
                    <div class="table-card" style="border-left:3px solid #2563eb">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-user-md"></i></div><div><h5>Doctor's Assessment</h5><p>Completed {{ $appointment->completed_at? $appointment->completed_at->format('M j, Y g:i A'):'' }}</p></div></div></div>
                        <div class="p-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;font-size:0.92rem;color:#1e293b;line-height:1.6">{{ e($appointment->doctor_notes) }}</div>
                        @if($appointment->follow_up_required)<div class="mt-3 p-2" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;color:#92400e;font-size:0.82rem;font-weight:600"><i class="fas fa-exclamation-triangle me-1"></i>Follow-up recommended</div>@endif
                    </div>
                @endif

                @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                    <div class="table-card">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#ecfdf5!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-prescription-bottle"></i></div><div><h5>Your Prescriptions</h5><p>{{ $appointment->prescriptions->count() }} active</p></div></div></div>
                        @foreach($appointment->prescriptions as $prescription)
                            <div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px">
                                <div class="d-flex justify-content-between align-items-start mb-2"><div><div style="font-weight:700;color:#065f46">{{ e($prescription->medication_name) }}</div><small style="color:#64748b">Prescribed {{ $prescription->created_at->format('M j, Y') }}</small></div><div class="d-flex gap-2"><a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.74rem">View</a><a href="{{ route('prescriptions.show', $prescription) }}?pdf=1" class="btn btn-sm" style="background:#059669;color:#fff;border-radius:8px;font-size:0.74rem">PDF</a></div></div>
                                <div class="row g-2" style="font-size:0.78rem;color:#475569"><div class="col-4"><strong>Dosage</strong><br>{{ e($prescription->dosage) }}</div><div class="col-4"><strong>Frequency</strong><br>{{ e($prescription->frequency) }}</div><div class="col-4"><strong>Duration</strong><br>{{ e($prescription->duration) }}</div></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($appointment->status=='completed')
                    <div class="table-card">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fffbeb!important;color:#92400e!important;border-color:#fde68a!important"><i class="fas fa-star"></i></div><div><h5>Your Review</h5><p>Share your experience</p></div></div></div>
                        @if($appointment->review)
                            <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div style="color:#f59e0b">@for($i=1;$i<=5;$i++)@if($i <= $appointment->review->rating)<i class="fas fa-star"></i>@else<i class="far fa-star"></i>@endif @endfor <small style="color:#64748b">{{ $appointment->review->created_at->format('M j, Y') }}</small></div>@if($appointment->review->comment)<p style="font-size:0.88rem;color:#334155;margin:0.5rem 0">{{ e($appointment->review->comment) }}</p>@endif<a href="{{ route('reviews.show', $appointment->review) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.74rem">View full review</a></div>
                        @else
                            <div class="text-center py-4"><div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:56px;height:56px;background:#fffbeb;border:1px solid #fde68a;color:#d97706"><i class="fas fa-star"></i></div><h6 style="font-weight:700;color:#0f172a">How was your appointment?</h6><p style="color:#64748b;font-size:0.84rem">Share your experience to help others</p><a href="{{ route('appointments.review', $appointment) }}" class="btn" style="background:#f59e0b;color:#fff;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600"><i class="fas fa-star me-1"></i>Leave a Review</a></div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="table-card" style="position:sticky;top:20px">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Quick Actions</h5><p>Manage appointment</p></div></div></div>
                    <div class="d-grid gap-2">
                        @if(in_array($appointment->status, ['pending','confirmed']) && $appointment->appointment_type=='video_call')<button onclick="joinVideoCall()" class="btn" style="background:#2563eb;color:#fff;border-radius:10px;padding:0.6rem;font-weight:600"><i class="fas fa-video me-2"></i>Join Video Call</button>@endif
                        @if($appointment->canBeRescheduled())<button onclick="rescheduleAppointment()" class="btn" style="background:#f59e0b;color:#fff;border-radius:10px;padding:0.6rem;font-weight:600"><i class="fas fa-calendar-alt me-2"></i>Reschedule</button>@endif
                        @if($appointment->canBeCancelled())<button onclick="showCancelModal()" class="btn" style="background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:0.6rem;font-weight:600"><i class="fas fa-times me-2"></i>Cancel Appointment</button>@endif
                        <a href="{{ route('doctors.show', $appointment->doctor) }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem;font-weight:500"><i class="fas fa-user-md me-2"></i>View Doctor Profile</a>
                        @if($appointment->doctor->phone)<a href="tel:{{ $appointment->doctor->phone }}" class="btn" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.6rem;font-weight:600"><i class="fas fa-phone me-2"></i>Call Doctor</a>@endif
                    </div>
                    <hr style="margin:1rem 0;border-top:1px solid #f1f5f9">
                    <div style="font-size:0.82rem;color:#475569"><div class="d-flex justify-content-between py-2 px-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px"><span style="color:#64748b">Booked on</span><span style="font-weight:600;color:#0f172a">{{ $appointment->created_at->format('M j, Y') }}</span></div>@if($appointment->cancelled_at)<div class="d-flex justify-content-between py-2 px-3 mt-2" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px"><span style="color:#991b1b">Cancelled</span><span style="font-weight:600;color:#991b1b">{{ $appointment->cancelled_at->format('M j, Y') }}</span></div>@endif</div>
                </div>
                @if(in_array($appointment->status, ['pending','confirmed']))
                    <div class="table-card">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-lightbulb"></i></div><div><h5>Preparation Tips</h5><p>For {{ str_replace('_',' ', $appointment->appointment_type) }}</p></div></div></div>
                        <ul style="list-style:none;padding:0;margin:0;font-size:0.82rem;color:#475569">
                            @if($appointment->appointment_type=='in_person')
                                <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Arrive 15 minutes early</li><li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Bring ID and insurance card</li><li><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Wear a mask if required</li>
                            @elseif($appointment->appointment_type=='video_call')
                                <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Test camera and microphone</li><li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Ensure stable internet</li><li><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Join 5 minutes early</li>
                            @else
                                <li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Ensure phone is charged</li><li class="mb-2"><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Be in a quiet location</li><li><i class="fas fa-check-circle me-2" style="color:#10b981"></i>Have medical history ready</li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rescheduleModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:12px;overflow:hidden"><div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0"><h5 class="modal-title" style="font-weight:700;color:#0f172a"><i class="fas fa-calendar-alt me-2" style="color:#f59e0b"></i>Reschedule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:8px;font-size:0.84rem"><strong>Select a new date and time.</strong><br><small>Appointments can only be rescheduled once.</small></div><form method="POST" action="{{ route('appointments.reschedule', $appointment) }}" id="rescheduleForm">@csrf<div class="mb-3"><label class="form-label" style="font-weight:600;font-size:0.82rem">Select Date</label><input type="date" name="reschedule_date" id="reschedule_date" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required></div><div class="mb-3"><label class="form-label" style="font-weight:600;font-size:0.82rem">Available Time Slots</label><select name="new_appointment_date" id="new_appointment_date" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0" required><option value="">Select a date first</option></select><small style="color:#64748b;display:none" id="slotsLoading"><i class="fas fa-spinner fa-spin me-1"></i>Loading...</small><small style="color:#dc2626;display:none" id="noSlotsMessage">No available slots on this date.</small></div></form></div><div class="modal-footer" style="border-top:1px solid #f1f5f9"><button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px" data-bs-dismiss="modal">Keep Current Time</button><button type="submit" form="rescheduleForm" class="btn" style="background:#f59e0b;color:#fff;border-radius:8px" id="rescheduleSubmitBtn" disabled><i class="fas fa-calendar-alt me-2"></i>Reschedule</button></div></div></div></div>
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:12px;overflow:hidden"><div class="modal-header" style="background:#fef2f2;border-bottom:1px solid #fecaca"><h5 class="modal-title" style="font-weight:700;color:#991b1b"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;font-size:0.84rem"><strong>Are you sure you want to cancel?</strong><br>This cannot be undone.</div><form method="POST" action="{{ route('appointments.cancel', $appointment) }}" id="cancelForm">@csrf<div class="mb-3"><label class="form-label" style="font-weight:600;font-size:0.82rem">Reason (optional)</label><textarea name="cancellation_reason" id="cancellation_reason" rows="4" class="form-control" style="border-radius:10px;border:1px solid #e2e8f0" placeholder="Please let us know why..."></textarea></div></form></div><div class="modal-footer" style="border-top:1px solid #f1f5f9"><button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px" data-bs-dismiss="modal">Keep Appointment</button><button type="submit" form="cancelForm" class="btn" style="background:#dc2626;color:#fff;border-radius:8px">Cancel Appointment</button></div></div></div></div>
@endsection

@push('scripts')
<script>
function showCancelModal(){ new bootstrap.Modal(document.getElementById('cancelModal')).show(); }
function rescheduleAppointment(){ new bootstrap.Modal(document.getElementById('rescheduleModal')).show(); }
document.getElementById('reschedule_date').addEventListener('change', function(){
    const date=this.value, timeSelect=document.getElementById('new_appointment_date'), loadingMsg=document.getElementById('slotsLoading'), noSlotsMsg=document.getElementById('noSlotsMessage'), submitBtn=document.getElementById('rescheduleSubmitBtn');
    if(!date){ timeSelect.innerHTML='<option value=\"\">Select a date first</option>'; submitBtn.disabled=true; return; }
    loadingMsg.style.display='block'; noSlotsMsg.style.display='none'; timeSelect.innerHTML='<option value=\"\">Loading...</option>'; submitBtn.disabled=true;
    fetch(`/doctors/{{ $appointment->doctor->id }}/slots?date=${date}`).then(r=>r.json()).then(data=>{
        loadingMsg.style.display='none'; timeSelect.innerHTML='<option value=\"\">Select a time slot</option>';
        if(data.slots && data.slots.length>0){ data.slots.forEach(slot=>{ const time=slot.datetime.split(' ')[1]; const [hours,minutes]=time.split(':'); const period=parseInt(hours)>=12?'PM':'AM'; const displayHour=parseInt(hours)>12?parseInt(hours)-12:(parseInt(hours)==0?12:parseInt(hours)); const option=document.createElement('option'); option.value=slot.datetime; option.textContent=displayHour+':'+minutes+' '+period; timeSelect.appendChild(option); }); submitBtn.disabled=false; } else { noSlotsMsg.style.display='block'; }
    }).catch(e=>{ loadingMsg.style.display='none'; timeSelect.innerHTML='<option value=\"\">Error loading slots</option>'; });
});
function joinVideoCall(){ window.open(`/video/room/{{ $appointment->id }}`, '_blank'); }
</script>
@endpush
