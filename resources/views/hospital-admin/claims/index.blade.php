@extends('layouts.app')

@section('page-title', 'Claims Dashboard')

@push('styles')
<style>
    .risk-low { color: #28a745; }
    .risk-medium { color: #ffc107; }
    .risk-high { color: #dc3545; }
    .underpayment-alert { color: #dc3545; font-weight: bold; }
    .ai-codes { background-color: #f8f9fa; padding: 5px; border-radius: 3px; font-size: 0.9em; }
    .confidence-score { font-size: 0.8em; color: #6c757d; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Claims Dashboard</h1>
                    <p class="text-muted">Monitor and manage medical claims with AI-powered insights</p>
                </div>
                <div>
                    <a href="{{ route('hospital-admin.claims.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>New Claim
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-file-medical text-primary fa-2x me-2"></i>
                                <h3 class="text-primary mb-0">{{ $totalClaims ?? 0 }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Total Claims</p>
                            <small class="text-muted">All time</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-check-circle text-success fa-2x me-2"></i>
                                <h3 class="text-success mb-0">{{ $approvedClaims ?? 0 }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Approved</p>
                            <small class="text-muted">Paid claims</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-clock text-warning fa-2x me-2"></i>
                                <h3 class="text-warning mb-0">{{ $pendingClaims ?? 0 }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Pending</p>
                            <small class="text-muted">Under review</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="fas fa-times-circle text-danger fa-2x me-2"></i>
                                <h3 class="text-danger mb-0">{{ $deniedClaims ?? 0 }}</h3>
                            </div>
                            <p class="mb-0 fw-bold">Denied</p>
                            <small class="text-muted">Rejected claims</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('hospital-admin.claims.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="denied" {{ request('status') === 'denied' ? 'selected' : '' }}>Denied</option>
                                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="risk_filter" class="form-label">Denial Risk</label>
                                <select name="risk_filter" id="risk_filter" class="form-select">
                                    <option value="">All Risks</option>
                                    <option value="low" {{ request('risk_filter') === 'low' ? 'selected' : '' }}>Low Risk</option>
                                    <option value="medium" {{ request('risk_filter') === 'medium' ? 'selected' : '' }}>Medium Risk</option>
                                    <option value="high" {{ request('risk_filter') === 'high' ? 'selected' : '' }}>High Risk</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Apply Filters
                                </button>
                                <a href="{{ route('hospital-admin.claims.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Claims Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Claims List</h5>
                </div>
                <div class="card-body">
                    @if(isset($claims) && $claims->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Claim ID</th>
                                        <th>Patient</th>
                                        <th>Provider</th>
                                        <th>Amount</th>
                                        <th>AI Suggested Codes</th>
                                        <th>Denial Risk</th>
                                        <th>Underpayment Alert</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($claims as $claim)
                                        <tr>
                                            <td>
                                                <strong>{{ $claim->claim_number ?? $claim->id }}</strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $claim->patient_name ?? 'N/A' }}</strong>
                                                    @if($claim->patient_dob)
                                                        <br><small class="text-muted">{{ $claim->patient_dob->format('M d, Y') }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $claim->provider_name ?? 'N/A' }}</strong>
                                                    @if($claim->provider_npi)
                                                        <br><small class="text-muted">NPI: {{ $claim->provider_npi }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <strong>${{ number_format($claim->total_amount ?? 0, 2) }}</strong>
                                            </td>
                                            <td>
                                                <div class="ai-codes">
                                                    @if(isset($claim->ai_suggested_codes) && is_array($claim->ai_suggested_codes))
                                                        @foreach($claim->ai_suggested_codes as $code)
                                                            <div>
                                                                <strong>{{ $code['type'] }}: {{ $code['code'] }}</strong>
                                                                <span class="confidence-score">({{ number_format($code['confidence'] * 100, 1) }}%)</span>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <small class="text-muted">No suggestions</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $riskLevel = 'low';
                                                    $riskColor = 'success';
                                                    $riskProbability = $claim->denial_risk_probability ?? 0;

                                                    if ($riskProbability > 0.7) {
                                                        $riskLevel = 'high';
                                                        $riskColor = 'danger';
                                                    } elseif ($riskProbability > 0.4) {
                                                        $riskLevel = 'medium';
                                                        $riskColor = 'warning';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $riskColor }}" data-bs-toggle="tooltip" data-bs-placement="top"
                                                      title="Denial Risk: {{ number_format($riskProbability * 100, 1) }}%">
                                                    {{ ucfirst($riskLevel) }} Risk
                                                </span>
                                                @if($riskProbability > 0.5)
                                                    <br><small class="text-muted">High denial probability</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($claim->underpayment_alert ?? false)
                                                    <i class="fas fa-exclamation-triangle underpayment-alert" data-bs-toggle="tooltip"
                                                       data-bs-placement="top" title="Potential underpayment detected"></i>
                                                    <small class="underpayment-alert">Alert</small>
                                                @else
                                                    <small class="text-muted">No alert</small>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $statusColor = match($claim->status ?? 'pending') {
                                                        'approved' => 'success',
                                                        'denied' => 'danger',
                                                        'pending' => 'warning',
                                                        'paid' => 'info',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusColor }}">
                                                    {{ ucfirst($claim->status ?? 'pending') }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $claim->submitted_at ? $claim->submitted_at->format('M d, Y') : 'Not submitted' }}
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <a href="{{ route('hospital-admin.claims.show', $claim) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                    <a href="{{ route('hospital-admin.claims.edit', $claim) }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($claims->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $claims->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Claims Found</h5>
                            <p class="text-muted">No claims match your current filters.</p>
                            <a href="{{ route('hospital-admin.claims.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Create First Claim
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-refresh data every 5 minutes
    setTimeout(function() {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 300000);

    // Handle visibility change to refresh when tab becomes active
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Optional: refresh data when tab becomes active
        }
    });
</script>
@endpush
