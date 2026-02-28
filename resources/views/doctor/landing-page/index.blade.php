@extends('master')

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '🌐';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-header">
    <h2>Landing Page</h2>
    <p>Manage your landing page</p>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Landing Page Management</h1>
                <div class="btn-group">
                    <a href="{{ route('doctor.landing-page.page-builder') }}" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Page Builder
                    </a>
                    <button type="button" class="btn btn-outline-primary" id="previewBtn">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="button" class="btn {{ $landingPage->is_published ? 'btn-success' : 'btn-outline-success' }}" id="publishBtn">
                        <i class="fas {{ $landingPage->is_published ? 'fa-check-circle' : 'fa-circle' }}"></i>
                        {{ $landingPage->is_published ? 'Published' : 'Publish' }}
                    </button>
                </div>
            </div>

            @if($landingPage->is_published)
                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle"></i>
                    Your landing page is live!
                    <a href="{{ $landingPage->url }}" target="_blank" class="alert-link">View Public Page</a>
                </div>
            @endif

            <div class="row">
                <!-- Settings Panel -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                        Basic
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab">
                                        Design
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="sections-tab" data-bs-toggle="tab" data-bs-target="#sections" type="button" role="tab">
                                        Sections
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="domain-tab" data-bs-toggle="tab" data-bs-target="#domain" type="button" role="tab">
                                        Domain
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="language-tab" data-bs-toggle="tab" data-bs-target="#language" type="button" role="tab">
                                        Language
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab">
                                        Analytics
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
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
                                            <div class="form-text">Only letters, numbers, hyphens, and underscores allowed.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="template" class="form-label">Template</label>
                                            <select class="form-select" id="template" name="template">
                                                <option value="template1" {{ $landingPage->template === 'template1' ? 'selected' : '' }}>Modern Professional</option>
                                                <option value="template2" {{ $landingPage->template === 'template2' ? 'selected' : '' }}>Clean Minimal</option>
                                                <option value="template3" {{ $landingPage->template === 'template3' ? 'selected' : '' }}>Advanced Builder</option>
                                                <option value="template4" {{ $landingPage->template === 'template4' ? 'selected' : '' }}>Medical Focus</option>
                                            </select>
                                            <div class="form-text">
                                                <strong>Advanced Builder</strong> template supports the page builder with custom sections and animations.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="page_title" class="form-label">Page Title</label>
                                            <input type="text" class="form-control" id="page_title" name="page_title" value="{{ $landingPage->page_title }}" maxlength="255">
                                            <div class="form-text">Used for SEO and browser title.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="page_description" class="form-label">Page Description</label>
                                            <textarea class="form-control" id="page_description" name="page_description" rows="3" maxlength="500">{{ $landingPage->page_description }}</textarea>
                                            <div class="form-text">Used for SEO meta description.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="tagline" class="form-label">Tagline</label>
                                            <input type="text" class="form-control" id="tagline" name="tagline" value="{{ $landingPage->tagline }}" maxlength="255">
                                            <div class="form-text">Displayed in the hero section.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="about_text" class="form-label">About Text</label>
                                            <textarea class="form-control" id="about_text" name="about_text" rows="5" maxlength="2000">{{ $landingPage->about_text }}</textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="hero_image" class="form-label">Hero Image</label>
                                            <input type="file" class="form-control" id="hero_image" name="hero_image" accept="image/*">
                                            @if($landingPage->hero_image)
                                                <div class="mt-2">
                                                    <img src="{{ Storage::url($landingPage->hero_image) }}" alt="Current hero image" class="img-thumbnail" style="max-height: 100px;">
                                                </div>
                                            @endif
                                            <div class="form-text">Recommended size: 1200x600px. Max 2MB.</div>
                                        </div>
                                    </div>

                                    <!-- Design Settings -->
                                    <div class="tab-pane fade" id="design" role="tabpanel">
                                        <h6 class="mb-3">Color Scheme</h6>

                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label for="color_primary" class="form-label">Primary Color</label>
                                                <input type="color" class="form-control form-control-color" id="color_primary" name="colors[primary]" value="{{ $landingPage->colors['primary'] ?? '#3b82f6' }}">
                                            </div>
                                            <div class="col-6">
                                                <label for="color_secondary" class="form-label">Secondary Color</label>
                                                <input type="color" class="form-control form-control-color" id="color_secondary" name="colors[secondary]" value="{{ $landingPage->colors['secondary'] ?? '#64748b' }}">
                                            </div>
                                            <div class="col-6">
                                                <label for="color_accent" class="form-label">Accent Color</label>
                                                <input type="color" class="form-control form-control-color" id="color_accent" name="colors[accent]" value="{{ $landingPage->colors['accent'] ?? '#10b981' }}">
                                            </div>
                                            <div class="col-6">
                                                <label for="color_button" class="form-label">Button Color</label>
                                                <input type="color" class="form-control form-control-color" id="color_button" name="colors[button]" value="{{ $landingPage->colors['button'] ?? '#3b82f6' }}">
                                            </div>
                                            <div class="col-6">
                                                <label for="color_header_bg" class="form-label">Header Background</label>
                                                <input type="color" class="form-control form-control-color" id="color_header_bg" name="colors[header_bg]" value="{{ $landingPage->colors['header_bg'] ?? '#ffffff' }}">
                                            </div>
                                            <div class="col-6">
                                                <label for="color_footer_bg" class="form-label">Footer Background</label>
                                                <input type="color" class="form-control form-control-color" id="color_footer_bg" name="colors[footer_bg]" value="{{ $landingPage->colors['footer_bg'] ?? '#f8fafc' }}">
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetColors">
                                                <i class="fas fa-undo"></i> Reset to Default
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Section Visibility -->
                                    <div class="tab-pane fade" id="sections" role="tabpanel">
                                        <h6 class="mb-3">Section Visibility</h6>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="section_hero" name="section_visibility[hero]" {{ ($landingPage->section_visibility['hero'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="section_hero">
                                                <strong>Hero Section</strong>
                                                <div class="text-muted small">Main banner with your photo and tagline</div>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="section_about" name="section_visibility[about]" {{ ($landingPage->section_visibility['about'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="section_about">
                                                <strong>About Section</strong>
                                                <div class="text-muted small">Your bio and professional information</div>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="section_appointments" name="section_visibility[appointments]" {{ ($landingPage->section_visibility['appointments'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="section_appointments">
                                                <strong>Appointment Booking</strong>
                                                <div class="text-muted small">Allow patients to book appointments directly</div>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="section_reviews" name="section_visibility[reviews]" {{ ($landingPage->section_visibility['reviews'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="section_reviews">
                                                <strong>Reviews Section</strong>
                                                <div class="text-muted small">Display patient reviews and testimonials</div>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="section_contact" name="section_visibility[contact]" {{ ($landingPage->section_visibility['contact'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="section_contact">
                                                <strong>Contact Section</strong>
                                                <div class="text-muted small">Your contact information and location</div>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="section_chat_widget" name="section_visibility[chat_widget]" {{ ($landingPage->section_visibility['chat_widget'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="section_chat_widget">
                                                <strong>Live Chat Widget</strong>
                                                <div class="text-muted small">Allow visitors to chat with you directly</div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Domain Settings -->
                                    <div class="tab-pane fade" id="domain" role="tabpanel">
                                        <div class="mb-4">
                                            <h6>Default URL</h6>
                                            <div class="input-group">
                                                <span class="input-group-text">medcuraai.com/doctor/</span>
                                                <input type="text" class="form-control" value="{{ $landingPage->username }}" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ route('doctor.landing', $landingPage->username) }}')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="subdomain_enabled" name="subdomain_enabled" {{ $landingPage->subdomain_enabled ? 'checked' : '' }}>
                                                <label class="form-check-label" for="subdomain_enabled">
                                                    <strong>Enable Subdomain</strong>
                                                </label>
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
                                            <div class="form-text">
                                                To use a custom domain, add a CNAME record pointing to: <code>medcuraai.com</code>
                                            </div>
                                        </div>

                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Custom Domain Setup</h6>
                                            <ol class="mb-0">
                                                <li>Go to your domain registrar's DNS settings</li>
                                                <li>Add a CNAME record with your domain pointing to <code>medcuraai.com</code></li>
                                                <li>Enter your domain above and save</li>
                                                <li>It may take up to 24 hours for changes to take effect</li>
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
                                            <div class="form-text">This will be the default language for your landing page.</div>
                                        </div>

                                        <div class="mb-4">
                                            <h6>Arabic Translations</h6>
                                            <p class="text-muted small">Provide Arabic translations for your content. If left empty, the default English content will be used.</p>

                                            <div class="mb-3">
                                                <label for="ar_page_title" class="form-label">Page Title (Arabic)</label>
                                                <input type="text" class="form-control" id="ar_page_title" name="translations[ar][page_title]" value="{{ $landingPage->translations['ar']['page_title'] ?? '' }}" maxlength="255">
                                            </div>

                                            <div class="mb-3">
                                                <label for="ar_page_description" class="form-label">Page Description (Arabic)</label>
                                                <textarea class="form-control" id="ar_page_description" name="translations[ar][page_description]" rows="3" maxlength="500">{{ $landingPage->translations['ar']['page_description'] ?? '' }}</textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="ar_tagline" class="form-label">Tagline (Arabic)</label>
                                                <input type="text" class="form-control" id="ar_tagline" name="translations[ar][tagline]" value="{{ $landingPage->translations['ar']['tagline'] ?? '' }}" maxlength="255">
                                            </div>

                                            <div class="mb-3">
                                                <label for="ar_about_text" class="form-label">About Text (Arabic)</label>
                                                <textarea class="form-control" id="ar_about_text" name="translations[ar][about_text]" rows="5" maxlength="2000">{{ $landingPage->translations['ar']['about_text'] ?? '' }}</textarea>
                                            </div>

                                            <h6 class="mt-4 mb-3">Appointment Form Translations</h6>

                                            <div class="mb-3">
                                                <label for="ar_appointment_title" class="form-label">Appointment Section Title (Arabic)</label>
                                                <input type="text" class="form-control" id="ar_appointment_title" name="translations[ar][appointment_title]" value="{{ $landingPage->translations['ar']['appointment_title'] ?? '' }}" maxlength="255">
                                            </div>

                                            <div class="mb-3">
                                                <label for="ar_appointment_subtitle" class="form-label">Appointment Section Subtitle (Arabic)</label>
                                                <input type="text" class="form-control" id="ar_appointment_subtitle" name="translations[ar][appointment_subtitle]" value="{{ $landingPage->translations['ar']['appointment_subtitle'] ?? '' }}" maxlength="255">
                                            </div>

                                            <div class="mb-3">
                                                <label for="ar_form_labels" class="form-label">Form Labels (Arabic)</label>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_name_label]" placeholder="Name label" value="{{ $landingPage->translations['ar']['form_name_label'] ?? '' }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_email_label]" placeholder="Email label" value="{{ $landingPage->translations['ar']['form_email_label'] ?? '' }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_phone_label]" placeholder="Phone label" value="{{ $landingPage->translations['ar']['form_phone_label'] ?? '' }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_date_label]" placeholder="Date label" value="{{ $landingPage->translations['ar']['form_date_label'] ?? '' }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_time_label]" placeholder="Time label" value="{{ $landingPage->translations['ar']['form_time_label'] ?? '' }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_message_label]" placeholder="Message label" value="{{ $landingPage->translations['ar']['form_message_label'] ?? '' }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <input type="text" class="form-control form-control-sm" name="translations[ar][form_submit_button]" placeholder="Submit button text" value="{{ $landingPage->translations['ar']['form_submit_button'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="mt-4 mb-3">Navigation & Section Titles</h6>

                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <input type="text" class="form-control form-control-sm" name="translations[ar][nav_home]" placeholder="Home" value="{{ $landingPage->translations['ar']['nav_home'] ?? '' }}">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control form-control-sm" name="translations[ar][nav_about]" placeholder="About" value="{{ $landingPage->translations['ar']['nav_about'] ?? '' }}">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control form-control-sm" name="translations[ar][nav_appointments]" placeholder="Appointments" value="{{ $landingPage->translations['ar']['nav_appointments'] ?? '' }}">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control form-control-sm" name="translations[ar][nav_reviews]" placeholder="Reviews" value="{{ $landingPage->translations['ar']['nav_reviews'] ?? '' }}">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control form-control-sm" name="translations[ar][nav_contact]" placeholder="Contact" value="{{ $landingPage->translations['ar']['nav_contact'] ?? '' }}">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control form-control-sm" name="translations[ar][about_title]" placeholder="About section title" value="{{ $landingPage->translations['ar']['about_title'] ?? '' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Analytics Tab -->
                                    <div class="tab-pane fade" id="analytics" role="tabpanel">
                                        <div class="text-center mb-4">
                                            <h6>Landing Page Analytics</h6>
                                            <p class="text-muted">Track your landing page performance</p>
                                        </div>

                                        <div class="row g-3 mb-4">
                                            <div class="col-6">
                                                <div class="card text-center">
                                                    <div class="card-body">
                                                        <h5 class="card-title text-primary" id="totalVisits">-</h5>
                                                        <p class="card-text small">Total Visits</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="card text-center">
                                                    <div class="card-body">
                                                        <h5 class="card-title text-success" id="uniqueVisitors">-</h5>
                                                        <p class="card-text small">Unique Visitors</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Device Types</label>
                                            <div id="deviceStats" class="small text-muted">Loading...</div>
                                        </div>

                                        <div class="text-center">
                                            <a href="{{ route('doctor.analytics.index') }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-chart-bar"></i> View Full Analytics
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Live Preview</h5>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary active" data-preview-device="desktop">
                                    <i class="fas fa-desktop"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-preview-device="tablet">
                                    <i class="fas fa-tablet-alt"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-preview-device="mobile">
                                    <i class="fas fa-mobile-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="previewContainer" class="position-relative">
                                <iframe id="previewFrame" src="{{ route('doctor.landing-page.preview', $landingPage->username) }}"
                                        style="width: 100%; height: 600px; border: none; transition: all 0.3s ease;"></iframe>
                                <div id="previewLoader" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
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

@push('styles')
<style>
    .form-control-color {
        width: 100%;
        height: 38px;
    }

    #previewFrame.tablet {
        width: 768px;
        margin: 0 auto;
        display: block;
    }

    #previewFrame.mobile {
        width: 375px;
        margin: 0 auto;
        display: block;
    }

    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let isPublished = {{ $landingPage->is_published ? 'true' : 'false' }};

    // Form submission
    $('#landingPageForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Convert checkboxes to proper boolean values
        $('input[type="checkbox"]').each(function() {
            if (this.name.includes('section_visibility') || this.name === 'subdomain_enabled') {
                formData.set(this.name, this.checked ? '1' : '0');
            }
        });

        $.ajax({
            url: '{{ route("doctor.landing-page.update") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    refreshPreview();

                    // Update username in URL displays
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
                    let errorMessage = 'Please fix the following errors:\n';
                    Object.keys(errors).forEach(key => {
                        errorMessage += '- ' + errors[key][0] + '\n';
                    });
                    showAlert('danger', errorMessage);
                } else {
                    showAlert('danger', 'An error occurred while saving changes.');
                }
            }
        });
    });

    // Hero image upload
    $('#hero_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('hero_image', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: '{{ route("doctor.landing-page.upload-hero-image") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        refreshPreview();

                        // Update image preview
                        const imgPreview = $('#hero_image').siblings('.mt-2').find('img');
                        if (imgPreview.length) {
                            imgPreview.attr('src', response.image_url);
                        } else {
                            $('#hero_image').after(`
                                <div class="mt-2">
                                    <img src="${response.image_url}" alt="Current hero image" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            `);
                        }
                    }
                },
                error: function(xhr) {
                    showAlert('danger', 'Error uploading image. Please try again.');
                }
            });
        }
    });

    // Publish/Unpublish toggle
    $('#publishBtn').on('click', function() {
        $.ajax({
            url: '{{ route("doctor.landing-page.toggle-publish") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    isPublished = response.is_published;
                    updatePublishButton();
                    showAlert('success', response.message);

                    if (response.public_url) {
                        $('.alert-success').remove();
                        $('.container-fluid .row .col-12').eq(0).after(`
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle"></i>
                                Your landing page is live!
                                <a href="${response.public_url}" target="_blank" class="alert-link">View Public Page</a>
                            </div>
                        `);
                    } else {
                        $('.alert-success').remove();
                    }
                }
            },
            error: function() {
                showAlert('danger', 'Error updating publish status. Please try again.');
            }
        });
    });

    // Preview button
    $('#previewBtn').on('click', function() {
        const previewUrl = '{{ route("doctor.landing-page.preview", $landingPage->username) }}';
        window.open(previewUrl, '_blank');
    });

    // Device preview toggles
    $('[data-preview-device]').on('click', function() {
        const device = $(this).data('preview-device');
        const $frame = $('#previewFrame');

        $('[data-preview-device]').removeClass('active');
        $(this).addClass('active');

        $frame.removeClass('tablet mobile');
        if (device !== 'desktop') {
            $frame.addClass(device);
        }
    });

    // Color changes trigger preview refresh
    $('input[type="color"]').on('change', function() {
        setTimeout(refreshPreview, 500);
    });

    // Section visibility changes trigger preview refresh
    $('input[name^="section_visibility"]').on('change', function() {
        setTimeout(refreshPreview, 500);
    });

    // Template change triggers preview refresh
    $('#template').on('change', function() {
        setTimeout(refreshPreview, 500);
    });

    // Subdomain toggle
    $('#subdomain_enabled').on('change', function() {
        if (this.checked) {
            $('#subdomainUrl').show();
        } else {
            $('#subdomainUrl').hide();
        }
    });

    // Reset colors
    $('#resetColors').on('click', function() {
        const defaultColors = {
            primary: '#3b82f6',
            secondary: '#64748b',
            accent: '#10b981',
            button: '#3b82f6',
            header_bg: '#ffffff',
            footer_bg: '#f8fafc'
        };

        Object.keys(defaultColors).forEach(key => {
            $(`#color_${key}`).val(defaultColors[key]);
        });

        setTimeout(refreshPreview, 500);
    });

    function updatePublishButton() {
        const $btn = $('#publishBtn');
        if (isPublished) {
            $btn.removeClass('btn-outline-success').addClass('btn-success');
            $btn.find('i').removeClass('fa-circle').addClass('fa-check-circle');
            $btn.find('span, text').last().replaceWith('Published');
        } else {
            $btn.removeClass('btn-success').addClass('btn-outline-success');
            $btn.find('i').removeClass('fa-check-circle').addClass('fa-circle');
            $btn.find('span, text').last().replaceWith('Publish');
        }
    }

    function refreshPreview() {
        const $loader = $('#previewLoader');
        const $frame = $('#previewFrame');

        $loader.show();
        $frame.on('load', function() {
            $loader.hide();
        });

        // Reload iframe
        $frame.attr('src', $frame.attr('src'));
    }

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('.container-fluid').prepend(alertHtml);

        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Load analytics data
    function loadAnalytics() {
        $.ajax({
            url: '{{ route("doctor.analytics.data") }}',
            method: 'GET',
            data: { period: 30 },
            success: function(response) {
                if (response.success) {
                    $('#totalVisits').text(response.stats.total_visits || 0);
                    $('#uniqueVisitors').text(response.stats.unique_visitors || 0);

                    let deviceHtml = '';
                    if (response.deviceStats && response.deviceStats.length > 0) {
                        response.deviceStats.forEach(function(device) {
                            deviceHtml += `<span class="badge bg-secondary me-1">${device.device_type}: ${device.visits}</span>`;
                        });
                    } else {
                        deviceHtml = 'No visits yet';
                    }
                    $('#deviceStats').html(deviceHtml);
                }
            },
            error: function() {
                $('#totalVisits').text('0');
                $('#uniqueVisitors').text('0');
                $('#deviceStats').text('No data available');
            }
        });
    }

    // Load analytics when tab is clicked
    $('#analytics-tab').on('click', function() {
        loadAnalytics();
    });
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('success', 'URL copied to clipboard!');
    });
}
</script>
@endpush
@endsection
