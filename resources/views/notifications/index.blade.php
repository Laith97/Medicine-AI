@extends('master')

@section('title', 'Notifications | MedcuraAI')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-bell me-2 text-primary"></i>
                    Notifications
                </h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm mark-all-read-btn" id="markAllReadBtn">
                        <i class="fas fa-check-all me-1"></i>
                        Mark All as Read
                    </button>
                    <a href="{{ route('settings') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-cog me-1"></i>
                        Settings
                    </a>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                        All Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">
                        Unread ({{ $unreadCount }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="appointment-tab" data-bs-toggle="tab" data-bs-target="#appointment" type="button" role="tab">
                        Appointments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="diagnosis-tab" data-bs-toggle="tab" data-bs-target="#diagnosis" type="button" role="tab">
                        Diagnoses
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                        System Alerts
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- All Notifications -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    @if($notifications->count() > 0)
                        @foreach($notifications as $notification)
                            @include('notifications.item', ['notification' => $notification])
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No notifications found</h5>
                            <p class="text-muted">You'll see your notifications here when they arrive.</p>
                        </div>
                    @endif
                </div>

                <!-- Unread Notifications -->
                <div class="tab-pane fade" id="unread" role="tabpanel">
                    @if($unreadNotifications->count() > 0)
                        @foreach($unreadNotifications as $notification)
                            @include('notifications.item', ['notification' => $notification])
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $unreadNotifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No unread notifications</h5>
                            <p class="text-muted">You're all caught up! All notifications have been read.</p>
                        </div>
                    @endif
                </div>

                <!-- Appointment Notifications -->
                <div class="tab-pane fade" id="appointment" role="tabpanel">
                    @if($appointmentNotifications->count() > 0)
                        @foreach($appointmentNotifications as $notification)
                            @include('notifications.item', ['notification' => $notification])
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $appointmentNotifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No appointment notifications</h5>
                            <p class="text-muted">You'll receive appointment notifications here when they're available.</p>
                        </div>
                    @endif
                </div>

                <!-- Diagnosis Notifications -->
                <div class="tab-pane fade" id="diagnosis" role="tabpanel">
                    @if($diagnosisNotifications->count() > 0)
                        @foreach($diagnosisNotifications as $notification)
                            @include('notifications.item', ['notification' => $notification])
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $diagnosisNotifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-stethoscope fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No diagnosis notifications</h5>
                            <p class="text-muted">You'll receive diagnosis notifications here when they're available.</p>
                        </div>
                    @endif
                </div>

                <!-- System Alerts -->
                <div class="tab-pane fade" id="system" role="tabpanel">
                    @if($systemNotifications->count() > 0)
                        @foreach($systemNotifications as $notification)
                            @include('notifications.item', ['notification' => $notification])
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $systemNotifications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No system alerts</h5>
                            <p class="text-muted">Everything is running smoothly. No system alerts at this time.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Mark all as read
    $('#markAllReadBtn').click(function() {
        $.ajax({
            url: '{{ route("notifications.mark-all-read") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                location.reload();
            }
        });
    });

    // Mark notification as read when clicked
    $('.notification-item').click(function() {
        const notificationId = $(this).data('notification-id');
        const $item = $(this);

        $.ajax({
            url: '{{ route("notifications.mark-read", ":id") }}'.replace(':id', notificationId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                $item.removeClass('unread').addClass('read');
                // Update unread count
                const unreadCount = parseInt($('.badge.bg-primary').text() || 0);
                if (unreadCount > 0) {
                    $('.badge.bg-primary').text(unreadCount - 1);
                }
            }
        });
    });
});
</script>
@endsection
