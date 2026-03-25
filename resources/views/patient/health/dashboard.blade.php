@extends('master')

@section('title', 'Health Tracking Dashboard')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="page-title">
                        <i class="fas fa-heartbeat text-danger me-2" aria-hidden="true"></i>
                        Health Tracking
                    </h1>
                    <p class="text-muted mb-0" id="page-subtitle">Monitor your daily health and medication adherence</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('patient.health.journal') }}" class="btn btn-primary">
                        <i class="fas fa-pen me-1" aria-hidden="true"></i>
                        {{ $todayJournal ? 'Update Journal' : 'Log Symptoms' }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                        <i class="fas fa-heartbeat" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number">
                        {{ $todayJournal ? count($todayJournal->symptoms ?? []) : '0' }}
                    </div>
                    <div class="stats-label">Symptoms Logged Today</div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);">
                        <i class="fas fa-pills" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number">{{ $todayMedications->count() }}</div>
                    <div class="stats-label">Medications Today</div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);">
                        <i class="fas fa-fire" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number">{{ $adherenceStreak }}</div>
                    <div class="stats-label">Day Adherence Streak</div>
                </div>
            </div>
        </div>

        <!-- AI Insights Panel -->
        @if($latestHealthInsight)
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-robot me-2" aria-hidden="true"></i>
                        AI Health Insight
                    </h5>
                    <a href="{{ route('patient.health.insights') }}" class="btn btn-sm btn-light">
                        View Full Insights <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-primary mb-2">{{ $latestHealthInsight->summary }}</h6>
                            <p class="small text-muted mb-0">
                                Generated {{ $latestHealthInsight->created_at->diffForHumans() }}
                                @if($latestHealthInsight->expires_at && $latestHealthInsight->expires_at->isFuture())
                                    — expires {{ $latestHealthInsight->expires_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card border-dashed mb-4 text-center py-4">
                <div class="card-body">
                    <i class="fas fa-brain fa-2x text-muted mb-2" aria-hidden="true"></i>
                    <h6 class="text-muted">Get AI Health Insights</h6>
                    <p class="small text-muted mb-3">Personalized analysis of your health patterns</p>
                    <a href="{{ route('patient.health.insights') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-robot me-1" aria-hidden="true"></i>Generate Insights
                    </a>
                </div>
            </div>
        @endif

        <div class="row">
            <!-- Today's Medication Status -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-pills me-2 text-success" aria-hidden="true"></i>
                            Today's Medications
                        </h5>
                        <a href="{{ route('patient.health.medications') }}" class="btn btn-sm btn-outline-primary">
                            Manage
                        </a>
                    </div>
                    <div class="card-body">
                        @if($todayMedications->count() > 0)
                            @foreach($todayMedications as $item)
                                @php
                                    $schedule = $item['schedule'];
                                    $log = $item['log'];
                                    $isTaken = $log && $log->taken_at;
                                    $isSkipped = $log && $log->skipped;
                                @endphp
                                <div class="medication-item mb-3 p-3 border rounded {{ $isTaken ? 'border-success bg-light' : ($isSkipped ? 'border-warning bg-light' : '') }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 {{ $isTaken ? 'text-success' : ($isSkipped ? 'text-warning' : '') }}">
                                                {{ $schedule->medication_name }}
                                            </h6>
                                            <p class="small text-muted mb-1">{{ $schedule->dosage }} — {{ $schedule->frequency }}</p>
                                            @if($schedule->time_of_day)
                                                <p class="small text-muted mb-0"><i class="fas fa-clock me-1" aria-hidden="true"></i>{{ $schedule->time_of_day }}</p>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            @if($isTaken)
                                                <span class="badge bg-success"><i class="fas fa-check me-1" aria-hidden="true"></i>Taken</span>
                                                <p class="small text-muted mb-0 mt-1">{{ $log->taken_at->format('g:i A') }}</p>
                                            @elseif($isSkipped)
                                                <span class="badge bg-warning text-dark"><i class="fas fa-minus me-1" aria-hidden="true"></i>Skipped</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-pills fa-3x text-muted mb-3" aria-hidden="true"></i>
                                <h5 class="text-muted">No Medications</h5>
                                <p class="text-muted">Add your medications to start tracking.</p>
                                <a href="{{ route('patient.health.medications') }}" class="btn btn-sm btn-primary">
                                    Add Medication
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Journal Entries -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-book-medical me-2 text-primary" aria-hidden="true"></i>
                            Recent Journal Entries
                        </h5>
                        <a href="{{ route('patient.health.history') }}" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        @if($recentJournals->count() > 0)
                            @foreach($recentJournals as $journal)
                                <div class="journal-entry mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $journal->entry_date->format('l, M j, Y') }}</h6>
                                            @if($journal->symptoms && count($journal->symptoms) > 0)
                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    @foreach($journal->symptoms as $symptom)
                                                        <span class="badge bg-secondary">{{ $symptom }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="small text-muted mb-2">No symptoms logged</p>
                                            @endif
                                            @if($journal->notes)
                                                <p class="small text-muted mb-0">{{ Str::limit($journal->notes, 100) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3" aria-hidden="true"></i>
                                <h5 class="text-muted">No Entries Yet</h5>
                                <p class="text-muted">Start logging your daily symptoms.</p>
                                <a href="{{ route('patient.health.journal') }}" class="btn btn-sm btn-primary">
                                    Log First Entry
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    text-align: center;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin: 0 auto 1rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2d3748;
    margin: 0;
}

.stats-label {
    color: #718096;
    font-size: 0.9rem;
    margin: 0;
}

.medication-item, .journal-entry {
    transition: all 0.2s ease;
}

.medication-item:hover, .journal-entry:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .dashboard-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }

    .stats-card {
        padding: 1rem;
    }

    .stats-number {
        font-size: 1.5rem;
    }
}
</style>
@endpush
@endsection
