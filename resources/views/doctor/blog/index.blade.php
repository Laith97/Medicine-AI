@extends('master')

@section('title', 'Blog Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.patient-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.85rem;flex-shrink:0}
.patient-avatar-default{background:linear-gradient(135deg,#6c757d 0%,#495057 100%)}
</style>
@endpush

@section('content')
@php
    $total = $posts->total();
    $publishedCount = $posts->getCollection()->where('is_published', true)->count();
    $draftCount = $posts->getCollection()->where('is_published', false)->count();
    $totalViews = $posts->getCollection()->sum('views_count');
@endphp
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-blog me-2"></i>Blog Management</h2>
                    <p>Manage your blog posts · share health insights</p>
                </div>
                <a href="{{ route('doctor.blog.create') }}" class="doctor-btn doctor-btn-primary doctor-btn-sm"><i class="fas fa-plus me-1"></i> New Blog Post</a>
            </div>
        </div>

        <!-- Stats like appointments/index -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);"><i class="fas fa-blog"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $total }}</p><p class="stats-label">Total Posts</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-globe"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $publishedCount }}</p><p class="stats-label">Published</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);"><i class="fas fa-file-lines"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $draftCount }}</p><p class="stats-label">Drafts</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);"><i class="fas fa-eye"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $totalViews }}</p><p class="stats-label">Total Views</p></div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="card border-0 shadow-sm cases-panel mb-3">
            <div class="cases-toolbar">
                <div class="cases-toolbar__title">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-blog me-2 text-primary"></i>Posts ({{ $total }})</h6>
                    <span class="cases-toolbar__meta d-none d-md-inline">— Instant search</span>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm cases-search" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#ffffff">
                            <span class="input-group-text" style="background:#ffffff;border:none;color:#94a3b8"><i class="fas fa-search"></i></span>
                            <input type="text" id="blogSearch" class="form-control" placeholder="Search title, description..." style="border:none;box-shadow:none">
                            <button class="btn btn-outline-secondary" type="button" id="clearBlogSearch" style="border:none;background:#ffffff;color:#94a3b8" title="Clear"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <select id="filterStatus" class="form-select form-select-sm cases-sort">
                            <option value="">All Statuses</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <select id="sortPosts" class="form-select form-select-sm cases-sort">
                            <option value="recent">Most recent</option>
                            <option value="views_desc">Most viewed</option>
                            <option value="title_asc">Title A → Z</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        @if($posts->count() > 0)
            <div class="card border-0 shadow-sm cases-panel" style="overflow:hidden;border:1px solid #eef0f3;border-radius:12px">
                <div class="doctor-table-container" style="background:#fff">
                    <div class="table-responsive">
                        <table class="doctor-table table-hover mb-0" style="width:100%">
                            <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);">
                                <tr>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Title</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Status</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:center">Views</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="far fa-calendar me-1 opacity-60"></i>Published</th>
                                    <th class="text-end" style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($posts as $post)
                                    <tr class="post-row" data-title="{{ strtolower($post->title) }}" data-status="{{ $post->is_published ? 'published' : 'draft' }}" data-views="{{ $post->views_count }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($post->featured_image)
                                                    <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}" class="rounded me-3" style="width:42px;height:42px;object-fit:cover;border:1px solid #eef2f7">
                                                @else
                                                    <div class="patient-avatar patient-avatar-default me-3" style="width:42px;height:42px;font-size:0.9rem;background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)"><i class="fas fa-blog"></i></div>
                                                @endif
                                                <div class="min-w-0" style="max-width:280px">
                                                    <div class="fw-semibold text-dark text-truncate" style="font-size:0.92rem">{{ $post->title }}</div>
                                                    <small class="text-muted text-truncate d-block" style="max-width:280px">{{ Str::limit($post->short_description, 60) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($post->is_published)<span class="doctor-badge doctor-badge-success">Published</span>
                                            @else<span class="doctor-badge doctor-badge-secondary">Draft</span>@endif
                                        </td>
                                        <td class="text-center"><span class="badge" style="background:#eff6ff;color:#2563eb;border:1px solid #dbeafe;border-radius:99px;font-size:0.72rem;padding:0.3rem 0.6rem">{{ $post->views_count }}</span></td>
                                        <td><small class="text-muted">@if($post->published_at) {{ $post->published_at->format('M j, Y') }} @else <span style="color:#94a3b8">Not published</span> @endif</small></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('doctor.blog.show', $post) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                                <a href="{{ route('doctor.blog.edit', $post) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
                                                <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm toggle-publish-btn" data-post-id="{{ $post->id }}" data-current-status="{{ $post->is_published ? 'published' : 'draft' }}" title="{{ $post->is_published ? 'Unpublish' : 'Publish' }}" style="{{ $post->is_published ? 'color:#b45309;border-color:#fde68a;background:#fffbeb' : 'color:#059669;border-color:#a7f3d0;background:#ecfdf5' }}"><i class="fas fa-{{ $post->is_published ? 'eye-slash' : 'globe' }}"></i></button>
                                                @if($post->is_published && auth()->user()->getEffectiveDoctor()?->landingPage)
                                                    <a href="{{ route('doctor.blog.post', [auth()->user()->getEffectiveDoctor()->landingPage->username, $post->slug]) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View Live" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                                                @endif
                                                <form action="{{ route('doctor.blog.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this blog post?')">@csrf @method('DELETE')<button type="submit" class="doctor-btn doctor-btn-danger doctor-btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center p-3" style="background:#f8fafc;border-top:1px solid #eef0f3">{{ $posts->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm cases-panel">
                <div class="doctor-empty-state">
                    <i class="fas fa-blog"></i>
                    <h5>No blog posts yet</h5>
                    <p>Create your first blog post to share health tips with your patients.</p>
                    <a href="{{ route('doctor.blog.create') }}" class="doctor-btn doctor-btn-primary"><i class="fas fa-plus me-1"></i> Create First Post</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function(){
    $('.toggle-publish-btn').click(function(){
        const btn=$(this); const postId=btn.data('post-id');
        $.ajax({url:`/doctor/blog/${postId}/toggle-publish`,method:'POST',data:{_token:'{{ csrf_token() }}'},beforeSend:()=>btn.prop('disabled',true),success:function(res){
            if(res.success){
                const row=btn.closest('tr');
                if(res.is_published){
                    btn.data('current-status','published').attr('title','Unpublish').css({color:'#b45309',borderColor:'#fde68a',background:'#fffbeb'}).find('i').removeClass('fa-globe').addClass('fa-eye-slash');
                    row.find('td:eq(1)').html('<span class="doctor-badge doctor-badge-success">Published</span>'); row.attr('data-status','published');
                } else {
                    btn.data('current-status','draft').attr('title','Publish').css({color:'#059669',borderColor:'#a7f3d0',background:'#ecfdf5'}).find('i').removeClass('fa-eye-slash').addClass('fa-globe');
                    row.find('td:eq(1)').html('<span class="doctor-badge doctor-badge-secondary">Draft</span>'); row.attr('data-status','draft');
                }
                const alert=`<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46">${res.message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
                $('.container.py-4').prepend(alert); setTimeout(()=>$('.alert').alert('close'),2500);
            }
        },error:()=>{ const a=`<div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px">An error occurred<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`; $('.container.py-4').prepend(a); },complete:()=>btn.prop('disabled',false)});
    });
    function filterPosts(){
        const term=($('#blogSearch').val()||'').toLowerCase().trim();
        const status=$('#filterStatus').val()||'';
        const sort=$('#sortPosts').val()||'recent';
        let rows=$('tbody tr.post-row').get();
        rows.forEach(r=>{
            const $r=$(r); const title=$r.data('title')||''; const st=$r.data('status')||'';
            const matchesTerm=!term || title.includes(term) || $r.text().toLowerCase().includes(term);
            const matchesStatus=!status || st===status;
            $r.toggle(matchesTerm && matchesStatus);
        });
        if(sort==='title_asc'){ rows.sort((a,b)=> $(a).data('title').localeCompare($(b).data('title')) ); $('tbody').append(rows); }
        else if(sort==='views_desc'){ rows.sort((a,b)=> $(b).data('views') - $(a).data('views')); $('tbody').append(rows); }
    }
    $('#blogSearch').on('input', filterPosts);
    $('#clearBlogSearch').on('click', function(){ $('#blogSearch').val(''); filterPosts(); });
    $('#filterStatus,#sortPosts').on('change', filterPosts);
});
</script>
@endpush
