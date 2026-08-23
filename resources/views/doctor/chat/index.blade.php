@extends('master')

@section('title', 'Chat Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
/* Chat premium — aligned with cases-overview + clinical/monitoring + appointments/show */
.app-main { background-color: var(--bg-secondary, #f8f9fa); }
.clinical-card {
    border-radius: 12px !important;
    overflow: hidden;
    border: 1px solid #eef0f3 !important;
    background: #ffffff;
    box-shadow: 0 6px 20px rgba(44,62,80,0.05), 0 1px 6px rgba(44,62,80,0.04) !important;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.2s ease;
}
.clinical-card:hover {
    box-shadow: 0 12px 28px rgba(44,62,80,0.08), 0 4px 12px rgba(44,62,80,0.05) !important;
    border-color: #e6e8eb !important;
}
.clinical-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1.15rem;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.clinical-card__head-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}
.clinical-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    border: 1px solid;
}
.clinical-icon-box.icon-sessions { background:#f8fafc; color:#1e3a8a; border-color:#dbeafe; }
.clinical-icon-box.icon-messages { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
.clinical-card__title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.01em;
    margin: 0;
    line-height: 1.2;
    white-space: nowrap;
}
.clinical-card__subtitle {
    font-size: 0.74rem;
    color: #94a3b8;
    font-weight: 500;
    margin: 2px 0 0;
    line-height: 1.2;
}
.clinical-card__meta {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.32rem 0.65rem;
    border-radius: 99px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    white-space: nowrap;
}
.clinical-toolbar {
    background: #ffffff;
    border-bottom: 1px solid #eef2f7;
    padding: 0.55rem 1.15rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.clinical-toolbar__hint {
    font-size: 0.74rem;
    color: #94a3b8;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.pulse-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 0 rgba(16,185,129,0.6);
    animation: pulse-live 1.8s infinite;
    display: inline-block;
}
@keyframes pulse-live {
    0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.55); }
    70% { box-shadow: 0 0 0 7px rgba(16,185,129,0); }
    100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}
.online-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.32rem 0.65rem;
    border-radius: 99px;
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
    letter-spacing: 0.02em;
}
.online-badge .pulse-dot { width:7px; height:7px; }
/* Sessions list — doctor-table inspired */
.chat-session-item {
    cursor: pointer;
    border-left: 3px solid transparent;
    transition: all 0.18s ease;
    background: #fff;
}
.chat-session-item:hover {
    background-color: #f8f9fa !important;
    border-left-color: #3498db !important;
}
.chat-session-item.active {
    background-color: #e3f2fd !important;
    border-left: 4px solid #2196f3 !important;
}
.chat-session-item.active:hover {
    border-left-color: #2196f3 !important;
}
.patient-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color, #2c3e50) 0%, var(--primary-light, #34495e) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(44,62,80,0.2);
}
.session-meta-badge {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.chat-message {
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
}
.chat-message.visitor { align-items: flex-end; text-align: right; }
.chat-message.doctor, .chat-message.bot { align-items: flex-start; text-align: left; }
.message-bubble {
    border-radius: 18px;
    padding: 10px 15px;
    display: inline-block;
    max-width: 70%;
    word-wrap: break-word;
    font-size: 0.88rem;
    line-height: 1.5;
    box-shadow: 0 2px 8px rgba(15,23,42,0.06);
}
.chat-message.visitor .message-bubble {
    background-color: #e3f2fd;
    color: #1565c0;
    border-radius: 18px 18px 4px 18px;
    border: 1px solid #dbeafe;
}
.chat-message.bot .message-bubble {
    background-color: #f5f5f5;
    color: #333;
    border-radius: 18px 18px 18px 4px;
    padding: 10px 15px;
    border: 1px solid #e2e8f0;
}
.chat-message.doctor .message-bubble {
    background-color: #4caf50;
    color: white;
    border-radius: 18px 18px 18px 4px;
    border: 1px solid #4caf50;
    box-shadow: 0 4px 12px rgba(76,175,80,0.22);
}
.chat-message .message-time {
    font-size: 0.70rem;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}
.chat-message.visitor .message-time { text-align: right; }
.chat-message.doctor .message-time, .chat-message.bot .message-time { text-align: left; }
#chat-messages {
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 transparent;
    background: #fcfdff;
}
#chat-messages::-webkit-scrollbar { width: 6px; }
#chat-messages::-webkit-scrollbar-track { background: transparent; }
#chat-messages::-webkit-scrollbar-thumb { background-color: #dee2e6; border-radius: 3px; }
#chat-messages::-webkit-scrollbar-thumb:hover { background-color: #adb5bd; }
.chat-input-wrap {
    background: #f8fafc;
    border-top: 1px solid #eef2f7;
    padding: 0.85rem 1rem;
}
.chat-input-wrap .form-control {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 0.88rem;
    padding: 0.62rem 0.85rem;
    box-shadow: 0 1px 4px rgba(15,23,42,0.04);
}
.chat-input-wrap .form-control:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 3px rgba(33,150,243,0.12);
}
.chat-input-wrap .btn-primary {
    border-radius: 10px;
    padding: 0.62rem 1rem;
    background: #2196f3;
    border-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33,150,243,0.25);
}
.chat-input-wrap .btn-primary:hover { background:#1976d2; border-color:#1976d2; }
.chat-empty {
    background: #fcfdff;
    border: 1px dashed #e2e8f0;
    border-radius: 12px;
    margin: 1rem;
    padding: 2rem 1.25rem;
    text-align: center;
}
@media (max-width: 768px) {
    .clinical-card__head { padding: 0.85rem 1rem; }
    .message-bubble { max-width: 82%; }
}
</style>
@endpush
@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact" style="position:relative; overflow:hidden;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-comments me-2"></i>Doctor Chat</h2>
                    <p>Real-time patient communications</p>
                    <div class="d-flex align-items-center gap-2 mt-2" style="font-size:0.78rem; color:rgba(255,255,255,0.85);">
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="pulse-dot" style="background:#10b981; width:7px;height:7px;"></span> Telemetry active
                        </span>
                        <span class="d-none d-sm-inline" style="opacity:0.55;">·</span>
                        <span class="d-none d-sm-inline"><i class="far fa-clock me-1"></i>Auto-refresh 30s</span>
                    </div>
                </div>
                <span class="doctor-badge doctor-badge-primary d-none d-md-inline-flex"><i class="fas fa-circle me-1" style="font-size:0.5rem;"></i> Live Chat</span>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Communications toolbar — premium --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 fw-bold" style="color:#1e293b; font-size:1.02rem; letter-spacing:-0.01em;"><i class="fas fa-inbox me-2" style="color:#64748b;"></i>Communications</h5>
                <span id="unread-count" class="badge bg-danger rounded-pill px-2 py-1" style="display: none; font-size:0.71rem;">0 unread</span>
                <span class="d-none d-md-inline-flex align-items-center gap-2" style="font-size:0.74rem; color:#94a3b8; font-weight:500;">
                    <span class="d-inline-block" style="width:6px;height:6px;border-radius:50%;background:#e2e8f0;"></span> Select a session to reply
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('doctor.chat.settings') }}" class="btn btn-sm fw-semibold" style="background:#fff; border:1px solid #e2e8f0; color:#475569; border-radius:8px; font-size:0.78rem;">
                    <i class="fas fa-cog me-1"></i> Settings
                </a>
                <button id="mark-all-read-btn" class="btn btn-sm fw-semibold" style="background:#eff6ff; border:1px solid #dbeafe; color:#1d4ed8; border-radius:8px; font-size:0.78rem;">
                    <i class="fas fa-check-double me-1"></i> Mark All Read
                </button>
            </div>
        </div>

        <div class="row g-3 align-items-stretch">
            <div class="col-lg-4 d-flex">
                {{-- Chat Sessions List — clinical-card cases-panel --}}
                <div class="card border-0 shadow-sm clinical-card cases-panel w-100">
                    <div class="clinical-card__head">
                        <div class="clinical-card__head-left">
                            <div class="clinical-icon-box icon-sessions">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div>
                                <h6 class="clinical-card__title">Chat Sessions</h6>
                                <p class="clinical-card__subtitle">{{ $chatSessions->count() }} conversations · Newest first</p>
                            </div>
                        </div>
                        <span class="clinical-card__meta d-none d-sm-inline-flex">
                            <i class="fas fa-list-ul" style="font-size:0.70rem;"></i> Inbox
                        </span>
                    </div>
                    <div class="clinical-toolbar">
                        <span class="clinical-toolbar__hint">
                            <i class="fas fa-mouse-pointer me-1"></i> Click to open
                        </span>
                        <span class="clinical-toolbar__hint d-none d-sm-inline-flex" style="font-size:0.71rem;">
                            <span class="d-inline-block me-1" style="width:8px;height:8px;border-radius:50%;background:#2196f3;"></span> Active · <span class="d-inline-block mx-1" style="width:8px;height:8px;border-radius:50%;background:#f8f9fa;border:1px solid #e2e8f0;"></span> Hover → <span style="color:#3498db; font-weight:700;">#3498db</span>
                        </span>
                    </div>
                    <div class="card-body p-0" style="background:#fff;">
                        <div id="chat-sessions-list">
                            @if($chatSessions->count() > 0)
                                @foreach($chatSessions as $session)
                                    @php
                                        $initials = collect(explode(' ', $session->visitor_name ?: 'Anonymous Visitor'))->map(fn($w)=>mb_substr($w,0,1))->take(2)->implode('');
                                        $initials = strtoupper($initials ?: 'AV');
                                    @endphp
                                    <div class="chat-session-item p-3 border-bottom {{ $loop->first ? 'active' : '' }}"
                                         data-session-id="{{ $session->id }}"
                                         data-email="{{ $session->visitor_email }}"
                                         style="cursor: pointer;">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="patient-avatar">{{ $initials }}</div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <h6 class="mb-0" style="font-size:0.88rem; font-weight:700; color:#1e293b; letter-spacing:-0.01em;">
                                                        {{ $session->visitor_name ?: 'Anonymous Visitor' }}
                                                    </h6>
                                                    @if($session->has_unread_messages)
                                                        <span class="badge bg-danger rounded-pill session-meta-badge">New</span>
                                                    @endif
                                                    @if($session->visitor_email)
                                                        <span class="badge bg-light text-muted border rounded-pill" style="font-size:0.68rem; font-weight:600;"><i class="fas fa-envelope me-1"></i> Email</span>
                                                    @endif
                                                </div>
                                                <p class="mb-1 text-muted small" style="font-size:0.78rem; line-height:1.4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                    {{ Str::limit($session->last_message, 52) ?: 'No messages yet — start conversation' }}
                                                </p>
                                                <small class="d-inline-flex align-items-center gap-1" style="font-size:0.72rem; color:#94a3b8; font-weight:500;">
                                                    <i class="fas fa-clock" style="font-size:0.66rem;"></i>
                                                    {{ $session->updated_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            @if($session->visitor_email)
                                                <i class="fas fa-envelope text-info mt-1" title="Email provided" style="font-size:0.85rem; opacity:0.85;"></i>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="chat-empty">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px; height:48px; background:#fff; border:1px solid #eef2f7; color:#94a3b8;">
                                        <i class="fas fa-comments" style="font-size:1.2rem;"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1" style="font-size:0.88rem; color:#475569;">No chat sessions yet</h6>
                                    <p class="small mb-0" style="font-size:0.76rem; color:#94a3b8;">Chat sessions will appear here when visitors start conversations.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background:var(--gray-50, #f8fafc); border-top:1px solid #eef2f7; font-size:0.72rem; color:#64748b; border-radius:0 0 12px 12px;">
                        <span><i class="fas fa-info-circle me-1"></i> {{ $chatSessions->count() }} total sessions</span>
                        <span class="d-none d-sm-inline">Hover accentuates <span style="color:#3498db; font-weight:700;">left #3498db</span></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 d-flex">
                {{-- Chat Messages — clinical-card cases-panel --}}
                <div class="card border-0 shadow-sm clinical-card cases-panel w-100 d-flex flex-column">
                    <div class="clinical-card__head" id="chat-header">
                        @if($chatSessions->count() > 0)
                            <div class="clinical-card__head-left">
                                <div class="clinical-icon-box icon-messages">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                                <div>
                                    <h6 class="clinical-card__title" id="chat-title" style="font-size:0.92rem;">
                                        {{ $chatSessions->first()->visitor_name ?: 'Anonymous Visitor' }}
                                    </h6>
                                    <p class="clinical-card__subtitle" id="chat-subtitle" style="font-size:0.74rem; display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                        @if($chatSessions->first()->visitor_email)
                                            <span><i class="fas fa-envelope me-1"></i>{{ $chatSessions->first()->visitor_email }}</span>
                                        @endif
                                        <span><i class="fas fa-clock me-1"></i>Started {{ $chatSessions->first()->created_at->diffForHumans() }}</span>
                                    </p>
                                </div>
                            </div>
                            <span class="online-badge">
                                <span class="pulse-dot"></span> Online
                            </span>
                        @else
                            <div class="clinical-card__head-left">
                                <div class="clinical-icon-box icon-messages">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                                <div>
                                    <h6 class="clinical-card__title">Select a chat session</h6>
                                    <p class="clinical-card__subtitle">Choose from the left inbox</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="clinical-toolbar">
                        <span class="clinical-toolbar__hint">
                            <i class="fas fa-shield-alt me-1"></i> Doctor <span class="badge rounded-pill" style="background:#4caf50; color:#fff; font-size:0.66rem;">● You</span> &nbsp;·&nbsp; Visitor <span class="badge rounded-pill" style="background:#e3f2fd; color:#1565c0; border:1px solid #dbeafe; font-size:0.66rem;">● Guest</span>
                        </span>
                        <span class="clinical-toolbar__hint d-none d-md-inline-flex" style="font-size:0.71rem;">
                            <i class="fas fa-arrows-alt-v me-1"></i> Scroll · thin bar
                        </span>
                    </div>
                    <div class="card-body p-0 d-flex flex-column flex-grow-1" style="background:#fff;">
                        <div id="chat-messages" style="height: 400px; overflow-y: auto;">
                            @if($chatSessions->count() > 0)
                                <div id="messages-container" class="p-3">
                                    <!-- Messages will be loaded here via AJAX -->
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center h-100 p-4">
                                    <div class="chat-empty" style="margin:0; width:100%;">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px; height:48px; background:#fff; border:1px solid #eef2f7; color:#94a3b8;">
                                            <i class="fas fa-comment-dots" style="font-size:1.2rem;"></i>
                                        </div>
                                        <h6 class="fw-bold mb-1" style="font-size:0.88rem; color:#475569;">No chat selected</h6>
                                        <p class="small mb-0" style="font-size:0.76rem; color:#94a3b8;">Select a chat session from the left to view messages.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @if($chatSessions->count() > 0)
                        <div class="chat-input-wrap">
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
@endsection

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
        $(this).find('.badge.bg-danger').remove();

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
                    $('.chat-session-item .badge.bg-danger').remove();
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

        let subtitle = '<i class="fas fa-clock me-1"></i>Started ' + sessionItem.find('small').text().trim();
        if (email) {
            subtitle = '<i class="fas fa-envelope me-1"></i>' + email + ' <span class="ms-2">' + subtitle + '</span>';
        }
        $('#chat-subtitle').html(subtitle);
    }

    function updateUnreadCount() {
        const unreadCount = $('.chat-session-item .badge.bg-danger').length;
        const countElement = $('#unread-count');

        if (unreadCount > 0) {
            countElement.text(unreadCount + ' unread').show();
        } else {
            countElement.hide();
        }
    }

    function scrollToBottom() {
        const chatMessages = $('#chat-messages');
        if (chatMessages.length && chatMessages[0]) {
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }
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
        $('.container .container').first().prepend(alert);

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
