@extends('layouts.app')

@section('title', 'Edit Blog Post')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Edit Blog Post</h1>
                <div>
                    <a href="{{ route('doctor.blog.show', $post) }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    <a href="{{ route('doctor.blog.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Blog
                    </a>
                </div>
            </div>

            <form action="{{ route('doctor.blog.update', $post) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Post Content</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('title') is-invalid @enderror"
                                           id="title"
                                           name="title"
                                           value="{{ old('title', $post->title) }}"
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="short_description" class="form-label">Short Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('short_description') is-invalid @enderror"
                                              id="short_description"
                                              name="short_description"
                                              rows="3"
                                              maxlength="500"
                                              required>{{ old('short_description', $post->short_description) }}</textarea>
                                    <div class="form-text">This will be shown in the blog post preview (max 500 characters)</div>
                                    @error('short_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('content') is-invalid @enderror"
                                              id="content"
                                              name="content"
                                              rows="15"
                                              required>{{ old('content', $post->content) }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- SEO Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">SEO Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="seo_title" class="form-label">SEO Title</label>
                                    <input type="text"
                                           class="form-control @error('seo_title') is-invalid @enderror"
                                           id="seo_title"
                                           name="seo_title"
                                           value="{{ old('seo_title', $post->seo_title) }}"
                                           maxlength="255">
                                    <div class="form-text">If empty, the post title will be used</div>
                                    @error('seo_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="seo_description" class="form-label">SEO Description</label>
                                    <textarea class="form-control @error('seo_description') is-invalid @enderror"
                                              id="seo_description"
                                              name="seo_description"
                                              rows="3"
                                              maxlength="500">{{ old('seo_description', $post->seo_description) }}</textarea>
                                    <div class="form-text">If empty, the short description will be used</div>
                                    @error('seo_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="seo_keywords" class="form-label">SEO Keywords</label>
                                    <input type="text"
                                           class="form-control @error('seo_keywords') is-invalid @enderror"
                                           id="seo_keywords"
                                           name="seo_keywords"
                                           value="{{ old('seo_keywords', $post->seo_meta['keywords'] ?? '') }}"
                                           maxlength="255">
                                    <div class="form-text">Separate keywords with commas</div>
                                    @error('seo_keywords')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Publish Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Publish Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_published"
                                           name="is_published"
                                           {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_published">
                                        Published
                                    </label>
                                </div>
                                <div class="form-text">
                                    @if($post->is_published)
                                        Published on {{ $post->published_at->format('M j, Y g:i A') }}
                                    @else
                                        Currently saved as draft
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Featured Image -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Featured Image</h5>
                            </div>
                            <div class="card-body">
                                @if($post->featured_image)
                                    <div class="mb-3">
                                        <img src="{{ Storage::url($post->featured_image) }}"
                                             alt="Current featured image"
                                             class="img-fluid rounded">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="remove_image"
                                                   name="remove_image">
                                            <label class="form-check-label" for="remove_image">
                                                Remove current image
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <input type="file"
                                           class="form-control @error('featured_image') is-invalid @enderror"
                                           id="featured_image"
                                           name="featured_image"
                                           accept="image/*">
                                    <div class="form-text">
                                        @if($post->featured_image)
                                            Upload a new image to replace the current one
                                        @else
                                            Recommended size: 800x400px (max 2MB)
                                        @endif
                                    </div>
                                    @error('featured_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div id="image-preview" class="d-none">
                                    <img id="preview-img" src="" alt="Preview" class="img-fluid rounded">
                                </div>
                            </div>
                        </div>

                        <!-- Post Stats -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Post Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary">{{ $post->views_count }}</h4>
                                        <small class="text-muted">Views</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-info">{{ $post->reading_time }}</h4>
                                        <small class="text-muted">Read Time</small>
                                    </div>
                                </div>
                                <hr>
                                <small class="text-muted">
                                    <strong>Created:</strong> {{ $post->created_at->format('M j, Y g:i A') }}<br>
                                    <strong>Last Updated:</strong> {{ $post->updated_at->format('M j, Y g:i A') }}
                                </small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Post
                                    </button>
                                    @if($post->is_published)
                                        <a href="{{ route('doctor.blog.post', [auth()->user()->doctor->landingPage->username ?? 'preview', $post->slug]) }}"
                                           class="btn btn-outline-info"
                                           target="_blank">
                                            <i class="fas fa-external-link-alt"></i> View Live Post
                                        </a>
                                    @endif
                                    <a href="{{ route('doctor.blog.index') }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
$(document).ready(function() {
    // Initialize CKEditor
    ClassicEditor
        .create(document.querySelector('#content'), {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', '|',
                'bulletedList', 'numberedList', '|',
                'blockQuote', 'insertTable', '|',
                'undo', 'redo'
            ]
        })
        .catch(error => {
            console.error(error);
        });

    // Image preview
    $('#featured_image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-img').attr('src', e.target.result);
                $('#image-preview').removeClass('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            $('#image-preview').addClass('d-none');
        }
    });

    // Character counter for short description
    $('#short_description').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;

        let counterText = `${currentLength}/${maxLength} characters`;
        if (remaining < 50) {
            counterText = `<span class="text-warning">${counterText}</span>`;
        }
        if (remaining < 0) {
            counterText = `<span class="text-danger">${counterText}</span>`;
        }

        $(this).siblings('.form-text').html(counterText);
    });
});
</script>
@endpush
