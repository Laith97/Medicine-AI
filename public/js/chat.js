document.addEventListener('DOMContentLoaded', function() {
    const followUpForm = document.getElementById('follow-up-form');
    const chatMessages = document.getElementById('chat-messages');
    const followUpInput = document.getElementById('follow-up-message');
    
    if (followUpForm) {
        followUpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = followUpInput.value.trim();
            const threadId = document.getElementById('thread-id').value;
            
            if (!message) return;
            
            // Add user message to the chat
            addMessage(message, 'user');
            
            // Clear input
            followUpInput.value = '';
            
            // Show loading indicator
            const loadingId = addLoadingIndicator();
            
            // Send message to server
            fetch('/openai/follow-up', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message,
                    thread_id: threadId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                removeLoadingIndicator(loadingId);
                
                if (data.success) {
                    // Add AI response to the chat
                    addMessage(data.message, 'ai');
                    
                    // Update thread ID if needed
                    if (data.thread_id) {
                        document.getElementById('thread-id').value = data.thread_id;
                    }
                } else {
                    // Show error message
                    addErrorMessage(data.message || 'Failed to get a response. Please try again.');
                }
            })
            .catch(error => {
                // Remove loading indicator
                removeLoadingIndicator(loadingId);
                
                // Show error message
                addErrorMessage('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
    
    // Function to add a message to the chat
    function addMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'message-avatar';
        
        const icon = document.createElement('i');
        icon.className = sender === 'user' ? 'fas fa-user' : 'fas fa-robot';
        avatarDiv.appendChild(icon);
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        if (sender === 'ai') {
            const pre = document.createElement('pre');
            pre.className = 'response-text';
            pre.textContent = content;
            contentDiv.appendChild(pre);
        } else {
            contentDiv.textContent = content;
        }
        
        messageDiv.appendChild(avatarDiv);
        messageDiv.appendChild(contentDiv);
        
        chatMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Function to add a loading indicator
    function addLoadingIndicator() {
        const id = 'loading-' + Date.now();
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message ai-message';
        loadingDiv.id = id;
        
        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'message-avatar';
        
        const icon = document.createElement('i');
        icon.className = 'fas fa-robot';
        avatarDiv.appendChild(icon);
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content message-loading';
        
        const loadingDotsDiv = document.createElement('div');
        loadingDotsDiv.className = 'loading-dots';
        
        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('span');
            loadingDotsDiv.appendChild(dot);
        }
        
        contentDiv.appendChild(loadingDotsDiv);
        loadingDiv.appendChild(avatarDiv);
        loadingDiv.appendChild(contentDiv);
        
        chatMessages.appendChild(loadingDiv);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        return id;
    }
    
    // Function to remove the loading indicator
    function removeLoadingIndicator(id) {
        const loadingDiv = document.getElementById(id);
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }
    
    // Function to add an error message
    function addErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mt-3';
        errorDiv.textContent = message;
        
        chatMessages.appendChild(errorDiv);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
});