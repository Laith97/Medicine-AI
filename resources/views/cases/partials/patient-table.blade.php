@if(count($patients) > 0)
<div class="table-responsive">
    <table class="doctor-table table table-hover align-middle mb-0" id="patients-table-{{ $category }}">
        <thead class="table-light">
            <tr>
                <th>Patient Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Total Visits</th>
                <th>Last Visit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($patients as $key => $group)
                @php
                    $patient = $group['patient'];
                    $showInTab = $category === 'all' || $group['category'] === $category;
                @endphp

                @if($showInTab)
                <tr data-patient-key="{{ $key }}"
                    data-visits="{{ $group['visit_count'] }}"
                    data-last-visit="{{ $group['last_visit']->timestamp }}"
                    data-category="{{ $group['category'] }}"
                    class="patient-row">
                    <td>
                        <div class="d-flex align-items-center">
                            <span>{{ $patient->name ?? 'N/A' }}</span>
                            @if($group['category'] === 'diagnosed')
                                <span class="badge bg-success-subtle text-success ms-2">
                                    <i class="fas fa-check-circle"></i> Diagnosed
                                </span>
                            @elseif($group['category'] === 'pending')
                                <span class="badge bg-warning-subtle text-warning ms-2">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $patient->age ?? 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $patient->gender == 'male' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }}">
                            {{ ucfirst($patient->gender ?? 'N/A') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary">{{ $group['visit_count'] }}</span>
                    </td>
                    <td>{{ $group['last_visit'] ? $group['last_visit']->format('M d, Y') : 'N/A' }}</td>
                    <td>
                        @if($group['category'] === 'diagnosed')
                            <button type="button"
                                    class="btn btn-sm btn-primary btn-patient-summary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#summaryModal"
                                    data-patient-name="{{ $patient->name }}"
                                    data-patient-age="{{ $patient->age }}"
                                    data-patient-gender="{{ $patient->gender }}"
                                    data-patient-key="{{ $key }}">
                                <i class="fas fa-brain me-1"></i>Patient Summary
                            </button>
                        @endif
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
            No patients with completed diagnoses found.
        @else
            No patient records found.
        @endif
    </p>
</div>
@endif
