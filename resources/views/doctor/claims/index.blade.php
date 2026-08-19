@extends('master')

@section('title', 'Claims Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
.claim-stat-card {
    border: none;
    border-left: 5px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    margin-bottom: 1rem;
}
.claim-stat-card .card-body {
    padding: 1.5rem;
}
.claim-stat-card h4 {
    font-weight: 700;
}
.claim-stat-icon {
    opacity: 0.55;
}
.claim-stat-total {
    border-left-color: #cfd8e3;
    background: #f5f7fa;
    color: #4b5565;
}
.claim-stat-total h4,
.claim-stat-total small {
    color: #4b5565;
}
.claim-stat-draft {
    border-left-color: #e9d8a6;
    background: #fbf6e9;
    color: #96701a;
}
.claim-stat-draft h4,
.claim-stat-draft small {
    color: #96701a;
}
.claim-stat-approved {
    border-left-color: #bfe3c1;
    background: #f0faf1;
    color: #2f6b33;
}
.claim-stat-approved h4,
.claim-stat-approved small {
    color: #2f6b33;
}
.claim-stat-denied {
    border-left-color: #f0c4c0;
    background: #fdf2f1;
    color: #a54845;
}
.claim-stat-denied h4,
.claim-stat-denied small {
    color: #a54845;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-file-medical me-2"></i>Claims Management</h2>
                    <p class="text-muted mb-0">Track and manage insurance claims and billing information</p>
                </div>
                <a href="{{ route('doctor.claims.create') }}" class="btn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">
                    <i class="fas fa-plus me-2"></i>Create New Claim
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Claims Statistics -->
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                    <div class="card claim-stat-card claim-stat-total">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->total() }}</h4>
                                    <small>Total Claims</small>
                                </div>
                                <i class="fas fa-file-medical fa-2x claim-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card claim-stat-card claim-stat-draft">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->where('claim_status', 'pending')->count() }}</h4>
                                    <small>Draft</small>
                                </div>
                                <i class="fas fa-clock fa-2x claim-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card claim-stat-card claim-stat-approved">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->where('claim_status', 'approved')->count() }}</h4>
                                    <small>Approved</small>
                                </div>
                                <i class="fas fa-check-circle fa-2x claim-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card claim-stat-card claim-stat-denied">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->where('claim_status', 'denied')->count() }}</h4>
                                    <small>Denied</small>
                                </div>
                                <i class="fas fa-times-circle fa-2x claim-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Claims Table -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Amount</th>
                                    <th>Insurance Provider</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($claims as $claim)
                                    <tr>
                                        <td>#{{ $claim->id }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $claim->patient->name }}</strong><br>
                                                <small class="text-muted">{{ $claim->patient->email }}</small>
                                            </div>
                                        </td>
                                        <td>${{ number_format($claim->expected_amount, 2) }}</td>
                                        <td>{{ $claim->payer }}</td>
                                        <td>
                                            @switch($claim->claim_status)
                                                @case('pending')
                                                    <span class="badge bg-warning">Draft</span>
                                                    @break
                                                @case('submitted')
                                                    <span class="badge bg-info">Ready for Processing</span>
                                                    @break
                                                @case('approved')
                                                    <span class="badge bg-success">Approved</span>
                                                    @break
                                                @case('denied')
                                                    <span class="badge bg-danger">Denied</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($claim->claim_status) }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $claim->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('doctor.claims.show', $claim) }}" class="btn btn-sm btn-outline-primary" title="View Claim">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($claim->claim_status === 'pending')
                                                    <a href="{{ route('doctor.claims.edit', $claim) }}" class="btn btn-sm btn-outline-secondary" title="Edit Claim">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if($claim->claim_status === 'pending')
                                                        <form action="{{ route('doctor.claims.submit-to-clearinghouse', $claim) }}" method="POST" style="display: inline; margin-bottom: 0;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Submit for Processing">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('doctor.claims.destroy', $claim) }}" method="POST" style="display: inline; margin-bottom: 0;" onsubmit="return confirm('Are you sure you want to delete this claim?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Claim">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No claims found</h5>
                                            <p class="text-muted">You haven't created any claims yet.</p>
                                            <a href="{{ route('doctor.claims.create') }}" class="btn btn-primary">
                                                Create Your First Claim
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($claims->hasPages())
                    <div class="card-footer">
                        {{ $claims->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
