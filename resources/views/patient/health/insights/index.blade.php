@extends('master')

@section('title', 'AI Health Insights')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="page-title">
                        <i class="fas fa-robot text-primary me-2" aria-hidden="true"></i>
                        AI Health Insights
                    </h1>
                    <p class="text-muted mb-0" id="page-subtitle">
                        Personalized analysis of your health patterns and trends
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('patient.health.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back
                    </a>
                    <button type="button" id="generateBtn" class="btn btn-primary" onclick="generateInsights()">
                        <i class="fas fa-brain me-1" aria-hidden="true"></i>
                        {{ $latestInsight ? 'Regenerate Insights' : 'Generate Insights' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Alert -->
        <div id="insightAlert" class="d-none alert" role="alert"></div>

        <div class="row">
            <!-- Main content -->
            <div class="col-lg-8">
                @if($latestInsight)
                    <div id="latestInsightSection">
                        @include('patient.health.insights.components.insight-card', ['insight' => $latestInsight, 'showRegenerate' => false])
                    </div>
                @else
                    <div id="emptyState" class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-brain fa-4x text-muted mb-3" aria-hidden="true"></i>
                            <h4 class="text-muted">No Insights Yet</h4>
                            <p class="text-muted mb-3">
                                Log some symptoms or medications first, then click "Generate Insights"
                                to get your personalized health analysis.
                            </p>
                            <a href="{{ route('patient.health.journal') }}" class="btn btn-outline-primary me-2">
                                <i class="fas fa-pen me-1" aria-hidden="true"></i>Log Symptoms
                            </a>
                            <a href="{{ route('patient.health.medications') }}" class="btn btn-outline-success">
                                <i class="fas fa-pills me-1" aria-hidden="true"></i>Track Medication
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Dynamically rendered new insight (hidden by default) -->
                <div id="dynamicInsightSection" class="d-none"></div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Past Insights -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2 text-secondary" aria-hidden="true"></i>
                            Past Insights
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($pastInsights->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($pastInsights->take(10) as $past)
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="me-auto">
                                                <p class="mb-1 small fw-bold text-truncate" style="max-width: 200px;">
                                                    {{ $past->summary ?? 'Insight generated' }}
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                                    {{ $past->created_at->format('M j, Y g:i A') }}
                                                </small>
                                            </div>
                                            @if($past->expires_at && $past->expires_at->isFuture())
                                                <span class="badge bg-success">Fresh</span>
                                            @else
                                                <span class="badge bg-secondary">Expired</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if($pastInsights->hasPages())
                                <div class="card-footer text-center">
                                    {{ $pastInsights->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted small mb-0">No past insights yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Info card -->
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                            How It Works
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="small text-muted mb-0 ps-3">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2" aria-hidden="true"></i>
                                AI analyzes your last 14 days of health data
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2" aria-hidden="true"></i>
                                Detects symptom trends and medication adherence patterns
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2" aria-hidden="true"></i>
                                Provides personalized tips and recommendations
                            </li>
                            <li>
                                <i class="fas fa-check text-success me-2" aria-hidden="true"></i>
                                Insights refresh daily and expire after 24 hours
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let isGenerating = false;

function esc(str) {
    if (str == null) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

function renderInsightCard(insight) {
    const content = insight.content || {};
    const patterns = content.patterns || [];
    const medInsight = content.medication_insight || null;
    const nextSteps = content.next_steps || [];

    const severityMap = {
        'info':    ['bg-info',        'fa-info-circle',         'Info'],
        'warning': ['bg-warning text-dark', 'fa-exclamation-triangle', 'Watch'],
        'alert':   ['bg-danger',      'fa-exclamation-circle',  'Alert'],
    };

    let patternsHtml = '';
    for (const p of patterns) {
        const cfg = severityMap[p.severity] || severityMap['info'];
        patternsHtml += `
        <div class="pattern-item mb-3 p-3 border rounded">
            <div class="d-flex align-items-start gap-2">
                <span class="badge ${cfg[0]} mt-1"><i class="fas ${cfg[1]} me-1" aria-hidden="true"></i>${esc(cfg[2])}</span>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${esc(p.title || 'Pattern')}</h6>
                    <p class="small text-muted mb-2">${esc(p.description || '')}</p>
                    ${p.recommendation ? `<p class="small mb-0"><i class="fas fa-lightbulb text-warning me-1" aria-hidden="true"></i><strong>Tip:</strong> ${esc(p.recommendation)}</p>` : ''}
                </div>
            </div>
        </div>`;
    }

    let medHtml = '';
    if (medInsight) {
        const rate = medInsight.adherence_rate || 0;
        const rateClass = rate >= 80 ? 'bg-success' : (rate >= 50 ? 'bg-warning text-dark' : 'bg-danger');
        const statusClass = { excellent: 'bg-success', good: 'bg-success', concerning: 'bg-warning text-dark' }[medInsight.overall_status] || 'bg-secondary';
        const statusLabel = (medInsight.overall_status || 'good').charAt(0).toUpperCase() + (medInsight.overall_status || 'good').slice(1);

        let medListHtml = '';
        for (const med of (medInsight.medications || [])) {
            const mr = med.adherence_rate || 0;
            const mrClass = mr >= 80 ? 'bg-success' : (mr >= 50 ? 'bg-warning text-dark' : 'bg-danger');
            const name = esc(med.Name || med.name || 'Medication');
            medListHtml += `<li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span><i class="fas fa-medkit text-muted me-2" aria-hidden="true"></i>${name}</span>
                <span class="badge ${mrClass}">${mr}%</span>
            </li>`;
        }

        medHtml = `
        <h5 class="mb-3"><i class="fas fa-pills text-success me-2" aria-hidden="true"></i>Medication Adherence</h5>
        <div class="card bg-light mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="h4 mb-0">${rate}%</span>
                    <span class="text-muted">adherence rate</span>
                    <span class="badge ${statusClass}">${statusLabel}</span>
                </div>
                ${medInsight.missed_doses > 0 ? `<p class="small text-muted mb-3"><i class="fas fa-exclamation-triangle text-warning me-1" aria-hidden="true"></i>${esc(medInsight.missed_doses)} missed dose(s) in the last 14 days</p>` : ''}
                ${medListHtml ? `<ul class="list-unstyled mb-0 small">${medListHtml}</ul>` : ''}
            </div>
        </div>`;
    }

    let stepsHtml = '';
    for (const step of nextSteps) {
        stepsHtml += `<li class="list-group-item small"><i class="fas fa-check text-success me-2" aria-hidden="true"></i>${esc(step)}</li>`;
    }

    return `
    <div class="insight-card border rounded-3 p-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-robot text-primary me-2" aria-hidden="true"></i>AI Health Insights</h4>
                <p class="text-muted small mb-0"><i class="fas fa-clock me-1" aria-hidden="true"></i>Generated just now — expires in 24 hours</p>
            </div>
        </div>
        ${content.summary ? `<div class="alert alert-light border mb-4" role="alert"><i class="fas fa-bullseye text-primary me-2" aria-hidden="true"></i><strong>${esc(content.summary)}</strong></div>` : ''}
        ${patternsHtml ? `<h5 class="mb-3"><i class="fas fa-chart-line text-secondary me-2" aria-hidden="true"></i>Patterns Detected</h5><div class="mb-4">${patternsHtml}</div>` : ''}
        ${medHtml}
        ${content.overall_assessment ? `<h5 class="mb-3"><i class="fas fa-stethoscope text-primary me-2" aria-hidden="true"></i>Overall Assessment</h5><p class="text-muted mb-4">${esc(content.overall_assessment)}</p>` : ''}
        ${stepsHtml ? `<h5 class="mb-3"><i class="fas fa-arrow-right text-primary me-2" aria-hidden="true"></i>Next Steps</h5><ul class="list-group mb-4">${stepsHtml}</ul>` : ''}
        <div class="alert alert-warning py-2 small mb-0" role="alert">
            <i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>
            <strong>Disclaimer:</strong> AI-generated insights are not a substitute for professional medical advice.
        </div>
    </div>`;
}

async function generateInsights() {
    if (isGenerating) return;

    const btn = document.getElementById('generateBtn');
    const alertEl = document.getElementById('insightAlert');

    isGenerating = true;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1" aria-hidden="true"></i>Generating...';

    try {
        const response = await fetch('/patient/health/insights/generate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Generation failed. Please try again.');
        }

        alertEl.className = 'alert alert-success';
        alertEl.innerHTML = '<i class="fas fa-check-circle me-2" aria-hidden="true"></i>New insight generated successfully!';
        alertEl.classList.remove('d-none');

        const emptyState = document.getElementById('emptyState');
        const latestSection = document.getElementById('latestInsightSection');
        if (emptyState) emptyState.classList.add('d-none');
        if (latestSection) latestSection.classList.add('d-none');

        const dynamicSection = document.getElementById('dynamicInsightSection');
        dynamicSection.classList.remove('d-none');
        dynamicSection.innerHTML = renderInsightCard(data.insight);

    } catch (err) {
        alertEl.className = 'alert alert-danger';
        alertEl.innerHTML = '<i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>' + esc(err.message);
        alertEl.classList.remove('d-none');
        // Debounce button on failure to prevent rapid retries
        btn.disabled = true;
        setTimeout(() => { btn.disabled = false; }, 10000);
    } finally {
        isGenerating = false;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo me-1" aria-hidden="true"></i>Regenerate Insights';
    }
}
</script>
@endpush

@push('styles')
<style>
.insight-card {
    border-color: #e9ecef !important;
}
.pattern-item {
    background: #fafbfc;
}
@media (max-width: 768px) {
    .dashboard-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }
}
</style>
@endpush
@endsection
