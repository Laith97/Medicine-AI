@extends('master')

@section('title', 'My Diagnosis')

@push('styles')
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#1e293b;border-bottom:1px solid #0f172a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:rgba(255,255,255,0.12)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.18)!important}
.section-head-modern h5{color:#fff!important}
.section-head-modern p{color:rgba(255,255,255,0.75)!important}
.rating-stars{display:flex;gap:5px;margin-bottom:0.5rem}
.rating-stars .star{font-size:1.7rem;color:#e2e8f0;cursor:pointer;transition:color 0.18s}
.rating-stars .star.active,.rating-stars .star.hover{color:#f59e0b}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-file-medical me-2"></i>My Diagnosis</h2>
                    <p>From Dr. {{ e($diagnosis->doctor->name) }} · {{ $diagnosis->created_at->format('F j, Y g:i A') }} @if($diagnosis->appointment?->appointment_number) · {{ $diagnosis->appointment->appointment_number }} @endif</p>
                </div>
                <a href="{{ route('diagnosis.patient.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back to My Diagnoses</a>
            </div>
        </div>

        @if (session('success'))<div class="alert d-flex align-items-center" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.85rem 1rem;margin-bottom:1.25rem"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert d-flex align-items-center" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:0.85rem 1rem;margin-bottom:1.25rem"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>@endif

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-user-md"></i></div><div><h5 style="margin:0;font-weight:800;color:#0f172a;font-size:1rem">Doctor Information</h5><p style="margin:0;font-size:0.78rem;color:#64748b">Your diagnosing physician</p></div></div>
                <div class="d-flex gap-2">
                    <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700"><i class="fas fa-user-md me-1"></i>Doctor's Diagnosis</span>
                    @if($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0)<span class="badge" style="background:#eff6ff;color:#2563eb;border:1px solid #dbeafe;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700"><i class="fas fa-robot me-1"></i>AI Assisted</span>@endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:52px;height:52px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;flex-shrink:0"><i class="fas fa-user-md"></i></div>
                <div><div style="font-weight:700;color:#0f172a">{{ e($diagnosis->doctor->name) }}</div><small style="color:#64748b">{{ e($diagnosis->doctor->email) }}</small></div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-clipboard-check"></i></div><div><h5>Diagnosis</h5><p>Clinical findings & treatment plan</p></div></div>
            </div>
            <div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:1rem;font-size:0.95rem;color:#1e293b;line-height:1.6">{!! nl2br(e($diagnosis->diagnosis_text)) !!}</div>
            @if($diagnosis->voice_transcript && $diagnosis->voice_transcript !== $diagnosis->diagnosis_text)
                <div class="mt-3 p-3" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px"><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem"><i class="fas fa-microphone me-2" style="color:#059669"></i>Voice Transcript</h6><div class="p-2" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;font-size:0.88rem;color:#334155">{!! nl2br(e($diagnosis->voice_transcript)) !!}</div></div>
            @endif
            @if($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0)
                <div class="mt-3">
                    @foreach($diagnosis->aiAssistantResults as $index => $result)
                        <div class="p-3 mb-2" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px">
                            <div class="d-flex justify-content-between align-items-center mb-2"><h6 style="font-weight:700;color:#1e40af;font-size:0.84rem"><i class="fas fa-robot me-1"></i>AI Analysis {{ $index+1 }} ({{ ucfirst($result->source) }})</h6><small style="color:#64748b">{{ $result->created_at->format('M d, Y H:i') }}</small></div>
                            <div style="font-size:0.88rem;color:#1e293b">{!! nl2br(e($result->ai_analysis)) !!}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($diagnosis->patient_data)
            <div class="table-card">
                <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f8fafc!important;color:#475569!important;border-color:#e2e8f0!important"><i class="fas fa-notes-medical"></i></div><div><h5>Additional Information</h5><p>Vitals & patient data</p></div></div></div>
                <div class="row g-3">
                    @foreach($diagnosis->patient_data as $key => $value) @if($value)<div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><small style="font-weight:700;letter-spacing:0.06em;color:#64748b;font-size:0.70rem;text-transform:uppercase">{{ str_replace('_',' ', $key) }}</small><div style="font-size:0.88rem;color:#0f172a;margin-top:0.25rem">@if(is_array($value))<pre style="margin:0;font-size:0.78rem;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:0.5rem">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>@else{{ e($value) }}@endif</div></div></div>@endif @endforeach
                </div>
            </div>
        @endif

        <div class="table-card">
            <div class="section-head-modern">
                <div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fffbeb!important;color:#92400e!important;border-color:#fde68a!important"><i class="fas fa-question-circle"></i></div><div><h5>Follow-up Questions</h5><p>{{ $diagnosis->follow_up_count }}/5 used · AI answers instantly</p></div></div>
                <span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;border-radius:99px;padding:0.35rem 0.6rem;font-size:0.70rem;font-weight:700">{{ 5 - $diagnosis->follow_up_count }} left</span>
            </div>
            <div id="followUpsList">
                @foreach($diagnosis->followUps as $followUp)
                    <div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px">
                        <div style="font-weight:600;color:#0f172a;font-size:0.84rem"><i class="fas fa-user me-2" style="color:#475569"></i>You asked:</div>
                        <p style="font-size:0.88rem;color:#334155;margin:0.25rem 0">{{ e($followUp->question) }}</p><small style="color:#94a3b8">{{ $followUp->created_at->format('M j, Y g:i A') }}</small>
                        <div class="mt-2 p-2" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px"><strong style="font-size:0.82rem;color:#2563eb"><i class="fas fa-robot me-1"></i>AI Response:</strong><div style="font-size:0.88rem;color:#1e293b;margin-top:0.25rem">{!! nl2br(e($followUp->ai_response)) !!}</div></div>
                    </div>
                @endforeach
            </div>
            @if($diagnosis->canAskFollowUp())
                <div class="p-3" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px">
                    <h6 style="font-weight:700;color:#0f172a;font-size:0.84rem"><i class="fas fa-plus me-1"></i>Ask a Follow-up Question</h6>
                    <form id="followUpForm">
                        @csrf
                        <textarea class="form-control" id="followUpQuestion" name="question" rows="3" placeholder="Ask a question about your diagnosis..." required style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.88rem"></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small style="color:#64748b;font-size:0.78rem">You have {{ 5 - $diagnosis->follow_up_count }} questions remaining.</small>
                            <button type="submit" class="btn" id="submitFollowUp" style="background:#2563eb;color:#fff;border-radius:8px;padding:0.45rem 0.9rem;font-weight:600;font-size:0.84rem"><i class="fas fa-paper-plane me-1"></i>Ask Question</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="alert d-flex align-items-center" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-info-circle me-2"></i>You have used all 5 follow-up questions. Please contact Dr. {{ e($diagnosis->doctor->name) }} directly for additional questions.</div>
            @endif
        </div>

        @if(!$diagnosis->patient_reviewed)
            <div class="table-card">
                <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fffbeb!important;color:#92400e!important;border-color:#fde68a!important"><i class="fas fa-star"></i></div><div><h5>Rate This Diagnosis</h5><p>Help others · 1-5 stars</p></div></div></div>
                <form action="{{ route('diagnosis.review.store', $diagnosis) }}" method="POST" id="reviewForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;font-size:0.84rem;color:#1e293b">Rating <span class="text-danger">*</span></label>
                        <div class="rating-stars" id="ratingStars">@for($i=1;$i<=5;$i++)<span class="star" data-rating="{{ $i }}"><i class="fas fa-star"></i></span>@endfor</div>
                        <input type="hidden" name="rating" id="ratingInput" required><div class="rating-text mt-1" id="ratingText" style="font-size:0.82rem;color:#64748b;font-weight:500"></div>
                        @error('rating')<div style="color:#dc2626;font-size:0.78rem">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3"><label for="review_text" class="form-label" style="font-weight:600;font-size:0.84rem;color:#1e293b">Review (Optional)</label><textarea class="form-control" id="review_text" name="review_text" rows="4" placeholder="Share your experience..." style="border-radius:10px;border:1px solid #e2e8f0;font-size:0.88rem"></textarea></div>
                    <button type="submit" class="btn" id="submitReview" style="background:#f59e0b;color:#fff;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600"><i class="fas fa-star me-1"></i>Submit Review</button>
                </form>
            </div>
        @else
            <div class="alert d-flex align-items-center" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-check-circle me-2"></i>Thank you for reviewing this diagnosis!</div>
        @endif

        <div class="text-center mt-3"><a href="{{ route('diagnosis.patient.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.1rem;font-weight:500;font-size:0.88rem"><i class="fas fa-arrow-left me-2"></i>Back to My Diagnoses</a></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const ratingStars=document.getElementById('ratingStars'), ratingInput=document.getElementById('ratingInput'), ratingText=document.getElementById('ratingText');
    if(ratingStars){
        const stars=ratingStars.querySelectorAll('.star'); let selectedRating=0;
        const ratingTexts={1:'Poor',2:'Fair',3:'Good',4:'Very Good',5:'Excellent'};
        stars.forEach(star=>{
            star.addEventListener('mouseenter', function(){ const rating=parseInt(this.dataset.rating); if(selectedRating===0) highlightHover(rating); else highlight(rating); });
            star.addEventListener('mouseleave', function(){ if(selectedRating===0) stars.forEach(s=>s.classList.remove('hover')); else highlight(selectedRating); });
            star.addEventListener('click', function(){ selectedRating=parseInt(this.dataset.rating); ratingInput.value=selectedRating; highlight(selectedRating); ratingText.textContent=ratingTexts[selectedRating]; });
        });
        function highlight(rating){ stars.forEach(star=>{ star.classList.remove('active','hover'); if(parseInt(star.dataset.rating) <= rating) star.classList.add('active'); }); }
        function highlightHover(rating){ stars.forEach(star=>{ star.classList.remove('hover'); if(parseInt(star.dataset.rating) <= rating) star.classList.add('hover'); }); }
    }
    const reviewForm=document.getElementById('reviewForm');
    if(reviewForm){ reviewForm.addEventListener('submit', function(e){ if(!document.getElementById('ratingInput').value || document.getElementById('ratingInput').value==='0'){ e.preventDefault(); alert('Please select a rating before submitting.'); return false; }}); }
    const followUpForm=document.getElementById('followUpForm'), submitBtn=document.getElementById('submitFollowUp'), followUpsList=document.getElementById('followUpsList');
    if(followUpForm){
        followUpForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const question=document.getElementById('followUpQuestion').value.trim();
            if(!question){ alert('Please enter a question.'); return; }
            submitBtn.disabled=true; submitBtn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            try{
                const response=await fetch('{{ route("diagnosis.follow-up.store", $diagnosis) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({question:question})});
                const data=await response.json();
                if(data.success){
                    const html=`<div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px"><div style="font-weight:600;color:#0f172a;font-size:0.84rem"><i class="fas fa-user me-2" style="color:#475569"></i>You asked:</div><p style="font-size:0.88rem;color:#334155;margin:0.25rem 0">`+question+`</p><small style="color:#94a3b8">`+data.followUp.created_at+`</small><div class="mt-2 p-2" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px"><strong style="font-size:0.82rem;color:#2563eb"><i class="fas fa-robot me-1"></i>AI Response:</strong><div style="font-size:0.88rem;color:#1e293b;margin-top:0.25rem">`+data.followUp.ai_response.replace(/\\n/g,'<br>')+`</div></div></div>`;
                    followUpsList.insertAdjacentHTML('beforeend', html);
                    document.getElementById('followUpQuestion').value='';
                    if(data.remaining_questions===0){ document.querySelector('.follow-up-form')?.style.setProperty('display','none'); followUpsList.insertAdjacentHTML('afterend', `<div class="alert d-flex align-items-center" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:10px;padding:0.85rem 1rem"><i class="fas fa-info-circle me-2"></i>You have used all 5 follow-up questions. Please contact Dr. {{ $diagnosis->doctor->name }} directly.</div>`); }
                } else { alert(data.error || 'Failed to submit question.'); }
            } catch(error){ console.error(error); alert('Failed to submit question.'); } finally { submitBtn.disabled=false; submitBtn.innerHTML='<i class="fas fa-paper-plane me-2"></i>Ask Question'; }
        });
    }
});
</script>
@endsection
