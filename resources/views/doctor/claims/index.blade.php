@extends('master')

@section('title', 'Claims Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0"><i class="fas fa-file-medical me-2"></i>Claims Management</h3>
                            <p class="text-muted mb-0">Manage insurance claims and billing submissions</p>
                        </div>
                        <a href="{{ route('doctor.claims.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Claim
                        </a>
                    </div>
                </div>
            </div>

            <!-- Claims Statistics -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->total() }}</h4>
                                    <small>Total Claims</small>
                                </div>
                                <i class="fas fa-file-medical fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->where('status', 'pending')->count() }}</h4>
                                    <small>Pending</small>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->where('status', 'approved')->count() }}</h4>
                                    <small>Approved</small>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">{{ $claims->where('status', 'denied')->count() }}</h4>
                                    <small>Denied</small>
                                </div>
                                <i class="fas fa-times-circle fa-2x opacity-50"></i>
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
                                        <td>${{ number_format($claim->amount, 2) }}</td>
                                        <td>{{ $claim->insurance_provider }}</td>
                                        <td>
                                            @switch($claim->status)
                                                @case('pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                    @break
                                                @case('submitted')
                                                    <span class="badge bg-info">Submitted</span>
                                                    @break
                                                @case('approved')
                                                    <span class="badge bg-success">Approved</span>
                                                    @break
                                                @case('denied')
                                                    <span class="badge bg-danger">Denied</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($claim->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $claim->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('doctor.claims.show', $claim) }}" class="btn btn-sm btn-outline-primary" title="View Claim">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($claim->status === 'pending')
                                                    <a href="{{ route('doctor.claims.edit', $claim) }}" class="btn btn-sm btn-outline-secondary" title="Edit Claim">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if($claim->status === 'pending')
                                                        <form action="{{ route('doctor.claims.submit-to-clearinghouse', $claim) }}" method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Submit to Clearinghouse">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('doctor.claims.destroy', $claim) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this claim?')">
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
