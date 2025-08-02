@extends('layouts.admin')

@section('title', 'System Settings')

@push('styles')
<style>

    .settings-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .settings-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }
    
    .settings-card:hover {
        box-shadow: 0 12px 30px rgba(222, 98, 98, 0.15);
        transform: translateY(-2px);
    }

    .setting-item {
        padding: 1.5rem 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .setting-item:last-child {
        border-bottom: none;
    }

    .setting-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
    }

    .setting-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #DE6262;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .btn-custom-primary {
        background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
    }
    
    .btn-custom-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
        color: white;
        text-decoration: none;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        border: 1px solid transparent;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Configure global application settings</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<!-- Settings Form -->
<div class="settings-card">
            <form method="POST" action="{{ route('admin.system-settings.update') }}">
                @csrf
                
                <div class="setting-item">
                    <div class="setting-label">
                        <i class="fas fa-credit-card me-2" style="color: #DE6262;"></i>
                        Pricing Section Visibility
                    </div>
                    <div class="setting-description">
                        Control whether the pricing information section is displayed on the home page. When enabled, visitors will see information about personalized pricing.
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">
                            Currently: <strong>{{ ($settings['show_pricing_section']->value ?? '1') == '1' ? 'Visible' : 'Hidden' }}</strong>
                        </span>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   name="show_pricing_section" 
                                   value="1"
                                   {{ ($settings['show_pricing_section']->value ?? '1') == '1' ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-label">
                        <i class="fas fa-calculator me-2" style="color: #DE6262;"></i>
                        Default Monthly Pricing
                        <span class="badge bg-warning ms-2">Deprecated</span>
                    </div>
                    <div class="setting-description">
                        <strong>⚠️ This setting is deprecated.</strong> The system now uses per-user pricing instead of default amounts. 
                        Set individual pricing when creating/editing users in the <a href="{{ route('admin.users.index') }}">Manage Users</a> section.
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 me-3">
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       name="default_monthly_amount" 
                                       class="form-control"
                                       value="{{ $settings['default_monthly_amount']->value ?? '99.99' }}"
                                       step="0.01" 
                                       min="0" 
                                       max="9999.99"
                                       placeholder="99.99">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-label">
                        <i class="fas fa-calendar-alt me-2" style="color: #DE6262;"></i>
                        Default Grace Period
                    </div>
                    <div class="setting-description">
                        Set the default grace period (in days) for new user accounts before access is restricted for non-payment.
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 me-3">
                            <div class="input-group">
                                <input type="number" 
                                       name="default_grace_period" 
                                       class="form-control"
                                       value="{{ $settings['default_grace_period']->value ?? '7' }}"
                                       min="1" 
                                       max="30"
                                       placeholder="7">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trial Days Setting -->
                <div class="setting-item">
                    <div class="setting-title">
                        <i class="fas fa-calendar-check me-2"></i>
                        Free Trial Days
                    </div>
                    <div class="setting-description">
                        Set the number of free trial days for new users. Users can access all features during this period without payment.
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 me-3">
                            <div class="input-group">
                                <input type="number" 
                                       name="trial_days" 
                                       class="form-control"
                                       value="{{ $settings['trial_days']->value ?? '7' }}"
                                       min="1" 
                                       max="365"
                                       placeholder="7">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn-custom-primary">
                        <i class="fas fa-save me-2"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add visual feedback for toggle switches
    const toggles = document.querySelectorAll('.toggle-switch input');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const status = this.closest('.setting-item').querySelector('strong');
            if (status) {
                status.textContent = this.checked ? 'Visible' : 'Hidden';
            }
        });
    });
});
</script>
@endpush