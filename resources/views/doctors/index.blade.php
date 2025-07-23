@extends('layouts.app')

@section('title', 'Find Doctors')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Find the Right Doctor for You</h1>
            <p class="text-lg text-gray-600">Search and book appointments with qualified healthcare professionals</p>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" action="{{ route('doctors.index') }}" class="space-y-4">
                <!-- Search Bar -->
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by doctor name or specialty..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Specialty Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Specialty</label>
                        <select name="specialty" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="">All Specialties</option>
                            @foreach($specialties as $specialty)
                                <option value="{{ $specialty->id }}" {{ request('specialty') == $specialty->id ? 'selected' : '' }}>
                                    {{ $specialty->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <select name="city" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="">All Cities</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Language Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                        <select name="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="">All Languages</option>
                            @foreach($languages as $language)
                                <option value="{{ $language }}" {{ request('language') == $language ? 'selected' : '' }}>
                                    {{ $language }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rating Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Rating</label>
                        <select name="min_rating" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="">Any Rating</option>
                            <option value="4" {{ request('min_rating') == '4' ? 'selected' : '' }}>4+ Stars</option>
                            <option value="4.5" {{ request('min_rating') == '4.5' ? 'selected' : '' }}>4.5+ Stars</option>
                        </select>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select name="sort_by" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="rating" {{ request('sort_by') == 'rating' ? 'selected' : '' }}>Rating</option>
                            <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="reviews" {{ request('sort_by') == 'reviews' ? 'selected' : '' }}>Reviews</option>
                            <option value="fee" {{ request('sort_by') == 'fee' ? 'selected' : '' }}>Consultation Fee</option>
                        </select>
                    </div>
                    <button type="button" onclick="clearFilters()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Clear Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="mb-6">
            <p class="text-gray-600">
                Showing {{ $doctors->firstItem() ?? 0 }}-{{ $doctors->lastItem() ?? 0 }} of {{ $doctors->total() }} doctors
            </p>
        </div>

        <!-- Doctor Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @forelse($doctors as $doctor)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Doctor Image -->
                    <div class="h-48 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                        @if($doctor->profile_image)
                            <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                 alt="{{ $doctor->user->name }}"
                                 class="w-24 h-24 rounded-full border-4 border-white object-cover">
                        @else
                            <div class="w-24 h-24 rounded-full border-4 border-white bg-white flex items-center justify-center">
                                <i class="fas fa-user-md text-3xl text-primary-600"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Doctor Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $doctor->user->name }}</h3>
                        <p class="text-primary-600 font-medium mb-2">{{ $doctor->specialty->name }}</p>

                        <!-- Rating -->
                        <div class="flex items-center mb-3">
                            <div class="flex text-yellow-400">
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
                            <span class="ml-2 text-sm text-gray-600">
                                {{ number_format($doctor->average_rating, 1) }} ({{ $doctor->total_reviews }} reviews)
                            </span>
                        </div>

                        <!-- Location -->
                        <div class="flex items-center text-gray-600 mb-3">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span class="text-sm">{{ $doctor->city }}, {{ $doctor->state }}</span>
                        </div>

                        <!-- Languages -->
                        @if($doctor->languages)
                            <div class="flex items-center text-gray-600 mb-3">
                                <i class="fas fa-language mr-2"></i>
                                <span class="text-sm">{{ implode(', ', $doctor->languages) }}</span>
                            </div>
                        @endif

                        <!-- Consultation Fee -->
                        <div class="flex items-center text-gray-900 font-semibold mb-4">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            <span>${{ number_format($doctor->consultation_fee / 100, 2) }} consultation</span>
                        </div>

                        <!-- Bio -->
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ $doctor->bio }}</p>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="{{ route('doctors.show', $doctor) }}"
                               class="flex-1 bg-primary-600 text-white text-center py-2 px-4 rounded-lg hover:bg-primary-700 transition-colors">
                                View Profile
                            </a>
                            @auth
                                <a href="{{ route('appointments.create', $doctor) }}"
                                   class="flex-1 bg-accent-600 text-white text-center py-2 px-4 rounded-lg hover:bg-accent-700 transition-colors">
                                    Book Now
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="flex-1 bg-accent-600 text-white text-center py-2 px-4 rounded-lg hover:bg-accent-700 transition-colors">
                                    Login to Book
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-user-md text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No doctors found</h3>
                    <p class="text-gray-600">Try adjusting your search criteria or filters.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($doctors->hasPages())
            <div class="flex justify-center">
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

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
