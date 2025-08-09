@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Hospital Departments</h1>
                    <p class="text-muted mb-0">Organize and manage your hospital's departments</p>
                </div>
                <div>
                    <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-user-md me-2"></i>View Doctors
                    </a>
                    <a href="{{ route('hospital-admin.departments.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Department
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $hospital->name }} - Departments</h5>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Departments are for organizational structure only. Doctors are managed globally at the hospital level.
                    </small>
                </div>
                <div class="card-body">
                    @if($departments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Department Name</th>
                                        <th>Head of Department</th>
                                        <th>Doctors</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($departments as $department)
                                        @php
                                            // Since department-user relationship was removed, show 0 doctors
                                            // This reflects the current database structure
                                            $doctorCount = 0;
                                            $activeDoctorCount = 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <i class="fas fa-building text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $department->name }}</strong>
                                                        @if($department->description)
                                                            <br><small class="text-muted">{{ Str::limit($department->description, 60) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($department->head_of_department)
                                                    <i class="fas fa-user-tie text-info me-1"></i>{{ $department->head_of_department }}
                                                @else
                                                    <span class="text-muted">Not assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2">{{ $doctorCount }}</span>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Department structure only
                                                    </span>
                                                    <div class="mt-1">
                                                        <a href="{{ route('hospital-admin.doctors.index') }}" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>View All Doctors
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($department->phone || $department->email)
                                                    @if($department->phone)
                                                        <div><i class="fas fa-phone me-1 text-success"></i>{{ $department->phone }}</div>
                                                    @endif
                                                    @if($department->email)
                                                        <div><i class="fas fa-envelope me-1 text-info"></i>{{ $department->email }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">No contact info</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($department->is_active)
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>
                                                @else
                                                    <span class="badge bg-secondary"><i class="fas fa-pause me-1"></i>Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('hospital-admin.departments.show', $department) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="View Department">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('hospital-admin.departments.edit', $department) }}" 
                                                       class="btn btn-sm btn-outline-secondary" title="Edit Department">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('hospital-admin.departments.destroy', $department) }}" 
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this department?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Department">
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

                        {{ $departments->links() }}
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5>No Departments Found</h5>
                            <p class="text-muted">Start by creating your first department.</p>
                            <a href="{{ route('hospital-admin.departments.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add First Department
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection