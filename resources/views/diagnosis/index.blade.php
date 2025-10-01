@extends('master')

@section('title', 'My Diagnoses')

@section('content')
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">Diagnosis</h2>
    <p>View and manage diagnoses</p>
</div>
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-clipboard-list me-2"></i>My Diagnoses</h2>
                    <p class="text-muted">Manage and view all diagnoses you've created</p>
                </div>
                <a href="{{ route('diagnosis.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Diagnosis
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Diagnoses List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Diagnoses ({{ $diagnoses->total() }})</h5>
                </div>
                <div class="card-body p-0">
                    @if($diagnoses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Patient</th>
                                        <th>Type</th>
                                        <th>Created</th>
                                        <th>Status</th>
                                        <th>Follow-ups</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($diagnoses as $diagnosis)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $diagnosis->patient->name }}</h6>
                                                        <small class="text-muted">{{ $diagnosis->patient->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $diagnosis->type === 'ai' ? 'info' : 'success' }}">
                                                    <i class="fas fa-{{ $diagnosis->type === 'ai' ? 'robot' : 'user-md' }} me-1"></i>
                                                    {{ ucfirst($diagnosis->type) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <small class="text-muted">{{ $diagnosis->created_at->format('M j, Y') }}</small><br>
                                                    <small class="text-muted">{{ $diagnosis->created_at->format('g:i A') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    @if($diagnosis->patient_viewed_at)
                                                        <span class="badge bg-success mb-1">
                                                            <i class="fas fa-eye me-1"></i>Viewed
                                                        </span>
                                                        <small class="text-muted">{{ $diagnosis->patient_viewed_at->format('M j, g:i A') }}</small>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i>Pending
                                                        </span>
                                                    @endif

                                                    @if($diagnosis->patient_reviewed)
                                                        <span class="badge bg-info mt-1">
                                                            <i class="fas fa-star me-1"></i>Reviewed
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <span class="badge bg-secondary">
                                                        {{ $diagnosis->follow_up_count }}/5
                                                    </span>
                                                    @if($diagnosis->follow_up_count > 0)
                                                        <br><small class="text-muted">questions</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('diagnosis.show', $diagnosis) }}"
                                                       class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($diagnosis->voice_file_path)
                                                        <button class="btn btn-sm btn-outline-info"
                                                                onclick="playVoice('{{ $diagnosis->id }}')" title="Play Voice">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($diagnoses->hasPages())
                            <div class="card-footer">
                                {{ $diagnoses->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No diagnoses yet</h5>
                            <p class="text-muted">Start by creating your first diagnosis for a patient.</p>
                            <a href="{{ route('diagnosis.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Diagnosis
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
}
</style>

<script>
function playVoice(diagnosisId) {
    // Create audio element
    const audio = new Audio();
    const voiceUrl = `/diagnosis/${diagnosisId}/voice`;

    // Set audio source
    audio.src = voiceUrl;

    // Add loading state
    const playButton = document.querySelector(`button[onclick="playVoice('${diagnosisId}')"]`);
    if (playButton) {
        const originalContent = playButton.innerHTML;
        playButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        playButton.disabled = true;

        // Reset button after audio ends or on error
        const resetButton = () => {
            playButton.innerHTML = originalContent;
            playButton.disabled = false;
        };

        audio.addEventListener('ended', resetButton);
        audio.addEventListener('error', () => {
            resetButton();
            alert('Error playing voice file. Please try again.');
        });

        audio.addEventListener('loadeddata', () => {
            resetButton();
        });
    }

    // Play the audio
    audio.play().catch(error => {
        console.error('Error playing audio:', error);
        if (playButton) {
            playButton.innerHTML = '<i class="fas fa-volume-up"></i>';
            playButton.disabled = false;
        }
        alert('Could not play voice file. Please check if the file exists.');
    });
}
</script>
@endsection
