@extends('layouts.admin')

@section('title', 'Edit Payer - ' . $payer->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Edit Payer</h1>
                <p class="text-muted">{{ $payer->name }} ({{ $payer->payer_id }})</p>
            </div>
            <a href="{{ route('admin.payers.show', $payer) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Payer
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payer Information</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.payers.update', $payer) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Payer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $payer->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="payer_id" class="form-label">Payer ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('payer_id') is-invalid @enderror"
                                   id="payer_id" name="payer_id" value="{{ old('payer_id', $payer->payer_id) }}" required>
                            <div class="form-text">Unique identifier for the payer (e.g., BCBS001)</div>
                            @error('payer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h6 class="mb-3">Contact Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_info_email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('contact_info.email') is-invalid @enderror"
                                   id="contact_info_email" name="contact_info[email]"
                                   value="{{ old('contact_info.email', $payer->contact_info['email'] ?? '') }}">
                            @error('contact_info.email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="contact_info_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control @error('contact_info.phone') is-invalid @enderror"
                                   id="contact_info_phone" name="contact_info[phone]"
                                   value="{{ old('contact_info.phone', $payer->contact_info['phone'] ?? '') }}">
                            @error('contact_info.phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="contact_info_address" class="form-label">Address</label>
                        <textarea class="form-control @error('contact_info.address') is-invalid @enderror"
                                  id="contact_info_address" name="contact_info[address]" rows="3">{{ old('contact_info.address', $payer->contact_info['address'] ?? '') }}</textarea>
                        @error('contact_info.address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <h6 class="mb-3">Processing Settings</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="processing_time_days" class="form-label">Processing Time (Days)</label>
                            <input type="number" class="form-control @error('settings.processing_time_days') is-invalid @enderror"
                                   id="processing_time_days" name="settings[processing_time_days]"
                                   value="{{ old('settings.processing_time_days', $payer->settings['processing_time_days'] ?? 30) }}"
                                   min="1" max="365">
                            <div class="form-text">Average days for claim processing</div>
                            @error('settings.processing_time_days')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="requires_pre_auth" class="form-label">Requires Pre-Authorization</label>
                            <select class="form-select @error('settings.requires_pre_auth') is-invalid @enderror"
                                    id="requires_pre_auth" name="settings[requires_pre_auth]">
                                <option value="0" {{ old('settings.requires_pre_auth', $payer->settings['requires_pre_auth'] ?? 0) == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('settings.requires_pre_auth', $payer->settings['requires_pre_auth'] ?? 0) == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                            @error('settings.requires_pre_auth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="auto_approve_under" class="form-label">Auto-Approve Under ($)</label>
                            <input type="number" class="form-control @error('settings.auto_approve_under') is-invalid @enderror"
                                   id="auto_approve_under" name="settings[auto_approve_under]"
                                   value="{{ old('settings.auto_approve_under', $payer->settings['auto_approve_under'] ?? '') }}"
                                   min="0" step="0.01">
                            <div class="form-text">Claims under this amount are auto-approved</div>
                            @error('settings.auto_approve_under')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.payers.show', $payer) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Payer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
