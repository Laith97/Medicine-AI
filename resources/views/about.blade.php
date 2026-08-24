@extends('master')

@section('title', 'About Us')

@push('styles')
<style>
.hero-premium{background:linear-gradient(180deg, rgba(255,255,255,0.82) 0%, rgba(254,242,242,0.78) 55%, rgba(248,250,252,0.88) 100%), url('{{ asset('demos/medical/images/about-us/page-title/1.jpg') }}') center/cover no-repeat;border-bottom:1px solid #e2e8f0;padding:4rem 0 3.5rem;position:relative;overflow:hidden}
.hero-premium::before{content:'';position:absolute;top:-40px;right:-80px;width:420px;height:420px;background:radial-gradient(circle, rgba(222,98,98,0.06) 0%, transparent 70%);pointer-events:none}
.hero-badge{display:inline-flex;align-items:center;gap:0.4rem;background:#ffffff;border:1px solid #e2e8f0;border-radius:99px;padding:0.35rem 0.85rem;font-size:0.72rem;font-weight:600;color:#475569;box-shadow:0 1px 2px rgba(0,0,0,0.04)}
.hero-badge i{color:#DE6262}
.hero-title{font-size:2.4rem;font-weight:800;color:#0f172a;letter-spacing:-0.03em;line-height:1.1;margin:1rem 0 0.75rem}
.hero-title span{color:#DE6262}
.hero-sub{font-size:1rem;color:#475569;line-height:1.6;max-width:560px}
.hero-cta{margin-top:1.5rem;display:flex;gap:0.75rem;flex-wrap:wrap}
.btn-hero-primary{background:#0f172a;color:#ffffff;border:1px solid #0f172a;border-radius:10px;padding:0.7rem 1.25rem;font-weight:600;font-size:0.875rem}
.btn-hero-primary:hover{background:#1e293b;color:#ffffff}
.btn-hero-ghost{background:#ffffff;border:1px solid #e2e8f0;color:#334155;border-radius:10px;padding:0.7rem 1.25rem;font-weight:600;font-size:0.875rem}
.btn-hero-ghost:hover{background:#f8fafc}
.hero-visual{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,0.06);padding:1.25rem;position:relative}
.hero-visual-grid{display:grid;grid-template-columns:1fr 1fr;gap:0.75rem}
.hero-visual-card{background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:1rem;text-align:center}
.hero-visual-card i{color:#DE6262;margin-bottom:0.5rem}
.hero-visual-card strong{display:block;font-size:1.4rem;font-weight:800;color:#0f172a;line-height:1}
.hero-visual-card span{font-size:0.72rem;color:#64748b;font-weight:500}
.section-label{font-size:0.68rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#DE6262;margin:0 0 0.5rem}
.section-h{font-size:1.5rem;font-weight:700;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.5rem}
.section-p{font-size:0.9rem;color:#64748b;line-height:1.6;margin:0}
.feature-modern{padding:1.25rem 0;border-bottom:1px solid #f1f5f9}
.feature-modern:last-child{border-bottom:none}
.feature-modern-icon{width:40px;height:40px;border-radius:10px;background:#ffffff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#DE6262;flex-shrink:0}
.feature-modern h5{font-size:0.95rem;font-weight:600;color:#0f172a;margin:0}
.feature-modern p{font-size:0.84rem;color:#64748b;margin:0.25rem 0 0;line-height:1.5}
.stats-bar{background:#0f172a;border-radius:16px;padding:1.5rem;display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
.stat-item{text-align:center;color:#ffffff}
.stat-item strong{display:block;font-size:1.5rem;font-weight:800;line-height:1;color:#ffffff}
.stat-item span{font-size:0.72rem;color:#94a3b8;font-weight:500}
@media(max-width:768px){.hero-title{font-size:1.8rem}.stats-bar{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
<section class="hero-premium">
    <div class="container position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="hero-badge"><i class="fas fa-shield-halved"></i> Trusted by 1,200+ professionals</div>
                <h1 class="hero-title">{{ $aboutTitle ?? 'Healthcare,' }} <span>reimagined</span> with AI</h1>
                <p class="hero-sub">{{ $aboutTagline ?? 'MedCura unifies clinical decision support, voice assistance and patient growth in one calm, compliant workspace.' }}</p>
                <div class="hero-cta">
                    <a href="/register" class="btn-hero-primary">Start free trial</a>
                    <a href="/doctors" class="btn-hero-ghost">Browse doctors</a>
                </div>
                <div class="d-flex align-items-center gap-3 mt-4" style="font-size:0.78rem;color:#64748b">
                    <span class="d-flex align-items-center gap-1"><i class="fas fa-check" style="color:#10b981"></i> HIPAA compliant</span>
                    <span class="d-flex align-items-center gap-1"><i class="fas fa-check" style="color:#10b981"></i> No credit card</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="hero-visual-grid">
                        <div class="hero-visual-card"><i class="fas fa-stethoscope fa-lg"></i><strong>15k+</strong><span>Consultations</span></div>
                        <div class="hero-visual-card"><i class="fas fa-user-md fa-lg"></i><strong>1.2k</strong><span>Professionals</span></div>
                        <div class="hero-visual-card"><i class="fas fa-calendar-check fa-lg"></i><strong>75k</strong><span>Appointments</span></div>
                        <div class="hero-visual-card"><i class="fas fa-star fa-lg" style="color:#f59e0b"></i><strong>4.8</strong><span>Satisfaction</span></div>
                    </div>
                    <div class="mt-3 p-3 rounded" style="background:#f8fafc;border:1px solid #f1f5f9;display:flex;align-items:center;gap:0.75rem">
                        <span class="d-flex align-items-center justify-content-center" style="width:36px;height:36px;border-radius:8px;background:#DE6262;color:#ffffff"><i class="fas fa-microphone"></i></span>
                        <div><div style="font-size:0.84rem;font-weight:600;color:#0f172a">Voice assistant live</div><div style="font-size:0.72rem;color:#94a3b8">Transcribing · Summarizing · Coding</div></div>
                        <span class="ms-auto" style="width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 6px rgba(16,185,129,0.15)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background:#ffffff">
    <div class="container">
        <div class="section-label">Platform</div>
        <h2 class="section-h">Everything for modern practice</h2>
        <p class="section-p" style="max-width:600px">From intake to follow-up, one consistent interface - no heavy gradients, no clutter.</p>
        <div class="row g-0 mt-4" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
            @foreach($features ?? [] as $feature)
            <div class="col-md-4">
                <div class="feature-modern d-flex gap-3 p-4 h-100" style="border-right:1px solid #f1f5f9">
                    <div class="feature-modern-icon"><i class="{{ $feature['icon'] }}"></i></div>
                    <div>
                        <h5>{{ $feature['title'] }}</h5>
                        <p>{{ $feature['description'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-4" style="background:#f8fafc">
    <div class="container">
        <div class="stats-bar">
            <div class="stat-item"><strong>15,000+</strong><span>Consultations</span></div>
            <div class="stat-item"><strong>1,200+</strong><span>Professionals</span></div>
            <div class="stat-item"><strong>75,000+</strong><span>Appointments</span></div>
            <div class="stat-item"><strong>4.8★</strong><span>Satisfaction</span></div>
        </div>
    </div>
</section>

<section class="py-5" style="background:#ffffff">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="section-label">Workflow</div>
                <h2 class="section-h">{{ $whatWeDoTitle ?? 'What we do, clearly' }}</h2>
                <p class="section-p mb-4">{{ $whatWeDoDescription ?? 'Intake, transcription, diagnosis support and patient communication without switching tools.' }}</p>
                @foreach($whatWeDoFeatures ?? [] as $f)
                <div class="d-flex gap-3 mb-3">
                    <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;color:#DE6262"><i class="{{ $f['icon'] }}" style="font-size:0.85rem"></i></span>
                    <span style="font-size:0.875rem;color:#334155;line-height:1.5">{{ $f['description'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="col-lg-6">
                <div class="p-4" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px">
                    <h4 style="font-size:1rem;font-weight:600;color:#0f172a;margin:0 0 0.5rem">How it works</h4>
                    <div class="d-flex justify-content-between text-center">
                        @foreach([['user-plus','Register'],['cog','Setup'],['stethoscope','Care'],['users','Manage'],['chart-line','Grow']] as $s)
                        <div style="flex:1">
                            <div class="mx-auto d-flex align-items-center justify-content-center" style="width:40px;height:40px;border-radius:50%;background:#0f172a;color:#ffffff"><i class="fas fa-{{ $s[0] }}" style="font-size:0.85rem"></i></div>
                            <div style="font-size:0.75rem;font-weight:600;color:#0f172a;margin-top:0.4rem">{{ $s[1] }}</div>
                        </div>
                        @if(!$loop->last)<div style="flex:0 0 12px;height:1px;background:#e2e8f0;margin-top:20px"></div>@endif
                        @endforeach
                    </div>
                    <a href="/register" class="btn w-100 mt-4" style="background:#DE6262;color:#ffffff;border-radius:8px;font-weight:600;font-size:0.875rem;padding:0.65rem">Get started</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
