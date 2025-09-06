@extends('master')

@section('title', 'Notification Settings | MedcuraAI')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-cog me-2 text-primary"></i>
                    Notification Settings
                </h2>
                <div>
                    <button class="btn btn-primary btn-sm" id="saveSettingsBtn">
                        <i class="fas fa-save me-1"></i>
                        Save Settings
                    </button>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Notifications
                    </a>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="card">
                <div class="card-body">
                    <form id="notificationSettingsForm">
                        @csrf
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <!-- Email Preferences -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                Email Notifications
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[enabled]" id="emailEnabled"
                                               value="1" {{ $settings->email_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emailEnabled">
                                            <strong>Enable Email Notifications</strong>
                                            <div class="text-muted small">Receive notifications via email</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[appointment_reminders]" id="emailAppointmentReminders"
                                               value="1" {{ $settings->email_appointment_reminders ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emailAppointmentReminders">
                                            <strong>Appointment Reminders</strong>
                                            <div class="text-muted small">Get reminded about upcoming appointments</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[diagnosis_updates]" id="emailDiagnosisUpdates"
                                               value="1" {{ $settings->email_diagnosis_updates ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emailDiagnosisUpdates">
                                            <strong>Diagnosis Updates</strong>
                                            <div class="text-muted small">Receive updates on diagnosis results</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[review_requests]" id="emailReviewRequests"
                                               value="1" {{ $settings->email_review_requests ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emailReviewRequests">
                                            <strong>Review Requests</strong>
                                            <div class="text-muted small">Get requests to leave reviews</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[system_alerts]" id="emailSystemAlerts"
                                               value="1" {{ $settings->email_system_alerts ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emailSystemAlerts">
                                            <strong>System Alerts</strong>
                                            <div class="text-muted small">Important system notifications</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications[marketing]" id="emailMarketing"
                                               value="1" {{ $settings->email_marketing ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emailMarketing">
                                            <strong>Marketing & Updates</strong>
                                            <div class="text-muted small">Product updates and promotional content</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Preferences -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-sms me-2 text-primary"></i>
                                SMS Notifications
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications[enabled]" id="smsEnabled"
                                               value="1" {{ $settings->sms_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="smsEnabled">
                                            <strong>Enable SMS Notifications</strong>
                                            <div class="text-muted small">Receive notifications via SMS</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications[appointment_reminders]" id="smsAppointmentReminders"
                                               value="1" {{ $settings->sms_appointment_reminders ? 'checked' : '' }}>
                                        <label class="form-check-label" for="smsAppointmentReminders">
                                            <strong>Appointment Reminders</strong>
                                            <div class="text-muted small">SMS reminders for appointments</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications[urgent_alerts]" id="smsUrgentAlerts"
                                               value="1" {{ $settings->sms_urgent_alerts ? 'checked' : '' }}>
                                        <label class="form-check-label" for="smsUrgentAlerts">
                                            <strong>Urgent Alerts</strong>
                                            <div class="text-muted small">Critical system alerts only</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- In-App Preferences -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-bell me-2 text-primary"></i>
                                In-App Notifications
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="in_app_notifications[enabled]" id="inAppEnabled"
                                               value="1" {{ $settings->in_app_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inAppEnabled">
                                            <strong>Enable In-App Notifications</strong>
                                            <div class="text-muted small">Show notifications in the app</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="in_app_notifications[sound]" id="inAppSound"
                                               value="1" {{ $settings->in_app_sound ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inAppSound">
                                            <strong>Notification Sounds</strong>
                                            <div class="text-muted small">Play sound for new notifications</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="in_app_notifications[desktop]" id="inAppDesktop"
                                               value="1" {{ $settings->in_app_desktop ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inAppDesktop">
                                            <strong>Desktop Notifications</strong>
                                            <div class="text-muted small">Show desktop notifications when browser is open</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="in_app_notifications[vibrate]" id="inAppVibrate"
                                               value="1" {{ $settings->in_app_vibrate ? 'checked' : '' }}>
                                        <label class="form-check-label" for="inAppVibrate">
                                            <strong>Vibration</strong>
                                            <div class="text-muted small">Vibrate for mobile notifications</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Frequency -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-clock me-2 text-primary"></i>
                                Notification Frequency
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="frequency" class="form-label">Email Digest Frequency</label>
                                        <select class="form-select" id="frequency" name="frequency">
                                            <option value="immediate" {{ $settings->frequency === 'immediate' ? 'selected' : '' }}>Immediate</option>
                                            <option value="hourly" {{ $settings->frequency === 'hourly' ? 'selected' : '' }}>Hourly Digest</option>
                                            <option value="daily" {{ $settings->frequency === 'daily' ? 'selected' : '' }}>Daily Digest</option>
                                            <option value="weekly" {{ $settings->frequency === 'weekly' ? 'selected' : '' }}>Weekly Digest</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quiet_hours_start" class="form-label">Quiet Hours Start</label>
                                        <input type="time" class="form-control" id="quiet_hours_start"
                                               name="quiet_hours[start]" value="{{ $settings->quiet_hours_start ?? '22:00' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quiet_hours_end" class="form-label">Quiet Hours End</label>
                                        <input type="time" class="form-control" id="quiet_hours_end"
                                               name="quiet_hours[end]" value="{{ $settings->quiet_hours_end ?? '08:00' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="respect_quiet_hours" id="respectQuietHours"
                                               value="1" {{ $settings->respect_quiet_hours ? 'checked' : '' }}>
                                        <label class="form-check-label" for="respectQuietHours">
                                            <strong>Respect Quiet Hours</strong>
                                            <div class="text-muted small">Don't send notifications during quiet hours</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Types -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-filter me-2 text-primary"></i>
                                Notification Types
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="notification_types[appointment_booked]" id="typeAppointmentBooked"
                                               value="1" {{ $settings->appointment_booked ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeAppointmentBooked">
                                            <strong>New Appointment Booked</strong>
                                            <div class="text-muted small">When someone books an appointment with you</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="notification_types[appointment_reminder]" id="typeAppointmentReminder"
                                               value="1" {{ $settings->appointment_reminder ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeAppointmentReminder">
                                            <strong>Appointment Reminders</strong>
                                            <div class="text-muted small">Reminders for upcoming appointments</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="notification_types[diagnosis_submitted]" id="typeDiagnosisSubmitted"
                                               value="1" {{ $settings->diagnosis_submitted ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeDiagnosisSubmitted">
                                            <strong>Diagnosis Submitted</strong>
                                            <div class="text-muted small">When a doctor submits a diagnosis</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="notification_types[review_submitted]" id="typeReviewSubmitted"
                                               value="1" {{ $settings->review_submitted ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeReviewSubmitted">
                                            <strong>Review Submitted</strong>
                                            <div class="text-muted small">When a patient submits a review</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="notification_types[voice_transcription_completed]" id="typeVoiceTranscriptionCompleted"
                                               value="1" {{ $settings->voice_transcription_completed ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeVoiceTranscriptionCompleted">
                                            <strong>Voice Transcription Completed</strong>
                                            <div class="text-muted small">When AI voice assistant completes transcription</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="notification_types[system_alert]" id="typeSystemAlert"
                                               value="1" {{ $settings->system_alert ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeSystemAlert">
                                            <strong>System Alerts</strong>
                                            <div class="text-muted small">Important system notifications and alerts</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="resetDefaultsBtn">
                                    <i class="fas fa-undo me-1"></i>
                                    Reset to Defaults
                                </button>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Save settings
    $('#notificationSettingsForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("notifications.settings.update") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showSuccessMessage('Settings saved successfully!');
                } else {
                    showErrorMessage('Error saving settings. Please try again.');
                }
            },
            error: function() {
                showErrorMessage('Error saving settings. Please try again.');
            }
        });
    });

    // Reset to defaults
    $('#resetDefaultsBtn').click(function() {
        if (confirm('Are you sure you want to reset all notification settings to defaults?')) {
            $.ajax({
                url: '{{ route("notifications.settings.update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reset_defaults: 1
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        showErrorMessage('Error resetting settings. Please try again.');
                    }
                },
                error: function() {
                    showErrorMessage('Error resetting settings. Please try again.');
                }
            });
        }
    });

    // Show success message
    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#saveSettingsBtn').before(alertHtml);
    }

    // Show error message
    function showErrorMessage(message) {
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#saveSettingsBtn').before(alertHtml);
    }
});
</script>
@endsection
