@extends('layouts.admin')

@section('title', 'Add New Kiosk')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Kiosk</h3>
                    <div class="card-tools">
                        <a href="{{ route('kiosks.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <form id="createKioskForm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Kiosk Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <small class="form-text text-muted">A descriptive name for this kiosk (e.g., "Main Lobby Kiosk 1")</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="serial_number">Serial Number *</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                                    <small class="form-text text-muted">Unique hardware serial number</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                            <small class="form-text text-muted">Physical location of the kiosk (e.g., "Hospital Lobby, Floor 1")</small>
                        </div>

                        <div class="form-group">
                            <label for="status">Initial Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="inactive">Inactive</option>
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            <small class="form-text text-muted">Kiosk will be inactive until it registers itself</small>
                        </div>

                        <div class="form-group">
                            <label>Configuration (JSON)</label>
                            <textarea class="form-control" id="configuration" name="configuration" rows="6" placeholder='{
  "screen_resolution": "1920x1080",
  "touch_enabled": true,
  "printer_connected": false,
  "card_reader": true,
  "biometric_scanner": false,
  "voice_assistant": true,
  "high_contrast_mode": false,
  "auto_logout_minutes": 30
}'></textarea>
                            <small class="form-text text-muted">Optional JSON configuration for kiosk hardware capabilities</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Create Kiosk
                        </button>
                        <a href="{{ route('kiosks.index') }}" class="btn btn-secondary ml-2">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createKioskForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    // Validate JSON configuration if provided
    const configTextarea = document.getElementById('configuration');
    if (configTextarea.value.trim()) {
        try {
            JSON.parse(configTextarea.value);
        } catch (error) {
            alert('Invalid JSON in configuration field');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
    }

    const formData = new FormData(this);

    fetch('/admin/kiosks', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/admin/kiosks';
        } else {
            alert('Error: ' + (data.message || 'Failed to create kiosk'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error creating kiosk');
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Auto-generate serial number suggestion
document.getElementById('name').addEventListener('input', function() {
    const name = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
    const serialInput = document.getElementById('serial_number');
    if (!serialInput.value) {
        serialInput.value = 'KIOSK-' + name.toUpperCase() + '-' + Date.now().toString().slice(-6);
    }
});
</script>
@endsection
