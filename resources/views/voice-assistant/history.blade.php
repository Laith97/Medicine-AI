@extends('master')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
</style>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="card-title h3 mb-2">🎤 Ambient Listening History</h1>
                            <p class="card-text mb-0">Review your previous ambient listening sessions</p>
                        </div>
                        <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-light">
                            <i class="fas fa-microphone me-2"></i>
                            New Session
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="row">
        <div class="col-12">
            @if($transcriptions->count() > 0)
                @foreach($transcriptions as $transcription)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        {{ $transcription->patient ? $transcription->patient->name : 'Unknown Patient' }}
                                    </h5>
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-calendar me-2"></i>
                                        {{ $transcription->session_started_at ? $transcription->session_started_at->format('M d, Y - H:i A') : 'Date not available' }}
                                    </p>
                                    @if($transcription->raw_transcription)
                                        <p class="card-text">
                                            <small class="text-muted">
                                                {{ Str::limit($transcription->raw_transcription, 150) }}
                                            </small>
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="mb-2">
                                        @if($transcription->status === 'completed')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                Completed
                                            </span>
                                        @elseif($transcription->status === 'active')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-pause me-1"></i>
                                                {{ ucfirst($transcription->status) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('ai.ambient-listening.show', $transcription) }}"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>
                                            View
                                        </a>
                                        @if($transcription->ai_analysis)
                                            <button class="btn btn-outline-info btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#analysisModal{{ $transcription->id }}">
                                                <i class="fas fa-robot me-1"></i>
                                                Analysis
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analysis Modal -->
                    @if($transcription->ai_analysis)
                        <div class="modal fade" id="analysisModal{{ $transcription->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-robot me-2"></i>
                                            AI Analysis - {{ $transcription->patient ? $transcription->patient->name : 'Unknown Patient' }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div style="white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
                                            {{ $transcription->ai_analysis }}
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="{{ route('ai.ambient-listening.show', $transcription) }}" class="btn btn-primary">
                                            View Full Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $transcriptions->links() }}
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-microphone fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Voice Sessions Yet</h4>
                        <p class="text-muted mb-4">You haven't recorded any voice consultations yet.</p>
                        <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-primary">
                            <i class="fas fa-microphone me-2"></i>
                            Start Your First Session
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
