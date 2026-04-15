@extends('master')

@section('title', 'Clinical Early Warning System')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-heartbeat me-2"></i>Clinical Monitoring</h2>
                        <p class="text-muted mb-0">Real-time patient risk assessment and early warning system</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="row g-4">
        <!-- Dashboard for a specific patient (if patientId is provided) -->
        @if($patientId)
        <div class="col-12">
            <div id="clinical-dashboard-root" data-patient-id="{{ $patientId }}">
                <!-- React will mount here -->
                <div class="p-8 text-center bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Loading Patient Dashboard...</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Global Alert Management -->
        <div class="col-12 col-xl-8">
            <div id="alert-management-root">
                <!-- React will mount here -->
                <div class="p-8 text-center bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Loading Alert Manager...</p>
                </div>
            </div>
        </div>

        <!-- Configuration Panel -->
        <div class="col-12 col-xl-4">
            <div id="clinical-config-root">
                <!-- React will mount here -->
                <div class="p-8 text-center bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Loading Configuration...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Add some Tailwind-like utilities if they are missing from the project's CSS */
    .font-bold { font-weight: 700; }
    .text-gray-900 { color: #111827; }
    .text-gray-600 { color: #4b5563; }
    .text-gray-700 { color: #374151; }
    .text-gray-500 { color: #6b7280; }
    .bg-white { background-color: #ffffff; }
    .bg-gray-50 { background-color: #f9fafb; }
    .rounded-xl { border-radius: 0.75rem; }
    .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .border-gray-100 { border-color: #f3f4f6; }
</style>
@endpush
