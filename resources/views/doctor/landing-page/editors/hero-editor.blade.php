<div class="section-editor hero-editor">
    <div class="editor-tabs">
        <ul class="nav nav-pills nav-fill mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#hero-content" type="button">
                    <i class="fas fa-edit me-2"></i>Content
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hero-design" type="button">
                    <i class="fas fa-palette me-2"></i>Design
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#hero-animation" type="button">
                    <i class="fas fa-magic me-2"></i>Animation
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- Content Tab -->
        <div class="tab-pane fade show active" id="hero-content">
            <div class="form-group mb-3">
                <label class="form-label fw-bold">Main Title</label>
                <input type="text" class="form-control" name="title" placeholder="Welcome to My Practice">
                <small class="form-text text-muted">The main headline that grabs attention</small>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Subtitle</label>
                <textarea class="form-control" name="subtitle" rows="2" placeholder="Providing quality healthcare with compassion"></textarea>
                <small class="form-text text-muted">Supporting text that explains your value proposition</small>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Primary Button</label>
                <div class="row g-2">
                    <div class="col-8">
                        <input type="text" class="form-control" name="button_text" placeholder="Book Appointment">
                    </div>
                    <div class="col-4">
                        <select class="form-select" name="button_icon">
                            <option value="">No Icon</option>
                            <option value="fas fa-calendar-plus">Calendar</option>
                            <option value="fas fa-phone">Phone</option>
                            <option value="fas fa-arrow-right">Arrow</option>
                            <option value="fas fa-heart">Heart</option>
                        </select>
                    </div>
                </div>
                <input type="text" class="form-control mt-2" name="button_link" placeholder="#appointments">
                <small class="form-text text-muted">Button text, icon, and link destination</small>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Secondary Button (Optional)</label>
                <div class="row g-2">
                    <div class="col-8">
                        <input type="text" class="form-control" name="secondary_button_text" placeholder="Learn More">
                    </div>
                    <div class="col-4">
                        <select class="form-select" name="secondary_button_icon">
                            <option value="">No Icon</option>
                            <option value="fas fa-info-circle">Info</option>
                            <option value="fas fa-play">Play</option>
                            <option value="fas fa-arrow-right">Arrow</option>
                        </select>
                    </div>
                </div>
                <input type="text" class="form-control mt-2" name="secondary_button_link" placeholder="#about">
            </div>

            <div class="form-group mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="show_scroll_indicator" checked>
                    <label class="form-check-label">Show scroll indicator</label>
                </div>
            </div>
        </div>

        <!-- Design Tab -->
        <div class="tab-pane fade" id="hero-design">
            <div class="form-group mb-4">
                <label class="form-label fw-bold">Background Type</label>
                <div class="background-type-selector">
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="background_type" id="bg-color" value="color" checked>
                            <label class="btn btn-outline-primary w-100" for="bg-color">
                                <i class="fas fa-fill-drip d-block mb-1"></i>
                                <small>Solid Color</small>
                            </label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="background_type" id="bg-gradient" value="gradient">
                            <label class="btn btn-outline-primary w-100" for="bg-gradient">
                                <i class="fas fa-palette d-block mb-1"></i>
                                <small>Gradient</small>
                            </label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="background_type" id="bg-image" value="image">
                            <label class="btn btn-outline-primary w-100" for="bg-image">
                                <i class="fas fa-image d-block mb-1"></i>
                                <small>Image</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="background-options">
                <!-- Color Option -->
                <div class="bg-option" data-type="color">
                    <div class="form-group mb-3">
                        <label class="form-label">Background Color</label>
                        <div class="color-picker-group">
                            <input type="color" class="form-control form-control-color" name="background_color" value="#3b82f6">
                            <input type="text" class="form-control" name="background_color_hex" value="#3b82f6">
                        </div>
                    </div>
                </div>

                <!-- Gradient Option -->
                <div class="bg-option" data-type="gradient" style="display: none;">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Start Color</label>
                            <div class="color-picker-group">
                                <input type="color" class="form-control form-control-color" name="gradient_start" value="#3b82f6">
                                <input type="text" class="form-control" name="gradient_start_hex" value="#3b82f6">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Color</label>
                            <div class="color-picker-group">
                                <input type="color" class="form-control form-control-color" name="gradient_end" value="#10b981">
                                <input type="text" class="form-control" name="gradient_end_hex" value="#10b981">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label class="form-label">Gradient Direction</label>
                        <select class="form-select" name="gradient_direction">
                            <option value="135deg">Diagonal (Default)</option>
                            <option value="90deg">Horizontal</option>
                            <option value="0deg">Vertical</option>
                            <option value="45deg">Diagonal Alt</option>
                        </select>
                    </div>
                </div>

                <!-- Image Option -->
                <div class="bg-option" data-type="image" style="display: none;">
                    <div class="form-group mb-3">
                        <label class="form-label">Background Image</label>
                        <div class="image-upload-area">
                            <input type="file" class="form-control" name="background_image" accept="image/*">
                            <div class="upload-preview mt-2" style="display: none;">
                                <img src="" alt="Preview" class="img-fluid rounded">
                                <button type="button" class="btn btn-sm btn-danger mt-2 remove-image">Remove</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Image Position</label>
                        <select class="form-select" name="background_position">
                            <option value="center">Center</option>
                            <option value="top">Top</option>
                            <option value="bottom">Bottom</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Overlay Opacity</label>
                        <div class="range-slider">
                            <input type="range" class="form-range" name="overlay_opacity" min="0" max="1" step="0.1" value="0.5">
                            <div class="range-value">50%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Text Color</label>
                <div class="color-picker-group">
                    <input type="color" class="form-control form-control-color" name="text_color" value="#ffffff">
                    <input type="text" class="form-control" name="text_color_hex" value="#ffffff">
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Button Colors</label>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small">Button Background</label>
                        <div class="color-picker-group">
                            <input type="color" class="form-control form-control-color" name="button_color" value="#ffffff">
                            <input type="text" class="form-control" name="button_color_hex" value="#ffffff">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Button Text</label>
                        <div class="color-picker-group">
                            <input type="color" class="form-control form-control-color" name="button_text_color" value="#3b82f6">
                            <input type="text" class="form-control" name="button_text_color_hex" value="#3b82f6">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Height</label>
                <select class="form-select" name="section_height">
                    <option value="auto">Auto</option>
                    <option value="100vh" selected>Full Screen</option>
                    <option value="80vh">80% Screen</option>
                    <option value="600px">600px</option>
                    <option value="500px">500px</option>
                </select>
            </div>
        </div>

        <!-- Animation Tab -->
        <div class="tab-pane fade" id="hero-animation">
            <div class="form-group mb-3">
                <label class="form-label fw-bold">Entrance Animation</label>
                <select class="form-select" name="animation">
                    <option value="">No Animation</option>
                    <optgroup label="Fade Effects">
                        <option value="fadeIn">Fade In</option>
                        <option value="fadeInUp" selected>Fade In Up</option>
                        <option value="fadeInDown">Fade In Down</option>
                        <option value="fadeInLeft">Fade In Left</option>
                        <option value="fadeInRight">Fade In Right</option>
                    </optgroup>
                    <optgroup label="Slide Effects">
                        <option value="slideInUp">Slide In Up</option>
                        <option value="slideInDown">Slide In Down</option>
                        <option value="slideInLeft">Slide In Left</option>
                        <option value="slideInRight">Slide In Right</option>
                    </optgroup>
                    <optgroup label="Zoom Effects">
                        <option value="zoomIn">Zoom In</option>
                        <option value="zoomInUp">Zoom In Up</option>
                        <option value="zoomInDown">Zoom In Down</option>
                    </optgroup>
                    <optgroup label="Bounce Effects">
                        <option value="bounceIn">Bounce In</option>
                        <option value="bounceInUp">Bounce In Up</option>
                        <option value="bounceInDown">Bounce In Down</option>
                    </optgroup>
                </select>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Animation Duration</label>
                <select class="form-select" name="animation_duration">
                    <option value="500">Fast (0.5s)</option>
                    <option value="1000" selected>Normal (1s)</option>
                    <option value="1500">Slow (1.5s)</option>
                    <option value="2000">Very Slow (2s)</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label class="form-label fw-bold">Animation Delay</label>
                <div class="range-slider">
                    <input type="range" class="form-range" name="animation_delay" min="0" max="2000" step="100" value="0">
                    <div class="range-value">0ms</div>
                </div>
                <small class="form-text text-muted">Delay before animation starts</small>
            </div>

            <div class="form-group mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="animation_repeat">
                    <label class="form-check-label">Repeat animation on scroll</label>
                </div>
            </div>

            <div class="animation-preview">
                <button type="button" class="btn btn-outline-primary btn-sm" id="previewAnimation">
                    <i class="fas fa-play me-2"></i>Preview Animation
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.section-editor {
    max-height: 70vh;
    overflow-y: auto;
}

.color-picker-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.color-picker-group .form-control-color {
    width: 50px;
    height: 38px;
    flex-shrink: 0;
}

.background-type-selector .btn-check:checked + .btn {
    background-color: var(--bs-primary);
    color: white;
}

.range-slider {
    position: relative;
}

.range-value {
    position: absolute;
    top: -30px;
    right: 0;
    background: var(--bs-primary);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.image-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.image-upload-area:hover {
    border-color: var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.upload-preview img {
    max-height: 150px;
    object-fit: cover;
}

.animation-preview {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Background type switching
    const bgTypeInputs = document.querySelectorAll('input[name="background_type"]');
    const bgOptions = document.querySelectorAll('.bg-option');

    bgTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            bgOptions.forEach(option => {
                option.style.display = option.dataset.type === this.value ? 'block' : 'none';
            });
        });
    });

    // Color picker sync
    document.querySelectorAll('.color-picker-group').forEach(group => {
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

    // Range slider value display
    document.querySelectorAll('.range-slider input[type="range"]').forEach(slider => {
        const valueDisplay = slider.parentNode.querySelector('.range-value');

        slider.addEventListener('input', function() {
            let value = this.value;
            let suffix = '';

            if (this.name === 'overlay_opacity') {
                value = Math.round(value * 100);
                suffix = '%';
            } else if (this.name === 'animation_delay') {
                suffix = 'ms';
            }

            valueDisplay.textContent = value + suffix;
        });
    });

    // Image upload preview
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const preview = this.parentNode.querySelector('.upload-preview');
            const img = preview.querySelector('img');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Remove image
    document.querySelectorAll('.remove-image').forEach(btn => {
        btn.addEventListener('click', function() {
            const preview = this.parentNode;
            const input = preview.parentNode.querySelector('input[type="file"]');

            input.value = '';
            preview.style.display = 'none';
        });
    });

    // Animation preview
    document.getElementById('previewAnimation')?.addEventListener('click', function() {
        const animationType = document.querySelector('select[name="animation"]').value;
        const duration = document.querySelector('select[name="animation_duration"]').value;

        if (animationType) {
            // This would trigger a preview in the main canvas
            window.parent.postMessage({
                type: 'preview-animation',
                animation: animationType,
                duration: duration
            }, '*');
        }
    });
});
</script>
