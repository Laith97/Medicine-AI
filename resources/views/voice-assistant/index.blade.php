@extends('master')

@section('content')
<style>
.app-main { background:#f8fafc }
.dashboard-header{ background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius:16px; padding:1.6rem 1.8rem; margin-bottom:1.25rem; border:1px solid rgba(255,255,255,0.06); box-shadow:0 8px 24px rgba(15,23,42,0.12) }
.dashboard-header h2{ color:#fff; font-weight:800; letter-spacing:-0.02em; margin-bottom:0.2rem; font-size:1.35rem }
.dashboard-header p{ color:rgba(255,255,255,0.72); margin:0; font-size:0.86rem }
.dashboard-header .btn{ background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.18); color:#fff; font-weight:700; padding:0.55rem 1rem; border-radius:10px; backdrop-filter:blur(6px) }
.dashboard-header .btn:hover{ background:rgba(255,255,255,0.2); color:#fff; transform:translateY(-1px) }
.modern-card{ border:1px solid #eef2f7!important; border-radius:14px!important; box-shadow:0 4px 16px rgba(15,23,42,0.04)!important; background:#fff }
.modern-card .card-body{ padding:1rem 1.1rem }
</style>
<div class="container-fluid" style="background:#f8fafc" data-session-id="{{ $sessionId }}">
    <div class="container-fluid px-3 px-lg-4">
    <div class="row g-0">
        <div class="col-12 px-0">
            <div class="dashboard-header d-flex justify-content-between align-items-center" style="margin-left:0">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h2 class="mb-0"><i class="fa-solid fa-microphone me-2" style="font-family:'Font Awesome 6 Free'!important;font-weight:900!important"></i>Ambient Listening</h2>
                        <p>AI-powered consultation recording · diarized transcription · auto chart</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="d-none d-md-inline badge bg-white bg-opacity-10 border text-white" style="border-radius:20px;padding:6px 10px;font-weight:600"><span class="status-dot me-1" style="width:7px;height:7px;background:#22c55e;display:inline-block;border-radius:50%"></span> Live AI</span>
                    <a href="{{ route('ai.ambient-listening.recorded-voices') }}" class="btn btn-sm"><i class="fas fa-clock-rotate-left me-2"></i>History</a>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="container">

    <!-- Patient Selection - Modern -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-body">
                    <label for="patientSearchInput" class="form-label fw-bold mb-2 d-block" style="font-size:0.72rem;letter-spacing:0.06em;text-transform:uppercase;color:#64748b;line-height:1">Patient</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;border-radius:10px;background:#eff6ff;color:#2563eb"><i class="fas fa-user-injured" style="font-size:0.9rem"></i></div>
                        <div class="flex-grow-1 min-w-0 position-relative">
                            <div class="position-relative">
                                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.85rem"></i>
                                <input type="text" id="patientSearchInput" class="form-control" placeholder="Search by name, phone or click to browse..." autocomplete="off" value="{{ collect($patients)->firstWhere('id', request('patient'))['name'] ?? '' }}" style="border-radius:10px;border:1px solid #e2e8f0;font-weight:600;color:#1e293b;height:38px;padding:6px 12px 6px 34px;font-size:0.9rem">
                                <button type="button" id="clearPatientSearch" class="btn btn-sm position-absolute" style="right:6px;top:50%;transform:translateY(-50%);background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:2px 8px;display:none"><i class="fas fa-times" style="font-size:0.7rem"></i></button>
                            </div>
                            <input type="hidden" id="patientSelect" value="{{ request('patient') }}">
                            <div id="patientSearchResults" class="position-absolute bg-white border shadow-sm" style="top:calc(100% + 6px);left:0;right:0;z-index:1055;border-radius:12px;display:none;max-height:300px;overflow-y:auto;box-shadow:0 12px 24px rgba(15,23,42,0.12)"></div>
                        </div>
                        <button id="showNewPatientFormBtn" class="btn btn-light border flex-shrink-0" type="button" style="border-radius:10px;font-weight:700;color:#1e293b;height:38px;padding:0 14px;white-space:nowrap;font-size:0.88rem">
                            <i class="fas fa-user-plus me-1 text-primary"></i> New Patient
                        </button>
                    </div>
                    <div class="form-text" style="font-size:0.72rem;color:#64748b;margin-left:50px"><span id="patientSearchHint">Click to browse · type 2+ chars to search · shows 10</span> <span id="patientSelectedHint" class="text-success fw-bold" style="display:none"><i class="fas fa-check me-1"></i> Selected</span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const searchInput = document.getElementById('patientSearchInput');
        const hiddenSelect = document.getElementById('patientSelect');
        const resultsBox = document.getElementById('patientSearchResults');
        const clearBtn = document.getElementById('clearPatientSearch');
        const hint = document.getElementById('patientSearchHint');
        const selectedHint = document.getElementById('patientSelectedHint');
        let debounceTimer = null;
        let selectedPatientData = null;

        function showSelected(){
            if(hiddenSelect.value){
                hint.style.display='none';
                selectedHint.style.display='inline';
                clearBtn.style.display='block';
            } else {
                hint.style.display='inline';
                selectedHint.style.display='none';
                clearBtn.style.display='none';
            }
        }
        showSelected();

        // If pre-selected from ?patient=, fetch name already set, ensure hidden has value
        if(hiddenSelect.value && searchInput.value){
            showSelected();
        }

        function hideResults(){ resultsBox.style.display='none'; resultsBox.innerHTML=''; }

        function renderResults(patients){
            if(!patients.length){
                resultsBox.innerHTML = '<div class="p-3 text-center text-muted small">No patients found · try different term or <a href="#" onclick="document.getElementById(\'showNewPatientFormBtn\').click();return false;">create new</a></div>';
                resultsBox.style.display='block';
                return;
            }
            resultsBox.innerHTML = patients.map(p => `
                <button type="button" class="w-100 text-start p-2 px-3 d-flex align-items-center gap-3" data-id="${p.id}" data-name="${p.name.replace(/"/g,'&quot;')}" style="background:#fff;border:none;border-bottom:1px solid #f1f5f9;transition:background 0.15s">
                    <span style="width:36px;height:36px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#0f172a;font-weight:800">${(p.name||'?').charAt(0).toUpperCase()}</span>
                    <span class="flex-grow-1 text-start">
                        <span style="font-weight:700;color:#0f172a;font-size:0.88rem;display:block">${p.name}</span>
                        <span style="font-size:0.74rem;color:#64748b">${p.email||''} ${p.phone?'· '+p.phone:''}</span>
                    </span>
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:0.7rem"></i>
                </button>
            `).join('');
            resultsBox.style.display='block';
            resultsBox.querySelectorAll('button').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const id = btn.getAttribute('data-id');
                    const name = btn.getAttribute('data-name');
                    hiddenSelect.value = id;
                    searchInput.value = name;
                    selectedPatientData = {id, name};
                    hideResults();
                    showSelected();
                    // Trigger change for existing listeners
                    hiddenSelect.dispatchEvent(new Event('change', {bubbles:true}));
                    searchInput.dispatchEvent(new Event('change', {bubbles:true}));
                });
                btn.addEventListener('mouseenter', ()=> btn.style.background='#f8fafc');
                btn.addEventListener('mouseleave', ()=> btn.style.background='#fff');
            });
        }

        searchInput.addEventListener('input', function(){
            const q = this.value.trim();
            hiddenSelect.value = '';
            selectedPatientData = null;
            showSelected();
            if(debounceTimer) clearTimeout(debounceTimer);
            if(q.length < 2){
                hideResults();
                if(!q) return;
                resultsBox.innerHTML = '<div class="p-3 text-center text-muted small">Type 2+ characters to search</div>';
                resultsBox.style.display='block';
                return;
            }
            debounceTimer = setTimeout(()=>{
                resultsBox.innerHTML = '<div class="p-3 text-center text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>Searching...</div>';
                resultsBox.style.display='block';
                fetch(`{{ route('doctor.patients.search') }}?query=${encodeURIComponent(q)}`, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
                    .then(r=>r.json())
                    .then(data=>{
                        const patients = data.patients || data || [];
                        renderResults(patients);
                    })
                    .catch(()=>{
                        resultsBox.innerHTML = '<div class="p-3 text-center text-danger small">Search failed · try again</div>';
                    });
            }, 300);
        });

        const initialPatients = @json(collect($patients)->take(10)->values());
        searchInput.addEventListener('focus', function(){
            const q = this.value.trim();
            if(q.length >= 2 && resultsBox.innerHTML){
                resultsBox.style.display='block';
                return;
            }
            if(q === ''){
                // Show browse-any: recent 10 patients
                const recent = initialPatients.map(p => ({ id: p.id, name: p.name, email: p.email ?? '', phone: p.phone ?? '' }));
                if(recent.length){
                    resultsBox.innerHTML = '<div class="px-3 py-2 small fw-bold" style="color:#64748b;background:#f8fafc;border-bottom:1px solid #eef2f7;border-radius:12px 12px 0 0"><i class="fas fa-clock me-1"></i> Recent patients — click any to select</div>' + recent.map(p => `
                        <button type="button" class="w-100 text-start p-2 px-3 d-flex align-items-center gap-3" data-id="${p.id}" data-name="${String(p.name).replace(/"/g,'&quot;')}" style="background:#fff;border:none;border-bottom:1px solid #f1f5f9">
                            <span style="width:36px;height:36px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#0f172a;font-weight:800">${(p.name||'?').charAt(0).toUpperCase()}</span>
                            <span class="flex-grow-1 text-start"><span style="font-weight:700;color:#0f172a;font-size:0.88rem;display:block">${p.name}</span><span style="font-size:0.74rem;color:#64748b">${p.email||''}</span></span>
                            <i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:0.7rem"></i>
                        </button>
                    `).join('') + '<div class="p-2 text-center"><small class="text-muted">Showing 10 recent · type to search all</small></div>';
                    resultsBox.style.display='block';
                    resultsBox.querySelectorAll('button').forEach(btn=>{
                        btn.addEventListener('click', ()=>{
                            hiddenSelect.value = btn.getAttribute('data-id');
                            searchInput.value = btn.getAttribute('data-name');
                            hideResults(); showSelected();
                            hiddenSelect.dispatchEvent(new Event('change',{bubbles:true}));
                        });
                        btn.addEventListener('mouseenter', ()=> btn.style.background='#f8fafc');
                        btn.addEventListener('mouseleave', ()=> btn.style.background='#fff');
                    });
                } else {
                    resultsBox.innerHTML = '<div class="p-3 text-center text-muted small">No recent patients · type to search or create new</div>';
                    resultsBox.style.display='block';
                }
            }
        });

        document.addEventListener('click', function(e){
            if(!searchInput.contains(e.target) && !resultsBox.contains(e.target)){
                hideResults();
            }
        });

        clearBtn.addEventListener('click', function(){
            searchInput.value='';
            hiddenSelect.value='';
            selectedPatientData=null;
            hideResults();
            showSelected();
            searchInput.focus();
            hiddenSelect.dispatchEvent(new Event('change', {bubbles:true}));
        });

        // If hiddenSelect has initial value but input empty (e.g., back button), ensure input shows name
        if(hiddenSelect.value && !searchInput.value){
            // Try to find name from initial patients collection via data attribute if available, else keep hidden
            // Fallback: show ID
            searchInput.placeholder = 'Selected patient ID '+hiddenSelect.value+' · search to change';
        }
    })();
    </script>

    <!-- New Patient Form - Modern -->
    <div id="newPatientForm" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card modern-card" style="overflow:hidden">
                <div class="card-header border-0 d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);color:#fff;padding:1rem 1.2rem">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:10px;background:rgba(255,255,255,0.14)"><i class="fas fa-user-plus" style="font-size:0.85rem"></i></span>
                        <h5 class="mb-0" style="font-weight:800;font-size:0.95rem;color:#fff">Create New Patient</h5>
                    </div>
                    <button id="hideNewPatientFormBtn" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.2);border-radius:10px"><i class="fas fa-times"></i></button>
                </div>
                <div class="card-body p-3" style="background:#fff">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Full Name *</label><input type="text" id="newPatientName" class="form-control" placeholder="Enter full name" style="border-radius:10px;border:1px solid #e2e8f0"><div id="newPatientNameError" class="text-danger small d-none"></div></div>
                        <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Email Address *</label><input type="email" id="newPatientEmail" class="form-control" placeholder="patient@example.com" style="border-radius:10px;border:1px solid #e2e8f0"><div id="newPatientEmailError" class="text-danger small d-none"></div></div>
                        <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Age *</label><input type="number" id="newPatientAge" class="form-control" min="1" max="150" placeholder="Age" style="border-radius:10px;border:1px solid #e2e8f0"><div id="newPatientAgeError" class="text-danger small d-none"></div></div>
                        <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Gender *</label><select id="newPatientGender" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0"><option value="">Select</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select><div id="newPatientGenderError" class="text-danger small d-none"></div></div>
                        <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Phone Number</label><input type="tel" id="newPatientPhone" class="form-control" placeholder="Phone number" style="border-radius:10px;border:1px solid #e2e8f0"><div id="newPatientPhoneError" class="text-danger small d-none"></div></div>
                    </div>
                    <div class="mt-3 p-2 px-3 rounded-3 d-flex gap-2 align-items-center" style="background:#eff6ff;border:1px solid #dbeafe;font-size:0.78rem;color:#1e40af"><i class="fas fa-info-circle"></i><span><strong>Note:</strong> Default password <code>patient123</code> assigned. Ask patient to change on first login.</span></div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" id="createNewPatientBtn" class="btn text-white" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;border-radius:10px;font-weight:700"><i class="fas fa-user-plus me-2"></i>Create Patient</button>
                        <button type="button" id="cancelNewPatientBtn" class="btn btn-light border" style="border-radius:10px;font-weight:600"><i class="fas fa-times me-2"></i>Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Alert Container -->
    <div id="alertContainer" class="mb-3"></div>

    <!-- Toolbar - Modern -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-body p-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-bold" style="font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase">Language</span>
                        <select id="languageSelector" class="form-select form-select-sm" style="width:auto;min-width:140px;border-radius:10px;border:1px solid #e2e8f0;font-weight:600;font-size:0.82rem">
                            <option value="auto" selected>✨ Auto Detect</option>
                            <option value="ar">🇸🇦 العربية</option>
                            <option value="en">🇺🇸 English</option>
                            <option value="fr">🇫🇷 Français</option>
                            <option value="es">🇪🇸 Español</option>
                            <option value="de">🇩🇪 Deutsch</option>
                        </select>
                        <span class="d-none d-md-inline text-muted" style="font-size:0.76rem">· Auto detects Arabic/English</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('ai.ambient-listening.training') }}" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600"><i class="fas fa-graduation-cap me-1 text-primary"></i>Guide</a>
                        <a href="{{ route('ai.ambient-listening.recorded-voices') }}" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600"><i class="fas fa-history me-1 text-muted"></i>History</a>
                        <a href="{{ route('ai.ambient-listening.performance') }}" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600"><i class="fas fa-chart-line me-1 text-success"></i>Stats</a>
                        <button type="button" class="btn btn-light border btn-sm" data-bs-toggle="modal" data-bs-target="#ambientListeningHelpModal" style="border-radius:10px;font-weight:600"><i class="fas fa-question-circle me-1"></i>Help</button>
                        <div class="vr mx-1 d-none d-md-block"></div>
                        <button id="writeDirectlyBtn" class="btn btn-sm text-white" type="button" disabled style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;border-radius:10px;font-weight:700"><i class="fas fa-edit me-1"></i> Write Directly</button>
                        <button id="advancedControlsToggleBtn" class="btn btn-light border btn-sm" type="button" style="border-radius:10px"><i class="fas fa-sliders-h"></i></button>
                    </div>
                </div>
                <div id="voiceAssistantAdvancedControls" class="border-top bg-white p-3" style="display:none;border-radius:0 0 14px 14px">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Audio Quality</label>
                            <select class="form-select" id="audioQuality" style="border-radius:10px"><option value="high">High (16kHz)</option><option value="medium">Medium (8kHz)</option><option value="low">Low (4kHz)</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Sensitivity</label>
                            <select class="form-select" id="sensitivity" style="border-radius:10px"><option value="high">High</option><option value="medium" selected>Medium</option><option value="low">Low</option></select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS for Enhanced UI -->
    <style>
        /* Status Indicator Styles */
        .status-indicator {
            display: inline-block;
            position: relative;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            background-color: #6c757d;
            transition: all 0.3s ease;
        }

        .status-dot.active {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }

        .status-dot.connecting {
            background-color: #ffc107;
            animation: pulse 1s infinite;
        }

        .status-dot.recording {
            background-color: #DE6262;
            animation: pulse 0.5s infinite;
        }

        .status-dot.error {
            background-color: #dc3545;
            animation: shake 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }

        /* Enhanced button styles */
        .btn-lg {
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            min-height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .btn-lg:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-lg:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-lg:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Recording button animation */
        .recording-pulse {
            animation: recordingPulse 1.5s infinite ease-in-out;
        }

        @keyframes recordingPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
                background-color: #dc3545;
            }
            50% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
                background-color: #c82333;
            }
        }

        /* Transcription container */
        .transcription-container {
            position: relative;
        }

        /* Transcript message styling */
        .message-segment {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .message-segment.patient {
            border-left-color: #28a745;
        }

        .message-segment.doctor {
            border-left-color: #DE6262;
        }

        .message-segment.unknown {
            border-left-color: #6c757d;
        }

        .message-content {
            border-radius: 8px;
            padding: 12px;
            margin-top: 5px;
        }

        /* Progress bar styling */
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        /* Card styling */
        .card {
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
        }

        /* Custom scrollbar for transcription */
        .transcription-container::-webkit-scrollbar {
            width: 8px;
        }

        .transcription-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .transcription-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .transcription-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Enhanced recording button */
        .ambient-recorder-container,
        #react-audio-recorder-container {
            max-width: 300px;
        }
        
        .ambient-recorder-container .btn,
        #react-audio-recorder-container .btn {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            min-height: 38px;
            max-height: 42px;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .ambient-recorder-container .btn:active:not(:disabled),
        #react-audio-recorder-container .btn:active:not(:disabled) {
            transform: scale(0.98);
        }

        .ambient-recorder-container .btn:hover:not(:disabled),
        #react-audio-recorder-container .btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* Constrain recorder component size */
        #react-audio-recorder-container > * {
            max-width: 100%;
        }

        /* Transcript container scrollbar */
        #react-transcript-container {
            min-height: 400px;
            max-height: 60vh;
            overflow-y: auto;
            border-radius: 8px;
            scroll-behavior: smooth;
        }

        /* Recording dot animation */
        .recording-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #dc3545;
            display: inline-block;
            animation: recordingPulseDot 1.5s infinite;
        }

        @keyframes recordingPulseDot {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Status text */
        .status-text {
            font-weight: 500;
        }

        /* Loading and status animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner-border {
            animation: spin 1s linear infinite;
        }

        /* Speaker identification styles */
        .speaker-transcription {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.5;
        }

        .speaker-segment {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px;
        }

        .speaker-segment:hover {
            background-color: #f8f9fa;
        }

        .speaker-header {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .speaker-label {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .speaker-doctor .speaker-label {
            background-color: #d4edda;
            color: #155724;
        }

        .speaker-patient .speaker-label {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .speaker-text {
            font-size: 0.9rem;
            color: #212529;
        }

        /* Enhanced transcript status */
        #transcriptionStatus .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .card-body {
                padding: 1rem;
            }

            .transcription-container {
                height: 300px;
            }
        }
    </style>


    
    <style>
        #voiceAssistantTabs .nav-link {
            transition: all 0.3s ease;
        }
        #voiceAssistantTabs .nav-link:not(.active) {
            background-color: #ffffff !important;
            color: #6c757d !important;
            border: 1px solid #dee2e6 !important;
            opacity: 1 !important;
        }
        #voiceAssistantTabs .nav-link:not(.active):hover {
            background-color: #f8f9fa !important;
            color: #DE6262 !important;
            border-color: #DE6262 !important;
        }
        #voiceAssistantTabs .nav-link.active {
            background-color: #DE6262 !important;
            color: white !important;
            border: 1px solid #DE6262 !important;
            box-shadow: 0 2px 4px rgba(222, 98, 98, 0.2) !important;
        }

        /* Enhanced tab-content connection */
        .tab-content {
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Recording state visual emphasis */
        #transcriptCard.recording-active {
            animation: recordingGlow 2s ease-in-out infinite alternate;
            border-color: #DE6262 !important;
            box-shadow: 0 0 20px rgba(222, 98, 98, 0.4) !important;
        }

        @keyframes recordingGlow {
            from {
                box-shadow: 0 0 20px rgba(222, 98, 98, 0.4);
                border-color: #DE6262;
            }
            to {
                box-shadow: 0 0 30px rgba(222, 98, 98, 0.7);
                border-color: #c55252;
            }
        }

        /* Enhanced status dot in header */
        .card-header .status-dot.recording {
            animation: recordingPulse 0.8s infinite;
        }

        @keyframes recordingPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.3); }
        }

        /* Better visual hierarchy */
        .card-header.bg-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Consistent button colors */
        .btn-warning {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #212529 !important;
        }

        .btn-warning:hover:not(:disabled) {
            background-color: #e0a800 !important;
            border-color: #d39e00 !important;
        }

        .btn-info {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
            color: white !important;
        }

        .btn-info:hover:not(:disabled) {
            background-color: #138496 !important;
            border-color: #117a8b !important;
        }

        /* Improved button spacing in header */
        .card-header .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        /* Status text in header */
        .card-header #recordingStatusText {
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>

    <div class="tab-content" id="voiceAssistantTabsContent">
        <!-- Live Session Tab -->
        <div class="tab-pane fade show active" id="transcription-pane" role="tabpanel" aria-labelledby="transcription-tab">
            <!-- Main Content Grid -->
            <div class="row">
        <!-- Left Column: Transcription - Modern -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 modern-card" id="transcriptCard" style="overflow:hidden">
                <div class="card-header border-0" style="background:#1e293b;color:#fff;padding:1rem 1.1rem">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2" style="color:#fff!important;font-weight:800;font-size:0.95rem">
                            <span style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.12);line-height:1;text-align:center;padding-top:6px;display:inline-block"><i class="fa-solid fa-microphone-lines" style="color:#fff;font-size:0.8rem;font-family:'Font Awesome 6 Free'!important;font-weight:900!important;display:inline-block!important"></i></span>
                            Real-time Transcript
                        </h5>
                        <div id="transcriptionStatus" class="d-flex align-items-center gap-2"></div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-pill" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.14)">
                            <span class="status-dot" id="statusDot" style="width:8px;height:8px"></span>
                            <span class="fw-bold" id="recordingStatusText" style="font-size:0.78rem;letter-spacing:0.02em">Ready to Listen</span>
                        </div>
                        <span id="processingStatus" class="align-items-center gap-1 px-2 py-1 rounded-pill bg-warning text-dark" style="display:none!important;font-size:0.72rem;font-weight:700"><span class="spinner-border spinner-border-sm" style="width:12px;height:12px;border-width:1.5px"></span> Processing...</span>
                        <div id="react-audio-recorder-container" class="ms-1"></div>
                        <button id="startRecordingBtn" class="btn btn-success btn-sm d-none" type="button" disabled><i class="fas fa-microphone me-1"></i>Start</button>
                        <button id="stopRecordingBtn" class="btn btn-danger btn-sm d-none" disabled><i class="fas fa-stop me-1"></i>Stop</button>
                        <div class="ms-auto d-flex gap-1">
                            <button id="generateAnalysisBtn" class="btn btn-sm text-dark" disabled style="background:#facc15;border:none;border-radius:10px;font-weight:700"><i class="fas fa-brain me-1"></i>AI Analysis</button>
                            <button id="resetSessionBtn" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600"><i class="fas fa-rotate me-1"></i>Reset</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 d-flex flex-column" style="min-height:420px">
                    <div class="transcription-container flex-grow-1" style="background:#f8fafc;">
                        <div id="transcriptionContainer" class="h-100">
                            <div id="react-transcript-container" style="min-height:380px"></div>
                            <textarea id="transcriptionArea" class="form-control" style="height: 100%; border: none; background: transparent; resize: none; display: none;" placeholder="Start ambient listening to see transcription here..."></textarea>
                        </div>
                    </div>
                    <div class="p-2 px-3 bg-white border-top d-flex justify-content-between align-items-center">
                        <button id="copyTranscriptBtn" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600"><i class="fas fa-copy me-1"></i> Copy</button>
                        <div class="d-flex gap-1">
                            <button id="clearTranscriptBtn" class="btn btn-light border btn-sm" style="border-radius:10px"><i class="fas fa-trash me-1 text-danger"></i> Clear</button>
                            <button id="exportTranscriptBtn" class="btn btn-light border btn-sm" style="border-radius:10px"><i class="fas fa-download me-1 text-primary"></i> Export</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Clinical Chart - Modern -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 modern-card" style="overflow:hidden">
                <div class="card-header border-0 d-flex justify-content-between align-items-center" style="background:#1e293b;color:#fff;padding:1rem 1.1rem">
                    <div>
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2" style="color:#fff!important;font-weight:800;font-size:0.95rem">
                            <span class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.12)"><i class="fas fa-clipboard-list" style="font-size:0.8rem"></i></span>
                            Clinical Chart
                        </h5>
                        <small style="color:rgba(255,255,255,0.68);font-size:0.74rem">Auto-filled from AI · editable before completion</small>
                    </div>
                    <span class="badge bg-white bg-opacity-10 border text-white" style="border-radius:20px;font-size:0.68rem"><i class="fas fa-wand-magic-sparkles me-1"></i>AI</span>
                </div>
                <div class="card-body p-3" style="background:#fff">
                    <div class="row g-2">
                        <div class="col-12"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Symptoms</label><textarea id="symptoms" class="form-control" rows="2" placeholder="Auto-extracted..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-12"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Medical History</label><textarea id="medicalHistory" class="form-control" rows="2" placeholder="Auto-extracted..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-12"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Physical Findings</label><textarea id="physicalFindings" class="form-control" rows="2" placeholder="Auto-extracted..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Medications</label><textarea id="medications" class="form-control" rows="2" placeholder="Auto-extracted..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Vital Signs</label><textarea id="vitalSigns" class="form-control" rows="2" placeholder="Auto-extracted..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Diagnosis</label><textarea id="diagnosis" class="form-control" rows="2" placeholder="Suggestions..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold mb-1" style="font-size:0.78rem;color:#475569">Care Plan</label><textarea id="carePlan" class="form-control" rows="2" placeholder="Generated..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea></div>
                        <div class="col-12 mt-2">
                            <div id="accuracyContainer" class="d-flex align-items-center justify-content-between p-2 px-3 rounded-3" style="background:#f8fafc;border:1px solid #eef2f7; display:none">
                                <span class="small fw-bold" style="color:#475569;font-size:0.76rem" title="Confidence from STT (AssemblyAI/GPT-4o). Hidden until audio processed."><i class="fas fa-signal me-1 text-success"></i> Transcription Accuracy <span class="text-muted fw-normal" style="font-size:0.68rem">· AI confidence</span></span>
                                <div class="d-flex align-items-center gap-2" style="min-width:160px">
                                    <div class="progress flex-grow-1" style="height:6px;border-radius:10px;background:#e2e8f0"><div class="progress-bar" role="progressbar" style="width:0%;background:#10b981;border-radius:10px" id="accuracyBar"></div></div>
                                    <span class="badge" id="accuracyScore" style="background:#64748b;color:#fff;border-radius:20px;font-size:0.7rem">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


            </div>
        </div>

    </div>

    <!-- Diagnosis Entry Form - Modern -->
    <div id="diagnosisEntryForm" class="row mt-4 mb-4" style="display: none;">
        <div class="col-12">
            <div class="card modern-card" style="overflow:hidden">
                <div class="card-header border-0 d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#fff;padding:1rem 1.2rem">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;border-radius:10px;background:rgba(255,255,255,0.18)"><i class="fas fa-user-md" style="font-size:0.85rem"></i></span>
                        <div>
                            <h5 class="card-title mb-0" style="color:#fff!important;font-weight:800;font-size:0.95rem">Write Your Professional Diagnosis</h5>
                            <small style="color:rgba(255,255,255,0.86);font-size:0.76rem">Your judgment → saved to patient record</small>
                        </div>
                    </div>
                    <span class="badge bg-white bg-opacity-20 border text-white d-none d-md-inline" style="border-radius:20px;font-size:0.68rem"><i class="fas fa-lock me-1"></i> Doctor only</span>
                </div>
                <div class="card-body p-3" style="background:#fff">
                    <label for="diagnosisText" class="form-label fw-semibold mb-2" style="font-size:0.84rem;color:#1e293b">Your Professional Diagnosis</label>
                    <textarea id="diagnosisText" class="form-control" rows="5" placeholder="Write your professional diagnosis based on clinical judgment, transcript & chart..." required style="border-radius:12px;border:1px solid #e2e8f0;font-size:0.9rem;line-height:1.6"></textarea>
                    <div class="d-flex align-items-center gap-2 mt-2" style="font-size:0.76rem;color:#64748b"><i class="fas fa-info-circle text-primary"></i><span>Saved to patient record. Linked to appointment if available, otherwise independent.</span></div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button id="cancelDiagnosisBtn" class="btn btn-light border" style="border-radius:10px;font-weight:600"><i class="fas fa-times me-1"></i>Cancel</button>
                        <button id="completeConsultationBtn" class="btn text-white" disabled style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border:none;border-radius:10px;font-weight:700;opacity:0.6"><i class="fas fa-check me-1"></i>Complete Session</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Consultation Modal - Modern -->
    <div class="modal fade" id="completeConsultationModal" tabindex="-1" aria-labelledby="completeConsultationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color:#fff; padding:1.25rem 1.5rem;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center" style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.15);">
                            <i class="fas fa-check-circle" style="font-size:1.1rem"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="completeConsultationModalLabel" style="font-weight:800;letter-spacing:-0.02em;color:#fff!important">Complete Session</h5>
                            <small style="opacity:0.85;font-size:0.78rem">Review diagnosis &amp; complete consultation</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#f8fafc">
                    <!-- Step indicator -->
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="badge" style="background:#1e293b;color:#fff;border-radius:20px;padding:6px 12px;font-weight:700;font-size:0.72rem"><i class="fas fa-file-medical me-1"></i> 1 Diagnosis</span>
                        <span class="text-muted" style="font-size:0.7rem">—</span>
                        <span class="badge bg-white text-muted border" style="border-radius:20px;padding:6px 12px;font-weight:600;font-size:0.72rem">2 Patient Data</span>
                        <span class="text-muted" style="font-size:0.7rem">—</span>
                        <span class="badge bg-white text-muted border" style="border-radius:20px;padding:6px 12px;font-weight:600;font-size:0.72rem">3 Confirm</span>
                    </div>

                    <!-- Patient chip -->
                    <div class="d-flex align-items-center gap-2 mb-3 p-2 px-3 bg-white border rounded-3 shadow-sm">
                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width:32px;height:32px"><i class="fas fa-user text-primary" style="font-size:0.8rem"></i></div>
                        <div class="flex-grow-1">
                            <small class="text-muted" style="font-size:0.7rem;letter-spacing:0.04em;text-transform:uppercase">Patient</small>
                            <div id="modalPatientName" class="fw-bold" style="font-size:0.92rem;color:#1e293b"></div>
                        </div>
                        <small class="text-muted" style="font-size:0.72rem">Selected from form</small>
                    </div>

                    <!-- Diagnosis Preview -->
                    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;overflow:hidden">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.86rem"><i class="fas fa-clipboard-check me-2 text-primary"></i>Diagnosis Preview</h6>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size:0.68rem"><i class="fas fa-shield-check me-1"></i> To be saved</span>
                            </div>
                            <div id="diagnosisPreview" class="rounded-3 p-3" style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #10b981;max-height:140px;overflow-y:auto;white-space:pre-wrap;line-height:1.6;font-size:0.88rem;color:#334155;min-height:56px">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-start p-3 mb-3 rounded-3" style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.18)">
                        <i class="fas fa-info-circle text-primary mt-1"></i>
                        <div style="font-size:0.82rem;line-height:1.5;color:#334155"><strong>What happens next?</strong> This will save the diagnosis to the patient record and, if an appointment exists, mark it completed. No appointment → saved independently.</div>
                    </div>

                    <!-- Hidden legacy hooks (kept for JS compatibility) -->
                    <div id="appointmentInfo" class="alert alert-info border-0 mb-3" style="display:none;background:rgba(59,130,246,0.08);border-radius:10px"><i class="fas fa-info-circle me-2 text-primary"></i><span id="appointmentInfoText"></span></div>
                    <div id="appointmentPreview" class="card border-0 shadow-sm mb-3" style="display:none;border-radius:12px"><div class="card-body p-3"><h6 class="mb-2" style="font-weight:700;font-size:0.84rem"><i class="fas fa-calendar-alt me-2 text-primary"></i>Appointment Details</h6><div id="appointmentDetails" style="font-size:0.84rem"></div></div></div>
                    <div id="doctorNotesSection" class="mb-3" style="display:none">
                        <label for="appointmentDoctorNotes" class="form-label fw-bold" style="font-size:0.84rem;color:#1e293b"><i class="fas fa-notes-medical me-2 text-primary"></i>Doctor Notes</label>
                        <textarea id="appointmentDoctorNotes" class="form-control" rows="3" placeholder="Treatment plan, follow-up instructions..." style="border-radius:10px;border:1px solid #e2e8f0"></textarea>
                        <div class="form-text" style="font-size:0.74rem">Added to appointment record.</div>
                    </div>

                    <!-- Additional Patient Data Section -->
                    <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between" style="padding:0.9rem 1rem">
                            <h6 class="mb-0" style="font-weight:800;color:#1e293b;font-size:0.86rem"><i class="fas fa-notes-medical me-2 text-primary"></i>Additional Patient Data</h6>
                            <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;font-size:0.68rem"><i class="fas fa-star me-1"></i> For AI Prescriptions</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="rounded-3 p-2 px-3 mb-3 d-flex gap-2 align-items-center" style="background:#fffbeb;border:1px solid #fde68a;font-size:0.78rem;color:#92400e"><i class="fas fa-exclamation-triangle"></i><span><strong>Required:</strong> allergies &amp; current medications to enable safe AI suggestions.</span></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="modal_allergies" class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Patient Allergies <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="modal_allergies" rows="2" placeholder="e.g., Penicillin, Sulfa drugs, or None" style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea>
                                    <div class="form-text" style="font-size:0.72rem">Comma-separated</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="modal_medications" class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Current Medications <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="modal_medications" rows="2" placeholder="e.g., Metformin 500mg twice daily" style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea>
                                    <div class="form-text" style="font-size:0.72rem">Dosage &amp; frequency if known</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="modal_symptoms" class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Symptoms</label>
                                    <textarea class="form-control" id="modal_symptoms" rows="2" placeholder="List patient symptoms..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="modal_medical_history" class="form-label fw-semibold" style="font-size:0.82rem;color:#334155">Medical History</label>
                                    <textarea class="form-control" id="modal_medical_history" rows="2" placeholder="Relevant medical history..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.86rem"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 px-4" style="background:#fff">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" id="modalCompleteConsultationBtn" class="btn" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;border-radius:10px;font-weight:700;color:#fff!important;box-shadow:0 4px 12px rgba(16,185,129,0.3)">
                        <i class="fas fa-check me-1"></i>Complete Session
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ambient Listening Help Modal - Modern -->
    <div class="modal fade" id="ambientListeningHelpModal" tabindex="-1" aria-labelledby="ambientListeningHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
                <div class="modal-header border-0" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);color:#fff;padding:1.2rem 1.5rem">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.14);text-align:center;padding-top:10px"><i class="fas fa-headset" style="color:#fff;font-size:1.1rem"></i></div>
                        <div>
                            <h5 class="modal-title mb-0" id="ambientListeningHelpModalLabel" style="font-weight:800;color:#fff!important">Ambient Listening Help</h5>
                            <small style="opacity:0.8;font-size:0.76rem">How to record, troubleshoot & shortcuts</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#f8fafc">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
                                <div class="card-body p-3">
                                    <h6 style="font-weight:800;color:#1e293b;font-size:0.84rem"><i class="fas fa-microphone text-primary me-2"></i>How to Use</h6>
                                    <ul class="mb-3" style="font-size:0.82rem;line-height:1.6;color:#334155">
                                        <li>Select a patient from the dropdown</li>
                                        <li>Click <strong>Start Listening</strong> to begin</li>
                                        <li>Speak naturally during consultation</li>
                                        <li>View diarized transcript live</li>
                                        <li>Click Stop when complete</li>
                                    </ul>
                                    <h6 style="font-weight:800;color:#1e293b;font-size:0.84rem"><i class="fas fa-shield-alt text-success me-2"></i>Privacy & Security</h6>
                                    <ul class="mb-0" style="font-size:0.82rem;color:#334155">
                                        <li>End-to-end encrypted</li>
                                        <li>HIPAA compliant</li>
                                        <li>Only authorized access</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
                                <div class="card-body p-3">
                                    <h6 style="font-weight:800;color:#1e293b;font-size:0.84rem"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Troubleshooting</h6>
                                    <ul class="mb-3" style="font-size:0.82rem;color:#334155">
                                        <li><strong>No mic:</strong> Check browser permissions</li>
                                        <li><strong>Poor transcript:</strong> Reduce noise, clear audio</li>
                                        <li><strong>Connection:</strong> Verify internet</li>
                                        <li><strong>Language:</strong> Adjust selector before start</li>
                                    </ul>
                                    <h6 style="font-weight:800;color:#1e293b;font-size:0.84rem"><i class="fas fa-keyboard text-info me-2"></i>Shortcuts</h6>
                                    <ul class="mb-0" style="font-size:0.82rem;color:#334155">
                                        <li><kbd>Ctrl + Enter</kbd> Start/Stop</li>
                                        <li><kbd>Alt + T</kbd> Focus transcript</li>
                                        <li><kbd>Enter</kbd> Submit diagnosis</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-0 mt-3" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px">
                        <div class="card-body p-3 d-flex gap-3">
                            <div style="width:32px;height:32px;border-radius:8px;background:#f59e0b;color:#fff;text-align:center;padding-top:6px;flex-shrink:0"><i class="fas fa-lightbulb" style="font-size:0.85rem"></i></div>
                            <div>
                                <h6 style="font-weight:800;color:#92400e;font-size:0.84rem;margin-bottom:0.3rem">Pro Tips</h6>
                                <ul class="mb-0" style="font-size:0.82rem;color:#78350f;line-height:1.5">
                                    <li>Microphone close to both speakers</li>
                                    <li>Quiet environment = better accuracy</li>
                                    <li>Use medical terms for better AI</li>
                                    <li>Review transcript before AI Analysis</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3" style="background:#fff">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600"><i class="fas fa-times me-1"></i>Close</button>
                    <a href="{{ route('ai.ambient-listening.training') }}" class="btn text-white" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border:none;border-radius:10px;font-weight:700"><i class="fas fa-graduation-cap me-1"></i>Open Guide</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Make PHP variables available to JavaScript -->
<script>
    window.records = @json($records ?? []);
    window.patientAppointments = @json($patientAppointments ?? []);
</script>

<!-- Include React components for ambient listening -->
@viteReactRefresh
@vite(['resources/js/voice-assistant-main.jsx'])

<!-- Enhanced status indicator and UI script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update recording status visuals
        function updateRecordingStatus(status) {
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('recordingStatusText');
            const transcriptCard = document.getElementById('transcriptCard');

            if (!statusDot || !statusText) return;

            // Reset all classes
            statusDot.className = 'status-dot';
            statusText.textContent = getStatusText(status);

            // Remove recording emphasis
            if (transcriptCard) {
                transcriptCard.classList.remove('recording-active');
            }

            // Add appropriate class based on status
            switch(status) {
                case 'idle':
                case 'stopped':
                    statusDot.classList.add('active');
                    statusText.innerHTML = '<span class="text-white fw-bold">Ready to Listen</span>';
                    break;
                case 'connecting':
                    statusDot.classList.add('connecting');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #ffc107 !important;">Connecting...</span>';
                    break;
                case 'recording':
                    statusDot.classList.add('recording');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #dc3545 !important;">🔴 LIVE</span>';
                    // Add visual emphasis to transcript card
                    if (transcriptCard) {
                        transcriptCard.classList.add('recording-active');
                    }
                    break;
                case 'disconnected':
                    statusDot.classList.add('error');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #dc3545 !important;">Disconnected</span>';
                    break;
                case 'reconnecting':
                    statusDot.classList.add('connecting');
                    statusText.innerHTML = '<span class="text-white fw-bold" style="color: #ffc107 !important;">Reconnecting...</span>';
                    break;
                default:
                    statusText.innerHTML = '<span class="text-white fw-bold">Ready</span>';
            }
        }

        // Function to update accuracy score display - only shows when real confidence available
        function updateAccuracyScore(accuracy) {
            const accuracyBar = document.getElementById('accuracyBar');
            const accuracyScore = document.getElementById('accuracyScore');
            const container = document.getElementById('accuracyContainer');
            if (!accuracyBar || !accuracyScore || !container) return;
            if (accuracy === undefined || accuracy === null) return; // keep hidden until real value
            container.style.display = 'flex';
            const score = Math.round(accuracy);
            accuracyBar.style.width = score + '%';
            accuracyScore.textContent = score + '%';
            accuracyScore.style.background = score > 80 ? '#10b981' : score > 60 ? '#f59e0b' : '#ef4444';
            accuracyScore.style.color = score > 60 && score <= 80 ? '#1e293b' : '#fff';
        }

        // Helper function to get status text
        function getStatusText(status) {
            const statusMap = {
                'idle': 'Ready to Listen',
                'connecting': 'Connecting...',
                'recording': 'LIVE',
                'stopped': 'Stopped',
                'disconnected': 'Disconnected',
                'reconnecting': 'Reconnecting...'
            };
            return statusMap[status] || status;
        }

        // Listen for status updates from the React component
        window.addEventListener('transcriptUpdate', function(event) {
            const data = event.detail;
            if (data.status) {
                updateRecordingStatus(data.status);

                // Enable AI Analysis and Clinical Doc buttons when recording stops
                const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
                const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

                if (data.status === 'stopped') {
                    if (generateAnalysisBtn) {
                        generateAnalysisBtn.disabled = false;
                        generateAnalysisBtn.style.opacity = '1';
                    }
                    if (generateClinicalDocBtn) {
                        generateClinicalDocBtn.disabled = false;
                        generateClinicalDocBtn.style.opacity = '1';
                    }
                }
            }

            // Update accuracy score if confidence is provided (real value only, else keep hidden)
            if (data.payload && data.payload.confidence !== undefined && data.payload.confidence !== null) {
                updateAccuracyScore(data.payload.confidence * 100);
            }
        });

        // Listen for WebSocket connection status changes
        window.addEventListener('websocketStatus', function(event) {
            const data = event.detail;
            if (data.status) {
                updateRecordingStatus(data.status);
            }
        });

        // Listen for server transcript ready event - also auto-fill Clinical Chart
        window.addEventListener('serverTranscriptReady', function(event) {
            console.log('Server transcript ready - enabling buttons');
            // Persist transcript to DB immediately (survives page reloads)
            try{
                const t = event.detail?.transcription || '';
                if(t && window.sessionId){
                    const fd = new URLSearchParams({sessionId: window.sessionId, text: t, _token: '{{ csrf_token() }}'});
                    fetch('{{ route("ai.ambient-listening.handle-transcription") }}', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: fd}).then(r=>r.json()).then(j=>console.log('Transcript persisted:', j.success)).catch(e=>console.warn('Persist transcript failed', e));
                }
            }catch(e){}
            const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
            const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');
            if (generateAnalysisBtn) {
                generateAnalysisBtn.disabled = false;
                generateAnalysisBtn.style.opacity = '1';
                generateAnalysisBtn.style.cursor = 'pointer';
                console.log('Analysis button enabled via server transcript');
            }
            if (generateClinicalDocBtn) {
                generateClinicalDocBtn.disabled = false;
                generateClinicalDocBtn.style.opacity = '1';
                generateClinicalDocBtn.style.cursor = 'pointer';
                console.log('Clinical doc button enabled via server transcript');
            }
            // Auto-populate Clinical Chart from server extracted data (if available) - precise, fills even if empty string is valid (e.g., "None")
            const extracted = event.detail?.extractedData || event.detail?.server_extracted_data;
            if (extracted && typeof extracted === 'object') {
                const map = {symptoms:'symptoms', medical_history:'medicalHistory', physical_findings:'physicalFindings', medications:'medications', vital_signs:'vitalSigns', diagnosis:'diagnosis', care_plan:'carePlan'};
                let filled = 0;
                Object.entries(map).forEach(([key, id])=>{
                    const el=document.getElementById(id);
                    const val = extracted[key];
                    if(el && val !== undefined && val !== null && String(val).trim() !== ''){
                        if(!el.value.trim()){
                            el.value = String(val).trim();
                            el.dispatchEvent(new Event('input', {bubbles:true}));
                            filled++;
                        }
                    }
                });
                if(filled>0) console.log('Clinical Chart auto-filled from server extracted data', filled, 'fields');
            }
            // Attach Auto-fill button handler (once)
            const autoFillBtn=document.getElementById('autoFillChartBtn');
            if(autoFillBtn && !autoFillBtn.dataset.bound){
                autoFillBtn.dataset.bound='1';
                autoFillBtn.addEventListener('click', async function(){
                    const transcript = event.detail?.transcription || document.getElementById('react-transcript-container')?.innerText || '';
                    if(!transcript || transcript.trim().length<20){ alert('No transcript available to extract chart from.'); return; }
                    const orig = autoFillBtn.innerHTML;
                    autoFillBtn.disabled=true; autoFillBtn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Filling...';
                    try{
                        const res = await fetch('{{ route("ai.ambient-listening.generate-ai-analysis") }}', {
                            method:'POST',
                            headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                            body: new URLSearchParams({transcription: transcript, sessionId: window.sessionId || document.querySelector('[data-session-id]')?.getAttribute('data-session-id') || '', selectedPatient: document.getElementById('patientSelect')?.value || '', _token:'{{ csrf_token() }}'})
                        });
                        const data = await res.json();
                        if(data.success && data.aiAnalysis){
                            const symEl=document.getElementById('symptoms');
                            if(symEl && !symEl.value.trim()){
                                const firstPart = data.aiAnalysis.split('\n').filter(l=>l.trim()).slice(0,4).join('\n');
                                symEl.value = firstPart.substring(0,300);
                                symEl.dispatchEvent(new Event('input',{bubbles:true}));
                            }
                            alert('Clinical Chart auto-filled from AI analysis. Please review and edit as needed.');
                        } else {
                            alert('Could not auto-fill chart: ' + (data.message || 'Unknown error'));
                        }
                    }catch(e){ alert('Error auto-filling chart: '+e.message); }
                    finally{ autoFillBtn.disabled=false; autoFillBtn.innerHTML=orig; }
                });
            }
        });

        // Add click handler for AI Analysis button
        document.getElementById('generateAnalysisBtn').addEventListener('click', function() {
            console.log('AI Analysis button clicked');
            const transcriptContainer = document.getElementById('react-transcript-container');
            const transcript = transcriptContainer ? transcriptContainer.innerText.trim() : '';
            
            console.log('Sending transcript to AI:', transcript);
            console.log('Transcript length:', transcript.length);
            
            if (!transcript || transcript.length < 20) {
                alert('Please record more audio. Transcript is too short for analysis.');
                return;
            }
            
            const patientSelect = document.getElementById('patientSelect');
            if (!patientSelect || !patientSelect.value) {
                alert('Please select a patient first');
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyzing...';
            
            fetch('{{ route("ai.ambient-listening.generate-ai-analysis") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new URLSearchParams({
                    transcription: transcript,
                    sessionId: sessionId || '',
                    selectedPatient: patientSelect.value,
                    _token: '{{ csrf_token() }}'
                })
            })
            .then(r => {
                if (!r.ok) throw new Error('Server error: ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    // Fill Clinical Chart from AI analysis (fallback only - empty fields) - safe, no overwrite
                    try{
                        const txt = data.aiAnalysis || '';
                        const fillIfEmpty = (id, val) => {
                            const el=document.getElementById(id);
                            if(el && !el.value.trim() && val && String(val).trim()){
                                el.value = String(val).trim().substring(0,500);
                                el.dispatchEvent(new Event('input',{bubbles:true}));
                            }
                        };
                        const extractSection = (re) => { const m=txt.match(re); return m ? m[1].trim() : ''; };
                        // Heuristic parsing of AI markdown
                        fillIfEmpty('symptoms', extractSection(/\*\*Symptoms\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|\n🔍|\n💊|\n🧪|\n⚠️|\n🔵|$)/i));
                        fillIfEmpty('medicalHistory', extractSection(/\*\*Medical History\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|\n🔍|\n💊|\n🧪|\n⚠️|\n🔵|$)/i) || extractSection(/\*\*Relevant History\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|$)/i));
                        fillIfEmpty('physicalFindings', extractSection(/\*\*Physical Findings\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|$)/i));
                        fillIfEmpty('medications', extractSection(/\*\*Current Medications\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|$)/i) || extractSection(/\*\*Medications\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|$)/i));
                        fillIfEmpty('vitalSigns', extractSection(/\*\*Vital Signs\*\*[:\s]*\n?([\s\S]*?)(?=\n\*\*|$)/i));
                        const diagMatch = txt.match(/1\.\s*\*?\*?([^*\n]+)\*?\*?\s*\(Probability/i);
                        if(diagMatch) fillIfEmpty('diagnosis', diagMatch[1].trim());
                        const planMatch = txt.match(/💊\s*INITIAL\s*MANAGEMENT\s*PLAN:([\s\S]*?)(?=⚠️|---|🔵|$)/i);
                        if(planMatch) fillIfEmpty('carePlan', planMatch[1].trim());
                        // Fallback: if still empty symptoms, use first lines
                        const symEl=document.getElementById('symptoms');
                        if(symEl && !symEl.value.trim()){
                            const firstPart = txt.split('\n').filter(l=>l.trim()).slice(0,4).join('\n');
                            if(firstPart) fillIfEmpty('symptoms', firstPart);
                        }
                    }catch(e){ console.warn('Chart fill from AI analysis failed', e); }
                    // Persist AI result for Voice Assistant Diagnosis (so AI Clinical Data Sources shows Available)
                    try{
                        const fd = new URLSearchParams({sessionId: sessionId || '', transcription: transcript, selectedPatient: patientSelect.value, aiAnalysis: data.aiAnalysis, _token: '{{ csrf_token() }}'});
                        fetch('{{ route("ai.ambient-listening.create-ai-result") }}', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: fd}).then(r=>r.json()).then(j=> console.log('AI result persisted', j)).catch(e=> console.warn('Persist AI result failed', e));
                    }catch(e){}
                    // Format the AI analysis for professional display
                    const formattedAnalysis = formatAIAnalysis(data.aiAnalysis);
                    
                    // Show in modal
                    const modalHtml = `
                        <div class="modal fade" id="aiAnalysisModal" tabindex="-1">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header bg-gradient-primary text-white">
                                        <h5 class="modal-title"><i class="fas fa-brain me-2"></i>AI Medical Analysis</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                                        ${formattedAnalysis}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-primary" onclick="copyAnalysis()"><i class="fas fa-copy me-1"></i>Copy</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
                    modal.show();
                    document.getElementById('aiAnalysisModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                } else {
                    alert('Error: ' + (data.message || 'Failed'));
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-brain me-1"></i>AI Analysis';
            })
            .catch(e => {
                alert('Error: ' + e.message);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-brain me-1"></i>AI Analysis';
            });
        });

        // Fallback: Enable buttons after recording stops (with delay to ensure status is set)
        window.addEventListener('statusUpdate', function(event) {
            const status = event.detail.status;
            if (status === 'stopped' || status === 'idle') {
                // Don't enable buttons here - wait for serverTranscriptReady event
                console.log('Recording stopped, waiting for server processing...');
            }
        });

        // Initialize status indicators - accuracy hidden until real confidence
        updateRecordingStatus('idle');

        // Format AI Analysis for professional display
        window.formatAIAnalysis = function(text) {
            if (!text) return '<p class="text-muted">No analysis available</p>';
            
            // Decode HTML entities
            text = text.replace(/&#39;/g, "'").replace(/&quot;/g, '"').replace(/&amp;/g, '&');
            
            // Convert markdown-style formatting to HTML
            let html = text
                // Headers
                .replace(/^🟢 (.+)$/gm, '<div class="alert alert-success mt-4 mb-3"><h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i>$1</h4></div>')
                .replace(/^🔵 (.+)$/gm, '<div class="alert alert-info mt-4 mb-3"><h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>$1</h4></div>')
                .replace(/^📋 (.+)$/gm, '<h5 class="text-primary mt-3 mb-2"><i class="fas fa-clipboard me-2"></i>$1</h5>')
                .replace(/^🔍 (.+)$/gm, '<h5 class="text-info mt-3 mb-2"><i class="fas fa-search me-2"></i>$1</h5>')
                .replace(/^🚨 (.+)$/gm, '<h5 class="text-danger mt-3 mb-2"><i class="fas fa-exclamation-triangle me-2"></i>$1</h5>')
                .replace(/^🧪 (.+)$/gm, '<h5 class="text-success mt-3 mb-2"><i class="fas fa-flask me-2"></i>$1</h5>')
                .replace(/^💊 (.+)$/gm, '<h5 class="text-warning mt-3 mb-2"><i class="fas fa-pills me-2"></i>$1</h5>')
                .replace(/^⚠️ (.+)$/gm, '<h5 class="text-danger mt-3 mb-2"><i class="fas fa-exclamation-circle me-2"></i>$1</h5>')
                .replace(/^💡 (.+)$/gm, '<h5 class="text-info mt-3 mb-2"><i class="fas fa-lightbulb me-2"></i>$1</h5>')
                // Bold text
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                // Bullet points
                .replace(/^• (.+)$/gm, '<li class="mb-1">$1</li>')
                // Numbered lists
                .replace(/^(\d+)\. \*\*(.+?)\*\*/gm, '<div class="card mb-2"><div class="card-body py-2"><strong class="text-primary">$1. $2</strong></div></div>')
                // Horizontal rules
                .replace(/^---$/gm, '<hr class="my-4">')
                // Line breaks
                .replace(/\n\n/g, '</p><p class="mb-2">')
                .replace(/\n/g, '<br>');
            
            // Wrap in paragraphs
            html = '<div class="formatted-analysis" style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.6;"><p class="mb-2">' + html + '</p></div>';
            
            // Wrap consecutive <li> in <ul>
            html = html.replace(/(<li[^>]*>.*?<\/li>\s*)+/gs, '<ul class="mb-3">$&</ul>');
            
            return html;
        };

        // Copy analysis to clipboard
        window.copyAnalysis = function() {
            const analysisText = document.querySelector('#aiAnalysisModal .modal-body').innerText;
            navigator.clipboard.writeText(analysisText).then(() => {
                alert('Analysis copied to clipboard!');
            });
        };

        // Add functionality to transcript controls
        const copyTranscriptBtn = document.getElementById('copyTranscriptBtn');
        const clearTranscriptBtn = document.getElementById('clearTranscriptBtn');
        const exportTranscriptBtn = document.getElementById('exportTranscriptBtn');

        if (copyTranscriptBtn) {
            copyTranscriptBtn.addEventListener('click', function() {
                const transcriptContainer = document.getElementById('react-transcript-container');
                if (transcriptContainer) {
                    const text = transcriptContainer.innerText || transcriptContainer.textContent;
                    if (!text.trim()) {
                        alert('No transcript to copy');
                        return;
                    }
                    navigator.clipboard.writeText(text).then(function() {
                        const originalHTML = copyTranscriptBtn.innerHTML;
                        copyTranscriptBtn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
                        copyTranscriptBtn.classList.remove('btn-outline-secondary');
                        copyTranscriptBtn.classList.add('btn-success');
                        setTimeout(function() {
                            copyTranscriptBtn.innerHTML = originalHTML;
                            copyTranscriptBtn.classList.add('btn-outline-secondary');
                            copyTranscriptBtn.classList.remove('btn-success');
                        }, 2000);
                    });
                }
            });
        }

        if (clearTranscriptBtn) {
            clearTranscriptBtn.addEventListener('click', function() {
                const transcriptContainer = document.getElementById('react-transcript-container');
                if (!transcriptContainer || !transcriptContainer.innerText.trim()) {
                    alert('No transcript to clear');
                    return;
                }
                if (confirm('Are you sure you want to clear the transcript? This cannot be undone.')) {
                    window.dispatchEvent(new CustomEvent('clearTranscript'));
                    transcriptContainer.innerHTML = '';
                }
            });
        }

        if (exportTranscriptBtn) {
            exportTranscriptBtn.addEventListener('click', function() {
                const transcriptContainer = document.getElementById('react-transcript-container');
                if (transcriptContainer) {
                    const text = transcriptContainer.innerText || transcriptContainer.textContent;
                    if (!text.trim()) {
                        alert('No transcript to export');
                        return;
                    }
                    const blob = new Blob([text], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `transcript-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.txt`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    const originalHTML = exportTranscriptBtn.innerHTML;
                    exportTranscriptBtn.innerHTML = '<i class="fas fa-check me-1"></i> Exported!';
                    exportTranscriptBtn.classList.remove('btn-outline-primary');
                    exportTranscriptBtn.classList.add('btn-success');
                    setTimeout(function() {
                        exportTranscriptBtn.innerHTML = originalHTML;
                        exportTranscriptBtn.classList.add('btn-outline-primary');
                        exportTranscriptBtn.classList.remove('btn-success');
                    }, 2000);
                }
            });
        }

        // Add event listener for when React component updates status
        window.addEventListener('statusUpdate', function(event) {
            const status = event.detail.status;
            console.log('Status update received:', status);
            updateRecordingStatus(status);

            // Enable AI Analysis and Clinical Doc buttons when recording stops
            const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
            const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

            console.log('Button states before update:', {
                analysisDisabled: generateAnalysisBtn?.disabled,
                clinicalDisabled: generateClinicalDocBtn?.disabled,
                status: status
            });

            if (status === 'stopped') {
                console.log('Enabling buttons for stopped status');
                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = false;
                    generateAnalysisBtn.style.opacity = '1';
                    console.log('Analysis button enabled');
                }
                if (generateClinicalDocBtn) {
                    generateClinicalDocBtn.disabled = false;
                    generateClinicalDocBtn.style.opacity = '1';
                    console.log('Clinical doc button enabled');
                }
            } else if (status === 'idle' || status === 'recording') {
                // Disable buttons when not stopped
                console.log('Disabling buttons for status:', status);
                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = true;
                    generateAnalysisBtn.style.opacity = '0.6';
                }
                if (generateClinicalDocBtn) {
                    generateClinicalDocBtn.disabled = true;
                    generateClinicalDocBtn.style.opacity = '0.6';
                }
            }

            console.log('Button states after update:', {
                analysisDisabled: generateAnalysisBtn?.disabled,
                clinicalDisabled: generateClinicalDocBtn?.disabled
            });
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to start/stop recording
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                // Simulate click on the recording button
                const recordingBtn = document.querySelector('.ambient-recorder-container .btn:not(.disabled)');
                if (recordingBtn) {
                    recordingBtn.click();
                }
            }

            // Alt + T to focus on transcript area
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                const transcriptContainer = document.querySelector('.transcript-container');
                if (transcriptContainer) {
                    transcriptContainer.focus();
                    // Scroll to the bottom of the transcript
                    transcriptContainer.scrollTop = transcriptContainer.scrollHeight;
                }
            }
        });

        // AI Analysis functionality handled by button-click-handlers.js
        // Removed duplicate code - using external handler

        // Removed duplicate generateAIAnalysis - using button-click-handlers.js

        // Removed duplicate generateClinicalDoc - button removed from UI

        // Update clinical chart fields with AI results
        function updateClinicalFields(data) {
            if (data.symptoms) {
                const symptomsField = document.getElementById('symptoms');
                if (symptomsField) symptomsField.value = data.symptoms;
            }

            if (data.medical_history) {
                const medicalHistoryField = document.getElementById('medicalHistory');
                if (medicalHistoryField) medicalHistoryField.value = data.medical_history;
            }

            if (data.physical_findings) {
                const physicalFindingsField = document.getElementById('physicalFindings');
                if (physicalFindingsField) physicalFindingsField.value = data.physical_findings;
            }

            if (data.medications) {
                const medicationsField = document.getElementById('medications');
                if (medicationsField) medicationsField.value = data.medications;
            }

            if (data.vital_signs) {
                const vitalSignsField = document.getElementById('vitalSigns');
                if (vitalSignsField) vitalSignsField.value = data.vital_signs;
            }

            if (data.diagnosis) {
                const diagnosisField = document.getElementById('diagnosis');
                if (diagnosisField) diagnosisField.value = data.diagnosis;
            }

            if (data.care_plan) {
                const carePlanField = document.getElementById('carePlan');
                if (carePlanField) carePlanField.value = data.care_plan;
            }
        }

        // Alert function for user feedback
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            if (!alertContainer) return;

            const alertClass = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            const alertHTML = `
                <div class="${alertClass}" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            alertContainer.innerHTML = alertHTML;

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        // Custom Advanced Controls Toggle Implementation
        function initializeAdvancedControls() {
            console.log('Initializing Advanced Controls toggle');

            const toggleBtn = document.getElementById('advancedControlsToggleBtn');
            const advancedControlsDiv = document.getElementById('voiceAssistantAdvancedControls');

            if (toggleBtn && advancedControlsDiv) {
                console.log('Advanced controls elements found, setting up toggle');

                // Track the state of the advanced controls
                let advancedControlsVisible = false;

                // Remove any existing event listeners to prevent duplicates
                const newToggleBtn = toggleBtn.cloneNode(true);
                toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);

                newToggleBtn.addEventListener('click', function() {
                    console.log('Advanced controls toggle button clicked');

                    if (advancedControlsVisible) {
                        // Hide the controls
                        advancedControlsDiv.style.display = 'none';
                        newToggleBtn.innerHTML = '<i class="fas fa-cog me-1"></i> Advanced Controls';
                        advancedControlsVisible = false;
                        console.log('Advanced controls hidden');
                    } else {
                        // Show the controls
                        advancedControlsDiv.style.display = 'block';
                        newToggleBtn.innerHTML = '<i class="fas fa-cog me-1"></i> Advanced Controls (Hide)';
                        advancedControlsVisible = true;
                        console.log('Advanced controls shown');
                    }
                });

                console.log('Advanced controls toggle initialized successfully');
            } else {
                console.log('Advanced controls elements not found:', {
                    toggleBtn: !!toggleBtn,
                    advancedControlsDiv: !!advancedControlsDiv
                });
            }
        }

        // Initialize when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeAdvancedControls();
            // Removed - using button-click-handlers.js
                initializeButtonStates();
            });
        } else {
            // DOM is already ready, initialize immediately
            initializeAdvancedControls();
            // Removed - using button-click-handlers.js
            initializeButtonStates();
        }

        // Initialize button states based on available content
        function initializeButtonStates() {
            // Check if there's any transcript content available
            const transcriptContainer = document.querySelector('.transcript-container');
            const transcriptionArea = document.getElementById('transcriptionArea');

            let hasContent = false;
            if (transcriptContainer && transcriptContainer.innerText.trim()) {
                hasContent = true;
            } else if (transcriptionArea && transcriptionArea.value.trim()) {
                hasContent = true;
            }

            // If there's content, enable the buttons
            if (hasContent) {
                console.log('Content detected on page load - enabling buttons');
                const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
                const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = false;
                    generateAnalysisBtn.style.opacity = '1';
                }
                if (generateClinicalDocBtn) {
                    generateClinicalDocBtn.disabled = false;
                    generateClinicalDocBtn.style.opacity = '1';
                }
            }
        }
    });

    // New patient form toggle
    const showNewPatientFormBtn = document.getElementById('showNewPatientFormBtn');
    const hideNewPatientFormBtn = document.getElementById('hideNewPatientFormBtn');
    const cancelNewPatientBtn = document.getElementById('cancelNewPatientBtn');
    const newPatientForm = document.getElementById('newPatientForm');

    if (showNewPatientFormBtn && newPatientForm) {
        showNewPatientFormBtn.addEventListener('click', () => {
            newPatientForm.style.display = 'block';
            newPatientForm.scrollIntoView({ behavior: 'smooth' });
        });
    }

    if (hideNewPatientFormBtn && newPatientForm) {
        hideNewPatientFormBtn.addEventListener('click', () => {
            newPatientForm.style.display = 'none';
        });
    }

    if (cancelNewPatientBtn && newPatientForm) {
        cancelNewPatientBtn.addEventListener('click', () => {
            newPatientForm.style.display = 'none';
        });
    }

    // Write Directly button - enable when patient is selected
    const writeDirectlyBtn = document.getElementById('writeDirectlyBtn');
    const patientSelect = document.getElementById('patientSelect');

    if (writeDirectlyBtn && patientSelect) {
        // Enable/disable button based on patient selection
        patientSelect.addEventListener('change', function() {
            writeDirectlyBtn.disabled = !this.value;
        });

        // Initial state - disable if no patient selected
        writeDirectlyBtn.disabled = !patientSelect.value;

        // Click handler - show diagnosis entry form directly
        writeDirectlyBtn.addEventListener('click', function() {
            if (!patientSelect.value) {
                alert('Please select a patient first');
                return;
            }

            const diagnosisForm = document.getElementById('diagnosisEntryForm');
            if (diagnosisForm) {
                diagnosisForm.style.display = 'block';
                diagnosisForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Focus on the diagnosis text area
                setTimeout(() => {
                    const diagnosisText = document.getElementById('diagnosisText');
                    if (diagnosisText) {
                        diagnosisText.focus();
                    }
                }, 300);
            }
        });
    }

    // Cancel diagnosis button - hide form when cancelled
    const cancelDiagnosisBtn = document.getElementById('cancelDiagnosisBtn');
    if (cancelDiagnosisBtn) {
        cancelDiagnosisBtn.addEventListener('click', function() {
            const diagnosisForm = document.getElementById('diagnosisEntryForm');
            if (diagnosisForm) {
                diagnosisForm.style.display = 'none';
                // Clear the diagnosis text
                const diagnosisText = document.getElementById('diagnosisText');
                if (diagnosisText) {
                    diagnosisText.value = '';
                }
            }
        });
    }

    // Create new patient button handler
    const createNewPatientBtn = document.getElementById('createNewPatientBtn');
    if (createNewPatientBtn) {
        createNewPatientBtn.addEventListener('click', function() {
            const nameField = document.getElementById('newPatientName');
            const phoneField = document.getElementById('newPatientPhone');
            const emailField = document.getElementById('newPatientEmail');
            const ageField = document.getElementById('newPatientAge');
            const genderField = document.getElementById('newPatientGender');
            
            const name = nameField ? nameField.value.trim() : '';
            const phone = phoneField ? phoneField.value.trim() : '';
            const email = emailField ? emailField.value.trim() : '';
            const age = ageField ? parseInt(ageField.value) || 25 : 25;
            const gender = genderField ? genderField.value : 'male';
            
            console.log('Creating patient with:', {name, phone, email, age, gender});
            
            if (!name || !age || !gender) {
                alert('Name, age, and gender are required');
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
            
            fetch('{{ route("ai.ambient-listening.create-new-patient") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({
                    newPatientName: name,
                    newPatientPhone: phone,
                    newPatientEmail: email,
                    newPatientAge: age,
                    newPatientGender: gender,
                    _token: '{{ csrf_token() }}'
                })
            })
            .then(r => {
                console.log('Create patient response:', r.status);
                return r.text().then(text => {
                    console.log('Response text:', text.substring(0, 200));
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Not JSON, full response:', text);
                        throw new Error('Server returned HTML instead of JSON');
                    }
                });
            })
            .then(data => {
                console.log('Create patient response:', data);
                if (data.success) {
                    alert('Patient created successfully!');
                    // Add to dropdown and select it - trigger change to enable Write Directly button
                    const select = document.getElementById('patientSelect');
                    const patientLabel = `${data.patient.name} (${data.patient.age || '?'}y, ${data.patient.gender || 'Unknown'})`;
                    const option = new Option(patientLabel, data.patient.id, true, true);
                    select.add(option);
                    select.dispatchEvent(new Event('change', {bubbles:true}));
                    // Directly enable Write Directly button as fallback
                    const writeBtn = document.getElementById('writeDirectlyBtn');
                    if(writeBtn) writeBtn.disabled = false;
                    // Hide form
                    document.getElementById('newPatientForm').style.display = 'none';
                    // Clear form
                    if (nameField) nameField.value = '';
                    if (phoneField) phoneField.value = '';
                    if (emailField) emailField.value = '';
                    if (ageField) ageField.value = '';
                    if (genderField) genderField.value = '';
                } else {
                    alert('Error: ' + (data.message || 'Failed to create patient'));
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Patient';
            })
            .catch(e => {
                alert('Error: ' + e.message);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Patient';
            });
        });
    }

    // Enable complete button when diagnosis is filled
    const diagnosisText = document.getElementById('diagnosisText');
    const completeBtn = document.getElementById('completeConsultationBtn');
    if (diagnosisText && completeBtn) {
        diagnosisText.addEventListener('input', function() {
            completeBtn.disabled = this.value.trim().length === 0;
        });
        
        completeBtn.addEventListener('click', function() {
            const diagnosis = diagnosisText.value.trim();
            if (!diagnosis) {
                alert('Please enter your diagnosis first.');
                return;
            }
            
            const patientSelect = document.getElementById('patientSelect');
            const selectedPatient = patientSelect ? patientSelect.value : null;
            const sessionId = window.sessionId || document.querySelector('[data-session-id]')?.getAttribute('data-session-id');
            const transcriptContainer = document.getElementById('react-transcript-container');
            const transcription = transcriptContainer ? (transcriptContainer.innerText || transcriptContainer.textContent || '').trim() : '';
            
            if (!selectedPatient) {
                alert('Please select a patient.');
                return;
            }
            
            if (!transcription) {
                alert('No transcript available. Please record a session first.');
                return;
            }
            
            // Show modal with additional patient data fields
            const modal = new bootstrap.Modal(document.getElementById('completeConsultationModal'));
            document.getElementById('diagnosisPreview').textContent = diagnosis;
            document.getElementById('modalPatientName').textContent = patientSelect.options[patientSelect.selectedIndex].text;
            modal.show();
        });
    }
    
    // Handle modal complete button
    const modalCompleteBtn = document.getElementById('modalCompleteConsultationBtn');
    if (modalCompleteBtn) {
        modalCompleteBtn.addEventListener('click', function() {
            const diagnosisText = document.getElementById('diagnosisText');
            const diagnosis = diagnosisText.value.trim();
            const patientSelect = document.getElementById('patientSelect');
            const selectedPatient = patientSelect ? patientSelect.value : null;
            const sessionId = window.sessionId || document.querySelector('[data-session-id]')?.getAttribute('data-session-id');
            const transcriptContainer = document.getElementById('react-transcript-container');
            const transcription = transcriptContainer ? (transcriptContainer.innerText || transcriptContainer.textContent || '').trim() : '';
            
            // Get additional patient data
            const allergies = document.getElementById('modal_allergies').value.trim();
            const medications = document.getElementById('modal_medications').value.trim();
            const symptoms = document.getElementById('modal_symptoms').value.trim();
            const medicalHistory = document.getElementById('modal_medical_history').value.trim();
            
            modalCompleteBtn.disabled = true;
            modalCompleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            
            fetch('/ai/ambient-listening/complete-consultation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    diagnosisText: diagnosis,
                    selectedPatient: selectedPatient,
                    transcription: transcription,
                    sessionId: sessionId,
                    completionType: 'complete_appointment',
                    patient_data: {
                        allergies: allergies,
                        medications: medications,
                        symptoms: symptoms,
                        medical_history: medicalHistory
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Consultation completed successfully!');
                    if (data.redirectUrl) {
                        window.location.href = data.redirectUrl;
                    } else {
                        location.reload();
                    }
                } else {
                    throw new Error(data.message || 'Failed to complete consultation');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                modalCompleteBtn.disabled = false;
                modalCompleteBtn.innerHTML = '<i class="fas fa-check me-1"></i>Complete Session';
            });
        });
    }

    // Reset button functionality
    const resetBtn = document.getElementById('resetSessionBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset? This will clear the current session.')) {
                location.reload();
            }
        });
    }

    // Show diagnosis form after recording stops
    window.addEventListener('serverTranscriptReady', function() {
        const diagnosisForm = document.getElementById('diagnosisEntryForm');
        if (diagnosisForm) {
            diagnosisForm.style.display = 'block';
            diagnosisForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
</script>

<!-- Form components are now initialized by the main ambient listening script -->
<!-- This ensures proper timing and prevents conflicts -->
@endsection
