@extends('master')

@section('title', 'Find Doctors')

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
    content: '👨‍⚕️';
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
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header">
            <h2>Doctors</h2>
            <p>Find and manage doctors</p>
        </div>

        <!-- Search and Filters -->
        <div class="table-card mb-4">
            <form method="GET" action="{{ route('doctors.index') }}">
                <!-- Search Bar -->
                <div class="row g-3 mb-3">
                    <div class="col-md-9">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search by doctor name or specialty..." class="form-control">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row g-3">
                    <!-- Specialty Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Specialty</label>
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
                        <label class="form-label">City</label>
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
                        <label class="form-label">Language</label>
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
                        <label class="form-label">Min Rating</label>
                        <select name="min_rating" class="form-select">
                            <option value="">Any Rating</option>
                            <option value="4" {{ request('min_rating') == '4' ? 'selected' : '' }}>4+ Stars</option>
                            <option value="4.5" {{ request('min_rating') == '4.5' ? 'selected' : '' }}>4.5+ Stars</option>
                        </select>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Sort By</label>
                        <select name="sort_by" class="form-select">
                            <option value="rating" {{ request('sort_by') == 'rating' ? 'selected' : '' }}>Rating</option>
                            <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="reviews" {{ request('sort_by') == 'reviews' ? 'selected' : '' }}>Reviews</option>
                            <option value="fee" {{ request('sort_by') == 'fee' ? 'selected' : '' }}>Consultation Fee</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" onclick="clearFilters()" class="btn btn-outline-secondary me-2">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="mb-4">
            <p class="text-muted">
                Showing {{ $doctors->firstItem() ?? 0 }}-{{ $doctors->lastItem() ?? 0 }} of {{ $doctors->total() }} doctors
            </p>
        </div>

        <!-- Doctor Cards -->
        <div class="row">
            @forelse($doctors as $doctor)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <!-- Doctor Image -->
                        <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background: linear-gradient(135deg, #2c3e50 0%, #c55252 100%);">
                            @if($doctor->profile_image)
                                <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                     alt="{{ $doctor->user->name }}"
                                     class="rounded-circle border border-4 border-white"
                                     style="width: 100px; height: 100px; object-fit: cover;">
                            @else
                                <div class="rounded-circle border border-4 border-white bg-white d-flex align-items-center justify-content-center"
                                     style="width: 100px; height: 100px;">
                                    <i class="fas fa-user-md text-primary" style="font-size: 2rem;"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Doctor Info -->
                        <div class="card-body">
                            <h5 class="card-title">{{ $doctor->user->name }}</h5>
                            <p class="text-primary fw-medium mb-2">{{ $doctor->specialty->name }}</p>

                            <!-- Rating -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-warning me-2">
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
                                <small class="text-muted">
                                    {{ number_format($doctor->average_rating, 1) }} ({{ $doctor->total_reviews }} reviews)
                                </small>
                            </div>

                            <!-- Location -->
                            <div class="d-flex align-items-center text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <small>{{ $doctor->city }}, {{ $doctor->state }}</small>
                            </div>

                            <!-- Languages -->
                            @if($doctor->languages)
                                <div class="d-flex align-items-center text-muted mb-2">
                                    <i class="fas fa-language me-2"></i>
                                    <small>{{ implode(', ', $doctor->languages) }}</small>
                                </div>
                            @endif

                            <!-- Consultation Fee -->
                            <div class="d-flex align-items-center fw-bold mb-3 text-success">
                                <i class="fas fa-dollar-sign me-2"></i>
                                <span>{{ number_format($doctor->consultation_fee / 100, 2) }} consultation</span>
                            </div>

                            <!-- Bio -->
                            <p class="card-text text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $doctor->bio }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-primary-custom">
                                    View Profile
                                </a>
                                @auth
                                    <a href="{{ route('appointments.create', $doctor) }}" class="btn btn-primary-custom">
                                        Book Now
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary-custom">
                                        Login to Book
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-user-md"></i>
                        <h5>No doctors found</h5>
                        <p>Try adjusting your search criteria or filters.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($doctors->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $doctors->links() }}
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
