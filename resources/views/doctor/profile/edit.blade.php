@extends('master')

@section('title', 'Doctor Profile')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#64748b!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.form-label{font-size:0.82rem;font-weight:600;color:#1e293b}
.form-control,.form-select{border-radius:10px!important;border:1px solid #e2e8f0!important;font-size:0.88rem!important}
.appointment-type-preference-card{border:1px solid #e2e8f0!important;border-radius:12px!important;transition:all 0.2s ease;cursor:pointer;background:#fff}
.appointment-type-preference-card.enabled{border-color:#a7f3d0!important;background:#ecfdf5!important}
.appointment-type-preference-card.disabled{border-color:#e2e8f0!important;background:#fff!important}
.appointment-type-preference-card:hover{box-shadow:0 4px 12px rgba(15,23,42,0.08)!important;transform:translateY(-1px)}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-user-md me-2"></i>Doctor Profile</h2>
                    <p>Manage your professional profile, practice address and appointment preferences</p>
                </div>
                <span class="badge" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:99px;padding:0.4rem 0.85rem;font-size:0.74rem;font-weight:600"><i class="fas fa-shield-alt me-1"></i>Verified</span>
            </div>
        </div>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert d-flex align-items-center" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:0.9rem"><i class="fas fa-exclamation-circle me-2"></i><ul class="mb-0" style="font-size:0.84rem">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('doctor.profile.update') }}" enctype="multipart/form-data">
        @csrf @method('PATCH')

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon"><i class="fas fa-user"></i></div>
                    <div><h5>Basic Information</h5><p>Public profile · specialty, bio & languages</p></div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label">Profile Image</label>
                    <div class="d-flex align-items-center gap-4">
                        <div style="width:80px;height:80px;position:relative;flex-shrink:0">
                            @if($doctor->profile_image_url)
                                <img src="{{ $doctor->profile_image_url }}" alt="" class="rounded-circle border" id="profileImagePreview" style="width:80px;height:80px;object-fit:cover;overflow:hidden" onerror="this.style.display='none'; document.getElementById('profileImagePlaceholder').classList.remove('d-none'); document.getElementById('profileImagePlaceholder').classList.add('d-flex');">
                                <div id="profileImagePlaceholder" class="rounded-circle bg-light border align-items-center justify-content-center d-none" style="width:80px;height:80px;position:absolute;top:0;left:0"><i class="fas fa-user-md fs-4 text-muted"></i></div>
                            @else
                                <div id="profileImagePlaceholder" class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:80px;height:80px"><i class="fas fa-user-md fs-4 text-muted"></i></div>
                            @endif
                        </div>
                        <div class="flex-grow-1"><input type="file" name="profile_image" id="profile_image" accept="image/*" class="form-control"><small style="color:#64748b;font-size:0.72rem">JPG, PNG, GIF, WEBP up to 2MB</small></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="specialty_id" class="form-label">Specialty <span class="text-danger">*</span></label>
                    <select name="specialty_id" id="specialty_id" required class="form-select">
                        <option value="">Select Specialty</option>
                        @foreach($specialties as $specialty)<option value="{{ $specialty->id }}" {{ old('specialty_id', $doctor->specialty_id) == $specialty->id ? 'selected' : '' }}>{{ $specialty->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $doctor->phone) }}" class="form-control" required placeholder="+1234567890">
                    <small style="color:#64748b;font-size:0.72rem">Required for phone call appointments</small>
                </div>
                <input type="hidden" name="consultation_fee" value="{{ old('consultation_fee', $doctor->consultation_fee / 100) }}">
                <div class="col-md-6">
                    <label for="appointment_duration" class="form-label">Appointment Duration <span class="text-danger">*</span></label>
                    <select name="appointment_duration" id="appointment_duration" required class="form-select">
                        @foreach([15,30,45,60,90,120] as $duration)<option value="{{ $duration }}" {{ old('appointment_duration', $doctor->appointment_duration) == $duration ? 'selected' : '' }}>{{ $duration }} minutes</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="bio" class="form-label">Professional Bio</label>
                    <textarea name="bio" id="bio" rows="4" placeholder="Tell patients about your experience, qualifications, and approach to healthcare..." class="form-control">{{ old('bio', $doctor->bio) }}</textarea>
                    <small style="color:#64748b;font-size:0.72rem">Displayed on your public profile</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Languages Spoken</label>
                    <div class="row g-2">
                        @php $commonLanguages = ['English','Spanish','French','German','Italian','Portuguese','Chinese','Japanese','Korean','Arabic','Hindi','Russian']; $doctorLanguages = old('languages', $doctor->languages ?? []); @endphp
                        @foreach($commonLanguages as $language)
                            <div class="col-md-3 col-6"><div class="form-check"><input type="checkbox" name="languages[]" value="{{ $language }}" {{ in_array($language, $doctorLanguages) ? 'checked' : '' }} class="form-check-input" id="lang_{{ $loop->index }}"><label class="form-check-label" for="lang_{{ $loop->index }}" style="font-size:0.84rem">{{ $language }}</label></div></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-map-marker-alt"></i></div>
                    <div><h5>Practice Address</h5><p>Where patients find you · used on landing page</p></div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-12"><label for="address" class="form-label">Street Address</label><input type="text" name="address" id="address" value="{{ old('address', $doctor->address) }}" class="form-control"></div>
                <div class="col-md-6"><label for="city" class="form-label">City</label><input type="text" name="city" id="city" value="{{ old('city', $doctor->city) }}" class="form-control"></div>
                <div class="col-md-6"><label for="state" class="form-label">State/Province</label><input type="text" name="state" id="state" value="{{ old('state', $doctor->state) }}" class="form-control"></div>
                <div class="col-md-6"><label for="zip_code" class="form-label">ZIP/Postal Code</label><input type="text" name="zip_code" id="zip_code" value="{{ old('zip_code', $doctor->zip_code) }}" class="form-control"></div>
                <div class="col-md-6"><label for="country" class="form-label">Country</label><input type="text" name="country" id="country" value="{{ old('country', $doctor->country) }}" class="form-control"></div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-cog"></i></div>
                    <div><h5>Appointment Settings</h5><p>Auto-approve, cancellation & rescheduling</p></div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-12"><div class="d-flex justify-content-between align-items-center p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div><label for="auto_approve_appointments" class="form-label mb-0">Auto-approve appointments</label><small style="color:#64748b;font-size:0.78rem">Automatically confirm new requests</small></div><div class="form-check form-switch m-0"><input type="checkbox" name="auto_approve_appointments" id="auto_approve_appointments" value="1" {{ old('auto_approve_appointments', $doctor->auto_approve_appointments) ? 'checked' : '' }} class="form-check-input"></div></div></div>
                <div class="col-12"><div class="d-flex justify-content-between align-items-center p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div><label for="allow_cancellation" class="form-label mb-0">Allow patient cancellations</label><small style="color:#64748b;font-size:0.78rem">Let patients cancel their own appointments</small></div><div class="form-check form-switch m-0"><input type="checkbox" name="allow_cancellation" id="allow_cancellation" value="1" {{ old('allow_cancellation', $doctor->allow_cancellation) ? 'checked' : '' }} class="form-check-input"></div></div></div>
                <div class="col-12"><div class="d-flex justify-content-between align-items-center p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div><label for="allow_rescheduling" class="form-label mb-0">Allow patient rescheduling</label><small style="color:#64748b;font-size:0.78rem">Let patients reschedule their own appointments</small></div><div class="form-check form-switch m-0"><input type="checkbox" name="allow_rescheduling" id="allow_rescheduling" value="1" {{ old('allow_rescheduling', $doctor->allow_rescheduling) ? 'checked' : '' }} class="form-check-input"></div></div></div>
                <div class="col-md-6"><label for="cancellation_hours" class="form-label">Minimum cancellation notice <span class="text-danger">*</span></label><select name="cancellation_hours" id="cancellation_hours" required class="form-select">@foreach([1,2,4,6,12,24,48,72] as $hours)<option value="{{ $hours }}" {{ old('cancellation_hours', $doctor->cancellation_hours) == $hours ? 'selected' : '' }}>{{ $hours }} {{ $hours==1?'hour':'hours' }}</option>@endforeach</select><small style="color:#64748b;font-size:0.72rem">Patients must cancel at least this many hours before</small></div>
            </div>
            <div class="mt-4 pt-4" style="border-top:1px solid #f1f5f9">
                <label class="form-label">Available Appointment Types</label>
                <p style="color:#64748b;font-size:0.78rem;margin-bottom:0.75rem">Only enabled types appear when patients book. At least one must stay enabled.</p>
                @php $appointmentTypes = ['in_person'=>['label'=>'In-Person','description'=>'Face-to-face at clinic','icon'=>'fas fa-hospital','color'=>'#2563eb'],'video_call'=>['label'=>'Video Call','description'=>'Online video','icon'=>'fas fa-video','color'=>'#059669'],'phone_call'=>['label'=>'Phone Call','description'=>'Phone call','icon'=>'fas fa-phone','color'=>'#0e7490']]; @endphp
                <div class="row g-3">
                    @foreach($appointmentTypes as $type => $config)
                        <div class="col-md-4"><div class="card h-100 appointment-type-preference-card {{ $doctor->isAppointmentTypeEnabled($type) ? 'enabled' : 'disabled' }}"><div class="card-body text-center"><div class="mb-2 d-flex align-items-center justify-content-center mx-auto" style="width:44px;height:44px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:{{ $config['color'] }}"><i class="{{ $config['icon'] }}"></i></div><h6 style="font-size:0.88rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem">{{ $config['label'] }}</h6><p style="font-size:0.74rem;color:#64748b;margin-bottom:0.75rem">{{ $config['description'] }}</p><div class="form-check form-switch d-flex justify-content-center"><input class="form-check-input appointment-type-toggle" type="checkbox" name="appointment_types[]" value="{{ $type }}" id="appointment_type_{{ $type }}" {{ $doctor->isAppointmentTypeEnabled($type) ? 'checked' : '' }}><label class="form-check-label ms-2" for="appointment_type_{{ $type }}" style="font-size:0.82rem"><span class="status-text">{{ $doctor->isAppointmentTypeEnabled($type) ? 'Enabled' : 'Disabled' }}</span></label></div></div></div></div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-3 mb-4">
            <a href="{{ route('dashboard') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.65rem 1.1rem;font-weight:500;font-size:0.88rem"><i class="fas fa-times me-2"></i>Cancel</a>
            <button type="submit" class="btn" style="background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%);color:#fff;border:none;border-radius:10px;padding:0.65rem 1.4rem;font-weight:600;font-size:0.88rem;box-shadow:0 4px 14px rgba(37,99,235,0.25)"><i class="fas fa-save me-2"></i>Save Changes</button>
        </div>
    </form>

    <div class="table-card">
        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-globe"></i></div><div><h5>Your Landing Page</h5><p>Public page to attract patients</p></div></div></div>
        <div class="d-flex gap-3 flex-wrap"><a href="{{ route('doctor.landing-page.index') }}" class="btn" style="background:#1e293b;color:#fff;border:1px solid #1e293b;border-radius:10px;padding:0.6rem 1rem;font-weight:600;font-size:0.84rem"><i class="fas fa-edit me-2"></i>Manage Landing Page</a>@if($doctor->landingPage && $doctor->landingPage->is_published)<a href="{{ $doctor->landingPage->url }}" target="_blank" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#2563eb;border-radius:10px;padding:0.6rem 1rem;font-weight:600;font-size:0.84rem"><i class="fas fa-external-link-alt me-2"></i>View Public Page</a>@endif</div>
    </div>

    <div class="table-card">
        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-eye"></i></div><div><h5>Profile Preview</h5><p>How patients see you</p></div></div></div>
        <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px">
            <div class="d-flex align-items-center mb-3">
                @if($doctor->profile_image_url)<img src="{{ $doctor->profile_image_url }}" alt="" class="rounded-circle me-3" style="width:64px;height:64px;object-fit:cover;border:1px solid #e2e8f0">@else<div class="rounded-circle bg-white border d-flex align-items-center justify-content-center me-3" style="width:64px;height:64px"><i class="fas fa-user-md text-muted"></i></div>@endif
                <div><h5 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0">Dr. {{ $doctor->user->name }}</h5><p style="color:#64748b;font-size:0.84rem;margin:0">{{ $doctor->specialty->name ?? 'Specialty not set' }}</p><div class="d-flex align-items-center gap-1"><span style="color:#f59e0b">@for($i=1;$i<=5;$i++)@if($i<=floor($doctor->average_rating))<i class="fas fa-star small"></i>@elseif($i-0.5 <= $doctor->average_rating)<i class="fas fa-star-half-alt small"></i>@else<i class="far fa-star small"></i>@endif @endfor</span><small style="color:#64748b;font-size:0.72rem">{{ number_format($doctor->average_rating,1) }} ({{ $doctor->total_reviews }})</small></div></div>
            </div>
            @if($doctor->bio)<p style="font-size:0.88rem;color:#334155">{{ $doctor->bio }}</p>@endif
            <div class="row g-2" style="font-size:0.82rem;color:#475569"><div class="col-md-6"><strong>Duration:</strong> {{ $doctor->appointment_duration }} min</div>@if($doctor->languages)<div class="col-12"><strong>Languages:</strong> {{ implode(', ', $doctor->languages) }}</div>@endif</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.appointment-type-toggle');
    toggles.forEach(function(toggle){
        const card = toggle.closest('.appointment-type-preference-card');
        const statusText = toggle.parentElement.querySelector('.status-text');
        toggle.addEventListener('change', function(){
            const isChecked=this.checked;
            const enabled = document.querySelectorAll('.appointment-type-toggle:checked');
            if(enabled.length===0){ this.checked=true; card.classList.remove('disabled'); card.classList.add('enabled'); statusText.textContent='Enabled'; showNotification('At least one appointment type must be enabled.','warning'); return; }
            if(isChecked){ card.classList.remove('disabled'); card.classList.add('enabled'); statusText.textContent='Enabled'; } else { card.classList.remove('enabled'); card.classList.add('disabled'); statusText.textContent='Disabled'; }
            const enabledTypes=[]; document.querySelectorAll('.appointment-type-toggle:checked').forEach(t=>enabledTypes.push(t.value));
            const loading=showNotification('Saving appointment preferences...','info',false);
            fetch('{{ route("doctor.settings.appointments.update") }}',{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content'),'Accept':'application/json'},body:JSON.stringify({appointment_types: enabledTypes})}).then(r=>r.json()).then(data=>{ if(loading) loading.remove(); showNotification(data.message||'Appointment preferences updated!','success');}).catch(e=>{ if(loading) loading.remove(); showNotification('Failed to update preferences.','error'); });
        });
    });
    function showNotification(message, type='info', autoHide=true){
        document.querySelectorAll('.appointment-notification').forEach(n=>n.remove());
        const notification=document.createElement('div');
        notification.className=`alert alert-${type==='error'?'danger':type} appointment-notification`;
        notification.style.cssText='position:fixed;top:85px;right:20px;z-index:9999;min-width:300px;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        const icon=type==='success'?'check-circle':type==='warning'?'exclamation-triangle':type==='error'?'exclamation-circle':'info-circle';
        notification.innerHTML=`<i class="fas fa-${icon} me-2"></i>${message}<button type="button" class="btn-close" aria-label="Close"></button>`;
        notification.querySelector('.btn-close').addEventListener('click',()=>notification.remove());
        document.body.appendChild(notification);
        if(autoHide) setTimeout(()=>{ if(notification.parentElement) notification.remove(); },3000);
        return notification;
    }
});
document.addEventListener('DOMContentLoaded', function(){
    const fileInput=document.getElementById('profile_image');
    if(!fileInput) return;
    fileInput.addEventListener('change', function(event){
        const file=event.target.files&&event.target.files[0];
        if(!file) return;
        if(!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)){ alert('Please select a valid image file (JPG, PNG, or GIF).'); fileInput.value=''; return; }
        if(file.size>2*1024*1024){ alert('Image must be under 2MB.'); fileInput.value=''; return; }
        const reader=new FileReader();
        reader.onload=function(e){
            const preview=document.getElementById('profileImagePreview');
            const placeholder=document.getElementById('profileImagePlaceholder');
            if(preview){ preview.style.display=''; preview.src=e.target.result; preview.onerror=function(){ this.style.display='none'; if(placeholder){ placeholder.classList.remove('d-none'); placeholder.classList.add('d-flex'); } }; if(placeholder){ placeholder.classList.add('d-none'); placeholder.classList.remove('d-flex'); } }
            else if(placeholder){ const img=document.createElement('img'); img.id='profileImagePreview'; img.src=e.target.result; img.className='rounded-circle border'; img.style.cssText='width:80px;height:80px;object-fit:cover;overflow:hidden;'; img.onerror=function(){ this.style.display='none'; placeholder.classList.remove('d-none'); placeholder.classList.add('d-flex'); }; placeholder.parentNode.appendChild(img); placeholder.classList.add('d-none'); placeholder.classList.remove('d-flex'); }
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush
