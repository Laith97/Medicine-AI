@extends('master')

@section('title', 'Cases Overview')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
@endpush

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-folder-open me-2"></i>Cases Overview</h2>
                        <p class="text-muted mb-0">Manage and review your patient medical records</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-success">
                            <i class="fas fa-microphone me-2"></i>Start Consultation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="container">

    <!-- Quick Access Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <!-- Language Selector placeholder for consistency -->
                        <div class="d-flex align-items-center">
                            <label class="form-label me-2 mb-0 small fw-bold">Filter:</label>
                            <select id="filterSelector" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                                <option value="all">All Cases</option>
                                <option value="diagnosed">Diagnosed</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>

                        <!-- Global Action Buttons -->
                        <div class="d-flex gap-2">
                            <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-microphone me-1"></i>New Consultation
                            </a>
                            <a href="{{ route('doctor.appointments.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-calendar-check me-1"></i>Appointments
                            </a>
                            <a href="{{ route('doctor.patients.index') }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-users me-1"></i>Patients
                            </a>
                            <a href="{{ route('diagnosis.create') }}" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-stethoscope me-1"></i>Create Diagnosis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="doctor-dashboard-container">

        <!-- Smart Contextual Guidance -->
        @php
            $hasRecords = $records->count() > 0;
        @endphp

        @if(!$hasRecords)
            <div class="alert alert-info text-center mb-4">
                <i class="fas fa-folder-open me-2"></i>
                <strong>No Patient Cases Yet</strong>
                <p class="mb-2">Start your first consultation to begin building patient records.</p>
                <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-microphone me-1"></i>Start Consultation
                </a>
            </div>
        @else
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-success bg-opacity-10 rounded p-2">
                                    <i class="fas fa-calendar-plus text-success"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Schedule Follow-up</h6>
                                <small class="text-muted">Book appointments</small>
                            </div>
                            <a href="{{ route('doctor.appointments.create') }}" class="btn btn-success btn-sm">Schedule</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-info bg-opacity-10 rounded p-2">
                                    <i class="fas fa-headphones text-info"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Review Recordings</h6>
                                <small class="text-muted">Listen to consultations</small>
                            </div>
                            <a href="{{ route('ai.ambient-listening.recorded-voices') }}" class="btn btn-info btn-sm">Listen</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-primary bg-opacity-10 rounded p-2">
                                    <i class="fas fa-plus-circle text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">New Consultation</h6>
                                <small class="text-muted">Start consultation</small>
                            </div>
                            <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-primary btn-sm">Start</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($hasRecords)
            <!-- Patient Management Panel -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><i class="fas fa-users me-2 text-primary"></i>Patient Records</h5>
                            <small class="text-muted">Manage and review patient cases</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 300px;">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="patientSearch" placeholder="Search patients...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <ul class="nav nav-tabs nav-fill border-0" id="patientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active border-0 py-3" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-patients" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>All Patients
                                <span class="badge bg-primary ms-2">{{ count($patientGroups) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 py-3" id="diagnosed-tab" data-bs-toggle="tab" data-bs-target="#diagnosed-patients" type="button" role="tab">
                                <i class="fas fa-check-circle me-2"></i>Diagnosed
                                <span class="badge bg-success ms-2">{{ collect($patientGroups)->where('category', 'diagnosed')->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-0 py-3" id="insurance-tab" data-bs-toggle="tab" data-bs-target="#insurance-eligibility" type="button" role="tab">
                                <i class="fas fa-shield-alt me-2"></i>Insurance
                                <span class="badge bg-warning ms-2">Soon</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Patient Tables -->
            <div class="tab-content" id="patientTabContent">
                <div class="tab-pane fade show active" id="all-patients" role="tabpanel">
                    <div class="doctor-table-container">
                        @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'all'])
                    </div>
                </div>
                <div class="tab-pane fade" id="diagnosed-patients" role="tabpanel">
                    <div class="doctor-table-container">
                        @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'diagnosed'])
                    </div>
                </div>
                <div class="tab-pane fade" id="insurance-eligibility" role="tabpanel">
                    <div class="doctor-card">
                        <div class="doctor-card-header">
                            <h5><i class="fas fa-shield-alt"></i>Insurance & Eligibility</h5>
                        </div>
                        <div class="doctor-card-body">
                            <div class="text-center py-4">
                                <i class="fas fa-shield-alt text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <h6 class="text-muted">Coming Soon</h6>
                                <p class="text-muted mb-0">Insurance and eligibility management features will be available here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="doctor-empty-state">
                <i class="fas fa-user-injured"></i>
                <h5>No Patient Records Found</h5>
                <p>You haven't created any patient records yet.</p>
                <a href="{{ route('ai.ambient-listening.index') }}" class="doctor-btn doctor-btn-primary">
                    <i class="fas fa-plus"></i>Start First Consultation
                </a>
            </div>
        @endif
    </div>
</div>
</div>

@include('cases.partials.modals')

@endsection

@push('scripts')
<script>
// Format AI response similar to voice assistant
function formatAiResponse(response) {
    if (!response) return '';
    
    // Clean up the response
    let formattedResponse = response.trim();
    
    // Convert **bold** markdown to HTML
    formattedResponse = formattedResponse.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Convert section headers to HTML with proper styling
    formattedResponse = formattedResponse.replace(/^📋 PATIENT CASE SUMMARY:$/gm, '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">');
    formattedResponse = formattedResponse.replace(/^🔬 KEY MEDICAL ISSUES IDENTIFIED:?$/gm, '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header">🔬 KEY MEDICAL ISSUES IDENTIFIED</h4><div class="section-content">');
    formattedResponse = formattedResponse.replace(/^📈 IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS:?$/gm, '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header">📈 IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS</h4><div class="section-content">');
    formattedResponse = formattedResponse.replace(/^💊 TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION:?$/gm, '</div></div><div class="medcura-section management-plan"><h4 class="section-header">💊 TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION</h4><div class="section-content">');
    formattedResponse = formattedResponse.replace(/^🩺 RECOMMENDATIONS FOR FUTURE CARE:?$/gm, '</div></div><div class="medcura-section warning-signs"><h4 class="section-header">🩺 RECOMMENDATIONS FOR FUTURE CARE</h4><div class="section-content">');
    
    // Close the final section
    formattedResponse += '</div></div>';
    
    // Convert bullet points to proper list items
    formattedResponse = formattedResponse.replace(/^- (.+)$/gm, '<li class="bullet-item">$1</li>');
    
    // Handle paragraphs - convert double line breaks to paragraph breaks
    formattedResponse = formattedResponse.replace(/\n\n/g, '</p><p>');
    
    // Wrap content that's not already in HTML tags
    const lines = formattedResponse.split('\n');
    const processedLines = [];
    
    for (let line of lines) {
        line = line.trim();
        if (!line) continue;
        
        // Skip lines that are already HTML tags or list items
        if (line.startsWith('<') || line.startsWith('</')) {
            processedLines.push(line);
        } else if (!line.includes('<li class="bullet-item">')) {
            // Wrap non-HTML content in paragraphs
            processedLines.push('<p>' + line + '</p>');
        } else {
            processedLines.push(line);
        }
    }
    
    return processedLines.join('\n');
}

document.addEventListener('DOMContentLoaded', function() {
    // Patient search functionality
    const searchInput = document.getElementById('patientSearch');
    const clearButton = document.getElementById('clearSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterPatients(this.value);
        });
    }
    
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            filterPatients('');
        });
    }
    
    function filterPatients(searchTerm) {
        const rows = document.querySelectorAll('.doctor-table tbody tr.patient-row');
        const term = searchTerm.toLowerCase().trim();
        
        rows.forEach(row => {
            const patientKey = row.dataset.patientKey;
            const visitsRow = document.querySelector(`tr.visits-row[data-patient-key="${patientKey}"]`);
            
            if (!term) {
                row.style.display = '';
                if (visitsRow) visitsRow.style.display = 'none';
                return;
            }
            
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(term);
            
            row.style.display = isVisible ? '' : 'none';
            if (visitsRow) visitsRow.style.display = 'none';
        });
        
        // Update empty state
        updateEmptyState(term);
    }
    
    function updateEmptyState(searchTerm) {
        const tables = document.querySelectorAll('.doctor-table');
        
        tables.forEach(table => {
            const visibleRows = table.querySelectorAll('tbody tr.patient-row:not([style*="display: none"])');
            let emptyState = table.parentElement.querySelector('.search-empty-state');
            
            if (visibleRows.length === 0 && searchTerm) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'search-empty-state text-center py-4';
                    emptyState.innerHTML = `
                        <i class="fas fa-search text-muted" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h6 class="text-muted">No patients found</h6>
                        <p class="text-muted small">Try adjusting your search terms</p>
                    `;
                    table.parentElement.appendChild(emptyState);
                }
                table.style.display = 'none';
                emptyState.style.display = 'block';
            } else {
                if (emptyState) emptyState.style.display = 'none';
                table.style.display = '';
            }
        });
    }
    const summaryModal = document.getElementById('summaryModal');
    if (summaryModal) {
        summaryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            if (button) {
                const name = button.getAttribute('data-patient-name');
                const age = button.getAttribute('data-patient-age');
                const gender = button.getAttribute('data-patient-gender');
                const patientKey = button.getAttribute('data-patient-key');
                
                document.getElementById('patientName').textContent = name;
                document.getElementById('patientAge').textContent = age;
                document.getElementById('patientGender').textContent = gender;
                
                // Load real patient data from server records
                const allRecords = @json($records ?? []);
                let patientRecords = allRecords.filter(record => 
                    record.patient_key === patientKey || 
                    (record.name === name && record.age == age && record.gender === gender)
                );

                document.getElementById('patientVisits').textContent = patientRecords.length;

                if (patientRecords.length > 0) {
                    const latest = patientRecords[patientRecords.length - 1];
                    
                    // Real symptoms
                    if (latest.symptoms && latest.symptoms !== 'N/A') {
                        document.getElementById('symptomsContent').innerHTML = latest.symptoms;
                    } else {
                        document.getElementById('symptomsContent').innerHTML = '<span class="text-muted">No symptoms recorded</span>';
                    }

                    // Real medical history
                    if (latest.past_medical_history && latest.past_medical_history.length > 0) {
                        document.getElementById('historyContent').innerHTML = latest.past_medical_history.join(', ');
                    } else {
                        document.getElementById('historyContent').innerHTML = '<span class="text-muted">No medical history</span>';
                    }

                    // Real medications
                    if (latest.past_medications && latest.past_medications.length > 0) {
                        document.getElementById('medicationsContent').innerHTML = latest.past_medications.join(', ');
                    } else {
                        document.getElementById('medicationsContent').innerHTML = '<span class="text-muted">No medications</span>';
                    }

                    // Real allergies
                    if (latest.allergies && latest.allergies.length > 0) {
                        document.getElementById('allergiesContent').innerHTML = latest.allergies.join(', ');
                    } else {
                        document.getElementById('allergiesContent').innerHTML = '<span class="text-muted">No allergies</span>';
                    }

                    // Real visit history
                    let visitHtml = '';
                    patientRecords.forEach((record, index) => {
                        const date = new Date(record.created_at).toLocaleDateString();
                        const diagnosis = record.ai_response || record.diagnosis_text || 'No diagnosis';
                        const shortDiagnosis = diagnosis.length > 100 ? diagnosis.substring(0, 100) + '...' : diagnosis;
                        
                        visitHtml += `
                            <div class="visit-item mb-2 p-3 border rounded">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Visit ${index + 1}</strong>
                                    <small class="text-muted">${date}</small>
                                </div>
                                <p class="mb-0 small">${shortDiagnosis}</p>
                            </div>
                        `;
                    });
                    
                    document.getElementById('visitHistory').innerHTML = visitHtml;
                    
                    // Generate real AI summary
                    const summaryData = {
                        patient_id: latest.patient_id || latest.id || 1, // Use patient_id, fallback to record id, then default
                        patient_name: name,
                        patient_age: age,
                        patient_gender: gender,
                        visit_count: patientRecords.length,
                        visits: patientRecords.map((record, index) => ({
                            visit_number: index + 1,
                            date: new Date(record.created_at).toLocaleDateString(),
                            diagnosis: record.ai_response || record.diagnosis_text || 'No diagnosis'
                        }))
                    };

                    console.log('Sending patient summary request:', summaryData); // Debug log

                    // Show loading state
                    document.getElementById('aiSummaryContent').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Generating AI summary...</div>';

                    fetch('/patient/summary', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(summaryData)
                    })
                    .then(response => {
                        console.log('Response status:', response.status); // Debug log
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data); // Debug log
                        if (data.success) {
                            // Format the AI response similar to how it's done in generateAnalysisBtn
                            const formattedSummary = formatAiResponse(data.summary || data.raw_response);
                            document.getElementById('aiSummaryContent').innerHTML = formattedSummary;
                        } else {
                            console.error('API Error:', data);
                            document.getElementById('aiSummaryContent').innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ${data.message || 'Could not generate AI summary'}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        document.getElementById('aiSummaryContent').innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error generating AI summary: ${error.message}</div>`;
                    });
                } else {
                    document.getElementById('symptomsContent').innerHTML = '<span class="text-muted">No data available</span>';
                    document.getElementById('historyContent').innerHTML = '<span class="text-muted">No data available</span>';
                    document.getElementById('medicationsContent').innerHTML = '<span class="text-muted">No data available</span>';
                    document.getElementById('allergiesContent').innerHTML = '<span class="text-muted">No data available</span>';
                    document.getElementById('visitHistory').innerHTML = '<span class="text-muted">No visit history</span>';
                    document.getElementById('aiSummaryContent').innerHTML = '<span class="text-muted">No data for summary</span>';
                }
                
                // Set up the View Details button
                const viewDetailsBtn = document.getElementById('viewDetailsBtn');
                if (viewDetailsBtn && patientRecords.length > 0) {
                    const latest = patientRecords[patientRecords.length - 1];
                    const patientId = latest.patient_id || latest.id;
                    
                    // Update the href to redirect to patient profile
                    viewDetailsBtn.href = `/doctor/patients/${patientId}`;
                }
            }
        });
    }
});
</script>
@endpush