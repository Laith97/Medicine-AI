@extends('layouts.admin')

@section('title', 'Edit User Pricing - ' . $user->name)

@push('styles')
<style>
    .admin-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .admin-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }

    .form-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .pricing-preview {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1rem;
    }

    .plan-preview {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .plan-preview.monthly {
        border-color: #007bff;
    }

    .plan-preview.yearly {
        border-color: #28a745;
    }

    .savings-badge {
        background: #d4edda;
        color: #155724;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2 text-white">Edit User Pricing</h1>
                    <p class="mb-0 opacity-75">Configure pricing for {{ $user->name }}</p>
                </div>
                <a href="{{ route('admin.user-pricing.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- User Info -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="form-card">
                    <h5 class="mb-3">User Information</h5>
                    <div class="mb-2">
                        <strong>Name:</strong> {{ $user->name }}
                    </div>
                    <div class="mb-2">
                        <strong>Email:</strong> {{ $user->email }}
                    </div>
                    <div class="mb-2">
                        <strong>Role:</strong> <span class="badge bg-secondary">{{ ucfirst($user->role) }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="form-card">
                    <h5 class="mb-3">Pricing Configuration</h5>
                    
                    <form action="{{ route('admin.user-pricing.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="monthly_price" class="form-label">Monthly Price ($)</label>
                                    <input type="number" class="form-control @error('monthly_price') is-invalid @enderror" 
                                           id="monthly_price" name="monthly_price" 
                                           value="{{ old('monthly_price', $setting->monthly_price ?? 0) }}" 
                                           step="0.01" min="0" max="9999.99" required>
                                    @error('monthly_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="yearly_price" class="form-label">Yearly Price ($)</label>
                                    <input type="number" class="form-control @error('yearly_price') is-invalid @enderror" 
                                           id="yearly_price" name="yearly_price" 
                                           value="{{ old('yearly_price', $setting->yearly_price ?? 0) }}" 
                                           step="0.01" min="0" max="99999.99" required>
                                    @error('yearly_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="grace_period_days" class="form-label">Grace Period (Days)</label>
                                    <input type="number" class="form-control @error('grace_period_days') is-invalid @enderror" 
                                           id="grace_period_days" name="grace_period_days" 
                                           value="{{ old('grace_period_days', $setting->grace_period_days ?? 7) }}" 
                                           min="0" max="365" required>
                                    @error('grace_period_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="warning_period_days" class="form-label">Warning Period (Days)</label>
                                    <input type="number" class="form-control @error('warning_period_days') is-invalid @enderror" 
                                           id="warning_period_days" name="warning_period_days" 
                                           value="{{ old('warning_period_days', $setting->warning_period_days ?? 7) }}" 
                                           min="0" max="365" required>
                                    @error('warning_period_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="reminder_frequency_days" class="form-label">Reminder Frequency (Days)</label>
                                    <input type="number" class="form-control @error('reminder_frequency_days') is-invalid @enderror" 
                                           id="reminder_frequency_days" name="reminder_frequency_days" 
                                           value="{{ old('reminder_frequency_days', $setting->reminder_frequency_days ?? 3) }}" 
                                           min="1" max="30" required>
                                    @error('reminder_frequency_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div class="pricing-preview">
                            <h6 class="mb-3">Pricing Preview</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="plan-preview monthly">
                                        <h6 class="text-primary">Monthly Plan</h6>
                                        <div class="h4 text-primary mb-1">$<span id="preview-monthly">{{ $setting->monthly_price ?? 0 }}</span></div>
                                        <small class="text-muted">per month</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="plan-preview yearly">
                                        <h6 class="text-success">Yearly Plan</h6>
                                        <div class="h4 text-success mb-1">$<span id="preview-yearly">{{ $setting->yearly_price ?? 0 }}</span></div>
                                        <small class="text-muted">per year</small>
                                        <div class="mt-2">
                                            <span class="savings-badge" id="savings-badge" style="display: none;">
                                                Save $<span id="savings-amount">0</span> (<span id="savings-percent">0</span>%)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.user-pricing.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Update Pricing
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyInput = document.getElementById('monthly_price');
    const yearlyInput = document.getElementById('yearly_price');
    const previewMonthly = document.getElementById('preview-monthly');
    const previewYearly = document.getElementById('preview-yearly');
    const savingsBadge = document.getElementById('savings-badge');
    const savingsAmount = document.getElementById('savings-amount');
    const savingsPercent = document.getElementById('savings-percent');

    function updatePreview() {
        const monthlyPrice = parseFloat(monthlyInput.value) || 0;
        const yearlyPrice = parseFloat(yearlyInput.value) || 0;

        previewMonthly.textContent = monthlyPrice.toFixed(2);
        previewYearly.textContent = yearlyPrice.toFixed(2);

        // Calculate savings
        if (monthlyPrice > 0 && yearlyPrice > 0) {
            const yearlyEquivalent = monthlyPrice * 12;
            const savings = yearlyEquivalent - yearlyPrice;
            const savingsPercentage = Math.round((savings / yearlyEquivalent) * 100);

            if (savings > 0) {
                savingsAmount.textContent = savings.toFixed(2);
                savingsPercent.textContent = savingsPercentage;
                savingsBadge.style.display = 'inline-block';
            } else {
                savingsBadge.style.display = 'none';
            }
        } else {
            savingsBadge.style.display = 'none';
        }
    }

    monthlyInput.addEventListener('input', updatePreview);
    yearlyInput.addEventListener('input', updatePreview);

    // Initial preview update
    updatePreview();
});
</script>
@endpush
@endsection