@extends('master')

@section('title', 'Kiosk Setup - Doctor Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-desktop mr-2"></i>
                        Kiosk Configuration
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('doctor.kiosk.management') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-cogs"></i> Management Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible" role="alert" aria-live="assertive">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close error messages">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="alert-heading">
                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                Please correct the following errors:
                            </h4>
                            <ul class="mb-0" id="error-list">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <script>
                            // Focus on first error field when page loads
                            document.addEventListener('DOMContentLoaded', function() {
                                const firstErrorField = document.querySelector('.form-control.is-invalid');
                                if (firstErrorField) {
                                    firstErrorField.focus();
                                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            });
                        </script>
                    @endif

                    <form action="{{ route('doctor.kiosk.setup.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Clinic Information -->
                            <div class="col-md-6">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Clinic Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="clinic_name">Clinic Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control {{ $errors->has('clinic_name') ? 'is-invalid' : '' }}"
                                                   id="clinic_name" name="clinic_name"
                                                   value="{{ old('clinic_name', $kioskConfig->clinic_name ?? '') }}"
                                                   required aria-required="true"
                                                   aria-describedby="clinic_name_help"
                                                   aria-invalid="{{ $errors->has('clinic_name') ? 'true' : 'false' }}">
                                            @if($errors->has('clinic_name'))
                                                <div class="invalid-feedback" role="alert">
                                                    {{ $errors->first('clinic_name') }}
                                                </div>
                                            @endif
                                            <small id="clinic_name_help" class="form-text text-muted">
                                                The name that will be displayed on your kiosk
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="clinic_address">Clinic Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control {{ $errors->has('clinic_address') ? 'is-invalid' : '' }}"
                                                      id="clinic_address" name="clinic_address" rows="3" required
                                                      aria-required="true" aria-describedby="clinic_address_help"
                                                      aria-invalid="{{ $errors->has('clinic_address') ? 'true' : 'false' }}">{{ old('clinic_address', $kioskConfig->clinic_address ?? '') }}</textarea>
                                            @if($errors->has('clinic_address'))
                                                <div class="invalid-feedback" role="alert">
                                                    {{ $errors->first('clinic_address') }}
                                                </div>
                                            @endif
                                            <small id="clinic_address_help" class="form-text text-muted">
                                                Full address of your clinic
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="contact_phone">Contact Phone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control {{ $errors->has('contact_phone') ? 'is-invalid' : '' }}"
                                                   id="contact_phone" name="contact_phone"
                                                   value="{{ old('contact_phone', $kioskConfig->contact_phone ?? '') }}"
                                                   required aria-required="true"
                                                   aria-describedby="contact_phone_help"
                                                   aria-invalid="{{ $errors->has('contact_phone') ? 'true' : 'false' }}"
                                                   pattern="[\+]?[1-9][\d]{0,15}">
                                            @if($errors->has('contact_phone'))
                                                <div class="invalid-feedback" role="alert">
                                                    {{ $errors->first('contact_phone') }}
                                                </div>
                                            @endif
                                            <small id="contact_phone_help" class="form-text text-muted">
                                                Phone number for patient inquiries (e.g., +1-555-123-4567)
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="kiosk_display_name">Kiosk Display Name</label>
                                            <input type="text" class="form-control {{ $errors->has('kiosk_display_name') ? 'is-invalid' : '' }}"
                                                   id="kiosk_display_name" name="kiosk_display_name"
                                                   value="{{ old('kiosk_display_name', $kioskConfig->kiosk_display_name ?? 'Welcome to Our Clinic') }}"
                                                   placeholder="Welcome message for your kiosk"
                                                   aria-describedby="kiosk_display_name_help"
                                                   aria-invalid="{{ $errors->has('kiosk_display_name') ? 'true' : 'false' }}">
                                            @if($errors->has('kiosk_display_name'))
                                                <div class="invalid-feedback" role="alert">
                                                    {{ $errors->first('kiosk_display_name') }}
                                                </div>
                                            @endif
                                            <small id="kiosk_display_name_help" class="form-text text-muted">
                                                Custom welcome message displayed on kiosk
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kiosk Settings -->
                            <div class="col-md-6">
                                <div class="card card-success">
                                    <div class="card-header">
                                        <h3 class="card-title">Kiosk Settings</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="primary_color">Primary Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control {{ $errors->has('primary_color') ? 'is-invalid' : '' }}"
                                                       id="primary_color" name="primary_color"
                                                       value="{{ old('primary_color', $kioskConfig->primary_color ?? '#2563eb') }}"
                                                       aria-describedby="primary_color_help"
                                                       aria-invalid="{{ $errors->has('primary_color') ? 'true' : 'false' }}">
                                                <div class="input-group-append">
                                                    <input type="text" class="form-control" id="primary_color_text"
                                                           value="{{ old('primary_color', $kioskConfig->primary_color ?? '#2563eb') }}"
                                                           readonly aria-label="Primary color hex value">
                                                </div>
                                            </div>
                                            @if($errors->has('primary_color'))
                                                <div class="invalid-feedback" role="alert">
                                                    {{ $errors->first('primary_color') }}
                                                </div>
                                            @endif
                                            <small id="primary_color_help" class="form-text text-muted">
                                                Main theme color for your kiosk interface
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="secondary_color">Secondary Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control {{ $errors->has('secondary_color') ? 'is-invalid' : '' }}"
                                                       id="secondary_color" name="secondary_color"
                                                       value="{{ old('secondary_color', $kioskConfig->secondary_color ?? '#6b7280') }}"
                                                       aria-describedby="secondary_color_help"
                                                       aria-invalid="{{ $errors->has('secondary_color') ? 'true' : 'false' }}">
                                                <div class="input-group-append">
                                                    <input type="text" class="form-control" id="secondary_color_text"
                                                           value="{{ old('secondary_color', $kioskConfig->secondary_color ?? '#6b7280') }}"
                                                           readonly aria-label="Secondary color hex value">
                                                </div>
                                            </div>
                                            @if($errors->has('secondary_color'))
                                                <div class="invalid-feedback" role="alert">
                                                    {{ $errors->first('secondary_color') }}
                                                </div>
                                            @endif
                                            <small id="secondary_color_help" class="form-text text-muted">
                                                Accent color for buttons and highlights
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="hidden" name="auto_approve_appointments" value="0">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="auto_approve_appointments" name="auto_approve_appointments" value="1"
                                                       {{ old('auto_approve_appointments', $kioskConfig->auto_approve_appointments ?? false) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="auto_approve_appointments">
                                                    Auto-approve Appointments
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Automatically confirm appointments made through kiosk
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="hidden" name="require_payment_upfront" value="0">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="require_payment_upfront" name="require_payment_upfront" value="1"
                                                       {{ old('require_payment_upfront', $kioskConfig->require_payment_upfront ?? false) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="require_payment_upfront">
                                                    Require Payment Upfront
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Require payment at time of appointment booking
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="hidden" name="voice_instructions_enabled" value="0">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="voice_instructions_enabled" name="voice_instructions_enabled" value="1"
                                                       {{ old('voice_instructions_enabled', $kioskConfig->voice_instructions_enabled ?? true) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="voice_instructions_enabled">
                                                    Voice Instructions
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Enable voice guidance for patients using the kiosk
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="hidden" name="high_contrast_mode" value="0">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="high_contrast_mode" name="high_contrast_mode" value="1"
                                                       {{ old('high_contrast_mode', $kioskConfig->high_contrast_mode ?? false) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="high_contrast_mode">
                                                    High Contrast Mode
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Enable high contrast colors for better accessibility
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Information -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">Security & Access</h3>
                                    </div>
                                    <div class="card-body">
                                        @if($kioskConfig && $kioskConfig->kiosk_token)
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Kiosk Access URL:</strong>
                                                <code style="word-break: break-all;">{{ route('kiosk.welcome') }}?token={{ $kioskConfig->kiosk_token }}&doctor={{ auth()->id() }}</code>
                                                <br>
                                                <small class="text-muted">
                                                    Share this URL with patients or print it for kiosk placement
                                                </small>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-secondary btn-block"
                                                            onclick="regenerateToken()">
                                                        <i class="fas fa-key"></i> Regenerate Access Token
                                                    </button>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-info btn-block"
                                                            onclick="generateQRCode()">
                                                        <i class="fas fa-qrcode"></i> Generate QR Code
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                After saving your configuration, you will receive a secure access URL and token for your kiosk.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="float-right">
                                            <a href="{{ route('dashboard') }}" class="btn btn-secondary mr-2">
                                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Kiosk Configuration
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Color picker synchronization
document.addEventListener('DOMContentLoaded', function() {
    // Sync color inputs with text displays
    const primaryColorInput = document.getElementById('primary_color');
    const primaryColorText = document.getElementById('primary_color_text');
    const secondaryColorInput = document.getElementById('secondary_color');
    const secondaryColorText = document.getElementById('secondary_color_text');

    if (primaryColorInput && primaryColorText) {
        primaryColorInput.addEventListener('input', function() {
            primaryColorText.value = this.value.toUpperCase();
        });
        primaryColorText.value = primaryColorInput.value.toUpperCase();
    }

    if (secondaryColorInput && secondaryColorText) {
        secondaryColorInput.addEventListener('input', function() {
            secondaryColorText.value = this.value.toUpperCase();
        });
        secondaryColorText.value = secondaryColorInput.value.toUpperCase();
    }

    // Form validation enhancement
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Remove any existing error highlights
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.style.display = 'none';
            });
        });
    }
});

function regenerateToken() {
    if (confirm('Are you sure you want to regenerate the kiosk access token? This will invalidate the current kiosk URL.')) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;

        // Show loading state
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regenerating...';

        fetch('{{ route('doctor.kiosk.regenerate-token') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.new_token) {
                showNotification('Access token regenerated successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Error regenerating token', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error regenerating token. Please try again.', 'error');
        })
        .finally(() => {
            // Restore button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
}

function generateQRCode() {
    try {
        const token = '{{ $kioskConfig->kiosk_token ?? '' }}';
        if (!token) {
            showNotification('No kiosk token available. Please save your configuration first.', 'warning');
            return;
        }

        const url = '{{ route('kiosk.welcome') }}?token=' + token + '&doctor={{ auth()->id() }}';
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;

        // Open QR code in new window with proper error handling
        const qrWindow = window.open(qrUrl, '_blank', 'width=350,height=350');
        if (!qrWindow) {
            showNotification('Please allow popups for this site to view the QR code.', 'warning');
        }
    } catch (error) {
        console.error('Error generating QR code:', error);
        showNotification('Error generating QR code. Please try again.', 'error');
    }
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.custom-notification').forEach(el => el.remove());

    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible custom-notification`;
    notification.setAttribute('role', 'alert');
    notification.setAttribute('aria-live', 'assertive');
    notification.innerHTML = `
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}" aria-hidden="true"></i>
        ${message}
    `;

    // Insert at top of page content
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(notification, container.firstChild);
    }

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            $(notification).alert('close');
        }
    }, 5000);
}
</script>
@endsection
