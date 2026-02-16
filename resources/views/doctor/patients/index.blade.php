@extends('master')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-users me-2"></i>My Patients</h2>
                    <p class="text-muted mb-0 mt-1">Your assigned patient profiles</p>
                </div>
                <a href="{{ route('doctor.patients.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Add New Patient
                </a>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('doctor.patients.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, email, or phone..." 
                                   value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                            @if(request('search'))
                                <a href="{{ route('doctor.patients.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Patients List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($patients->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Age/Gender</th>
                                        <th>Contact</th>
                                        <th>Last Visit</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patients as $patient)
                                        <tr>
                                            <td>
                                                <strong>{{ $patient->name }}</strong>
                                            </td>
                                            <td>
                                                {{ $patient->age ?? 'N/A' }} years / 
                                                {{ ucfirst($patient->gender ?? 'N/A') }}
                                            </td>
                                            <td>
                                                <div>{{ $patient->email }}</div>
                                                @if($patient->phone)
                                                    <small class="text-muted">{{ $patient->phone }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($patient->appointments->first())
                                                    {{ $patient->appointments->first()->appointment_date->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">No visits</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('doctor.patients.show', $patient->id) }}" 
                                                       class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('doctor.patients.edit', $patient->id) }}" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('ai.voice-assistant.index', ['patient' => $patient->id]) }}" 
                                                       class="btn btn-sm btn-success" title="Start Consultation">
                                                        <i class="fas fa-microphone"></i>
                                                    </a>
                                                    <form action="{{ route('doctor.patients.destroy', $patient->id) }}" 
                                                          method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Delete this patient? This cannot be undone.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
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
                        
                        <div class="mt-3">
                            {{ $patients->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No patients found.</p>
                            <a href="{{ route('doctor.patients.create') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Add Your First Patient
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
