@extends('master')

@section('title', $post->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:1.9rem!important;margin-bottom:0.4rem!important;line-height:1.2}
.dashboard-header p{color:rgba(255,255,255,0.85)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.blog-content{font-size:1rem;line-height:1.75;color:#1e293b}
.blog-content p{margin-bottom:1.2rem}
.blog-content h1,.blog-content h2,.blog-content h3{margin-top:1.6rem;margin-bottom:0.8rem;font-weight:800;color:#0f172a}
.blog-content ul,.blog-content ol{margin-bottom:1.2rem;padding-left:1.5rem}
.blog-content blockquote{border-left:3px solid #3b82f6;background:#eff6ff;padding:0.8rem 1rem;border-radius:0 8px 8px 0;margin:1.2rem 0;color:#1e40af}
.badge-soft{padding:0.35rem 0.6rem;border-radius:99px;font-size:0.70rem;font-weight:700;border:1px solid transparent}
.status-badge{padding:0.38rem 0.75rem;border-radius:99px;font-size:0.72rem;font-weight:700;border:1px solid transparent}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <!-- Header like appointments/show -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div style="max-width:680px">
                    <h2><i class="fas fa-blog me-2"></i>{{ Str::limit($post->title, 70) }}</h2>
                    <p class="d-flex align-items-center gap-2 flex-wrap">
                        @if($post->is_published)<span class="status-badge" style="background:#d1fae5;color:#065f46;border-color:#a7f3d0">Published</span> @else <span class="status-badge" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0">Draft</span> @endif
                        @if($post->published_at)<span><i class="far fa-calendar me-1"></i>{{ $post->published_at->format('M j, Y g:i A') }}</span>@else <span>Created {{ $post->created_at->format('M j, Y') }}</span>@endif
                        <span><i class="fas fa-eye me-1"></i>{{ $post->views_count }} views</span>
                        <span><i class="far fa-clock me-1"></i>{{ $post->reading_time }}</span>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('doctor.blog.edit', $post) }}" class="btn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:700;font-size:0.83rem"><i class="fas fa-pen me-2"></i>Edit</a>
                    <a href="{{ route('doctor.blog.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main -->
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-file-lines"></i></div><div><h5>{{ $post->title }}</h5><p>{{ $post->is_published ? 'Published post' : 'Draft' }} · {{ $post->reading_time }}</p></div></div>
                    <span class="badge-soft" style="background:{{ $post->is_published ? '#d1fae5;color:#065f46;border-color:#a7f3d0' : '#f1f5f9;color:#475569;border-color:#e2e8f0' }}">{{ $post->is_published ? 'Published' : 'Draft' }}</span></div>

                    @if($post->featured_image)
                        <div class="mb-4"><img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}" class="img-fluid" style="width:100%;max-height:420px;object-fit:cover;border-radius:10px;border:1px solid #eef2f7"></div>
                    @endif
                    <div class="mb-4 p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px">
                        <small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">SUMMARY</small>
                        <p class="mb-0 mt-1" style="font-size:0.95rem;color:#334155;line-height:1.6">{{ $post->short_description }}</p>
                    </div>
                    <div class="blog-content">
                        {!! nl2br(e($post->content)) !!}
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="table-card">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Actions</h5><p>Manage post</p></div></div></div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('doctor.blog.edit', $post) }}" class="doctor-btn doctor-btn-primary" style="justify-content:center"><i class="fas fa-pen me-2"></i>Edit Post</a>
                        <button type="button" class="doctor-btn {{ $post->is_published ? 'doctor-btn-outline' : 'doctor-btn-success' }} toggle-publish-btn" style="justify-content:center" data-post-id="{{ $post->id }}" data-current-status="{{ $post->is_published ? 'published' : 'draft' }}"><i class="fas fa-{{ $post->is_published ? 'eye-slash' : 'globe' }} me-2"></i>{{ $post->is_published ? 'Unpublish' : 'Publish' }}</button>
                        @if($post->is_published && auth()->user()->getEffectiveDoctor()?->landingPage)
                            <a href="{{ route('doctor.blog.post', [auth()->user()->getEffectiveDoctor()->landingPage->username, $post->slug]) }}" class="doctor-btn doctor-btn-outline" style="justify-content:center" target="_blank"><i class="fas fa-external-link-alt me-2"></i>View Live Post</a>
                        @endif
                        <form action="{{ route('doctor.blog.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this blog post?')">@csrf @method('DELETE')<button type="submit" class="doctor-btn doctor-btn-danger" style="width:100%;justify-content:center"><i class="fas fa-trash me-2"></i>Delete Post</button></form>
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-chart-simple"></i></div><div><h5>Statistics</h5><p>Views · reading time</p></div></div></div>
                    <div class="row text-center g-3">
                        <div class="col-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px"><div style="font-weight:800;color:#2563eb;font-size:1.4rem">{{ $post->views_count }}</div><small style="color:#64748b;font-weight:600;font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase">Total Views</small></div></div>
                        <div class="col-6"><div class="p-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px"><div style="font-weight:800;color:#0ea5e9;font-size:1.4rem">{{ $post->reading_time }}</div><small style="color:#64748b;font-weight:600;font-size:0.72rem;letter-spacing:0.04em;text-transform:uppercase">Reading Time</small></div></div>
                    </div>
                </div>

                @if($post->seo_meta)
                <div class="table-card">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#f0fdf4!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-magnifying-glass"></i></div><div><h5>SEO</h5><p>Meta information</p></div></div></div>
                    @if(isset($post->seo_meta['title']))<div class="mb-3"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">SEO TITLE</small><p class="mb-0" style="font-size:0.88rem;color:#334155">{{ $post->seo_meta['title'] }}</p></div>@endif
                    @if(isset($post->seo_meta['description']))<div class="mb-3"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">DESCRIPTION</small><p class="mb-0" style="font-size:0.88rem;color:#334155">{{ $post->seo_meta['description'] }}</p></div>@endif
                    @if(isset($post->seo_meta['keywords']))<div class="mb-0"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">KEYWORDS</small><p class="mb-0" style="font-size:0.88rem;color:#334155">{{ $post->seo_meta['keywords'] }}</p></div>@endif
                </div>
                @endif

                <div class="table-card">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#fff!important;color:#64748b!important;border:1px solid #e2e8f0!important"><i class="fas fa-circle-info"></i></div><div><h5>Details</h5><p>Slug · dates</p></div></div></div>
                    <div style="font-size:0.84rem">
                        <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span style="color:#64748b;font-weight:600">Slug</span><code style="font-size:0.76rem;background:#f8fafc;padding:0.2rem 0.4rem;border-radius:6px">{{ $post->slug }}</code></div>
                        <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span style="color:#64748b;font-weight:600">Created</span><small style="color:#334155">{{ $post->created_at->format('M j, Y g:i A') }}</small></div>
                        <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:#f1f5f9!important"><span style="color:#64748b;font-weight:600">Updated</span><small style="color:#334155">{{ $post->updated_at->format('M j, Y g:i A') }}</small></div>
                        @if($post->published_at)<div class="d-flex justify-content-between py-2"><span style="color:#64748b;font-weight:600">Published</span><small style="color:#334155">{{ $post->published_at->format('M j, Y g:i A') }}</small></div>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
  $('.toggle-publish-btn').click(function(){
    const btn=$(this), postId=btn.data('post-id');
    $.ajax({url:`/doctor/blog/${postId}/toggle-publish`,method:'POST',data:{_token:'{{ csrf_token() }}'},beforeSend:()=>btn.prop('disabled',true),success:function(res){
      if(res.success){
        if(res.is_published){ btn.html('<i class="fas fa-eye-slash me-2"></i>Unpublish').removeClass('doctor-btn-success').addClass('doctor-btn-outline').data('current-status','published'); }
        else { btn.html('<i class="fas fa-globe me-2"></i>Publish').removeClass('doctor-btn-outline').addClass('doctor-btn-success').data('current-status','draft'); }
        location.reload();
      }
    },error:()=>alert('Error updating status'),complete:()=>btn.prop('disabled',false)});
  });
});
</script>
@endpush
