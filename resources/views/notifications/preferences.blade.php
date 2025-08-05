@extends('master')

@section('title', 'Notification Preferences')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-bell me-2"></i>
                        Notification Preferences
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Customize which notifications you receive and how you want to receive them.
                    </p>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('notification.preferences.update') }}">
                        @csrf

                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-calendar-event me-2 text-primary"></i>
                                Appointment Notifications
                            </h5>

                            <div class="notification-category">
                                @foreach ($notificationTypes->where('category', 'appointments') as $type)
                                    @php
                                        $preference = $userPreferences->where('notification_type_id', $type->id)->first();
                                        $enabled = $preference ? $preference->enabled : $type->default_enabled;
                                        $channels = $preference ? json_decode($preference->channels) : json_decode($type->default_channels);
                                    @endphp

                                    <div class="notification-item mb-3 p-3 border rounded">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="preferences[{{ $type->id }}][enabled]"
                                                   id="pref-{{ $type->id }}"
                                                   value="1"
                                                   {{ $enabled ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pref-{{ $type->id }}">
                                                {{ $type->name }}
                                            </label>
                                        </div>
                                        <p class="text-muted mb-2 small">{{ $type->description }}</p>

                                        <div class="channels-options">
                                            <label class="form-label small fw-semibold">Delivery Channels:</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-database"
                                                       value="database"
                                                       {{ in_array('database', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-database">
                                                    In-App
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-mail"
                                                       value="mail"
                                                       {{ in_array('mail', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-mail">
                                                    Email
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-sms"
                                                       value="sms"
                                                       {{ in_array('sms', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-sms">
                                                    SMS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-file-medical me-2 text-success"></i>
                                Diagnosis Notifications
                            </h5>

                            <div class="notification-category">
                                @foreach ($notificationTypes->where('category', 'diagnosis') as $type)
                                    @php
                                        $preference = $userPreferences->where('notification_type_id', $type->id)->first();
                                        $enabled = $preference ? $preference->enabled : $type->default_enabled;
                                        $channels = $preference ? json_decode($preference->channels) : json_decode($type->default_channels);
                                    @endphp

                                    <div class="notification-item mb-3 p-3 border rounded">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="preferences[{{ $type->id }}][enabled]"
                                                   id="pref-{{ $type->id }}"
                                                   value="1"
                                                   {{ $enabled ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pref-{{ $type->id }}">
                                                {{ $type->name }}
                                            </label>
                                        </div>
                                        <p class="text-muted mb-2 small">{{ $type->description }}</p>

                                        <div class="channels-options">
                                            <label class="form-label small fw-semibold">Delivery Channels:</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-database"
                                                       value="database"
                                                       {{ in_array('database', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-database">
                                                    In-App
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-mail"
                                                       value="mail"
                                                       {{ in_array('mail', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-mail">
                                                    Email
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-sms"
                                                       value="sms"
                                                       {{ in_array('sms', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-sms">
                                                    SMS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-star me-2 text-warning"></i>
                                Review Notifications
                            </h5>

                            <div class="notification-category">
                                @foreach ($notificationTypes->where('category', 'reviews') as $type)
                                    @php
                                        $preference = $userPreferences->where('notification_type_id', $type->id)->first();
                                        $enabled = $preference ? $preference->enabled : $type->default_enabled;
                                        $channels = $preference ? json_decode($preference->channels) : json_decode($type->default_channels);
                                    @endphp

                                    <div class="notification-item mb-3 p-3 border rounded">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="preferences[{{ $type->id }}][enabled]"
                                                   id="pref-{{ $type->id }}"
                                                   value="1"
                                                   {{ $enabled ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pref-{{ $type->id }}">
                                                {{ $type->name }}
                                            </label>
                                        </div>
                                        <p class="text-muted mb-2 small">{{ $type->description }}</p>

                                        <div class="channels-options">
                                            <label class="form-label small fw-semibold">Delivery Channels:</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-database"
                                                       value="database"
                                                       {{ in_array('database', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-database">
                                                    In-App
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-mail"
                                                       value="mail"
                                                       {{ in_array('mail', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-mail">
                                                    Email
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-sms"
                                                       value="sms"
                                                       {{ in_array('sms', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-sms">
                                                    SMS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-microphone me-2 text-info"></i>
                                Voice Assistant Notifications
                            </h5>

                            <div class="notification-category">
                                @foreach ($notificationTypes->where('category', 'voice_assistant') as $type)
                                    @php
                                        $preference = $userPreferences->where('notification_type_id', $type->id)->first();
                                        $enabled = $preference ? $preference->enabled : $type->default_enabled;
                                        $channels = $preference ? json_decode($preference->channels) : json_decode($type->default_channels);
                                    @endphp

                                    <div class="notification-item mb-3 p-3 border rounded">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="preferences[{{ $type->id }}][enabled]"
                                                   id="pref-{{ $type->id }}"
                                                   value="1"
                                                   {{ $enabled ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pref-{{ $type->id }}">
                                                {{ $type->name }}
                                            </label>
                                        </div>
                                        <p class="text-muted mb-2 small">{{ $type->description }}</p>

                                        <div class="channels-options">
                                            <label class="form-label small fw-semibold">Delivery Channels:</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-database"
                                                       value="database"
                                                       {{ in_array('database', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-database">
                                                    In-App
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-mail"
                                                       value="mail"
                                                       {{ in_array('mail', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-mail">
                                                    Email
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-sms"
                                                       value="sms"
                                                       {{ in_array('sms', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-sms">
                                                    SMS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-exclamation-triangle me-2 text-danger"></i>
                                System Notifications
                            </h5>

                            <div class="notification-category">
                                @foreach ($notificationTypes->where('category', 'system') as $type)
                                    @php
                                        $preference = $userPreferences->where('notification_type_id', $type->id)->first();
                                        $enabled = $preference ? $preference->enabled : $type->default_enabled;
                                        $channels = $preference ? json_decode($preference->channels) : json_decode($type->default_channels);
                                    @endphp

                                    <div class="notification-item mb-3 p-3 border rounded">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                   name="preferences[{{ $type->id }}][enabled]"
                                                   id="pref-{{ $type->id }}"
                                                   value="1"
                                                   {{ $enabled ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="pref-{{ $type->id }}">
                                                {{ $type->name }}
                                            </label>
                                        </div>
                                        <p class="text-muted mb-2 small">{{ $type->description }}</p>

                                        <div class="channels-options">
                                            <label class="form-label small fw-semibold">Delivery Channels:</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-database"
                                                       value="database"
                                                       {{ in_array('database', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-database">
                                                    In-App
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-mail"
                                                       value="mail"
                                                       {{ in_array('mail', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-mail">
                                                    Email
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       name="preferences[{{ $type->id }}][channels][]"
                                                       id="channel-{{ $type->id }}-sms"
                                                       value="sms"
                                                       {{ in_array('sms', $channels) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="channel-{{ $type->id }}-sms">
                                                    SMS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAll(true)">
                                    <i class="bi bi-check-all me-1"></i>
                                    Enable All
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAll(false)">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Disable All
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function selectAll(enable) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name*="[enabled]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = enable;
    });

    // Also select all channel checkboxes
    const channelCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="[channels]"]');
    channelCheckboxes.forEach(checkbox => {
        checkbox.checked = enable;
    });
}
</script>
@endpush
@endsection
