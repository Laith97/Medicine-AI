@extends('layouts.admin')

@section('title', 'Edit Exercise: ' . $exercise->name)

@push('styles')
<style>
    .form-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .form-section h4 {
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #ecf0f1;
    }

    .tag-input {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 2.5rem;
        padding: 0.5rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        background: white;
    }

    .tag {
        background: #e3f2fd;
        color: #1976d2;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .tag-remove {
        cursor: pointer;
        opacity: 0.7;
    }

    .tag-remove:hover {
        opacity: 1;
    }

    .tag-input input {
        border: none;
        outline: none;
        flex: 1;
        min-width: 100px;
    }

    .media-preview {
        max-width: 200px;
        max-height: 200px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e9ecef;
    }

    .video-preview {
        position: relative;
    }

    .video-preview video {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
    }

    .current-media {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Edit Exercise</h1>
                    <p class="mb-0">{{ $exercise->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.exercises.show', $exercise) }}" class="btn btn-outline-light">
                        <i class="fas fa-eye me-2"></i>View Details
                    </a>
                    <a href="{{ route('admin.exercises.index') }}" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Library
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.exercises.update', $exercise) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="form-section">
                <h4><i class="fas fa-info-circle me-2"></i>Basic Information</h4>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">Exercise Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $exercise->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror"
                                    id="category" name="category" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" {{ old('category', $exercise->category) === $category ? 'selected' : '' }}>
                                        {{ ucfirst($category) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3" required>{{ old('description', $exercise->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="difficulty_level" class="form-label">Difficulty Level <span class="text-danger">*</span></label>
                            <select class="form-select @error('difficulty_level') is-invalid @enderror"
                                    id="difficulty_level" name="difficulty_level" required>
                                <option value="">Select Difficulty</option>
                                @foreach($difficulties as $difficulty)
                                    <option value="{{ $difficulty }}" {{ old('difficulty_level', $exercise->difficulty_level) === $difficulty ? 'selected' : '' }}>
                                        {{ ucfirst($difficulty) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('difficulty_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (seconds)</label>
                            <input type="number" class="form-control @error('duration') is-invalid @enderror"
                                   id="duration" name="duration" value="{{ old('duration', $exercise->duration) }}" min="1">
                            @error('duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions & Details -->
            <div class="form-section">
                <h4><i class="fas fa-list me-2"></i>Instructions & Details</h4>
                <div class="mb-3">
                    <label for="instructions" class="form-label">Instructions <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('instructions') is-invalid @enderror"
                              id="instructions" name="instructions" rows="5" required>{{ old('instructions', $exercise->instructions) }}</textarea>
                    @error('instructions')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Equipment Required</label>
                            <div class="tag-input" id="equipment-container">
                                @php $equipmentData = old('equipment_required', $exercise->equipment_required ?? []); @endphp
                                @if($equipmentData)
                                    @foreach($equipmentData as $equipment)
                                        <span class="tag">
                                            {{ $equipment }}
                                            <span class="tag-remove" onclick="removeTag(this, 'equipment_required[]')">×</span>
                                        </span>
                                    @endforeach
                                @endif
                                <input type="text" placeholder="Add equipment..." onkeydown="addTag(event, 'equipment_required[]', 'equipment-container')">
                            </div>
                            <div id="equipment-hidden" style="display: none;">
                                @if($equipmentData)
                                    @foreach($equipmentData as $equipment)
                                        <input type="hidden" name="equipment_required[]" value="{{ $equipment }}">
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Target Muscle Groups</label>
                            <div class="tag-input" id="muscle-container">
                                @php $muscleData = old('target_muscle_groups', $exercise->target_muscle_groups ?? []); @endphp
                                @if($muscleData)
                                    @foreach($muscleData as $muscle)
                                        <span class="tag">
                                            {{ $muscle }}
                                            <span class="tag-remove" onclick="removeTag(this, 'target_muscle_groups[]')">×</span>
                                        </span>
                                    @endforeach
                                @endif
                                <input type="text" placeholder="Add muscle group..." onkeydown="addTag(event, 'target_muscle_groups[]', 'muscle-container')">
                            </div>
                            <div id="muscle-hidden" style="display: none;">
                                @if($muscleData)
                                    @foreach($muscleData as $muscle)
                                        <input type="hidden" name="target_muscle_groups[]" value="{{ $muscle }}">
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraindications</label>
                    <div class="tag-input" id="contraindications-container">
                        @php $contraindicationsData = old('contraindications', $exercise->contraindications ?? []); @endphp
                        @if($contraindicationsData)
                            @foreach($contraindicationsData as $contraindication)
                                <span class="tag">
                                    {{ $contraindication }}
                                    <span class="tag-remove" onclick="removeTag(this, 'contraindications[]')">×</span>
                                </span>
                            @endforeach
                        @endif
                        <input type="text" placeholder="Add contraindication..." onkeydown="addTag(event, 'contraindications[]', 'contraindications-container')">
                    </div>
                    <div id="contraindications-hidden" style="display: none;">
                        @if($contraindicationsData)
                            @foreach($contraindicationsData as $contraindication)
                                <input type="hidden" name="contraindications[]" value="{{ $contraindication }}">
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Media Upload -->
            <div class="form-section">
                <h4><i class="fas fa-photo-video me-2"></i>Media</h4>

                <!-- Current Media Display -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        @if($exercise->image_url)
                            <div class="current-media">
                                <h6>Current Image</h6>
                                <img src="{{ $exercise->image_url }}" alt="Current image" class="media-preview">
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        @if($exercise->video_url)
                            <div class="current-media">
                                <h6>Current Video</h6>
                                <video class="media-preview" controls>
                                    <source src="{{ $exercise->video_url }}" type="video/mp4">
                                </video>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image_file" class="form-label">Upload New Image</label>
                            <input type="file" class="form-control @error('image_file') is-invalid @enderror"
                                   id="image_file" name="image_file" accept="image/*">
                            <div class="form-text">Upload a new image to replace the current one (max 5MB)</div>
                            @error('image_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="image-preview" class="mt-2" style="display: none;">
                                <img id="image-preview-img" class="media-preview" alt="New image preview">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Or New Image URL</label>
                            <input type="url" class="form-control @error('image_url') is-invalid @enderror"
                                   id="image_url" name="image_url" value="{{ old('image_url', $exercise->image_url) }}"
                                   placeholder="https://example.com/image.jpg">
                            @error('image_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="video_file" class="form-label">Upload New Video</label>
                            <input type="file" class="form-control @error('video_file') is-invalid @enderror"
                                   id="video_file" name="video_file" accept="video/*">
                            <div class="form-text">Upload a new video to replace the current one (max 50MB)</div>
                            @error('video_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="video-preview" class="mt-2" style="display: none;">
                                <video id="video-preview-video" class="media-preview" controls></video>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Or New Video URL</label>
                            <input type="url" class="form-control @error('video_url') is-invalid @enderror"
                                   id="video_url" name="video_url" value="{{ old('video_url', $exercise->video_url) }}"
                                   placeholder="https://example.com/video.mp4">
                            @error('video_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.exercises.show', $exercise) }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Exercise
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize existing tags on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Equipment tags
        @if($exercise->equipment_required)
            @foreach($exercise->equipment_required as $equipment)
                addExistingTag('{{ $equipment }}', 'equipment_required[]', 'equipment-container');
            @endforeach
        @endif

        // Muscle group tags
        @if($exercise->target_muscle_groups)
            @foreach($exercise->target_muscle_groups as $muscle)
                addExistingTag('{{ $muscle }}', 'target_muscle_groups[]', 'muscle-container');
            @endforeach
        @endif

        // Contraindications tags
        @if($exercise->contraindications)
            @foreach($exercise->contraindications as $contraindication)
                addExistingTag('{{ $contraindication }}', 'contraindications[]', 'contraindications-container');
            @endforeach
        @endif
    });

    function addExistingTag(value, inputName, containerId) {
        const tag = document.createElement('span');
        tag.className = 'tag';
        tag.innerHTML = `${value} <span class="tag-remove" onclick="removeTag(this, '${inputName}')">×</span>`;

        const container = document.getElementById(containerId);
        const input = container.querySelector('input');
        container.insertBefore(tag, input);

        // Add hidden input
        const hiddenContainer = document.getElementById(containerId.replace('-container', '-hidden'));
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = inputName;
        hiddenInput.value = value;
        hiddenContainer.appendChild(hiddenInput);
    }

    // Tag input functionality
    function addTag(event, inputName, containerId) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            const input = event.target;
            const value = input.value.trim();

            if (value) {
                // Create tag element
                const tag = document.createElement('span');
                tag.className = 'tag';
                tag.innerHTML = `${value} <span class="tag-remove" onclick="removeTag(this, '${inputName}')">×</span>`;

                // Add to container
                const container = document.getElementById(containerId);
                container.insertBefore(tag, input);

                // Add hidden input
                const hiddenContainer = document.getElementById(containerId.replace('-container', '-hidden'));
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = inputName;
                hiddenInput.value = value;
                hiddenContainer.appendChild(hiddenInput);

                // Clear input
                input.value = '';
            }
        }
    }

    function removeTag(element, inputName) {
        const tag = element.parentElement;
        const value = tag.textContent.replace('×', '').trim();

        // Remove tag
        tag.remove();

        // Remove hidden input
        const hiddenInputs = document.querySelectorAll(`input[name="${inputName}"][value="${value}"]`);
        hiddenInputs.forEach(input => input.remove());
    }

    // Media preview functionality
    document.getElementById('image_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('image-preview-img').src = e.target.result;
                document.getElementById('image-preview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('video_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            document.getElementById('video-preview-video').src = url;
            document.getElementById('video-preview').style.display = 'block';
        }
    });

    // URL preview
    document.getElementById('image_url').addEventListener('input', function(e) {
        const url = e.target.value;
        if (url) {
            document.getElementById('image-preview-img').src = url;
            document.getElementById('image-preview').style.display = 'block';
        } else {
            document.getElementById('image-preview').style.display = 'none';
        }
    });

    document.getElementById('video_url').addEventListener('input', function(e) {
        const url = e.target.value;
        if (url) {
            document.getElementById('video-preview-video').src = url;
            document.getElementById('video-preview').style.display = 'block';
        } else {
            document.getElementById('video-preview').style.display = 'none';
        }
    });
</script>
@endpush
