<div class="notification-item {{ $notification->is_read ? 'read' : 'unread' }} border-bottom py-3 cursor-pointer"
     data-notification-id="{{ $notification->id }}"
     data-href="{{ $notification->link }}">
    <div class="d-flex align-items-start">
        <!-- Notification Icon -->
        <div class="notification-icon me-3 mt-1">
            @if($notification->type === 'appointment_booked')
                <i class="fas fa-calendar-check text-primary"></i>
            @elseif($notification->type === 'diagnosis_submitted')
                <i class="fas fa-stethoscope text-success"></i>
            @elseif($notification->type === 'review_submitted')
                <i class="fas fa-star text-warning"></i>
            @elseif($notification->type === 'voice_transcription_completed')
                <i class="fas fa-microphone text-info"></i>
            @elseif($notification->type === 'system_alert')
                <i class="fas fa-exclamation-triangle text-danger"></i>
            @else
                <i class="fas fa-bell text-secondary"></i>
            @endif
        </div>

        <!-- Notification Content -->
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="mb-0 notification-title" style="font-size: 15px; font-weight: 500; color: #333;">
                    {{ $notification->title }}
                </h6>
                <small class="text-muted notification-time">
                    {{ $notification->created_at->diffForHumans() }}
                </small>
            </div>

            <p class="mb-0 notification-message" style="font-size: 14px; color: #6c757d; line-height: 1.5;">
                {{ $notification->message }}
            </p>

            <!-- Additional Info -->
            @if($notification->data && isset($notification->data['appointment_date']))
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Appointment: {{ \Carbon\Carbon::parse($notification->data['appointment_date'])->format('M d, Y - g:i A') }}
                    </small>
                </div>
            @endif

            @if($notification->data && isset($notification->data['doctor_name']))
                <div class="mt-1">
                    <small class="text-muted">
                        <i class="fas fa-user-md me-1"></i>
                        Dr. {{ $notification->data['doctor_name'] }}
                    </small>
                </div>
            @endif

            @if($notification->data && isset($notification->data['patient_name']))
                <div class="mt-1">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>
                        {{ $notification->data['patient_name'] }}
                    </small>
                </div>
            @endif

            <!-- Status Badge -->
            @if(!$notification->is_read)
                <div class="mt-2">
                    <span class="badge bg-primary rounded-pill" style="font-size: 11px;">
                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                        New
                    </span>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="ms-3">
            @if($notification->link)
                <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- Hover Effect -->
    <div class="notification-overlay" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,123,255,0.1); border-radius: 8px;"></div>
</div>

<style>
.notification-item {
    position: relative;
    transition: all 0.2s ease;
    border-radius: 8px !important;
}

.notification-item:hover {
    background-color: rgba(222, 98, 98, 0.05) !important;
    transform: translateX(4px);
}

.notification-item.unread {
    background-color: rgba(222, 98, 98, 0.02) !important;
    border-left: 3px solid #DE6262;
}

.notification-item.read {
    opacity: 0.8;
}

.notification-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(222, 98, 98, 0.1);
    border-radius: 50%;
    font-size: 14px;
}

.notification-item.unread .notification-icon {
    background: rgba(222, 98, 98, 0.2);
    color: #DE6262;
}

.notification-time {
    font-size: 12px;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .notification-item {
        padding: 12px !important;
    }

    .notification-icon {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
}
</style>
