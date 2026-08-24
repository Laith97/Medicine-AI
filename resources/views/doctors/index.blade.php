@extends('master')

@section('title', 'Find Doctors')

@push('styles')
<style>
.hero-search{background:linear-gradient(180deg, rgba(255,255,255,0.82) 0%, rgba(248,250,252,0.88) 100%), url('{{ asset('demos/medical/images/doctors/3.jpg') }}') center/cover no-repeat;border-bottom:1px solid #e2e8f0;padding:2.5rem 0 2rem;box-shadow:0 1px 3px rgba(15,23,42,0.04);margin-top:-1px}
.hero-search h1{font-size:1.9rem;font-weight:800;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.4rem}
.hero-search p{font-size:0.9rem;color:#64748b;margin:0 0 1.25rem}
.search-bar{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 4px 12px rgba(15,23,42,0.04);padding:6px;display:flex;gap:6px;max-width:720px}
.search-bar input{flex:1;border:none;background:transparent;padding:0.6rem 0.85rem;font-size:0.9rem;box-shadow:none}
.search-bar input:focus{outline:none;box-shadow:none}
.search-bar button{background:#DE6262;border:1px solid #DE6262;color:#ffffff;border-radius:8px;padding:0.6rem 1.1rem;font-weight:600;font-size:0.875rem;white-space:nowrap}
.search-bar button:hover{background:#c55050}
.filter-pills{display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:1rem}
.filter-pills select{background:#ffffff;border:1px solid #e2e8f0;border-radius:99px;padding:0.4rem 0.85rem;font-size:0.82rem;color:#334155;font-weight:500}
.doctor-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;transition:border-color .15s, box-shadow .15s;height:100%;display:flex;flex-direction:column}
.doctor-card:hover{border-color:#cbd5e1;box-shadow:0 8px 24px rgba(15,23,42,0.06)}
.doctor-card-top{height:72px;background:linear-gradient(135deg,#fef2f2 0%,#f8fafc 100%);border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:center;position:relative}
.avatar{width:64px;height:64px;border-radius:50%;background:#ffffff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin-bottom:-32px;position:relative;z-index:1}
.doctor-body{padding:2rem 1.25rem 1.25rem;text-align:center;flex:1}
.doctor-name{font-size:0.95rem;font-weight:600;color:#0f172a;margin:0}
.doctor-spec{font-size:0.78rem;color:#DE6262;font-weight:500;margin:0.15rem 0 0.5rem}
.rating{font-size:0.78rem;color:#f59e0b}
.meta{font-size:0.78rem;color:#64748b;display:flex;align-items:center;justify-content:center;gap:0.35rem;margin-top:0.5rem}
.btn-card-primary{background:#0f172a;color:#ffffff;border:1px solid #0f172a;border-radius:8px;padding:0.55rem;font-size:0.84rem;font-weight:600;width:100%;text-align:center;display:block;text-decoration:none}
.btn-card-primary:hover{background:#1e293b;color:#ffffff}
.btn-card-ghost{background:#ffffff;border:1px solid #e2e8f0;color:#334155;border-radius:8px;padding:0.55rem;font-size:0.84rem;font-weight:500;width:100%;text-align:center;display:block;text-decoration:none}
.pagination{--bs-pagination-bg:#ffffff;--bs-pagination-border-color:#e2e8f0;--bs-pagination-color:#475569;--bs-pagination-hover-bg:#f1f5f9;--bs-pagination-hover-border-color:#e2e8f0;--bs-pagination-hover-color:#0f172a;--bs-pagination-active-bg:rgba(222,98,98,0.08);--bs-pagination-active-border-color:rgba(222,98,98,0.15);--bs-pagination-active-color:#DE6262;gap:6px;justify-content:center}
.pagination .page-link{border-radius:8px !important;padding:0.45rem 0.75rem;font-size:0.84rem;font-weight:500;min-width:36px;text-align:center;transition:all .15s}
.pagination .page-item.active .page-link{font-weight:600;box-shadow:none}
.pagination .page-item.disabled .page-link{background:#f8fafc;opacity:0.6}
nav[role="navigation"] p, nav[role="navigation"] span{font-size:0.84rem;color:#64748b}
</style>
@endpush

@section('content')
<section class="hero-search">
    <div class="container" style="max-width:860px">
        <h1>Find your doctor</h1>
        <p>Verified professionals, clear profiles, instant booking.</p>
        <form method="GET" action="{{ route('doctors.index') }}">
            <div class="search-bar">
                <span class="d-flex align-items-center ps-2" style="color:#94a3b8"><i class="fas fa-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, specialty, condition...">
                <button type="submit">Search</button>
            </div>
            <div class="filter-pills">
                <select name="specialty" onchange="this.form.submit()">
                    <option value="">All specialties</option>
                    @foreach($specialties as $s)<option value="{{ $s->id }}" {{ request('specialty')==$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
                </select>
                <select name="city" onchange="this.form.submit()">
                    <option value="">All cities</option>
                    @foreach($cities as $c)<option value="{{ $c }}" {{ request('city')==$c?'selected':'' }}>{{ $c }}</option>@endforeach
                </select>
                <select name="language" onchange="this.form.submit()">
                    <option value="">Any language</option>
                    @foreach($languages as $l)<option value="{{ $l }}" {{ request('language')==$l?'selected':'' }}>{{ $l }}</option>@endforeach
                </select>
                <select name="min_rating" onchange="this.form.submit()">
                    <option value="">Any rating</option>
                    <option value="4" {{ request('min_rating')=='4'?'selected':'' }}>4+ ★</option>
                    <option value="4.5" {{ request('min_rating')=='4.5'?'selected':'' }}>4.5+ ★</option>
                </select>
                <a href="{{ route('doctors.index') }}" style="font-size:0.82rem;color:#475569;text-decoration:none;padding:0.4rem 0.75rem;border:1px solid #e2e8f0;border-radius:99px;background:#ffffff">Clear</a>
            </div>
        </form>
    </div>
</section>

<div style="background:#f8fafc;padding:1.75rem 0 2.5rem">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3" style="font-size:0.82rem;color:#64748b">
            <span>Showing <strong style="color:#0f172a">{{ $doctors->firstItem() ?? 0 }}-{{ $doctors->lastItem() ?? 0 }}</strong> of {{ $doctors->total() }}</span>
            <select name="sort_by" form="sortForm" onchange="this.form.submit()" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:0.35rem 0.65rem;font-size:0.78rem;color:#334155">
                <option value="rating" {{ request('sort_by')=='rating'?'selected':'' }}>Top rated</option>
                <option value="name" {{ request('sort_by')=='name'?'selected':'' }}>Name</option>
                <option value="reviews" {{ request('sort_by')=='reviews'?'selected':'' }}>Most reviews</option>
            </select>
        </div>

        @if($doctors->count()>0)
            <div class="row g-3">
                @foreach($doctors as $doctor)
                <div class="col-lg-4 col-md-6">
                    <div class="doctor-card">
                        <div class="doctor-card-top">
                            @if($doctor->profile_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($doctor->profile_image))
                                <img src="{{ asset('storage/' . $doctor->profile_image) }}" alt="" class="avatar" style="object-fit:cover" onerror="this.style.display='none';document.getElementById('fallback-{{ $doctor->id }}').style.display='flex'">
                                <span id="fallback-{{ $doctor->id }}" class="avatar" style="display:none"><i class="fas fa-user-md" style="color:#94a3b8"></i></span>
                            @else
                                <span class="avatar"><i class="fas fa-user-md" style="color:#94a3b8"></i></span>
                            @endif
                        </div>
                        <div class="doctor-body">
                            <h5 class="doctor-name">{{ $doctor->user->name }}</h5>
                            <p class="doctor-spec">{{ $doctor->specialty->name }}</p>
                            <div class="rating">
                                @for($i=1;$i<=5;$i++)
                                    @if($i <= floor($doctor->average_rating))<i class="fas fa-star"></i>
                                    @elseif($i - 0.5 <= $doctor->average_rating)<i class="fas fa-star-half-alt"></i>
                                    @else<i class="far fa-star" style="color:#e2e8f0"></i>@endif
                                @endfor
                                <span style="color:#64748b;font-size:0.72rem;margin-left:0.25rem">{{ number_format($doctor->average_rating,1) }} · {{ $doctor->total_reviews }}</span>
                            </div>
                            <div class="meta"><i class="fas fa-map-marker-alt" style="color:#94a3b8"></i> {{ $doctor->city }}, {{ $doctor->state }}</div>
                            @if($doctor->languages)<div class="meta"><i class="fas fa-language" style="color:#94a3b8"></i> {{ implode(', ', array_slice($doctor->languages,0,2)) }}</div>@endif
                        </div>
                        <div class="p-3 d-grid gap-2" style="border-top:1px solid #f1f5f9">
                            <a href="{{ route('doctors.show', $doctor) }}" class="btn-card-primary">View profile</a>
                            @auth
                                <a href="{{ route('appointments.create', $doctor) }}" class="btn-card-ghost">Book appointment</a>
                            @else
                                <a href="{{ route('login') }}" class="btn-card-ghost">Login to book</a>
                            @endauth
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @if($doctors->hasPages())<div class="d-flex justify-content-center mt-4">{{ $doctors->links('pagination::bootstrap-5') }}</div>@endif
        @else
            <div class="text-center py-5" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
                <i class="fas fa-user-md" style="font-size:2rem;color:#cbd5e1"></i>
                <h5 class="mt-3" style="font-size:1rem;color:#0f172a">No doctors found</h5>
                <p style="font-size:0.875rem;color:#64748b">Adjust filters to see more results</p>
            </div>
        @endif
    </div>
</div>
@endsection
