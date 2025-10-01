@extends('layouts.admin')

@section('title', 'Edit Monthly Invoice Settings - ' . $user->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Monthly Invoice Settings</h1>
                    <p class="text-muted mb-0">Configure monthly invoicing for {{ $user->name }}</p>
                </div>
                <a href="{{ route('admin.monthly-invoices.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Monthly Invoice Configuration</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.monthly-invoices.update', $user) }}">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="billing_amount" class="form-label">Billing Amount ($) <span class="text-danger">*</span></label>
                                            <input type="number" name="billing_amount" id="billing_amount" 
                                                   class="form-control @error('billing_amount') is-invalid @enderror" 
                                                   value="{{ old('billing_amount', $setting->billing_amount) }}" 
                                                   step="0.01" min="0" required>
                                            @error('billing_amount')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">The amount to charge this user monthly.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" id="is_active" 
                                                       class="form-check-input @error('is_active') is-invalid @enderror" 
                                                       value="1" {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    <strong>Enable Monthly Invoicing</strong>
                                                </label>
                                                @error('is_active')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">When enabled, monthly invoices will be automatically generated for this user.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="grace_period_days" class="form-label">Grace Period (days) <span class="text-danger">*</span></label>
                                            <input type="number" name="grace_period_days" id="grace_period_days" 
                                                   class="form-control @error('grace_period_days') is-invalid @enderror" 
                                                   value="{{ old('grace_period_days', $setting->grace_period_days) }}" 
                                                   min="1" max="30" required>
                                            @error('grace_period_days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Number of days after due date before restrictions apply.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reminder_frequency_days" class="form-label">Reminder Frequency (days) <span class="text-danger">*</span></label>
                                            <input type="number" name="reminder_frequency_days" id="reminder_frequency_days" 
                                                   class="form-control @error('reminder_frequency_days') is-invalid @enderror" 
                                                   value="{{ old('reminder_frequency_days', $setting->reminder_frequency_days) }}" 
                                                   min="1" max="14" required>
                                            @error('reminder_frequency_days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">How often to send reminder notifications (in days).</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Restricted Pages</label>
                                    <div class="form-text mb-2">Select which pages should be restricted when the user has unpaid invoices:</div>
                                    <div class="row">
                                        @foreach($availablePages as $route => $name)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input type="checkbox" name="restricted_pages[]" value="{{ $route }}" 
                                                           class="form-check-input @error('restricted_pages') is-invalid @enderror" 
                                                           id="page_{{ $route }}"
                                                           {{ in_array($route, old('restricted_pages', $setting->restricted_pages ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="page_{{ $route }}">
                                                        <strong>{{ $name }}</strong>
                                                        @php
                                                            $routeMapping = [
                                                                'ai.ask-ai' => ['ai.ask-ai', 'openai.respond', 'openai.follow-up', 'patient.summary'],
                                                                'cases' => ['cases'],
                                                                'dashboard' => ['dashboard'],
                                                                'appointments' => ['appointments.index', 'appointments.show', 'appointments.cancel', 'appointments.reschedule', 'appointments.calendar.events'],
                                                                'reviews' => ['reviews.index', 'reviews.show', 'reviews.create', 'reviews.store', 'reviews.edit', 'reviews.update', 'reviews.destroy', 'appointments.review'],
                                                                'settings' => ['settings', 'settings.update'],
                                                                'profile.edit' => ['profile.edit', 'profile.update', 'profile.destroy'],
                                                            ];
                                                        @endphp
                                                        @if(isset($routeMapping[$route]))
                                                            <br><small class="text-muted">Includes: {{ implode(', ', $routeMapping[$route]) }}</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('restricted_pages')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="alert alert-info mt-2">
                                        <small><i class="fas fa-info-circle"></i> <strong>Note:</strong> When a page is selected, all related routes (including POST/PUT requests) will also be restricted.</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="restriction_message" class="form-label">Custom Restriction Message</label>
                                    <textarea name="restriction_message" id="restriction_message" 
                                              class="form-control @error('restriction_message') is-invalid @enderror" 
                                              rows="3" placeholder="Leave empty to use the default message">{{ old('restriction_message', $setting->restriction_message) }}</textarea>
                                    @error('restriction_message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Custom message to show when the user's access is restricted. Leave empty for default message.</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('admin.monthly-invoices.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- User Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">User Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Name:</strong><br>
                                {{ $user->name }}
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong><br>
                                {{ $user->email }}
                            </div>
                            @if($user->phone)
                                <div class="mb-3">
                                    <strong>Phone:</strong><br>
                                    {{ $user->phone }}
                                </div>
                            @endif
                            <div class="mb-3">
                                <strong>Member Since:</strong><br>
                                {{ $user->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>

                    <!-- Current Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Current Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Monthly Invoicing:</strong><br>
                                @if($setting->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                            <div class="mb-3">
                                <strong>Access Status:</strong><br>
                                @if($setting->is_restricted)
                                    <span class="badge bg-danger">Restricted</span>
                                @else
                                    <span class="badge bg-success">Unrestricted</span>
                                @endif
                            </div>
                            @if($setting->last_reminder_sent_at)
                                <div class="mb-3">
                                    <strong>Last Reminder:</strong><br>
                                    {{ $setting->last_reminder_sent_at->format('M d, Y g:i A') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            @if($setting->is_restricted)
                                <form method="POST" action="{{ route('admin.monthly-invoices.unrestrict', $user) }}" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-100" 
                                            onclick="return confirm('Are you sure you want to unrestrict this user?')">
                                        <i class="fas fa-unlock"></i> Remove Restrictions
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-warning btn-sm w-100 mb-2" 
                                        onclick="showRestrictModal()">
                                    <i class="fas fa-ban"></i> Restrict Access
                                </button>
                            @endif
                            
                            <a href="{{ route('admin.invoices.index', ['user_id' => $user->id]) }}" 
                               class="btn btn-info btn-sm w-100">
                                <i class="fas fa-file-invoice"></i> View Invoices
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restrict User Modal -->
<div class="modal fade" id="restrictUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.monthly-invoices.restrict', $user) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Restrict User Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will immediately restrict access for <strong>{{ $user->name }}</strong>.</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Restricted Pages</label>
                        @foreach($availablePages as $route => $name)
                            <div class="form-check">
                                <input type="checkbox" name="restricted_pages[]" value="{{ $route }}" 
                                       class="form-check-input" id="modal_page_{{ $route }}" checked>
                                <label class="form-check-label" for="modal_page_{{ $route }}">
                                    {{ $name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_restriction_message" class="form-label">Custom Restriction Message</label>
                        <textarea name="restriction_message" id="modal_restriction_message" class="form-control" rows="3"
                                  placeholder="Leave empty for default message"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Restrict User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showRestrictModal() {
    new bootstrap.Modal(document.getElementById('restrictUserModal')).show();
}
</script>
@endpush