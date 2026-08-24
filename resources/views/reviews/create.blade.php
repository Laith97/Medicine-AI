@extends('master')

@section('title', 'Leave a Review')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.rating-input{display:flex;gap:6px}
.rating-input input[type="radio"]{display:none}
.rating-input .star{cursor:pointer;font-size:1.9rem;color:#e2e8f0;transition:color 0.18s, transform 0.12s}
.rating-input .star:hover{transform:scale(1.08)}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-star me-2"></i>Leave a Review</h2>
                    <p>For Dr. {{ e($appointment->doctor->user->name) }} · {{ $appointment->appointment_date->format('M j, Y g:i A') }} @if($appointment->appointment_number) · {{ $appointment->appointment_number }} @endif</p>
                </div>
                <a href="{{ route('appointments.show', $appointment) }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back to Appointment</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if(session('success'))<div class="alert d-flex align-items-center" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert d-flex align-items-center" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>@endif
                @if($errors->any())<div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:0.9rem"><ul class="mb-0" style="font-size:0.84rem">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <div class="table-card">
                    <div class="section-head-modern">
                        <div class="d-flex align-items-center gap-3">
                            <div class="head-icon"><i class="fas fa-user-md"></i></div>
                            <div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Your Doctor</h5><p style="margin:0;font-size:0.78rem;color:#64748b">{{ $appointment->doctor->specialty->name ?? 'General Practice' }} · {{ $appointment->appointment_date->format('M j, Y') }}</p></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        @if($appointment->doctor->profile_image_url)<img src="{{ $appointment->doctor->profile_image_url }}" alt="Dr. {{ $appointment->doctor->user->name }}" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover">@else<div class="d-flex align-items-center justify-content-center rounded-circle" style="width:64px;height:64px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b"><i class="fas fa-user-md fs-5"></i></div>@endif
                        <div><div style="font-weight:700;color:#0f172a;font-size:1rem">Dr. {{ e($appointment->doctor->user->name) }}</div><small style="color:#2563eb;font-weight:600">{{ e($appointment->doctor->specialty->name ?? 'General Practice') }}</small><div style="font-size:0.78rem;color:#64748b">{{ $appointment->appointment_date->format('M j, Y g:i A') }} · {{ ucfirst(str_replace('_',' ', $appointment->appointment_type)) }}</div></div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-head-modern">
                        <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fffbeb!important;color:#92400e!important;border-color:#fde68a!important"><i class="fas fa-star"></i></div><div><h5>Your Review</h5><p>Rating · comment · privacy</p></div></div>
                    </div>
                    <form action="{{ route('reviews.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:600;font-size:0.84rem;color:#1e293b">Rating <span class="text-danger">*</span></label>
                            <div class="rating-input">
                                @for($i=1;$i<=5;$i++)
                                    <input type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}" {{ old('rating')==$i?'checked':'' }}>
                                    <label for="star{{ $i }}" class="star"><i class="fas fa-star"></i></label>
                                @endfor
                            </div>
                            @error('rating')<div style="color:#dc2626;font-size:0.78rem;margin-top:0.25rem">{{ $message }}</div>@enderror
                            <small style="color:#64748b;font-size:0.72rem">Tap a star · 1 = Poor, 5 = Excellent</small>
                        </div>
                        <div class="mb-4">
                            <label for="comment" class="form-label" style="font-weight:600;font-size:0.84rem;color:#1e293b">Your Review</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Share your experience with Dr. {{ $appointment->doctor->user->name }}..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.88rem">{{ old('comment') }}</textarea>
                            @error('comment')<div style="color:#dc2626;font-size:0.78rem;margin-top:0.25rem">{{ $message }}</div>@enderror
                        </div>
                        <div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_anonymous" value="1" id="is_anonymous" class="form-check-input" {{ old('is_anonymous')?'checked':'' }}>
                                <label for="is_anonymous" class="form-check-label" style="font-size:0.84rem;color:#334155">Post anonymously</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="consent_google_posting" value="1" id="consent_google_posting" class="form-check-input" {{ old('consent_google_posting')?'checked':'' }}>
                                <label for="consent_google_posting" class="form-check-label" style="font-size:0.84rem;color:#334155"><i class="fab fa-google me-1" style="color:#4285F4"></i>I consent to have this review posted to Google</label>
                                <small style="color:#64748b;font-size:0.72rem;display:block;margin-left:1.5rem">By checking, you agree to have your review posted to Google Reviews for this doctor's business.</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between gap-2">
                            <a href="{{ route('appointments.show', $appointment) }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.1rem;font-weight:500;font-size:0.88rem"><i class="fas fa-arrow-left me-1"></i>Back</a>
                            <button type="submit" class="btn" style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:#fff;border:none;border-radius:10px;padding:0.6rem 1.4rem;font-weight:600;font-size:0.88rem;box-shadow:0 4px 14px rgba(245,158,11,0.25)"><i class="fas fa-star me-1"></i>Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const stars=document.querySelectorAll('.rating-input .star');
    function highlight(rating){
        stars.forEach((star,index)=>{
            star.style.color = index < rating ? '#f59e0b' : '#e2e8f0';
        });
    }
    stars.forEach((star,index)=>{
        star.addEventListener('mouseover',()=>highlight(index+1));
        star.addEventListener('mouseout',()=>{
            const checked=document.querySelector('.rating-input input[type=\"radio\"]:checked');
            highlight(checked?parseInt(checked.value):0);
        });
    });
    const checked=document.querySelector('.rating-input input[type=\"radio\"]:checked');
    if(checked) highlight(parseInt(checked.value));
});
</script>
@endpush
