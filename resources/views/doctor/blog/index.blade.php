@extends('master')

@section('title', 'Blog Management')

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '📝';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-header">
    <h2>Blog</h2>
    <p>Manage your blog posts</p>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Blog Management</h1>
                <a href="{{ route('doctor.blog.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Blog Post
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    @if($posts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Published Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($posts as $post)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($post->featured_image)
                                                        <img src="{{ Storage::url($post->featured_image) }}"
                                                             alt="{{ $post->title }}"
                                                             class="rounded me-3"
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ $post->title }}</h6>
                                                        <small class="text-muted">{{ Str::limit($post->short_description, 60) }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $post->is_published ? 'success' : 'secondary' }}">
                                                    {{ $post->is_published ? 'Published' : 'Draft' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $post->views_count }} views</span>
                                            </td>
                                            <td>
                                                @if($post->published_at)
                                                    {{ $post->published_at->format('M j, Y') }}
                                                @else
                                                    <span class="text-muted">Not published</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('doctor.blog.show', $post) }}"
                                                       class="btn btn-sm btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('doctor.blog.edit', $post) }}"
                                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-{{ $post->is_published ? 'warning' : 'success' }} toggle-publish-btn"
                                                            data-post-id="{{ $post->id }}"
                                                            data-current-status="{{ $post->is_published ? 'published' : 'draft' }}"
                                                            title="{{ $post->is_published ? 'Unpublish' : 'Publish' }}">
                                                        <i class="fas fa-{{ $post->is_published ? 'eye-slash' : 'globe' }}"></i>
                                                    </button>
                                                    @if($post->is_published)
                                                        <a href="{{ route('doctor.blog.post', [auth()->user()->doctor->landingPage->username ?? 'preview', $post->slug]) }}"
                                                           class="btn btn-sm btn-outline-info"
                                                           title="View on Landing Page"
                                                           target="_blank">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    @endif
                                                    <form action="{{ route('doctor.blog.destroy', $post) }}"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this blog post?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-blog fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No blog posts yet</h5>
                            <p class="text-muted">Create your first blog post to share health tips with your patients.</p>
                            <a href="{{ route('doctor.blog.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Post
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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
                        btn.removeClass('btn-outline-success')
                           .addClass('btn-outline-warning')
                           .attr('title', 'Unpublish')
                           .data('current-status', 'published');
                        btn.find('i').removeClass('fa-globe').addClass('fa-eye-slash');

                        // Update status badge
                        btn.closest('tr').find('.badge').removeClass('bg-secondary').addClass('bg-success').text('Published');
                    } else {
                        btn.removeClass('btn-outline-warning')
                           .addClass('btn-outline-success')
                           .attr('title', 'Publish')
                           .data('current-status', 'draft');
                        btn.find('i').removeClass('fa-eye-slash').addClass('fa-globe');

                        // Update status badge
                        btn.closest('tr').find('.badge').removeClass('bg-success').addClass('bg-secondary').text('Draft');
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
