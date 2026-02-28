@extends('layouts.admin')

@section('title', 'Edit Kiosk - ' . $kiosk->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Kiosk: {{ $kiosk->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('kiosks.show', $kiosk) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="{{ route('kiosks.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <form id="editKioskForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Kiosk Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $kiosk->name }}" required>
                                    <small class="form-text text-muted">A descriptive name for this kiosk</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="serial_number">Serial Number</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" value="{{ $kiosk->serial_number }}" readonly>
                                    <small class="form-text text-muted">Serial number cannot be changed</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="{{ $kiosk->location }}">
                            <small class="form-text text-muted">Physical location of the kiosk</small>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" {{ $kiosk->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $kiosk->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="maintenance" {{ $kiosk->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            <small class="form-text text-muted">Current operational status</small>
                        </div>

                        <div class="form-group">
                            <label>Configuration (JSON)</label>
                            <textarea class="form-control" id="configuration" name="configuration" rows="8">{{ $kiosk->configuration ? json_encode($kiosk->configuration, JSON_PRETTY_PRINT) : '' }}</textarea>
                            <small class="form-text text-muted">JSON configuration for kiosk hardware capabilities</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Ping</label>
                                    <p class="form-control-plaintext">
                                        @if($kiosk->last_ping)
                                            {{ $kiosk->last_ping->format('M j, Y g:i A') }}
                                            <br><small class="text-muted">{{ $kiosk->last_ping->diffForHumans() }}</small>
                                        @else
                                            Never
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Created</label>
                                    <p class="form-control-plaintext">{{ $kiosk->created_at->format('M j, Y g:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Update Kiosk
                        </button>
                        <a href="{{ route('kiosks.show', $kiosk) }}" class="btn btn-secondary ml-2">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('editKioskForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

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

    fetch('/admin/kiosks/{{ $kiosk->id }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/admin/kiosks/{{ $kiosk->id }}';
        } else {
            alert('Error: ' + (data.message || 'Failed to update kiosk'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error updating kiosk');
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>
@endsection
