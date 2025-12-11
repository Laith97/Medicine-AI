@extends('layouts.admin')

@section('title', 'Create Rule for ' . $payer->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Create New Rule</h1>
                <p class="text-muted">{{ $payer->name }} ({{ $payer->payer_id }})</p>
            </div>
            <a href="{{ route('admin.payers.rules.index', $payer) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Rules
            </a>
        </div>

        <form action="{{ route('admin.payers.rules.store', $payer) }}" method="POST" id="ruleForm">
            @csrf

            <div class="row">
                <!-- Basic Rule Settings -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Rule Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="rule_type_id" class="form-label">Rule Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('rule_type_id') is-invalid @enderror"
                                        id="rule_type_id" name="rule_type_id" required>
                                    <option value="">Select Rule Type</option>
                                    @foreach($ruleTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('rule_type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('rule_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror"
                                        id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    @for($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}" {{ old('priority', 5) == $i ? 'selected' : '' }}>
                                            {{ $i }} - {{ $i <= 3 ? 'High' : ($i <= 7 ? 'Medium' : 'Low') }}
                                        </option>
                                    @endfor
                                </select>
                                <div class="form-text">Lower numbers = higher priority</div>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conditions Builder -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Conditions</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addCondition">
                                <i class="fas fa-plus me-1"></i>Add Condition
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="conditionsContainer">
                                <!-- Conditions will be added here dynamically -->
                            </div>
                            @error('conditions')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Actions Builder -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Actions</h5>
                            <button type="button" class="btn btn-sm btn-outline-success" id="addAction">
                                <i class="fas fa-plus me-1"></i>Add Action
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="actionsContainer">
                                <!-- Actions will be added here dynamically -->
                            </div>
                            @error('actions')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.payers.rules.index', $payer) }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Rule
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Condition/Action Templates (Hidden) -->
<div id="templates" style="display: none;">
    <div class="condition-template mb-3 p-3 border rounded">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Field</label>
                <select class="form-select condition-field" name="conditions[INDEX][field]">
                    <option value="">Select Field</option>
                    <option value="patient_id">Patient ID</option>
                    <option value="provider_id">Provider ID</option>
                    <option value="service_code">Service Code</option>
                    <option value="diagnosis_codes">Diagnosis Codes</option>
                    <option value="procedure_codes">Procedure Codes</option>
                    <option value="amount">Amount</option>
                    <option value="date_of_service">Date of Service</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Operator</label>
                <select class="form-select condition-operator" name="conditions[INDEX][operator]">
                    <option value="">Select Operator</option>
                    <option value="equals">Equals</option>
                    <option value="not_equals">Not Equals</option>
                    <option value="contains">Contains</option>
                    <option value="not_contains">Not Contains</option>
                    <option value="greater_than">Greater Than</option>
                    <option value="less_than">Less Than</option>
                    <option value="in">In List</option>
                    <option value="not_in">Not In List</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Value</label>
                <input type="text" class="form-control condition-value" name="conditions[INDEX][value]" placeholder="Enter value">
            </div>
            <div class="col-md-2">
                <label class="form-label">Logic</label>
                <select class="form-select condition-logic" name="conditions[INDEX][logic]">
                    <option value="AND">AND</option>
                    <option value="OR">OR</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm remove-condition">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="action-template mb-3 p-3 border rounded">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Action Type</label>
                <select class="form-select action-type" name="actions[INDEX][type]">
                    <option value="">Select Action</option>
                    <option value="approve">Approve Claim</option>
                    <option value="deny">Deny Claim</option>
                    <option value="modify_amount">Modify Amount</option>
                    <option value="add_note">Add Note</option>
                    <option value="flag_for_review">Flag for Review</option>
                    <option value="escalate">Escalate</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Parameters</label>
                <input type="text" class="form-control action-params" name="actions[INDEX][params]" placeholder="Action parameters (JSON)">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-action">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let conditionIndex = 0;
let actionIndex = 0;

$(document).ready(function() {
    // Add initial condition and action
    addCondition();
    addAction();
});

$('#addCondition').click(function() {
    addCondition();
});

$('#addAction').click(function() {
    addAction();
});

function addCondition() {
    const template = $('.condition-template').clone().removeClass('condition-template');
    const html = template.html().replace(/INDEX/g, conditionIndex);
    $('#conditionsContainer').append(html);
    conditionIndex++;
}

function addAction() {
    const template = $('.action-template').clone().removeClass('action-template');
    const html = template.html().replace(/INDEX/g, actionIndex);
    $('#actionsContainer').append(html);
    actionIndex++;
}

$(document).on('click', '.remove-condition', function() {
    $(this).closest('.condition-template').remove();
});

$(document).on('click', '.remove-action', function() {
    $(this).closest('.action-template').remove();
});

// Update form field names when conditions/actions are removed
$('#ruleForm').on('submit', function() {
    // Re-index conditions
    $('#conditionsContainer .condition-template').each(function(index) {
        $(this).find('[name]').each(function() {
            const name = $(this).attr('name').replace(/\[\d+\]/, '[' + index + ']');
            $(this).attr('name', name);
        });
    });

    // Re-index actions
    $('#actionsContainer .action-template').each(function(index) {
        $(this).find('[name]').each(function() {
            const name = $(this).attr('name').replace(/\[\d+\]/, '[' + index + ']');
            $(this).attr('name', name);
        });
    });
});
</script>
@endsection
