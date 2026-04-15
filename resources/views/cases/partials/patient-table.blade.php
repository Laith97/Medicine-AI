@if(count($patients) > 0)
<div class="table-responsive mb-4">
    <table class="doctor-table mb-0" id="patients-table-{{ $category }}">
        <thead>
            <tr>
                <th><a href="#" class="sort-link text-white" data-sort="name">Patient Name <i class="fas fa-sort"></i></a></th>
                <th><a href="#" class="sort-link text-white" data-sort="age">Age <i class="fas fa-sort"></i></a></th>
                <th><a href="#" class="sort-link text-white" data-sort="gender">Gender <i class="fas fa-sort"></i></a></th>
                <th><a href="#" class="sort-link text-white" data-sort="visits">Total Visits <i class="fas fa-sort"></i></a></th>
                <th><a href="#" class="sort-link text-white" data-sort="last-visit">Last Visit <i class="fas fa-sort"></i></a></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($patients as $key => $group)
                @php
                    $patient = $group['patient'];
                    $categoryClass = '';
                    $statusBadge = '';
                    $showInTab = true;

                    // Determine category and styling based on patient status
                    if ($category === 'diagnosed') {
                        $showInTab = $group['category'] === 'diagnosed';
                        $categoryClass = 'table-success';
                        $statusBadge = '<span class="doctor-badge doctor-badge-success"><i class="fas fa-check-circle"></i>Diagnosed</span>';
                    } elseif ($category === 'pending') {
                        $showInTab = $group['category'] === 'pending';
                        $categoryClass = 'table-warning';
                        $statusBadge = '<span class="doctor-badge doctor-badge-warning"><i class="fas fa-clock"></i>Pending</span>';
                    } elseif ($category === 'scheduled') {
                        $showInTab = $group['category'] === 'scheduled';
                        $categoryClass = 'table-info';
                        $statusBadge = '<span class="doctor-badge doctor-badge-info"><i class="fas fa-calendar"></i>Scheduled</span>';
                    } else {
                        // All patients tab - show all with appropriate styling
                        if ($group['category'] === 'diagnosed') {
                            $categoryClass = 'table-success';
                            $statusBadge = '<span class="doctor-badge doctor-badge-success"><i class="fas fa-check-circle"></i>Diagnosed</span>';
                        } elseif ($group['category'] === 'pending') {
                            $categoryClass = 'table-warning';
                            $statusBadge = '<span class="doctor-badge doctor-badge-warning"><i class="fas fa-clock"></i>Pending</span>';
                        } elseif ($group['category'] === 'scheduled') {
                            $categoryClass = 'table-info';
                            $statusBadge = '<span class="doctor-badge doctor-badge-info"><i class="fas fa-calendar"></i>Scheduled</span>';
                        }
                    }
                @endphp

                @if($showInTab)
                <tr data-patient-key="{{ $key }}"
                    data-visits="{{ $group['visit_count'] }}"
                    data-last-visit="{{ $group['last_visit']->timestamp }}"
                    data-category="{{ $group['category'] }}"
                    class="patient-row {{ $categoryClass }}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span>{{ $patient->name ?? 'N/A' }}</span>
                            {!! $statusBadge !!}
                        </div>
                    </td>
                    <td>{{ $patient->age ?? 'N/A' }}</td>
                    <td>
                        <span class="doctor-badge {{ $patient->gender == 'male' ? 'doctor-badge-primary' : 'doctor-badge-danger' }}">
                            {{ ucfirst($patient->gender ?? 'N/A') }}
                        </span>
                    </td>
                    <td>
                        <span class="doctor-badge doctor-badge-secondary">{{ $group['visit_count'] }}</span>
                    </td>
                    <td data-date="{{ $group['last_visit']->timestamp }}">{{ $group['last_visit'] ? $group['last_visit']->format('M d, Y') : 'N/A' }}</td>
                    <td>
                        <div class="btn-group">
                            @if($group['category'] === 'diagnosed')
                                <button type="button"
                                        class="doctor-btn doctor-btn-primary doctor-btn-sm btn-patient-summary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#summaryModal"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        data-patient-id="{{ $patient->id }}">
                                    <i class="fas fa-brain"></i><span class="btn-text">Patient Summary</span>
                                </button>
                            @elseif($group['category'] === 'pending')
                                <button type="button" class="doctor-btn doctor-btn-warning doctor-btn-sm btn-expand-visits"
                                        data-patient-key="{{ $key }}"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}">
                                    <i class="fas fa-clock expand-icon"></i>Review Case
                                </button>
                                <button type="button" class="doctor-btn doctor-btn-secondary doctor-btn-sm btn-schedule-appointment"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="Schedule Follow-up">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                            @elseif($group['category'] === 'scheduled')
                                <button type="button" class="doctor-btn doctor-btn-secondary doctor-btn-sm btn-view-appointment"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="View Appointment Details">
                                    <i class="fas fa-calendar-check"></i>View Appointment
                                </button>
                                <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm btn-reschedule"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="Reschedule Appointment">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                <!-- Expandable Visits Section -->
                <tr class="visits-row" data-patient-key="{{ $key }}" style="display: none;">
                    <td colspan="6" class="visits-container">
                        @foreach($group['visits'] as $visit)
                            <div class="visit-item" data-visit-id="{{ $visit->id }}">
                                <div class="visit-header">
                                    <div class="visit-info">
                                        @php
                                            $recordType = $visit->source_model ?? 'Appointment';
                                            $typeLabel = match($recordType) {
                                                'Appointment' => 'Appointment',
                                                'Diagnosis' => 'Diagnosis',
                                                'PatientAnalysis' => 'Analysis',
                                                default => 'Record'
                                            };
                                        @endphp
                                        <span class="visit-number">{{ $typeLabel }} #{{ $loop->iteration }}</span>
                                        <span class="visit-date">{{ $visit->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-expand-visit"
                                            data-visit-id="{{ $visit->id }}"
                                            data-record-id="{{ $visit->id }}"
                                            data-patient-name="{{ $patient->name }}"
                                            data-patient-age="{{ $patient->age }}"
                                            data-patient-gender="{{ $patient->gender }}"
                                            aria-expanded="false"
                                            aria-controls="visit-details-{{ $visit->id }}">
                                        <i class="fas fa-chevron-down me-1 visit-expand-icon"></i>Expand Details
                                    </button>
                                </div>
                                <div class="visit-details" id="visit-details-{{ $visit->id }}" style="display: none;">
                                    <div class="visit-details-content">
                                        <!-- Visit details will be loaded here -->
                                        <div class="text-center py-3">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 mb-0">Loading visit details...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-between align-items-center">
    <div class="showing-entries">
        Showing <span id="showing-count-{{ $category }}">{{ count(array_filter($patients, function($group) use ($category) {
            return $category === 'all' || $group['category'] === $category;
        })) }}</span> patients
    </div>
    <div class="table-pagination">
        <button class="btn btn-sm btn-outline-secondary me-1" id="prev-page-{{ $category }}" disabled>
            <i class="fas fa-chevron-left"></i>
        </button>
        <span id="current-page-{{ $category }}">1</span> / <span id="total-pages-{{ $category }}">1</span>
        <button class="btn btn-sm btn-outline-secondary ms-1" id="next-page-{{ $category }}" disabled>
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>
@else
<div class="doctor-empty-state">
    <i class="fas fa-user-injured"></i>
    <h5>No {{ ucfirst($category) }} Patients Found</h5>
    <p>
        @if($category === 'diagnosed')
            No patients with completed diagnoses found.
        @elseif($category === 'pending')
            No patients awaiting diagnosis found.
        @elseif($category === 'scheduled')
            No scheduled appointments found.
        @else
            No patient records found.
        @endif
    </p>
    @if($category === 'all')
        <a href="{{ route('openai.form') }}" class="doctor-btn doctor-btn-primary">
            <i class="fas fa-plus"></i>Add New Patient
        </a>
    @endif
</div>
@endif