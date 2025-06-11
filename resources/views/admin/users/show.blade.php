@extends('master')

@section('title', 'User Details')

@push('styles')
<style>
    .admin-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .admin-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(44, 62, 80, 0.3);
    }
    
    .info-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 2rem;
    }
    
    .user-avatar-large {
        width: 80px;
        height: 80px;
        background: #DE6262;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .info-item {
        padding: 1rem 0;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 1.1rem;
        color: #2c3e50;
        margin-top: 0.25rem;
    }
    
    .analysis-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .analysis-card:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">User Details</h1>
                    <p class="mb-0 opacity-75">Detailed information about {{ $user->name }}</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Users
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Edit User
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- User Information -->
            <div class="col-lg-8">
                <div class="info-card">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <h3>{{ $user->name }}</h3>
                        @if($user->isAdmin())
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-shield-check me-1"></i>Administrator
                            </span>
                        @else
                            <span class="badge bg-secondary fs-6">
                                <i class="bi bi-person me-1"></i>Regular User
                            </span>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value">{{ $user->email }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value">{{ $user->created_at->format('F j, Y') }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">{{ $user->updated_at->format('F j, Y g:i A') }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Email Verification</div>
                                <div class="info-value">
                                    @if($user->email_verified_at)
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Verified
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i>Not Verified
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Analyses -->
                <div class="info-card">
                    <h5 class="mb-4">
                        <i class="bi bi-file-medical me-2"></i>Patient Analyses
                        <span class="badge bg-primary ms-2">{{ $user->patientAnalyses->count() }}</span>
                    </h5>
                    
                    @if($user->patientAnalyses->count() > 0)
                        @foreach($user->patientAnalyses->take(5) as $analysis)
                            <div class="analysis-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">Analysis #{{ $analysis->id }}</h6>
                                        <p class="text-muted mb-0 small">
                                            {{ Str::limit($analysis->symptoms ?? 'No symptoms recorded', 100) }}
                                        </p>
                                    </div>
                                    <small class="text-muted">{{ $analysis->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($user->patientAnalyses->count() > 5)
                            <div class="text-center">
                                <small class="text-muted">
                                    And {{ $user->patientAnalyses->count() - 5 }} more analyses...
                                </small>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-file-medical display-4 text-muted"></i>
                            <p class="text-muted mt-2">No patient analyses found.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Statistics & Actions -->
            <div class="col-lg-4">
                <!-- Statistics -->
                <div class="info-card">
                    <h5 class="mb-4">
                        <i class="bi bi-graph-up me-2"></i>Statistics
                    </h5>
                    
                    <div class="info-item">
                        <div class="info-label">Total Analyses</div>
                        <div class="info-value">
                            <span class="h4 text-primary">{{ $user->patientAnalyses->count() }}</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Account Age</div>
                        <div class="info-value">{{ $user->created_at->diffForHumans(null, true) }}</div>
                    </div>
                    
                    @if($user->setting)
                        <div class="info-item">
                            <div class="info-label">Settings Configured</div>
                            <div class="info-value">
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Yes
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Quick Actions -->
                @if($user->id !== auth()->id())
                    <div class="info-card">
                        <h5 class="mb-4">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h5>
                        
                        <div class="d-grid gap-3">
                            <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-{{ $user->isAdmin() ? 'shield-x' : 'shield-check' }} me-2"></i>
                                    {{ $user->isAdmin() ? 'Remove Admin Rights' : 'Grant Admin Rights' }}
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-trash me-2"></i>Delete User
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection