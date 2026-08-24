@extends('master')

@section('title', 'Notification Settings')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.form-check-input:checked{background-color:#2563eb;border-color:#2563eb}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-cog me-2"></i>Notification Settings</h2>
                    <p>Manage how and when you receive notifications</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn" id="saveSettingsBtn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-save me-1"></i>Save Settings</button>
                    <a href="{{ route('notifications.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-1"></i>Back</a>
                </div>
            </div>
        </div>

        <form id="notificationSettingsForm">
            @csrf
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-envelope"></i></div><div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Email Notifications</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Via email · digest & quiet hours below</p></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="email_notifications[enabled]" id="emailEnabled" value="1" {{ $settings->email_enabled ? 'checked' : '' }}><label class="form-check-label" for="emailEnabled" style="font-weight:600;color:#0f172a">Enable Email</label><div style="font-size:0.78rem;color:#64748b">Receive notifications via email</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="email_notifications[appointment_reminders]" id="emailAppointmentReminders" value="1" {{ $settings->email_appointment_reminders ? 'checked' : '' }}><label class="form-check-label" for="emailAppointmentReminders" style="font-weight:600;color:#0f172a">Appointment Reminders</label><div style="font-size:0.78rem;color:#64748b">Upcoming appointments</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="email_notifications[diagnosis_updates]" id="emailDiagnosisUpdates" value="1" {{ $settings->email_diagnosis_updates ? 'checked' : '' }}><label class="form-check-label" for="emailDiagnosisUpdates" style="font-weight:600;color:#0f172a">Diagnosis Updates</label><div style="font-size:0.78rem;color:#64748b">Diagnosis results</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="email_notifications[review_requests]" id="emailReviewRequests" value="1" {{ $settings->email_review_requests ? 'checked' : '' }}><label class="form-check-label" for="emailReviewRequests" style="font-weight:600;color:#0f172a">Review Requests</label><div style="font-size:0.78rem;color:#64748b">Leave reviews</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="email_notifications[system_alerts]" id="emailSystemAlerts" value="1" {{ $settings->email_system_alerts ? 'checked' : '' }}><label class="form-check-label" for="emailSystemAlerts" style="font-weight:600;color:#0f172a">System Alerts</label><div style="font-size:0.78rem;color:#64748b">Important notifications</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="email_notifications[marketing]" id="emailMarketing" value="1" {{ $settings->email_marketing ? 'checked' : '' }}><label class="form-check-label" for="emailMarketing" style="font-weight:600;color:#0f172a">Marketing & Updates</label><div style="font-size:0.78rem;color:#64748b">Product updates</div></div></div></div>
                </div>
            </div>

            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#ecfdf5!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-sms"></i></div><div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">SMS Notifications</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Via phone · urgent only</p></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sms_notifications[enabled]" id="smsEnabled" value="1" {{ $settings->sms_enabled ? 'checked' : '' }}><label class="form-check-label" for="smsEnabled" style="font-weight:600;color:#0f172a">Enable SMS</label><div style="font-size:0.78rem;color:#64748b">Via SMS</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sms_notifications[appointment_reminders]" id="smsAppointmentReminders" value="1" {{ $settings->sms_appointment_reminders ? 'checked' : '' }}><label class="form-check-label" for="smsAppointmentReminders" style="font-weight:600;color:#0f172a">Appointment Reminders</label><div style="font-size:0.78rem;color:#64748b">SMS reminders</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sms_notifications[urgent_alerts]" id="smsUrgentAlerts" value="1" {{ $settings->sms_urgent_alerts ? 'checked' : '' }}><label class="form-check-label" for="smsUrgentAlerts" style="font-weight:600;color:#0f172a">Urgent Alerts</label><div style="font-size:0.78rem;color:#64748b">Critical only</div></div></div></div>
                </div>
            </div>

            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-bell"></i></div><div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">In-App Notifications</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Inside the app · sound & desktop</p></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_app_notifications[enabled]" id="inAppEnabled" value="1" {{ $settings->in_app_enabled ? 'checked' : '' }}><label class="form-check-label" for="inAppEnabled" style="font-weight:600;color:#0f172a">Enable In-App</label><div style="font-size:0.78rem;color:#64748b">Show in app</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_app_notifications[sound]" id="inAppSound" value="1" {{ $settings->in_app_sound ? 'checked' : '' }}><label class="form-check-label" for="inAppSound" style="font-weight:600;color:#0f172a">Sounds</label><div style="font-size:0.78rem;color:#64748b">Play sound</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_app_notifications[desktop]" id="inAppDesktop" value="1" {{ $settings->in_app_desktop ? 'checked' : '' }}><label class="form-check-label" for="inAppDesktop" style="font-weight:600;color:#0f172a">Desktop</label><div style="font-size:0.78rem;color:#64748b">Browser notifications</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="in_app_notifications[vibrate]" id="inAppVibrate" value="1" {{ $settings->in_app_vibrate ? 'checked' : '' }}><label class="form-check-label" for="inAppVibrate" style="font-weight:600;color:#0f172a">Vibration</label><div style="font-size:0.78rem;color:#64748b">Mobile vibrate</div></div></div></div>
                </div>
            </div>

            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-clock"></i></div><div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Frequency & Quiet Hours</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Digest · do not disturb</p></div></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label for="frequency" class="form-label" style="font-weight:600;font-size:0.82rem;color:#1e293b">Email Digest Frequency</label><select class="form-select" id="frequency" name="frequency" style="border-radius:10px;border:1px solid #e2e8f0"><option value="immediate" {{ $settings->frequency==='immediate'?'selected':'' }}>Immediate</option><option value="hourly" {{ $settings->frequency==='hourly'?'selected':'' }}>Hourly Digest</option><option value="daily" {{ $settings->frequency==='daily'?'selected':'' }}>Daily Digest</option><option value="weekly" {{ $settings->frequency==='weekly'?'selected':'' }}>Weekly Digest</option></select></div>
                    <div class="col-md-3"><label for="quiet_hours_start" class="form-label" style="font-weight:600;font-size:0.82rem;color:#1e293b">Quiet Start</label><input type="time" class="form-control" id="quiet_hours_start" name="quiet_hours[start]" value="{{ $settings->quiet_hours_start ?? '22:00' }}" style="border-radius:10px;border:1px solid #e2e8f0"></div>
                    <div class="col-md-3"><label for="quiet_hours_end" class="form-label" style="font-weight:600;font-size:0.82rem;color:#1e293b">Quiet End</label><input type="time" class="form-control" id="quiet_hours_end" name="quiet_hours[end]" value="{{ $settings->quiet_hours_end ?? '08:00' }}" style="border-radius:10px;border:1px solid #e2e8f0"></div>
                    <div class="col-12"><div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="respect_quiet_hours" id="respectQuietHours" value="1" {{ $settings->respect_quiet_hours ? 'checked' : '' }}><label class="form-check-label" for="respectQuietHours" style="font-weight:600;color:#0f172a">Respect Quiet Hours</label><div style="font-size:0.78rem;color:#64748b">Don't send during quiet hours</div></div></div></div>
                </div>
            </div>

            <div class="table-card">
                <div class="section-head-modern">
                    <div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-filter"></i></div><div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Notification Types</h5><p style="margin:0;font-size:0.78rem;color:#64748b">What to be notified about</p></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notification_types[appointment_booked]" id="typeAppointmentBooked" value="1" {{ $settings->appointment_booked ? 'checked' : '' }}><label class="form-check-label" for="typeAppointmentBooked" style="font-weight:600;color:#0f172a">New Appointment Booked</label><div style="font-size:0.78rem;color:#64748b">Someone books with you</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notification_types[appointment_reminder]" id="typeAppointmentReminder" value="1" {{ $settings->appointment_reminder ? 'checked' : '' }}><label class="form-check-label" for="typeAppointmentReminder" style="font-weight:600;color:#0f172a">Appointment Reminders</label><div style="font-size:0.78rem;color:#64748b">Upcoming reminders</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notification_types[diagnosis_submitted]" id="typeDiagnosisSubmitted" value="1" {{ $settings->diagnosis_submitted ? 'checked' : '' }}><label class="form-check-label" for="typeDiagnosisSubmitted" style="font-weight:600;color:#0f172a">Diagnosis Submitted</label><div style="font-size:0.78rem;color:#64748b">Doctor submits diagnosis</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notification_types[review_submitted]" id="typeReviewSubmitted" value="1" {{ $settings->review_submitted ? 'checked' : '' }}><label class="form-check-label" for="typeReviewSubmitted" style="font-weight:600;color:#0f172a">Review Submitted</label><div style="font-size:0.78rem;color:#64748b">Patient submits review</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notification_types[voice_transcription_completed]" id="typeVoiceTranscriptionCompleted" value="1" {{ $settings->voice_transcription_completed ? 'checked' : '' }}><label class="form-check-label" for="typeVoiceTranscriptionCompleted" style="font-weight:600;color:#0f172a">Voice Transcription</label><div style="font-size:0.78rem;color:#64748b">AI completes transcription</div></div></div></div>
                    <div class="col-md-6"><div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notification_types[system_alert]" id="typeSystemAlert" value="1" {{ $settings->system_alert ? 'checked' : '' }}><label class="form-check-label" for="typeSystemAlert" style="font-weight:600;color:#0f172a">System Alerts</label><div style="font-size:0.78rem;color:#64748b">Important alerts</div></div></div></div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <button type="button" class="btn" id="resetDefaultsBtn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1rem;font-weight:500;font-size:0.84rem"><i class="fas fa-undo me-1"></i>Reset to Defaults</button>
                <button type="submit" class="btn" style="background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%);color:#fff;border:none;border-radius:10px;padding:0.65rem 1.4rem;font-weight:600;font-size:0.88rem;box-shadow:0 4px 14px rgba(37,99,235,0.25)"><i class="fas fa-save me-1"></i>Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#notificationSettingsForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("notifications.settings.update") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    const alertHtml = `<div class="alert alert-success alert-dismissible fade show" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px" role="alert"><i class="fas fa-check-circle me-2"></i>Settings saved successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
                    $('.dashboard-header').after(alertHtml);
                    setTimeout(()=>$('.alert-success').fadeOut(),3000);
                } else {
                    const alertHtml = `<div class="alert alert-danger alert-dismissible fade show" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px" role="alert"><i class="fas fa-exclamation-circle me-2"></i>Error saving settings.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
                    $('.dashboard-header').after(alertHtml);
                }
            },
            error: function() {
                const alertHtml = `<div class="alert alert-danger alert-dismissible fade show" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px" role="alert"><i class="fas fa-exclamation-circle me-2"></i>Error saving settings.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
                $('.dashboard-header').after(alertHtml);
            }
        });
    });
    $('#resetDefaultsBtn').click(function() {
        if (confirm('Are you sure you want to reset all notification settings to defaults?')) {
            $.ajax({
                url: '{{ route("notifications.settings.update") }}',
                method: 'POST',
                data: {_token: '{{ csrf_token() }}', reset_defaults: 1},
                success: function(response) { if (response.success) location.reload(); else alert('Error resetting'); },
                error: function() { alert('Error resetting'); }
            });
        }
    });
});
</script>
@endsection
