@if(count($patients) > 0)
<div class="table-responsive">
    <table class="doctor-table mb-0" id="patients-table-{{ $category }}">
        <thead>
            <tr>
                <th class="text-nowrap"><i class="fas fa-user me-1 opacity-75"></i> Patient</th>
                <th class="text-center" style="width:80px">Age</th>
                <th class="text-center" style="width:110px">Gender</th>
                <th class="text-center" style="width:110px">Visits</th>
                <th class="text-nowrap"><i class="far fa-calendar me-1 opacity-75"></i> Last Visit</th>
                <th class="text-end" style="width:200px">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($patients as $key => $group)
                @php
                    $patient = $group['patient'];
                    $showInTab = $category === 'all' || $group['category'] === $category;
                    $initial = strtoupper(mb_substr($patient->name ?? 'N', 0, 1));
                    $gender = strtolower($patient->gender ?? '');
                @endphp

                @if($showInTab)
                <tr data-patient-key="{{ $key }}"
                    data-visits="{{ $group['visit_count'] }}"
                    data-last-visit="{{ $group['last_visit']->timestamp }}"
                    data-category="{{ $group['category'] }}"
                    data-patient-name="{{ strtolower($patient->name ?? '') }}"
                    class="patient-row">
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="patient-avatar flex-shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <div class="fw-semibold text-dark text-truncate" style="max-width:200px;" title="{{ $patient->name ?? 'N/A' }}">{{ $patient->name ?? 'N/A' }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    @if($group['category'] === 'diagnosed')
                                        <span class="doctor-badge doctor-badge-success">
                                            <i class="fas fa-check-circle"></i> Diagnosed
                                        </span>
                                    @elseif($group['category'] === 'pending_diagnosis' || $group['category'] === 'pending')
                                        <span class="doctor-badge doctor-badge-warning">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @else
                                        <span class="doctor-badge doctor-badge-secondary">
                                            <i class="fas fa-folder"></i> {{ ucfirst($group['category'] ?? 'Record') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="fw-medium">{{ $patient->age ?? '—' }}</span>
                        @if(isset($patient->age) && is_numeric($patient->age))
                            <small class="text-muted d-block" style="font-size:0.7rem;">yrs</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($gender === 'male')
                            <span class="doctor-badge doctor-badge-primary"><i class="fas fa-mars me-1"></i>Male</span>
                        @elseif($gender === 'female')
                            <span class="doctor-badge" style="background: rgba(232,62,140,0.1); color:#c2185b; border:1px solid rgba(232,62,140,0.2);"><i class="fas fa-venus me-1"></i>Female</span>
                        @else
                            <span class="doctor-badge doctor-badge-secondary">{{ ucfirst($patient->gender ?? '—') }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="visit-count-badge" title="{{ $group['visit_count'] }} total visits">
                            <i class="fas fa-clipboard-list me-1 opacity-75"></i>{{ $group['visit_count'] }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium text-dark">{{ $group['last_visit'] ? $group['last_visit']->format('M d, Y') : '—' }}</span>
                            @if($group['last_visit'])
                                <small class="text-muted">{{ $group['last_visit']->diffForHumans() }}</small>
                            @endif
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2 align-items-center justify-content-end flex-wrap">
                            @if($group['category'] === 'diagnosed')
                                <button type="button"
                                        class="doctor-btn doctor-btn-primary doctor-btn-sm btn-patient-summary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#summaryModal"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="View AI medical summary">
                                    <i class="fas fa-brain"></i> Summary
                                </button>
                            @else
                                <span class="text-muted small fst-italic">No summary</span>
                            @endif
                            @if(!empty($patient->patient_id) || !empty($group['patient']->patient_id))
                                <a href="{{ route('doctor.patients.show', $patient->patient_id ?? $group['patient']->patient_id) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Open patient profile">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="doctor-empty-state">
    <i class="fas fa-user-injured"></i>
    <h5>No {{ ucfirst($category) }} Patients Found</h5>
    <p>
        @if($category === 'diagnosed')
            No patients with completed diagnoses found. Diagnosed patients will appear here after you complete a consultation.
        @else
            No patient records found. Start a consultation to create your first record.
        @endif
    </p>
    <a href="{{ route('ai.ambient-listening.index') }}" class="doctor-btn doctor-btn-primary mt-2">
        <i class="fas fa-microphone"></i> Start Consultation
    </a>
</div>
@endif
