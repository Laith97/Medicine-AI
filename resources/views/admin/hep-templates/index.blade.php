@extends('layouts.admin')

@section('title', 'HEP Program Templates')

@push('styles')
<style>
    .template-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
        border-radius: 12px;
    }

    .template-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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

    .badge-inactive {
        background-color: #6c757d;
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
                    <h1 class="text-white">HEP Program Templates</h1>
                    <p class="mb-0">Manage pre-built HEP program templates</p>
                </div>
                <a href="{{ route('admin.hep-templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Template
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-card">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="mb-1">{{ $templates->total() }}</h3>
                    <small>Total Templates</small>
                </div>
                <div class="col-md-3">
                    <h3 class="mb-1">{{ $templates->where('is_active', true)->count() }}</h3>
                    <small>Active Templates</small>
                </div>
                <div class="col-md-3">
                    <h3 class="mb-1">{{ $categories->count() }}</h3>
                    <small>Categories</small>
                </div>
                <div class="col-md-3">
                    <h3 class="mb-1">{{ $templates->sum('getUsageCount') }}</h3>
                    <small>Programs Created</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="{{ route('admin.hep-templates.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ request('search') }}" placeholder="Search templates...">
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $category)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="diagnosis_type" class="form-label">Diagnosis Type</label>
                    <select class="form-select" id="diagnosis_type" name="diagnosis_type">
                        <option value="">All Types</option>
                        @foreach($diagnosisTypes as $type)
                            <option value="{{ $type }}" {{ request('diagnosis_type') === $type ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.hep-templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Templates Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Template</th>
                        <th>Category</th>
                        <th>Diagnosis</th>
                        <th>Duration</th>
                        <th>Exercises</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <div>
                                    <h6 class="mb-1">{{ $template->name }}</h6>
                                    <small class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                    <br>
                                    <small class="text-muted">By: {{ $template->creator->name }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $template->category)) }}</span>
                            </td>
                            <td>
                                @if($template->diagnosis_type)
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $template->diagnosis_type)) }}</span>
                                @else
                                    <small class="text-muted">General</small>
                                @endif
                            </td>
                            <td>
                                <small>{{ $template->duration_weeks }} weeks</small><br>
                                <small>{{ $template->frequency_per_week }}x/week</small>
                            </td>
                            <td>
                                <small>{{ $template->templateExercises()->count() }} exercises</small>
                            </td>
                            <td>
                                <small>{{ $template->getUsageCount() }} programs created</small>
                            </td>
                            <td>
                                @if($template->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="action-buttons">
                                <a href="{{ route('admin.hep-templates.show', $template) }}" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.hep-templates.edit', $template) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.hep-templates.toggle-active', $template) }}"
                                      class="d-inline" onsubmit="return confirm('Toggle template status?')">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-sm {{ $template->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $template->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fas {{ $template->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.hep-templates.duplicate', $template) }}"
                                      class="d-inline" onsubmit="return confirm('Create a copy of this template?')">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.hep-templates.destroy', $template) }}"
                                      class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No templates found</h5>
                                <p class="text-muted mb-3">Get started by creating your first HEP program template.</p>
                                <a href="{{ route('admin.hep-templates.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create First Template
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($templates->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $templates->appends(request()->query())->links() }}
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
