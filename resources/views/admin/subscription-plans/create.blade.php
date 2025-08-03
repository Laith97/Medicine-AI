@extends('layouts.admin')

@section('title', 'Create Subscription Plan')

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

    .form-control:focus {
        border-color: #DE6262;
        box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    }

    .form-check-input:checked {
        background-color: #DE6262;
        border-color: #DE6262;
    }

    .feature-input {
        margin-bottom: 0.5rem;
    }

    .feature-input .input-group {
        margin-bottom: 0.5rem;
    }

    .btn-add-feature {
        background: #28a745;
        border: none;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-remove-feature {
        background: #dc3545;
        border: none;
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 5px;
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
let featureCount = 1;

function addFeature() {
    const container = document.getElementById('features-container');
    const newFeature = document.createElement('div');
    newFeature.className = 'feature-input';
    newFeature.innerHTML = `
        <div class="input-group">
            <input type="text" name="features[]" class="form-control" placeholder="Enter feature description">
            <button type="button" class="btn-remove-feature" onclick="removeFeature(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newFeature);
    featureCount++;
}

function removeFeature(button) {
    button.closest('.feature-input').remove();
}

function generateSlug() {
    const name = document.getElementById('name').value;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim('-');
    document.getElementById('slug').value = slug;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('name').addEventListener('input', generateSlug);
});
</script>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="admin-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2 text-white">Create Subscription Plan</h1>
                            <p class="mb-0 opacity-75">Add a new subscription plan</p>
                        </div>
                        <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-light">
                            <i class="bi bi-arrow-left me-2"></i>Back to Plans
                        </a>
                    </div>
                </div>

                <!-- Form -->
                <div class="form-card">
                    <form method="POST" action="{{ route('admin.subscription-plans.store') }}">
                        @csrf

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold">Plan Name</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="e.g., Basic Monthly">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="slug" class="form-label fw-bold">Slug</label>
                                <input id="slug" type="text" name="slug" value="{{ old('slug') }}" required
                                       class="form-control @error('slug') is-invalid @enderror"
                                       placeholder="e.g., basic-monthly">
                                <small class="text-muted">Auto-generated from name, but you can customize it</small>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Brief description of the plan">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Pricing -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="price" class="form-label fw-bold">Price ($)</label>
                                <input id="price" type="number" name="price" value="{{ old('price') }}" 
                                       step="0.01" min="0" max="99999.99" required
                                       class="form-control @error('price') is-invalid @enderror"
                                       placeholder="99.00">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="billing_cycle" class="form-label fw-bold">Billing Cycle</label>
                                <select id="billing_cycle" name="billing_cycle" required
                                        class="form-control @error('billing_cycle') is-invalid @enderror">
                                    <option value="">-- Select Billing Cycle --</option>
                                    <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                </select>
                                @error('billing_cycle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Features</label>
                            <div id="features-container">
                                <div class="feature-input">
                                    <div class="input-group">
                                        <input type="text" name="features[]" class="form-control" 
                                               placeholder="Enter feature description" value="{{ old('features.0') }}">
                                        <button type="button" class="btn-remove-feature" onclick="removeFeature(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-add-feature mt-2" onclick="addFeature()">
                                <i class="bi bi-plus me-2"></i>Add Feature
                            </button>
                            <small class="text-muted d-block mt-1">Add features that describe what's included in this plan</small>
                        </div>

                        <!-- Settings -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="sort_order" class="form-label fw-bold">Sort Order</label>
                                <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" 
                                       min="0" class="form-control @error('sort_order') is-invalid @enderror">
                                <small class="text-muted">Lower numbers appear first</small>
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                           value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="is_active">
                                        Active
                                    </label>
                                    <small class="text-muted d-block">Plan is available for selection</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" 
                                           value="1" {{ old('is_featured') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="is_featured">
                                        Featured
                                    </label>
                                    <small class="text-muted d-block">Highlight this plan as popular</small>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Create Plan
                            </button>
                            <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection