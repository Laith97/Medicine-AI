@extends('master')

@section('title', 'Review Details')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-star me-2"></i>Your Review</h2>
                    <p>For Dr. {{ e($review->doctor->user->name) }} · {{ $review->appointment->appointment_date->format('M j, Y g:i A') }} @if($review->appointment->appointment_number) · {{ $review->appointment->appointment_number }} @endif</p>
                </div>
                <span class="badge" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:99px;padding:0.4rem 0.85rem;font-size:0.74rem;font-weight:600"><i class="fas fa-check-circle me-1"></i>{{ ucfirst($review->is_approved ? 'Published' : 'Pending') }}</span>
            </div>
        </div>

        @if(session('success'))
            <div class="alert d-flex align-items-center" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.85rem 1rem;margin-bottom:1.25rem"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="alert d-flex align-items-center" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;padding:0.85rem 1rem;margin-bottom:1.25rem"><i class="fas fa-info-circle me-2"></i>{{ session('info') }}</div>
        @endif

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon"><i class="fas fa-user-md"></i></div>
                    <div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Doctor</h5><p style="margin:0;font-size:0.78rem;color:#64748b">{{ $review->doctor->specialty->name ?? 'General Practice' }}</p></div>
                </div>
                <a href="{{ route('appointments.show', $review->appointment) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.78rem;font-weight:600"><i class="fas fa-calendar me-1"></i>View Appointment</a>
            </div>
            <div class="d-flex gap-3 align-items-center">
                @if($review->doctor->user->profile_image_url ?? $review->doctor->profile_image)
                    <img src="{{ $review->doctor->user->profile_image_url ?? asset('storage/'.$review->doctor->profile_image) }}" alt="Dr. {{ $review->doctor->user->name }}" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover">
                @else
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:64px;height:64px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b"><i class="fas fa-user-md"></i></div>
                @endif
                <div>
                    <div style="font-weight:700;color:#0f172a;font-size:1rem">Dr. {{ e($review->doctor->user->name) }}</div>
                    <small style="color:#2563eb;font-weight:600">{{ e($review->doctor->specialty->name ?? 'General Practice') }}</small>
                    <div style="font-size:0.78rem;color:#64748b">{{ $review->appointment->appointment_date->format('M j, Y g:i A') }} · {{ ucfirst(str_replace('_',' ', $review->appointment->appointment_type)) }}</div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fffbeb!important;color:#92400e!important;border-color:#fde68a!important"><i class="fas fa-star"></i></div><div><h5>Your Rating</h5><p>{{ $review->rating }}/5 · {{ $review->created_at->format('M j, Y') }}</p></div></div>
                <span class="badge" style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700">{{ $review->rating }}/5</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-3" style="color:#f59e0b;font-size:1.3rem">
                @for($i=1;$i<=5;$i++)@if($i <= (int)$review->rating)<i class="fas fa-star"></i>@else<i class="far fa-star" style="color:#e2e8f0"></i>@endif @endfor
                <span style="color:#64748b;font-size:0.84rem;font-weight:600;margin-left:0.5rem">{{ $review->rating }}/5 stars</span>
            </div>
            @if($review->comment)
                <div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;font-size:0.92rem;color:#1e293b;line-height:1.6">{{ e($review->comment) }}</div>
            @else
                <p style="color:#94a3b8;font-size:0.84rem;font-style:italic">No comment provided.</p>
            @endif
            <div class="d-flex justify-content-between align-items-center mt-3" style="font-size:0.78rem;color:#64748b">
                <span>@if($review->is_anonymous)<i class="fas fa-user-secret me-1"></i>Posted anonymously @else<i class="fas fa-user me-1"></i>Posted by {{ e($review->patient->name ?? 'You') }}@endif</span>
                <span>{{ $review->created_at->format('M j, Y g:i A') }}</span>
            </div>
        </div>

        <div class="d-flex justify-content-between flex-wrap gap-3">
            <a href="{{ route('appointments.show', $review->appointment) }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.1rem;font-weight:500;font-size:0.88rem"><i class="fas fa-arrow-left me-1"></i>Back to Appointment</a>
            <div class="d-flex gap-2">
                @if($review->created_at->diffInHours(now()) <= 24)
                    <a href="{{ route('reviews.edit', $review) }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#2563eb;border-radius:10px;padding:0.6rem 1.1rem;font-weight:600;font-size:0.88rem"><i class="fas fa-edit me-1"></i>Edit</a>
                    <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this review?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn" style="background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:0.6rem 1.1rem;font-weight:600;font-size:0.88rem"><i class="fas fa-trash me-1"></i>Delete</button>
                    </form>
                @else
                    <small style="color:#94a3b8;font-size:0.78rem;align-self:center">Editing allowed within 24 hours</small>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
