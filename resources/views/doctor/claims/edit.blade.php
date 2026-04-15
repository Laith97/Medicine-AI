@extends('master')

@section('title', 'Edit Claim')

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
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-edit me-2"></i>Edit Insurance Claim</h2>
                        <p class="text-muted mb-0">Update the claim details below</p>
                    </div>
                    <a href="{{ route('doctor.claims.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Claims
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

@if($errors->any())
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    </div>
@endif

<form action="{{ route('doctor.claims.update', $claim) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Claim Information</h5>
                            </div>
                            <div class="card-body">
                                <!-- Patient Selection (read-only) -->
                                <div class="mb-3">
                                    <label class="form-label">Patient</label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $claim->patient->name }} ({{ $claim->patient->email }})"
                                           readonly>
                                </div>

                                <!-- Diagnosis -->
                                <div class="mb-3">
                                    <label for="diagnosis_text" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('diagnosis_text') is-invalid @enderror"
                                              id="diagnosis_text"
                                              name="diagnosis_text"
                                              rows="3"
                                              required
                                              placeholder="Enter the diagnosis description...">{{ old('diagnosis_text', $claim->diagnosis_text) }}</textarea>
                                    @error('diagnosis_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Procedure -->
                                <div class="mb-3">
                                    <label for="procedure_text" class="form-label">Procedure <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('procedure_text') is-invalid @enderror"
                                              id="procedure_text"
                                              name="procedure_text"
                                              rows="3"
                                              required
                                              placeholder="Enter the procedure description...">{{ old('procedure_text', $claim->procedure_text) }}</textarea>
                                    @error('procedure_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Insurance Provider -->
                                <div class="mb-3">
                                    <label for="payer" class="form-label">Insurance Provider <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('payer') is-invalid @enderror"
                                           id="payer"
                                           name="payer"
                                           value="{{ old('payer', $claim->payer) }}"
                                           required
                                           placeholder="Enter insurance company name">
                                    @error('payer')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Expected Amount -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="expected_amount" class="form-label">Expected Amount ($) <span class="text-danger">*</span></label>
                                            <input type="number"
                                                   class="form-control @error('expected_amount') is-invalid @enderror"
                                                   id="expected_amount"
                                                   name="expected_amount"
                                                   value="{{ old('expected_amount', $claim->expected_amount) }}"
                                                   min="0"
                                                   step="0.01"
                                                   required
                                                   placeholder="0.00">
                                            @error('expected_amount')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="service_date" class="form-label">Service Date</label>
                                            <input type="date"
                                                   class="form-control @error('service_date') is-invalid @enderror"
                                                   id="service_date"
                                                   name="service_date"
                                                   value="{{ old('service_date', $claim->service_date ? $claim->service_date->format('Y-m-d') : '') }}">
                                            @error('service_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ICD-10 Codes -->
                                <div class="mb-3">
                                    <label for="icd10_codes" class="form-label">ICD-10 Codes</label>
                                    <input type="text"
                                           class="form-control @error('icd10_codes') is-invalid @enderror"
                                           id="icd10_codes"
                                           name="icd10_codes"
                                           value="{{ old('icd10_codes', $claim->icd10_codes ? implode(',', $claim->icd10_codes) : '') }}"
                                           placeholder="Enter codes separated by commas (e.g., J45.909, R06.02)">
                                    <div class="form-text">Comma-separated list of ICD-10 diagnostic codes</div>
                                    @error('icd10_codes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- CPT Codes -->
                                <div class="mb-3">
                                    <label for="cpt_codes" class="form-label">CPT Codes</label>
                                    <input type="text"
                                           class="form-control @error('cpt_codes') is-invalid @enderror"
                                           id="cpt_codes"
                                           name="cpt_codes"
                                           value="{{ old('cpt_codes', $claim->cpt_codes ? implode(',', $claim->cpt_codes) : '') }}"
                                           placeholder="Enter codes separated by commas (e.g., 99213, 87070)">
                                    <div class="form-text">Comma-separated list of CPT procedural codes</div>
                                    @error('cpt_codes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Claim Details -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Claim Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Claim ID</label>
                                    <p class="mb-0">#{{ $claim->id }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    @switch($claim->claim_status)
                                        @case('pending')
                                            <span class="badge bg-warning">Draft</span>
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
                                            <span class="badge bg-secondary">{{ ucfirst($claim->claim_status) }}</span>
                                    @endswitch
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Created</label>
                                    <p class="mb-0">{{ $claim->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                                @if($claim->updated_at->gt($claim->created_at))
                                    <div class="mb-3">
                                        <label class="form-label">Last Updated</label>
                                        <p class="mb-0">{{ $claim->updated_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Claim
                                    </button>
                                    <a href="{{ route('doctor.claims.show', $claim) }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Information</h5>
                            </div>
                            <div class="card-body">
                                @if($claim->claim_status !== 'submitted')
                                    <p class="mb-0 text-success">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        This claim can still be edited since it's still in draft status.
                                    </p>
                                @else
                                    <p class="mb-0 text-danger">
                                        <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                        This claim has been marked as ready for processing and can no longer be edited.
                                    </p>
                                @endif
                                @if($claim->claim_status === 'denied' && $claim->denial_reason)
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Denial Reason:</strong> {{ $claim->denial_reason }}
                                    </div>
                                @endif
                                @if($claim->paid_amount && $claim->paid_amount != $claim->expected_amount)
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Paid Amount:</strong> ${{ number_format($claim->paid_amount, 2) }}
                                        @if($claim->claim_status == 'approved')
                                            (Partial payment of expected ${{ number_format($claim->expected_amount, 2) }})
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
    </div></div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Format amount input
    $('#expected_amount').on('blur', function() {
        let value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });

    // Auto-capitalize first letter of payer name
    $('#payer').on('blur', function() {
        let value = $(this).val();
        if (value.length > 0) {
            $(this).val(value.charAt(0).toUpperCase() + value.slice(1).toLowerCase());
        }
    });

    // Convert JSON codes to comma-separated values when loading
    let icd10Codes = @json($claim->icd10_codes && is_array($claim->icd10_codes) ? $claim->icd10_codes : []);
    if (icd10Codes.length > 0) {
        $('#icd10_codes').val(icd10Codes.join(', '));
    }

    let cptCodes = @json($claim->cpt_codes && is_array($claim->cpt_codes) ? $claim->cpt_codes : []);
    if (cptCodes.length > 0) {
        $('#cpt_codes').val(cptCodes.join(', '));
    }

    // Convert comma-separated codes to JSON before form submission
    $('form').on('submit', function() {
        // Process ICD-10 codes
        let icd10Input = $('#icd10_codes').val().trim();
        if (icd10Input) {
            let icd10Codes = icd10Input.split(',').map(code => code.trim()).filter(code => code);
            $('#icd10_codes').val(JSON.stringify(icd10Codes));
        } else {
            $('#icd10_codes').val('');
        }

        // Process CPT codes
        let cptInput = $('#cpt_codes').val().trim();
        if (cptInput) {
            let cptCodes = cptInput.split(',').map(code => code.trim()).filter(code => code);
            $('#cpt_codes').val(JSON.stringify(cptCodes));
        } else {
            $('#cpt_codes').val('');
        }
    });
});
</script>
@endpush