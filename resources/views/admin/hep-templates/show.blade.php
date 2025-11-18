@extends('layouts.admin')

@section('title', $template->name . ' - Template Details')

@push('styles')
<style>
    .template-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .detail-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .detail-card h5 {
        color: #2c3e50;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #ecf0f1;
    }

    .tag {
        background: #e3f2fd;
        color: #1976d2;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        display: inline-block;
    }

    .usage-stats {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .week-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .week-header {
        background: #e9ecef;
        padding: 0.75rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .exercise-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 0.5rem;
    }

    .program-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: box-shadow 0.2s ease;
    }

    .program-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">{{ $template->name }}</h1>
                    <p class="mb-0">HEP Program Template Details</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.hep-templates.edit', $template) }}" class="btn btn-outline-light">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="{{ route('admin.hep-templates.index') }}" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Templates
                    </a>
                </div>
            </div>
        </div>

        <!-- Template Header -->
        <div class="template-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">{{ $template->name }}</h2>
                    <p class="mb-3">{{ $template->description }}</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-tag me-1"></i>{{ ucfirst(str_replace('_', ' ', $template->category)) }}
                        </span>
                        @if($template->diagnosis_type)
                            <span class="badge bg-light text-dark fs-6">
                                <i class="fas fa-stethoscope me-1"></i>{{ ucfirst(str_replace('_', ' ', $template->diagnosis_type)) }}
                            </span>
                        @endif
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-calendar me-1"></i>{{ $template->duration_weeks }} weeks
                        </span>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-clock me-1"></i>{{ $template->frequency_per_week }}x/week
                        </span>
                        @if($template->is_active)
                            <span class="badge bg-success fs-6">Active</span>
                        @else
                            <span class="badge bg-secondary fs-6">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-2">
                        <i class="fas fa-clipboard-list fa-4x text-white-50"></i>
                    </div>
                    <h4>{{ $exercisesByWeek->count() }} Weeks</h4>
                    <small>{{ $template->templateExercises->count() }} Total Exercises</small>
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="usage-stats">
            <div class="row text-center">
                <div class="col-md-4">
                    <h3 class="mb-1">{{ $usageCount }}</h3>
                    <small>Programs Created</small>
                </div>
                <div class="col-md-4">
                    <h3 class="mb-1">{{ $activePrograms }}</h3>
                    <small>Active Programs</small>
                </div>
                <div class="col-md-4">
                    <h3 class="mb-1">{{ $template->templateExercises->count() }}</h3>
                    <small>Total Exercises</small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Template Details -->
            <div class="col-lg-8">
                <!-- Goals -->
                @if($template->goals && count($template->goals) > 0)
                    <div class="detail-card">
                        <h5><i class="fas fa-bullseye me-2"></i>Goals</h5>
                        <ul class="mb-0">
                            @foreach($template->goals as $goal)
                                <li>{{ $goal }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Precautions -->
                @if($template->precautions && count($template->precautions) > 0)
                    <div class="detail-card">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Precautions</h5>
                        <div class="alert alert-warning">
                            <strong>Important:</strong> Consider these precautions when using this template.
                        </div>
                        <ul class="mb-0">
                            @foreach($template->precautions as $precaution)
                                <li>{{ $precaution }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Exercise Program -->
                <div class="detail-card">
                    <h5><i class="fas fa-list me-2"></i>Exercise Program</h5>

                    @foreach($exercisesByWeek as $weekNumber => $exercises)
                        <div class="week-section">
                            <div class="week-header">
                                Week {{ $weekNumber }}
                                <small class="text-muted ms-2">({{ $exercises->count() }} exercises)</small>
                            </div>

                            @foreach($exercises->sortBy('order') as $exercise)
                                <div class="exercise-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $exercise->exercise->name }}</h6>
                                            <p class="mb-2 text-muted small">{{ $exercise->exercise->description }}</p>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    @if($exercise->sets && $exercise->reps)
                                                        <small><strong>Sets/Reps:</strong> {{ $exercise->sets }} × {{ $exercise->reps }}</small><br>
                                                    @endif
                                                    @if($exercise->duration_seconds)
                                                        <small><strong>Duration:</strong> {{ $exercise->getFormattedDuration() }}</small><br>
                                                    @endif
                                                    @if($exercise->frequency)
                                                        <small><strong>Frequency:</strong> {{ $exercise->frequency }}</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    @if($exercise->progression_notes)
                                                        <small><strong>Progression:</strong> {{ $exercise->progression_notes }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            @if($exercise->exercise->video_url)
                                                <i class="fas fa-video text-primary" title="Has video"></i>
                                            @endif
                                            @if($exercise->exercise->image_url)
                                                <i class="fas fa-image text-success ms-2" title="Has image"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <!-- Programs Created from Template -->
                @if($template->programs->count() > 0)
                    <div class="detail-card">
                        <h5><i class="fas fa-history me-2"></i>Programs Created ({{ $template->programs->count() }})</h5>
                        <div class="row">
                            @foreach($template->programs->take(6) as $program)
                                <div class="col-md-6">
                                    <div class="program-card">
                                        <h6 class="mb-1">{{ $program->title }}</h6>
                                        <small class="text-muted d-block">
                                            Patient: {{ $program->patient->name }}
                                        </small>
                                        <small class="text-muted d-block">
                                            Doctor: {{ $program->doctor->user->name }}
                                        </small>
                                        <small class="text-muted d-block">
                                            Status: <span class="badge bg-{{ $program->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($program->status) }}</span>
                                        </small>
                                        <small class="text-muted d-block">
                                            Created: {{ $program->created_at->format('M d, Y') }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($template->programs->count() > 6)
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    And {{ $template->programs->count() - 6 }} more programs...
                                </small>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Metadata -->
                <div class="detail-card">
                    <h5><i class="fas fa-info-circle me-2"></i>Template Info</h5>
                    <table class="table table-sm mb-0">
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>{{ $template->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Updated:</strong></td>
                            <td>{{ $template->updated_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Created By:</strong></td>
                            <td>{{ $template->creator->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><code>{{ $template->id }}</code></td>
                        </tr>
                    </table>
                </div>

                <!-- Actions -->
                <div class="detail-card">
                    <h5><i class="fas fa-cogs me-2"></i>Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.hep-templates.edit', $template) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Template
                        </a>

                        <form method="POST" action="{{ route('admin.hep-templates.duplicate', $template) }}"
                              onsubmit="return confirm('Create a copy of this template?')">
                            @csrf
                            @method('POST')
                            <button type="submit" class="btn btn-outline-info">
                                <i class="fas fa-copy me-2"></i>Duplicate Template
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.hep-templates.toggle-active', $template) }}">
                            @csrf
                            @method('POST')
                            <button type="submit" class="btn {{ $template->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                <i class="fas {{ $template->is_active ? 'fa-pause' : 'fa-play' }} me-2"></i>
                                {{ $template->is_active ? 'Deactivate' : 'Activate' }} Template
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.hep-templates.destroy', $template) }}"
                              onsubmit="return confirm('Are you sure you want to delete this template? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-trash me-2"></i>Delete Template
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
