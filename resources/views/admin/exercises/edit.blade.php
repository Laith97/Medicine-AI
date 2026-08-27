@extends('layouts.admin')

@section('title', 'Edit Exercise: ' . $exercise->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-pen" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Edit Exercise</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $exercise->name }} · {{ ucfirst($exercise->category) }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.exercises.show', $exercise) }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-eye me-1"></i> View</a>
            <a href="{{ route('admin.exercises.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.exercises.update', $exercise) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden">
                    <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><span style="width:28px;height:28px;border-radius:8px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:.75rem"><i class="fas fa-info-circle"></i></span> Basic Information</h5></div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Exercise Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $exercise->name) }}" required style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}" {{ old('category', $exercise->category) === $category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                                    @endforeach
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required style="border-radius:12px;border:1px solid #e2e8f0">{{ old('description', $exercise->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="difficulty_level" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Difficulty Level <span class="text-danger">*</span></label>
                                <select class="form-select @error('difficulty_level') is-invalid @enderror" id="difficulty_level" name="difficulty_level" required style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                    <option value="">Select Difficulty</option>
                                    @foreach($difficulties as $difficulty)
                                        <option value="{{ $difficulty }}" {{ old('difficulty_level', $exercise->difficulty_level) === $difficulty ? 'selected' : '' }}>{{ ucfirst($difficulty) }}</option>
                                    @endforeach
                                </select>
                                @error('difficulty_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="duration" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Duration (seconds)</label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" value="{{ old('duration', $exercise->duration) }}" min="1" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions & Details -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden">
                    <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><span style="width:28px;height:28px;border-radius:8px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:.75rem"><i class="fas fa-list"></i></span> Instructions & Details</h5></div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="instructions" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Instructions <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('instructions') is-invalid @enderror" id="instructions" name="instructions" rows="5" required style="border-radius:12px;border:1px solid #e2e8f0">{{ old('instructions', $exercise->instructions) }}</textarea>
                            @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Equipment Required</label>
                                @php $equipmentData = old('equipment_required', $exercise->equipment_required ?? []); @endphp
                                <div class="tag-input" id="equipment-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;min-height:42px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:10px;background:white">
                                    @if($equipmentData) @foreach($equipmentData as $equipment)<span class="tag" style="background:#eff6ff;color:#1d4ed8;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #dbeafe;display:flex;align-items:center;gap:0.25rem">{{ $equipment }} <span class="tag-remove" onclick="removeTag(this, 'equipment_required[]')" style="cursor:pointer;opacity:0.7">×</span></span>@endforeach @endif
                                    <input type="text" placeholder="Add + Enter" onkeydown="addTag(event, 'equipment_required[]', 'equipment-container')" style="border:none;outline:none;flex:1;min-width:100px;font-size:.88rem">
                                </div>
                                <div id="equipment-hidden" style="display:none;">@if($equipmentData) @foreach($equipmentData as $equipment)<input type="hidden" name="equipment_required[]" value="{{ $equipment }}">@endforeach @endif</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Target Muscle Groups</label>
                                @php $muscleData = old('target_muscle_groups', $exercise->target_muscle_groups ?? []); @endphp
                                <div class="tag-input" id="muscle-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;min-height:42px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:10px;background:white">
                                    @if($muscleData) @foreach($muscleData as $muscle)<span class="tag" style="background:#f5f3ff;color:#6d28d9;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #ddd6fe;display:flex;align-items:center;gap:0.25rem">{{ $muscle }} <span class="tag-remove" onclick="removeTag(this, 'target_muscle_groups[]')" style="cursor:pointer;opacity:0.7">×</span></span>@endforeach @endif
                                    <input type="text" placeholder="Add + Enter" onkeydown="addTag(event, 'target_muscle_groups[]', 'muscle-container')" style="border:none;outline:none;flex:1;min-width:100px;font-size:.88rem">
                                </div>
                                <div id="muscle-hidden" style="display:none;">@if($muscleData) @foreach($muscleData as $muscle)<input type="hidden" name="target_muscle_groups[]" value="{{ $muscle }}">@endforeach @endif</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Contraindications</label>
                                @php $contraindicationsData = old('contraindications', $exercise->contraindications ?? []); @endphp
                                <div class="tag-input" id="contraindications-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;min-height:42px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:10px;background:white">
                                    @if($contraindicationsData) @foreach($contraindicationsData as $contraindication)<span class="tag" style="background:#fef2f2;color:#991b1b;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #fecaca;display:flex;align-items:center;gap:0.25rem">{{ $contraindication }} <span class="tag-remove" onclick="removeTag(this, 'contraindications[]')" style="cursor:pointer;opacity:0.7">×</span></span>@endforeach @endif
                                    <input type="text" placeholder="Add + Enter" onkeydown="addTag(event, 'contraindications[]', 'contraindications-container')" style="border:none;outline:none;flex:1;min-width:100px;font-size:.88rem">
                                </div>
                                <div id="contraindications-hidden" style="display:none;">@if($contraindicationsData) @foreach($contraindicationsData as $contraindication)<input type="hidden" name="contraindications[]" value="{{ $contraindication }}">@endforeach @endif</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Media -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden">
                    <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><span style="width:28px;height:28px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;font-size:.75rem"><i class="fas fa-photo-video"></i></span> Media</h5></div>
                    <div class="card-body p-4">
                        @if($exercise->image_url || $exercise->video_url)
                        <div class="mb-3 p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px">
                            <div style="font-size:.72rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Current Media</div>
                            @if($exercise->image_url)<div class="mb-2"><img src="{{ $exercise->image_url }}" alt="Current" style="width:100%;max-height:160px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0"><small class="text-muted" style="font-size:.72rem">Current Image</small></div>@endif
                            @if($exercise->video_url)<div><video style="width:100%;max-height:160px;border-radius:10px;border:1px solid #e2e8f0" controls><source src="{{ $exercise->video_url }}" type="video/mp4"></video><small class="text-muted" style="font-size:.72rem">Current Video</small></div>@endif
                        </div>
                        @endif
                        <div class="mb-3">
                            <label for="image_file" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Upload New Image</label>
                            <input type="file" class="form-control @error('image_file') is-invalid @enderror" id="image_file" name="image_file" accept="image/*" style="border-radius:10px;border:1px solid #e2e8f0">
                            <div class="form-text" style="font-size:.74rem;color:#64748b">Max 5MB</div>
                            @error('image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="image-preview" class="mt-3" style="display:none;"><img id="image-preview-img" style="width:100%;max-height:200px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0" alt="preview"></div>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Or New Image URL</label>
                            <input type="url" class="form-control @error('image_url') is-invalid @enderror" id="image_url" name="image_url" value="{{ old('image_url', $exercise->image_url) }}" placeholder="https://example.com/image.jpg" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                            @error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <hr style="border-color:#f1f5f9">
                        <div class="mb-3">
                            <label for="video_file" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Upload New Video</label>
                            <input type="file" class="form-control @error('video_file') is-invalid @enderror" id="video_file" name="video_file" accept="video/*" style="border-radius:10px;border:1px solid #e2e8f0">
                            <div class="form-text" style="font-size:.74rem;color:#64748b">Max 50MB</div>
                            @error('video_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="video-preview" class="mt-3" style="display:none;"><video id="video-preview-video" style="width:100%;max-height:200px;border-radius:12px;border:1px solid #e2e8f0" controls></video></div>
                        </div>
                        <div class="mb-1">
                            <label for="video_url" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Or New Video URL</label>
                            <input type="url" class="form-control @error('video_url') is-invalid @enderror" id="video_url" name="video_url" value="{{ old('video_url', $exercise->video_url) }}" placeholder="https://example.com/video.mp4" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                            @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius:16px">
                    <div class="card-body p-3 d-flex gap-2">
                        <a href="{{ route('admin.exercises.show', $exercise) }}" class="btn btn-light border flex-grow-1" style="border-radius:12px;font-weight:600;padding:10px">Cancel</a>
                        <button type="submit" class="btn text-white flex-grow-1" style="background:#0f172a;border-radius:12px;font-weight:700;padding:10px"><i class="fas fa-save me-1"></i> Update Exercise</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function addTag(event, inputName, containerId) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            const input = event.target;
            const value = input.value.trim().replace(/,$/, '');
            if (value) {
                const tag = document.createElement('span');
                tag.style.cssText='background:#eff6ff;color:#1d4ed8;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #dbeafe;display:flex;align-items:center;gap:0.25rem';
                if(inputName.includes('muscle')) tag.style.cssText='background:#f5f3ff;color:#6d28d9;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #ddd6fe;display:flex;align-items:center;gap:0.25rem';
                if(inputName.includes('contraindications')) tag.style.cssText='background:#fef2f2;color:#991b1b;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #fecaca;display:flex;align-items:center;gap:0.25rem';
                tag.innerHTML = `${value} <span class="tag-remove" onclick="removeTag(this, '${inputName}')" style="cursor:pointer;opacity:0.7">×</span>`;
                const container = document.getElementById(containerId);
                container.insertBefore(tag, input);
                const hiddenContainer = document.getElementById(containerId.replace('-container', '-hidden'));
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden'; hiddenInput.name = inputName; hiddenInput.value = value;
                hiddenContainer.appendChild(hiddenInput);
                input.value = '';
            }
        }
    }
    function removeTag(element, inputName) {
        const tag = element.parentElement;
        const value = tag.textContent.replace('×', '').trim();
        tag.remove();
        document.querySelectorAll(`input[name="${inputName}"][value="${value}"]`).forEach(input => input.remove());
    }
    document.getElementById('image_file')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('image-preview-img').src = ev.target.result;
                document.getElementById('image-preview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
    document.getElementById('video_file')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            document.getElementById('video-preview-video').src = url;
            document.getElementById('video-preview').style.display = 'block';
        }
    });
</script>
@endpush
