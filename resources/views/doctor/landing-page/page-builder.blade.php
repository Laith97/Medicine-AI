@extends('master')

@section('title', 'Page Builder - Landing Page')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid" style="max-width:100%; padding-left:1.5rem; padding-right:1.5rem;">
        <!-- Header — IDENTICAL to system headers (landing-page/index, hep/show, analytics) -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-magic me-2"></i>Page Builder</h2>
                    <p>Design your perfect landing page — drag, edit, preview</p>
                </div>
                <div class="header-actions-wrap">
                    <span class="status-badge {{ $landingPage->is_published ? 'published' : 'draft' }}" style="background:#fff !important; color:#1e293b !important; border:1px solid #e2e8f0 !important; box-shadow:0 1px 3px rgba(0,0,0,0.08) !important; border-radius:99px !important; padding:0.38rem 0.85rem !important; font-size:0.73rem !important; font-weight:700 !important;">
                        <span class="live-dot me-1" style="width:8px;height:8px;border-radius:50%;background:{{ $landingPage->is_published ? '#10b981' : '#f59e0b' }};display:inline-block;"></span>
                        {{ $landingPage->is_published ? 'Published' : 'Draft' }}
                    </span>
                    <a href="{{ route('doctor.landing-page.index') }}" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <button type="button" class="btn action-btn" style="background:#fff !important; color:#1e293b !important; border:1px solid #fff !important; border-radius:10px !important; padding:0.48rem 0.85rem !important; font-size:0.80rem !important; font-weight:700 !important;" id="previewBtn">
                        <i class="fas fa-eye me-1"></i> Preview
                    </button>
                    <button type="button" class="btn action-btn" style="background:#fff !important; color:#1e293b !important; border:1px solid #fff !important; border-radius:10px !important; padding:0.48rem 0.85rem !important; font-size:0.80rem !important; font-weight:700 !important;" id="responsiveToggle">
                        <i class="fas fa-mobile-alt me-1"></i> Mobile View
                    </button>
                    <button type="button" class="btn action-btn" style="background:#fff !important; color:#1e293b !important; border:1px solid #fff !important; border-radius:10px !important; padding:0.48rem 0.85rem !important; font-size:0.80rem !important; font-weight:700 !important;" id="saveBtn">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn action-btn" style="{{ $landingPage->is_published ? 'background:#fff !important; color:#1e293b !important; border:1px solid #fff !important;' : 'background:#10b981 !important; color:#fff !important; border:1px solid #10b981 !important;' }} border-radius:10px !important; padding:0.48rem 0.85rem !important; font-size:0.80rem !important; font-weight:700 !important;" id="publishBtn">
                        <i class="fas {{ $landingPage->is_published ? 'fa-eye-slash' : 'fa-rocket' }}"></i>
                        {{ $landingPage->is_published ? 'Unpublish' : 'Publish' }}
                    </button>
                </div>
            </div>
        </div>
        <div class="page-builder-container" style="margin:0; border-radius:12px; overflow:hidden; border:1px solid #eef2f7; box-shadow:0 6px 20px rgba(44,62,80,.05);">

    <!-- Main Content -->
    <div class="page-builder-content">
        <!-- Editor toolbar — Undo/Redo moved out of header to avoid empty buttons & crowding -->
        <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#ffffff; border-bottom:1px solid #eef2f7;">
            <span class="small text-muted fw-semibold" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;"><i class="fas fa-history me-1"></i>History</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-weight:600;font-size:0.78rem;" id="undoBtn" disabled>
                    <i class="fas fa-undo me-1"></i> Undo
                </button>
                <button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-weight:600;font-size:0.78rem;" id="redoBtn" disabled>
                    <i class="fas fa-redo me-1"></i> Redo
                </button>
            </div>
            <span class="small text-muted ms-2" style="font-size:0.76rem;">Changes autosaved on Save</span>
        </div>
        <div class="row g-0">
            <!-- Sidebar - Section Library -->
            <div class="col-md-3 page-builder-sidebar">
                <div class="sidebar-content">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs nav-fill" id="sidebarTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sections-tab" data-bs-toggle="tab" data-bs-target="#sections-panel" type="button" role="tab">
                                <i class="fas fa-th-large"></i>
                                <span class="d-none d-lg-inline">Sections</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design-panel" type="button" role="tab">
                                <i class="fas fa-palette"></i>
                                <span class="d-none d-lg-inline">Design</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-panel" type="button" role="tab">
                                <i class="fas fa-cog"></i>
                                <span class="d-none d-lg-inline">Settings</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="sidebarTabContent">
                        <!-- Sections Panel -->
                        <div class="tab-pane fade show active" id="sections-panel" role="tabpanel">
                            <div class="section-categories">
                                <div class="category-filter mb-3">
                                    <select class="form-select form-select-sm" id="categoryFilter">
                                        <option value="">All Categories</option>
                                        <option value="header">Header</option>
                                        <option value="content">Content</option>
                                        <option value="media">Media</option>
                                        <option value="social-proof">Social Proof</option>
                                        <option value="conversion">Conversion</option>
                                        <option value="footer">Footer</option>
                                    </select>
                                </div>

                                <div class="section-templates">
                                    @foreach($sectionTemplates as $key => $template)
                                    <div class="section-template-card" data-category="{{ $template['category'] }}" data-type="{{ $template['type'] }}">
                                        <div class="template-preview">
                                            <img src="{{ $template['preview_image'] }}" alt="{{ $template['name'] }}" class="img-fluid">
                                            <div class="template-overlay">
                                                <button class="btn btn-primary btn-sm add-section-btn" data-type="{{ $template['type'] }}">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                        <div class="template-info">
                                            <h6 class="template-name">{{ $template['name'] }}</h6>
                                            <p class="template-description">{{ $template['description'] }}</p>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Design Panel -->
                        <div class="tab-pane fade" id="design-panel" role="tabpanel">
                            <div class="design-controls">
                                <!-- Global Colors -->
                                <div class="control-group">
                                    <h6>Global Colors</h6>
                                    <div class="color-palette">
                                        <div class="color-input-group">
                                            <label>Primary</label>
                                            <input type="color" class="form-control form-control-color" id="globalPrimary" value="{{ $landingPage->colors['primary'] ?? '#3b82f6' }}">
                                        </div>
                                        <div class="color-input-group">
                                            <label>Secondary</label>
                                            <input type="color" class="form-control form-control-color" id="globalSecondary" value="{{ $landingPage->colors['secondary'] ?? '#64748b' }}">
                                        </div>
                                        <div class="color-input-group">
                                            <label>Accent</label>
                                            <input type="color" class="form-control form-control-color" id="globalAccent" value="{{ $landingPage->colors['accent'] ?? '#10b981' }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Typography -->
                                <div class="control-group">
                                    <h6>Typography</h6>
                                    <div class="typography-controls">
                                        <div class="mb-3">
                                            <label>Primary Font</label>
                                            <select class="form-select" id="primaryFont">
                                                <option value="Inter">Inter</option>
                                                <option value="Roboto">Roboto</option>
                                                <option value="Open Sans">Open Sans</option>
                                                <option value="Lato">Lato</option>
                                                <option value="Montserrat">Montserrat</option>
                                                <option value="Poppins">Poppins</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Heading Font</label>
                                            <select class="form-select" id="headingFont">
                                                <option value="Inter">Inter</option>
                                                <option value="Roboto">Roboto</option>
                                                <option value="Playfair Display">Playfair Display</option>
                                                <option value="Merriweather">Merriweather</option>
                                                <option value="Montserrat">Montserrat</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Animations -->
                                <div class="control-group">
                                    <h6>Animations</h6>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="enableAnimations" {{ $landingPage->enable_animations ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enableAnimations">
                                            Enable Animations
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label>Animation Speed</label>
                                        <select class="form-select" id="animationSpeed">
                                            <option value="slow">Slow (2s)</option>
                                            <option value="normal" selected>Normal (1s)</option>
                                            <option value="fast">Fast (0.5s)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Panel -->
                        <div class="tab-pane fade" id="settings-panel" role="tabpanel">
                            <div class="settings-controls">
                                <!-- Page Layout -->
                                <div class="control-group">
                                    <h6>Page Layout</h6>
                                    <div class="layout-options">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pageLayout" id="layoutDefault" value="default" {{ ($landingPage->page_layout ?? 'default') === 'default' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="layoutDefault">
                                                Default
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pageLayout" id="layoutFullwidth" value="fullwidth" {{ $landingPage->page_layout === 'fullwidth' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="layoutFullwidth">
                                                Full Width
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pageLayout" id="layoutBoxed" value="boxed" {{ $landingPage->page_layout === 'boxed' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="layoutBoxed">
                                                Boxed
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Navbar Settings -->
                                <div class="control-group">
                                    <h6>Navigation Bar</h6>
                                    <div class="navbar-settings">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="stickyNavbar" checked>
                                            <label class="form-check-label" for="stickyNavbar">
                                                Sticky Navigation
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label>Navigation Style</label>
                                            <select class="form-select" id="navbarStyle">
                                                <option value="transparent">Transparent</option>
                                                <option value="solid" selected>Solid</option>
                                                <option value="gradient">Gradient</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Custom Links</label>
                                            <div id="customNavLinks">
                                                <!-- Dynamic nav links will be added here -->
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="addNavLink">
                                                <i class="fas fa-plus"></i> Add Link
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Canvas -->
            <div class="col-md-9 page-builder-canvas">
                <div class="canvas-container">
                    <!-- Device Frame -->
                    <div class="device-frame desktop-frame" id="deviceFrame">
                        <div class="device-screen">
                            <!-- Canvas Content -->
                            <div class="canvas-content" id="canvasContent">
                                <!-- Sections will be rendered here -->
                                <div class="sections-container" id="sectionsContainer">
                                    @if($landingPage->page_sections)
                                        @foreach($landingPage->page_sections as $section)
                                            @include('doctor.landing-page.sections.' . $section['type'], ['section' => $section, 'isBuilder' => true])
                                        @endforeach
                                    @else
                                        <div class="empty-canvas">
                                            <div class="empty-canvas-content">
                                                <i class="fas fa-magic fa-3x text-muted mb-3"></i>
                                                <h4 class="text-muted">Start Building Your Page</h4>
                                                <p class="text-muted">Add sections from the sidebar to create your perfect landing page</p>
                                            </div>
                                        </div>
                                    @endif
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

<!-- Section Editor Modal -->
<div class="modal fade" id="sectionEditorModal" tabindex="-1" aria-labelledby="sectionEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionEditorModalLabel">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="section-editor-sidebar">
                            <!-- Section editor controls will be loaded here -->
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="section-preview">
                            <!-- Section preview will be shown here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSectionBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.css">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
/* System header — identical to hep/show & landing-page/index */
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
.dashboard-header h2{ color:#fff !important; font-weight:600 !important; font-size:2.2rem !important; margin-bottom:0.5rem !important; }
.dashboard-header p{ color:rgba(255,255,255,0.9) !important; font-size:1rem !important; margin-bottom:0 !important; }
.header-actions-wrap{ display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap; justify-content:flex-end; }
.btn-back{ background:rgba(255,255,255,0.15) !important; border:1px solid rgba(255,255,255,0.32) !important; color:#fff !important; border-radius:10px !important; padding:0.5rem 1rem !important; font-weight:600 !important; font-size:0.83rem !important; }
.btn-back:hover{ background:#fff !important; color:#1e3a8a !important; border-color:#fff !important; }
.action-btn{ border-radius:10px !important; padding:0.48rem 0.85rem !important; font-size:0.80rem !important; font-weight:700 !important; display:inline-flex !important; align-items:center !important; gap:0.35rem !important; border:1px solid transparent !important; box-shadow:0 1px 3px rgba(0,0,0,0.10) !important; transition:all .18s ease !important; }
.action-btn:hover{ transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,0.14) !important; }
.dashboard-container{ background: var(--bg-secondary, #f8f9fa); min-height: calc(100vh - 52px); padding-bottom:2rem; }
.page-builder-container {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #eef2f7;
    box-shadow: 0 6px 20px rgba(44,62,80,.05), 0 1px 6px rgba(44,62,80,.04);
    min-height: 560px;
}
.btn-back {
    background: rgba(255,255,255,0.15) !important; border:1px solid rgba(255,255,255,0.32) !important;
    color:#fff !important; border-radius:10px !important; padding:0.45rem 0.85rem !important;
    font-weight:600 !important; font-size:0.80rem !important; transition:all .18s ease !important;
}
.btn-back:hover { background:#fff !important; color:#1e3a8a !important; border-color:#fff !important; }
.action-btn {
    border-radius:10px !important; padding:0.48rem 0.85rem !important; font-size:0.80rem !important;
    font-weight:700 !important; display:inline-flex !important; align-items:center !important; gap:0.35rem !important;
    border:1px solid transparent !important; box-shadow:0 1px 3px rgba(0,0,0,0.10) !important; transition:all .18s ease !important;
}
.action-btn:hover{ transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,0.14) !important; }
.action-btn-soft { background:rgba(255,255,255,0.15) !important; color:#fff !important; border-color:rgba(255,255,255,0.32) !important; }
.action-btn-soft:hover{ background:#fff !important; color:#1e3a8a !important; }
.action-btn-white{ background:#fff !important; color:#1e293b !important; border-color:#fff !important; }
.action-btn-white:hover{ background:#f1f5f9 !important; }
.action-btn-success{ background:#10b981 !important; color:#fff !important; border-color:#10b981 !important; }
.action-btn-success:hover{ background:#059669 !important; }

.page-builder-content {
    flex: 1;
    overflow: hidden;
}

.page-builder-sidebar {
    background: #ffffff;
    border-right: 1px solid #eef2f7;
    height: calc(100vh - 52px - 68px);
    min-height: 560px;
    overflow-y: auto;
    border-radius: 12px 0 0 12px;
}

.sidebar-content {
    height: 100%;
}

.page-builder-sidebar .nav-tabs {
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.5rem;
    gap: 0.25rem;
}
.page-builder-sidebar .nav-tabs .nav-link {
    border: 1px solid transparent !important;
    color: #64748b !important;
    background: transparent !important;
    padding: 0.55rem 0.75rem;
    font-weight: 700;
    font-size: 0.78rem;
    border-radius: 8px !important;
    display: flex; align-items: center; gap: 0.35rem; justify-content: center;
}
.page-builder-sidebar .nav-tabs .nav-link:hover {
    color: #1e293b !important;
    background: #f8fafc !important;
    border-color:#e2e8f0 !important;
}
.page-builder-sidebar .nav-tabs .nav-link.active {
    background: #1e293b !important;
    color: #fff !important;
    border-color:#1e293b !important;
    box-shadow: 0 2px 8px rgba(15,23,42,0.12);
}

.section-template-card {
    margin-bottom: 0.85rem;
    border: 1px solid #eef2f7;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.2s;
    cursor: pointer;
    background: #fff;
}

.section-template-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    transform: translateY(-1px);
}

.template-preview {
    position: relative;
    height: 120px;
    background: #f8fafc;
    overflow: hidden;
}

.template-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.template-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.section-template-card:hover .template-overlay {
    opacity: 1;
}

.template-info {
    padding: 0.75rem;
}

.template-name {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.template-description {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}

.page-builder-canvas {
    background: #f8fafc;
    height: calc(100vh - 52px - 68px);
    min-height: 560px;
    overflow: auto;
    padding: 1.5rem;
    border-left: 1px solid #eef2f7;
}

.canvas-container {
    display: flex;
    justify-content: center;
    min-height: 100%;
}

.device-frame {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #eef2f7;
    box-shadow: 0 6px 20px rgba(44,62,80,.05), 0 1px 6px rgba(44,62,80,.04);
    overflow: hidden;
    transition: all 0.3s;
}

.desktop-frame {
    width: 100%;
    max-width: 1200px;
}

.mobile-frame {
    width: 375px;
    max-width: 375px;
}

.device-screen {
    width: 100%;
    height: 100%;
    overflow-y: auto;
}

.canvas-content {
    min-height: 600px;
}

.sections-container {
    position: relative;
}

.empty-canvas {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 600px;
    text-align: center;
}

.section-item {
    position: relative;
    margin-bottom: 0;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.section-item:hover {
    border-color: #3b82f6;
}

.section-item.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.section-controls {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
    opacity: 0;
    transition: opacity 0.2s;
}

.section-item:hover .section-controls {
    opacity: 1;
}

.section-controls .btn {
    margin-left: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.control-group {
    margin-bottom: 1.25rem;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #eef2f7;
    margin: 0 0.75rem 0.75rem 0.75rem;
}
.control-group:last-child { border-bottom: 1px solid #eef2f7; }
.control-group h6 {
    font-weight: 800;
    margin-bottom: 0.75rem;
    color: #0f172a;
    font-size: 0.84rem;
    letter-spacing: -0.01em;
    display:flex; align-items:center; gap:0.4rem;
}

.color-palette {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 0.75rem;
}

.color-input-group {
    text-align: center;
}

.color-input-group label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.form-control-color {
    width: 100%;
    height: 40px;
    border-radius: 6px;
}

.sortable-ghost {
    opacity: 0.5;
}

.sortable-chosen {
    transform: scale(1.02);
}

.drag-handle {
    cursor: move;
    color: #64748b;
    padding: 0.5rem;
}

.drag-handle:hover {
    color: #3b82f6;
}

@media (max-width: 768px) {
    .page-builder-sidebar {
        position: fixed;
        left: -100%;
        top: 80px;
        width: 300px;
        z-index: 1000;
        transition: left 0.3s;
    }

    .page-builder-sidebar.show {
        left: 0;
    }

    .page-builder-canvas {
        padding: 1rem;
    }

    .device-frame {
        width: 100%;
    }
}

/* Fix modal below navbar - master header has z-index 9999999 */
#sectionEditorModal {
    z-index: 10000010 !important;
}
.modal-backdrop {
    z-index: 10000000 !important;
}
.page-builder-header {
    z-index: 900 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
class PageBuilder {
    constructor() {
        this.sections = @json($landingPage->page_sections ?? []);
        this.sectionTemplates = @json($sectionTemplates);
        this.currentSection = null;
        this.history = [];
        this.historyIndex = -1;
        this.isResponsive = false;

        this.init();
    }

    init() {
        this.initSortable();
        this.bindEvents();
        this.loadSections();
        this.updateHistory();
    }

    initSortable() {
        const sectionsContainer = document.getElementById('sectionsContainer');
        if (sectionsContainer) {
            new Sortable(sectionsContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                handle: '.drag-handle',
                onEnd: (evt) => {
                    this.reorderSections(evt.oldIndex, evt.newIndex);
                }
            });
        }
    }

    bindEvents() {
        // Add section buttons
        document.querySelectorAll('.add-section-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const type = e.target.closest('.add-section-btn').dataset.type;
                this.addSection(type);
            });
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', (e) => {
            this.filterSections(e.target.value);
        });

        // Responsive toggle
        document.getElementById('responsiveToggle').addEventListener('click', () => {
            this.toggleResponsive();
        });

        // Save button
        document.getElementById('saveBtn').addEventListener('click', () => {
            this.savePage();
        });

        // Preview button
        document.getElementById('previewBtn').addEventListener('click', () => {
            this.previewPage();
        });

        // Publish button
        document.getElementById('publishBtn').addEventListener('click', () => {
            this.togglePublish();
        });

        // Undo/Redo
        document.getElementById('undoBtn').addEventListener('click', () => {
            this.undo();
        });

        document.getElementById('redoBtn').addEventListener('click', () => {
            this.redo();
        });

        // Global design controls
        this.bindDesignControls();

        // Section editor save button
        document.getElementById('saveSectionBtn').addEventListener('click', () => {
            this.saveSectionChanges();
        });
    }

    bindDesignControls() {
        // Color controls
        ['globalPrimary', 'globalSecondary', 'globalAccent'].forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', () => {
                    this.updateGlobalColors();
                });
            }
        });

        // Font controls
        ['primaryFont', 'headingFont'].forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.addEventListener('change', () => {
                    this.updateGlobalFonts();
                });
            }
        });

        // Animation controls
        document.getElementById('enableAnimations').addEventListener('change', () => {
            this.toggleAnimations();
        });

        // Layout controls
        document.querySelectorAll('input[name="pageLayout"]').forEach(radio => {
            radio.addEventListener('change', () => {
                this.updatePageLayout();
            });
        });
    }

    addSection(type) {
        const template = this.sectionTemplates[type];
        if (!template) return;

        const sectionId = 'section_' + Date.now();
        const newSection = {
            id: sectionId,
            type: type,
            config: { ...template.default_config },
            order: this.sections.length
        };

        this.sections.push(newSection);
        this.renderSection(newSection);
        this.updateHistory();

        // Auto-open editor for new section
        setTimeout(() => {
            this.editSection(sectionId);
        }, 100);
    }

    renderSection(section) {
        const sectionsContainer = document.getElementById('sectionsContainer');
        const emptyCanvas = sectionsContainer.querySelector('.empty-canvas');

        if (emptyCanvas) {
            emptyCanvas.remove();
        }

        const sectionElement = this.createSectionElement(section);
        sectionsContainer.appendChild(sectionElement);
    }

    createSectionElement(section) {
        const div = document.createElement('div');
        div.className = 'section-item';
        div.dataset.sectionId = section.id;
        div.dataset.sectionType = section.type;

        div.innerHTML = `
            <div class="section-controls">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary drag-handle" title="Drag to reorder">
                        <i class="fas fa-grip-vertical"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary edit-section-btn" title="Edit section">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary duplicate-section-btn" title="Duplicate section">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-section-btn" title="Delete section">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-content">
                ${this.renderSectionContent(section)}
            </div>
        `;

        // Bind section controls
        div.querySelector('.edit-section-btn').addEventListener('click', () => {
            this.editSection(section.id);
        });

        div.querySelector('.duplicate-section-btn').addEventListener('click', () => {
            this.duplicateSection(section.id);
        });

        div.querySelector('.delete-section-btn').addEventListener('click', () => {
            this.deleteSection(section.id);
        });

        return div;
    }

    renderSectionContent(section) {
        // This would render the actual section content based on type
        // For now, return a placeholder
        return `
            <div class="section-placeholder" style="padding: 2rem; background: #f8fafc; border: 2px dashed #cbd5e1; text-align: center;">
                <h5>${this.sectionTemplates[section.type]?.name || section.type}</h5>
                <p class="text-muted">Click edit to customize this section</p>
            </div>
        `;
    }

    editSection(sectionId) {
        const section = this.sections.find(s => s.id === sectionId);
        if (!section) return;

        this.currentSection = section;
        this.openSectionEditor(section);
    }

    openSectionEditor(section) {
        const modal = new bootstrap.Modal(document.getElementById('sectionEditorModal'));
        const modalTitle = document.getElementById('sectionEditorModalLabel');
        const editorSidebar = document.querySelector('.section-editor-sidebar');
        const sectionPreview = document.querySelector('.section-preview');

        modalTitle.textContent = `Edit ${this.sectionTemplates[section.type]?.name || section.type}`;

        // Load section editor form
        editorSidebar.innerHTML = this.generateSectionEditor(section);
        // init background type visibility
        const bgTypeSelect = editorSidebar.querySelector('select[name="background_type"]');
        if (bgTypeSelect) this.toggleBackgroundFields(bgTypeSelect.value);

        // Load section preview
        sectionPreview.innerHTML = this.renderSectionContent(section);

        modal.show();
    }

    generateSectionEditor(section) {
        const template = this.sectionTemplates[section.type];
        if (!template) return '';

        let editorHTML = '<form id="sectionEditorForm">';

        // Generate form fields based on section type and config
        Object.keys(template.default_config).forEach(key => {
            const value = section.config[key] || template.default_config[key];
            editorHTML += this.generateFormField(key, value, section.type);
        });

        editorHTML += '</form>';
        return editorHTML;
    }

    generateFormField(key, value, sectionType) {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        switch (key) {
            case 'background_type':
                return `
                    <div class="mb-3">
                        <label class="form-label">${label}</label>
                        <select class="form-select" name="${key}" data-field="background_type" onchange="window.pageBuilder && window.pageBuilder.toggleBackgroundFields(this.value)">
                            <option value="color" ${value === 'color' ? 'selected' : ''}>Color</option>
                            <option value="image" ${value === 'image' ? 'selected' : ''}>Image</option>
                            <option value="gradient" ${value === 'gradient' ? 'selected' : ''}>Gradient</option>
                        </select>
                    </div>
                `;

            case 'background_image':
            case 'image':
                const hasImage = value && value.length > 0;
                const previewUrl = hasImage ? (value.startsWith('http') || value.startsWith('/storage') ? value : '/storage/' + value) : '';
                return `
                    <div class="mb-3 background-image-group">
                        <label class="form-label">${label}</label>
                        <input type="file" class="form-control" accept="image/*" onchange="window.pageBuilder && window.pageBuilder.uploadBackgroundImage(this, '${key}')">
                        <input type="hidden" name="${key}" value="${value || ''}">
                        ${hasImage ? `<div class="mt-2"><img src="${previewUrl}" alt="Preview" class="img-thumbnail" style="max-height:100px;" onerror="this.style.display='none'"><small class="text-muted d-block mt-1">Current: ${value}</small></div>` : '<small class="text-muted d-block mt-1">No image selected. Choose file to upload (max 2MB).</small>'}
                    </div>
                `;

            case 'background_color':
            case 'text_color':
                return `
                    <div class="mb-3 background-color-group">
                        <label class="form-label">${label}</label>
                        <input type="color" class="form-control form-control-color" name="${key}" value="${value}">
                    </div>
                `;

            case 'animation':
                return `
                    <div class="mb-3">
                        <label class="form-label">${label}</label>
                        <select class="form-select" name="${key}">
                            <option value="">No Animation</option>
                            <option value="fadeIn" ${value === 'fadeIn' ? 'selected' : ''}>Fade In</option>
                            <option value="fadeInUp" ${value === 'fadeInUp' ? 'selected' : ''}>Fade In Up</option>
                            <option value="slideInLeft" ${value === 'slideInLeft' ? 'selected' : ''}>Slide In Left</option>
                            <option value="zoomIn" ${value === 'zoomIn' ? 'selected' : ''}>Zoom In</option>
                        </select>
                    </div>
                `;

            case 'content':
            case 'about_text':
                return `
                    <div class="mb-3">
                        <label class="form-label">${label}</label>
                        <textarea class="form-control" name="${key}" rows="4">${value}</textarea>
                    </div>
                `;

            default:
                if (typeof value === 'boolean') {
                    return `
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="${key}" ${value ? 'checked' : ''}>
                                <label class="form-check-label">${label}</label>
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="mb-3">
                            <label class="form-label">${label}</label>
                            <input type="text" class="form-control" name="${key}" value="${value}">
                        </div>
                    `;
                }
        }
    }

    toggleBackgroundFields(type) {
        const form = document.getElementById('sectionEditorForm');
        if (!form) return;
        const colorGroups = form.querySelectorAll('.background-color-group');
        const imageGroups = form.querySelectorAll('.background-image-group');
        if (type === 'image') {
            colorGroups.forEach(el => el.style.display = 'none');
            imageGroups.forEach(el => el.style.display = 'block');
        } else {
            colorGroups.forEach(el => el.style.display = 'block');
            imageGroups.forEach(el => el.style.display = 'none');
        }
    }

    uploadBackgroundImage(input, fieldName) {
        const file = input.files && input.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            this.showNotification('Please select a valid image (JPG, PNG, GIF).', 'error');
            input.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            this.showNotification('Image must be under 2MB.', 'error');
            input.value = '';
            return;
        }
        const formData = new FormData();
        formData.append('image', file);
        formData.append('section_id', this.currentSection ? this.currentSection.id : 'temp');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

        const hiddenInput = input.parentElement.querySelector(`input[type="hidden"][name="${fieldName}"]`);
        const originalLabel = input.nextElementSibling?.textContent;

        fetch('{{ route("doctor.landing-page.upload-section-image") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
            body: formData
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                if (hiddenInput) hiddenInput.value = data.path;
                // show preview
                let preview = input.parentElement.querySelector('img');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'img-thumbnail mt-2';
                    preview.style.maxHeight = '100px';
                    input.parentElement.appendChild(preview);
                }
                preview.src = data.image_url;
                preview.style.display = 'block';
                this.showNotification(data.message || 'Image uploaded!', 'success');
            } else {
                throw new Error(data.error || data.message || 'Upload failed');
            }
        })
        .catch(err => {
            this.showNotification('Error uploading image: ' + err.message, 'error');
            console.error(err);
        });
    }

    saveSectionChanges() {
        if (!this.currentSection) return;

        const form = document.getElementById('sectionEditorForm');
        if (!form) return;

        const formData = new FormData(form);
        const config = {};

        // Collect form data
        for (let [key, value] of formData.entries()) {
            // Handle checkboxes
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type === 'checkbox') {
                config[key] = input.checked;
            } else {
                config[key] = value;
            }
        }

        // Update section config
        this.currentSection.config = { ...this.currentSection.config, ...config };

        // Re-render the section in the canvas
        const sectionElement = document.querySelector(`[data-section-id="${this.currentSection.id}"]`);
        if (sectionElement) {
            sectionElement.querySelector('.section-content').innerHTML = this.renderSectionContent(this.currentSection);
        }

        // Update history
        this.updateHistory();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('sectionEditorModal'));
        if (modal) {
            modal.hide();
        }

        // Show success notification
        this.showNotification('Section updated successfully!', 'success');
    }

    duplicateSection(sectionId) {
        const section = this.sections.find(s => s.id === sectionId);
        if (!section) return;

        const newSection = {
            ...section,
            id: 'section_' + Date.now(),
            order: section.order + 1
        };

        this.sections.splice(section.order + 1, 0, newSection);
        this.reorderSections();
        this.renderSection(newSection);
        this.updateHistory();
    }

    deleteSection(sectionId) {
        if (confirm('Are you sure you want to delete this section?')) {
            this.sections = this.sections.filter(s => s.id !== sectionId);
            document.querySelector(`[data-section-id="${sectionId}"]`).remove();
            this.updateHistory();

            // Show empty canvas if no sections
            if (this.sections.length === 0) {
                this.showEmptyCanvas();
            }
        }
    }

    reorderSections(oldIndex, newIndex) {
        if (oldIndex !== undefined && newIndex !== undefined) {
            const section = this.sections.splice(oldIndex, 1)[0];
            this.sections.splice(newIndex, 0, section);
        }

        // Update order property
        this.sections.forEach((section, index) => {
            section.order = index;
        });

        this.updateHistory();
    }

    showEmptyCanvas() {
        const sectionsContainer = document.getElementById('sectionsContainer');
        sectionsContainer.innerHTML = `
            <div class="empty-canvas">
                <div class="empty-canvas-content">
                    <i class="fas fa-magic fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Start Building Your Page</h4>
                    <p class="text-muted">Add sections from the sidebar to create your perfect landing page</p>
                </div>
            </div>
        `;
    }

    loadSections() {
        const sectionsContainer = document.getElementById('sectionsContainer');
        sectionsContainer.innerHTML = '';

        if (this.sections.length === 0) {
            this.showEmptyCanvas();
        } else {
            this.sections.forEach(section => {
                this.renderSection(section);
            });
        }
    }

    filterSections(category) {
        const cards = document.querySelectorAll('.section-template-card');
        cards.forEach(card => {
            const cardCategory = card.dataset.category;
            if (!category || cardCategory === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    toggleResponsive() {
        const deviceFrame = document.getElementById('deviceFrame');
        const toggleBtn = document.getElementById('responsiveToggle');

        this.isResponsive = !this.isResponsive;

        if (this.isResponsive) {
            deviceFrame.className = 'device-frame mobile-frame';
            toggleBtn.innerHTML = '<i class="fas fa-desktop"></i> Desktop View';
        } else {
            deviceFrame.className = 'device-frame desktop-frame';
            toggleBtn.innerHTML = '<i class="fas fa-mobile-alt"></i> Mobile View';
        }
    }

    updateGlobalColors() {
        const primary = document.getElementById('globalPrimary').value;
        const secondary = document.getElementById('globalSecondary').value;
        const accent = document.getElementById('globalAccent').value;

        // Apply colors to CSS custom properties
        document.documentElement.style.setProperty('--primary-color', primary);
        document.documentElement.style.setProperty('--secondary-color', secondary);
        document.documentElement.style.setProperty('--accent-color', accent);
    }

    updateGlobalFonts() {
        const primaryFont = document.getElementById('primaryFont').value;
        const headingFont = document.getElementById('headingFont').value;

        // Apply fonts to CSS custom properties
        document.documentElement.style.setProperty('--primary-font', primaryFont);
        document.documentElement.style.setProperty('--heading-font', headingFont);
    }

    toggleAnimations() {
        const enabled = document.getElementById('enableAnimations').checked;
        document.documentElement.style.setProperty('--animations-enabled', enabled ? '1' : '0');
    }

    updatePageLayout() {
        const layout = document.querySelector('input[name="pageLayout"]:checked').value;
        const canvasContent = document.getElementById('canvasContent');

        canvasContent.className = `canvas-content layout-${layout}`;
    }

    savePage() {
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn.innerHTML;

        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;

        try {
            // Collect all form data with error handling
            const formData = {
                sections: this.sections,
                navbar_config: this.getNavbarConfig(),
                animations_config: this.getAnimationsConfig(),
                fonts_config: this.getFontsConfig(),
                colors: this.getColorsConfig(),
                page_layout: this.getPageLayout(),
                enable_animations: this.getEnableAnimations()
            };

            console.log('Sending formData:', formData);

            fetch('{{ route("doctor.landing-page.update-sections") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Error response text:', text);
                        throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Success response:', data);
                if (data.success) {
                    this.showNotification('Page saved successfully!', 'success');
                } else {
                    this.showNotification('Error saving page: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                this.showNotification('Error saving page: ' + error.message, 'error');
            })
            .finally(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        } catch (error) {
            console.error('JavaScript error in savePage:', error);
            this.showNotification('Error preparing data: ' + error.message, 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    }

    getNavbarConfig() {
        const stickyNavbar = document.getElementById('stickyNavbar');
        const navbarStyle = document.getElementById('navbarStyle');

        return {
            sticky: stickyNavbar ? stickyNavbar.checked : true,
            style: navbarStyle ? navbarStyle.value : 'default',
            custom_links: [] // TODO: Implement custom links
        };
    }

    getAnimationsConfig() {
        const enableAnimations = document.getElementById('enableAnimations');
        const animationSpeed = document.getElementById('animationSpeed');

        return {
            enabled: enableAnimations ? enableAnimations.checked : false,
            speed: animationSpeed ? animationSpeed.value : 'medium'
        };
    }

    getFontsConfig() {
        const primaryFont = document.getElementById('primaryFont');
        const headingFont = document.getElementById('headingFont');

        return {
            primary: primaryFont ? primaryFont.value : 'Inter',
            heading: headingFont ? headingFont.value : 'Inter'
        };
    }

    getColorsConfig() {
        const primary = document.getElementById('globalPrimary');
        const secondary = document.getElementById('globalSecondary');
        const accent = document.getElementById('globalAccent');

        return {
            primary: primary ? primary.value : '#3b82f6',
            secondary: secondary ? secondary.value : '#6b7280',
            accent: accent ? accent.value : '#10b981'
        };
    }

    getPageLayout() {
        const checkedLayout = document.querySelector('input[name="pageLayout"]:checked');
        return checkedLayout ? checkedLayout.value : 'default';
    }

    getEnableAnimations() {
        const animationsCheckbox = document.getElementById('enableAnimations');
        return animationsCheckbox ? animationsCheckbox.checked : false;
    }

    previewPage() {
        const previewUrl = '{{ route("doctor.landing-page.preview", $landingPage->username) }}';
        window.open(previewUrl, '_blank');
    }

    togglePublish() {
        const publishBtn = document.getElementById('publishBtn');
        const originalText = publishBtn.innerHTML;

        publishBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        publishBtn.disabled = true;

        fetch('{{ route("doctor.landing-page.toggle-publish") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                publishBtn.className = data.is_published ? 'btn btn-warning' : 'btn btn-primary';
                publishBtn.innerHTML = data.is_published ?
                    '<i class="fas fa-eye-slash"></i> Unpublish' :
                    '<i class="fas fa-globe"></i> Publish';

                this.showNotification(data.message, 'success');
            } else {
                this.showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            this.showNotification('Error processing request', 'error');
            console.error('Error:', error);
        })
        .finally(() => {
            publishBtn.disabled = false;
        });
    }

    updateHistory() {
        // Remove any history after current index
        this.history = this.history.slice(0, this.historyIndex + 1);

        // Add current state
        this.history.push(JSON.parse(JSON.stringify(this.sections)));
        this.historyIndex++;

        // Limit history size
        if (this.history.length > 50) {
            this.history.shift();
            this.historyIndex--;
        }

        // Update undo/redo buttons
        document.getElementById('undoBtn').disabled = this.historyIndex <= 0;
        document.getElementById('redoBtn').disabled = this.historyIndex >= this.history.length - 1;
    }

    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.sections = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.loadSections();

            document.getElementById('undoBtn').disabled = this.historyIndex <= 0;
            document.getElementById('redoBtn').disabled = false;
        }
    }

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.sections = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.loadSections();

            document.getElementById('redoBtn').disabled = this.historyIndex >= this.history.length - 1;
            document.getElementById('undoBtn').disabled = false;
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize page builder when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.pageBuilder = new PageBuilder();
});
</script>
@endpush
