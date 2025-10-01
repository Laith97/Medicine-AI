@extends('master')

@section('title', 'My Diagnoses')

@section('content')
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">My Diagnoses</h2>
    <p>View your diagnoses</p>
</div>
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Page Header -->
            <div class="page-header text-center text-md-start mb-4">
                <h2><i class="fas fa-file-medical me-2"></i>My Diagnoses</h2>
                <p class="text-muted">View all diagnoses you've received from doctors</p>
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
                    <h5 class="mb-0">My Diagnoses ({{ $diagnoses->total() }})</h5>
                </div>
                <div class="card-body p-0">
                    @if($diagnoses->count() > 0)
                        <div class="row g-0">
                            @foreach($diagnoses as $diagnosis)
                                <div class="col-12">
                                    <div class="diagnosis-card border-bottom p-4 {{ !$diagnosis->patient_viewed_at ? 'bg-light' : '' }}">
                                        <div class="row align-items-center">
                                            <!-- Doctor Info -->
                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-md bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-user-md text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">Dr. {{ $diagnosis->doctor->name }}</h6>
                                                        <small class="text-muted">{{ $diagnosis->doctor->email }}</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Diagnosis Info -->
                                            <div class="col-md-4">
                                                <div class="diagnosis-preview">
                                                    <p class="mb-1 text-truncate">
                                                        {{ Str::limit($diagnosis->diagnosis_text, 100) }}
                                                    </p>
                                                    <div class="d-flex gap-2 mb-2">
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-user-md me-1"></i>
                                                            Doctor's Diagnosis
                                                        </span>
                                                        @if($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0)
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-robot me-1"></i>
                                                                AI Assisted
                                                            </span>
                                                        @endif
                                                        @if($diagnosis->follow_up_count > 0)
                                                            <span class="badge bg-secondary">
                                                                {{ $diagnosis->follow_up_count }} follow-ups
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Date & Status -->
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <div class="mb-2">
                                                        <strong>{{ $diagnosis->created_at->format('M j, Y') }}</strong><br>
                                                        <small class="text-muted">{{ $diagnosis->created_at->format('g:i A') }}</small>
                                                    </div>

                                                    <div class="status-badges">
                                                        @if(!$diagnosis->patient_viewed_at)
                                                            <span class="badge bg-warning mb-1">
                                                                <i class="fas fa-eye-slash me-1"></i>New
                                                            </span>
                                                        @endif

                                                        @if($diagnosis->patient_reviewed)
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-star me-1"></i>Reviewed
                                                            </span>
                                                        @elseif($diagnosis->patient_viewed_at)
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-star-half-alt me-1"></i>Review Pending
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="col-md-2 text-end">
                                                <div class="btn-group-vertical" role="group">
                                                    <a href="{{ route('diagnosis.patient.view', $diagnosis) }}"
                                                       class="btn btn-primary btn-sm mb-2">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>

                                                    @if($diagnosis->canAskFollowUp())
                                                        <span class="badge bg-secondary">
                                                            {{ 5 - $diagnosis->follow_up_count }} questions left
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Follow-up Preview -->
                                        @if($diagnosis->followUps->count() > 0)
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <div class="follow-up-preview bg-light p-3 rounded">
                                                        <h6 class="mb-2"><i class="fas fa-comments me-2"></i>Recent Follow-up</h6>
                                                        @php $lastFollowUp = $diagnosis->followUps->last(); @endphp
                                                        <div class="d-flex">
                                                            <div class="flex-grow-1">
                                                                <strong>Q:</strong> {{ Str::limit($lastFollowUp->question, 80) }}<br>
                                                                <strong>A:</strong> {{ Str::limit($lastFollowUp->ai_response, 100) }}
                                                            </div>
                                                            <small class="text-muted ms-3">
                                                                {{ $lastFollowUp->created_at->format('M j') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($diagnoses->hasPages())
                            <div class="card-footer">
                                {{ $diagnoses->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No diagnoses yet</h5>
                            <p class="text-muted">You haven't received any diagnoses from doctors yet.</p>
                            <p class="text-muted">When a doctor creates a diagnosis for you, it will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Help Section -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>How it works</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-user-md fa-2x text-primary mb-2"></i>
                            <h6>Doctor Creates Diagnosis</h6>
                            <p class="text-muted small">A doctor creates a diagnosis for you and you receive notifications</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-eye fa-2x text-success mb-2"></i>
                            <h6>View & Ask Questions</h6>
                            <p class="text-muted small">View your diagnosis and ask up to 5 follow-up questions using AI</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <h6>Rate & Review</h6>
                            <p class="text-muted small">Rate your experience and help other patients</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-md {
    width: 50px;
    height: 50px;
}

.diagnosis-card {
    transition: all 0.2s ease;
}

.diagnosis-card:hover {
    background-color: #f8f9fa !important;
}

.diagnosis-preview p {
    font-size: 0.9rem;
    line-height: 1.4;
}

.status-badges .badge {
    display: block;
    margin-bottom: 2px;
}

.follow-up-preview {
    border-left: 4px solid #17a2b8;
}

.follow-up-preview strong {
    color: #495057;
}

.btn-group-vertical .btn {
    border-radius: 0.375rem !important;
}

.badge {
    font-size: 0.75rem;
}
</style>
@endsection
