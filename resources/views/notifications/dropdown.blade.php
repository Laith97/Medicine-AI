@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <div class="notification-item {{ $notification->is_read ? 'read' : 'unread' }} px-3 py-2 border-bottom cursor-pointer"
             data-notification-id="{{ $notification->id }}"
             data-href="{{ $notification->link }}">
            <div class="d-flex align-items-start">
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
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 notification-title" style="font-size: 14px; font-weight: 500;">
                            {{ $notification->title }}
                        </h6>
                        <small class="text-muted notification-time">
                            {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>
                    <p class="mb-0 notification-message" style="font-size: 13px; color: #6c757d;">
                        {{ $notification->message }}
                    </p>
                    @if(!$notification->is_read)
                        <div class="mt-1">
                            <span class="badge bg-primary rounded-pill" style="font-size: 10px;">New</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    @if($notifications->hasMorePages())
        <div class="text-center py-2">
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                View More Notifications
            </a>
        </div>
    @endif
@else
    <div class="text-center py-4 text-muted">
        <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
        <small>No notifications</small>
    </div>
@endif
