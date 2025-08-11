@extends('layouts.admin')

@section('title', 'Audit Log Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('security.dashboard') }}">Security Dashboard</a></li>
                        <li class="breadcrumb-item active">Audit Log Details</li>
                    </ol>
                </div>
                <h4 class="page-title">Audit Log Details</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Audit Log Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>ID:</th>
                                    <td>{{ $auditLog->id }}</td>
                                </tr>
                                <tr>
                                    <th>Action:</th>
                                    <td>
                                        <span class="badge bg-{{ $auditLog->getActionBadgeClass() }}">{{ $auditLog->action }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Timestamp:</th>
                                    <td>{{ $auditLog->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address:</th>
                                    <td>{{ $auditLog->ip_address }}</td>
                                </tr>
                                <tr>
                                    <th>User Agent:</th>
                                    <td>{{ $auditLog->user_agent }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5>Related Entities</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>User:</th>
                                    <td>
                                        @if($auditLog->user)
                                            <a href="{{ route('admin.users.show', $auditLog->user) }}">{{ $auditLog->user->name }}</a>
                                            ({{ $auditLog->user->email }})
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Doctor:</th>
                                    <td>
                                        @if($auditLog->doctor)
                                            <a href="{{ route('admin.users.show', $auditLog->doctor) }}">{{ $auditLog->doctor->name }}</a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Patient:</th>
                                    <td>
                                        @if($auditLog->patient)
                                            <a href="{{ route('admin.users.show', $auditLog->patient) }}">{{ $auditLog->patient->name }}</a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5>Metadata</h5>
                            @if($auditLog->metadata)
                                <pre class="bg-light p-3">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <p>No additional metadata available.</p>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="{{ route('security.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
