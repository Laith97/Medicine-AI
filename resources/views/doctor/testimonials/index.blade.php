@extends('master')

@section('title', 'Testimonials Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
/* Testimonials premium compact — clinical-card 12px / #eef0f3 / shadow / quote left #DE6262 */
.t-stats-note{font-size:0.68rem;color:#94a3b8;font-weight:500}
.t-card{
  background:#ffffff;
  border:1px solid #eef0f3 !important;
  border-radius:12px !important;
  overflow:hidden;
  box-shadow:0 6px 20px rgba(44,62,80,0.06), 0 1px 4px rgba(44,62,80,0.04);
  transition:all 0.25s cubic-bezier(0.4,0,0.2,1);
  display:flex;
  flex-direction:column;
}
.t-card:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(44,62,80,0.10), 0 4px 12px rgba(44,62,80,0.06);
  border-color:#e6e8eb !important;
}
.t-card.border-success{ border-color:#a7f3d0 !important; }
.t-card.border-secondary{ border-color:#eef0f3 !important; }
.t-card-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:0.75rem;
  padding:0.9rem 1rem;
  border-bottom:1px solid #f1f5f9;
  background:#ffffff;
}
.patient-avatar{
  width:40px;height:40px;border-radius:50%;
  background:linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  color:#fff;display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:0.85rem;flex-shrink:0;
  box-shadow:0 2px 6px rgba(44,62,80,0.18);
  border:1px solid rgba(255,255,255,0.9);
}
.t-badge{
  font-size:0.68rem;font-weight:700;letter-spacing:0.03em;
  padding:0.32rem 0.62rem;border-radius:99px;border:1px solid transparent;
  text-transform:uppercase;white-space:nowrap;
}
.t-badge.bg-success{ background:#ecfdf5 !important;color:#065f46 !important;border-color:#a7f3d0 !important; }
.t-badge.bg-secondary{ background:#f1f5f9 !important;color:#475569 !important;border-color:#e2e8f0 !important; }
.star-rating{font-size:0.78rem;line-height:1}
.star-rating .text-warning{color:#f59e0b !important}
.star-rating .text-muted{color:#e2e8f0 !important}
.t-quote{
  position:relative;
  background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border:1px solid #eef2f7;
  border-radius:10px;
  padding:0.9rem 1rem 0.9rem 1.1rem;
  margin:0;
  overflow:hidden;
  font-size:0.88rem;line-height:1.6;color:#334155;font-weight:500;
}
.t-quote::before{
  content:'';position:absolute;left:0;top:12%;bottom:12%;width:4px;border-radius:99px;
  background:linear-gradient(180deg, #DE6262 0%, #ff8a7a 100%);
}
.t-quote .quote-icon{
  position:absolute;right:10px;top:6px;font-size:1.6rem;color:#e2e8f0;opacity:0.9;pointer-events:none;
}
.t-area-label{
  font-size:0.72rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;margin-bottom:0.35rem;
}
.case-study-input.t-textarea{
  border:1px solid #e2e8f0 !important;border-radius:10px !important;
  font-size:0.84rem;padding:0.6rem 0.75rem;background:#fcfdff;
  box-shadow:0 1px 2px rgba(15,23,42,0.03);
  transition:border-color 0.15s, box-shadow 0.15s, background 0.15s;
  resize:vertical;min-height:78px;
}
.case-study-input.t-textarea:focus{
  background:#ffffff;border-color:#DE6262 !important;
  box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important;outline:0;
}
.case-study-input.t-textarea::placeholder{color:#94a3b8}
.t-card-footer{
  display:flex;justify-content:space-between;align-items:center;gap:0.5rem;flex-wrap:wrap;
  padding:0.85rem 1rem;background:#fcfdff;border-top:1px solid #f1f5f9;
}
.doctor-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:0.35rem;
  border-radius:10px;padding:0.52rem 0.9rem;font-size:0.80rem;font-weight:700;
  line-height:1;white-space:nowrap;border:1px solid transparent;cursor:pointer;
  transition:all 0.18s ease;letter-spacing:-0.01em;
  box-shadow:0 1px 3px rgba(15,23,42,0.06);
}
.doctor-btn:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(15,23,42,0.08)}
.doctor-btn:active{transform:translateY(0)}
.doctor-btn-success{background:#10b981 !important;color:#fff !important;border-color:#10b981 !important}
.doctor-btn-success:hover{background:#059669 !important;border-color:#059669 !important;color:#fff !important}
.doctor-btn-warning{background:#f59e0b !important;color:#fff !important;border-color:#f59e0b !important}
.doctor-btn-warning:hover{background:#d97706 !important;border-color:#d97706 !important}
.doctor-btn-primary{background:#2c3e50 !important;color:#fff !important;border-color:#2c3e50 !important}
.doctor-btn-primary:hover{background:#1e293b !important;border-color:#1e293b !important}
.doctor-btn-outline{background:#ffffff !important;color:#475569 !important;border-color:#e2e8f0 !important}
.doctor-btn-outline:hover{background:#f8fafc !important;color:#1e293b !important;border-color:#cbd5e1 !important}
.btn.doctor-btn:disabled{opacity:0.65;transform:none;box-shadow:none;cursor:not-allowed}
/* Preview cards */
.review-card{border:1px solid #eef0f3;border-radius:12px;overflow:hidden;box-shadow:0 6px 20px rgba(44,62,80,0.05)}
.review-card .case-preview{border:1px solid #eef2f7;border-radius:10px;background:#f8fafc}
.cases-panel{border-radius:12px !important;overflow:hidden;border:1px solid #eef0f3 !important;box-shadow:0 6px 20px rgba(44,62,80,0.05)}
@media (max-width:576px){
  .t-card-footer .doctor-btn{flex:1;justify-content:center}
}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-star me-2"></i>Testimonials</h2>
                    <p>Manage which reviews appear publicly on your landing page</p>
                </div>
                <span class="doctor-badge doctor-badge-success d-none d-md-inline-flex"><i class="fas fa-check-circle me-1"></i> Public</span>
            </div>
        </div>

        @php
            $totalCount = method_exists($reviews, 'total') ? $reviews->total() : $reviews->count();
            $publicCount = $reviews->where('is_public', true)->count();
            $privateCount = $reviews->where('is_public', false)->count();
        @endphp
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $totalCount }}</p>
                        <p class="stats-label">Total Reviews</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $publicCount }}</p>
                        <p class="stats-label">Public</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $privateCount }}</p>
                        <p class="stats-label">Private</p>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Testimonials grid --}}
        @if($reviews->count() > 0)
            <div class="row g-3">
                @foreach($reviews as $review)
                    <div class="col-lg-6 col-xl-4">
                        <div class="card t-card h-100 {{ $review->is_public ? 'border-success' : 'border-secondary' }}">
                            <div class="t-card-header">
                                <div class="d-flex align-items-center gap-2" style="min-width:0;">
                                    <div class="patient-avatar flex-shrink-0">
                                        @if($review->is_anonymous)
                                            A
                                        @elseif($review->patient_name)
                                            @php
                                                $names = explode(' ', trim($review->patient_name));
                                                echo count($names) >= 2 ?
                                                    strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                    strtoupper(substr($names[0], 0, 1)) . '.';
                                            @endphp
                                        @elseif($review->user && $review->user->name)
                                            @php
                                                $names = explode(' ', trim($review->user->name));
                                                echo count($names) >= 2 ?
                                                    strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                    strtoupper(substr($names[0], 0, 1)) . '.';
                                            @endphp
                                        @else
                                            P.
                                        @endif
                                    </div>
                                    <div style="min-width:0;">
                                        <div class="star-rating mb-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star{{ $i <= $review->rating ? ' text-warning' : ' text-muted' }}"></i>
                                            @endfor
                                        </div>
                                        <small class="text-muted" style="font-size:0.73rem;">{{ $review->created_at->format('M j, Y') }}</small>
                                    </div>
                                </div>
                                <span class="badge t-badge bg-{{ $review->is_public ? 'success' : 'secondary' }}">
                                    {{ $review->is_public ? 'Public' : 'Private' }}
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column" style="padding:1rem;">
                                <div class="t-quote mb-3">
                                    <i class="fas fa-quote-right quote-icon"></i>
                                    <p class="mb-0">"{{ $review->comment }}"</p>
                                </div>
                                <div class="mt-auto">
                                    <label class="t-area-label">Case Study <span style="text-transform:none;letter-spacing:0;font-weight:500;color:#94a3b8">(Optional)</span></label>
                                    <textarea class="form-control form-control-sm case-study-input t-textarea"
                                              data-review-id="{{ $review->id }}"
                                              rows="3"
                                              maxlength="1000"
                                              placeholder="Add a case study or additional context for this testimonial...">{{ $review->case_study }}</textarea>
                                    <div class="form-text" style="font-size:0.72rem;color:#94a3b8;">Displayed below the testimonial on your landing page</div>
                                </div>
                            </div>
                            <div class="t-card-footer">
                                <button type="button"
                                        class="btn doctor-btn toggle-public-btn {{ $review->is_public ? 'doctor-btn-warning btn-warning' : 'doctor-btn-success btn-success' }}"
                                        data-review-id="{{ $review->id }}"
                                        data-current-status="{{ $review->is_public ? 'public' : 'private' }}">
                                    <i class="fas fa-{{ $review->is_public ? 'eye-slash' : 'eye' }}"></i>
                                    {{ $review->is_public ? 'Make Private' : 'Make Public' }}
                                </button>
                                <button type="button"
                                        class="btn doctor-btn doctor-btn-outline save-case-study-btn"
                                        data-review-id="{{ $review->id }}">
                                    <i class="fas fa-save"></i>
                                    Save Case Study
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $reviews->links() }}
            </div>
        @else
            <div class="cases-panel p-4 py-5 text-center" style="background:#fff;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:56px;height:56px;background:#f8fafc;border:1px solid #eef2f7;color:#94a3b8;">
                    <i class="fas fa-comments" style="font-size:1.4rem;"></i>
                </div>
                <h6 class="fw-bold" style="color:#1e293b;">No reviews yet</h6>
                <p class="text-muted small mb-0">Patient reviews will appear here once they start leaving feedback.</p>
            </div>
        @endif

        <!-- Public Testimonials Preview — cases-panel -->
        @if($reviews->where('is_public', true)->count() > 0)
            <div class="card cases-panel mt-4 border-0">
                <div class="cases-toolbar" style="border-radius:12px 12px 0 0;">
                    <div class="cases-toolbar__title">
                        <h6 class="mb-0 fw-bold" style="font-size:0.95rem;color:#1e293b;"><i class="fas fa-eye text-success me-2"></i>Public Testimonials Preview</h6>
                        <span class="t-stats-note d-none d-md-inline">How testimonials appear on your landing page</span>
                    </div>
                    <span class="badge t-badge bg-success d-none d-sm-inline-flex"><i class="fas fa-check-circle me-1"></i> Live</span>
                </div>
                <div class="card-body" style="background:#fcfdff;padding:1.15rem;">
                    <div class="row g-3">
                        @foreach($reviews->where('is_public', true)->take(3) as $review)
                            <div class="col-lg-4">
                                <div class="card review-card h-100" style="background:#ffffff; border-left:4px solid #10b981 !important;">
                                    <div class="card-body" style="padding:1rem;">
                                        <div class="d-flex align-items-center mb-3 gap-2">
                                            <div class="patient-avatar" style="width:42px;height:42px;font-size:0.85rem;">
                                                @if($review->is_anonymous)
                                                    A
                                                @elseif($review->patient_name)
                                                    @php
                                                        $names = explode(' ', trim($review->patient_name));
                                                        echo count($names) >= 2 ?
                                                            strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                            strtoupper(substr($names[0], 0, 1)) . '.';
                                                    @endphp
                                                @elseif($review->user && $review->user->name)
                                                    @php
                                                        $names = explode(' ', trim($review->user->name));
                                                        echo count($names) >= 2 ?
                                                            strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1)) :
                                                            strtoupper(substr($names[0], 0, 1)) . '.';
                                                    @endphp
                                                @else
                                                    P.
                                                @endif
                                            </div>
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <div class="star-rating mb-1" style="color:#f59e0b;">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="fas fa-star{{ $i <= $review->rating ? '' : ' text-muted' }}"></i>
                                                    @endfor
                                                </div>
                                                <small class="text-muted" style="font-size:0.72rem;">
                                                    {{ $review->created_at->format('M Y') }}
                                                    <span class="badge bg-success ms-1" style="font-size:0.62rem;border-radius:99px;">
                                                        <i class="fas fa-check-circle me-1"></i>Verified
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                        <p class="mb-0" style="font-size:0.86rem;line-height:1.6;color:#334155;">"{{ $review->comment }}"</p>
                                        @if($review->case_study)
                                            <div class="mt-3 p-3 case-preview">
                                                <h6 class="mb-2 d-flex align-items-center gap-2" style="font-size:0.78rem;font-weight:700;color:#1e293b;">
                                                    <span class="d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;border-radius:7px;background:#fff;border:1px solid #e2e8f0;color:#64748b;font-size:0.7rem;"><i class="fas fa-notes-medical"></i></span>
                                                    Case Study
                                                </h6>
                                                <p class="mb-0 small" style="font-size:0.80rem;line-height:1.55;color:#475569;">{{ $review->case_study }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($reviews->where('is_public', true)->count() > 3)
                        <div class="text-center mt-3">
                            <small class="text-muted" style="font-size:0.76rem;">
                                And {{ $reviews->where('is_public', true)->count() - 3 }} more public testimonials...
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle public status — keep routes /doctor/testimonials/{id}/toggle-public
    $('.toggle-public-btn').click(function() {
        const btn = $(this);
        const reviewId = btn.data('review-id');
        const currentStatus = btn.data('current-status');

        $.ajax({
            url: `/doctor/testimonials/${reviewId}/toggle-public`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                btn.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    if (response.is_public) {
                        btn.removeClass('btn-success doctor-btn-success')
                           .addClass('btn-warning doctor-btn-warning')
                           .data('current-status', 'public')
                           .html('<i class="fas fa-eye-slash"></i> Make Private');
                        btn.closest('.t-card').removeClass('border-secondary').addClass('border-success');
                        btn.closest('.t-card').find('.t-badge').removeClass('bg-secondary').addClass('bg-success').text('Public');
                    } else {
                        btn.removeClass('btn-warning doctor-btn-warning')
                           .addClass('btn-success doctor-btn-success')
                           .data('current-status', 'private')
                           .html('<i class="fas fa-eye"></i> Make Public');
                        btn.closest('.t-card').removeClass('border-success').addClass('border-secondary');
                        btn.closest('.t-card').find('.t-badge').removeClass('bg-success').addClass('bg-secondary').text('Private');
                    }
                    showAlert('success', response.message);
                    setTimeout(function() { location.reload(); }, 2000);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while updating the testimonial status.');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Save case study — keep route /doctor/testimonials/{id}/case-study
    $('.save-case-study-btn').click(function() {
        const btn = $(this);
        const reviewId = btn.data('review-id');
        const caseStudy = $(`.case-study-input[data-review-id="${reviewId}"]`).val();

        $.ajax({
            url: `/doctor/testimonials/${reviewId}/case-study`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                case_study: caseStudy
            },
            beforeSend: function() {
                btn.prop('disabled', true);
                btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    btn.html('<i class="fas fa-check"></i> Saved');
                    setTimeout(function() {
                        btn.html('<i class="fas fa-save"></i> Save Case Study');
                    }, 2000);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while saving the case study.');
                btn.html('<i class="fas fa-save"></i> Save Case Study');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    function showAlert(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container').first().prepend(alert);
        setTimeout(function() { $('.alert').alert('close'); }, 5000);
    }
});
</script>
@endpush
