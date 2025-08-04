@extends('master')

@section('title', 'Diagnosis Details')

@section('content')
<div class="container-fluid px-2 px-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-clipboard-check me-2"></i>Diagnosis Details</h2>
                    <p class="text-muted">Created on {{ $diagnosis->created_at->format('F j, Y \a\t g:i A') }}</p>
                </div>
                <a href="{{ route('diagnosis.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Patient Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Patient Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-lg bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">{{ $diagnosis->patient->name }}</h5>
                                    <p class="text-muted mb-0">{{ $diagnosis->patient->email }}</p>
                                    @if($diagnosis->patient->phone)
                                        <p class="text-muted mb-0">{{ $diagnosis->patient->phone }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="patient-details">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Age:</strong> {{ $diagnosis->patient->age ?? 'N/A' }}
                                    </div>
                                    <div class="col-6">
                                        <strong>Gender:</strong> {{ ucfirst($diagnosis->patient->gender ?? 'N/A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagnosis Information -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Diagnosis</h5>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-{{ $diagnosis->type === 'ai' ? 'robot' : 'user-md' }} me-1"></i>
                            {{ ucfirst($diagnosis->type) }} Diagnosis
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="diagnosis-content mb-4">
                        <h6>Diagnosis Text:</h6>
                        <div class="bg-light p-3 rounded">
                            {!! nl2br(e($diagnosis->diagnosis_text)) !!}
                        </div>
                    </div>

                    @if($diagnosis->voice_transcript && $diagnosis->voice_transcript !== $diagnosis->diagnosis_text)
                        <div class="voice-transcript mb-4">
                            <h6><i class="fas fa-microphone me-2"></i>Voice Transcript:</h6>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                {!! nl2br(e($diagnosis->voice_transcript)) !!}
                            </div>
                            @if($diagnosis->voice_file_path)
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-info" onclick="playVoiceFile()">
                                        <i class="fas fa-play me-1"></i>Play Original Recording
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($diagnosis->ai_response)
                        <div class="ai-response">
                            <h6><i class="fas fa-robot me-2"></i>AI Analysis:</h6>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                {!! nl2br(e($diagnosis->ai_response)) !!}
                            </div>
                        </div>
                    @endif

                    @if($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0)
                        <hr>
                        <div class="ai-assistant-results">
                            <h6><i class="fas fa-robot me-2"></i>AI Assistant Analysis</h6>
                            @foreach($diagnosis->aiAssistantResults as $index => $result)
                                <div class="ai-assistant-result mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 text-info">
                                            <i class="fas fa-robot me-1"></i>
                                            AI Analysis {{ $index + 1 }}
                                        </h6>
                                        <small class="text-muted">{{ $result->created_at->format('M d, Y H:i A') }}</small>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        {!! nl2br($result->ai_analysis) !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Patient Data -->
            @if($diagnosis->patient_data)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Additional Patient Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($diagnosis->patient_data as $key => $value)
                                @if($value)
                            <div class="col-md-6 mb-3">
                                <h6 class="text-capitalize">{{ str_replace('_', ' ', $key) }}</h6>
                                <div class="bg-light p-2 rounded">
                                    @if(is_array($value))
                                        @php
                                            // Check if this is the symptoms field and contains IDs
                                            $isSymptomsField = ($key === 'symptoms');
                                            $symptomNames = [];
                                        @endphp
                                        @foreach($value as $subKey => $subValue)
                                            @php
                                                // If this is the symptoms field and the value is a numeric ID, look up the symptom name
                                                if ($isSymptomsField && is_numeric($subValue)) {
                                                    $symptom = \App\Models\Symptom::find($subValue);
                                                    if ($symptom) {
                                                        $subValue = $symptom->name;
                                                    } else {
                                                        // Debug: Show that symptom was not found
                                                        $subValue = "[ID:{$subValue} - Not Found]";
                                                    }
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <strong>{{ is_string($subKey) ? str_replace('_', ' ', ucfirst($subKey)) : 'Item ' . ($subKey + 1) }}:</strong>
                                                @if(is_array($subValue))
                                                    <div class="ms-3">
                                                        @foreach($subValue as $nestedKey => $nestedValue)
                                                            @php
                                                                // Also check for symptom IDs in nested arrays
                                                                if ($isSymptomsField && is_numeric($nestedValue)) {
                                                                    $symptom = \App\Models\Symptom::find($nestedValue);
                                                                    if ($symptom) {
                                                                        $nestedValue = $symptom->name;
                                                                    } else {
                                                                        // Debug: Show that symptom was not found
                                                                        $nestedValue = "[ID:{$nestedValue} - Not Found]";
                                                                    }
                                                                }
                                                            @endphp
                                                            <div>
                                                                <strong>{{ is_string($nestedKey) ? str_replace('_', ' ', ucfirst($nestedKey)) : 'Item ' . ($nestedKey + 1) }}:</strong>
                                                                {{ is_array($nestedValue) ? json_encode($nestedValue) : $nestedValue }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    {{ $subValue }}
                                                @endif
                                            </div>
                                        @endforeach
                                    @elseif($key === 'symptoms' && is_string($value))
                                        @php
                                            // Handle symptoms that might be stored as a JSON string
                                            $symptomsArray = json_decode($value, true);
                                            if (is_array($symptomsArray)) {
                                                // Process each symptom ID to get the text value
                                                $processedSymptoms = [];
                                                foreach ($symptomsArray as $symptomId) {
                                                    if (is_numeric($symptomId)) {
                                                        $symptom = \App\Models\Symptom::find($symptomId);
                                                        if ($symptom) {
                                                            $processedSymptoms[] = $symptom->name;
                                                        } else {
                                                            // Debug: Show that symptom was not found
                                                            $processedSymptoms[] = "[ID:{$symptomId} - Not Found]";
                                                        }
                                                    } else {
                                                        // This is already a text symptom
                                                        $processedSymptoms[] = $symptomId;
                                                    }
                                                }
                                                $value = implode(', ', $processedSymptoms);
                                            }
                                        @endphp
                                        {{ $value }}
                                    @else
                                        @php
                                            // Check if this is the symptoms field and the value is a numeric ID
                                            if ($key === 'symptoms' && is_numeric($value)) {
                                                $symptom = \App\Models\Symptom::find($value);
                                                if ($symptom) {
                                                    $value = $symptom->name;
                                                }
                                            }
                                        @endphp
                                        {{ $value }}
                                    @endif
                                </div>
                            </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Patient Status & Activity -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Patient Activity</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="activity-stat">
                                <i class="fas fa-eye fa-2x {{ $diagnosis->patient_viewed_at ? 'text-success' : 'text-muted' }} mb-2"></i>
                                <h6>Viewed</h6>
                                @if($diagnosis->patient_viewed_at)
                                    <small class="text-success">{{ $diagnosis->patient_viewed_at->format('M j, g:i A') }}</small>
                                @else
                                    <small class="text-muted">Not viewed yet</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="activity-stat">
                                <i class="fas fa-comments fa-2x {{ $diagnosis->follow_up_count > 0 ? 'text-info' : 'text-muted' }} mb-2"></i>
                                <h6>Follow-ups</h6>
                                <small class="text-muted">{{ $diagnosis->follow_up_count }}/5 questions asked</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="activity-stat">
                                <i class="fas fa-star fa-2x {{ $diagnosis->patient_reviewed ? 'text-warning' : 'text-muted' }} mb-2"></i>
                                <h6>Review</h6>
                                @if($diagnosis->patient_reviewed)
                                    <small class="text-success">Reviewed</small>
                                @else
                                    <small class="text-muted">Not reviewed</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="activity-stat">
                                <i class="fas fa-bell fa-2x {{ $diagnosis->patient_notified ? 'text-success' : 'text-muted' }} mb-2"></i>
                                <h6>Notified</h6>
                                @if($diagnosis->patient_notified)
                                    <small class="text-success">Patient notified</small>
                                @else
                                    <small class="text-muted">Not notified</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Follow-up Questions -->
            @if($diagnosis->followUps->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Patient Follow-up Questions ({{ $diagnosis->followUps->count() }})</h5>
                    </div>
                    <div class="card-body">
                        @foreach($diagnosis->followUps as $followUp)
                            <div class="follow-up-item mb-4 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Patient Question</h6>
                                    <small class="text-muted">{{ $followUp->created_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                                <div class="question mb-3 p-2 bg-light rounded">
                                    {{ $followUp->question }}
                                </div>

                                <h6 class="mb-2"><i class="fas fa-robot me-2 text-info"></i>AI Response</h6>
                                <div class="answer p-2 bg-info bg-opacity-10 rounded">
                                    {!! nl2br(e($followUp->ai_response)) !!}
                                </div>

                                @if($followUp->usage_data)
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Tokens used: {{ $followUp->usage_data['tokens_used'] ?? 'N/A' }}
                                        </small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="btn-group" role="group">
                        <a href="{{ route('diagnosis.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        @if(!$diagnosis->patient_notified && $diagnosis->patient->email)
                            <button class="btn btn-info" onclick="resendNotification()">
                                <i class="fas fa-envelope me-2"></i>Resend Notification
                            </button>
                        @endif
                        <button class="btn btn-primary" onclick="copyDiagnosisLink()">
                            <i class="fas fa-link me-2"></i>Copy Patient Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-lg {
    width: 60px;
    height: 60px;
}

.activity-stat {
    padding: 1rem;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.follow-up-item {
    background-color: #f8f9fa;
}

.diagnosis-content {
    font-size: 1.05rem;
    line-height: 1.6;
}
</style>

<script>
function playVoiceFile() {
    // This would need to be implemented to play the actual voice file
    alert('Voice playback feature would be implemented here');
}

function resendNotification() {
    if (confirm('Are you sure you want to resend the notification to the patient?')) {
        // This would need to be implemented
        alert('Notification resend feature would be implemented here');
    }
}

function copyDiagnosisLink() {
    const link = '{{ route("diagnosis.patient.view", $diagnosis) }}';
    navigator.clipboard.writeText(link).then(function() {
        alert('Patient link copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
        alert('Failed to copy link. Please copy manually: ' + link);
    });
}
</script>
@endsection
