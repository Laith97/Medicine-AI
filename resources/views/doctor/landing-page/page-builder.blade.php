@extends('master')

@section('title', 'Page Builder - Landing Page')

@section('content')
<div class="page-builder-container">
    <!-- Header -->
    <div class="page-builder-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h4 mb-0">
                        <i class="fas fa-magic text-primary"></i>
                        Page Builder
                    </h1>
                    <small class="text-muted">Design your perfect landing page</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-secondary" id="undoBtn" disabled>
                            <i class="fas fa-undo"></i> Undo
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="redoBtn" disabled>
                            <i class="fas fa-redo"></i> Redo
                        </button>
                    </div>
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" id="previewBtn">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-outline-info" id="responsiveToggle">
                            <i class="fas fa-mobile-alt"></i> Mobile View
                        </button>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" id="saveBtn">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <button type="button" class="btn {{ $landingPage->is_published ? 'btn-warning' : 'btn-primary' }}" id="publishBtn">
                            <i class="fas {{ $landingPage->is_published ? 'fa-eye-slash' : 'fa-globe' }}"></i>
                            {{ $landingPage->is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="page-builder-content">
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
<style>
.page-builder-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}

.page-builder-header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 0;
    z-index: 1000;
}

.page-builder-content {
    flex: 1;
    overflow: hidden;
}

.page-builder-sidebar {
    background: white;
    border-right: 1px solid #e2e8f0;
    height: calc(100vh - 80px);
    overflow-y: auto;
}

.sidebar-content {
    height: 100%;
}

.nav-tabs {
    border-bottom: 1px solid #e2e8f0;
}

.nav-tabs .nav-link {
    border: none;
    color: #64748b;
    padding: 0.75rem 1rem;
}

.nav-tabs .nav-link.active {
    background: #f1f5f9;
    color: #3b82f6;
    border-bottom: 2px solid #3b82f6;
}

.section-template-card {
    margin-bottom: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s;
    cursor: pointer;
}

.section-template-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
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
    background: #f1f5f9;
    height: calc(100vh - 80px);
    overflow: auto;
    padding: 2rem;
}

.canvas-container {
    display: flex;
    justify-content: center;
    min-height: 100%;
}

.device-frame {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
    margin-bottom: 1.5rem;
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.control-group:last-child {
    border-bottom: none;
}

.control-group h6 {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #374151;
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
            case 'background_color':
            case 'text_color':
                return `
                    <div class="mb-3">
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

        // Collect all form data
        const formData = {
            page_sections: this.sections,
            navbar_config: this.getNavbarConfig(),
            animations_config: this.getAnimationsConfig(),
            fonts_config: this.getFontsConfig(),
            colors: this.getColorsConfig(),
            page_layout: document.querySelector('input[name="pageLayout"]:checked').value,
            enable_animations: document.getElementById('enableAnimations').checked
        };

        fetch('{{ route("doctor.landing-page.update-sections") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Page saved successfully!', 'success');
            } else {
                this.showNotification('Error saving page: ' + data.message, 'error');
            }
        })
        .catch(error => {
            this.showNotification('Error saving page', 'error');
            console.error('Error:', error);
        })
        .finally(() => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }

    getNavbarConfig() {
        return {
            sticky: document.getElementById('stickyNavbar').checked,
            style: document.getElementById('navbarStyle').value,
            custom_links: [] // TODO: Implement custom links
        };
    }

    getAnimationsConfig() {
        return {
            enabled: document.getElementById('enableAnimations').checked,
            speed: document.getElementById('animationSpeed').value
        };
    }

    getFontsConfig() {
        return {
            primary: document.getElementById('primaryFont').value,
            heading: document.getElementById('headingFont').value
        };
    }

    getColorsConfig() {
        return {
            primary: document.getElementById('globalPrimary').value,
            secondary: document.getElementById('globalSecondary').value,
            accent: document.getElementById('globalAccent').value
        };
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
