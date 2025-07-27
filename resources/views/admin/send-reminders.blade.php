@extends('layouts.admin')

@section('title', 'Send Manual Reminders')

@push('styles')
<style>
    .reminder-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 1.5rem;
    }

    .reminder-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }

    .user-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        background: #f8f9fa;
    }

    .user-item {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .user-item:last-child {
        border-bottom: none;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
    }

    .status-grace {
        background: rgba(255, 193, 7, 0.2);
        color: #856404;
    }

    .status-warning {
        background: rgba(220, 53, 69, 0.2);
        color: #721c24;
    }

    .status-overdue {
        background: rgba(255, 108, 55, 0.2);
        color: #8b2635;
    }

    .form-section {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        background: #f8f9fa;
    }

    .btn-send-reminders {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-send-reminders:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container">
    <!-- Header -->
    <div class="reminder-header">
        <h1 class="h2 mb-2 text-white">Send Manual Reminders</h1>
        <p class="mb-0 opacity-75">Send email and SMS reminders to users manually</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Validation Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('detailed_errors'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Detailed Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('detailed_errors') as $detailedError)
                    <li><small>{{ $detailedError }}</small></li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.send-reminders') }}" method="POST" id="reminderForm">
        @csrf

        <!-- Reminder Type Selection -->
        <div class="reminder-card">
            <h5 class="mb-3"><i class="fas fa-bell me-2"></i>Reminder Type</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reminder_type" id="grace_period" value="grace_period" checked>
                        <label class="form-check-label" for="grace_period">
                            <strong>Grace Period Reminders</strong>
                            <br><small class="text-muted">Users whose subscription expired but are still in grace period</small>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reminder_type" id="warning_period" value="warning_period">
                        <label class="form-check-label" for="warning_period">
                            <strong>Warning Period Reminders</strong>
                            <br><small class="text-muted">Users in final warning period before restriction</small>
                        </label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reminder_type" id="overdue" value="overdue">
                        <label class="form-check-label" for="overdue">
                            <strong>Overdue Invoice Reminders</strong>
                            <br><small class="text-muted">Users with overdue invoices past grace period</small>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reminder_type" id="all" value="all">
                        <label class="form-check-label" for="all">
                            <strong>All Reminder Types</strong>
                            <br><small class="text-muted">Send all applicable reminders to all eligible users</small>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Selection -->
        <div class="reminder-card">
            <h5 class="mb-3"><i class="fas fa-users me-2"></i>User Selection</h5>
            
            <!-- Grace Period Users -->
            <div id="grace-users" class="user-section">
                <h6 class="text-warning"><i class="fas fa-clock me-2"></i>Grace Period Users ({{ $gracePeriodUsers->count() }})</h6>
                @if($gracePeriodUsers->count() > 0)
                    <div class="user-list">
                        @foreach($gracePeriodUsers as $user)
                            <div class="user-item">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="form-check-input me-3 grace-user">
                                <div class="flex-grow-1">
                                    <strong>{{ $user->name }}</strong>
                                    <br><small class="text-muted">{{ $user->email }}</small>
                                    @if($user->phone)
                                        <br><small class="text-info"><i class="fas fa-phone me-1"></i>{{ $user->phone }}</small>
                                    @endif
                                </div>
                                <div>
                                    <span class="status-badge status-grace">Grace Period</span>
                                    <br><small class="text-muted">{{ $user->getDaysRemainingInCurrentPeriod() }} days left</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleUsers('grace-user')">Toggle All</button>
                    </div>
                @else
                    <p class="text-muted">No users in grace period.</p>
                @endif
            </div>

            <!-- Warning Period Users -->
            <div id="warning-users" class="user-section mt-4" style="display: none;">
                <h6 class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Warning Period Users ({{ $warningPeriodUsers->count() }})</h6>
                @if($warningPeriodUsers->count() > 0)
                    <div class="user-list">
                        @foreach($warningPeriodUsers as $user)
                            <div class="user-item">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="form-check-input me-3 warning-user">
                                <div class="flex-grow-1">
                                    <strong>{{ $user->name }}</strong>
                                    <br><small class="text-muted">{{ $user->email }}</small>
                                    @if($user->phone)
                                        <br><small class="text-info"><i class="fas fa-phone me-1"></i>{{ $user->phone }}</small>
                                    @endif
                                </div>
                                <div>
                                    <span class="status-badge status-warning">Warning</span>
                                    <br><small class="text-muted">{{ $user->getDaysRemainingInCurrentPeriod() }} days left</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleUsers('warning-user')">Toggle All</button>
                    </div>
                @else
                    <p class="text-muted">No users in warning period.</p>
                @endif
            </div>

            <!-- Overdue Users -->
            <div id="overdue-users" class="user-section mt-4" style="display: none;">
                <h6 class="text-danger"><i class="fas fa-file-invoice-dollar me-2"></i>Overdue Invoice Users ({{ $overdueUsers->count() }})</h6>
                @if($overdueUsers->count() > 0)
                    <div class="user-list">
                        @foreach($overdueUsers as $user)
                            <div class="user-item">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="form-check-input me-3 overdue-user">
                                <div class="flex-grow-1">
                                    <strong>{{ $user->name }}</strong>
                                    <br><small class="text-muted">{{ $user->email }}</small>
                                    @if($user->phone)
                                        <br><small class="text-info"><i class="fas fa-phone me-1"></i>{{ $user->phone }}</small>
                                    @endif
                                </div>
                                <div>
                                    <span class="status-badge status-overdue">Overdue</span>
                                    <br><small class="text-muted">{{ $user->stripeInvoices->where('status', 'open')->count() }} invoice(s)</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleUsers('overdue-user')">Toggle All</button>
                    </div>
                @else
                    <p class="text-muted">No users with overdue invoices.</p>
                @endif
            </div>

            <!-- All Eligible Users -->
            <div id="all-users" class="user-section mt-4" style="display: none;">
                <h6 class="text-info"><i class="fas fa-users me-2"></i>All Eligible Users ({{ $allEligibleUsers->count() }})</h6>
                @if($allEligibleUsers->count() > 0)
                    <div class="user-list">
                        @foreach($allEligibleUsers as $user)
                            <div class="user-item">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="all_user_{{ $user->id }}" checked>
                                <label for="all_user_{{ $user->id }}" class="user-label">
                                    <div class="user-info">
                                        <strong>{{ $user->name }}</strong>
                                        <br><small class="text-muted">{{ $user->email }}</small>
                                        @if($user->phone)
                                            <br><small class="text-success"><i class="fas fa-phone me-1"></i>{{ $user->phone }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        @if($user->isInGracePeriod())
                                            <span class="status-badge status-grace">Grace Period</span>
                                            <br><small class="text-muted">{{ $user->getDaysRemainingInCurrentPeriod() }} days remaining</small>
                                        @elseif($user->isInWarningPeriod())
                                            <span class="status-badge status-warning">Warning Period</span>
                                            <br><small class="text-muted">{{ $user->getDaysRemainingInCurrentPeriod() }} days remaining</small>
                                        @elseif($user->stripeInvoices->where('status', 'open')->where('due_date', '<', now())->count() > 0)
                                            <span class="status-badge status-overdue">Overdue</span>
                                            <br><small class="text-muted">{{ $user->stripeInvoices->where('status', 'open')->count() }} invoice(s)</small>
                                        @else
                                            <span class="status-badge" style="background: rgba(40, 167, 69, 0.2); color: #28a745;">Active</span>
                                            <br><small class="text-muted">No issues</small>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllUsers('all')">Toggle All</button>
                    </div>
                @else
                    <p class="text-muted">No eligible users found.</p>
                @endif
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>All Reminder Types Selected</strong>
                    <br>This will send appropriate reminders based on each user's current status:
                    <ul class="mb-0 mt-2">
                        <li>{{ $gracePeriodUsers->count() }} users will receive grace period reminders</li>
                        <li>{{ $warningPeriodUsers->count() }} users will receive warning period reminders</li>
                        <li>{{ $overdueUsers->count() }} users will receive overdue invoice reminders</li>
                        <li>{{ $allEligibleUsers->count() - $gracePeriodUsers->count() - $warningPeriodUsers->count() - $overdueUsers->count() }} users have no current issues</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Options -->
        <div class="reminder-card">
            <h5 class="mb-3"><i class="fas fa-cog me-2"></i>Options</h5>
            
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="force_send" id="force_send" value="1">
                <label class="form-check-label" for="force_send">
                    <strong>Force Send</strong>
                    <br><small class="text-muted">Ignore reminder frequency limits and send reminders immediately</small>
                </label>
            </div>
        </div>

        <!-- Send Button -->
        <div class="text-center">
            <button type="submit" class="btn btn-send-reminders btn-lg">
                <i class="fas fa-paper-plane me-2"></i>Send Reminders
            </button>
            <br><small class="text-muted mt-2 d-block">Reminders will be sent via email and SMS (if phone number available)</small>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reminderTypeInputs = document.querySelectorAll('input[name="reminder_type"]');
    const userSections = {
        'grace_period': document.getElementById('grace-users'),
        'warning_period': document.getElementById('warning-users'),
        'overdue': document.getElementById('overdue-users'),
        'all': document.getElementById('all-users')
    };

    function showUserSection(type) {
        // Hide all sections
        Object.values(userSections).forEach(section => {
            section.style.display = 'none';
        });
        
        // Show selected section
        if (userSections[type]) {
            userSections[type].style.display = 'block';
        }
        
        // Clear all checkboxes when switching types
        document.querySelectorAll('input[name="user_ids[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    // Handle reminder type changes
    reminderTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            showUserSection(this.value);
        });
    });

    // Initialize with default selection
    showUserSection('grace_period');
});

function toggleUsers(className) {
    const checkboxes = document.querySelectorAll('.' + className);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

function toggleAllUsers(section) {
    const checkboxes = document.querySelectorAll(`#${section}-users input[name="user_ids[]"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Form submission confirmation
document.getElementById('reminderForm').addEventListener('submit', function(e) {
    const reminderType = document.querySelector('input[name="reminder_type"]:checked').value;
    const selectedUsers = document.querySelectorAll('input[name="user_ids[]"]:checked').length;
    const forceSend = document.getElementById('force_send').checked;
    
    let message = `Are you sure you want to send ${reminderType.replace('_', ' ')} reminders?`;
    
    if (reminderType === 'all') {
        message += '\n\nThis will send ALL applicable reminders to eligible users.';
    } else if (selectedUsers > 0) {
        message += `\n\nThis will send reminders to ${selectedUsers} selected user(s).`;
    } else {
        message += '\n\nThis will send reminders to all eligible users.';
    }
    
    if (forceSend) {
        message += '\n\n⚠️ FORCE SEND is enabled - frequency limits will be ignored.';
    }
    
    if (!confirm(message)) {
        e.preventDefault();
    }
});
</script>
@endpush
@endsection