@extends('layouts.admin')

@section('title', 'Security Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Security Dashboard</li>
                    </ol>
                </div>
                <h4 class="page-title">Security Dashboard</h4>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Filter Options</h4>
                    <form method="GET" action="{{ route('security.dashboard') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="time_range" class="form-label">Time Range</label>
                                    <select name="time_range" id="time_range" class="form-select">
                                        <option value="1_hour" {{ request('time_range') == '1_hour' ? 'selected' : '' }}>Last 1 Hour</option>
                                        <option value="24_hours" {{ request('time_range', '24_hours') == '24_hours' ? 'selected' : '' }}>Last 24 Hours</option>
                                        <option value="7_days" {{ request('time_range') == '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                        <option value="30_days" {{ request('time_range') == '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="action_type" class="form-label">Action Type</label>
                                    <select name="action_type" id="action_type" class="form-select">
                                        <option value="all">All Actions</option>
                                        @foreach($actionTypes as $type)
                                            <option value="{{ $type }}" {{ request('action_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">User ID</label>
                                    <input type="text" name="user_id" id="user_id" class="form-control" value="{{ request('user_id') }}" placeholder="Enter User ID">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('security.dashboard') }}" class="btn btn-secondary">Reset</a>
                                        <a href="{{ route('security.export', request()->query()) }}" class="btn btn-success">Export</a>
                                    </div>
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
            <h4 class="mb-3">Security Reports</h4>
        </div>

        <!-- Unauthorized Access Reports -->
        <div class="col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Unauthorized Access">Unauthorized Access</h5>
                            <h3 class="my-2 py-1">{{ $unauthorizedAccessReports->count() }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-danger me-2"><i class="mdi mdi-alert"></i> {{ $unauthorizedAccessReports->where('severity', 'high')->count() }} High</span>
                            </p>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <div id="unauthorized-access-chart"></div>
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
                    <h4 class="header-title">Recent Audit Logs</h4>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Doctor</th>
                                    <th>Patient</th>
                                    <th>Timestamp</th>
                                    <th>IP Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->getActionBadgeClass() }}">{{ $log->action }}</span>
                                        </td>
                                        <td>
                                            @if($log->user)
                                                {{ $log->user->name }} ({{ $log->user->email }})
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->doctor)
                                                {{ $log->doctor->name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->patient)
                                                {{ $log->patient->name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $log->ip_address }}</td>
                                        <td>
                                            <a href="{{ route('security.audit-logs.show', $log) }}" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No audit logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $auditLogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Initialize charts if needed
    document.addEventListener('DOMContentLoaded', function() {
        // You can add chart initialization code here if using a charting library
    });
</script>
@endsection
