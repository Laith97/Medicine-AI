@extends('master')

@section('title', 'Chat Settings')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-cog me-2"></i>Chat Settings</h2>
                        <p class="text-muted mb-0">Configure your AI assistant and chat preferences</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog mr-2"></i>
                        Chat Settings
                    </h3>
                </div>

                <form action="{{ route('doctor.chat.update-settings') }}" method="POST">
                    @csrf

                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- AI Chat Enable/Disable -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-robot mr-2"></i>
                                            AI Assistant
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="ai_chat_enabled"
                                                   id="ai_chat_enabled"
                                                   value="1"
                                                   {{ $doctor->ai_chat_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ai_chat_enabled">
                                                <strong>Enable AI Assistant</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">
                                            When enabled, the AI assistant will automatically respond to patient messages.
                                            When disabled, you'll need to manually respond to all messages.
                                        </small>

                                        <div class="mt-3">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                <strong>AI Features:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>Automatic language detection and response</li>
                                                    <li>Appointment booking assistance</li>
                                                    <li>Basic medical information</li>
                                                    <li>Emergency situations handling</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Settings (show only if AI is enabled) -->
                        <div id="ai-settings" style="{{ $doctor->ai_chat_enabled ? '' : 'display: none;' }}">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ai_welcome_message" class="form-label">
                                        <i class="fas fa-hand-wave mr-1"></i>
                                        Custom Welcome Message
                                    </label>
                                    <textarea class="form-control"
                                              id="ai_welcome_message"
                                              name="ai_welcome_message"
                                              rows="3"
                                              placeholder="Leave empty to use default welcome message">{{ $doctor->ai_chat_settings['welcome_message'] ?? '' }}</textarea>
                                    <small class="text-muted">
                                        Customize the first message patients see when they start a chat.
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="ai_fallback_message" class="form-label">
                                        <i class="fas fa-question-circle mr-1"></i>
                                        Fallback Message
                                    </label>
                                    <textarea class="form-control"
                                              id="ai_fallback_message"
                                              name="ai_fallback_message"
                                              rows="3"
                                              placeholder="Leave empty to use default fallback message">{{ $doctor->ai_chat_settings['fallback_message'] ?? '' }}</textarea>
                                    <small class="text-muted">
                                        Message shown when AI can't understand the patient's question.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Chat Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-md mr-2"></i>
                                            Manual Chat Management
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">
                                            <strong>Real-time Responses:</strong> When you reply to patients from the
                                            <a href="{{ route('doctor.chat.index') }}" class="text-decoration-none">
                                                Chat Management page
                                            </a>,
                                            your messages will appear instantly in the patient's chat widget.
                                        </p>
                                        <p class="mb-0">
                                            <strong>AI + Manual:</strong> You can have AI enabled and still send manual replies.
                                            Your manual messages will take priority and the AI will pause for that conversation.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Save Settings
                        </button>
                        <a href="{{ route('doctor.chat.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Chat Management
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const aiToggle = document.getElementById('ai_chat_enabled');
    const aiSettings = document.getElementById('ai-settings');

    aiToggle.addEventListener('change', function() {
        if (this.checked) {
            aiSettings.style.display = 'block';
        } else {
            aiSettings.style.display = 'none';
        }
    });
});
</script>
@endsection
