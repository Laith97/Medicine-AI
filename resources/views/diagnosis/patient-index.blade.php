@extends('master')

@section('title', 'My Diagnoses')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
/* Professional Dashboard Header */
.dashboard-header {
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.15);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #E8A0A0 0%, #DE6262 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 600;
    font-size: 2.2rem;
    margin-bottom: 0.5rem;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    font-weight: 400;
    margin-bottom: 0;
}

/* Professional Cards */
.professional-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.professional-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* Diagnosis Card */
.diagnosis-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.diagnosis-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.diagnosis-card.unread {
    border-left: 4px solid #DE6262;
}

.diagnosis-card.unread .card-body {
    background-color: rgba(222, 98, 98, 0.05);
}

/* Doctor Avatar */
.doctor-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
}

/* Info Items */
.info-item {
    display: flex;
    align-items: center;
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.info-item i {
    width: 20px;
    margin-right: 0.5rem;
    color: #94a3b8;
}

/* Status Badges */
.badge-professional {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-new {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.badge-reviewed {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.badge-pending {
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.badge-info {
    background-color: #fce8e8;
    color: #DE6262;
    border: 1px solid #f5c0c0;
}

.badge-secondary {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Diagnosis Preview */
.diagnosis-preview {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.5;
}

.diagnosis-preview p {
    margin-bottom: 0.5rem;
}

/* Follow-up Preview */
.follow-up-preview {
    background: #f8fafc;
    border-left: 4px solid #DE6262;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
    margin-top: 1rem;
}

.follow-up-preview h6 {
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.follow-up-preview .question {
    color: #475569;
    font-size: 0.875rem;
}

.follow-up-preview .response {
    color: #DE6262;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Professional Buttons */
.btn-primary-professional {
    background-color: #DE6262;
    border-color: #DE6262;
    color: white;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-primary-professional:hover {
    background-color: #D64A4A;
    border-color: #D64A4A;
    color: white;
    transform: translateY(-1px);
}

/* Help Section */
.help-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.help-card-header {
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    color: white;
    padding: 1rem 1.5rem;
}

.help-card-header h6 {
    margin: 0;
    font-weight: 600;
}

.help-step {
    text-align: center;
    padding: 1.5rem;
}

.help-step i {
    font-size: 2rem;
    color: #DE6262;
    margin-bottom: 1rem;
}

.help-step h6 {
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.help-step p {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-state h5 {
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #adb5bd;
    margin-bottom: 0.5rem;
}

/* Pagination */
.pagination-wrapper .pagination {
    border-radius: 8px;
    overflow: hidden;
}

.pagination-wrapper .page-link {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    color: #495057;
    background: #ffffff;
}

.pagination-wrapper .page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #495057;
}

.pagination-wrapper .page-item.active .page-link {
    background-color: #DE6262;
    border-color: #DE6262;
    color: white;
}

/* Alert Styles */
.alert-success {
    background-color: #d1fae5;
    border-color: #a7f3d0;
    color: #065f46;
    border-radius: 12px;
}

/* Background */
.page-background {
    background-color: #f8f9fa;
    min-height: 100vh;
}
</style>
@endpush

@section('content')
<div class="page-background">
    <div class="container py-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-clipboard-list me-2"></i>My Diagnoses</h2>
                    <p class="text-muted mb-0">View your medical diagnoses and track your health history</p>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Diagnoses List -->
        @if($diagnoses->count() > 0)
            <div class="row">
                @foreach($diagnoses as $diagnosis)
                    <div class="col-12 mb-4">
                        <div class="diagnosis-card {{ !$diagnosis->patient_viewed_at ? 'unread' : '' }}">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <!-- Doctor Info -->
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="doctor-avatar me-3">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-dark fw-semibold">Dr. {{ $diagnosis->doctor->name }}</h6>
                                                <small class="text-muted">{{ Str::limit($diagnosis->doctor->email, 25) }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Diagnosis Info -->
                                    <div class="col-md-4">
                                        <div class="diagnosis-preview">
                                            <p class="mb-2">
                                                <i class="fas fa-file-medical text-primary me-2"></i>
                                                {{ Str::limit($diagnosis->diagnosis_text, 80) }}
                                            </p>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <span class="badge badge-info">
                                                    <i class="fas fa-user-md me-1"></i>Doctor's Diagnosis
                                                </span>
                                                @if($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0)
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-robot me-1"></i>AI Assisted
                                                    </span>
                                                @endif
                                                @if($diagnosis->follow_up_count > 0)
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-comments me-1"></i>{{ $diagnosis->follow_up_count }} follow-ups
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date & Status -->
                                    <div class="col-md-3 text-center">
                                        <div class="mb-2">
                                            <strong class="text-dark">{{ $diagnosis->created_at->format('M j, Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $diagnosis->created_at->format('g:i A') }}</small>
                                        </div>

                                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                                            @if(!$diagnosis->patient_viewed_at)
                                                <span class="badge badge-new">
                                                    <i class="fas fa-eye-slash me-1"></i>New
                                                </span>
                                            @endif

                                            @if($diagnosis->patient_reviewed)
                                                <span class="badge badge-reviewed">
                                                    <i class="fas fa-star me-1"></i>Reviewed
                                                </span>
                                            @elseif($diagnosis->patient_viewed_at)
                                                <span class="badge badge-pending">
                                                    <i class="fas fa-star-half-alt me-1"></i>Review Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="col-md-2 text-end">
                                        <a href="{{ route('diagnosis.patient.view', $diagnosis) }}"
                                           class="btn btn-primary-professional mb-2 w-100">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>

                                        @if($diagnosis->canAskFollowUp())
                                            <small class="text-muted">
                                                <i class="fas fa-question-circle me-1"></i>
                                                {{ 5 - $diagnosis->follow_up_count }} questions left
                                            </small>
                                        @endif
                                    </div>
                                </div>

                                <!-- Quick Follow-up Preview -->
                                @if($diagnosis->followUps->count() > 0)
                                    <div class="follow-up-preview">
                                        <h6><i class="fas fa-comments me-2"></i>Recent Follow-up</h6>
                                        @php $lastFollowUp = $diagnosis->followUps->last(); @endphp
                                        <div class="row">
                                            <div class="col-10">
                                                <div class="question">
                                                    <strong>Q:</strong> {{ Str::limit($lastFollowUp->question, 80) }}
                                                </div>
                                                <div class="response">
                                                    <strong>A:</strong> {{ Str::limit($lastFollowUp->ai_response, 100) }}
                                                </div>
                                            </div>
                                            <div class="col-2 text-end">
                                                <small class="text-muted">{{ $lastFollowUp->created_at->format('M j') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($diagnoses->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    <div class="pagination-wrapper">
                        {{ $diagnoses->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-file-medical"></i>
                <h5>No Diagnoses Yet</h5>
                <p class="text-muted">You haven't received any diagnoses from doctors yet.</p>
                <p class="text-muted small">When a doctor creates a diagnosis for you, it will appear here.</p>
            </div>
        @endif

        <!-- Help Section -->
        <div class="help-card mt-4">
            <div class="help-card-header">
                <h6><i class="fas fa-question-circle me-2"></i>How it works</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="help-step">
                            <i class="fas fa-user-md"></i>
                            <h6>Doctor Creates Diagnosis</h6>
                            <p>A doctor creates a diagnosis for you and you'll receive notifications</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-step">
                            <i class="fas fa-eye"></i>
                            <h6>View & Ask Questions</h6>
                            <p>View your diagnosis and ask up to 5 follow-up questions using AI</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-step">
                            <i class="fas fa-star"></i>
                            <h6>Rate & Review</h6>
                            <p>Rate your experience and help other patients make informed decisions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
