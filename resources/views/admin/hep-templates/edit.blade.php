@extends('layouts.admin')

@section('title', 'Edit HEP Template: ' . $template->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-pen" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Edit Template</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $template->name }} · {{ ucfirst(str_replace('_',' ',$template->category)) }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hep-templates.show', $template) }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-eye me-1"></i> View</a>
            <a href="{{ route('admin.hep-templates.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hep-templates.update', $template) }}" id="templateForm">
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Basic Info -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden">
                    <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><span style="width:28px;height:28px;border-radius:8px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:.75rem"><i class="fas fa-info-circle"></i></span> Template Details</h5></div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Template Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="form-control @error('name') is-invalid @enderror" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Description <span class="text-danger">*</span></label>
                                <textarea name="description" rows="3" required class="form-control @error('description') is-invalid @enderror" style="border-radius:12px;border:1px solid #e2e8f0">{{ old('description', $template->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Category <span class="text-danger">*</span></label>
                                <select name="category" required class="form-select" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                    @foreach($categories as $category)<option value="{{ $category }}" {{ old('category', $template->category)==$category?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$category)) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Diagnosis Type</label>
                                <select name="diagnosis_type" class="form-select" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                    <option value="">General / All</option>
                                    @foreach($diagnosisTypes as $type)<option value="{{ $type }}" {{ old('diagnosis_type', $template->diagnosis_type)==$type?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$type)) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Duration (weeks) <span class="text-danger">*</span></label>
                                <input type="number" name="duration_weeks" value="{{ old('duration_weeks', $template->duration_weeks) }}" min="1" max="52" required class="form-control" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Frequency / week <span class="text-danger">*</span></label>
                                <input type="number" name="frequency_per_week" value="{{ old('frequency_per_week', $template->frequency_per_week) }}" min="1" max="7" required class="form-control" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $template->is_active)?'checked':'' }} style="width:44px;height:24px">
                                    <label class="form-check-label fw-bold" for="is_active" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em;margin-left:8px">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Goals & Precautions -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden">
                    <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><span style="width:28px;height:28px;border-radius:8px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:.75rem"><i class="fas fa-bullseye"></i></span> Goals & Precautions</h5></div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Goals</label>
                            @php $goalsData = old('goals', $template->goals ?? []); @endphp
                            <div class="tag-input" id="goals-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;min-height:42px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:10px;background:white">
                                @if($goalsData) @foreach($goalsData as $goal)<span class="tag" style="background:#ecfdf5;color:#065f46;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #a7f3d0;display:flex;align-items:center;gap:0.25rem">{{ $goal }} <span onclick="removeTag(this,'goals[]')" style="cursor:pointer">×</span></span>@endforeach @endif
                                <input type="text" placeholder="Add goal + Enter" onkeydown="addTag(event,'goals[]','goals-container')" style="border:none;outline:none;flex:1;min-width:140px;font-size:.88rem">
                            </div>
                            <div id="goals-hidden" style="display:none;">@if($goalsData) @foreach($goalsData as $goal)<input type="hidden" name="goals[]" value="{{ $goal }}">@endforeach @endif</div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Precautions</label>
                            @php $precsData = old('precautions', $template->precautions ?? []); @endphp
                            <div class="tag-input" id="precautions-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;min-height:42px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:10px;background:white">
                                @if($precsData) @foreach($precsData as $prec)<span class="tag" style="background:#fef2f2;color:#991b1b;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #fecaca;display:flex;align-items:center;gap:0.25rem">{{ $prec }} <span onclick="removeTag(this,'precautions[]')" style="cursor:pointer">×</span></span>@endforeach @endif
                                <input type="text" placeholder="Add precaution + Enter" onkeydown="addTag(event,'precautions[]','precautions-container')" style="border:none;outline:none;flex:1;min-width:140px;font-size:.88rem">
                            </div>
                            <div id="precautions-hidden" style="display:none;">@if($precsData) @foreach($precsData as $prec)<input type="hidden" name="precautions[]" value="{{ $prec }}">@endforeach @endif</div>
                        </div>
                    </div>
                </div>

                <!-- Exercises -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;overflow:hidden">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                        <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><span style="width:28px;height:28px;border-radius:8px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:.75rem"><i class="fas fa-dumbbell"></i></span> Program Exercises <span class="badge bg-light border text-muted" id="exerciseCount" style="border-radius:20px">0 added</span></h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Exercise <span class="text-danger">*</span></label>
                                    <select id="ex_select" class="form-select" style="border-radius:10px;height:38px;border:1px solid #e2e8f0;font-size:.88rem">
                                        <option value="">Select exercise...</option>
                                        @foreach($exercises as $ex)<option value="{{ $ex->id }}" data-name="{{ $ex->name }}">{{ $ex->name }} — {{ ucfirst($ex->category) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Week</label>
                                    <input type="number" id="ex_week" value="1" min="1" class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Sets</label>
                                    <input type="number" id="ex_sets" value="3" min="1" class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Reps</label>
                                    <input type="number" id="ex_reps" value="10" min="1" class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Duration (s)</label>
                                    <input type="number" id="ex_duration" placeholder="30" class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Rest (s)</label>
                                    <input type="number" id="ex_rest" placeholder="60" class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Frequency</label>
                                    <input type="text" id="ex_freq" value="Daily" class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                                <div class="col-md-3 d-flex">
                                    <button type="button" onclick="addExercise()" class="btn text-white w-100" style="background:#0f172a;border-radius:10px;font-weight:700;height:38px"><i class="fas fa-plus me-1"></i> Add</button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold" style="font-size:.72rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Progression Notes</label>
                                    <input type="text" id="ex_notes" placeholder="Optional notes..." class="form-control" style="border-radius:10px;height:38px;border:1px solid #e2e8f0">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                            <table class="table mb-0" style="font-size:.84rem" id="exercisesTable">
                                <thead><tr style="background:#f8fafc"><th style="padding:10px 12px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">#</th><th style="padding:10px 12px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Exercise</th><th style="padding:10px 12px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Week</th><th style="padding:10px 12px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Sets×Reps</th><th style="padding:10px 12px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Duration</th><th style="padding:10px 12px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em"></th></tr></thead>
                                <tbody id="exercisesBody"></tbody>
                            </table>
                        </div>
                        <div id="exercisesHidden"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm" style="border-radius:16px;position:sticky;top:24px">
                    <div class="card-body p-4">
                        <h6 style="font-weight:800;color:#0f172a;font-size:.88rem;margin-bottom:12px">Update Template</h6>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn text-white" style="background:#0f172a;border-radius:12px;font-weight:700;padding:11px"><i class="fas fa-save me-1"></i> Save Changes</button>
                            <a href="{{ route('admin.hep-templates.show', $template) }}" class="btn btn-light border" style="border-radius:12px;font-weight:600;padding:11px">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let exIndex = 0;
function addTag(event, inputName, containerId) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        const input = event.target;
        const value = input.value.trim().replace(/,$/, '');
        if (value) {
            const tag = document.createElement('span');
            const isGoal = inputName==='goals[]';
            tag.style.cssText=isGoal?'background:#ecfdf5;color:#065f46;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #a7f3d0;display:flex;align-items:center;gap:0.25rem':'background:#fef2f2;color:#991b1b;padding:0.25rem 0.5rem;border-radius:20px;font-size:0.82rem;border:1px solid #fecaca;display:flex;align-items:center;gap:0.25rem';
            tag.innerHTML = `${value} <span onclick="removeTag(this,'${inputName}')" style="cursor:pointer">×</span>`;
            const container = document.getElementById(containerId);
            container.insertBefore(tag, input);
            const hidden = document.getElementById(containerId.replace('-container','-hidden'));
            const inp = document.createElement('input'); inp.type='hidden'; inp.name=inputName; inp.value=value; hidden.appendChild(inp);
            input.value='';
        }
    }
}
function removeTag(el, name){ const tag=el.parentElement; const v=tag.textContent.replace('×','').trim(); tag.remove(); document.querySelectorAll(`input[name="${name}"][value="${v}"]`).forEach(i=>i.remove()); }

function addExercise(prefill=null){
    const sel=document.getElementById('ex_select');
    let exId, exName, week, sets, reps, duration, rest, freq, notes, order;
    if(prefill){
        exId=prefill.exercise_id; exName=prefill.exercise_name; week=prefill.week_number; sets=prefill.sets; reps=prefill.reps; duration=prefill.duration_seconds; rest=prefill.rest_seconds; freq=prefill.frequency; notes=prefill.progression_notes; order=prefill.order;
    } else {
        exId=sel.value;
        if(!exId){ alert('Select an exercise'); return; }
        exName=sel.options[sel.selectedIndex].dataset.name;
        week=document.getElementById('ex_week').value||1;
        sets=document.getElementById('ex_sets').value;
        reps=document.getElementById('ex_reps').value;
        duration=document.getElementById('ex_duration').value;
        rest=document.getElementById('ex_rest').value;
        freq=document.getElementById('ex_freq').value;
        notes=document.getElementById('ex_notes').value;
        order=exIndex;
    }
    const tbody=document.getElementById('exercisesBody');
    const tr=document.createElement('tr');
    tr.innerHTML=`<td style="padding:10px 12px;border-bottom:1px solid #f1f5f9"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px">#${exIndex+1}</span></td>
        <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9"><span style="font-weight:700;color:#0f172a">${exName}</span></td>
        <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9"><span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe;border-radius:20px">W${week}</span></td>
        <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9">${sets||'-'} × ${reps||'-'}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9">${duration?duration+'s':'—'}${freq?' · '+freq:''}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;text-align:right"><button type="button" class="btn btn-light border btn-sm" style="border-radius:8px" onclick="removeExercise(this,${exIndex})"><i class="fas fa-trash" style="color:#dc2626"></i></button></td>`;
    tbody.appendChild(tr);
    const hidden=document.getElementById('exercisesHidden');
    const fields={exercise_id:exId,week_number:week,sets, reps, duration_seconds:duration, rest_seconds:rest, frequency:freq, progression_notes:notes, order};
    Object.entries(fields).forEach(([k,v])=>{
        const inp=document.createElement('input'); inp.type='hidden'; inp.name=`exercises[${exIndex}][${k}]`; inp.value=v??''; inp.dataset.group=exIndex; hidden.appendChild(inp);
    });
    exIndex++; updateCount();
    if(!prefill) document.getElementById('ex_notes').value='';
}
function removeExercise(btn, idx){
    btn.closest('tr').remove();
    document.querySelectorAll(`#exercisesHidden input[data-group="${idx}"]`).forEach(i=>i.remove());
    updateCount();
}
function updateCount(){
    const count=document.querySelectorAll('#exercisesHidden input[name*="[exercise_id]"]').length;
    const badge=document.getElementById('exerciseCount');
    badge.textContent=count+' added';
    badge.style.background=count?'#ecfdf5':'#f1f5f9';
    badge.style.color=count?'#065f46':'#64748b';
    badge.style.borderColor=count?'#a7f3d0':'#e2e8f0';
}
document.getElementById('templateForm')?.addEventListener('submit', function(e){
    const count=document.querySelectorAll('#exercisesHidden input[name*="[exercise_id]"]').length;
    if(count===0){ e.preventDefault(); alert('Add at least one exercise'); }
});
document.addEventListener('DOMContentLoaded', ()=>{
    @php
        $allExercises = collect($templateExercises)->flatten();
        if(old('exercises')) $allExercises = collect(old('exercises'))->map(function($e) use ($exercises){ $exName = $exercises->firstWhere('id', $e['exercise_id'])?->name ?? 'Exercise #'.$e['exercise_id']; return (object)array_merge($e, ['exercise_name'=>$exName]); });
    @endphp
    @foreach($allExercises as $te)
        addExercise({exercise_id: '{{ $te->exercise_id }}', exercise_name: '{{ addslashes($te->exercise->name ?? $te->exercise_name ?? "") }}', week_number: '{{ $te->week_number }}', sets: '{{ $te->sets ?? "" }}', reps: '{{ $te->reps ?? "" }}', duration_seconds: '{{ $te->duration_seconds ?? "" }}', rest_seconds: '{{ $te->rest_seconds ?? "" }}', frequency: '{{ addslashes($te->frequency ?? "") }}', progression_notes: '{{ addslashes($te->progression_notes ?? "") }}', order: '{{ $te->order ?? 0 }}'});
    @endforeach
});
</script>
@endpush
