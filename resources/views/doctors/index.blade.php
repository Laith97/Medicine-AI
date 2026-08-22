@extends('master')

@section('title', 'Find Doctors')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
/* Professional Dashboard Header */
.dashboard-header {
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.15);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #E8A0A0 0%, #DE6262 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 600;
    font-size: 2.2rem;
    margin-bottom: 0.5rem;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    font-weight: 400;
    margin-bottom: 0;
}

/* Professional Cards */
.professional-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.professional-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* Enhanced Filter Section */
.filter-section {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.filter-section .form-select,
.filter-section .form-control {
    border-radius: 10px;
    border: 1px solid #e0e6ed;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-section .form-select:focus,
.filter-section .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Professional Buttons */
.btn-primary-professional {
    background-color: #DE6262;
    border-color: #DE6262;
    color: white;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary-professional:hover {
    background-color: #D64A4A;
    border-color: #D64A4A;
    color: white;
    transform: translateY(-1px);
}

/* Doctor Card */
.doctor-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
}

.doctor-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.doctor-card-img {
    height: 200px;
    background: linear-gradient(135deg, #DE6262 0%, #D64A4A 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.doctor-card-body {
    padding: 1.5rem;
}

.doctor-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.doctor-card-specialty {
    color: #DE6262;
    font-weight: 500;
    margin-bottom: 1rem;
}

/* Star Rating */
.rating-stars {
    color: #fbbf24;
}

.rating-text {
    color: #64748b;
    font-size: 0.875rem;
}

/* Location & Language */
.info-item {
    display: flex;
    align-items: center;
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.info-item i {
    width: 20px;
    margin-right: 0.5rem;
    color: #94a3b8;
}

/* Consultation Fee */
.fee-tag {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
}

/* Doctor Bio */
.doctor-bio {
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-state h5 {
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state p {
    color: #adb5bd;
    margin-bottom: 2rem;
}

/* Pagination */
.pagination-wrapper .pagination {
    border-radius: 8px;
    overflow: hidden;
}

.pagination-wrapper .page-link {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    color: #495057;
    background: #ffffff;
}

.pagination-wrapper .page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #495057;
}

.pagination-wrapper .page-item.active .page-link {
    background-color: #DE6262;
    border-color: #DE6262;
    color: white;
}

/* Background */
.page-background {
    background-color: #f8f9fa;
    min-height: 100vh;
}
</style>
@endpush

@section('content')
<div class="page-background">
    <div class="container py-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-user-md me-2"></i>Find Doctors</h2>
                    <p class="text-muted mb-0">Search and connect with healthcare professionals</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="{{ route('doctors.index') }}">
                <!-- Search Bar -->
                <div class="row g-3 mb-3">
                    <div class="col-md-9">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search by doctor name or specialty..." class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary-professional w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="row g-3">
                    <!-- Specialty Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium text-dark">Specialty</label>
                        <select name="specialty" class="form-select">
                            <option value="">All Specialties</option>
                            @foreach($specialties as $specialty)
                                <option value="{{ $specialty->id }}" {{ request('specialty') == $specialty->id ? 'selected' : '' }}>
                                    {{ $specialty->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium text-dark">City</label>
                        <select name="city" class="form-select">
                            <option value="">All Cities</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Language Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium text-dark">Language</label>
                        <select name="language" class="form-select">
                            <option value="">All Languages</option>
                            @foreach($languages as $language)
                                <option value="{{ $language }}" {{ request('language') == $language ? 'selected' : '' }}>
                                    {{ $language }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rating Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium text-dark">Min Rating</label>
                        <select name="min_rating" class="form-select">
                            <option value="">Any Rating</option>
                            <option value="4" {{ request('min_rating') == '4' ? 'selected' : '' }}>4+ Stars</option>
                            <option value="4.5" {{ request('min_rating') == '4.5' ? 'selected' : '' }}>4.5+ Stars</option>
                        </select>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark">Sort By</label>
                        <select name="sort_by" class="form-select">
                            <option value="rating" {{ request('sort_by') == 'rating' ? 'selected' : '' }}>Rating</option>
                            <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="reviews" {{ request('sort_by') == 'reviews' ? 'selected' : '' }}>Reviews</option>
                            {{-- Fee sort hidden for clinic SaaS --}}
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" onclick="clearFilters()" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="mb-4">
            <p class="text-muted">
                Showing {{ $doctors->firstItem() ?? 0 }}-{{ $doctors->lastItem() ?? 0 }} of {{ $doctors->total() }} doctors
            </p>
        </div>

        <!-- Doctor Cards -->
        @if($doctors->count() > 0)
            <div class="row">
                @foreach($doctors as $doctor)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="doctor-card">
                            <!-- Doctor Image -->
                            <div class="doctor-card-img">
                                @if($doctor->profile_image)
                                    <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                         alt="{{ $doctor->user->name }}"
                                         class="rounded-circle border border-4 border-white"
                                         style="width: 100px; height: 100px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle border border-4 border-white bg-white d-flex align-items-center justify-content-center"
                                         style="width: 100px; height: 100px;">
                                        <i class="fas fa-user-md" style="font-size: 2.5rem; color: #2c5aa0;"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Doctor Info -->
                            <div class="doctor-card-body">
                                <h5 class="doctor-card-title">{{ $doctor->user->name }}</h5>
                                <p class="doctor-card-specialty">{{ $doctor->specialty->name }}</p>

                                <!-- Rating -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rating-stars me-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($doctor->average_rating))
                                                <i class="fas fa-star"></i>
                                            @elseif($i - 0.5 <= $doctor->average_rating)
                                                <i class="fas fa-star-half-alt"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="rating-text">
                                        {{ number_format($doctor->average_rating, 1) }} ({{ $doctor->total_reviews }} reviews)
                                    </span>
                                </div>

                                <!-- Location -->
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>{{ $doctor->city }}, {{ $doctor->state }}</span>
                                </div>

                                <!-- Languages -->
                                @if($doctor->languages)
                                    <div class="info-item">
                                        <i class="fas fa-language"></i>
                                        <span>{{ implode(', ', $doctor->languages) }}</span>
                                    </div>
                                @endif

                                {{-- Fee hidden for clinic SaaS --}}

                                <!-- Bio -->
                                <p class="doctor-bio mb-0">{{ $doctor->bio }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="card-footer bg-transparent p-3 border-top">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-primary-professional">
                                        <i class="fas fa-eye me-2"></i>View Profile
                                    </a>
                                    @auth
                                        <a href="{{ route('appointments.create', $doctor) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-calendar-plus me-2"></i>Book Now
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-outline-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login to Book
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($doctors->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    <div class="pagination-wrapper">
                        {{ $doctors->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-user-md"></i>
                <h5>No Doctors Found</h5>
                <p>Try adjusting your search criteria or filters to find more doctors.</p>
                <a href="{{ route('doctors.index') }}" class="btn btn-primary-professional">
                    <i class="fas fa-refresh me-2"></i>Clear Filters
                </a>
            </div>
        @endif
    </div>
</div>

<script>
function clearFilters() {
    window.location.href = "{{ route('doctors.index') }}";
}
</script>
@endsection
