@extends('layouts.app')

@section('title', 'SMS Configuration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">SMS Configuration</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <p class="text-muted">
                        Configure your SMS settings. As a doctor or hospital admin, you can set up your own SMS provider or use the system's default settings.
                    </p>

                    <!-- User-specific configuration -->
                    <div class="mb-5">
                        <h5 class="mb-3">Your SMS Providers</h5>
                        <p class="text-muted">Configure SMS providers for your personal use</p>

                        @foreach($availableProviders as $key => $name)
                            @php
                                $userConfig = $userConfigMap->get($key);
                            @endphp
                            
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">{{ $name }}</h6>
                                    @if($userConfig)
                                        <span class="badge {{ $userConfig->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $userConfig->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('sms.config.store') }}">
                                        @csrf
                                        
                                        <input type="hidden" name="provider_key" value="{{ $key }}">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                                           id="active_{{ $key }}" {{ ($userConfig && $userConfig->is_active) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="active_{{ $key }}">
                                                        Enable {{ $name }} for your account
                                                    </label>
                                                </div>

                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="use_admin_config" value="1"
                                                           id="use_admin_{{ $key }}" {{ ($userConfig && $userConfig->use_admin_config) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="use_admin_{{ $key }}">
                                                        Use admin's configuration (don't configure your own)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        @if($userConfig && !$userConfig->use_admin_config)
                                            <div class="row mb-3">
                                                <div class="col-md-8">
                                                    <label class="form-label">Current Configuration Status</label>
                                                    <div class="alert alert-info">
                                                        Configuration is saved but may be incomplete. Please update if needed.
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if(!$userConfig || (!$userConfig->use_admin_config && $userConfig->is_active))
                                            <div class="row provider-config-fields" data-provider="{{ $key }}">
                                                @if($key === 'twilio')
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="twilio_account_sid_{{ $key }}" class="form-label">Account SID</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[account_sid]" 
                                                                   id="twilio_account_sid_{{ $key }}"
                                                                   value="{{ old('provider_config.account_sid', $userConfig ? ($userConfig->provider_config['account_sid'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="twilio_auth_token_{{ $key }}" class="form-label">Auth Token</label>
                                                            <input type="password" class="form-control" 
                                                                   name="provider_config[auth_token]" 
                                                                   id="twilio_auth_token_{{ $key }}"
                                                                   value="{{ old('provider_config.auth_token', $userConfig ? ($userConfig->provider_config['auth_token'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="twilio_from_number_{{ $key }}" class="form-label">From Number</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[from_number]" 
                                                                   id="twilio_from_number_{{ $key }}"
                                                                   value="{{ old('provider_config.from_number', $userConfig ? ($userConfig->provider_config['from_number'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                @elseif($key === 'plivo')
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="plivo_auth_id_{{ $key }}" class="form-label">Auth ID</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[auth_id]" 
                                                                   id="plivo_auth_id_{{ $key }}"
                                                                   value="{{ old('provider_config.auth_id', $userConfig ? ($userConfig->provider_config['auth_id'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="plivo_auth_token_{{ $key }}" class="form-label">Auth Token</label>
                                                            <input type="password" class="form-control" 
                                                                   name="provider_config[auth_token]" 
                                                                   id="plivo_auth_token_{{ $key }}"
                                                                   value="{{ old('provider_config.auth_token', $userConfig ? ($userConfig->provider_config['auth_token'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="plivo_from_number_{{ $key }}" class="form-label">From Number</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[from_number]" 
                                                                   id="plivo_from_number_{{ $key }}"
                                                                   value="{{ old('provider_config.from_number', $userConfig ? ($userConfig->provider_config['from_number'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                @elseif($key === 'messagebird')
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="messagebird_access_key_{{ $key }}" class="form-label">Access Key</label>
                                                            <input type="password" class="form-control" 
                                                                   name="provider_config[access_key]" 
                                                                   id="messagebird_access_key_{{ $key }}"
                                                                   value="{{ old('provider_config.access_key', $userConfig ? ($userConfig->provider_config['access_key'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="messagebird_from_number_{{ $key }}" class="form-label">Originator</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[from_number]" 
                                                                   id="messagebird_from_number_{{ $key }}"
                                                                   value="{{ old('provider_config.from_number', $userConfig ? ($userConfig->provider_config['from_number'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                @elseif($key === 'unifonic')
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="unifonic_app_sid_{{ $key }}" class="form-label">App SID</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[app_sid]" 
                                                                   id="unifonic_app_sid_{{ $key }}"
                                                                   value="{{ old('provider_config.app_sid', $userConfig ? ($userConfig->provider_config['app_sid'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="unifonic_sender_id_{{ $key }}" class="form-label">Sender ID</label>
                                                            <input type="text" class="form-control" 
                                                                   name="provider_config[sender_id]" 
                                                                   id="unifonic_sender_id_{{ $key }}"
                                                                   value="{{ old('provider_config.sender_id', $userConfig ? ($userConfig->provider_config['sender_id'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                @elseif($key === 'smsgatewayhub')
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="smsgatewayhub_email_{{ $key }}" class="form-label">Email</label>
                                                            <input type="email" class="form-control" 
                                                                   name="provider_config[email]" 
                                                                   id="smsgatewayhub_email_{{ $key }}"
                                                                   value="{{ old('provider_config.email', $userConfig ? ($userConfig->provider_config['email'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="smsgatewayhub_password_{{ $key }}" class="form-label">Password</label>
                                                            <input type="password" class="form-control" 
                                                                   name="provider_config[password]" 
                                                                   id="smsgatewayhub_password_{{ $key }}"
                                                                   value="{{ old('provider_config.password', $userConfig ? ($userConfig->provider_config['password'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="smsgatewayhub_device_{{ $key }}" class="form-label">Device ID</label>
                                                            <input type="text" class="form-control"
                                                                   name="provider_config[device]"
                                                                   id="smsgatewayhub_device_{{ $key }}"
                                                                   value="{{ old('provider_config.device', $userConfig ? ($userConfig->provider_config['device'] ?? '') : '') }}">
                                                        </div>
                                                    </div>
                                                @elseif(in_array($key, ['msegat', 'taqnyat', 'smsala', 'connectsaudi']) && isset($providerRequirements[$key]))
                                                    @php $prefix = $key; @endphp
                                                    <x-sms.provider-fields
                                                        :provider-key="$key"
                                                        :prefix="$prefix"
                                                        :requirements="$providerRequirements[$key]"
                                                        :config="$userConfig"
                                                    />
                                                @endif
                                            </div>
                                        @endif

                                        <div class="d-flex justify-content-between">
                                            <button type="submit" class="btn btn-primary">
                                                Save {{ $name }} Configuration
                                            </button>

                                            @if($userConfig)
                                                <button type="button" class="btn btn-success"
                                                        onclick="testSms('{{ $key }}', this)">
                                                    Test {{ $name }}
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Hospital configuration (for hospital admins) -->
                    @if($user->isHospitalAdmin() && $user->hospital_id)
                        <div class="mb-5">
                            <h5 class="mb-3">Hospital SMS Providers</h5>
                            <p class="text-muted">Configure SMS providers for your entire hospital</p>

                            @foreach($availableProviders as $key => $name)
                                @php
                                    $hospitalConfig = $hospitalConfigMap->get($key);
                                @endphp
                                
                                <div class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Hospital - {{ $name }}</h6>
                                        @if($hospitalConfig)
                                            <span class="badge {{ $hospitalConfig->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $hospitalConfig->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('sms.config.store.hospital') }}">
                                            @csrf
                                            
                                            <input type="hidden" name="provider_key" value="{{ $key }}">
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                                               id="hospital_active_{{ $key }}" {{ ($hospitalConfig && $hospitalConfig->is_active) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="hospital_active_{{ $key }}">
                                                            Enable {{ $name }} for your hospital
                                                        </label>
                                                    </div>

                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" name="use_admin_config" value="1"
                                                               id="hospital_use_admin_{{ $key }}" {{ ($hospitalConfig && $hospitalConfig->use_admin_config) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="hospital_use_admin_{{ $key }}">
                                                            Use admin's configuration (don't configure your own)
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($hospitalConfig && !$hospitalConfig->use_admin_config)
                                                <div class="row mb-3">
                                                    <div class="col-md-8">
                                                        <label class="form-label">Current Configuration Status</label>
                                                        <div class="alert alert-info">
                                                            Configuration is saved but may be incomplete. Please update if needed.
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(!$hospitalConfig || (!$hospitalConfig->use_admin_config && $hospitalConfig->is_active))
                                                <div class="row provider-config-fields" data-provider="{{ $key }}">
                                                    @if($key === 'twilio')
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_twilio_account_sid_{{ $key }}" class="form-label">Account SID</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[account_sid]" 
                                                                       id="hospital_twilio_account_sid_{{ $key }}"
                                                                       value="{{ old('provider_config.account_sid', $hospitalConfig ? ($hospitalConfig->provider_config['account_sid'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_twilio_auth_token_{{ $key }}" class="form-label">Auth Token</label>
                                                                <input type="password" class="form-control" 
                                                                       name="provider_config[auth_token]" 
                                                                       id="hospital_twilio_auth_token_{{ $key }}"
                                                                       value="{{ old('provider_config.auth_token', $hospitalConfig ? ($hospitalConfig->provider_config['auth_token'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_twilio_from_number_{{ $key }}" class="form-label">From Number</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[from_number]" 
                                                                       id="hospital_twilio_from_number_{{ $key }}"
                                                                       value="{{ old('provider_config.from_number', $hospitalConfig ? ($hospitalConfig->provider_config['from_number'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                    @elseif($key === 'plivo')
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_plivo_auth_id_{{ $key }}" class="form-label">Auth ID</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[auth_id]" 
                                                                       id="hospital_plivo_auth_id_{{ $key }}"
                                                                       value="{{ old('provider_config.auth_id', $hospitalConfig ? ($hospitalConfig->provider_config['auth_id'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_plivo_auth_token_{{ $key }}" class="form-label">Auth Token</label>
                                                                <input type="password" class="form-control" 
                                                                       name="provider_config[auth_token]" 
                                                                       id="hospital_plivo_auth_token_{{ $key }}"
                                                                       value="{{ old('provider_config.auth_token', $hospitalConfig ? ($hospitalConfig->provider_config['auth_token'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_plivo_from_number_{{ $key }}" class="form-label">From Number</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[from_number]" 
                                                                       id="hospital_plivo_from_number_{{ $key }}"
                                                                       value="{{ old('provider_config.from_number', $hospitalConfig ? ($hospitalConfig->provider_config['from_number'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                    @elseif($key === 'messagebird')
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_messagebird_access_key_{{ $key }}" class="form-label">Access Key</label>
                                                                <input type="password" class="form-control" 
                                                                       name="provider_config[access_key]" 
                                                                       id="hospital_messagebird_access_key_{{ $key }}"
                                                                       value="{{ old('provider_config.access_key', $hospitalConfig ? ($hospitalConfig->provider_config['access_key'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_messagebird_from_number_{{ $key }}" class="form-label">Originator</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[from_number]" 
                                                                       id="hospital_messagebird_from_number_{{ $key }}"
                                                                       value="{{ old('provider_config.from_number', $hospitalConfig ? ($hospitalConfig->provider_config['from_number'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                    @elseif($key === 'unifonic')
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_unifonic_app_sid_{{ $key }}" class="form-label">App SID</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[app_sid]" 
                                                                       id="hospital_unifonic_app_sid_{{ $key }}"
                                                                       value="{{ old('provider_config.app_sid', $hospitalConfig ? ($hospitalConfig->provider_config['app_sid'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_unifonic_sender_id_{{ $key }}" class="form-label">Sender ID</label>
                                                                <input type="text" class="form-control" 
                                                                       name="provider_config[sender_id]" 
                                                                       id="hospital_unifonic_sender_id_{{ $key }}"
                                                                       value="{{ old('provider_config.sender_id', $hospitalConfig ? ($hospitalConfig->provider_config['sender_id'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                    @elseif($key === 'smsgatewayhub')
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_smsgatewayhub_email_{{ $key }}" class="form-label">Email</label>
                                                                <input type="email" class="form-control" 
                                                                       name="provider_config[email]" 
                                                                       id="hospital_smsgatewayhub_email_{{ $key }}"
                                                                       value="{{ old('provider_config.email', $hospitalConfig ? ($hospitalConfig->provider_config['email'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_smsgatewayhub_password_{{ $key }}" class="form-label">Password</label>
                                                                <input type="password" class="form-control" 
                                                                       name="provider_config[password]" 
                                                                       id="hospital_smsgatewayhub_password_{{ $key }}"
                                                                       value="{{ old('provider_config.password', $hospitalConfig ? ($hospitalConfig->provider_config['password'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="hospital_smsgatewayhub_device_{{ $key }}" class="form-label">Device ID</label>
                                                                <input type="text" class="form-control"
                                                                       name="provider_config[device]"
                                                                       id="hospital_smsgatewayhub_device_{{ $key }}"
                                                                       value="{{ old('provider_config.device', $hospitalConfig ? ($hospitalConfig->provider_config['device'] ?? '') : '') }}">
                                                            </div>
                                                        </div>
                                                    @elseif(in_array($key, ['msegat', 'taqnyat', 'smsala', 'connectsaudi']) && isset($providerRequirements[$key]))
                                                        <x-sms.provider-fields
                                                            :provider-key="$key"
                                                            :prefix="'hospital_' . $key"
                                                            :requirements="$providerRequirements[$key]"
                                                            :config="$hospitalConfig"
                                                        />
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-primary">
                                                    Save Hospital {{ $name }} Configuration
                                                </button>

                                                @if($hospitalConfig)
                                                    <button type="button" class="btn btn-success"
                                                            onclick="testSms('{{ $key }}', this, true)">
                                                        Test Hospital {{ $name }}
                                                    </button>
                                                @endif
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testSms(providerKey, button, isHospital = false) {
    const phone = prompt('Enter a phone number to test SMS:', '');
    if (!phone) return;

    // Disable the button during testing
    const originalText = button.textContent;
    button.textContent = 'Testing...';
    button.disabled = true;

    // Show loading indicator
    const originalHtml = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Testing...';

    fetch('{{ route("sms.config.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            provider_key: providerKey,
            test_phone: phone,
            is_hospital: isHospital
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test SMS sent successfully!');
        } else {
            alert('Failed to send test SMS: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while testing SMS.');
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}

// Toggle provider configuration fields based on "use admin config" checkbox
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="use_admin_config"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const providerFields = this.closest('.card-body').querySelector('.provider-config-fields');
            if (providerFields) {
                providerFields.style.display = this.checked ? 'none' : 'flex';
            }
        });
    });
});
</script>
@endsection