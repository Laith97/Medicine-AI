@extends('master')

@section('title', 'My Reviews')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<style>
.app-main { background-color: var(--bg-secondary, #f8f9fa); }

/* Review cards premium */
.review-card {
    background: #ffffff;
    border: 1px solid #eef0f3;
    border-radius: 12px;
    padding: 1.05rem 1.15rem;
    border-left: 3px solid transparent;
    transition: all 0.22s ease;
    position: relative;
}
.review-card:hover {
    border-left-color: #3498db;
    background: #fcfdff;
    box-shadow: 0 6px 18px rgba(44,62,80,0.06);
    transform: translateY(-1px);
}
.review-card__stars { color: #f1c40f; font-size: 0.88rem; letter-spacing: 1px; }
.review-card__stars .far { color: #e2e8f0; }
.review-card__name { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2; }
.review-card__date { font-size: 0.74rem; color: #94a3b8; }
.review-card__comment {
    font-size: 0.88rem;
    line-height: 1.6;
    color: #334155;
    margin: 0;
}
.review-card__appointment {
    background: #f8fafc !important;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 0.75rem 0.85rem;
}
.review-card__appointment small, .review-card__appointment span { font-size: 0.78rem; }

/* clinical-card for help section — aligned with monitoring.blade.php */
.clinical-card {
    border-radius: 12px !important;
    overflow: hidden;
    border: 1px solid #eef0f3 !important;
    background: #ffffff;
    box-shadow: 0 6px 20px rgba(44,62,80,0.05), 0 1px 6px rgba(44,62,80,0.04) !important;
}
.clinical-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.9rem 1.15rem;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.clinical-card__head-left { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
.clinical-icon-box {
    width: 38px; height: 38px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; flex-shrink: 0; border: 1px solid;
}
.clinical-icon-box.icon-help { background: #f8fafc; color: #2563eb; border-color: #dbeafe; }
.clinical-card__title { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2; }
.clinical-card__subtitle { font-size: 0.74rem; color: #94a3b8; font-weight: 500; margin: 2px 0 0; }
.help-list li { font-size: 0.86rem; color: #475569; display: flex; align-items: flex-start; gap: 0.55rem; }
.help-list li i { margin-top: 0.2rem; }
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact" style="position:relative; overflow:hidden;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-star me-2"></i>Reviews</h2>
                    <p>Manage and view feedback from your patients</p>
                </div>
                <span class="doctor-badge doctor-badge-warning d-none d-md-inline-flex"><i class="fas fa-star me-1"></i> Feedback</span>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container pb-4">

        {{-- Compact stats — 42px icons like analytics / patients --}}
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ number_format($doctor->average_rating ?? 0, 1) }}</p>
                        <p class="stats-label">Average Rating</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $doctor->total_reviews ?? 0 }}</p>
                        <p class="stats-label">Total Reviews</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $positiveReviews }}</p>
                        <p class="stats-label">Positive Reviews</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $recentReviews }}</p>
                        <p class="stats-label">This Month</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter — cases-panel + cases-toolbar with cases-sort controls --}}
        <div class="card border-0 shadow-sm cases-panel mb-3">
            <form method="GET" action="{{ route('doctor.reviews.index') }}" class="m-0">
                <div class="cases-toolbar">
                    <div class="cases-toolbar__title">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-filter me-2 text-primary"></i>Filter Reviews</h6>
                        @if(request()->hasAny(['rating', 'status']))
                            <a href="{{ route('doctor.reviews.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
                                <i class="fas fa-times me-1"></i>Clear All
                            </a>
                        @endif
                        <span class="cases-toolbar__meta d-none d-sm-inline ms-2">{{ $reviews->total() }} result{{ $reviews->total() !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="cases-toolbar__controls">
                        <select name="rating" id="rating" class="form-select form-select-sm cases-sort">
                            <option value="">All Ratings</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                                    {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                </option>
                            @endfor
                        </select>
                        <select name="status" id="status" class="form-select form-select-sm cases-sort">
                            <option value="">All Reviews</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        </select>
                        <button type="submit" class="doctor-btn doctor-btn-primary doctor-btn-sm">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('doctor.reviews.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Reset filters">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Reviews list — premium modern card with left accent --}}
        <div class="card border-0 shadow-sm cases-panel mb-3">
            <div class="cases-toolbar" style="border-bottom:1px solid #f1f5f9;">
                <div class="cases-toolbar__title">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-star-half-alt me-2" style="color:#f59e0b;"></i>Patient Feedback</h6>
                    <span class="cases-toolbar__meta">{{ $reviews->total() }} total · {{ $reviews->count() }} shown</span>
                </div>
            </div>

            <div class="p-3" style="background: #f8f9fa;">
                @if($reviews->count() > 0)
                    <div class="d-flex flex-column gap-3">
                        @foreach($reviews as $review)
                            <div class="review-card">
                                {{-- Header: rating + patient + badges --}}
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="review-card__stars text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <div>
                                            <h6 class="review-card__name">
                                                @if($review->is_anonymous)
                                                    Anonymous Patient
                                                @elseif($review->patient)
                                                    {{ $review->patient->name }}
                                                @else
                                                    {{ $review->guest_name ?? 'Guest Patient' }}
                                                @endif
                                            </h6>
                                            <div class="review-card__date">
                                                <i class="far fa-clock me-1"></i>{{ $review->created_at->format('M j, Y \a\t g:i A') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($review->is_approved)
                                            <span class="doctor-badge doctor-badge-success">
                                                <i class="fas fa-check-circle me-1"></i>Approved
                                            </span>
                                        @else
                                            <span class="doctor-badge doctor-badge-warning">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        @endif
                                        @if($review->source === 'google')
                                            <span class="doctor-badge doctor-badge-info">
                                                <i class="fab fa-google me-1"></i>Google
                                            </span>
                                        @else
                                            <span class="doctor-badge doctor-badge-primary">
                                                <i class="fas fa-hospital me-1"></i>MedCura
                                            </span>
                                        @endif
                                        <span class="doctor-badge doctor-badge-secondary" style="font-variant-numeric: tabular-nums;">
                                            <i class="fas fa-star me-1" style="color:#f59e0b;"></i>{{ $review->rating }}/5
                                        </span>
                                    </div>
                                </div>

                                {{-- Comment --}}
                                @if($review->comment)
                                    <div class="mb-2">
                                        <p class="review-card__comment">{{ $review->comment }}</p>
                                    </div>
                                @endif

                                {{-- Appointment info --}}
                                @if($review->appointment)
                                    <div class="review-card__appointment">
                                        <div class="d-flex align-items-center gap-2 flex-wrap" style="color:#475569;">
                                            <i class="fas fa-calendar-check" style="color:#64748b;"></i>
                                            <span>
                                                Related to appointment on
                                                <strong style="color:#1e293b;">{{ $review->appointment->appointment_date->format('M j, Y') }}</strong>
                                                @if($review->appointment->appointment_time)
                                                    at {{ $review->appointment->appointment_time->format('g:i A') }}
                                                @endif
                                            </span>
                                        </div>
                                        @if($review->appointment->reason)
                                            <div class="mt-2 d-flex align-items-center gap-2" style="color:#64748b;">
                                                <i class="fas fa-stethoscope"></i>
                                                <span>Reason: {{ $review->appointment->reason }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($reviews->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $reviews->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center mt-3" style="font-size:0.76rem; color:#94a3b8;">
                            Showing {{ $reviews->count() }} of {{ $reviews->total() }} reviews
                        </div>
                    @endif
                @else
                    <div class="doctor-empty-state" style="background: white; border-radius: 12px; border: 1px dashed #e2e8f0;">
                        <i class="fas fa-star" style="color:#cbd5e1;"></i>
                        <h5>No Reviews Yet</h5>
                        @if(request()->hasAny(['rating', 'status']))
                            <p>No reviews match your current filters.</p>
                            <a href="{{ route('doctor.reviews.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </a>
                        @else
                            <p>You haven't received any patient reviews yet.</p>
                            <small class="text-muted d-block mt-2">Reviews will appear here after patients complete their appointments and leave feedback.</small>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Help section — clinical-card with icon box --}}
        <div class="card border-0 shadow-sm clinical-card cases-panel">
            <div class="clinical-card__head">
                <div class="clinical-card__head-left">
                    <div class="clinical-icon-box icon-help">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h6 class="clinical-card__title">About Patient Reviews</h6>
                        <p class="clinical-card__subtitle">How feedback builds trust & growth</p>
                    </div>
                </div>
                <span class="doctor-badge doctor-badge-info d-none d-sm-inline-flex"><i class="fas fa-shield-alt me-1"></i> Trusted feedback</span>
            </div>
            <div class="p-3 px-4">
                <ul class="list-unstyled mb-0 help-list d-flex flex-column gap-2">
                    <li><i class="fas fa-check-circle text-success"></i> Patients can leave reviews after completing their appointments</li>
                    <li><i class="fas fa-check-circle text-success"></i> All reviews are automatically approved and visible to other patients</li>
                    <li><i class="fas fa-check-circle text-success"></i> Reviews help build trust and attract new patients to your practice</li>
                    <li><i class="fas fa-check-circle text-success"></i> You can view both internal reviews and those synced from Google</li>
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection
