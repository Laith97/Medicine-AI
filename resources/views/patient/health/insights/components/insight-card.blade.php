{{-- resources/views/patient/health/insights/components/insight-card.blade.php --}}
@php
    $content = $insight->content ?? [];
    $patterns = $content['patterns'] ?? [];
    $medInsight = $content['medication_insight'] ?? null;
    $nextSteps = $content['next_steps'] ?? [];
    $severityConfig = [
        'info' => ['class' => 'bg-info', 'icon' => 'fa-info-circle', 'label' => 'Info'],
        'warning' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-exclamation-triangle', 'label' => 'Watch'],
        'alert' => ['class' => 'bg-danger', 'icon' => 'fa-exclamation-circle', 'label' => 'Alert'],
        'good' => ['class' => 'bg-success', 'icon' => 'fa-check-circle', 'label' => 'Good'],
    ];
@endphp

<div class="insight-card border rounded-3 p-4 mb-4 bg-white">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-robot text-primary me-2" aria-hidden="true"></i>
                AI Health Insights
            </h4>
            <p class="text-muted small mb-0">
                Generated {{ $insight->created_at->diffForHumans() }}
                @if($insight->expires_at && $insight->expires_at->isFuture())
                    <span class="ms-2">
                        <i class="fas fa-clock me-1" aria-hidden="true"></i>
                        Expires {{ $insight->expires_at->diffForHumans() }}
                    </span>
                @endif
            </p>
        </div>
        @if(isset($showRegenerate) && $showRegenerate)
            <button type="button" id="regenerateBtn" class="btn btn-outline-primary btn-sm" onclick="generateInsights()">
                <i class="fas fa-redo me-1" aria-hidden="true"></i>Regenerate
            </button>
        @endif
    </div>

    {{-- Summary --}}
    @if(!empty($content['summary']))
        <div class="alert alert-light border mb-4" role="alert">
            <i class="fas fa-bullseye text-primary me-2" aria-hidden="true"></i>
            <strong>{{ $content['summary'] }}</strong>
        </div>
    @endif

    {{-- Patterns --}}
    @if(!empty($patterns))
        <h5 class="mb-3">
            <i class="fas fa-chart-line text-secondary me-2" aria-hidden="true"></i>
            Patterns Detected
        </h5>
        <div class="mb-4">
            @foreach($patterns as $pattern)
                @php
                    $cfg = $severityConfig[$pattern['severity']] ?? $severityConfig['info'];
                @endphp
                <div class="pattern-item mb-3 p-3 border rounded {{ $pattern['severity'] === 'alert' ? 'border-danger' : ($pattern['severity'] === 'warning' ? 'border-warning' : 'border-light') }}">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge {{ $cfg['class'] }} mt-1">
                            <i class="fas {{ $cfg['icon'] }} me-1" aria-hidden="true"></i>
                            {{ $cfg['label'] }}
                        </span>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $pattern['title'] ?? 'Pattern' }}</h6>
                            <p class="small text-muted mb-2">{{ $pattern['description'] ?? '' }}</p>
                            @if(!empty($pattern['recommendation']))
                                <p class="small mb-0">
                                    <i class="fas fa-lightbulb text-warning me-1" aria-hidden="true"></i>
                                    <strong>Tip:</strong> {{ $pattern['recommendation'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Medication Insight --}}
    @if($medInsight)
        <h5 class="mb-3">
            <i class="fas fa-pills text-success me-2" aria-hidden="true"></i>
            Medication Adherence
        </h5>
        <div class="card bg-light mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="h4 mb-0">
                            {{ $medInsight['adherence_rate'] ?? 0 }}%
                        </span>
                        <span class="text-muted ms-2">adherence rate</span>
                    </div>
                    @php
                        $statusConfig = [
                            'excellent' => ['class' => 'text-success', 'label' => 'Excellent'],
                            'good' => ['class' => 'text-info', 'label' => 'Good'],
                            'concerning' => ['class' => 'text-warning', 'label' => 'Needs Attention'],
                        ];
                        $status = $medInsight['overall_status'] ?? 'good';
                        $sCfg = $statusConfig[$status] ?? $statusConfig['good'];
                    @endphp
                    <span class="badge bg-{{ $status === 'excellent' || $status === 'good' ? 'success' : ($status === 'concerning' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($status) }}
                    </span>
                </div>

                @if($medInsight['missed_doses'] > 0)
                    <p class="small text-muted mb-3">
                        <i class="fas fa-exclamation-triangle text-warning me-1" aria-hidden="true"></i>
                        {{ $medInsight['missed_doses'] }} missed dose(s) in the last 14 days
                    </p>
                @endif

                @if(!empty($medInsight['medications']))
                    <ul class="list-unstyled mb-0 small">
                        @foreach($medInsight['medications'] as $med)
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span>
                                    <i class="fas fa-medkit text-muted me-2" aria-hidden="true"></i>
                                    {{ $med['name'] ?? $med['Name'] ?? 'Medication' }}
                                </span>
                                <span class="badge bg-{{ ($med['adherence_rate'] ?? $med['adherence_rate'] ?? 0) >= 80 ? 'success' : (($med['adherence_rate'] ?? $med['adherence_rate'] ?? 0) >= 50 ? 'warning' : 'danger') }}">
                                    {{ $med['adherence_rate'] ?? $med['adherence_rate'] ?? 0 }}%
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    {{-- Overall Assessment --}}
    @if(!empty($content['overall_assessment']))
        <h5 class="mb-3">
            <i class="fas fa-stethoscope text-primary me-2" aria-hidden="true"></i>
            Overall Assessment
        </h5>
        <p class="text-muted mb-4">{{ $content['overall_assessment'] }}</p>
    @endif

    {{-- Next Steps --}}
    @if(!empty($nextSteps))
        <h5 class="mb-3">
            <i class="fas fa-arrow-right text-primary me-2" aria-hidden="true"></i>
            Next Steps
        </h5>
        <ul class="list-group mb-4">
            @foreach($nextSteps as $step)
                <li class="list-group-item small">
                    <i class="fas fa-check text-success me-2" aria-hidden="true"></i>
                    {{ $step }}
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Disclaimer --}}
    <div class="alert alert-warning py-2 small mb-0" role="alert">
        <i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>
        <strong>Disclaimer:</strong> AI-generated insights are not a substitute for professional medical advice.
        Please consult your healthcare provider for any medical concerns.
    </div>
</div>
