@extends('master')

@section('title', 'Landing Page Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
/* Header — unified with hep/show & appointments/show */
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%) !important;
    border-radius: 12px !important;
    padding: 2.5rem !important;
    margin-bottom: 2rem !important;
    box-shadow: 0 4px 15px rgba(44, 90, 160, 0.15) !important;
    position: relative; overflow: hidden;
}
.dashboard-header::before {
    content:''; position:absolute; top:0;left:0;right:0;height:3px;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}
.dashboard-header h2 { color:#fff !important; font-weight:600 !important; font-size:2.2rem !important; margin-bottom:0.5rem !important; }
.dashboard-header p { color:rgba(255,255,255,0.9) !important; font-size:1rem !important; margin-bottom:0 !important; }
.header-actions-wrap { display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap; justify-content:flex-end; }
.dashboard-header .status-badge {
    background:#fff !important; color:#1e293b !important; border:1px solid #e2e8f0 !important;
    box-shadow:0 1px 3px rgba(0,0,0,0.08) !important; border-radius:99px !important;
    padding:0.38rem 0.85rem !important; font-size:0.73rem !important; font-weight:700 !important; letter-spacing:0 !important;
}
.dashboard-header .status-badge.published { color:#065f46 !important; background:#d1fae5 !important; border-color:#a7f3d0 !important; }
.dashboard-header .status-badge.draft { color:#92400e !important; background:#fef3c7 !important; border-color:#fde68a !important; }
.btn-back {
    background:rgba(255,255,255,0.15) !important; border:1px solid rgba(255,255,255,0.32) !important;
    color:#fff !important; border-radius:10px !important; padding:0.5rem 1rem !important;
    font-weight:600 !important; font-size:0.83rem !important; transition:all .18s ease !important;
}
.btn-back:hover { background:#fff !important; color:#1e3a8a !important; border-color:#fff !important; }
.action-btn {
    border-radius:10px !important; padding:0.52rem 1.05rem !important; font-size:0.81rem !important;
    font-weight:700 !important; display:inline-flex !important; align-items:center !important; gap:0.4rem !important;
    border:1px solid transparent !important; box-shadow:0 1px 3px rgba(0,0,0,0.10) !important; transition:all .18s ease !important;
}
.action-btn:hover { transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,0.14) !important; }
.action-btn-primary { background:#fff !important; color:#1e3a8a !important; border-color:#fff !important; }
.action-btn-primary:hover { background:#f1f5f9 !important; }
.action-btn-success { background:#10b981 !important; color:#fff !important; border-color:#10b981 !important; }
.action-btn-success:hover { background:#059669 !important; }
.action-btn-outline { background:rgba(255,255,255,0.15) !important; color:#fff !important; border-color:rgba(255,255,255,0.32) !important; }
.action-btn-outline:hover { background:#fff !important; color:#1e3a8a !important; }
@media (max-width: 992px) { .header-actions-wrap{ width:100%; justify-content:space-between; } }
@media (max-width: 576px) { .dashboard-header{ padding:1.5rem !important; } }

/* Section headers premium */
.table-card { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 6px 20px rgba(44,62,80,.05),0 1px 6px rgba(44,62,80,.04); overflow:hidden; }
.table-card .section-head-modern { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:1rem 1.3rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; flex-wrap:wrap; }
.table-card .section-head-modern .head-left{ display:flex; align-items:center; gap:0.75rem; }
.table-card .section-head-modern .head-icon{ width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem; background:#1e293b !important; color:#fff !important; border:1px solid #1e293b !important; }
.table-card .section-head-modern h5{ margin:0;font-weight:800;color:#0f172a;letter-spacing:-0.01em; font-size:1rem; }
.table-card .section-head-modern p{ margin:2px 0 0;font-size:0.76rem;color:#64748b;font-weight:500; }
.table-card .card-body-premium{ padding:1.25rem; }

/* Premium segmented tabs — replaces nav-tabs */
.settings-tabs {
    display:flex; gap:0.25rem; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:0.25rem; flex-wrap:wrap;
}
.settings-tabs .nav-link {
    border:none !important; background:transparent !important; color:#64748b !important;
    border-radius:8px !important; font-size:0.78rem; font-weight:700; padding:0.45rem 0.75rem;
    transition:all .18s ease;
}
.settings-tabs .nav-link.active {
    background:#fff !important; color:#0f172a !important; border:1px solid #e2e8f0 !important;
    box-shadow:0 1px 4px rgba(15,23,42,0.06) !important;
}
.settings-tabs .nav-link:hover:not(.active){ background:#fff !important; color:#1e293b !important; }

/* Form premium */
.form-label{ font-size:0.82rem; font-weight:600; color:#334155; margin-bottom:0.35rem; }
.form-control, .form-select{ border-radius:10px !important; border:1px solid #e2e8f0 !important; font-size:0.88rem; }
.form-control:focus, .form-select:focus{ border-color:#2c5aa0 !important; box-shadow:0 0 0 3px rgba(44,90,160,0.12) !important; }
.form-control-color{ width:100%; height:38px; border-radius:10px; padding:0.2rem; }
.input-group .input-group-text{ background:#f8fafc; border:1px solid #e2e8f0; font-size:0.82rem; color:#64748b; font-weight:600; }
.input-group .form-control{ border-left:0; }
.form-check-input:checked{ background-color:#2c5aa0; border-color:#2c5aa0; }
.form-switch .form-check-label strong{ font-size:0.88rem; color:#1e293b; }
.form-switch .form-check-label .text-muted{ font-size:0.76rem; }

/* Preview device toggle */
.preview-device-toggle{ background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:0.25rem; gap:0.25rem; }
.preview-device-toggle .btn{ border:none !important; background:transparent !important; color:#64748b !important; border-radius:8px !important; padding:0.38rem 0.65rem; font-size:0.82rem; }
.preview-device-toggle .btn.active{ background:#fff !important; color:#0f172a !important; border:1px solid #e2e8f0 !important; box-shadow:0 1px 4px rgba(15,23,42,0.06) !important; }
#previewFrame{ width:100%; height:600px; border:none; transition:all 0.3s ease; }
#previewFrame.tablet{ width:768px; margin:0 auto; display:block; }
#previewFrame.mobile{ width:375px; margin:0 auto; display:block; }
.live-dot{ width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block; animation:pulse 2s infinite; }
@keyframes pulse{ 0%,100%{opacity:1} 50%{opacity:.6} }

/* Alert premium */
.alert-premium{ border-radius:12px; border:1px solid; padding:0.85rem 1rem; font-size:0.86rem; }
.alert-premium.alert-success{ background:#f0fdf4; border-color:#dcfce7; color:#166534; }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container appointment-details">

        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-globe me-2"></i>Landing Page</h2>
                    <p>Your public practice site — build, preview and publish in one place</p>
                </div>
                <div class="header-actions-wrap">
                    <span class="status-badge {{ $landingPage->is_published ? 'published' : 'draft' }}">
                        <span class="live-dot me-1" style="{{ $landingPage->is_published ? '' : 'background:#f59e0b' }}"></span>
                        {{ $landingPage->is_published ? 'Published' : 'Draft' }}
                    </span>
                    <a href="{{ route('doctor.landing-page.page-builder') }}" class="btn action-btn action-btn-primary">
                        <i class="fas fa-magic"></i> Page Builder
                    </a>
                    <button type="button" class="btn action-btn action-btn-outline" id="previewBtn">
                        <i class="fas fa-external-link-alt"></i> Preview
                    </button>
                    <button type="button" class="btn action-btn {{ $landingPage->is_published ? 'action-btn-success' : 'action-btn-outline' }}" id="publishBtn" style="{{ $landingPage->is_published ? '' : 'background:#fff !important;color:#1e3a8a !important;border-color:#fff !important;' }}">
                        <i class="fas {{ $landingPage->is_published ? 'fa-check-circle' : 'fa-rocket' }}"></i>
                        {{ $landingPage->is_published ? 'Published' : 'Publish' }}
                    </button>
                </div>
            </div>
        </div>

        @if($landingPage->is_published)
            <div class="alert alert-premium alert-success mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="fas fa-check-circle me-2"></i>Your landing page is <strong>live</strong> — patients can find you at <code>{{ $landingPage->url }}</code></span>
                <a href="{{ $landingPage->url }}" target="_blank" class="btn btn-sm" style="background:#fff;border:1px solid #dcfce7;color:#166534;border-radius:8px;font-weight:700;font-size:0.78rem;"><i class="fas fa-external-link-alt me-1"></i>View Public Page</a>
            </div>
        @else
            <div class="alert mb-4 d-flex align-items-center gap-2" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:12px;padding:0.85rem 1rem;font-size:0.86rem;">
                <i class="fas fa-info-circle"></i> Your page is in <strong>draft</strong> — publish to make it visible at <code>medcuraai.com/doctor/{{ $landingPage->username }}</code>
            </div>
        @endif

        <div class="row g-3">
            <!-- Settings Panel -->
            <div class="col-lg-5">
                <div class="table-card h-100">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon"><i class="fas fa-sliders-h"></i></div>
                            <div>
                                <h5>Page Settings</h5>
                                <p>Content · design · visibility · domain</p>
                            </div>
                        </div>
                        <span class="badge bg-light text-muted border" style="font-size:0.70rem;border-radius:99px;">6 tabs</span>
                    </div>
                    <div class="card-body-premium">
                        <ul class="nav settings-tabs mb-3" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab"><i class="fas fa-pen me-1"></i>Basic</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab"><i class="fas fa-palette me-1"></i>Design</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sections-tab" data-bs-toggle="tab" data-bs-target="#sections" type="button" role="tab"><i class="fas fa-layer-group me-1"></i>Sections</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="domain-tab" data-bs-toggle="tab" data-bs-target="#domain" type="button" role="tab"><i class="fas fa-link me-1"></i>Domain</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="language-tab" data-bs-toggle="tab" data-bs-target="#language" type="button" role="tab"><i class="fas fa-language me-1"></i>Language</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab"><i class="fas fa-chart-bar me-1"></i>Analytics</button>
                            </li>
                        </ul>

                        <form id="landingPageForm">
                            @csrf
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Basic Settings -->
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">medcuraai.com/doctor/</span>
                                            <input type="text" class="form-control" id="username" name="username" value="{{ $landingPage->username }}" required>
                                        </div>
                                        <div class="form-text" style="font-size:0.76rem;">Only letters, numbers, hyphens, and underscores allowed.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="template" class="form-label">Template</label>
                                        <select class="form-select" id="template" name="template">
                                            <option value="template1" {{ $landingPage->template === 'template1' ? 'selected' : '' }}>Modern Professional</option>
                                            <option value="template2" {{ $landingPage->template === 'template2' ? 'selected' : '' }}>Clean Minimal</option>
                                            <option value="template3" {{ $landingPage->template === 'template3' ? 'selected' : '' }}>Advanced Builder</option>
                                            <option value="template4" {{ $landingPage->template === 'template4' ? 'selected' : '' }}>Medical Focus</option>
                                        </select>
                                        <div class="form-text" style="font-size:0.76rem;">
                                            <strong>Advanced Builder</strong> supports page builder with custom sections.
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="page_title" class="form-label">Page Title</label>
                                        <input type="text" class="form-control" id="page_title" name="page_title" value="{{ $landingPage->page_title }}" maxlength="255" placeholder="e.g., Dr. Ahmed — Cardiology">
                                        <div class="form-text" style="font-size:0.76rem;">For SEO and browser title.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="page_description" class="form-label">Page Description</label>
                                        <textarea class="form-control" id="page_description" name="page_description" rows="3" maxlength="500" placeholder="Short practice description for search engines...">{{ $landingPage->page_description }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tagline" class="form-label">Tagline</label>
                                        <input type="text" class="form-control" id="tagline" name="tagline" value="{{ $landingPage->tagline }}" maxlength="255" placeholder="Compassionate care, modern medicine">
                                    </div>

                                    <div class="mb-3">
                                        <label for="about_text" class="form-label">About Text</label>
                                        <textarea class="form-control" id="about_text" name="about_text" rows="4" maxlength="2000" placeholder="Your bio and professional story...">{{ $landingPage->about_text }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="hero_image" class="form-label">Hero Image</label>
                                        <input type="file" class="form-control" id="hero_image" name="hero_image" accept="image/*">
                                        @if($landingPage->hero_image)
                                            <div class="mt-2 position-relative" style="display:inline-block;">
                                                <img src="{{ Storage::disk('public')->url($landingPage->hero_image) }}" alt="" class="img-thumbnail" style="max-height: 100px; border-radius:10px; border:1px solid #e2e8f0;" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('d-none');">
                                                <div class="d-none align-items-center justify-content-center border rounded bg-light" style="width:200px;height:100px;"><i class="fas fa-image text-muted"></i><small class="text-muted ms-2">No preview</small></div>
                                            </div>
                                        @endif
                                        <div class="form-text" style="font-size:0.76rem;">Recommended: 1200×600px · Max 2MB</div>
                                    </div>
                                </div>

                                <!-- Design Settings -->
                                <div class="tab-pane fade" id="design" role="tabpanel">
                                    <h6 class="mb-3 fw-bold" style="font-size:0.88rem;color:#1e293b;"><i class="fas fa-palette me-2 text-muted"></i>Color Scheme</h6>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label for="color_primary" class="form-label">Primary</label>
                                            <input type="color" class="form-control form-control-color" id="color_primary" name="colors[primary]" value="{{ $landingPage->colors['primary'] ?? '#3b82f6' }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="color_secondary" class="form-label">Secondary</label>
                                            <input type="color" class="form-control form-control-color" id="color_secondary" name="colors[secondary]" value="{{ $landingPage->colors['secondary'] ?? '#64748b' }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="color_accent" class="form-label">Accent</label>
                                            <input type="color" class="form-control form-control-color" id="color_accent" name="colors[accent]" value="{{ $landingPage->colors['accent'] ?? '#10b981' }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="color_button" class="form-label">Button</label>
                                            <input type="color" class="form-control form-control-color" id="color_button" name="colors[button]" value="{{ $landingPage->colors['button'] ?? '#3b82f6' }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="color_header_bg" class="form-label">Header Bg</label>
                                            <input type="color" class="form-control form-control-color" id="color_header_bg" name="colors[header_bg]" value="{{ $landingPage->colors['header_bg'] ?? '#ffffff' }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="color_footer_bg" class="form-label">Footer Bg</label>
                                            <input type="color" class="form-control form-control-color" id="color_footer_bg" name="colors[footer_bg]" value="{{ $landingPage->colors['footer_bg'] ?? '#f8fafc' }}">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-weight:600;" id="resetColors">
                                            <i class="fas fa-undo me-1"></i> Reset to Default
                                        </button>
                                    </div>
                                </div>

                                <!-- Section Visibility -->
                                <div class="tab-pane fade" id="sections" role="tabpanel">
                                    <h6 class="mb-3 fw-bold" style="font-size:0.88rem;color:#1e293b;"><i class="fas fa-eye me-2 text-muted"></i>Section Visibility</h6>
                                    @php $sections = [
                                        ['id'=>'hero','title'=>'Hero Section','desc'=>'Main banner with photo and tagline'],
                                        ['id'=>'about','title'=>'About Section','desc'=>'Bio and professional information'],
                                        ['id'=>'appointments','title'=>'Appointment Booking','desc'=>'Allow patients to book directly'],
                                        ['id'=>'reviews','title'=>'Reviews Section','desc'=>'Patient reviews and testimonials'],
                                        ['id'=>'contact','title'=>'Contact Section','desc'=>'Contact info and location'],
                                        ['id'=>'chat_widget','title'=>'Live Chat Widget','desc'=>'Visitors can chat directly'],
                                    ]; @endphp
                                    @foreach($sections as $s)
                                    <div class="d-flex align-items-center justify-content-between p-3 mb-2" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;">
                                        <div>
                                            <strong style="font-size:0.88rem;color:#1e293b;">{{ $s['title'] }}</strong>
                                            <div class="text-muted small" style="font-size:0.76rem;">{{ $s['desc'] }}</div>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" id="section_{{ $s['id'] }}" name="section_visibility[{{ $s['id'] }}]" {{ ($landingPage->section_visibility[$s['id']] ?? true) ? 'checked' : '' }} style="width:2.8em;height:1.4em;">
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <!-- Domain Settings -->
                                <div class="tab-pane fade" id="domain" role="tabpanel">
                                    <div class="mb-4">
                                        <h6 class="fw-bold" style="font-size:0.88rem;color:#1e293b;">Default URL</h6>
                                        <div class="input-group">
                                            <span class="input-group-text">medcuraai.com/doctor/</span>
                                            <input type="text" class="form-control" value="{{ $landingPage->username }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ route('doctor.landing', $landingPage->username) }}')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center justify-content-between p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;">
                                            <div><strong style="font-size:0.88rem;color:#1e293b;">Enable Subdomain</strong><div class="text-muted small" style="font-size:0.76rem;">{{ $landingPage->username }}.medcuraai.com</div></div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="subdomain_enabled" name="subdomain_enabled" {{ $landingPage->subdomain_enabled ? 'checked' : '' }} style="width:2.8em;height:1.4em;">
                                            </div>
                                        </div>
                                        <div class="mt-2" id="subdomainUrl" style="{{ $landingPage->subdomain_enabled ? '' : 'display: none;' }}">
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $landingPage->username }}.medcuraai.com" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('https://{{ $landingPage->username }}.medcuraai.com')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="custom_domain" class="form-label">Custom Domain</label>
                                        <input type="text" class="form-control" id="custom_domain" name="custom_domain" value="{{ $landingPage->custom_domain }}" placeholder="yourdomain.com">
                                        <div class="form-text" style="font-size:0.76rem;">Add a CNAME pointing to <code>medcuraai.com</code></div>
                                    </div>
                                    <div class="alert mb-0" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;font-size:0.82rem;">
                                        <h6 style="font-size:0.86rem;color:#1e40af;"><i class="fas fa-info-circle me-1"></i> Custom Domain Setup</h6>
                                        <ol class="mb-0 ps-3">
                                            <li>Go to registrar DNS settings</li>
                                            <li>Add CNAME → <code>medcuraai.com</code></li>
                                            <li>Enter domain above and save</li>
                                            <li>Up to 24h to propagate</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Language Settings -->
                                <div class="tab-pane fade" id="language" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="default_language" class="form-label">Default Language</label>
                                        <select class="form-select" id="default_language" name="default_language">
                                            <option value="en" {{ ($landingPage->default_language ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                                            <option value="ar" {{ ($landingPage->default_language ?? 'en') === 'ar' ? 'selected' : '' }}>العربية (Arabic)</option>
                                        </select>
                                    </div>
                                    <div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;">
                                        <h6 style="font-size:0.86rem;font-weight:700;color:#1e293b;"><i class="fas fa-language me-1"></i>Arabic Translations</h6>
                                        <p class="text-muted small mb-3" style="font-size:0.76rem;">Leave empty to fallback to English.</p>
                                        <div class="mb-3"><label class="form-label">Page Title (AR)</label><input type="text" class="form-control" name="translations[ar][page_title]" value="{{ $landingPage->translations['ar']['page_title'] ?? '' }}"></div>
                                        <div class="mb-3"><label class="form-label">Page Description (AR)</label><textarea class="form-control" name="translations[ar][page_description]" rows="2">{{ $landingPage->translations['ar']['page_description'] ?? '' }}</textarea></div>
                                        <div class="mb-3"><label class="form-label">Tagline (AR)</label><input type="text" class="form-control" name="translations[ar][tagline]" value="{{ $landingPage->translations['ar']['tagline'] ?? '' }}"></div>
                                        <div class="mb-3"><label class="form-label">About Text (AR)</label><textarea class="form-control" name="translations[ar][about_text]" rows="3">{{ $landingPage->translations['ar']['about_text'] ?? '' }}</textarea></div>
                                        <div class="mb-3"><label class="form-label">Appointment Title (AR)</label><input type="text" class="form-control" name="translations[ar][appointment_title]" value="{{ $landingPage->translations['ar']['appointment_title'] ?? '' }}"></div>
                                        <div class="mb-2"><label class="form-label">Form Labels (AR)</label>
                                            <div class="row g-2">
                                                @foreach([['form_name_label','Name'],['form_email_label','Email'],['form_phone_label','Phone'],['form_date_label','Date'],['form_time_label','Time'],['form_message_label','Message']] as $f)
                                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="translations[ar][{{ $f[0] }}]" placeholder="{{ $f[1] }}" value="{{ $landingPage->translations['ar'][$f[0]] ?? '' }}"></div>
                                                @endforeach
                                                <div class="col-12"><input type="text" class="form-control form-control-sm" name="translations[ar][form_submit_button]" placeholder="Submit button" value="{{ $landingPage->translations['ar']['form_submit_button'] ?? '' }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analytics Tab -->
                                <div class="tab-pane fade" id="analytics" role="tabpanel">
                                    <div class="text-center mb-3">
                                        <h6 class="fw-bold mb-1" style="color:#1e293b;">Landing Page Analytics</h6>
                                        <p class="text-muted small mb-0" style="font-size:0.76rem;">30-day performance</p>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6"><div class="p-3 text-center" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;"><h5 class="mb-0" style="color:#2563eb;font-weight:800;" id="totalVisits">-</h5><small class="text-muted" style="font-size:0.72rem;letter-spacing:0.05em;text-transform:uppercase;">Total Visits</small></div></div>
                                        <div class="col-6"><div class="p-3 text-center" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;"><h5 class="mb-0" style="color:#059669;font-weight:800;" id="uniqueVisitors">-</h5><small class="text-muted" style="font-size:0.72rem;letter-spacing:0.05em;text-transform:uppercase;">Unique Visitors</small></div></div>
                                    </div>
                                    <div class="mb-3"><label class="form-label" style="font-size:0.78rem;">Device Types</label><div id="deviceStats" class="small text-muted p-2" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:8px;">Loading...</div></div>
                                    <div class="text-center"><a href="{{ route('doctor.analytics.index') }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#1e293b;border-radius:8px;font-weight:600;font-size:0.78rem;"><i class="fas fa-chart-bar me-1"></i> View Full Analytics</a></div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex gap-2">
                                <button type="submit" class="btn action-btn" style="background:#1e293b;color:#fff;border-color:#1e293b;border-radius:10px;font-weight:700;"><i class="fas fa-save me-1"></i> Save Changes</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#64748b;border-radius:10px;font-weight:600;" onclick="location.reload()"><i class="fas fa-undo me-1"></i>Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="col-lg-7">
                <div class="table-card h-100">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon"><i class="fas fa-desktop"></i></div>
                            <div>
                                <h5>Live Preview</h5>
                                <p>Desktop · tablet · mobile</p>
                            </div>
                        </div>
                        <div class="btn-group preview-device-toggle" role="group">
                            <button type="button" class="btn active" data-preview-device="desktop" title="Desktop"><i class="fas fa-desktop"></i></button>
                            <button type="button" class="btn" data-preview-device="tablet" title="Tablet"><i class="fas fa-tablet-alt"></i></button>
                            <button type="button" class="btn" data-preview-device="mobile" title="Mobile"><i class="fas fa-mobile-alt"></i></button>
                        </div>
                    </div>
                    <div class="p-0">
                        <div id="previewContainer" class="position-relative" style="background:#f1f5f9; padding:1rem;">
                            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(15,23,42,0.08);border:1px solid #e2e8f0;">
                                <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                    <span class="d-inline-flex gap-1"><span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span><span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span><span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span></span>
                                    <small class="text-muted ms-2" style="font-size:0.72rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $landingPage->url ?? 'medcuraai.com/doctor/'.$landingPage->username }}</small>
                                </div>
                                <iframe id="previewFrame" src="{{ route('doctor.landing-page.preview', $landingPage->username) }}" style="width:100%;height:600px;border:none;"></iframe>
                            </div>
                            <div id="previewLoader" class="position-absolute top-50 start-50 translate-middle" style="display:none;">
                                <div class="spinner-border" style="color:#2c5aa0;" role="status"><span class="visually-hidden">Loading...</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let isPublished = {{ $landingPage->is_published ? 'true' : 'false' }};

    $('#landingPageForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        $('input[type="checkbox"]').each(function() {
            if (this.name.includes('section_visibility') || this.name === 'subdomain_enabled') {
                formData.set(this.name, this.checked ? '1' : '0');
            }
        });
        $.ajax({
            url: '{{ route("doctor.landing-page.update") }}',
            method: 'POST',
            data: formData,
            processData: false, contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    refreshPreview();
                    const newUsername = $('#username').val();
                    $('input[readonly]').each(function() {
                        if (this.value.includes('/doctor/')) {
                            this.value = this.value.replace(/\/doctor\/[^\/]+/, '/doctor/' + newUsername);
                        } else if (this.value.includes('.medcuraai.com')) {
                            this.value = newUsername + '.medcuraai.com';
                        }
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    let errorMessage = 'Please fix:\n';
                    Object.keys(errors).forEach(key => { errorMessage += '- ' + errors[key][0] + '\n'; });
                    showAlert('danger', errorMessage);
                } else {
                    showAlert('danger', 'An error occurred while saving changes.');
                }
            }
        });
    });

    $('#hero_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('hero_image', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            $.ajax({
                url: '{{ route("doctor.landing-page.upload-hero-image") }}',
                method: 'POST', data: formData, processData: false, contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        refreshPreview();
                        const imgPreview = $('#hero_image').siblings('.mt-2').find('img');
                        if (imgPreview.length) {
                            imgPreview.attr('src', response.image_url).css('display','').off('error').on('error', function(){ $(this).hide().next().removeClass('d-none').addClass('d-flex'); });
                        } else {
                            $('#hero_image').after(`<div class="mt-2 position-relative" style="display:inline-block;"><img src="${response.image_url}" alt="" class="img-thumbnail" style="max-height:100px;border-radius:10px;" onerror="this.style.display='none';this.nextElementSibling?.classList.remove('d-none');"><div class="d-none align-items-center justify-content-center border rounded bg-light" style="width:200px;height:100px;"><i class="fas fa-image text-muted"></i><small class="text-muted ms-2">No preview</small></div></div>`);
                        }
                    }
                },
                error: function(){ showAlert('danger','Error uploading image.'); }
            });
        }
    });

    $('#publishBtn').on('click', function() {
        $.ajax({
            url: '{{ route("doctor.landing-page.toggle-publish") }}', method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    isPublished = response.is_published;
                    location.reload();
                }
            },
            error: function(){ showAlert('danger','Error updating publish status.'); }
        });
    });
    $('#previewBtn').on('click', function(){ window.open('{{ route("doctor.landing-page.preview", $landingPage->username) }}','_blank'); });
    $('[data-preview-device]').on('click', function(){
        const device = $(this).data('preview-device');
        const $frame = $('#previewFrame');
        $('[data-preview-device]').removeClass('active'); $(this).addClass('active');
        $frame.removeClass('tablet mobile'); if(device!=='desktop') $frame.addClass(device);
    });
    $('input[type="color"]').on('change', function(){ setTimeout(refreshPreview,500); });
    $('input[name^="section_visibility"]').on('change', function(){ setTimeout(refreshPreview,500); });
    $('#template').on('change', function(){ setTimeout(refreshPreview,500); });
    $('#subdomain_enabled').on('change', function(){ $('#subdomainUrl').toggle(this.checked); });
    $('#resetColors').on('click', function(){
        const d={primary:'#3b82f6',secondary:'#64748b',accent:'#10b981',button:'#3b82f6',header_bg:'#ffffff',footer_bg:'#f8fafc'};
        Object.keys(d).forEach(k=>$(`#color_${k}`).val(d[k])); $('#landingPageForm').submit();
    });
    function refreshPreview(){
        const $loader=$('#previewLoader'); const $frame=$('#previewFrame');
        $loader.show(); $frame.on('load',function(){ $loader.hide(); }); $frame.attr('src',$frame.attr('src'));
    }
    function showAlert(type,message){
        const html=`<div class="alert alert-${type} alert-dismissible fade show" role="alert" style="border-radius:12px;">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        $('.dashboard-container').prepend(html); setTimeout(()=>$('.alert').fadeOut(),5000);
    }
    function loadAnalytics(){
        $.ajax({
            url:'{{ route("doctor.analytics.data") }}', method:'GET', data:{period:30},
            success:function(r){ if(r.success){ $('#totalVisits').text(r.stats.total_visits||0); $('#uniqueVisitors').text(r.stats.unique_visitors||0); let h=''; if(r.deviceStats&&r.deviceStats.length){ r.deviceStats.forEach(d=>h+=`<span class="badge me-1" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:99px;font-size:0.70rem;">${d.device_type}: ${d.visits}</span>`);} else h='No visits yet'; $('#deviceStats').html(h); }},
            error:function(){ $('#totalVisits').text('0'); $('#uniqueVisitors').text('0'); $('#deviceStats').text('No data available'); }
        });
    }
    $('#analytics-tab').on('click', loadAnalytics);
});
function copyToClipboard(text){ navigator.clipboard.writeText(text).then(()=>{ const h=`<div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px;">URL copied to clipboard!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`; $('.dashboard-container').prepend(h); setTimeout(()=>$('.alert').fadeOut(),3000); }); }
</script>
@endpush
@endsection
