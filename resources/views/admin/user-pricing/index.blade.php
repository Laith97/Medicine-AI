@extends('layouts.admin')

@section('title', 'User Pricing Management')

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

    .user-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }

    .user-card:hover {
        transform: translateY(-2px);
    }

    .pricing-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .pricing-set {
        background: #d4edda;
        color: #155724;
    }

    .pricing-not-set {
        background: #f8d7da;
        color: #721c24;
    }

    .bulk-actions {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
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
                    <h1 class="h2 mb-2 text-white">User Pricing Management</h1>
                    <p class="mb-0 opacity-75">Set individual monthly and yearly pricing for each user</p>
                </div>
                <div class="text-white">
                    <i class="bi bi-currency-dollar" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Bulk Actions -->
        <div class="bulk-actions">
            <h5 class="mb-3">Bulk Update Pricing</h5>
            <form action="{{ route('admin.user-pricing.bulk-update') }}" method="POST" id="bulkUpdateForm">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <label for="bulk_monthly_price" class="form-label">Monthly Price ($)</label>
                        <input type="number" class="form-control" id="bulk_monthly_price" name="monthly_price" 
                               step="0.01" min="0" max="9999.99" required>
                    </div>
                    <div class="col-md-3">
                        <label for="bulk_yearly_price" class="form-label">Yearly Price ($)</label>
                        <input type="number" class="form-control" id="bulk_yearly_price" name="yearly_price" 
                               step="0.01" min="0" max="99999.99" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Selected Users</label>
                        <div class="form-control" style="height: auto; min-height: 38px;">
                            <span id="selectedCount">0</span> users selected
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100" id="bulkUpdateBtn" disabled>
                            Update Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users List -->
        @if($users->count() > 0)
            <div class="row">
                @foreach($users as $user)
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="user-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="form-check">
                                    <input class="form-check-input user-checkbox" type="checkbox" 
                                           value="{{ $user->id }}" id="user_{{ $user->id }}">
                                    <label class="form-check-label fw-bold" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                    </label>
                                </div>
                                <span class="pricing-badge {{ $user->monthlyInvoiceSetting && $user->monthlyInvoiceSetting->monthly_price ? 'pricing-set' : 'pricing-not-set' }}">
                                    {{ $user->monthlyInvoiceSetting && $user->monthlyInvoiceSetting->monthly_price ? 'Pricing Set' : 'No Pricing' }}
                                </span>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>

                            <div class="mb-2">
                                <span class="badge bg-secondary">{{ ucfirst($user->role) }}</span>
                            </div>

                            @if($user->monthlyInvoiceSetting)
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="fw-bold text-primary">
                                            ${{ number_format($user->monthlyInvoiceSetting->monthly_price ?? 0, 2) }}
                                        </div>
                                        <small class="text-muted">Monthly</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold text-success">
                                            ${{ number_format($user->monthlyInvoiceSetting->yearly_price ?? 0, 2) }}
                                        </div>
                                        <small class="text-muted">Yearly</small>
                                    </div>
                                </div>

                                @if($user->monthlyInvoiceSetting->monthly_price && $user->monthlyInvoiceSetting->yearly_price)
                                    @php
                                        $monthlySavings = ($user->monthlyInvoiceSetting->monthly_price * 12) - $user->monthlyInvoiceSetting->yearly_price;
                                        $savingsPercentage = $user->monthlyInvoiceSetting->monthly_price > 0 ? round(($monthlySavings / ($user->monthlyInvoiceSetting->monthly_price * 12)) * 100) : 0;
                                    @endphp
                                    @if($monthlySavings > 0)
                                        <div class="text-center mb-3">
                                            <small class="text-success">
                                                <i class="bi bi-arrow-down"></i>
                                                Save ${{ number_format($monthlySavings, 2) }} ({{ $savingsPercentage }}%) yearly
                                            </small>
                                        </div>
                                    @endif
                                @endif
                            @else
                                <div class="text-center text-muted mb-3">
                                    <i class="bi bi-dash-circle"></i>
                                    <div>No pricing configured</div>
                                </div>
                            @endif

                            <div class="d-grid">
                                <a href="{{ route('admin.user-pricing.edit', $user) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil me-1"></i>Edit Pricing
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                </div>
                <h4 class="text-muted">No Users Found</h4>
                <p class="text-muted">No users available for pricing configuration.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
    const bulkUpdateForm = document.getElementById('bulkUpdateForm');

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        selectedCount.textContent = checked.length;
        bulkUpdateBtn.disabled = checked.length === 0;
        
        // Remove existing hidden inputs
        const existingInputs = bulkUpdateForm.querySelectorAll('input[name="user_ids[]"]');
        existingInputs.forEach(input => input.remove());
        
        // Add new hidden inputs for selected users
        checked.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'user_ids[]';
            hiddenInput.value = checkbox.value;
            bulkUpdateForm.appendChild(hiddenInput);
        });
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Select all functionality
    const selectAllBtn = document.createElement('button');
    selectAllBtn.type = 'button';
    selectAllBtn.className = 'btn btn-sm btn-outline-secondary me-2';
    selectAllBtn.innerHTML = '<i class="bi bi-check-all me-1"></i>Select All';
    selectAllBtn.onclick = function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateSelectedCount();
    };

    const clearAllBtn = document.createElement('button');
    clearAllBtn.type = 'button';
    clearAllBtn.className = 'btn btn-sm btn-outline-secondary';
    clearAllBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Clear All';
    clearAllBtn.onclick = function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateSelectedCount();
    };

    // Add buttons to bulk actions
    const bulkActions = document.querySelector('.bulk-actions .row');
    const buttonCol = document.createElement('div');
    buttonCol.className = 'col-12 mt-2';
    buttonCol.appendChild(selectAllBtn);
    buttonCol.appendChild(clearAllBtn);
    bulkActions.appendChild(buttonCol);
});
</script>
@endpush
@endsection