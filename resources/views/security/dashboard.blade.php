@extends('layouts.admin')

@section('title', 'Security Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded z-50">
        Skip to main content
    </a>

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <nav aria-label="Breadcrumb navigation">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Security Dashboard</li>
                        </ol>
                    </nav>
                </div>
                <h1 class="page-title" id="main-content">Security Dashboard</h1>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="header-title">Filter Options</h2>
                    <form id="filter-form" method="GET" action="{{ route('security.dashboard') }}" role="search" aria-labelledby="filter-form-title">
                        <div class="sr-only" id="filter-form-title">Security Dashboard Filters</div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="time_range" class="form-label">Time Range</label>
                                    <select name="time_range" id="time_range" class="form-select" aria-describedby="time-range-help">
                                        <option value="1_hour" {{ request('time_range') == '1_hour' ? 'selected' : '' }}>Last 1 Hour</option>
                                        <option value="24_hours" {{ request('time_range', '24_hours') == '24_hours' ? 'selected' : '' }}>Last 24 Hours</option>
                                        <option value="7_days" {{ request('time_range') == '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                        <option value="30_days" {{ request('time_range') == '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                                    </select>
                                    <div id="time-range-help" class="form-text">Select the time period for security reports</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="action_type" class="form-label">Action Type</label>
                                    <select name="action_type" id="action_type" class="form-select" aria-describedby="action-type-help">
                                        <option value="all">All Actions</option>
                                        @foreach($actionTypes as $type)
                                            <option value="{{ $type }}" {{ request('action_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    <div id="action-type-help" class="form-text">Filter by specific security action types</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">User ID</label>
                                    <input type="text" name="user_id" id="user_id" class="form-control" value="{{ request('user_id') }}" placeholder="Enter User ID" aria-describedby="user-id-help">
                                    <div id="user-id-help" class="form-text">Enter a specific user ID to filter results</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label sr-only">Actions</label>
                                    <div role="group" aria-label="Filter actions">
                                        <button type="submit" id="filter-btn" class="btn btn-primary me-2" aria-describedby="filter-help" disabled>
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                            <i class="mdi mdi-filter" aria-hidden="true"></i>
                                            <span class="filter-text">Filter</span>
                                        </button>
                                        <button type="button" id="reset-btn" class="btn btn-secondary me-2" aria-label="Reset all filters" disabled>
                                            <i class="mdi mdi-refresh" aria-hidden="true"></i>
                                            Reset
                                        </button>
                                        <a href="{{ route('security.export', request()->query()) }}" class="btn btn-success" aria-label="Export filtered security data">
                                            <i class="mdi mdi-download" aria-hidden="true"></i>
                                            Export
                                        </a>
                                    </div>
                                    <div id="filter-help" class="form-text">Apply filters or export the current data</div>
                                    <div id="filter-status" class="sr-only" role="status" aria-live="polite"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Reports Section -->
    <div class="row">
        <div class="col-12">
            <h2 class="mb-3">Security Reports</h2>
        </div>

        <!-- Unauthorized Access Reports -->
        <div class="col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h3 class="text-muted fw-normal mt-0 text-truncate" title="Unauthorized Access">Unauthorized Access</h3>
                            <div class="my-2 py-1" role="status" aria-label="Unauthorized access count">
                                <span class="visually-hidden">Count: </span>
                                <span class="h3">{{ $unauthorizedAccessReports->count() }}</span>
                            </div>
                            <p class="mb-0 text-muted">
                                <span class="text-danger me-2" role="status" aria-label="High severity unauthorized access">
                                    <i class="mdi mdi-alert" aria-hidden="true"></i>
                                    {{ $unauthorizedAccessReports->where('severity', 'high')->count() }} High
                                </span>
                            </p>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <div id="unauthorized-access-chart" role="img" aria-label="Unauthorized access trend chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frequent Impersonation Reports -->
        <div class="col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Frequent Impersonation">Frequent Impersonation</h5>
                            <h3 class="my-2 py-1">{{ $frequentImpersonationReports->count() }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-warning me-2"><i class="mdi mdi-account-switch"></i> {{ $frequentImpersonationReports->where('severity', 'medium')->count() }} Medium</span>
                            </p>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <div id="impersonation-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unusual Assignments Reports -->
        <div class="col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Unusual Assignments">Unusual Assignments</h5>
                            <h3 class="my-2 py-1">{{ $unusualAssignmentReports->count() }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-danger me-2"><i class="mdi mdi-account-plus"></i> {{ $unusualAssignmentReports->where('severity', 'high')->count() }} High</span>
                            </p>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <div id="assignments-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Logs Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="header-title">Recent Audit Logs</h2>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap table-hover mb-0" role="table" aria-label="Recent audit logs" aria-describedby="audit-logs-description">
                            <caption id="audit-logs-description" class="sr-only">Table showing recent security audit logs with details about actions, users, and timestamps</caption>
                            <thead>
                                <tr>
                                    <th scope="col" aria-sort="none">ID</th>
                                    <th scope="col" aria-sort="none">Action</th>
                                    <th scope="col" aria-sort="none">User</th>
                                    <th scope="col" aria-sort="none">Doctor</th>
                                    <th scope="col" aria-sort="none">Patient</th>
                                    <th scope="col" aria-sort="none">Timestamp</th>
                                    <th scope="col" aria-sort="none">IP Address</th>
                                    <th scope="col" aria-sort="none">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->getActionBadgeClass() }}" role="status" aria-label="Action type: {{ $log->action }}">{{ $log->action }}</span>
                                        </td>
                                        <td>
                                            @if($log->user)
                                                <span title="{{ $log->user->email }}">{{ $log->user->name }}</span>
                                                <span class="sr-only">({{ $log->user->email }})</span>
                                            @else
                                                <span aria-label="No user associated">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->doctor)
                                                {{ $log->doctor->name }}
                                            @else
                                                <span aria-label="No doctor associated">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->patient)
                                                {{ $log->patient->name }}
                                            @else
                                                <span aria-label="No patient associated">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <time datetime="{{ $log->created_at->toISOString() }}" title="{{ $log->created_at->format('l, F j, Y \a\t g:i A') }}">
                                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                            </time>
                                        </td>
                                        <td>{{ $log->ip_address }}</td>
                                        <td>
                                            <a href="{{ route('security.audit-logs.show', $log) }}" class="btn btn-sm btn-info" aria-label="View details for audit log {{ $log->id }}">
                                                <i class="mdi mdi-eye" aria-hidden="true"></i>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center" role="status" aria-live="polite">
                                            No audit logs found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-3" aria-label="Audit logs pagination">
                        {{ $auditLogs->links() }}
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filter-form');
    const filterBtn = document.getElementById('filter-btn');
    const resetBtn = document.getElementById('reset-btn');
    const filterStatus = document.getElementById('filter-status');
    const spinner = filterBtn.querySelector('.spinner-border');
    const filterText = filterBtn.querySelector('.filter-text');

    // Enable buttons initially
    filterBtn.disabled = false;
    resetBtn.disabled = false;

    // AJAX form submission
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading state
        filterBtn.disabled = true;
        resetBtn.disabled = true;
        spinner.classList.remove('d-none');
        filterText.textContent = 'Filtering...';
        filterStatus.textContent = 'Applying filters, please wait...';

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);

        // Update URL without page reload
        const newUrl = `${filterForm.action}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);

        // Fetch filtered data
        fetch(`${filterForm.action}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update the dashboard content with filtered data
            updateDashboardContent(data);
            filterStatus.textContent = 'Filters applied successfully.';
        })
        .catch(error => {
            console.error('Filter error:', error);
            filterStatus.textContent = 'Error applying filters. Please try again.';
            // Fallback to page reload
            window.location.href = newUrl;
        })
        .finally(() => {
            // Reset loading state
            filterBtn.disabled = false;
            resetBtn.disabled = false;
            spinner.classList.add('d-none');
            filterText.textContent = 'Filter';
        });
    });

    // Reset functionality
    resetBtn.addEventListener('click', function() {
        filterForm.reset();
        window.location.href = '{{ route("security.dashboard") }}';
    });

    function updateDashboardContent(data) {
        // Update security reports counts
        if (data.unauthorizedAccessReports) {
            const unauthorizedCard = document.querySelector('.col-md-6.col-xl-4');
            if (unauthorizedCard) {
                const countElement = unauthorizedCard.querySelector('.my-2.py-1 span:last-child');
                if (countElement) {
                    countElement.textContent = data.unauthorizedAccessReports.count;
                }
                const highCountElement = unauthorizedCard.querySelector('.text-danger');
                if (highCountElement) {
                    const highCount = highCountElement.querySelector('span') || highCountElement;
                    highCount.textContent = highCount.textContent.replace(/\d+/, data.unauthorizedAccessReports.high_count);
                }
            }
        }

        // Update audit logs table
        if (data.auditLogsHtml) {
            const tableBody = document.querySelector('.table tbody');
            if (tableBody) {
                tableBody.innerHTML = data.auditLogsHtml;
            }
        }

        // Update pagination
        if (data.paginationHtml) {
            const paginationContainer = document.querySelector('.mt-3');
            if (paginationContainer) {
                paginationContainer.innerHTML = data.paginationHtml;
            }
        }
    }

    // Keyboard navigation for table
    const table = document.querySelector('.table');
    if (table) {
        table.addEventListener('keydown', function(e) {
            const currentCell = e.target.closest('td, th');
            if (!currentCell) return;

            const currentRow = currentCell.closest('tr');
            const currentIndex = Array.from(currentRow.children).indexOf(currentCell);

            let nextCell = null;

            switch (e.key) {
                case 'ArrowRight':
                    e.preventDefault();
                    nextCell = currentRow.children[currentIndex + 1];
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    nextCell = currentRow.children[currentIndex - 1];
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    const nextRow = currentRow.nextElementSibling;
                    if (nextRow) {
                        nextCell = nextRow.children[currentIndex];
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    const prevRow = currentRow.previousElementSibling;
                    if (prevRow) {
                        nextCell = prevRow.children[currentIndex];
                    }
                    break;
            }

            if (nextCell) {
                nextCell.focus();
            }
        });
    }
});
</script>
@endsection
