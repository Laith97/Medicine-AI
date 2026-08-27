@extends('layouts.admin')

@section('title', 'Exercise Library')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-dumbbell" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Exercise Library</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Manage exercises for HEP programs · {{ $exercises->total() }} total · {{ count($categories) }} categories</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.exercises.import') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-upload me-1"></i> Import</a>
            <a href="{{ route('admin.exercises.export', request()->query()) }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-download me-1"></i> Export</a>
            <a href="{{ route('admin.exercises.create') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> Add Exercise</a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-dumbbell"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $exercises->total() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Total Exercises</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-tags"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ count($categories) }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Categories</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-video"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $exercises->getCollection()->where('video_url', '!=', null)->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">With Videos</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-image"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $exercises->getCollection()->where('image_url', '!=', null)->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">With Images</div></div></div></div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-body p-3" style="background:#fff">
            <form method="GET" action="{{ route('admin.exercises.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Search</label>
                        <div class="position-relative">
                            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.78rem"></i>
                            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name, description..." style="border-radius:10px;padding-left:34px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Category</label>
                        <select class="form-select" name="category" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Difficulty</label>
                        <select class="form-select" name="difficulty" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                            <option value="">All Levels</option>
                            @foreach($difficulties as $difficulty)
                                <option value="{{ $difficulty }}" {{ request('difficulty') === $difficulty ? 'selected' : '' }}>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Equipment</label>
                        <select class="form-select" name="equipment" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                            <option value="">All Equipment</option>
                            @foreach($equipmentOptions as $equipment)
                                <option value="{{ $equipment }}" {{ request('equipment') === $equipment ? 'selected' : '' }}>{{ $equipment }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn flex-grow-1 text-white" style="border-radius:10px;background:#0f172a;border:none;font-weight:700;height:38px"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="{{ route('admin.exercises.index') }}" class="btn btn-light border" style="border-radius:10px;font-weight:600;height:38px">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-responsive" style="border-top:1px solid #f1f5f9">
            <table class="table mb-0" style="font-size:.84rem">
                <thead>
                    <tr style="background:#f8fafc">
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Exercise</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Category</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Difficulty</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Equipment</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Media</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Usage</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Quality</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exercises as $exercise)
                        <tr>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                <div class="d-flex align-items-center gap-3">
                                    @if($exercise->image_url)
                                        <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}" class="rounded" style="width:40px;height:40px;object-fit:cover;border:1px solid #e2e8f0">
                                    @else
                                        <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-dumbbell" style="font-size:.85rem"></i></div>
                                    @endif
                                    <div class="min-w-0">
                                        <div style="font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px">{{ $exercise->name }}</div>
                                        <small style="font-size:.76rem;color:#64748b">{{ Str::limit($exercise->description, 48) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe">{{ ucfirst($exercise->category) }}</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                @php $diffColor = $exercise->difficulty_level === 'beginner' ? ['bg'=>'#ecfdf5','border'=>'#a7f3d0','text'=>'#065f46'] : ($exercise->difficulty_level === 'intermediate' ? ['bg'=>'#fffbeb','border'=>'#fde68a','text'=>'#92400e'] : ['bg'=>'#fef2f2','border'=>'#fecaca','text'=>'#991b1b']); @endphp
                                <span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:{{ $diffColor['bg'] }};color:{{ $diffColor['text'] }};border:1px solid {{ $diffColor['border'] }}">{{ ucfirst($exercise->difficulty_level) }}</span>
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                @if($exercise->equipment_required && count($exercise->equipment_required) > 0)
                                    <span style="font-size:.82rem;color:#334155">{{ implode(', ', $exercise->equipment_required) }}</span>
                                @else
                                    <span style="font-size:.78rem;color:#94a3b8">None</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                <div class="d-flex gap-1 align-items-center">
                                    @if($exercise->video_url)<span style="width:26px;height:26px;border-radius:8px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:inline-flex;align-items:center;justify-content:center" title="Has video"><i class="fas fa-video" style="font-size:.7rem"></i></span>@endif
                                    @if($exercise->image_url)<span style="width:26px;height:26px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:inline-flex;align-items:center;justify-content:center" title="Has image"><i class="fas fa-image" style="font-size:.7rem"></i></span>@endif
                                    @if(!$exercise->video_url && !$exercise->image_url)<span style="font-size:.78rem;color:#94a3b8">—</span>@endif
                                </div>
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.78rem;color:#64748b">Used in <strong style="color:#0f172a">{{ $exercise->hepExercises()->count() }}</strong> programs</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                <div class="d-flex align-items-center gap-2" style="min-width:110px">
                                    <small style="font-weight:700;color:#0f172a;font-size:.78rem">{{ $exercise->getQualityScore() }}<span style="color:#94a3b8;font-weight:600">/100</span></small>
                                    <div class="progress flex-grow-1" style="height:6px;background:#f1f5f9;border-radius:20px"><div class="progress-bar bg-{{ $exercise->getQualityStatusColor() }}" style="width: {{ $exercise->getQualityScore() }}%;border-radius:20px"></div></div>
                                </div>
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.exercises.show', $exercise) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px" title="View"><i class="fas fa-eye" style="font-size:.76rem"></i></a>
                                    <a href="{{ route('admin.exercises.edit', $exercise) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px" title="Edit"><i class="fas fa-pen" style="font-size:.76rem"></i></a>
                                    <form method="POST" action="{{ route('admin.exercises.destroy', $exercise) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exercise?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;color:#dc2626" title="Delete"><i class="fas fa-trash" style="font-size:.76rem"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5"><div class="d-flex flex-column align-items-center gap-2"><div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-dumbbell"></i></div><span class="text-muted" style="font-size:.88rem">No exercises found</span><p class="text-muted small mb-1">Get started by adding your first exercise to the library.</p><a href="{{ route('admin.exercises.create') }}" class="btn btn-sm text-white mt-1" style="background:#0f172a;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> Add First Exercise</a></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($exercises->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $exercises->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
