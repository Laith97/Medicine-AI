@extends('master')

@section('title', 'Ambient Listening Training Guide')

@section('content')
<style>
.app-main{ background:#f8fafc }
.modern-card{ border:1px solid #eef2f7!important; border-radius:14px!important; box-shadow:0 4px 16px rgba(15,23,42,0.04)!important; background:#fff }
</style>
<div class="container-fluid" style="background:#f8fafc">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;color:#fff;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);text-align:center;padding-top:11px"><i class="fas fa-graduation-cap" style="color:#fff;font-size:1.1rem"></i></div>
                <div>
                    <h4 class="mb-0" style="font-weight:800;color:#fff;letter-spacing:-0.02em">Ambient Listening Training Guide</h4>
                    <small style="color:rgba(255,255,255,0.78)">Hybrid transcription + AI processing for consultations</small>
                </div>
            </div>
            <a href="{{ route('ai.ambient-listening.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-microphone me-1"></i>Back to Session</a>
        </div>
    </div>
    <div class="container pb-4">
    <div class="row">
        <div class="col-12">
            <div class="card modern-card" style="overflow:hidden">
                <div class="card-body p-0">
                <div class="card-body p-4" style="background:#fff">
                    <!-- Introduction -->
                    <div class="d-flex gap-3 p-3 mb-3 rounded-3" style="background:#eff6ff;border:1px solid #dbeafe">
                        <div style="width:36px;height:36px;border-radius:10px;background:#2563eb;color:#fff;text-align:center;padding-top:8px;flex-shrink:0"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <h5 style="font-weight:800;color:#1e3a8a;font-size:0.95rem">Welcome to Ambient Listening Training</h5>
                            <p class="mb-0" style="font-size:0.86rem;color:#334155;line-height:1.6">Hybrid real-time + server AI processing for superior medical documentation accuracy.</p>
                        </div>
                    </div>

                    <!-- Quick Start -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card modern-card" style="overflow:hidden">
                                <div class="card-header border-0 d-flex align-items-center gap-2" style="background:#1e293b;color:#fff;padding:1rem 1.2rem">
                                    <span style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.12);text-align:center;padding-top:6px"><i class="fas fa-rocket" style="font-size:0.8rem"></i></span>
                                    <h5 class="mb-0" style="font-weight:800;color:#fff;font-size:0.95rem">Quick Start Guide</h5>
                                </div>
                                <div class="card-body p-3" style="background:#fff">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>1. Patient Selection</h6>
                                            <p>Select a patient from the dropdown or create a new patient profile.</p>

                                            <h6>2. Start Recording</h6>
                                            <p>Click "Start Recording" to begin the hybrid session. The system will:</p>
                                            <ul>
                                                <li>🎙️ Start live voice transcription</li>
                                                <li>🎵 Begin audio recording for server processing</li>
                                                <li>🤖 Enable AI-powered analysis</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>3. Conduct Consultation</h6>
                                            <p>Speak naturally with your patient. The system automatically:</p>
                                            <ul>
                                                <li>Transcribes conversation in real-time</li>
                                                <li>Detects language switching</li>
                                                <li>Monitors audio quality</li>
                                                <li>Identifies speaker transitions</li>
                                            </ul>

                                            <h6>4. Generate Analysis</h6>
                                            <p>Stop recording and let the AI analyze the consultation for:</p>
                                            <ul>
                                                <li>Medical data extraction</li>
                                                <li>Diagnosis suggestions</li>
                                                <li>Care plan recommendations</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Deep Dive -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="mb-3"><i class="fas fa-cogs me-2"></i>Understanding Hybrid Features</h4>
                        </div>
                    </div>

                    <!-- Real-time Transcription -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-microphone text-primary me-2"></i>
                                        Real-time Transcription
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>How it works:</h6>
                                            <p>The system uses your browser's speech recognition to transcribe conversation instantly. This provides immediate feedback and allows you to correct any transcription errors in real-time.</p>

                                            <h6>Best practices:</h6>
                                            <ul>
                                                <li>Speak clearly and at a moderate pace</li>
                                                <li>Pause briefly between speakers</li>
                                                <li>Use the language selector for multilingual consultations</li>
                                                <li>Monitor the audio level indicator for optimal recording</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="alert alert-success">
                                                <h6><i class="fas fa-check-circle me-1"></i>Benefits</h6>
                                                <ul class="mb-0">
                                                    <li>Immediate transcription</li>
                                                    <li>Real-time error correction</li>
                                                    <li>Multi-language support</li>
                                                    <li>Speaker transition detection</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Server-side Processing -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-server text-success me-2"></i>
                                        Server-side AI Processing
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>How it works:</h6>
                                            <p>When you stop recording, the system uploads the audio file to our secure servers where advanced AI (OpenAI Whisper) re-processes the entire conversation. This often provides more accurate transcription than real-time processing.</p>

                                            <h6>What happens:</h6>
                                            <ul>
                                                <li>Audio is securely uploaded and processed</li>
                                                <li>AI extracts medical information automatically</li>
                                                <li>System compares live vs server results</li>
                                                <li>Best transcription is selected and displayed</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="alert alert-success">
                                                <h6><i class="fas fa-brain me-1"></i>AI Features</h6>
                                                <ul class="mb-0">
                                                    <li>Higher accuracy transcription</li>
                                                    <li>Automatic medical data extraction</li>
                                                    <li>Structured diagnosis suggestions</li>
                                                    <li>Care plan recommendations</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hands-free Mode -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-robot text-warning me-2"></i>
                                        Hands-free Mode
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>Advanced features:</h6>
                                            <ul>
                                                <li><strong>Continuous recording:</strong> System restarts automatically after pauses</li>
                                                <li><strong>Silence detection:</strong> Automatically pauses during long silences</li>
                                                <li><strong>Audio monitoring:</strong> Real-time audio level visualization</li>
                                                <li><strong>Speaker detection:</strong> Identifies when different people are speaking</li>
                                            </ul>

                                            <h6>Controls:</h6>
                                            <ul>
                                                <li><kbd>Ctrl+H</kbd> - Toggle hands-free mode</li>
                                                <li><kbd>Ctrl+P</kbd> - Pause/Resume recording</li>
                                                <li><kbd>Ctrl+Space</kbd> - Quick start/stop</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="alert alert-warning">
                                                <h6><i class="fas fa-lightbulb me-1"></i>Tips</h6>
                                                <ul class="mb-0">
                                                    <li>Use in quiet environments</li>
                                                    <li>Position microphone properly</li>
                                                    <li>Monitor audio levels</li>
                                                    <li>Test before patient consultations</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Troubleshooting -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Troubleshooting Guide</h5>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="troubleshootingAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#microphone">
                                                    <i class="fas fa-microphone me-2"></i>Microphone Issues
                                                </button>
                                            </h2>
                                            <div id="microphone" class="accordion-collapse collapse show" data-bs-parent="#troubleshootingAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li><strong>Permission denied:</strong> Allow microphone access when prompted by your browser</li>
                                                        <li><strong>No microphone found:</strong> Check microphone connection and browser settings</li>
                                                        <li><strong>Poor audio quality:</strong> Use a quality microphone and reduce background noise</li>
                                                        <li><strong>Chrome recommended:</strong> Best compatibility with Chrome browser</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#transcription">
                                                    <i class="fas fa-file-alt me-2"></i>Transcription Problems
                                                </button>
                                            </h2>
                                            <div id="transcription" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li><strong>Inaccurate transcription:</strong> Speak clearly and use medical terminology consistently</li>
                                                        <li><strong>Language switching:</strong> The system auto-detects language changes</li>
                                                        <li><strong>Multiple speakers:</strong> Pause between speakers for better separation</li>
                                                        <li><strong>Server processing:</strong> Wait for server-side processing to complete for best accuracy</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#performance">
                                                    <i class="fas fa-chart-line me-2"></i>Performance Issues
                                                </button>
                                            </h2>
                                            <div id="performance" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                                <div class="accordion-body">
                                                    <ul>
                                                        <li><strong>Slow processing:</strong> Check internet connection and try again</li>
                                                        <li><strong>Server errors:</strong> System automatically falls back to live transcription</li>
                                                        <li><strong>Memory issues:</strong> Restart browser if experiencing slowdowns</li>
                                                        <li><strong>View performance:</strong> Check the Performance page for detailed analytics</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Best Practices -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Best Practices for Medical Consultations</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-user-md me-2"></i>Consultation Setup</h6>
                                            <ul>
                                                <li>Test microphone and audio levels before starting</li>
                                                <li>Ensure patient consent for voice recording</li>
                                                <li>Choose appropriate language setting</li>
                                                <li>Minimize background noise</li>
                                            </ul>

                                            <h6><i class="fas fa-comments me-2"></i>During Consultation</h6>
                                            <ul>
                                                <li>Speak clearly and at moderate pace</li>
                                                <li>Use consistent medical terminology</li>
                                                <li>Allow brief pauses between speakers</li>
                                                <li>Monitor transcription accuracy in real-time</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-brain me-2"></i>AI Analysis</h6>
                                            <ul>
                                                <li>Review AI-extracted medical data</li>
                                                <li>Verify diagnosis suggestions</li>
                                                <li>Use AI analysis as clinical decision support</li>
                                                <li>Always apply your professional judgment</li>
                                            </ul>

                                            <h6><i class="fas fa-save me-2"></i>Documentation</h6>
                                            <ul>
                                                <li>Complete diagnosis entry after AI analysis</li>
                                                <li>Link to appointments when appropriate</li>
                                                <li>Review and edit AI-generated content</li>
                                                <li>Save comprehensive medical records</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Monitoring -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Monitor Your Performance
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p>Use the Performance page to track your ambient listening usage and success rates. Key metrics include:</p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6>Success Rates</h6>
                                                <p>Track transcription and processing accuracy</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6>Processing Times</h6>
                                                <p>Monitor system performance and efficiency</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6>Error Analysis</h6>
                                                <p>Identify and resolve common issues</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="{{ route('ai.ambient-listening.performance') }}" class="btn btn-primary">
                                            <i class="fas fa-chart-line me-2"></i>View Performance Analytics
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling to accordion sections
    const accordionButtons = document.querySelectorAll('.accordion-button');
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            setTimeout(() => {
                this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 300);
        });
    });
});
</script>
@endsection