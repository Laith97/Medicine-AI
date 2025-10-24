@if(count($patients) > 0)
<div class="table-responsive mb-4">
    <table class="table table-custom mb-0" id="patients-table-{{ $category }}">
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
                        $statusBadge = '<span class="badge badge-diagnosed"><i class="fas fa-check-circle me-1"></i>Diagnosed</span>';
                    } elseif ($category === 'pending') {
                        $showInTab = $group['category'] === 'pending';
                        $categoryClass = 'table-warning';
                        $statusBadge = '<span class="badge badge-pending"><i class="fas fa-clock me-1"></i>Pending</span>';
                    } elseif ($category === 'scheduled') {
                        $showInTab = $group['category'] === 'scheduled';
                        $categoryClass = 'table-info';
                        $statusBadge = '<span class="badge badge-scheduled"><i class="fas fa-calendar me-1"></i>Scheduled</span>';
                    } else {
                        // All patients tab - show all with appropriate styling
                        if ($group['category'] === 'diagnosed') {
                            $categoryClass = 'table-success';
                            $statusBadge = '<span class="badge badge-diagnosed"><i class="fas fa-check-circle me-1"></i>Diagnosed</span>';
                        } elseif ($group['category'] === 'pending') {
                            $categoryClass = 'table-warning';
                            $statusBadge = '<span class="badge badge-pending"><i class="fas fa-clock me-1"></i>Pending</span>';
                        } elseif ($group['category'] === 'scheduled') {
                            $categoryClass = 'table-info';
                            $statusBadge = '<span class="badge badge-scheduled"><i class="fas fa-calendar me-1"></i>Scheduled</span>';
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
                        <div class="d-flex flex-column">
                            <span>{{ $patient->name ?? 'N/A' }}</span>
                            <div class="mt-1">{!! $statusBadge !!}</div>
                        </div>
                    </td>
                    <td>{{ $patient->age ?? 'N/A' }}</td>
                    <td>
                        <span class="badge" style="background-color: {{ $patient->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white; border-radius: 15px; padding: 0.4rem 0.8rem;">
                            {{ ucfirst($patient->gender ?? 'N/A') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-primary">{{ $group['visit_count'] }}</span>
                    </td>
                    <td data-date="{{ $group['last_visit']->timestamp }}">{{ $group['last_visit'] ? $group['last_visit']->format('M d, Y') : 'N/A' }}</td>
                    <td>
                        <div class="btn-group">
                            @if($group['category'] === 'diagnosed')
                                <button type="button" class="btn btn-sm btn-expand-visits btn-custom-primary"
                                        data-patient-key="{{ $key }}"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}">
                                    <i class="fas fa-chevron-down me-1 expand-icon"></i>View Details
                                </button>
                                <button type="button" class="btn btn-sm btn-show-summary btn-custom-secondary"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="View Patient Summary">
                                    <i class="fas fa-file-medical"></i>
                                </button>
                            @elseif($group['category'] === 'pending')
                                <button type="button" class="btn btn-sm btn-expand-visits btn-outline-warning"
                                        data-patient-key="{{ $key }}"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}">
                                    <i class="fas fa-clock me-1 expand-icon"></i>Review Case
                                </button>
                                <button type="button" class="btn btn-sm btn-schedule-appointment btn-outline-info"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="Schedule Follow-up">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                            @elseif($group['category'] === 'scheduled')
                                <button type="button" class="btn btn-sm btn-view-appointment btn-outline-primary"
                                        data-patient-name="{{ $patient->name }}"
                                        data-patient-age="{{ $patient->age }}"
                                        data-patient-gender="{{ $patient->gender }}"
                                        data-patient-key="{{ $key }}"
                                        title="View Appointment Details">
                                    <i class="fas fa-calendar-check me-1"></i>View Appointment
                                </button>
                                <button type="button" class="btn btn-sm btn-reschedule btn-outline-secondary"
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
                        <div class="visits-section">
                            <div class="visits-header">
                                <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Patient Visits</h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-collapse-visits" data-patient-key="{{ $key }}">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                            </div>
                            <div class="visits-content">
                                @foreach($group['visits'] as $visit)
                                    <div class="visit-item" data-visit-id="{{ $visit->id }}">
                                        <div class="visit-header">
                                            <div class="visit-info">
                                                <span class="visit-number">Visit #{{ $loop->iteration }}</span>
                                                <span class="visit-date">{{ $visit->created_at->format('M d, Y H:i') }}</span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-expand-visit"
                                                    data-visit-id="{{ $visit->id }}"
                                                    data-record-id="{{ $visit->id }}"
                                                    data-patient-name="{{ $patient->name }}"
                                                    data-patient-age="{{ $patient->age }}"
                                                    data-patient-gender="{{ $patient->gender }}">
                                                <i class="fas fa-chevron-right me-1 visit-expand-icon"></i>Details
                                            </button>
                                        </div>
                                        <div class="visit-details" style="display: none;">
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
                            </div>
                        </div>
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
<div class="empty-state">
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
        <a href="{{ route('openai.form') }}" class="btn-custom-primary">
            <i class="fas fa-plus me-2"></i>Add New Patient
        </a>
    @endif
</div>
@endif