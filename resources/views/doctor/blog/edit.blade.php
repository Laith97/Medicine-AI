@extends('master')

@section('title', 'Edit Blog Post')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#f59e0b 0%,#d97706 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#fffbeb;border-bottom:1px solid #fde68a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#f59e0b!important;color:#fff!important;border:1px solid #f59e0b!important}
.section-head-modern h5{color:#92400e!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#b45309!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.note-label{font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;margin-bottom:0.35rem;text-transform:uppercase}
.form-control,.form-select{border:1px solid #e2e8f0;border-radius:10px;padding:0.6rem 0.9rem;font-size:0.92rem;background:#f8fafc}
.form-control:focus,.form-select:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.12);background:#fff}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-pen me-2"></i>Edit Blog Post</h2>
                    <p>Update and manage your blog post</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.blog.show', $post) }}" class="btn" style="background:#fff;color:#92400e;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:700;font-size:0.83rem"><i class="fas fa-eye me-1"></i>Preview</a>
                    <a href="{{ route('doctor.blog.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
        </div>

        @if($errors->any())<div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px"><h6 style="font-weight:700"><i class="fas fa-exclamation-triangle me-2"></i>Please fix errors:</h6><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

        <form action="{{ route('doctor.blog.update', $post) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="table-card">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-file-lines"></i></div><div><h5>Post Content</h5><p>Title · description · body</p></div></div></div>
                        <div class="mb-3">
                            <label for="title" class="form-label note-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $post->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="short_description" class="form-label note-label">Short Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" rows="3" maxlength="500" required>{{ old('short_description', $post->short_description) }}</textarea>
                            <div class="form-text" style="font-size:0.76rem">Preview in blog listing (max 500)</div>
                            @error('short_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-0">
                            <label for="content" class="form-label note-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="12">{{ old('content', $post->content) }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="table-card" style="border-color:#fde68a">
                        <div class="section-head-modern" style="background:#fffbeb;border-color:#fde68a"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fff!important;color:#d97706!important;border-color:#fde68a!important"><i class="fas fa-magnifying-glass"></i></div><div><h5 style="color:#92400e!important">SEO Settings</h5><p style="color:#b45309!important">Title · description · keywords</p></div></div></div>
                        <div class="mb-3"><label for="seo_title" class="form-label note-label">SEO Title</label><input type="text" class="form-control @error('seo_title') is-invalid @enderror" id="seo_title" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}" maxlength="255" placeholder="Leave empty to use post title"></div>
                        <div class="mb-3"><label for="seo_description" class="form-label note-label">SEO Description</label><textarea class="form-control @error('seo_description') is-invalid @enderror" id="seo_description" name="seo_description" rows="3" maxlength="500">{{ old('seo_description', $post->seo_description) }}</textarea></div>
                        <div class="mb-0"><label for="seo_keywords" class="form-label note-label">SEO Keywords</label><input type="text" class="form-control @error('seo_keywords') is-invalid @enderror" id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $post->seo_meta['keywords'] ?? '') }}" maxlength="255" placeholder="health, tips"></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="table-card">
                        <div class="section-head-modern" style="background:#f8fafc;border-color:#e2e8f0"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#1e293b!important;color:#fff!important"><i class="fas fa-paper-plane"></i></div><div><h5 style="color:#0f172a!important">Publish</h5><p style="color:#475569!important">Visibility</p></div></div></div>
                        <div class="form-check" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.9rem 0.9rem 0.9rem 2.2rem">
                            <input type="hidden" name="is_published" value="0">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published" style="font-weight:700;color:#0f172a">Published</label>
                            <div style="font-size:0.76rem;color:#64748b">@if($post->is_published && $post->published_at) Published on {{ $post->published_at->format('M j, Y g:i A') }} @else Currently draft @endif</div>
                        </div>
                    </div>

                    <div class="table-card">
                        <div class="section-head-modern" style="background:#f8fafc;border-color:#e2e8f0"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-image"></i></div><div><h5 style="color:#0f172a!important">Featured Image</h5><p style="color:#475569!important">800×400 · max 2MB</p></div></div></div>
                        @if($post->featured_image)
                            <div class="mb-3"><img src="{{ Storage::url($post->featured_image) }}" alt="Current" class="img-fluid" style="width:100%;height:180px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="remove_image" name="remove_image"><label class="form-check-label" for="remove_image" style="font-size:0.84rem;font-weight:600">Remove current image</label></div></div>
                        @endif
                        <input type="file" class="form-control @error('featured_image') is-invalid @enderror" id="featured_image" name="featured_image" accept="image/*" style="border-radius:10px">
                        @error('featured_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="image-preview" class="d-none mt-3"><img id="preview-img" src="" alt="Preview" class="img-fluid" style="width:100%;max-height:220px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7"></div>
                    </div>

                    <div class="table-card">
                        <div class="section-head-modern" style="background:#f8fafc;border-color:#e2e8f0"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#64748b!important;border-color:#e2e8f0!important"><i class="fas fa-chart-simple"></i></div><div><h5 style="color:#0f172a!important">Stats</h5><p style="color:#475569!important">Views · dates</p></div></div></div>
                        <div class="row text-center g-3">
                            <div class="col-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px"><div style="font-weight:800;color:#2563eb;font-size:1.3rem">{{ $post->views_count }}</div><small style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Views</small></div></div>
                            <div class="col-6"><div class="p-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px"><div style="font-weight:800;color:#0ea5e9;font-size:1.3rem">{{ $post->reading_time }}</div><small style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Read Time</small></div></div>
                        </div>
                        <hr style="border-color:#eef2f7"><small style="color:#64748b;font-size:0.78rem"><strong>Created:</strong> {{ $post->created_at->format('M j, Y g:i A') }}<br><strong>Updated:</strong> {{ $post->updated_at->format('M j, Y g:i A') }}</small>
                    </div>

                    <div class="table-card">
                        <div class="section-head-modern" style="background:#f8fafc;border-color:#e2e8f0"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5 style="color:#0f172a!important">Actions</h5><p style="color:#475569!important">Save changes</p></div></div></div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="doctor-btn doctor-btn-primary" style="justify-content:center"><i class="fas fa-save me-2"></i>Update Post</button>
                            @if($post->is_published && auth()->user()->getEffectiveDoctor()?->landingPage)<a href="{{ route('doctor.blog.post', [auth()->user()->getEffectiveDoctor()->landingPage->username, $post->slug]) }}" class="doctor-btn doctor-btn-outline" style="justify-content:center" target="_blank"><i class="fas fa-external-link-alt me-2"></i>View Live</a>@endif
                            <a href="{{ route('doctor.blog.index') }}" class="doctor-btn doctor-btn-outline" style="justify-content:center">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
$(function(){
    let editorInstance;
    ClassicEditor.create(document.querySelector('#content'), {toolbar:['heading','|','bold','italic','link','|','bulletedList','numberedList','|','blockQuote','insertTable','|','undo','redo']}).then(e=>editorInstance=e).catch(console.error);
    $('form').on('submit', function(e){ if(editorInstance && !editorInstance.getData().trim()){ e.preventDefault(); alert('Please enter content'); return false; }});
    $('#featured_image').change(function(){ const f=this.files[0]; if(f){ const r=new FileReader(); r.onload=e=>{$('#preview-img').attr('src',e.target.result); $('#image-preview').removeClass('d-none');}; r.readAsDataURL(f);} else $('#image-preview').addClass('d-none');});
    $('#short_description').on('input', function(){ const m=500, cur=$(this).val().length, rem=m-cur; let t=`${cur}/${m} characters`; if(rem<50) t=`<span class="text-warning">${t}</span>`; if(rem<0) t=`<span class="text-danger">${t}</span>`; $(this).siblings('.form-text').html(t);});
});
</script>
@endpush
