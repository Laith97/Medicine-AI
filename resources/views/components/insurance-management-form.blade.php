@props(['patient' => null, 'insurance' => null])

<div class="insurance-management-form">
    <form id="insuranceForm" enctype="multipart/form-data">
        @csrf
        @if($insurance)
            <input type="hidden" name="insurance_id" value="{{ $insurance->id }}">
        @endif

        <div class="row">
            <!-- Insurance Provider Selection -->
            <div class="col-md-6 mb-3">
                <label for="insurance_provider_id" class="form-label">
                    <i class="fas fa-building me-2"></i>Insurance Provider <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="insurance_provider_id" name="insurance_provider_id" required
                        aria-describedby="providerHelp">
                    <option value="">Select Insurance Provider</option>
                    @foreach(\App\Models\InsuranceProvider::all() as $provider)
                        <option value="{{ $provider->id }}"
                                {{ ($insurance && $insurance->insurance_provider_id == $provider->id) ? 'selected' : '' }}>
                            {{ $provider->name }}
                        </option>
                    @endforeach
                </select>
                <div id="providerHelp" class="form-text">Choose the patient's insurance provider</div>
            </div>

            <!-- Policy Number -->
            <div class="col-md-6 mb-3">
                <label for="policy_number" class="form-label">
                    <i class="fas fa-id-card me-2"></i>Policy Number <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="policy_number" name="policy_number"
                       value="{{ $insurance ? $insurance->policy_number : '' }}" required
                       placeholder="Enter policy number" aria-describedby="policyHelp">
                <div id="policyHelp" class="form-text">The policy number from the insurance card</div>
            </div>
        </div>

        <div class="row">
            <!-- Group Number -->
            <div class="col-md-6 mb-3">
                <label for="group_number" class="form-label">
                    <i class="fas fa-users me-2"></i>Group Number
                </label>
                <input type="text" class="form-control" id="group_number" name="group_number"
                       value="{{ $insurance ? $insurance->group_number : '' }}"
                       placeholder="Enter group number (if applicable)">
            </div>

            <!-- Member ID -->
            <div class="col-md-6 mb-3">
                <label for="member_id" class="form-label">
                    <i class="fas fa-user-tag me-2"></i>Member ID <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="member_id" name="member_id"
                       value="{{ $insurance ? $insurance->member_id : '' }}" required
                       placeholder="Enter member ID" aria-describedby="memberHelp">
                <div id="memberHelp" class="form-text">The member ID from the insurance card</div>
            </div>
        </div>

        <div class="row">
            <!-- Effective Date -->
            <div class="col-md-6 mb-3">
                <label for="effective_date" class="form-label">
                    <i class="fas fa-calendar-plus me-2"></i>Effective Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control" id="effective_date" name="effective_date"
                       value="{{ $insurance ? $insurance->effective_date->format('Y-m-d') : '' }}" required>
            </div>

            <!-- Expiration Date -->
            <div class="col-md-6 mb-3">
                <label for="expiration_date" class="form-label">
                    <i class="fas fa-calendar-times me-2"></i>Expiration Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control" id="expiration_date" name="expiration_date"
                       value="{{ $insurance ? $insurance->expiration_date->format('Y-m-d') : '' }}" required>
            </div>
        </div>

        <!-- Insurance Card Upload -->
        <div class="mb-3">
            <label for="insurance_card" class="form-label">
                <i class="fas fa-upload me-2"></i>Insurance Card Upload
            </label>
            <input type="file" class="form-control" id="insurance_card" name="insurance_card"
                   accept="image/*,.pdf" aria-describedby="cardHelp">
            <div id="cardHelp" class="form-text">
                Upload a photo or scan of the insurance card (JPG, PNG, PDF - Max 5MB)
            </div>
            @if($insurance && $insurance->card_path)
                <div class="mt-2">
                    <small class="text-muted">Current file: {{ basename($insurance->card_path) }}</small>
                </div>
            @endif
        </div>

        <!-- Additional Notes -->
        <div class="mb-3">
            <label for="notes" class="form-label">
                <i class="fas fa-sticky-note me-2"></i>Additional Notes
            </label>
            <textarea class="form-control" id="notes" name="notes" rows="3"
                      placeholder="Any additional notes about the insurance coverage">{{ $insurance ? $insurance->notes : '' }}</textarea>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" onclick="closeInsuranceModal()">
                <i class="fas fa-times me-2"></i>Cancel
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>{{ $insurance ? 'Update' : 'Save' }} Insurance
            </button>
        </div>
    </form>
</div>

<script>
function closeInsuranceModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('insuranceModal'));
    if (modal) {
        modal.hide();
    }
}
</script>
