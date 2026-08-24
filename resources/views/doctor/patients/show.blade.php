@extends('master')

@section('content')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%);border-radius:12px;padding:2.5rem;margin-bottom:2rem}
</style>
<div class="container-fluid" style="background:#f8fafc">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-user me-2"></i>Patient Details</h2>
                    <p class="text-muted mb-0">View patient information and history</p>
                </div>
                @php
                    $prev = url()->previous();
                    $isCases = str_contains($prev, 'cases-overview') || str_contains($prev, 'cases');
                    $backUrl = $prev !== url()->current() && str_contains($prev, '/doctor/') ? $prev : route('doctor.patients.index');
                    $backLabel = $isCases ? 'Back to Cases' : 'Back to Patients';
                @endphp
                <a href="{{ $backUrl }}" class="btn" style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#ffffff;border-radius:8px;padding:0.55rem 1rem;font-weight:500;font-size:0.84rem"><i class="fas fa-arrow-left me-2"></i>{{ $backLabel }}</a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4" style="background:#f8fafc">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05)">
                    <div class="text-center p-4">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:72px;height:72px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:1.8rem"><i class="fas fa-user"></i></span>
                        <h4 class="mt-3 mb-1" style="font-size:1.1rem;font-weight:600;color:#0f172a">{{ $patient->name }}</h4>
                        <p style="font-size:0.84rem;color:#64748b;margin:0">{{ $patient->age ?? 'N/A' }} years · {{ ucfirst($patient->gender ?? 'N/A') }}</p>
                        <div class="mt-3 pt-3 d-flex flex-column gap-2 text-start" style="border-top:1px solid #f1f5f9">
                            <span style="font-size:0.84rem;color:#334155;display:flex;align-items:center;gap:0.5rem"><i class="fas fa-envelope" style="color:#94a3b8;width:16px"></i>{{ $patient->email }}</span>
                            @if($patient->phone)<span style="font-size:0.84rem;color:#334155;display:flex;align-items:center;gap:0.5rem"><i class="fas fa-phone" style="color:#94a3b8;width:16px"></i>{{ $patient->phone }}</span>@endif
                        </div>
                    </div>
                    <div class="p-3 d-grid gap-2" style="background:#f8fafc;border-top:1px solid #f1f5f9">
                        <a href="{{ route('ai.ambient-listening.index', ['patient' => $patient->id]) }}" class="btn" style="background:#10b981;border:1px solid #10b981;color:#ffffff;border-radius:8px;padding:0.6rem;font-weight:600;font-size:0.84rem"><i class="fas fa-microphone me-2"></i>Start Consultation</a>
                        <a href="{{ route('doctor.patients.edit', $patient->id) }}" class="btn" style="background:#ffffff;border:1px solid #e2e8f0;color:#334155;border-radius:8px;padding:0.6rem;font-weight:500;font-size:0.84rem"><i class="fas fa-edit me-2"></i>Edit Patient</a>
                        <form action="{{ route('doctor.patients.destroy', $patient->id) }}" method="POST" onsubmit="return confirm('Delete this patient? All appointments and diagnoses will remain but patient account will be removed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn w-100" style="background:#ffffff;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:0.6rem;font-weight:500;font-size:0.84rem"><i class="fas fa-trash me-2"></i>Delete Patient</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05)" class="mb-4">
                    <div class="px-3 py-3 d-flex align-items-center gap-2" style="background:linear-gradient(135deg,#f8f9fa 0%,#f1f5f9 100%);border-bottom:2px solid #e2e8f0">
                        <span class="d-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:8px;background:#ffffff;border:1px solid #e2e8f0;color:#0ea5e9"><i class="fas fa-calendar-alt"></i></span>
                        <h5 class="mb-0" style="font-size:0.95rem;font-weight:600;color:#0f172a">Appointments History</h5>
                        <span class="ms-auto" style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.6rem;border-radius:99px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569">{{ $appointments->count() }}</span>
                    </div>
                    <div class="p-0">
                        @if($appointments->count() > 0)
                            <div class="table-responsive">
                                <table class="table mb-0" style="width:100%">
                                    <thead style="background:#f8fafc">
                                        <tr>
                                            <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0">Date</th>
                                            <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0">Type</th>
                                            <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0">Status</th>
                                            <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0;text-align:right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($appointments as $appointment)
                                            <tr style="border-bottom:1px solid #f1f5f9">
                                                <td style="padding:0.85rem 1rem;font-size:0.875rem;color:#334155">{{ $appointment->appointment_date->format('M d, Y h:i A') }}</td>
                                                <td style="padding:0.85rem 1rem"><span style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.5rem;border-radius:99px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569">{{ ucfirst($appointment->appointment_type) }}</span></td>
                                                <td style="padding:0.85rem 1rem"><span class="badge" style="background:{{ $appointment->status === 'completed' ? '#ecfdf5' : '#fef3c7' }};color:{{ $appointment->status === 'completed' ? '#065f46' : '#92400e' }};border:1px solid {{ $appointment->status === 'completed' ? '#a7f3d0' : '#fde68a' }};border-radius:99px;font-size:0.72rem">{{ ucfirst($appointment->status) }}</span></td>
                                                <td style="padding:0.85rem 1rem;text-align:right"><a href="{{ route('doctor.appointments.show', $appointment->id) }}" class="btn btn-sm" style="background:#ffffff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.35rem 0.6rem"><i class="fas fa-eye"></i></a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4" style="font-size:0.875rem;color:#94a3b8">No appointments yet.</div>
                        @endif
                    </div>
                </div>

                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05)">
                    <div class="px-3 py-3 d-flex align-items-center gap-2" style="background:linear-gradient(135deg,#f8f9fa 0%,#f1f5f9 100%);border-bottom:2px solid #e2e8f0">
                        <span class="d-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:8px;background:#ffffff;border:1px solid #e2e8f0;color:#10b981"><i class="fas fa-notes-medical"></i></span>
                        <h5 class="mb-0" style="font-size:0.95rem;font-weight:600;color:#0f172a">Diagnoses History</h5>
                        <span style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.6rem;border-radius:99px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569">{{ $diagnoses->count() }}</span>
                        <button type="button" onclick="togglePatientDiagnosisForm()" class="btn btn-sm ms-auto" style="background:#f59e0b;color:#fff;border:1px solid #f59e0b;border-radius:8px;font-weight:600;font-size:0.78rem;padding:0.35rem 0.7rem"><i class="fas fa-stethoscope me-1"></i>New Diagnosis</button>
                    </div>
                    <div class="p-3">
                        <!-- Inline Create Diagnosis (patient-level, manual type) -->
                        <div id="patientDiagnosisForm" style="display:none;background:#fff;border:1px solid #eef2f7;border-radius:10px;padding:1rem;margin-bottom:1rem">
                            @if ($errors->any())
                                <div class="alert alert-danger" style="font-size:0.84rem">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('diagnosis.store') }}" enctype="multipart/form-data" id="patientDiagnosisInlineForm">
                                @csrf
                                <input type="hidden" name="existing_patient" value="{{ $patient->id }}">
                                <input type="hidden" name="patient_name" value="{{ $patient->name }}">
                                <input type="hidden" name="patient_email" value="{{ $patient->email }}">
                                <div style="font-size:0.84rem;color:#334155;background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:0.6rem 0.75rem;margin-bottom:0.75rem"><i class="fas fa-user me-2" style="color:#64748b"></i>For <strong>{{ e($patient->name) }}</strong> · {{ e($patient->email) }}</div>
                                <label for="patient_diag_text" class="form-label" style="font-weight:600;font-size:0.84rem;color:#1e293b">Diagnosis Text <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="patient_diag_text" name="diagnosis_text" rows="4" placeholder="Enter medical diagnosis, clinical findings, and treatment plan..." required style="border-radius:8px;border:1px solid #e2e8f0;font-size:0.88rem">{{ old('diagnosis_text') }}</textarea>
                                <div class="mt-3">
                                    <label class="form-label" style="font-weight:600;font-size:0.82rem;color:#334155"><i class="fas fa-microphone me-1" style="color:#64748b"></i>Voice Recording <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                                    <div class="p-2" style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px">
                                        <input type="file" class="form-control form-control-sm" name="voice_files[]" multiple accept="audio/*" style="border-radius:8px;font-size:0.82rem">
                                        <small style="font-size:0.72rem;color:#64748b">MP3, WAV, M4A, OGG, WebM — max 10MB each</small>
                                    </div>
                                </div>
                                <div class="row g-2 mt-3">
                                    <div class="col-6 col-md-3"><label class="form-label" style="font-size:0.78rem;font-weight:600;color:#475569">Height (cm)</label><input type="number" class="form-control form-control-sm" name="patient_data[height]" placeholder="170" style="border-radius:8px"></div>
                                    <div class="col-6 col-md-3"><label class="form-label" style="font-size:0.78rem;font-weight:600;color:#475569">Weight (kg)</label><input type="number" step="0.1" class="form-control form-control-sm" name="patient_data[weight]" placeholder="70.5" style="border-radius:8px"></div>
                                    <div class="col-6 col-md-3"><label class="form-label" style="font-size:0.78rem;font-weight:600;color:#475569">Blood Pressure</label><input type="text" class="form-control form-control-sm" name="patient_data[blood_pressure]" placeholder="120/80" style="border-radius:8px"></div>
                                    <div class="col-6 col-md-3"><label class="form-label" style="font-size:0.78rem;font-weight:600;color:#475569">Temp (°C)</label><input type="number" step="0.1" class="form-control form-control-sm" name="patient_data[temperature]" placeholder="36.6" style="border-radius:8px"></div>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn" style="background:#f59e0b;color:#fff;border:1px solid #f59e0b;border-radius:8px;font-weight:600;font-size:0.84rem;padding:0.5rem 1rem"><i class="fas fa-save me-1"></i>Create Diagnosis</button>
                                    <button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;font-weight:500;font-size:0.84rem" onclick="togglePatientDiagnosisForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                        @if($diagnoses->count() > 0)
                            @foreach($diagnoses as $diagnosis)
                                <div class="p-3 mb-2" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div style="font-size:0.78rem;font-weight:600;color:#64748b">{{ $diagnosis->created_at->format('M d, Y') }}</div>
                                            <p class="mb-0 mt-1" style="font-size:0.875rem;color:#334155;line-height:1.5">{{ Str::limit($diagnosis->diagnosis_text, 150) }}</p>
                                        </div>
                                        <a href="{{ route('diagnosis.show', $diagnosis->id) }}" class="btn btn-sm flex-shrink-0" style="background:#ffffff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.35rem 0.6rem"><i class="fas fa-eye"></i></a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-3" style="font-size:0.875rem;color:#94a3b8">No diagnoses yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function togglePatientDiagnosisForm(){
    const el=document.getElementById('patientDiagnosisForm');
    if(!el) return;
    el.style.display = el.style.display==='none' || !el.style.display ? 'block' : 'none';
    if(el.style.display==='block') el.scrollIntoView({behavior:'smooth', block:'nearest'});
}
@if($errors->any())
document.addEventListener('DOMContentLoaded', togglePatientDiagnosisForm);
@endif
</script>
@endsection
