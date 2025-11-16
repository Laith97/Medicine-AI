@extends('layouts.admin')

@section('title', $payer->name . ' - Payer Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ $payer->name }}</h1>
                <p class="text-muted">Payer ID: <code>{{ $payer->payer_id }}</code></p>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.payers.edit', $payer) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Edit Payer
                </a>
                <a href="{{ route('admin.payers.rules.index', $payer) }}" class="btn btn-outline-info">
                    <i class="fas fa-cogs me-2"></i>Manage Rules
                </a>
                <a href="{{ route('admin.payers.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Payers
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Payer Information -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Basic Details</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td class="text-muted">Name:</td>
                                        <td><strong>{{ $payer->name }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Payer ID:</td>
                                        <td><code>{{ $payer->payer_id }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Created:</td>
                                        <td>{{ $payer->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Last Updated:</td>
                                        <td>{{ $payer->updated_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Contact Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td class="text-muted">Email:</td>
                                        <td>{{ $payer->contact_info['email'] ?? 'Not provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Phone:</td>
                                        <td>{{ $payer->contact_info['phone'] ?? 'Not provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Address:</td>
                                        <td>{{ $payer->contact_info['address'] ?? 'Not provided' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Processing Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Processing Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-primary">{{ $payer->settings['processing_time_days'] ?? 30 }}</div>
                                    <small class="text-muted">Processing Time (Days)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-info">
                                        {{ ($payer->settings['requires_pre_auth'] ?? false) ? 'Yes' : 'No' }}
                                    </div>
                                    <small class="text-muted">Requires Pre-Auth</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h4 text-success">
                                        ${{ number_format($payer->settings['auto_approve_under'] ?? 0, 2) }}
                                    </div>
                                    <small class="text-muted">Auto-Approve Under</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rules Summary -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Rules Summary</h5>
                        <a href="{{ route('admin.payers.rules.index', $payer) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-cogs me-1"></i>Manage Rules
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="h3 text-primary">{{ $totalRules }}</div>
                                <small class="text-muted">Total Rules</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h3 text-success">{{ $activeRules }}</div>
                                <small class="text-muted">Active Rules</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h3 text-info">{{ $totalRules - $activeRules }}</div>
                                <small class="text-muted">Inactive Rules</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h3 text-warning">{{ $recentApplications->count() }}</div>
                                <small class="text-muted">Recent Applications</small>
                            </div>
                        </div>

                        @if($recentApplications->count() > 0)
                            <hr>
                            <h6>Recent Rule Applications</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Rule</th>
                                            <th>Result</th>
                                            <th>Applied At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentApplications as $application)
                                            <tr>
                                                <td>{{ $application->rule->name ?? 'Unknown Rule' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $application->result === 'success' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($application->result) }}
                                                    </span>
                                                </td>
                                                <td>{{ $application->created_at->format('M d, H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.payers.edit', $payer) }}" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Payer
                            </a>
                            <a href="{{ route('admin.payers.rules.create', $payer) }}" class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Add Rule
                            </a>
                            <a href="{{ route('admin.payers.rules.export', $payer) }}" class="btn btn-outline-info">
                                <i class="fas fa-download me-2"></i>Export Rules
                            </a>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash me-2"></i>Delete Payer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Total Rules</small>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: {{ $totalRules > 0 ? 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ $totalRules }}</small>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Active Rules</small>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $totalRules > 0 ? ($activeRules / $totalRules) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ $activeRules }} of {{ $totalRules }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Payer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong>{{ $payer->name }}</strong>?</p>
                <p class="text-danger">This action cannot be undone and will also delete all associated rules.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.payers.destroy', $payer) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Payer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
