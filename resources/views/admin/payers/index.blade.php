@extends('layouts.admin')

@section('title', 'Payer Management')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Payer Management</h1>
                <p class="text-muted">Manage insurance payers and their configurations</p>
            </div>
            <a href="{{ route('admin.payers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Payer
            </a>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search Payers</label>
                        <input type="text" class="form-control" id="search" name="search"
                               value="{{ request('search') }}" placeholder="Search by name or payer ID...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-outline-primary d-block">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        @if(request('search'))
                            <a href="{{ route('admin.payers.index') }}" class="btn btn-outline-secondary d-block">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Payers Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payers ({{ $payers->total() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($payers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Payer ID</th>
                                    <th>Name</th>
                                    <th>Contact Email</th>
                                    <th>Rules Count</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payers as $payer)
                                    <tr>
                                        <td>
                                            <code class="text-muted">{{ $payer->payer_id }}</code>
                                        </td>
                                        <td>
                                            <strong>{{ $payer->name }}</strong>
                                        </td>
                                        <td>
                                            {{ $payer->contact_info['email'] ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $payer->rules()->count() }}</span>
                                        </td>
                                        <td>
                                            {{ $payer->created_at->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.payers.show', $payer) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.payers.edit', $payer) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.payers.rules.index', $payer) }}"
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-cogs"></i>
                                                </a>
                                                <form action="{{ route('admin.payers.destroy', $payer) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this payer?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $payers->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No payers found</h5>
                        <p class="text-muted">Get started by adding your first payer.</p>
                        <a href="{{ route('admin.payers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add First Payer
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
