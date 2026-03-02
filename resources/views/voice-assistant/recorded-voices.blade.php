@extends('master')

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
    content: '🎙️';
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
<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('ai.ambient-listening.index') }}">Ambient Listening</a></li>
        <li class="breadcrumb-item active" aria-current="page">History</li>
    </ol>
</nav>

<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Consultation History</h2>
            <p>View all your saved consultation recordings and transcripts</p>
        </div>
        <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-light btn-lg">
            <i class="fas fa-microphone me-2"></i>New Consultation
        </a>
    </div>
</div>
<div class="container-fluid py-4">
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
                                                    <a href="{{ route('ai.ambient-listening.show', $transcription) }}"
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
                            <h4 class="text-muted">No Session Recordings Yet</h4>
                            <p class="text-muted mb-4">Start ambient listening sessions to see them here.</p>
                            <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-primary">
                                <i class="fas fa-microphone me-2"></i>
                                Start Ambient Listening Session
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection