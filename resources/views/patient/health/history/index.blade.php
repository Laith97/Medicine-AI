@extends('master')

@section('title', 'Health Journal History')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="page-title">
                        <i class="fas fa-history text-info me-2" aria-hidden="true"></i>
                        Journal History
                    </h1>
                    <p class="text-muted mb-0" id="page-subtitle">Your past health journal entries</p>
                </div>
                <a href="{{ route('patient.health.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back to Dashboard
                </a>
            </div>
        </div>

        @if($journals->count() > 0)
            <div class="row">
                @foreach($journals as $journal)
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-day me-2 text-primary" aria-hidden="true"></i>
                                    {{ $journal->entry_date->format('l, M j, Y') }}
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($journal->symptoms && count($journal->symptoms) > 0)
                                    <h6 class="text-muted small mb-2">Symptoms:</h6>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        @foreach($journal->symptoms as $index => $symptom)
                                            @php
                                                $severity = $journal->severity[$symptom] ?? null;
                                                $badgeColor = match(true) {
                                                    $severity >= 4 => 'danger',
                                                    $severity >= 3 => 'warning',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeColor }}"
                                                  title="Severity: {{ $severity ?? 'N/A' }}/5">
                                                {{ $symptom }}
                                                @if($severity)
                                                    <span class="ms-1">({{ $severity }})</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-3">No symptoms logged</p>
                                @endif

                                @if($journal->notes)
                                    <h6 class="text-muted small mb-1">Notes:</h6>
                                    <p class="small mb-0">{{ $journal->notes }}</p>
                                @endif
                            </div>
                            <div class="card-footer text-muted small">
                                <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                Logged {{ $journal->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center">
                {{ $journals->links() }}
            </div>
        @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-book fa-4x text-muted mb-3" aria-hidden="true"></i>
                    <h4 class="text-muted">No Entries Yet</h4>
                    <p class="text-muted">Start logging your daily health to see your history here.</p>
                    <a href="{{ route('patient.health.journal') }}" class="btn btn-primary">
                        <i class="fas fa-pen me-1" aria-hidden="true"></i>Log First Entry
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
