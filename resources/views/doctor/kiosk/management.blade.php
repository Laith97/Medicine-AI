@extends('master')

@section('title', 'Kiosk Management - Doctor Dashboard')

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
                        <i class="fas fa-cogs mr-2"></i>
                        Kiosk Management Dashboard
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('doctor.kiosk.setup') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-cog"></i> Setup Configuration
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

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-exclamation-triangle"></i> {{ session('warning') }}
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
                    @endif

                    <!-- Kiosk Status Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $stats['today_sessions'] ?? 0 }}</h3>
                                    <p>Sessions Today</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $stats['total_sessions'] ?? 0 }}</h3>
                                    <p>Total Sessions</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['appointments_created'] ?? 0 }}</h3>
                                    <p>Appointments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $stats['payments_processed'] ?? 0 }}</h3>
                                    <p>Payments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Kiosk Configuration Summary -->
                    @if($kioskConfig)
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Kiosk Configuration</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Clinic Name:</strong> {{ $kioskConfig->clinic_name }}</p>
                                            <p><strong>Clinic Address:</strong> {{ $kioskConfig->clinic_address }}</p>
                                            <p><strong>Contact Phone:</strong> {{ $kioskConfig->contact_phone }}</p>
                                            <p><strong>Kiosk Display Name:</strong> {{ $kioskConfig->kiosk_display_name }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Primary Color:</strong> <span style="display: inline-block; width: 20px; height: 20px; background-color: {{ $kioskConfig->primary_color }}; border: 1px solid #ccc; vertical-align: middle;"></span> {{ $kioskConfig->primary_color }}</p>
                                            <p><strong>Secondary Color:</strong> <span style="display: inline-block; width: 20px; height: 20px; background-color: {{ $kioskConfig->secondary_color }}; border: 1px solid #ccc; vertical-align: middle;"></span> {{ $kioskConfig->secondary_color }}</p>
                                            <p><strong>Auto-approve Appointments:</strong> {{ $kioskConfig->auto_approve_appointments ? 'Yes' : 'No' }}</p>
                                            <p><strong>Require Payment Upfront:</strong> {{ $kioskConfig->require_payment_upfront ? 'Yes' : 'No' }}</p>
                                        </div>
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
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Kiosk Access URL:</strong>
                                        <code style="word-break: break-all;">{{ route('kiosk.welcome') }}?token={{ $kioskConfig->kiosk_token }}&doctor={{ $kioskConfig->doctor_id }}</code>
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
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Kiosk not configured yet.</strong> Please <a href="{{ route('doctor.kiosk.setup') }}">set up your kiosk configuration</a> first.
                    </div>
                    @endif

                    <!-- Recent Kiosk Sessions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Kiosk Sessions</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Kiosk ID</th>
                                                    <th>Started At</th>
                                                    <th>Ended At</th>
                                                    <th>Duration</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if($recentSessions && $recentSessions->count() > 0)
                                                    @foreach($recentSessions as $session)
                                                        <tr>
                                                            <td>{{ $session->kiosk_id }}</td>
                                                            <td>{{ $session->created_at ? \Carbon\Carbon::parse($session->created_at)->format('M d, Y g:i A') : 'N/A' }}</td>
                                                            <td>{{ $session->ended_at ? \Carbon\Carbon::parse($session->ended_at)->format('M d, Y g:i A') : 'N/A' }}</td>
                                                            <td>
                                                                @if($session->ended_at && $session->created_at)
                                                                    {{ \Carbon\Carbon::parse($session->created_at)->diffInMinutes(\Carbon\Carbon::parse($session->ended_at)) }} min
                                                                @else
                                                                    Active
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-{{ $session->ended_at ? 'success' : 'warning' }}">
                                                                    {{ $session->ended_at ? 'Completed' : 'Active' }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <a href="#" class="btn btn-xs btn-info" title="View Session Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="6" class="text-center">No recent kiosk sessions</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
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
function regenerateToken() {
    if (confirm('Are you sure you want to regenerate the kiosk access token? This will invalidate the current kiosk URL.')) {
        const button = event.target;
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
            // console.error('Error:', error);
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
        const token = '{{ $kioskConfig->kiosk_token ?? "" }}';
        if (!token) {
            showNotification('No kiosk token available. Please save your configuration first.', 'warning');
            return;
        }

        const url = '{{ route('kiosk.welcome') }}?token=' + token + '&doctor={{ $kioskConfig->doctor_id }}';
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;

        // Open QR code in new window with proper error handling
        const qrWindow = window.open(qrUrl, '_blank', 'width=350,height=350');
        if (!qrWindow) {
            showNotification('Please allow popups for this site to view the QR code.', 'warning');
        }
    } catch (error) {
        // console.error('Error generating QR code:', error);
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