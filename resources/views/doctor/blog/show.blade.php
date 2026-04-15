@extends('master')

@section('title', $post->title)

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
                        <h2><i class="fas fa-blog me-2"></i>{{ $post->title }}</h2>
                        <p class="text-muted mb-0">View and manage your blog post</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('doctor.blog.edit', $post) }}" class="btn btn-primary me-2">
                            <i class="fas fa-edit"></i> Edit Post
                        </a>
                        <a href="{{ route('doctor.blog.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Blog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<div class="container-fluid">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <!-- Post Meta -->
                            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                                <div>
                                    <span class="badge bg-{{ $post->is_published ? 'success' : 'secondary' }} me-2">
                                        {{ $post->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                    @if($post->published_at)
                                        <small class="text-muted">
                                            Published on {{ $post->published_at->format('F j, Y \a\t g:i A') }}
                                        </small>
                                    @else
                                        <small class="text-muted">
                                            Created on {{ $post->created_at->format('F j, Y \a\t g:i A') }}
                                        </small>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">
                                        <i class="fas fa-eye me-1"></i> {{ $post->views_count }} views
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i> {{ $post->reading_time }}
                                    </div>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            @if($post->featured_image)
                                <div class="mb-4">
                                    <img src="{{ Storage::url($post->featured_image) }}" 
                                         alt="{{ $post->title }}" 
                                         class="img-fluid rounded">
                                </div>
                            @endif

                            <!-- Short Description -->
                            <div class="mb-4">
                                <h5 class="text-muted">Summary</h5>
                                <p class="lead">{{ $post->short_description }}</p>
                            </div>

                            <!-- Content -->
                            <div class="blog-content">
                                {!! nl2br(e($post->content)) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Post Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Post Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('doctor.blog.edit', $post) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Post
                                </a>
                                
                                <button type="button" 
                                        class="btn btn-{{ $post->is_published ? 'warning' : 'success' }} toggle-publish-btn"
                                        data-post-id="{{ $post->id }}"
                                        data-current-status="{{ $post->is_published ? 'published' : 'draft' }}">
                                    <i class="fas fa-{{ $post->is_published ? 'eye-slash' : 'globe' }}"></i>
                                    {{ $post->is_published ? 'Unpublish' : 'Publish' }}
                                </button>

                                @if($post->is_published)
                                    <a href="{{ route('doctor.blog.post', [auth()->user()->doctor->landingPage->username ?? 'preview', $post->slug]) }}" 
                                       class="btn btn-info" 
                                       target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Live Post
                                    </a>
                                @endif

                                <form action="{{ route('doctor.blog.destroy', $post) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Are you sure you want to delete this blog post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash"></i> Delete Post
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Post Statistics -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-primary">{{ $post->views_count }}</h4>
                                    <small class="text-muted">Total Views</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info">{{ $post->reading_time }}</h4>
                                    <small class="text-muted">Reading Time</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Information -->
                    @if($post->seo_meta)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">SEO Information</h5>
                            </div>
                            <div class="card-body">
                                @if(isset($post->seo_meta['title']))
                                    <div class="mb-3">
                                        <strong>SEO Title:</strong>
                                        <p class="text-muted small mb-0">{{ $post->seo_meta['title'] }}</p>
                                    </div>
                                @endif
                                
                                @if(isset($post->seo_meta['description']))
                                    <div class="mb-3">
                                        <strong>SEO Description:</strong>
                                        <p class="text-muted small mb-0">{{ $post->seo_meta['description'] }}</p>
                                    </div>
                                @endif
                                
                                @if(isset($post->seo_meta['keywords']))
                                    <div class="mb-3">
                                        <strong>Keywords:</strong>
                                        <p class="text-muted small mb-0">{{ $post->seo_meta['keywords'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Post Details -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Post Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Slug:</strong>
                                <code class="small">{{ $post->slug }}</code>
                            </div>
                            <div class="mb-2">
                                <strong>Created:</strong>
                                <small class="text-muted">{{ $post->created_at->format('M j, Y g:i A') }}</small>
                            </div>
                            <div class="mb-2">
                                <strong>Last Updated:</strong>
                                <small class="text-muted">{{ $post->updated_at->format('M j, Y g:i A') }}</small>
                            </div>
                            @if($post->published_at)
                                <div class="mb-2">
                                    <strong>Published:</strong>
                                    <small class="text-muted">{{ $post->published_at->format('M j, Y g:i A') }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div></div>
@endsection

@push('styles')
<style>
.blog-content {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #333;
}

.blog-content p {
    margin-bottom: 1.5rem;
}

.blog-content h1,
.blog-content h2,
.blog-content h3,
.blog-content h4,
.blog-content h5,
.blog-content h6 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.blog-content ul,
.blog-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.blog-content blockquote {
    border-left: 4px solid #DE6262;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #666;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle publish status
    $('.toggle-publish-btn').click(function() {
        const btn = $(this);
        const postId = btn.data('post-id');
        const currentStatus = btn.data('current-status');

        $.ajax({
            url: `/doctor/blog/${postId}/toggle-publish`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                btn.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Update button appearance
                    if (response.is_published) {
                        btn.removeClass('btn-success')
                           .addClass('btn-warning')
                           .html('<i class="fas fa-eye-slash"></i> Unpublish')
                           .data('current-status', 'published');

                        // Update status badge
                        $('.badge').removeClass('bg-secondary').addClass('bg-success').text('Published');
                    } else {
                        btn.removeClass('btn-warning')
                           .addClass('btn-success')
                           .html('<i class="fas fa-globe"></i> Publish')
                           .data('current-status', 'draft');

                        // Update status badge
                        $('.badge').removeClass('bg-success').addClass('bg-secondary').text('Draft');
                    }

                    // Show success message
                    showAlert('success', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while updating the post status.');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    function showAlert(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alert);

        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    }
});
</script>
@endpush