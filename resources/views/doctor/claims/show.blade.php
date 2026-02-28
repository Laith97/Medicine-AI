@extends('master')

@section('title', 'Claim Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Claim Details</h1>
                <div>
                    <a href="{{ route('doctor.claims.index') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Claims
                    </a>
                    @if($claim->claim_status !== 'submitted')
                        <a href="{{ route('doctor.claims.edit', $claim) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Claim Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Claim Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Claim ID</label>
                                        <p class="mb-0">#{{ $claim->id }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Patient</label>
                                        <p class="mb-0">
                                            <strong>{{ $claim->patient->name }}</strong><br>
                                            <small class="text-muted">{{ $claim->patient->email }}</small>
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Insurance Provider</label>
                                        <p class="mb-0">{{ $claim->payer }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <p class="mb-0">
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
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Expected Amount</label>
                                        <p class="mb-0">${{ number_format($claim->expected_amount, 2) }}</p>
                                    </div>
                                    @if($claim->claim_status == 'denied' && $claim->denial_reason)
                                        <div class="mb-3">
                                            <label class="form-label text-danger">Denial Reason</label>
                                            <p class="mb-0 text-danger"><strong>{{ $claim->denial_reason }}</strong></p>
                                        </div>
                                    @endif
                                    @if($claim->paid_amount)
                                        <div class="mb-3">
                                            <label class="form-label">Paid Amount</label>
                                            <p class="mb-0">${{ number_format($claim->paid_amount, 2) }}</p>
                                        </div>
                                    @endif
                                    <div class="mb-3">
                                        <label class="form-label">Service Date</label>
                                        <p class="mb-0">
                                            {{ $claim->service_date ? $claim->service_date->format('M d, Y') : 'Not specified' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Diagnosis and Procedure -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Diagnosis & Procedure</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label">Diagnosis</label>
                                <p class="mb-0">{{ $claim->diagnosis_text }}</p>
                            </div>
                            <div>
                                <label class="form-label">Procedure</label>
                                <p class="mb-0">{{ $claim->procedure_text }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Codes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Medical Codes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ICD-10 Codes</label>
                                        @if($claim->icd10_codes && count($claim->icd10_codes) > 0)
                                            <p class="mb-0">
                                                @foreach($claim->icd10_codes as $code)
                                                    <span class="badge bg-secondary me-1">{{ $code }}</span>
                                                @endforeach
                                            </p>
                                        @else
                                            <p class="mb-0 text-muted">No ICD-10 codes specified</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">CPT Codes</label>
                                        @if($claim->cpt_codes && count($claim->cpt_codes) > 0)
                                            <p class="mb-0">
                                                @foreach($claim->cpt_codes as $code)
                                                    <span class="badge bg-secondary me-1">{{ $code }}</span>
                                                @endforeach
                                            </p>
                                        @else
                                            <p class="mb-0 text-muted">No CPT codes specified</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Claim Status Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Processing Status</h5>
                        </div>
                        <div class="card-body text-center">
                            @switch($claim->claim_status)
                                @case('pending')
                                    <div class="mb-3">
                                        <i class="fas fa-file-medical fa-3x text-warning"></i>
                                    </div>
                                    <h5 class="text-warning">Draft</h5>
                                    <p class="mb-0">This claim is in draft state and hasn't been submitted</p>
                                    @break
                                @case('submitted')
                                    <div class="mb-3">
                                        <i class="fas fa-paper-plane fa-3x text-info"></i>
                                    </div>
                                    <h5 class="text-info">Ready for Processing</h5>
                                    <p class="mb-0">This claim has been marked for processing</p>
                                    @break
                                @case('approved')
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle fa-3x text-success"></i>
                                    </div>
                                    <h5 class="text-success">Approved</h5>
                                    <p class="mb-0">This claim has been approved by the insurance company</p>
                                    @break
                                @case('denied')
                                    <div class="mb-3">
                                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                                    </div>
                                    <h5 class="text-danger">Denied</h5>
                                    <p class="mb-0">This claim has been denied by the insurance company</p>
                                    @break
                                @default
                                    <div class="mb-3">
                                        <i class="fas fa-question-circle fa-3x text-secondary"></i>
                                    </div>
                                    <h5 class="text-secondary">{{ ucfirst($claim->claim_status) }}</h5>
                                    <p class="mb-0">This claim is in an unknown state</p>
                            @endswitch
                        </div>
                    </div>

                    <!-- Claim Actions -->
                    @if($claim->claim_status === 'pending')
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2 mb-3">
                                    <a href="{{ route('doctor.claims.edit', $claim) }}" class="btn btn-primary">
                                        <i class="fas fa-edit me-2"></i>Edit Claim
                                    </a>
                                </div>
                                <form action="{{ route('doctor.claims.submit-to-clearinghouse', $claim) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Mark Ready for Processing
                                    </button>
                                </form>
                                <button type="button" class="btn btn-outline-danger w-100 mt-2"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-2"></i>Delete Claim
                                </button>
                            </div>
                        </div>
                    @elseif($claim->claim_status === 'submitted')
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2 mb-3">
                                    <form action="{{ route('doctor.claims.approve', $claim) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-check me-2"></i>Mark as Approved
                                        </button>
                                    </form>
                                </div>
                                <button type="button" class="btn btn-outline-warning w-100"
                                        data-bs-toggle="modal" data-bs-target="#denyModal">
                                    <i class="fas fa-times me-2"></i>Mark as Denied
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-0">Claim status is final and cannot be changed.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Claim Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6>Created</h6>
                                        <p class="text-muted mb-0">{{ $claim->created_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                </div>
                                @if($claim->updated_at->gt($claim->created_at))
                                    <div class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h6>Last Updated</h6>
                                            <p class="text-muted mb-0">{{ $claim->updated_at->format('M d, Y g:i A') }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if($claim->claim_status === 'submitted')
                                    <div class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h6>Submitted</h6>
                                            <p class="text-muted mb-0">{{ $claim->updated_at->format('M d, Y g:i A') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deny Claim Modal -->
<div class="modal fade" id="denyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deny Claim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('doctor.claims.deny', $claim) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to mark this claim as denied?</p>
                    <div class="mb-3">
                        <label for="denial_reason_modal" class="form-label">Denial Reason (Optional)</label>
                        <textarea class="form-control" id="denial_reason_modal" name="denial_reason" rows="3" placeholder="Enter reason for denial..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Mark as Denied</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this claim?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('doctor.claims.destroy', $claim) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Claim</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add any specific JavaScript for claim show page if needed
});
</script>
@endpush