@extends('layouts.admin')

@section('title', 'Create Invoice')

@push('styles')
<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .invoice-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create Manual Invoice</h1>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Invoices
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="invoice-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Invoice Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.invoices.store') }}" id="invoiceForm">
                                @csrf
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="user_id" class="form-label">Select Doctor <span class="text-danger">*</span></label>
                                        <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                            <option value="">Choose a doctor...</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="description" class="form-label">Invoice Description <span class="text-danger">*</span></label>
                                        <input type="text" name="description" id="description" 
                                               class="form-control @error('description') is-invalid @enderror" 
                                               value="{{ old('description') }}" 
                                               placeholder="e.g., Manual billing adjustment" required>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6>Invoice Items</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addInvoiceItem()">
                                            <i class="fas fa-plus"></i> Add Item
                                        </button>
                                    </div>

                                    <div id="invoice-items">
                                        <!-- Initial item -->
                                        <div class="invoice-item border rounded p-3 mb-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[0][description]" 
                                                           class="form-control @error('items.0.description') is-invalid @enderror" 
                                                           value="{{ old('items.0.description') }}" 
                                                           placeholder="Item description" required>
                                                    @error('items.0.description')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" name="items[0][quantity]" 
                                                           class="form-control @error('items.0.quantity') is-invalid @enderror" 
                                                           value="{{ old('items.0.quantity', 1) }}" 
                                                           min="1" step="1">
                                                    @error('items.0.quantity')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                                                    <input type="number" name="items[0][amount]" 
                                                           class="form-control amount-input @error('items.0.amount') is-invalid @enderror" 
                                                           value="{{ old('items.0.amount') }}" 
                                                           min="0.01" step="0.01" 
                                                           placeholder="0.00" required>
                                                    @error('items.0.amount')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                                            onclick="removeInvoiceItem(this)" style="display: none;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <strong>Total: $<span id="total-amount">0.00</span></strong>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Invoice
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="invoice-card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Instructions</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Creating Manual Invoices</h6>
                                <ul class="mb-0">
                                    <li>Select the doctor who will receive this invoice</li>
                                    <li>Add a clear description for the invoice</li>
                                    <li>Add one or more line items with descriptions and amounts</li>
                                    <li>The invoice will be created in Stripe and sent to the doctor</li>
                                    <li>The doctor will receive an email notification</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Important Notes</h6>
                                <ul class="mb-0">
                                    <li>All amounts are in USD</li>
                                    <li>Invoices will be due 30 days from creation</li>
                                    <li>Once created, invoices cannot be edited</li>
                                    <li>You can void invoices if needed</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
let itemIndex = 1;

function addInvoiceItem() {
    const container = document.getElementById('invoice-items');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item border rounded p-3 mb-3';
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <input type="text" name="items[${itemIndex}][description]" 
                       class="form-control" 
                       placeholder="Item description" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" name="items[${itemIndex}][quantity]" 
                       class="form-control" 
                       value="1" 
                       min="1" step="1">
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                <input type="number" name="items[${itemIndex}][amount]" 
                       class="form-control amount-input" 
                       min="0.01" step="0.01" 
                       placeholder="0.00" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        onclick="removeInvoiceItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newItem);
    itemIndex++;
    
    // Show remove buttons if more than one item
    updateRemoveButtons();
    
    // Add event listener for amount calculation
    newItem.querySelector('.amount-input').addEventListener('input', calculateTotal);
    
    calculateTotal();
}

function removeInvoiceItem(button) {
    button.closest('.invoice-item').remove();
    updateRemoveButtons();
    calculateTotal();
}

function updateRemoveButtons() {
    const items = document.querySelectorAll('.invoice-item');
    const removeButtons = document.querySelectorAll('.invoice-item .btn-outline-danger');
    
    removeButtons.forEach((button, index) => {
        button.style.display = items.length > 1 ? 'block' : 'none';
    });
}

function calculateTotal() {
    const amountInputs = document.querySelectorAll('.amount-input');
    let total = 0;
    
    amountInputs.forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    
    document.getElementById('total-amount').textContent = total.toFixed(2);
}

// Add event listeners to existing amount inputs
document.addEventListener('DOMContentLoaded', function() {
    const amountInputs = document.querySelectorAll('.amount-input');
    amountInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    calculateTotal();
});
</script>
@endsection