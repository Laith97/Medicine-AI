<!-- Chat Widget -->
<div id="chat-widget" class="position-fixed" style="bottom: 20px; right: 20px; z-index: 1050;">
    <!-- Chat Button -->
    <button id="chat-toggle-btn" class="btn btn-primary rounded-circle shadow-lg" style="width: 60px; height: 60px;">
        <i class="fas fa-comments fa-lg"></i>
        <span id="chat-notification" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
            <span class="visually-hidden">unread messages</span>
        </span>
    </button>

    <!-- Chat Box -->
    <div id="chat-box" class="card shadow-lg d-none" style="width: 350px; height: 500px; margin-bottom: 80px;">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-md me-2"></i>
                <div>
                    <h6 class="mb-0">{{ $doctorName ?? 'Dr. Assistant' }}</h6>
                    <small class="opacity-75">Online now</small>
                </div>
            </div>
            <button id="chat-close-btn" class="btn btn-sm text-white p-0">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="chat-messages" class="card-body p-0" style="height: 350px; overflow-y: auto;">
            <div class="p-3">
                <div id="messages-container">
                    <!-- Messages will be loaded here -->
                </div>
                <div id="typing-indicator" class="d-none">
                    <div class="d-flex align-items-center text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <small>Assistant is typing...</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <!-- Contact Form (shown initially) -->
            <div id="contact-form" class="mb-3">
                <div class="row g-2">
                    <div class="col-12">
                        <input type="text" id="visitor-name" class="form-control form-control-sm" placeholder="Your name (optional)">
                    </div>
                    <div class="col-12">
                        <input type="email" id="visitor-email" class="form-control form-control-sm" placeholder="Your email (optional)">
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="input-group">
                <input type="text"
                       id="chat-message-input"
                       class="form-control"
                       placeholder="Type your message..."
                       maxlength="1000">
                <button id="chat-send-btn" class="btn btn-primary" type="button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.chat-message {
    margin-bottom: 15px;
}

.chat-message.visitor {
    text-align: right;
}

.chat-message.visitor .message-bubble {
    background-color: #007bff;
    color: white;
    border-radius: 18px 18px 4px 18px;
    padding: 8px 15px;
    display: inline-block;
    max-width: 80%;
    word-wrap: break-word;
}

.chat-message.bot, .chat-message.doctor {
    text-align: left;
}

.chat-message.bot .message-bubble, .chat-message.doctor .message-bubble {
    background-color: #f8f9fa;
    color: #333;
    border-radius: 18px 18px 18px 4px;
    padding: 8px 15px;
    display: inline-block;
    max-width: 80%;
    word-wrap: break-word;
    border: 1px solid #dee2e6;
}

.chat-message .message-time {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 4px;
}

.chat-message.visitor .message-time {
    text-align: right;
}

.chat-message.bot .message-time, .chat-message.doctor .message-time {
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

@media (max-width: 768px) {
    #chat-widget {
        bottom: 10px;
        right: 10px;
    }

    #chat-box {
        width: calc(100vw - 20px);
        height: 400px;
    }
}
</style>
@endpush

@push('scripts')
<script>
class ChatWidget {
    constructor(doctorUsername) {
        this.doctorUsername = doctorUsername;
        this.sessionId = null;
        this.isInitialized = false;
        this.contactInfoProvided = false;

        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        this.chatToggleBtn = document.getElementById('chat-toggle-btn');
        this.chatBox = document.getElementById('chat-box');
        this.chatCloseBtn = document.getElementById('chat-close-btn');
        this.messagesContainer = document.getElementById('messages-container');
        this.messageInput = document.getElementById('chat-message-input');
        this.sendBtn = document.getElementById('chat-send-btn');
        this.contactForm = document.getElementById('contact-form');
        this.visitorNameInput = document.getElementById('visitor-name');
        this.visitorEmailInput = document.getElementById('visitor-email');
        this.typingIndicator = document.getElementById('typing-indicator');
        this.chatMessages = document.getElementById('chat-messages');
    }

    bindEvents() {
        this.chatToggleBtn.addEventListener('click', () => this.toggleChat());
        this.chatCloseBtn.addEventListener('click', () => this.closeChat());
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Hide contact form after first message
        this.messageInput.addEventListener('focus', () => {
            if (!this.contactInfoProvided) {
                this.contactInfoProvided = true;
                setTimeout(() => {
                    $(this.contactForm).fadeOut();
                }, 2000);
            }
        });
    }

    async toggleChat() {
        if (this.chatBox.classList.contains('d-none')) {
            await this.openChat();
        } else {
            this.closeChat();
        }
    }

    async openChat() {
        this.chatBox.classList.remove('d-none');

        if (!this.isInitialized) {
            await this.initializeChat();
        }

        this.scrollToBottom();
        this.messageInput.focus();
    }

    closeChat() {
        this.chatBox.classList.add('d-none');
    }

    async initializeChat() {
        try {
            const response = await fetch(`/doctor/${this.doctorUsername}/chat/init`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                this.sessionId = data.session_id;
                this.isInitialized = true;

                // Add welcome message
                this.addMessage(data.welcome_message, 'bot', 'now');
            }
        } catch (error) {
            console.error('Failed to initialize chat:', error);
            this.addMessage('Sorry, chat is currently unavailable. Please try again later.', 'bot', 'now');
        }
    }

    async sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message || !this.sessionId) return;

        // Get contact info
        const visitorName = this.visitorNameInput.value.trim();
        const visitorEmail = this.visitorEmailInput.value.trim();

        // Add user message to chat
        this.addMessage(message, 'visitor', 'now');
        this.messageInput.value = '';

        // Show typing indicator
        this.showTypingIndicator();

        try {
            const response = await fetch(`/doctor/${this.doctorUsername}/chat/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId,
                    visitor_name: visitorName,
                    visitor_email: visitorEmail
                })
            });

            const data = await response.json();

            if (data.success && data.bot_response) {
                // Simulate typing delay
                setTimeout(() => {
                    this.hideTypingIndicator();
                    this.addMessage(data.bot_response, 'bot', data.formatted_time);
                }, 1000 + Math.random() * 2000); // 1-3 seconds delay
            } else {
                this.hideTypingIndicator();
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            this.hideTypingIndicator();
            this.addMessage('Sorry, I couldn\'t process your message. Please try again.', 'bot', 'now');
        }
    }

    addMessage(message, senderType, time) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${senderType}`;

        messageDiv.innerHTML = `
            <div class="message-bubble">${this.escapeHtml(message)}</div>
            <div class="message-time">${time}</div>
        `;

        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    showTypingIndicator() {
        this.typingIndicator.classList.remove('d-none');
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        this.typingIndicator.classList.add('d-none');
    }

    scrollToBottom() {
        setTimeout(() => {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }, 100);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat widget when page loads
document.addEventListener('DOMContentLoaded', function() {
    const doctorUsername = '{{ $doctorUsername ?? "" }}';
    if (doctorUsername) {
        window.chatWidget = new ChatWidget(doctorUsername);
    }
});
</script>
@endpush
