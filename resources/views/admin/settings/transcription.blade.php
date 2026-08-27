@extends('layouts.admin')
@section('title','Transcription Settings')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-microphone" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Transcription Settings</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Configure ambient listening provider & retention</p>
            </div>
        </div>
        <span class="badge d-none d-md-inline" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:20px;padding:6px 12px;font-weight:700"><i class="fas fa-sliders-h me-1"></i> System</span>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-cog" style="color:#64748b"></i> Provider Configuration</h5>
                </div>
                <div class="card-body p-4" style="background:#fff">
                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="transcription_provider" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Transcription Provider <span class="text-danger">*</span></label>
                            <select class="form-select" id="transcription_provider" name="transcription_provider" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem">
                                <option value="google" {{ $provider == 'google' ? 'selected' : '' }}>Google Cloud Speech-to-Text</option>
                                <option value="assemblyai" {{ $provider == 'assemblyai' ? 'selected' : '' }}>AssemblyAI</option>
                            </select>
                            <div class="form-text" style="font-size:0.76rem;color:#64748b"><i class="fas fa-info-circle me-1"></i>Select the default provider for ambient listening.</div>
                        </div>

                        <div class="mb-4">
                            <label for="assemblyai_api_key" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">AssemblyAI API Key</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px 0 0 10px"><i class="fas fa-key" style="color:#64748b"></i></span>
                                <input type="password" class="form-control" id="assemblyai_api_key" name="assemblyai_api_key" value="{{ $assemblyai_key }}" style="border-radius:0 10px 10px 0;border:1px solid #e2e8f0;border-left:none;height:38px;font-size:0.88rem" placeholder="••••••••••••••••">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="audio_retention_hours" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Audio Retention (Hours)</label>
                            <input type="number" class="form-control" id="audio_retention_hours" name="audio_retention_hours" value="{{ $retention_hours }}" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:0.88rem" placeholder="24" min="1">
                            <div class="form-text" style="font-size:0.76rem;color:#64748b"><i class="fas fa-clock me-1"></i>How long to keep raw audio files before automatic deletion.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3" style="border-top:1px solid #eef2f7">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-light border" style="border-radius:10px;font-weight:600;padding:0.55rem 1.1rem">Cancel</a>
                            <button type="submit" class="btn text-white" style="background:#0f172a;border:none;border-radius:10px;font-weight:700;padding:0.55rem 1.2rem"><i class="fas fa-save me-1"></i>Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert d-flex gap-2 mt-3" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:12px;font-size:0.84rem"><i class="fas fa-shield-alt mt-1"></i><div><strong>Security note:</strong> Audio files are automatically purged after the retention period. Provider keys are stored encrypted.</div></div>
        </div>
    </div>
</div>
@endsection
