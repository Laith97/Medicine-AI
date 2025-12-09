@extends('master')

@section('title', 'Create New Claim')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create New Insurance Claim</h1>
                <a href="{{ route('doctor.claims.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Claims
                </a>
            </div>

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('doctor.claims.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Claim Information</h5>
                            </div>
                            <div class="card-body">
                                <!-- Patient Selection -->
                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select @error('patient_id') is-invalid @enderror"
                                            id="patient_id"
                                            name="patient_id"
                                            required>
                                        <option value="">Select a patient</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}"
                                                    {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                                {{ $patient->name }} ({{ $patient->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patient_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Diagnosis -->
                                <div class="mb-3">
                                    <label for="diagnosis_text" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('diagnosis_text') is-invalid @enderror"
                                              id="diagnosis_text"
                                              name="diagnosis_text"
                                              rows="3"
                                              required
                                              placeholder="Enter the diagnosis description...">{{ old('diagnosis_text') }}</textarea>
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
                                              placeholder="Enter the procedure description...">{{ old('procedure_text') }}</textarea>
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
                                           value="{{ old('payer') }}"
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
                                                   value="{{ old('expected_amount') }}"
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
                                                   value="{{ old('service_date') }}">
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
                                           value="{{ old('icd10_codes') }}"
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
                                           value="{{ old('cpt_codes') }}"
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
                        <!-- Claim Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Claim Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="badge bg-info">Draft</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date Created</label>
                                    <p class="mb-0">{{ \Carbon\Carbon::now()->format('M d, Y g:i A') }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Claim ID</label>
                                    <p class="mb-0"><em>Will be assigned after creation</em></p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Claim
                                    </button>
                                    <a href="{{ route('doctor.claims.index') }}" class="btn btn-outline-secondary">
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
                                <p class="mb-0">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Once created, you can review and submit the claim to the insurance clearinghouse.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
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