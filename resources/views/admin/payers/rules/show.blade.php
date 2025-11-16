@extends('layouts.admin')

@section('title', $rule->ruleType->name . ' - Rule Details')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ $rule->ruleType->name }}</h1>
                <p class="text-muted">{{ $payer->name }} ({{ $payer->payer_id }})</p>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.payers.rules.edit', [$payer, $rule]) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Edit Rule
                </a>
                <a href="{{ route('admin.payers.rules.index', $payer) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Rules
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Rule Overview -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Rule Overview</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td class="text-muted">Rule Type:</td>
                                <td><strong>{{ $rule->ruleType->name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Priority:</td>
                                <td>
                                    <span class="badge bg-{{ $rule->priority <= 3 ? 'danger' : ($rule->priority <= 7 ? 'warning' : 'secondary') }}">
                                        {{ $rule->priority }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created:</td>
                                <td>{{ $rule->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Updated:</td>
                                <td>{{ $rule->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Application Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h4 text-primary">{{ $totalApplications }}</div>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-success">{{ $successfulApplications }}</div>
                                <small class="text-muted">Successful</small>
                            </div>
                        </div>
                        @if($totalApplications > 0)
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h4 text-danger">{{ $failedApplications }}</div>
                                    <small class="text-muted">Failed</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 text-info">{{ round(($successfulApplications / $totalApplications) * 100) }}%</div>
                                    <small class="text-muted">Success Rate</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Conditions and Actions -->
            <div class="col-lg-8">
                <!-- Conditions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Conditions ({{ count($rule->conditions) }})</h5>
                    </div>
                    <div class="card-body">
                        @if(count($rule->conditions) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Operator</th>
                                            <th>Value</th>
                                            <th>Logic</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rule->conditions as $condition)
                                            <tr>
                                                <td><code>{{ $condition['field'] }}</code></td>
                                                <td>{{ ucwords(str_replace('_', ' ', $condition['operator'])) }}</td>
                                                <td><code>{{ $condition['value'] }}</code></td>
                                                <td>{{ $condition['logic'] ?? 'AND' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No conditions defined</p>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Actions ({{ count($rule->actions) }})</h5>
                    </div>
                    <div class="card-body">
                        @if(count($rule->actions) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Action Type</th>
                                            <th>Parameters</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rule->actions as $action)
                                            <tr>
                                                <td>{{ ucwords(str_replace('_', ' ', $action['type'])) }}</td>
                                                <td><code>{{ json_encode($action['params'] ?? []) }}</code></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No actions defined</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Applications -->
                @if($recentApplications->count() > 0)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Applications</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Result</th>
                                            <th>Applied At</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentApplications as $application)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-{{ $application->result === 'success' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($application->result) }}
                                                    </span>
                                                </td>
                                                <td>{{ $application->created_at->format('M d, H:i') }}</td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $application->details ?? 'No details available' }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
