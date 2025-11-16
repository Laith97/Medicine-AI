@extends('master')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Recorded Voices History
                    </h1>
                    <p class="text-muted mb-0">View all your saved voice transcriptions and sessions</p>
                </div>
                <a href="{{ route('ai.voice-assistant.index') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    New Recording
                </a>
            </div>
        </div>
    </div>

    <!-- Transcriptions List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($transcriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Transcription</th>
                                        <th>Status</th>
                                        <th>Duration</th>
                                        <th>Recorded At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transcriptions as $transcription)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-3">
                                                        <span class="avatar-title rounded-circle bg-primary text-white">
                                                            {{ substr($transcription->patient->name ?? 'N/A', 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $transcription->patient->name ?? 'Unknown Patient' }}</h6>
                                                        <small class="text-muted">{{ $transcription->patient->email ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;" title="{{ $transcription->raw_transcription }}">
                                                    {{ Str::limit($transcription->raw_transcription, 50) }}
                                                </div>
                                            </td>
                                            <td>
                                                @switch($transcription->status)
                                                    @case('active')
                                                        <span class="badge bg-success">Active</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge bg-primary">Completed</span>
                                                        @break
                                                    @case('ai_analysis_complete')
                                                        <span class="badge bg-info">AI Analyzed</span>
                                                        @break
                                                    @case('diagnosis_created')
                                                        <span class="badge bg-success">Diagnosis Created</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($transcription->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($transcription->session_started_at && $transcription->session_ended_at)
                                                    {{ $transcription->session_started_at->diffInSeconds($transcription->session_ended_at) }}s
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $transcription->created_at->format('M d, Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('ai.voice-assistant.show', $transcription) }}"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($transcription->diagnosis)
                                                        <a href="{{ route('diagnosis.show', $transcription->diagnosis) }}"
                                                           class="btn btn-sm btn-outline-success"
                                                           title="View Diagnosis">
                                                            <i class="fas fa-file-medical"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $transcriptions->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-microphone-slash fa-4x text-muted"></i>
                            </div>
                            <h4 class="text-muted">No Recorded Voices Yet</h4>
                            <p class="text-muted mb-4">Start recording voice consultations to see them here.</p>
                            <a href="{{ route('ai.voice-assistant.index') }}" class="btn btn-primary">
                                <i class="fas fa-microphone me-2"></i>
                                Start Recording
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection