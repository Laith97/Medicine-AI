@extends('layouts.patient')

@section('title', 'Waitlist Preferences')

@section('styles')
<style>
.preference-card {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    background: white;
    transition: all 0.3s ease;
}

.preference-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.preference-header {
    display: flex;
    justify-content-between align-items-center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.doctor-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    margin-right: 1rem;
}

.preference-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-active {
    background-color: #dcfce7;
    color: #166534;
}

.badge-inactive {
    background-color: #f3f4f6;
    color: #6b7280;
}

.preference-section {
    margin-bottom: 1.5rem;
}

.preference-section:last-child {
    margin-bottom: 0;
}

.section-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
}

.section-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.5rem;
    font-size: 0.75rem;
}

.chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.preference-chip {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 1.5rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.preference-chip:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.preference-chip.selected {
    border-color: #3b82f6;
    background-color: #3b82f6;
    color: white;
}

.time-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 0.75rem;
}

.time-option {
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.time-option:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.time-option.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
    color: #3b82f6;
    font-weight: 600;
}

.analytics-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.analytics-item {
    text-align: center;
    padding: 1rem;
}

.analytics-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.analytics-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.suggestion-box {
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.suggestion-title {
    font-weight: 600;
    color: #0c4a6e;
    margin-bottom: 0.5rem;
}

.auto-accept-toggle {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
}

.notification-settings {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.notification-option {
    flex: 1;
    min-width: 120px;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.notification-option:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.notification-option.selected {
    border-color: #3b82f6;
    background-color: #3b82f6;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #d1d5db;
}
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Waitlist Preferences</h2>
                    <p class="text-muted mb-0">Manage your appointment preferences for better matches</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openNewPreferenceModal()">
                        <i class="fas fa-plus me-2"></i>Add New Preference
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="analytics-card">
                <div class="analytics-item">
                    <div class="analytics-number">{{ $preferences->count() }}</div>
                    <div class="analytics-label">Active Preferences</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <div class="analytics-item">
                    <div class="analytics-number">{{ $analytics['doctors_with_preferences'] }}</div>
                    <div class="analytics-label">Doctors Configured</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <div class="analytics-item">
                    <div class="analytics-number">{{ $analytics['average_auto_accept_threshold'] ?? 0 }}</div>
                    <div class="analytics-label">Avg Auto-Accept Days</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <div class="analytics-item">
                    <div class="analytics-number">{{ count($analytics['most_common_time_preferences']) }}</div>
                    <div class="analytics-label">Time Preferences</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Preferences List -->
        <div class="col-lg-8">
            @if($preferences->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-cog"></i>
                    <h4>No Preferences Set</h4>
                    <p>Set up your preferences to get better appointment slot recommendations.</p>
                    <button class="btn btn-primary" onclick="openNewPreferenceModal()">
                        <i class="fas fa-plus me-2"></i>Add Your First Preference
                    </button>
                </div>
            @else
                @foreach($preferences as $preference)
                    <div class="preference-card" data-preference-id="{{ $preference->id }}">
                        <div class="preference-header">
                            <div class="d-flex align-items-center">
                                <div class="doctor-avatar">
                                    {{ $preference->doctor->user->name[0] }}
                                </div>
                                <div>
                                    <h5 class="mb-0">{{ $preference->doctor->user->name }}</h5>
                                    <p class="text-muted mb-0">{{ $preference->doctor->specialty }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="preference-badge badge-active">Active</span>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="editPreference({{ $preference->id }})">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="duplicatePreference({{ $preference->id }})">
                                            <i class="fas fa-copy me-2"></i>Duplicate
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deletePreference({{ $preference->id }})">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Time Preferences -->
                            @if($preference->preferred_times)
                                <div class="col-md-6">
                                    <div class="preference-section">
                                        <div class="section-title">
                                            <div class="section-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            Preferred Times
                                        </div>
                                        <div class="chip-group">
                                            @foreach($preference->preferred_times as $time)
                                                <span class="preference-chip">{{ ucfirst($time) }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Day Preferences -->
                            @if($preference->preferred_days)
                                <div class="col-md-6">
                                    <div class="preference-section">
                                        <div class="section-title">
                                            <div class="section-icon">
                                                <i class="fas fa-calendar"></i>
                                            </div>
                                            Preferred Days
                                        </div>
                                        <div class="chip-group">
                                            @foreach($preference->preferred_days as $day)
                                                <span class="preference-chip">{{ ucfirst($day) }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Auto Accept Settings -->
                            @if($preference->auto_accept_threshold)
                                <div class="col-md-6">
                                    <div class="preference-section">
                                        <div class="section-title">
                                            <div class="section-icon">
                                                <i class="fas fa-magic"></i>
                                            </div>
                                            Auto-Accept
                                        </div>
                                        <p class="text-muted mb-0">
                                            Automatically accept appointments within {{ $preference->auto_accept_threshold }} days
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Notification Settings -->
                            @if($preference->notification_settings)
                                <div class="col-md-6">
                                    <div class="preference-section">
                                        <div class="section-title">
                                            <div class="section-icon">
                                                <i class="fas fa-bell"></i>
                                            </div>
                                            Notifications
                                        </div>
                                        <div class="chip-group">
                                            @foreach($preference->notification_settings as $type => $enabled)
                                                @if($enabled)
                                                    <span class="preference-chip">{{ ucfirst($type) }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- AI Suggestions -->
            @if(!empty($suggestedPreferences))
                <div class="suggestion-box">
                    <div class="suggestion-title">
                        <i class="fas fa-lightbulb me-2"></i>AI Suggestions
                    </div>
                    <p class="mb-2">Based on your appointment history:</p>
                    <div class="row">
                        @if(isset($suggestedPreferences['preferred_times']) && !empty($suggestedPreferences['preferred_times']))
                            <div class="col-12 mb-2">
                                <small><strong>Recommended Times:</strong>
                                    {{ implode(', ', $suggestedPreferences['preferred_times']) }}
                                </small>
                            </div>
                        @endif
                        @if(isset($suggestedPreferences['preferred_days']) && !empty($suggestedPreferences['preferred_days']))
                            <div class="col-12 mb-2">
                                <small><strong>Recommended Days:</strong>
                                    {{ implode(', ', $suggestedPreferences['preferred_days']) }}
                                </small>
                            </div>
                        @endif
                    </div>
                    <button class="btn btn-outline-primary btn-sm" onclick="applyAISuggestions()">
                        <i class="fas fa-magic me-2"></i>Apply Suggestions
                    </button>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="openNewPreferenceModal()">
                            <i class="fas fa-plus me-2"></i>Add New Preference
                        </button>
                        <button class="btn btn-outline-secondary" onclick="bulkUpdatePreferences()">
                            <i class="fas fa-cogs me-2"></i>Bulk Update
                        </button>
                        <button class="btn btn-outline-info" onclick="exportPreferences()">
                            <i class="fas fa-download me-2"></i>Export Preferences
                        </button>
                        <button class="btn btn-outline-warning" onclick="resetAllPreferences()">
                            <i class="fas fa-undo me-2"></i>Reset All
                        </button>
                    </div>
                </div>
            </div>

            <!-- Most Common Preferences -->
            @if(!empty($analytics['most_common_time_preferences']))
                <div class="card mt-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Popular Preferences</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted">Time Preferences</h6>
                            @foreach($analytics['most_common_time_preferences'] as $time => $count)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>{{ ucfirst($time) }}</span>
                                    <span class="badge bg-secondary">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($analytics['most_common_day_preferences']))
                            <div>
                                <h6 class="text-muted">Day Preferences</h6>
                                @foreach($analytics['most_common_day_preferences'] as $day => $count)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>{{ ucfirst($day) }}</span>
                                        <span class="badge bg-secondary">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- New Preference Modal -->
<div class="modal fade" id="newPreferenceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Preference</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="preferenceForm">
                    @csrf
                    <input type="hidden" id="preferenceId" name="preference_id">

                    <!-- Doctor Selection -->
                    <div class="mb-3">
                        <label for="doctorSelect" class="form-label">Select Doctor</label>
                        <select class="form-select" id="doctorSelect" name="doctor_id" required>
                            <option value="">Choose a doctor...</option>
                            @foreach(\App\Models\Doctor::with('user')->get() as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->user->name }} - {{ $doctor->specialty }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Time Preferences -->
                    <div class="mb-3">
                        <label class="form-label">Preferred Times</label>
                        <div class="time-grid">
                            <div class="time-option" onclick="toggleTimeSelection(this)" data-value="morning">
                                <i class="fas fa-sun mb-2 d-block"></i>Morning<br><small>6AM - 12PM</small>
                            </div>
                            <div class="time-option" onclick="toggleTimeSelection(this)" data-value="afternoon">
                                <i class="fas fa-cloud-sun mb-2 d-block"></i>Afternoon<br><small>12PM - 5PM</small>
                            </div>
                            <div class="time-option" onclick="toggleTimeSelection(this)" data-value="evening">
                                <i class="fas fa-moon mb-2 d-block"></i>Evening<br><small>5PM - 10PM</small>
                            </div>
                        </div>
                        <input type="hidden" id="preferredTimes" name="preferred_times[]">
                    </div>

                    <!-- Day Preferences -->
                    <div class="mb-3">
                        <label class="form-label">Preferred Days</label>
                        <div class="chip-group">
                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                <div class="preference-chip" onclick="toggleDaySelection(this)" data-value="{{ $day }}">
                                    {{ ucfirst($day) }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" id="preferredDays" name="preferred_days[]">
                    </div>

                    <!-- Auto Accept -->
                    <div class="auto-accept-toggle">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="autoAcceptToggle" name="auto_accept_enabled">
                            <label class="form-check-label" for="autoAcceptToggle">
                                Enable Auto-Accept Appointments
                            </label>
                        </div>
                        <div class="row" id="autoAcceptSettings" style="display: none;">
                            <div class="col-md-6">
                                <label for="autoAcceptDays" class="form-label">Auto-Accept Threshold (Days)</label>
                                <select class="form-select" id="autoAcceptDays" name="auto_accept_threshold">
                                    <option value="1">1 day</option>
                                    <option value="3">3 days</option>
                                    <option value="7" selected>1 week</option>
                                    <option value="14">2 weeks</option>
                                    <option value="30">1 month</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="mb-3">
                        <label class="form-label">Notification Preferences</label>
                        <div class="notification-settings">
                            <div class="notification-option selected" onclick="toggleNotification(this)" data-value="email">
                                <i class="fas fa-envelope mb-2 d-block"></i>Email
                            </div>
                            <div class="notification-option" onclick="toggleNotification(this)" data-value="sms">
                                <i class="fas fa-sms mb-2 d-block"></i>SMS
                            </div>
                            <div class="notification-option" onclick="toggleNotification(this)" data-value="push">
                                <i class="fas fa-bell mb-2 d-block"></i>Push
                            </div>
                        </div>
                        <input type="hidden" id="notificationSettings" name="notification_settings">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePreference()">Save Preference</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let selectedTimes = [];
let selectedDays = [];
let selectedNotifications = ['email'];

function openNewPreferenceModal() {
    // Reset form
    document.getElementById('preferenceForm').reset();
    document.getElementById('preferenceId').value = '';
    selectedTimes = [];
    selectedDays = [];
    selectedNotifications = ['email'];
    updateHiddenInputs();

    // Clear selections
    document.querySelectorAll('.time-option').forEach(option => option.classList.remove('selected'));
    document.querySelectorAll('.preference-chip').forEach(chip => chip.classList.remove('selected'));
    document.querySelectorAll('.notification-option').forEach(option => option.classList.remove('selected'));
    document.querySelector('.notification-option[data-value="email"]').classList.add('selected');

    // Hide auto-accept settings
    document.getElementById('autoAcceptSettings').style.display = 'none';

    // Show modal
    new bootstrap.Modal(document.getElementById('newPreferenceModal')).show();
}

function toggleTimeSelection(element) {
    const value = element.dataset.value;
    element.classList.toggle('selected');

    if (element.classList.contains('selected')) {
        if (!selectedTimes.includes(value)) {
            selectedTimes.push(value);
        }
    } else {
        selectedTimes = selectedTimes.filter(t => t !== value);
    }

    updateHiddenInputs();
}

function toggleDaySelection(element) {
    const value = element.dataset.value;
    element.classList.toggle('selected');

    if (element.classList.contains('selected')) {
        if (!selectedDays.includes(value)) {
            selectedDays.push(value);
        }
    } else {
        selectedDays = selectedDays.filter(d => d !== value);
    }

    updateHiddenInputs();
}

function toggleNotification(element) {
    const value = element.dataset.value;

    if (element.classList.contains('selected')) {
        element.classList.remove('selected');
        selectedNotifications = selectedNotifications.filter(n => n !== value);
    } else {
        element.classList.add('selected');
        selectedNotifications.push(value);
    }

    updateHiddenInputs();
}

function updateHiddenInputs() {
    document.getElementById('preferredTimes').value = JSON.stringify(selectedTimes);
    document.getElementById('preferredDays').value = JSON.stringify(selectedDays);
    document.getElementById('notificationSettings').value = JSON.stringify(selectedNotifications);
}

// Auto-accept toggle
document.getElementById('autoAcceptToggle').addEventListener('change', function() {
    document.getElementById('autoAcceptSettings').style.display = this.checked ? 'block' : 'none';
});

function savePreference() {
    const form = document.getElementById('preferenceForm');
    const formData = new FormData(form);

    // Convert arrays
    formData.set('preferred_times', JSON.stringify(selectedTimes));
    formData.set('preferred_days', JSON.stringify(selectedDays));
    formData.set('notification_settings', JSON.stringify(selectedNotifications));

    fetch('/api/waitlist/preferences', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.preference) {
            location.reload();
        } else {
            alert('Error saving preference: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('An error occurred while saving the preference.');
    });
}

function editPreference(preferenceId) {
    // This would load the preference data and populate the modal
    // console.log('Editing preference:', preferenceId);
    // Implementation would fetch preference data via AJAX
}

function deletePreference(preferenceId) {
    if (confirm('Are you sure you want to delete this preference?')) {
        fetch(`/api/waitlist/preferences/${preferenceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                location.reload();
            }
        });
    }
}

function duplicatePreference(preferenceId) {
    // Implementation for duplicating a preference
    // console.log('Duplicating preference:', preferenceId);
}

function applyAISuggestions() {
    // Implementation for applying AI suggestions
    // console.log('Applying AI suggestions');
}

function bulkUpdatePreferences() {
    // Implementation for bulk updating preferences
    // console.log('Bulk update preferences');
}

function exportPreferences() {
    window.open('/api/waitlist/preferences/export', '_blank');
}

function resetAllPreferences() {
    if (confirm('Are you sure you want to reset all preferences? This action cannot be undone.')) {
        fetch('/api/waitlist/preferences/reset-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                location.reload();
            }
        });
    }
}
</script>
@endsection
