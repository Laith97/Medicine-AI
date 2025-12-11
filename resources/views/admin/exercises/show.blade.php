@extends('layouts.admin')

@section('title', $exercise->name . ' - Exercise Details')

@push('styles')
<style>
    .exercise-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .exercise-image {
        max-width: 300px;
        max-height: 300px;
        object-fit: cover;
        border-radius: 12px;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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

    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: 12px;
    }

    .video-container video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 12px;
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
                    <h1 class="text-white">{{ $exercise->name }}</h1>
                    <p class="mb-0">Exercise Details & Usage Statistics</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.exercises.edit', $exercise) }}" class="btn btn-outline-light">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="{{ route('admin.exercises.index') }}" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Library
                    </a>
                </div>
            </div>
        </div>

        <!-- Exercise Header -->
        <div class="exercise-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">{{ $exercise->name }}</h2>
                    <p class="mb-3">{{ $exercise->description }}</p>
                    <div class="d-flex gap-3">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-tag me-1"></i>{{ ucfirst($exercise->category) }}
                        </span>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-chart-line me-1"></i>{{ ucfirst($exercise->difficulty_level) }}
                        </span>
                        @if($exercise->duration)
                            <span class="badge bg-light text-dark fs-6">
                                <i class="fas fa-clock me-1"></i>{{ $exercise->duration }}s
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    @if($exercise->image_url)
                        <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}" class="exercise-image">
                    @else
                        <div class="exercise-image bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-dumbbell fa-4x text-muted"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="usage-stats">
            <div class="row text-center">
                <div class="col-md-4">
                    <h3 class="mb-1">{{ $usageCount }}</h3>
                    <small>Total Usage in Programs</small>
                </div>
                <div class="col-md-4">
                    <h3 class="mb-1">{{ $activePrograms }}</h3>
                    <small>Active Programs</small>
                </div>
                <div class="col-md-4">
                    <h3 class="mb-1">{{ $exercise->hepExercises()->distinct('hep_program_id')->count() }}</h3>
                    <small>Unique Programs</small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Exercise Details -->
            <div class="col-lg-8">
                <!-- Instructions -->
                <div class="detail-card">
                    <h5><i class="fas fa-list me-2"></i>Instructions</h5>
                    <div class="instructions-content">
                        {!! nl2br(e($exercise->instructions)) !!}
                    </div>
                </div>

                <!-- Equipment & Muscles -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h5><i class="fas fa-tools me-2"></i>Equipment Required</h5>
                            @if($exercise->equipment_required && count($exercise->equipment_required) > 0)
                                <div>
                                    @foreach($exercise->equipment_required as $equipment)
                                        <span class="tag">{{ $equipment }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No equipment required</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <h5><i class="fas fa-dumbbell me-2"></i>Target Muscle Groups</h5>
                            @if($exercise->target_muscle_groups && count($exercise->target_muscle_groups) > 0)
                                <div>
                                    @foreach($exercise->target_muscle_groups as $muscle)
                                        <span class="tag">{{ $muscle }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">Not specified</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Contraindications -->
                <div class="detail-card">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Contraindications</h5>
                    @if($exercise->contraindications && count($exercise->contraindications) > 0)
                        <div class="alert alert-warning">
                            <strong>Important:</strong> This exercise should not be performed by patients with the following conditions:
                        </div>
                        <ul class="mb-0">
                            @foreach($exercise->contraindications as $contraindication)
                                <li>{{ $contraindication }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No specific contraindications noted</p>
                    @endif
                </div>

                <!-- Usage in Programs -->
                <div class="detail-card">
                    <h5><i class="fas fa-calendar-check me-2"></i>Used in HEP Programs</h5>
                    @if($exercise->hepExercises->count() > 0)
                        <div class="row">
                            @foreach($exercise->hepExercises->take(6) as $hepExercise)
                                <div class="col-md-6">
                                    <div class="program-card">
                                        <h6 class="mb-1">{{ $hepExercise->hepProgram->title }}</h6>
                                        <small class="text-muted d-block">
                                            Patient: {{ $hepExercise->hepProgram->patient->name }}
                                        </small>
                                        <small class="text-muted d-block">
                                            Week {{ $hepExercise->week_number }}, Order {{ $hepExercise->order }}
                                        </small>
                                        <div class="mt-2">
                                            @if($hepExercise->sets && $hepExercise->reps)
                                                <small>{{ $hepExercise->sets }} sets × {{ $hepExercise->reps }} reps</small>
                                            @endif
                                            @if($hepExercise->duration_seconds)
                                                <small>{{ $hepExercise->duration_seconds }} seconds</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($exercise->hepExercises->count() > 6)
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    And {{ $exercise->hepExercises->count() - 6 }} more programs...
                                </small>
                            </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">This exercise has not been used in any HEP programs yet.</p>
                    @endif
                </div>
            </div>

            <!-- Media Sidebar -->
            <div class="col-lg-4">
                <!-- Video -->
                @if($exercise->video_url)
                    <div class="detail-card">
                        <h5><i class="fas fa-video me-2"></i>Demonstration Video</h5>
                        <div class="video-container">
                            <video controls>
                                <source src="{{ $exercise->video_url }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                @endif

                <!-- Image -->
                @if($exercise->image_url)
                    <div class="detail-card">
                        <h5><i class="fas fa-image me-2"></i>Exercise Image</h5>
                        <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}"
                             class="img-fluid rounded" style="width: 100%;">
                    </div>
                @endif

                <!-- Quality Assurance -->
                <div class="detail-card">
                    <h5><i class="fas fa-award me-2"></i>Quality Assurance</h5>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Quality Score</span>
                            <span class="badge bg-{{ $exercise->getQualityStatusColor() }}">
                                {{ $exercise->getQualityScore() }}/100
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $exercise->getQualityStatusColor() }}"
                                 style="width: {{ $exercise->getQualityScore() }}%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            Status: <span class="text-{{ $exercise->getQualityStatusColor() }}">
                                {{ ucfirst($exercise->getQualityStatus()) }}
                            </span>
                        </small>
                    </div>

                    @if(count($exercise->getQualityIssues()) > 0)
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Quality Issues</h6>
                            <ul class="mb-0">
                                @foreach($exercise->getQualityIssues() as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            This exercise meets all quality standards!
                        </div>
                    @endif
                </div>

                <!-- Metadata -->
                <div class="detail-card">
                    <h5><i class="fas fa-info-circle me-2"></i>Metadata</h5>
                    <table class="table table-sm mb-0">
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>{{ $exercise->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Updated:</strong></td>
                            <td>{{ $exercise->updated_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><code>{{ $exercise->id }}</code></td>
                        </tr>
                    </table>
                </div>

                <!-- Actions -->
                <div class="detail-card">
                    <h5><i class="fas fa-cogs me-2"></i>Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.exercises.edit', $exercise) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Exercise
                        </a>
                        <a href="{{ route('admin.exercises.export', ['search' => $exercise->name]) }}" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Export Similar
                        </a>
                        <form method="POST" action="{{ route('admin.exercises.destroy', $exercise) }}"
                              onsubmit="return confirm('Are you sure you want to delete this exercise? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-2"></i>Delete Exercise
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
