/**
 * Advanced Page Builder JavaScript Library
 * For Medical Landing Pages
 */

class MedicalPageBuilder {
    constructor(options = {}) {
        this.options = {
            container: '#pageBuilder',
            canvas: '#canvasContent',
            sidebar: '#sidebarContent',
            apiEndpoint: '/doctor/landing-page/update-sections',
            autoSave: true,
            autoSaveInterval: 30000, // 30 seconds
            ...options
        };

        this.sections = [];
        this.history = [];
        this.historyIndex = -1;
        this.currentSection = null;
        this.isDragging = false;
        this.draggedElement = null;
        this.autoSaveTimer = null;
        this.unsavedChanges = false;

        this.init();
    }

    init() {
        this.bindEvents();
        this.initSortable();
        this.initAutoSave();
        this.loadSections();
        this.initKeyboardShortcuts();
        this.initResponsivePreview();
        this.initAnimationEngine();
    }

    bindEvents() {
        // Section management
        document.addEventListener('click', this.handleSectionClick.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));

        // Form changes
        document.addEventListener('input', this.handleFormChange.bind(this));
        document.addEventListener('change', this.handleFormChange.bind(this));

        // Window events
        window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));

        // Custom events
        document.addEventListener('section-added', this.handleSectionAdded.bind(this));
        document.addEventListener('section-removed', this.handleSectionRemoved.bind(this));
        document.addEventListener('section-updated', this.handleSectionUpdated.bind(this));
    }

    initSortable() {
        const container = document.querySelector(this.options.canvas);
        if (!container) return;

        new Sortable(container, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.drag-handle',
            onStart: (evt) => {
                this.isDragging = true;
                this.draggedElement = evt.item;
                document.body.classList.add('dragging');
            },
            onEnd: (evt) => {
                this.isDragging = false;
                this.draggedElement = null;
                document.body.classList.remove('dragging');

                if (evt.oldIndex !== evt.newIndex) {
                    this.reorderSections(evt.oldIndex, evt.newIndex);
                }
            }
        });
    }

    initAutoSave() {
        if (!this.options.autoSave) return;

        this.autoSaveTimer = setInterval(() => {
            if (this.unsavedChanges) {
                this.savePage(true); // Silent save
            }
        }, this.options.autoSaveInterval);
    }

    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S - Save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.savePage();
            }

            // Ctrl/Cmd + Z - Undo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                this.undo();
            }

            // Ctrl/Cmd + Shift + Z - Redo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && e.shiftKey) {
                e.preventDefault();
                this.redo();
            }

            // Delete - Remove selected section
            if (e.key === 'Delete' && this.currentSection) {
                e.preventDefault();
                this.removeSection(this.currentSection.id);
            }

            // Escape - Deselect section
            if (e.key === 'Escape') {
                this.deselectSection();
            }

            // Ctrl/Cmd + D - Duplicate section
            if ((e.ctrlKey || e.metaKey) && e.key === 'd' && this.currentSection) {
                e.preventDefault();
                this.duplicateSection(this.currentSection.id);
            }
        });
    }

    initResponsivePreview() {
        const deviceButtons = document.querySelectorAll('.device-preview-btn');
        deviceButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const device = e.target.dataset.device;
                this.switchDevice(device);
            });
        });
    }

    initAnimationEngine() {
        // Initialize Intersection Observer for animations
        this.animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const animation = element.dataset.animation;
                    const delay = element.dataset.animationDelay || 0;

                    if (animation) {
                        setTimeout(() => {
                            element.classList.add(`animate-${animation}`);
                        }, delay);
                    }
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
    }

    // Section Management
    addSection(type, config = {}) {
        const sectionId = 'section_' + Date.now();
        const template = this.getSectionTemplate(type);

        if (!template) {
            console.error('Section template not found:', type);
            return null;
        }

        const section = {
            id: sectionId,
            type: type,
            config: { ...template.default_config, ...config },
            order: this.sections.length,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        this.sections.push(section);
        this.renderSection(section);
        this.updateHistory();
        this.markUnsaved();

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent('section-added', {
            detail: { section }
        }));

        // Auto-select new section
        setTimeout(() => {
            this.selectSection(sectionId);
        }, 100);

        return section;
    }

    removeSection(sectionId) {
        const sectionIndex = this.sections.findIndex(s => s.id === sectionId);
        if (sectionIndex === -1) return false;

        if (confirm('Are you sure you want to delete this section?')) {
            const section = this.sections[sectionIndex];
            this.sections.splice(sectionIndex, 1);

            // Remove from DOM
            const element = document.querySelector(`[data-section-id="${sectionId}"]`);
            if (element) {
                element.remove();
            }

            // Update order
            this.reorderSections();
            this.updateHistory();
            this.markUnsaved();

            // Dispatch custom event
            document.dispatchEvent(new CustomEvent('section-removed', {
                detail: { section }
            }));

            // Deselect if current
            if (this.currentSection && this.currentSection.id === sectionId) {
                this.deselectSection();
            }

            return true;
        }

        return false;
    }

    duplicateSection(sectionId) {
        const section = this.sections.find(s => s.id === sectionId);
        if (!section) return null;

        const duplicatedSection = {
            ...section,
            id: 'section_' + Date.now(),
            order: section.order + 1,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        // Insert after original
        this.sections.splice(section.order + 1, 0, duplicatedSection);
        this.reorderSections();
        this.renderSection(duplicatedSection);
        this.updateHistory();
        this.markUnsaved();

        return duplicatedSection;
    }

    selectSection(sectionId) {
        // Deselect current
        this.deselectSection();

        const section = this.sections.find(s => s.id === sectionId);
        if (!section) return;

        this.currentSection = section;

        // Visual selection
        const element = document.querySelector(`[data-section-id="${sectionId}"]`);
        if (element) {
            element.classList.add('selected');
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Load editor
        this.loadSectionEditor(section);

        // Update UI
        this.updateSelectionUI();
    }

    deselectSection() {
        if (this.currentSection) {
            const element = document.querySelector(`[data-section-id="${this.currentSection.id}"]`);
            if (element) {
                element.classList.remove('selected');
            }
        }

        this.currentSection = null;
        this.clearSectionEditor();
        this.updateSelectionUI();
    }

    updateSection(sectionId, config) {
        const section = this.sections.find(s => s.id === sectionId);
        if (!section) return false;

        section.config = { ...section.config, ...config };
        section.updated_at = new Date().toISOString();

        this.rerenderSection(section);
        this.updateHistory();
        this.markUnsaved();

        // Dispatch custom event
        document.dispatchEvent(new CustomEvent('section-updated', {
            detail: { section }
        }));

        return true;
    }

    reorderSections(oldIndex = null, newIndex = null) {
        if (oldIndex !== null && newIndex !== null) {
            const section = this.sections.splice(oldIndex, 1)[0];
            this.sections.splice(newIndex, 0, section);
        }

        // Update order property
        this.sections.forEach((section, index) => {
            section.order = index;
        });

        this.markUnsaved();
    }

    // Rendering
    renderSection(section) {
        const container = document.querySelector(this.options.canvas);
        if (!container) return;

        // Remove empty state if exists
        const emptyState = container.querySelector('.empty-canvas');
        if (emptyState) {
            emptyState.remove();
        }

        const sectionElement = this.createSectionElement(section);
        container.appendChild(sectionElement);

        // Initialize animations
        this.initSectionAnimations(sectionElement);
    }

    rerenderSection(section) {
        const existingElement = document.querySelector(`[data-section-id="${section.id}"]`);
        if (!existingElement) return;

        const newElement = this.createSectionElement(section);
        existingElement.parentNode.replaceChild(newElement, existingElement);

        // Re-initialize animations
        this.initSectionAnimations(newElement);

        // Maintain selection
        if (this.currentSection && this.currentSection.id === section.id) {
            newElement.classList.add('selected');
        }
    }

    createSectionElement(section) {
        const element = document.createElement('div');
        element.className = 'section-item';
        element.dataset.sectionId = section.id;
        element.dataset.sectionType = section.type;

        // Add animation attributes
        if (section.config.animation) {
            element.dataset.animation = section.config.animation;
            element.dataset.animationDelay = section.config.animation_delay || 0;
        }

        element.innerHTML = `
            <div class="section-controls">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary drag-handle" title="Drag to reorder">
                        <i class="fas fa-grip-vertical"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary edit-section-btn" title="Edit section">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info duplicate-section-btn" title="Duplicate section">
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

        // Bind events
        this.bindSectionEvents(element, section);

        return element;
    }

    renderSectionContent(section) {
        // This would render the actual section content
        // For now, return a placeholder that matches the section type
        const template = this.getSectionTemplate(section.type);
        const title = template ? template.name : section.type;

        return `
            <div class="section-placeholder" style="
                padding: 2rem;
                background: ${section.config.background_color || '#f8fafc'};
                border: 2px dashed #cbd5e1;
                text-align: center;
                border-radius: 8px;
                color: ${section.config.text_color || '#64748b'};
            ">
                <div class="placeholder-icon mb-3">
                    <i class="fas fa-${this.getSectionIcon(section.type)} fa-2x"></i>
                </div>
                <h5>${section.config.title || title}</h5>
                <p class="text-muted mb-0">Click edit to customize this section</p>
            </div>
        `;
    }

    getSectionIcon(type) {
        const icons = {
            hero: 'rocket',
            about: 'user-md',
            services: 'stethoscope',
            testimonials: 'quote-left',
            contact: 'envelope',
            cta: 'bullhorn',
            gallery: 'images',
            faq: 'question-circle'
        };

        return icons[type] || 'square';
    }

    bindSectionEvents(element, section) {
        // Edit button
        element.querySelector('.edit-section-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectSection(section.id);
        });

        // Duplicate button
        element.querySelector('.duplicate-section-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            this.duplicateSection(section.id);
        });

        // Delete button
        element.querySelector('.delete-section-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            this.removeSection(section.id);
        });

        // Click to select
        element.addEventListener('click', (e) => {
            if (!e.target.closest('.section-controls')) {
                this.selectSection(section.id);
            }
        });
    }

    initSectionAnimations(element) {
        // Add to animation observer
        if (this.animationObserver) {
            this.animationObserver.observe(element);
        }
    }

    // Editor Management
    loadSectionEditor(section) {
        const editorContainer = document.querySelector('.section-editor-container');
        if (!editorContainer) return;

        // Load appropriate editor based on section type
        this.loadEditor(section.type, section);
    }

    clearSectionEditor() {
        const editorContainer = document.querySelector('.section-editor-container');
        if (editorContainer) {
            editorContainer.innerHTML = '<p class="text-muted text-center p-4">Select a section to edit</p>';
        }
    }

    loadEditor(type, section) {
        // This would load the appropriate editor component
        // For now, generate a basic form
        const editorContainer = document.querySelector('.section-editor-container');
        if (!editorContainer) return;

        const template = this.getSectionTemplate(type);
        if (!template) return;

        editorContainer.innerHTML = this.generateEditorForm(section, template);

        // Bind form events
        this.bindEditorEvents(editorContainer, section);
    }

    generateEditorForm(section, template) {
        let formHTML = `<div class="section-editor" data-section-id="${section.id}">`;
        formHTML += `<h5 class="mb-4">${template.name} Settings</h5>`;

        // Generate form fields based on config
        Object.keys(template.default_config).forEach(key => {
            const value = section.config[key] || template.default_config[key];
            formHTML += this.generateFormField(key, value, section.type);
        });

        formHTML += `
            <div class="editor-actions mt-4">
                <button type="button" class="btn btn-primary save-section-btn">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <button type="button" class="btn btn-outline-secondary cancel-section-btn">
                    Cancel
                </button>
            </div>
        </div>`;

        return formHTML;
    }

    generateFormField(key, value, sectionType) {
        const label = this.formatLabel(key);

        switch (key) {
            case 'background_color':
            case 'text_color':
            case 'button_color':
                return `
                    <div class="mb-3">
                        <label class="form-label">${label}</label>
                        <div class="color-picker-group">
                            <input type="color" class="form-control form-control-color" name="${key}" value="${value}">
                            <input type="text" class="form-control" name="${key}_hex" value="${value}">
                        </div>
                    </div>
                `;

            case 'animation':
                return `
                    <div class="mb-3">
                        <label class="form-label">${label}</label>
                        <select class="form-select" name="${key}">
                            <option value="">No Animation</option>
                            <optgroup label="Fade Effects">
                                <option value="fadeIn" ${value === 'fadeIn' ? 'selected' : ''}>Fade In</option>
                                <option value="fadeInUp" ${value === 'fadeInUp' ? 'selected' : ''}>Fade In Up</option>
                                <option value="fadeInDown" ${value === 'fadeInDown' ? 'selected' : ''}>Fade In Down</option>
                                <option value="fadeInLeft" ${value === 'fadeInLeft' ? 'selected' : ''}>Fade In Left</option>
                                <option value="fadeInRight" ${value === 'fadeInRight' ? 'selected' : ''}>Fade In Right</option>
                            </optgroup>
                            <optgroup label="Slide Effects">
                                <option value="slideInUp" ${value === 'slideInUp' ? 'selected' : ''}>Slide In Up</option>
                                <option value="slideInDown" ${value === 'slideInDown' ? 'selected' : ''}>Slide In Down</option>
                                <option value="slideInLeft" ${value === 'slideInLeft' ? 'selected' : ''}>Slide In Left</option>
                                <option value="slideInRight" ${value === 'slideInRight' ? 'selected' : ''}>Slide In Right</option>
                            </optgroup>
                            <optgroup label="Zoom Effects">
                                <option value="zoomIn" ${value === 'zoomIn' ? 'selected' : ''}>Zoom In</option>
                                <option value="zoomInUp" ${value === 'zoomInUp' ? 'selected' : ''}>Zoom In Up</option>
                                <option value="zoomInDown" ${value === 'zoomInDown' ? 'selected' : ''}>Zoom In Down</option>
                            </optgroup>
                            <optgroup label="Bounce Effects">
                                <option value="bounceIn" ${value === 'bounceIn' ? 'selected' : ''}>Bounce In</option>
                                <option value="bounceInUp" ${value === 'bounceInUp' ? 'selected' : ''}>Bounce In Up</option>
                                <option value="bounceInDown" ${value === 'bounceInDown' ? 'selected' : ''}>Bounce In Down</option>
                            </optgroup>
                        </select>
                    </div>
                `;

            case 'content':
            case 'about_text':
            case 'subtitle':
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

    formatLabel(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    bindEditorEvents(container, section) {
        // Save button
        const saveBtn = container.querySelector('.save-section-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveSection(section.id);
            });
        }

        // Cancel button
        const cancelBtn = container.querySelector('.cancel-section-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.deselectSection();
            });
        }

        // Form changes
        container.addEventListener('input', () => {
            this.markUnsaved();
        });

        container.addEventListener('change', () => {
            this.markUnsaved();
        });

        // Color picker sync
        container.querySelectorAll('.color-picker-group').forEach(group => {
            const colorInput = group.querySelector('input[type="color"]');
            const textInput = group.querySelector('input[type="text"]');

            if (colorInput && textInput) {
                colorInput.addEventListener('change', function() {
                    textInput.value = this.value;
                });

                textInput.addEventListener('change', function() {
                    if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                        colorInput.value = this.value;
                    }
                });
            }
        });
    }

    saveSection(sectionId) {
        const editorContainer = document.querySelector(`[data-section-id="${sectionId}"]`);
        if (!editorContainer) return;

        const formData = new FormData(editorContainer);
        const config = {};

        for (let [key, value] of formData.entries()) {
            if (key.endsWith('_hex')) continue; // Skip hex inputs

            // Convert checkbox values
            if (editorContainer.querySelector(`input[name="${key}"][type="checkbox"]`)) {
                config[key] = value === 'on';
            } else {
                config[key] = value;
            }
        }

        this.updateSection(sectionId, config);

        // Show success feedback
        this.showNotification('Section updated successfully!', 'success');
    }

    // History Management
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

        this.updateHistoryUI();
    }

    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.sections = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.reloadCanvas();
            this.updateHistoryUI();
            this.markUnsaved();
        }
    }

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.sections = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.reloadCanvas();
            this.updateHistoryUI();
            this.markUnsaved();
        }
    }

    updateHistoryUI() {
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');

        if (undoBtn) {
            undoBtn.disabled = this.historyIndex <= 0;
        }

        if (redoBtn) {
            redoBtn.disabled = this.historyIndex >= this.history.length - 1;
        }
    }

    // Utility Methods
    getSectionTemplate(type) {
        // This would fetch from the templates data
        // For now, return a basic template
        return window.sectionTemplates && window.sectionTemplates[type] || null;
    }

    loadSections() {
        // Load existing sections from the page data
        if (window.pageSections && Array.isArray(window.pageSections)) {
            this.sections = window.pageSections;
            this.reloadCanvas();
        }
    }

    reloadCanvas() {
        const container = document.querySelector(this.options.canvas);
        if (!container) return;

        container.innerHTML = '';

        if (this.sections.length === 0) {
            this.showEmptyCanvas();
        } else {
            this.sections.forEach(section => {
                this.renderSection(section);
            });
        }
    }

    showEmptyCanvas() {
        const container = document.querySelector(this.options.canvas);
        if (!container) return;

        container.innerHTML = `
            <div class="empty-canvas">
                <div class="empty-canvas-content">
                    <i class="fas fa-magic fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Start Building Your Page</h4>
                    <p class="text-muted">Add sections from the sidebar to create your perfect landing page</p>
                </div>
            </div>
        `;
    }

    switchDevice(device) {
        const frame = document.getElementById('deviceFrame');
        const buttons = document.querySelectorAll('.device-preview-btn');

        if (!frame) return;

        // Update button states
        buttons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.device === device);
        });

        // Update frame class
        frame.className = `device-frame ${device}-frame`;

        // Update responsive indicator
        const indicator = document.querySelector('.responsive-indicator');
        if (indicator) {
            indicator.textContent = device.charAt(0).toUpperCase() + device.slice(1) + ' View';
        }
    }

    markUnsaved() {
        this.unsavedChanges = true;
        document.title = document.title.replace(/^\*/, '') + ' *';

        // Update save button
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.classList.add('btn-warning');
            saveBtn.classList.remove('btn-success');
        }
    }

    markSaved() {
        this.unsavedChanges = false;
        document.title = document.title.replace(/^\* /, '');

        // Update save button
        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.classList.add('btn-success');
            saveBtn.classList.remove('btn-warning');
        }
    }

    savePage(silent = false) {
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn ? saveBtn.innerHTML : '';

        if (saveBtn && !silent) {
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
        }

        const data = {
            sections: this.sections,
            updated_at: new Date().toISOString()
        };

        return fetch(this.options.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            // Check if the response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned HTML instead of JSON. Please check authentication and permissions.');
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.markSaved();
                if (!silent) {
                    this.showNotification('Page saved successfully!', 'success');
                }
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            if (!silent) {
                this.showNotification('Error saving page: ' + error.message, 'error');
            }
        })
        .finally(() => {
            if (saveBtn && !silent) {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        });
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

    updateSelectionUI() {
        // Update selection-dependent UI elements
        const deleteBtn = document.querySelector('.delete-selected-btn');
        const duplicateBtn = document.querySelector('.duplicate-selected-btn');

        if (deleteBtn) {
            deleteBtn.disabled = !this.currentSection;
        }

        if (duplicateBtn) {
            duplicateBtn.disabled = !this.currentSection;
        }
    }

    // Event Handlers
    handleSectionClick(e) {
        if (e.target.closest('.add-section-btn')) {
            const type = e.target.closest('.add-section-btn').dataset.type;
            this.addSection(type);
        }
    }

    handleFormChange(e) {
        if (e.target.closest('.section-editor')) {
            this.markUnsaved();
        }
    }

    handleKeydown(e) {
        // Handle keyboard shortcuts (already implemented above)
    }

    handleBeforeUnload(e) {
        if (this.unsavedChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    }

    handleResize() {
        // Handle responsive adjustments
        this.updateCanvasSize();
    }

    handleSectionAdded(e) {
        // Custom event handler for section added
        console.log('Section added:', e.detail.section);
    }

    handleSectionRemoved(e) {
        // Custom event handler for section removed
        console.log('Section removed:', e.detail.section);
    }

    handleSectionUpdated(e) {
        // Custom event handler for section updated
        console.log('Section updated:', e.detail.section);
    }

    updateCanvasSize() {
        // Update canvas size based on current device view
        const frame = document.getElementById('deviceFrame');
        if (!frame) return;

        const container = frame.parentElement;
        const containerWidth = container.clientWidth;
        const containerHeight = container.clientHeight;

        // Adjust frame size to fit container while maintaining aspect ratio
        // This would be more complex in a real implementation
    }

    // Cleanup
    destroy() {
        // Clear timers
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
        }

        // Remove event listeners
        document.removeEventListener('click', this.handleSectionClick);
        document.removeEventListener('keydown', this.handleKeydown);
        window.removeEventListener('beforeunload', this.handleBeforeUnload);
        window.removeEventListener('resize', this.handleResize);

        // Disconnect observers
        if (this.animationObserver) {
            this.animationObserver.disconnect();
        }

        // Clear references
        this.sections = [];
        this.history = [];
        this.currentSection = null;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#pageBuilder')) {
        window.pageBuilder = new MedicalPageBuilder();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MedicalPageBuilder;
}
