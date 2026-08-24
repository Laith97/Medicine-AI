@extends('master')

@section('title', 'Cases Overview')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">

        <!-- Page Header - same as /dashboard -->
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-folder-open me-2"></i>Cases Overview</h2>
                    <p>Manage and review your patient medical records</p>
                </div>
                <a href="{{ route('ai.ambient-listening.index') }}" class="btn">
                    <i class="fas fa-microphone me-2"></i>Start Consultation
                </a>
            </div>
        </div>

        @if($records->count() === 0)
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="mb-2">No Patient Records Found</h5>
                    <p class="text-muted mb-4">You haven't created any patient records yet. Start your first consultation to begin building patient records.</p>
                    <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-primary">
                        <i class="fas fa-microphone me-1"></i>Start Consultation
                    </a>
                </div>
            </div>
        @else
            <!-- Stats Row - compact horizontal so table is visible without scroll -->
            <div class="row g-2 mb-3 cases-stats-compact">
                <div class="col-lg-4 col-4">
                    <div class="stats-card stats-card--compact">
                        <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-text">
                            <p class="stats-number">{{ $totalPatients ?? count($patientGroups) }}</p>
                            <p class="stats-label">Total Patients</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-4">
                    <div class="stats-card stats-card--compact">
                        <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-text">
                            <p class="stats-number">{{ collect($patientGroups)->where('category', 'diagnosed')->count() }}</p>
                            <p class="stats-label">Diagnosed</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-4">
                    <div class="stats-card stats-card--compact">
                        <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stats-text">
                            <p class="stats-number">{{ collect($patientGroups)->sum('visit_count') }}</p>
                            <p class="stats-label">Total Visits</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Records Panel -->
            <div class="card border-0 shadow-sm cases-panel">
                <div class="cases-toolbar">
                    <div class="cases-toolbar__title">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-users me-2 text-primary"></i>Patient Records</h5>
                        <span class="cases-toolbar__meta">— {{ count($patientGroups) }} patients · {{ collect($patientGroups)->sum('visit_count') }} visits</span>
                    </div>
                    <div class="cases-toolbar__controls">
                        <div class="input-group input-group-sm cases-search">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="patientSearch" placeholder="Search by name, age, gender...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <select class="form-select form-select-sm cases-sort" id="sortPatients" title="Sort patients">
                            <option value="recent">Most recent</option>
                            <option value="name_asc">Name A → Z</option>
                            <option value="name_desc">Name Z → A</option>
                            <option value="visits_desc">Most visits</option>
                            <option value="age_desc">Age high → low</option>
                        </select>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="cases-tabs-wrapper">
                    <ul class="nav nav-tabs cases-tabs" id="patientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-patients" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>All Patients
                                <span class="badge ms-2">{{ count($patientGroups) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="diagnosed-tab" data-bs-toggle="tab" data-bs-target="#diagnosed-patients" type="button" role="tab">
                                <i class="fas fa-check-circle me-2"></i>Diagnosed
                                <span class="badge ms-2">{{ collect($patientGroups)->where('category', 'diagnosed')->count() }}</span>
                            </button>
                        </li>
                    </ul>
                    </div>

                    <div class="tab-content" id="patientTabContent">
                        <div class="tab-pane fade show active" id="all-patients" role="tabpanel">
                            <div class="doctor-table-container">
                                @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'all'])
                                @if(count($patientGroups) > 0)
                                <div class="table-footer">
                                    <span><i class="fas fa-info-circle me-1"></i> Showing {{ $patientGroups instanceof \Illuminate\Pagination\LengthAwarePaginator ? $patientGroups->count() : count($patientGroups) }} of {{ $patientGroups instanceof \Illuminate\Pagination\LengthAwarePaginator ? $patientGroups->total() : count($patientGroups) }} patients</span>
                                    <span class="d-none d-sm-inline">Use search or tabs to filter</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="tab-pane fade" id="diagnosed-patients" role="tabpanel">
                            <div class="doctor-table-container">
                                @include('cases.partials.patient-table', ['patients' => $patientGroups, 'category' => 'diagnosed'])
                                @if(collect($patientGroups)->where('category', 'diagnosed')->count() > 0)
                                <div class="table-footer">
                                    <span>{{ collect($patientGroups)->where('category', 'diagnosed')->count() }} diagnosed</span>
                                    <span class="d-none d-sm-inline">Filtered view</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($patientGroups instanceof \Illuminate\Pagination\LengthAwarePaginator && $patientGroups->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $patientGroups->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
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
    const searchInput = document.getElementById('patientSearch');
    const clearButton = document.getElementById('clearSearch');
    const sortSelect = document.getElementById('sortPatients');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterPatients(this.value);
        });
    }
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            filterPatients('');
            if (searchInput) searchInput.focus();
        });
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortPatients(this.value);
        });
    }

    function filterPatients(searchTerm) {
        const rows = document.querySelectorAll('.doctor-table tbody tr.patient-row');
        const term = searchTerm.toLowerCase().trim();
        rows.forEach(row => {
            if (!term) {
                row.style.display = '';
                return;
            }
            const name = (row.getAttribute('data-patient-name') || '').toLowerCase();
            const text = row.textContent.toLowerCase();
            row.style.display = (text.includes(term) || name.includes(term)) ? '' : 'none';
        });
        updateEmptyState(term);
        updateFooterCounts();
    }
    
    function updateEmptyState(searchTerm) {
        const tables = document.querySelectorAll('.doctor-table');
        tables.forEach(table => {
            const visibleRows = table.querySelectorAll('tbody tr.patient-row:not([style*="display: none"])');
            let emptyState = table.parentElement.querySelector('.search-empty-state');
            if (visibleRows.length === 0 && (searchTerm || table.querySelectorAll('tbody tr.patient-row').length > 0)) {
                const isSearching = !!searchTerm;
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'search-empty-state text-center py-4 px-3';
                    table.parentElement.appendChild(emptyState);
                }
                emptyState.innerHTML = isSearching
                    ? `<i class="fas fa-search text-muted d-block" style="font-size: 2rem; margin-bottom: 0.75rem;"></i><h6 class="text-muted mb-1">No patients found</h6><p class="text-muted small mb-0">Try adjusting your search terms</p>`
                    : `<i class="fas fa-users text-muted d-block" style="font-size: 2rem; margin-bottom: 0.75rem;"></i><h6 class="text-muted">No records in this view</h6>`;
                emptyState.style.display = 'block';
                // hide table if no visible rows due to search, keep header visible via container
                table.style.display = visibleRows.length === 0 && isSearching ? 'none' : '';
                if (!isSearching) table.style.display = '';
            } else {
                if (emptyState) emptyState.style.display = 'none';
                table.style.display = '';
            }
        });
    }

    function updateFooterCounts() {
        document.querySelectorAll('.doctor-table-container').forEach(container => {
            const table = container.querySelector('.doctor-table');
            const footer = container.querySelector('.table-footer');
            if (!table || !footer) return;
            const visible = table.querySelectorAll('tbody tr.patient-row:not([style*="display: none"])').length;
            const total = table.querySelectorAll('tbody tr.patient-row').length;
            const firstSpan = footer.querySelector('span:first-child');
            if (firstSpan) {
                if (visible !== total) firstSpan.textContent = `Showing ${visible} of ${total} patients`;
                else firstSpan.innerHTML = total ? `<i class="fas fa-info-circle me-1"></i> Showing ${total} patients` : firstSpan.textContent;
            }
        });
    }

    function sortPatients(mode) {
        document.querySelectorAll('.doctor-table tbody').forEach(tbody => {
            const rows = Array.from(tbody.querySelectorAll('tr.patient-row'));
            rows.sort((a,b) => {
                if (mode === 'name_asc') return (a.getAttribute('data-patient-name')||'').localeCompare(b.getAttribute('data-patient-name')||'');
                if (mode === 'name_desc') return (b.getAttribute('data-patient-name')||'').localeCompare(a.getAttribute('data-patient-name')||'');
                if (mode === 'visits_desc') return parseInt(b.getAttribute('data-visits')||0) - parseInt(a.getAttribute('data-visits')||0);
                if (mode === 'age_desc') {
                    const ageA = parseInt(a.cells[1]?.innerText.trim()||0); const ageB = parseInt(b.cells[1]?.innerText.trim()||0);
                    return (isNaN(ageB)?0:ageB) - (isNaN(ageA)?0:ageA);
                }
                // recent default: by last-visit timestamp desc
                return parseInt(b.getAttribute('data-last-visit')||0) - parseInt(a.getAttribute('data-last-visit')||0);
            });
            rows.forEach(r => tbody.appendChild(r));
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
                        patient_id: latest.patient_id || latest.id || 1,
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

                    console.log('Sending patient summary request:', summaryData);

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
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
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
                    
                    viewDetailsBtn.href = `/doctor/patients/${patientId}`;
                }
            }
        });
    }
});
</script>
@endpush
