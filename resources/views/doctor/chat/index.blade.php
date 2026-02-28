@extends('master')

@section('title', 'Chat Management')

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '💬';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-header">
    <h2>Doctor Chat</h2>
    <p>Chat with patients</p>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Communications</h1>
                <div class="d-flex align-items-center">
                    <span id="unread-count" class="badge bg-danger me-3" style="display: none;">0 unread</span>
                    <a href="{{ route('doctor.chat.settings') }}" class="btn btn-outline-info btn-sm me-2">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <button id="mark-all-read-btn" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-4">
                    <!-- Chat Sessions List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-comments me-2"></i>
                                Chat Sessions
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="chat-sessions-list">
                                @if($chatSessions->count() > 0)
                                    @foreach($chatSessions as $session)
                                        <div class="chat-session-item p-3 border-bottom {{ $loop->first ? 'active' : '' }}"
                                             data-session-id="{{ $session->id }}"
                                             style="cursor: pointer;">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        {{ $session->visitor_name ?: 'Anonymous Visitor' }}
                                                        @if($session->has_unread_messages)
                                                            <span class="badge bg-danger ms-2">New</span>
                                                        @endif
                                                    </h6>
                                                    <p class="mb-1 text-muted small">
                                                        {{ Str::limit($session->last_message, 50) }}
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $session->updated_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                                @if($session->visitor_email)
                                                    <i class="fas fa-envelope text-info" title="Email provided"></i>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No chat sessions yet</h6>
                                        <p class="text-muted small">Chat sessions will appear here when visitors start conversations.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Chat Messages -->
                    <div class="card">
                        <div class="card-header" id="chat-header">
                            @if($chatSessions->count() > 0)
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0" id="chat-title">
                                            {{ $chatSessions->first()->visitor_name ?: 'Anonymous Visitor' }}
                                        </h5>
                                        <small class="text-muted" id="chat-subtitle">
                                            @if($chatSessions->first()->visitor_email)
                                                <i class="fas fa-envelope me-1"></i>
                                                {{ $chatSessions->first()->visitor_email }}
                                            @endif
                                            <span class="ms-2">
                                                <i class="fas fa-clock me-1"></i>
                                                Started {{ $chatSessions->first()->created_at->diffForHumans() }}
                                            </span>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>
                                            Online
                                        </span>
                                    </div>
                                </div>
                            @else
                                <h5 class="card-title mb-0">Select a chat session</h5>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <div id="chat-messages" style="height: 400px; overflow-y: auto;">
                                @if($chatSessions->count() > 0)
                                    <div id="messages-container" class="p-3">
                                        <!-- Messages will be loaded here via AJAX -->
                                    </div>
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <div class="text-center">
                                            <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No chat selected</h6>
                                            <p class="text-muted small">Select a chat session from the left to view messages.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if($chatSessions->count() > 0)
                            <div class="card-footer">
                                <form id="reply-form">
                                    <div class="input-group">
                                        <input type="text"
                                               id="reply-input"
                                               class="form-control"
                                               placeholder="Type your reply..."
                                               maxlength="1000">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.chat-session-item:hover {
    background-color: #f8f9fa;
}

.chat-session-item.active {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.chat-message {
    margin-bottom: 15px;
}

.chat-message.visitor {
    text-align: right;
}

.chat-message.visitor .message-bubble {
    background-color: #e3f2fd;
    color: #1976d2;
    border-radius: 18px 18px 4px 18px;
    padding: 10px 15px;
    display: inline-block;
    max-width: 70%;
    word-wrap: break-word;
}

.chat-message.doctor, .chat-message.bot {
    text-align: left;
}

.chat-message.doctor .message-bubble, .chat-message.bot .message-bubble {
    background-color: #f5f5f5;
    color: #333;
    border-radius: 18px 18px 18px 4px;
    padding: 10px 15px;
    display: inline-block;
    max-width: 70%;
    word-wrap: break-word;
    border: 1px solid #ddd;
}

.chat-message.doctor .message-bubble {
    background-color: #4caf50;
    color: white;
    border-color: #4caf50;
}

.chat-message .message-time {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 4px;
}

.chat-message.visitor .message-time {
    text-align: right;
}

.chat-message.doctor .message-time, .chat-message.bot .message-time {
    text-align: left;
}

#chat-messages {
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 transparent;
}

#chat-messages::-webkit-scrollbar {
    width: 6px;
}

#chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

#chat-messages::-webkit-scrollbar-thumb {
    background-color: #dee2e6;
    border-radius: 3px;
}

#chat-messages::-webkit-scrollbar-thumb:hover {
    background-color: #adb5bd;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentSessionId = {{ $chatSessions->first()->id ?? 'null' }};

    // Load initial messages if there's a session
    if (currentSessionId) {
        loadMessages(currentSessionId);
    }

    // Update unread count
    updateUnreadCount();

    // Chat session selection
    $('.chat-session-item').click(function() {
        const sessionId = $(this).data('session-id');

        // Update active state
        $('.chat-session-item').removeClass('active');
        $(this).addClass('active');

        // Remove unread badge
        $(this).find('.badge').remove();

        // Update current session
        currentSessionId = sessionId;

        // Load messages
        loadMessages(sessionId);

        // Update header
        updateChatHeader($(this));
    });

    // Send reply
    $('#reply-form').submit(function(e) {
        e.preventDefault();

        const message = $('#reply-input').val().trim();
        if (!message || !currentSessionId) return;

        sendMessage(currentSessionId, message);
    });

    // Mark all as read
    $('#mark-all-read-btn').click(function() {
        $.ajax({
            url: '/doctor/chat/mark-all-read',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('.chat-session-item .badge').remove();
                    updateUnreadCount();
                    showAlert('success', 'All messages marked as read');
                }
            }
        });
    });

    function loadMessages(sessionId) {
        $.ajax({
            url: `/doctor/chat/${sessionId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayMessages(response.messages);
                }
            },
            error: function() {
                showAlert('danger', 'Failed to load messages');
            }
        });
    }

    function sendMessage(sessionId, message) {
        $.ajax({
            url: `/doctor/chat/${sessionId}/send`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                message: message
            },
            beforeSend: function() {
                $('#reply-input').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#reply-input').val('');

                    // Add message to chat
                    const messageHtml = createMessageHtml({
                        message: message,
                        sender_type: 'doctor',
                        created_at: 'now'
                    });
                    $('#messages-container').append(messageHtml);
                    scrollToBottom();
                }
            },
            error: function() {
                showAlert('danger', 'Failed to send message');
            },
            complete: function() {
                $('#reply-input').prop('disabled', false).focus();
            }
        });
    }

    function displayMessages(messages) {
        const container = $('#messages-container');
        container.empty();

        messages.forEach(function(message) {
            const messageHtml = createMessageHtml(message);
            container.append(messageHtml);
        });

        scrollToBottom();
    }

    function createMessageHtml(message) {
        const senderClass = message.sender_type;
        const timeFormatted = message.created_at === 'now' ? 'Just now' : new Date(message.created_at).toLocaleTimeString();

        return `
            <div class="chat-message ${senderClass}">
                <div class="message-bubble">${escapeHtml(message.message)}</div>
                <div class="message-time">${timeFormatted}</div>
            </div>
        `;
    }

    function updateChatHeader(sessionItem) {
        const visitorName = sessionItem.find('h6').text().replace('New', '').trim();
        const email = sessionItem.data('email') || '';

        $('#chat-title').text(visitorName);

        let subtitle = '<i class="fas fa-clock me-1"></i>Started ' + sessionItem.find('small').text();
        if (email) {
            subtitle = '<i class="fas fa-envelope me-1"></i>' + email + ' <span class="ms-2">' + subtitle + '</span>';
        }
        $('#chat-subtitle').html(subtitle);
    }

    function updateUnreadCount() {
        const unreadCount = $('.chat-session-item .badge').length;
        const countElement = $('#unread-count');

        if (unreadCount > 0) {
            countElement.text(unreadCount + ' unread').show();
        } else {
            countElement.hide();
        }
    }

    function scrollToBottom() {
        const chatMessages = $('#chat-messages');
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showAlert(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alert);

        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    }

    // Auto-refresh messages every 30 seconds
    setInterval(function() {
        if (currentSessionId) {
            loadMessages(currentSessionId);
        }
    }, 30000);
});
</script>
@endpush
