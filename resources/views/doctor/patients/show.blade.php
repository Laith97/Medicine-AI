@extends('master')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('doctor.patients.index') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left me-1"></i>Back to Patients
            </a>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                    <h4>{{ $patient->name }}</h4>
                    <p class="text-muted">{{ $patient->age ?? 'N/A' }} years • {{ ucfirst($patient->gender ?? 'N/A') }}</p>
                    <hr>
                    <div class="text-start">
                        <p><i class="fas fa-envelope me-2"></i>{{ $patient->email }}</p>
                        @if($patient->phone)
                            <p><i class="fas fa-phone me-2"></i>{{ $patient->phone }}</p>
                        @endif
                    </div>
                    <a href="{{ route('ai.voice-assistant.index', ['patient' => $patient->id]) }}" 
                       class="btn btn-success w-100 mt-3">
                        <i class="fas fa-microphone me-1"></i>Start Consultation
                    </a>
                    <a href="{{ route('doctor.patients.edit', $patient->id) }}" 
                       class="btn btn-warning w-100 mt-2">
                        <i class="fas fa-edit me-1"></i>Edit Patient
                    </a>
                    <form action="{{ route('doctor.patients.destroy', $patient->id) }}" 
                          method="POST" class="mt-2" 
                          onsubmit="return confirm('Delete this patient? All appointments and diagnoses will remain but patient account will be removed.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-1"></i>Delete Patient
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Appointments History -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Appointments History</h5>
                </div>
                <div class="card-body">
                    @if($appointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appointment)
                                        <tr>
                                            <td>{{ $appointment->appointment_date->format('M d, Y h:i A') }}</td>
                                            <td>{{ ucfirst($appointment->appointment_type) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('appointments.show', $appointment->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No appointments yet.</p>
                    @endif
                </div>
            </div>

            <!-- Diagnoses History -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Diagnoses History</h5>
                </div>
                <div class="card-body">
                    @if($diagnoses->count() > 0)
                        @foreach($diagnoses as $diagnosis)
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>{{ $diagnosis->created_at->format('M d, Y') }}</h6>
                                            <p class="mb-0">{{ Str::limit($diagnosis->diagnosis_text, 150) }}</p>
                                        </div>
                                        <a href="{{ route('diagnosis.show', $diagnosis->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No diagnoses yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
