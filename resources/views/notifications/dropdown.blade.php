@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <div class="notification-item {{ $notification->is_read ? 'read' : 'unread' }} px-3 py-2 border-bottom cursor-pointer"
              data-notification-id="{{ $notification->id }}"
              data-href="{{ $notification->link }}"
              role="listitem"
              tabindex="0"
              aria-label="{{ $notification->title }}, {{ $notification->message }}, {{ $notification->created_at->diffForHumans() }}{!! !$notification->is_read ? ', New notification' : '' !!}"
              aria-describedby="notification-{{ $notification->id }}-details">
            <div class="d-flex align-items-start">
                <div class="notification-icon me-3 mt-1" aria-hidden="true">
                    @if($notification->type === 'appointment_booked')
                        <i class="fas fa-calendar-check text-primary" aria-hidden="true"></i>
                    @elseif($notification->type === 'diagnosis_submitted')
                        <i class="fas fa-stethoscope text-success" aria-hidden="true"></i>
                    @elseif($notification->type === 'review_submitted')
                        <i class="fas fa-star text-warning" aria-hidden="true"></i>
                    @elseif($notification->type === 'voice_transcription_completed')
                        <i class="fas fa-microphone text-info" aria-hidden="true"></i>
                    @elseif($notification->type === 'system_alert')
                        <i class="fas fa-exclamation-triangle text-danger" aria-hidden="true"></i>
                    @else
                        <i class="fas fa-bell text-secondary" aria-hidden="true"></i>
                    @endif
                </div>
                <div class="flex-grow-1" id="notification-{{ $notification->id }}-details">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 notification-title" style="font-size: 14px; font-weight: 500;">
                            {{ $notification->title }}
                        </h6>
                        <small class="text-muted notification-time" aria-label="Received {{ $notification->created_at->diffForHumans() }}">
                            {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>
                    <p class="mb-0 notification-message" style="font-size: 13px; color: #6c757d;">
                        {{ $notification->message }}
                    </p>
                    @if(!$notification->is_read)
                        <div class="mt-1">
                            <span class="badge bg-primary rounded-pill" style="font-size: 10px;" aria-label="New notification">New</span>
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
