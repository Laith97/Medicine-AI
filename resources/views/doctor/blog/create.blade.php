@extends('master')

@section('title', 'Create Blog Post')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-plus me-2"></i>Create Blog Post</h2>
                        <p class="text-muted mb-0">Create and publish a new blog post for your landing page</p>
                    </div>
                    <a href="{{ route('doctor.blog.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Blog
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

@if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('doctor.blog.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
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
                                           value="{{ old('title') }}"
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
                                              required>{{ old('short_description') }}</textarea>
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
                                              rows="15">{{ old('content') }}</textarea>
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
                                           value="{{ old('seo_title') }}"
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
                                              maxlength="500">{{ old('seo_description') }}</textarea>
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
                                           value="{{ old('seo_keywords') }}"
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
                                    <input type="hidden" name="is_published" value="0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_published"
                                           name="is_published"
                                           value="1"
                                           {{ old('is_published') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_published">
                                        Publish immediately
                                    </label>
                                </div>
                                <div class="form-text">If unchecked, the post will be saved as a draft</div>
                            </div>
                        </div>

                        <!-- Featured Image -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Featured Image</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="file"
                                           class="form-control @error('featured_image') is-invalid @enderror"
                                           id="featured_image"
                                           name="featured_image"
                                           accept="image/*">
                                    <div class="form-text">Recommended size: 800x400px (max 2MB)</div>
                                    @error('featured_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div id="image-preview" class="d-none">
                                    <img id="preview-img" src="" alt="Preview" class="img-fluid rounded">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Post
                                    </button>
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
    </div></div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
$(document).ready(function() {
    let editorInstance;
    
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
        .then(editor => {
            editorInstance = editor;
        })
        .catch(error => {
            console.error(error);
        });

    // Form validation
    $('form').on('submit', function(e) {
        if (editorInstance) {
            const content = editorInstance.getData().trim();
            if (!content) {
                e.preventDefault();
                alert('Please enter content for your blog post.');
                return false;
            }
        }
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
