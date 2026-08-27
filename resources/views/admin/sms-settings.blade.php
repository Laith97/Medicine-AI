@extends('layouts.admin')
@section('title','SMS Provider Settings')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-sms" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">SMS Provider Settings</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">System-wide provider & hospital/doctor hierarchy overview</p>
            </div>
        </div>
        <span class="badge d-none d-md-inline" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:6px 12px;font-weight:700">{{ $systemProvider ?? 'Not Set' }} · System Default</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569"><i class="fas fa-building"></i></div><div><div style="font-weight:800;color:#1e293b;font-size:1.1rem">{{ $totalHospitals }}</div><small class="text-muted">Total Hospitals</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-check"></i></div><div><div style="font-weight:800;color:#1e293b;font-size:1.1rem">{{ $hospitalsWithCustomProvider }}</div><small class="text-muted">With Custom Provider</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#fffbeb;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-user-md"></i></div><div><div style="font-weight:800;color:#1e293b;font-size:1.1rem">{{ $totalDoctorsWithOverrides }}</div><small class="text-muted">Doctor Overrides</small></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-cog"></i></div><div><div style="font-weight:800;color:#1e293b;font-size:0.9rem" class="text-truncate">{{ $systemProvider ?? 'Not Set' }}</div><small class="text-muted">System Default</small></div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-globe" style="color:#64748b"></i> System Default Provider</h5></div>
                <div class="card-body p-4">
                    <form id="systemProviderForm">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label for="system_sms_provider" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Select System Provider</label>
                            <select id="system_sms_provider" name="sms_provider" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                <option value="">-- No Default (Require Hospital/Doctor Override) --</option>
                                <option value="twilio" {{ $systemProvider === 'twilio' ? 'selected' : '' }}>Twilio</option>
                                <option value="plivo" {{ $systemProvider === 'plivo' ? 'selected' : '' }}>Plivo</option>
                                <option value="messagebird" {{ $systemProvider === 'messagebird' ? 'selected' : '' }}>MessageBird</option>
                                <option value="unifonic" {{ $systemProvider === 'unifonic' ? 'selected' : '' }}>Unifonic</option>
                                <option value="smsgatewayhub" {{ $systemProvider === 'smsgatewayhub' ? 'selected' : '' }}>SMS Gateway Hub</option>
                                <option value="log" {{ $systemProvider === 'log' ? 'selected' : '' }}>Log Only (Development)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn text-white w-100" id="saveSystemBtn" style="background:#0f172a;border:none;border-radius:10px;font-weight:700;padding:0.6rem"><i class="fas fa-save me-1"></i>Update System Default</button>
                    </form>
                    <div id="systemMessageContainer" class="mt-3 d-none"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h6 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-cogs" style="color:#64748b"></i> Provider Configuration</h6></div>
                <div class="list-group list-group-flush">
                    @foreach($providers as $key => $provider)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-3" style="border-bottom:1px solid #f1f5f9">
                            <div class="d-flex align-items-center gap-2">
                                @php $providerClass = strtolower($key); @endphp
                                <div style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.65rem;color:#fff;flex-shrink:0" class="{{ $providerClass === 'twilio' ? '' : '' }} @if($providerClass=='twilio'){{ '' }}@endif" style="background: @if($providerClass=='twilio')#F22F46 @elseif($providerClass=='plivo')#00A8E8 @elseif($providerClass=='messagebird')#1496FF @elseif($providerClass=='unifonic')#4CAF50 @elseif($providerClass=='smsgatewayhub')#FF9800 @else #6c757d @endif; width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.65rem;color:#fff">{{ strtoupper(substr($key, 0, 2)) }}</div>
                                <span style="font-weight:600;color:#0f172a;font-size:0.88rem">{{ $provider['name'] }}</span>
                            </div>
                            @if($provider['configured'])
                                <span class="badge bg-success" style="border-radius:20px;font-size:0.68rem"><i class="fas fa-check-circle me-1"></i>Configured</span>
                            @else
                                <span class="badge bg-light border text-muted" style="border-radius:20px;font-size:0.68rem">Not Configured</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-sitemap" style="color:#64748b"></i> SMS Provider Hierarchy</h5></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 p-3 mb-3" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:14px;color:#fff">
                        <div class="text-center px-3 py-2" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:10px;min-width:120px"><div style="font-size:0.62rem;letter-spacing:0.06em;text-transform:uppercase;opacity:0.8;font-weight:800">System Default</div><div style="font-weight:800">{{ $systemProvider ?? 'Not Set' }}</div></div>
                        <span style="font-size:1.2rem;opacity:0.7">→</span>
                        <div class="text-center px-3 py-2" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:10px;min-width:120px"><div style="font-size:0.62rem;letter-spacing:0.06em;text-transform:uppercase;opacity:0.8;font-weight:800">Hospital</div><div style="font-weight:800;font-size:0.84rem">Custom or Inherit</div></div>
                        <span style="font-size:1.2rem;opacity:0.7">→</span>
                        <div class="text-center px-3 py-2" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:10px;min-width:120px"><div style="font-size:0.62rem;letter-spacing:0.06em;text-transform:uppercase;opacity:0.8;font-weight:800">Doctor</div><div style="font-weight:800;font-size:0.84rem">Custom or Inherit</div></div>
                    </div>
                    <div class="alert d-flex gap-2 mb-0" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;font-size:0.84rem"><i class="fas fa-info-circle mt-1"></i><div><strong>Priority Order:</strong> Doctor Override → Hospital Default → System Default<br><small>Each level can override the level above it, or inherit from it.</small></div></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-building" style="color:#64748b"></i> Hospitals & Provider Settings</h5></div>
                <div class="card-body p-3">
                    @if($hospitals->count() > 0)
                        <div class="d-flex flex-column gap-3">
                        @foreach($hospitals as $hospital)
                            <div class="p-3" style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid {{ $hospital['has_custom_provider'] || $hospital['doctor_overrides'] > 0 ? '#f59e0b' : '#10b981' }};border-radius:12px">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div style="font-weight:800;color:#0f172a">{{ $hospital['name'] }}</div>
                                        <div style="font-size:0.78rem;color:#64748b">{{ $hospital['doctor_count'] }} doctors @if($hospital['doctor_overrides'] > 0)· {{ $hospital['doctor_overrides'] }} with custom overrides @endif</div>
                                    </div>
                                    <div>
                                        @if($hospital['has_custom_provider'])
                                            <span class="badge" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:20px">{{ ucfirst($hospital['provider']) }}</span>
                                        @else
                                            <span class="badge bg-light border text-muted" style="border-radius:20px">Inherits from System</span>
                                        @endif
                                    </div>
                                </div>
                                @if($hospital['doctor_overrides'] > 0)
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-light border" onclick="toggleDoctors({{ $hospital['id'] }})" style="border-radius:10px;font-weight:600;font-size:0.78rem"><i class="fas fa-chevron-down me-1"></i>Show {{ $hospital['doctor_overrides'] }} doctor(s) with overrides</button>
                                        <div id="doctors-{{ $hospital['id'] }}" class="d-none mt-2" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.5rem">
                                            @foreach($hospital['doctors_with_overrides'] as $doctor)
                                                <div class="d-flex justify-content-between align-items-center py-2 px-2" style="border-bottom:1px solid #eef2f7">
                                                    <div style="font-size:0.84rem"><strong>Dr. {{ $doctor['name'] }}</strong> <span class="text-muted" style="font-size:0.76rem">· {{ $doctor['specialty'] ?? 'General' }}</span></div>
                                                    <span class="badge" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:20px">{{ ucfirst($doctor['sms_provider']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        </div>
                    @else
                        <div class="text-center py-4"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-building"></i></div><p class="text-muted small mb-0">No hospitals registered yet.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/sms-settings.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){ if(window.SmsSettings) SmsSettings.init({formId:'systemProviderForm',saveUrl:'/api/admin/sms-settings',saveBtnId:'saveSystemBtn',messageContainerId:'systemMessageContainer',showProviderInfo:false}); });
function toggleDoctors(hospitalId){const e=document.getElementById('doctors-'+hospitalId);if(!e)return;const t=e.previousElementSibling;if(e.classList.contains('d-none')){e.classList.remove('d-none');if(t) t.innerHTML='<i class="fas fa-chevron-up me-1"></i>Hide doctor overrides';}else{e.classList.add('d-none');if(t){const s=t.textContent.match(/\d+/);const a=s?s[0]:'0';t.innerHTML='<i class="fas fa-chevron-down me-1"></i>Show '+a+' doctor(s) with overrides';}}}
</script>
@endpush
@endsection
