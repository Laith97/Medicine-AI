@extends('layouts.admin')

@section('title', 'Exercise Library')

@push('styles')
<style>
    .exercise-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
        border-radius: 12px;
    }

    .exercise-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .exercise-image {
        height: 150px;
        object-fit: cover;
        border-radius: 8px 8px 0 0;
    }

    .exercise-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.75rem;
    }

    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .action-buttons .btn {
        margin-right: 0.25rem;
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
                    <h1 class="text-white">Exercise Library</h1>
                    <p class="mb-0">Manage exercises for HEP programs</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.exercises.import') }}" class="btn btn-outline-light">
                        <i class="fas fa-upload me-2"></i>Import
                    </a>
                    <a href="{{ route('admin.exercises.export', request()->query()) }}" class="btn btn-outline-light">
                        <i class="fas fa-download me-2"></i>Export
                    </a>
                    <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Exercise
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-card">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="mb-1">{{ $exercises->total() }}</h3>
                        <small>Total Exercises</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="mb-1">{{ $categories->count() }}</h3>
                        <small>Categories</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="mb-1">{{ $exercises->where('video_url', '!=', null)->count() }}</h3>
                        <small>With Videos</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="mb-1">{{ $exercises->where('image_url', '!=', null)->count() }}</h3>
                        <small>With Images</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="{{ route('admin.exercises.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ request('search') }}" placeholder="Search exercises...">
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="difficulty" class="form-label">Difficulty</label>
                    <select class="form-select" id="difficulty" name="difficulty">
                        <option value="">All Levels</option>
                        @foreach($difficulties as $difficulty)
                            <option value="{{ $difficulty }}" {{ request('difficulty') === $difficulty ? 'selected' : '' }}>
                                {{ ucfirst($difficulty) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="equipment" class="form-label">Equipment</label>
                    <select class="form-select" id="equipment" name="equipment">
                        <option value="">All Equipment</option>
                        @foreach($equipmentOptions as $equipment)
                            <option value="{{ $equipment }}" {{ request('equipment') === $equipment ? 'selected' : '' }}>
                                {{ $equipment }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.exercises.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Exercises Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Exercise</th>
                        <th>Category</th>
                        <th>Difficulty</th>
                        <th>Equipment</th>
                        <th>Media</th>
                        <th>Usage</th>
                        <th>Quality</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exercises as $exercise)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($exercise->image_url)
                                        <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}"
                                             class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <i class="fas fa-dumbbell text-muted"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-0">{{ $exercise->name }}</h6>
                                        <small class="text-muted">{{ Str::limit($exercise->description, 50) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ ucfirst($exercise->category) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $exercise->difficulty_level === 'beginner' ? 'success' : ($exercise->difficulty_level === 'intermediate' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($exercise->difficulty_level) }}
                                </span>
                            </td>
                            <td>
                                @if($exercise->equipment_required && count($exercise->equipment_required) > 0)
                                    <small>{{ implode(', ', $exercise->equipment_required) }}</small>
                                @else
                                    <small class="text-muted">None</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($exercise->video_url)
                                        <i class="fas fa-video text-primary" title="Has video"></i>
                                    @endif
                                    @if($exercise->image_url)
                                        <i class="fas fa-image text-success" title="Has image"></i>
                                    @endif
                                    @if(!$exercise->video_url && !$exercise->image_url)
                                        <small class="text-muted">None</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">
                                    Used in {{ $exercise->hepExercises()->count() }} programs
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <small class="me-2">{{ $exercise->getQualityScore() }}/100</small>
                                    <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                        <div class="progress-bar bg-{{ $exercise->getQualityStatusColor() }}"
                                             style="width: {{ $exercise->getQualityScore() }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="action-buttons">
                                <a href="{{ route('admin.exercises.show', $exercise) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.exercises.edit', $exercise) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.exercises.destroy', $exercise) }}"
                                      class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exercise?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No exercises found</h5>
                                <p class="text-muted mb-3">Get started by adding your first exercise to the library.</p>
                                <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add First Exercise
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($exercises->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $exercises->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit form when filter changes
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
</script>
@endpush
