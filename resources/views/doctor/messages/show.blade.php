@extends('master')

@section('title', $thread->subject)

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('doctor.messages.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h1 class="h3 mb-1">{{ $thread->subject }}</h1>
                    <p class="text-muted small mb-0">
                        Patient: {{ $thread->patient->name ?? 'Unknown' }}
                        @if($thread->type === 'follow_up' && $thread->diagnosis)
                            <span class="badge bg-info ms-1">Re: {{ Str::limit($thread->diagnosis->diagnosis_text, 50) }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- AI Suggestion Panel --}}
        @if(isset($pendingSuggestion) && $pendingSuggestion)
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i>AI Reply Suggestion</h5>
                    <div>
                        <form action="{{ route('doctor.messages.approve-suggestion', [$thread, $pendingSuggestion]) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light me-1">
                                <i class="fas fa-check me-1"></i>Accept & Send
                            </button>
                        </form>
                        <form action="{{ route('doctor.messages.reject-suggestion', [$thread, $pendingSuggestion]) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-times me-1"></i>Reject
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $pendingSuggestion->suggested_reply }}</p>
                </div>
            </div>
        @elseif(!$thread->isArchived() && $thread->messages()->byPatient()->exists())
            <div class="card border-secondary mb-4">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i>AI Reply Suggestion</h5>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-3">Need help drafting a reply? Let AI generate a suggestion for you.</p>
                    <form action="{{ route('doctor.messages.generate-suggestion', $thread) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-magic me-1"></i>Generate AI Suggestion
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body" style="max-height: 500px; overflow-y: auto;" id="messageContainer">
                @forelse($thread->messages as $message)
                    <div class="mb-3 {{ $message->sender_type === 'doctor' ? 'text-end' : 'text-start' }}">
                        <div class="d-flex {{ $message->sender_type === 'doctor' ? 'justify-content-end' : 'justify-content-start' }}">
                            <div style="max-width: 70%;">
                                <div class="p-3 rounded {{ $message->sender_type === 'doctor' ? 'bg-primary text-white' : ($message->sender_type === 'ai' ? 'bg-success text-white' : 'bg-light text-dark') }}">
                                    @if($message->sender_type === 'ai')
                                        <span class="badge bg-light text-success mb-1">AI Suggestion</span>
                                    @endif
                                    <p class="mb-1">{{ $message->body }}</p>

                                    @if($message->attachments->count() > 0)
                                        <div class="mt-2">
                                            @foreach($message->attachments as $attachment)
                                                <a href="{{ route('doctor.messages.attachment', $attachment) }}" class="btn btn-sm btn-outline-light" target="_blank">
                                                    <i class="fas fa-paperclip me-1"></i>{{ $attachment->original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <small class="text-muted d-block mt-1">
                                    {{ $message->sender_type === 'doctor' ? 'You' : ($thread->patient->name ?? 'Patient') }}
                                    — {{ $message->created_at->format('M j, Y g:i A') }}
                                </small>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">No messages yet.</p>
                @endforelse
            </div>

            @if(!$thread->isArchived())
            <div class="card-footer">
                <form action="{{ route('doctor.messages.reply', $thread) }}" method="POST" enctype="multipart/form-data" id="replyForm">
                    @csrf
                    <div class="mb-2">
                        <textarea name="body" class="form-control" rows="3" placeholder="Write your reply..." required maxlength="5000" id="replyBody"></textarea>
                    </div>
                    <div class="mb-2">
                        <input type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt">
                        <small class="text-muted">Max 10MB per file.</small>
                    </div>
                    <button type="submit" class="btn btn-primary" id="sendBtn">
                        <i class="fas fa-paper-plane me-1"></i>Send Reply
                    </button>
                </form>
            </div>
            @else
            <div class="card-footer">
                <div class="alert alert-secondary mb-0">This conversation is archived.</div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('messageContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
    var form = document.getElementById('replyForm');
    if (form) {
        form.addEventListener('submit', function() {
            var btn = document.getElementById('sendBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Sending...';
            }
        });
    }
});
</script>
@endpush
@endsection