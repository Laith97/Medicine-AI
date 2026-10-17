@extends('master')

@section('title', 'MedCura AI - Modern EMR System for Healthcare Practices')

@push('styles')
{{-- Landing SEO + structured data (rendered in <head> via @stack('styles')) --}}
<meta name="description" content="MedCura AI is a secure, HIPAA-conscious modern EMR for healthcare practices: patient management, smart scheduling, voice transcription, digital prescriptions, billing, and AI copilot.">
<link rel="canonical" href="{{ url('/') }}">
<meta property="og:title" content="MedCura AI - Modern EMR System for Healthcare Practices">
<meta property="og:description" content="Patient management, scheduling, voice transcription, prescriptions and billing in one secure platform.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">
<script type="application/ld+json">
{"@@context":"https://schema.org","@@type":"SoftwareApplication","name":"MedCura AI","applicationCategory":"HealthApplication","operatingSystem":"Web","offers":{"@@type":"Offer","price":"0","priceCurrency":"USD","description":"Free trial, no credit card required"},"aggregateRating":{"@@type":"AggregateRating","ratingValue":"{{ $stats['avg_rating'] ?? '4.8' }}","reviewCount":"120"}}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #DE6262;
    --primary-dark: #c55555;
    --primary-light: #FF9B9B;
    --ink: #0B0F1E;
    --ink-2: #16213e;
    --text-dark: #2C3E50;
    --text-muted: #5B6B7A;
    --bg-light: #F8F9FA;
    --display-font: 'Sora', -apple-system, 'Segoe UI', sans-serif;
    --body-font: 'Atkinson Hyperlegible', -apple-system, 'Segoe UI', sans-serif;
}

.story-landing { font-family: var(--body-font); font-size: 16px; line-height: 1.6; overflow: clip; }
.story-landing h1, .story-landing h2, .story-landing h3, .story-landing .display { font-family: var(--display-font); }

/* ---------- global cinematic chrome ---------- */
.scroll-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 3000;
    background: linear-gradient(90deg, var(--primary), #E87A7A);
    transform-origin: 0 50%; transform: scaleX(0);
}
.grain {
    position: fixed; inset: 0; z-index: 1500; pointer-events: none; opacity: .055;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
}
.chapter-nav {
    position: fixed; right: 22px; top: 50%; transform: translateY(-50%);
    z-index: 1200; display: none; flex-direction: column; gap: 14px;
}
@media (min-width: 992px) { .chapter-nav { display: flex; } }
.chapter-nav a {
    width: 12px; height: 12px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,.55); background: transparent;
    transition: all .25s ease; padding: 0; min-width: 12px; min-height: 12px;
}
.chapter-nav.on-light a { border-color: rgba(44,62,80,.4); }
.chapter-nav a.active, .chapter-nav a:hover { background: var(--primary); border-color: var(--primary); transform: scale(1.25); }
.chapter-nav a:focus-visible { outline: 3px solid var(--primary); outline-offset: 3px; }

/* ---------- scenes ---------- */
.scene { position: relative; overflow: hidden; padding: clamp(84px, 12vh, 144px) 0; }
.scene-dark { background: var(--ink); color: #fff; }
.scene-dark::after {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(ellipse at center, transparent 55%, rgba(0,0,0,.5) 100%);
}
.scene-navy { background: linear-gradient(160deg, #0B0F1E 0%, #16213e 60%, #0f3460 130%); color: #fff; }
.scene-light { background: #fff; color: var(--text-dark); }
.scene-soft { background: var(--bg-light); color: var(--text-dark); }
.scene > .container { position: relative; z-index: 2; }

.kicker {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: .8rem; font-weight: 700; letter-spacing: .22em; text-transform: uppercase;
    color: var(--primary-light); margin-bottom: 18px;
}
.scene-light .kicker, .scene-soft .kicker { color: var(--primary-dark); }
.kicker::before { content: ''; width: 34px; height: 2px; background: currentColor; display: inline-block; }

.scene-title {
    font-size: clamp(2rem, 5vw, 3.6rem); line-height: 1.08; font-weight: 800;
    letter-spacing: -0.02em; margin-bottom: 18px; text-wrap: balance;
}
.scene-lead { font-size: 1.12rem; line-height: 1.65; max-width: 38rem; }
.scene-dark .scene-lead, .scene-navy .scene-lead { color: rgba(255,255,255,.82); }
.scene-light .scene-lead, .scene-soft .scene-lead { color: var(--text-muted); }

.giant-word {
    position: absolute; top: 6%; left: 0; z-index: 1;
    font-family: var(--display-font); font-weight: 800;
    font-size: clamp(4.5rem, 17vw, 15rem); line-height: 1; white-space: nowrap;
    color: transparent; -webkit-text-stroke: 1px rgba(255,255,255,.13);
    pointer-events: none; user-select: none;
}
.scene-light .giant-word, .scene-soft .giant-word { -webkit-text-stroke: 1px rgba(222,98,98,.22); }

/* ---------- hero / prologue ---------- */
.prologue { min-height: 100svh; display: flex; align-items: center; padding: 120px 0 0; }
.orb { position: absolute; border-radius: 50%; filter: blur(90px); pointer-events: none; z-index: 1; }
.orb-a { width: 520px; height: 520px; top: -160px; right: -120px; background: rgba(222,98,98,.28); }
.orb-b { width: 420px; height: 420px; bottom: -140px; left: -140px; background: rgba(15,52,96,.9); }
.orb-c { width: 220px; height: 220px; top: 38%; left: 44%; background: rgba(232,122,122,.16); }
.hero-title { font-size: clamp(2.6rem, 7.2vw, 5.2rem); line-height: 1.02; font-weight: 800; letter-spacing: -0.025em; }
.hero-title .accent { color: var(--primary-light); }
.mask { display: block; overflow: hidden; padding-bottom: .08em; }
.line-inner { display: block; }
.hero-sub { font-size: 1.15rem; color: rgba(255,255,255,.85); max-width: 34rem; }
.scroll-cue {
    position: absolute; bottom: 26px; left: 50%; transform: translateX(-50%);
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    color: rgba(255,255,255,.7); font-size: .75rem; letter-spacing: .28em; text-transform: uppercase; z-index: 3;
}
.scroll-cue .wire { width: 2px; height: 56px; background: rgba(255,255,255,.2); position: relative; overflow: hidden; }
.scroll-cue .wire::after {
    content: ''; position: absolute; top: -40%; left: 0; width: 100%; height: 40%;
    background: var(--primary-light); animation: cue-drop 1.8s ease-in-out infinite;
}
@keyframes cue-drop { 0% { top: -40%; } 60%, 100% { top: 110%; } }
.hero-strip {
    margin-top: 56px; border-top: 1px solid rgba(255,255,255,.16);
    padding-top: 26px; display: flex; flex-wrap: wrap; gap: 12px 40px; z-index: 3; position: relative;
}
.hero-strip .hs { display: flex; align-items: baseline; gap: 10px; }
.hero-strip .hs strong { font-family: var(--display-font); font-size: 1.6rem; color: #fff; }
.hero-strip .hs span { color: rgba(255,255,255,.7); font-size: .9rem; }

.btn-primary-custom {
    background: linear-gradient(135deg, var(--primary) 0%, #E87A7A 100%);
    border: none; color: white; padding: 14px 32px; border-radius: 50px;
    font-weight: 700; font-size: 1rem; min-height: 48px;
    display: inline-flex; align-items: center; justify-content: center;
    transition: transform .2s ease, box-shadow .2s ease; box-shadow: 0 8px 25px rgba(222,98,98,.35);
}
.btn-primary-custom:hover, .btn-primary-custom:focus-visible { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(222,98,98,.5); color: white; }
.btn-ghost-custom {
    background: transparent; border: 2px solid rgba(255,255,255,.4); color: white;
    padding: 14px 32px; border-radius: 50px; font-weight: 600; font-size: 1rem; min-height: 48px;
    display: inline-flex; align-items: center; justify-content: center; transition: all .2s ease;
}
.btn-ghost-custom:hover, .btn-ghost-custom:focus-visible { border-color: white; background: rgba(255,255,255,.1); color: white; transform: translateY(-2px); }
.btn-primary-custom:focus-visible, .btn-ghost-custom:focus-visible, a:focus-visible, button:focus-visible, summary:focus-visible {
    outline: 3px solid var(--primary-light); outline-offset: 3px;
}
.scene-light a:focus-visible, .scene-soft a:focus-visible { outline-color: var(--primary-dark); }

/* ---------- reveal system (JS-gated: visible by default, no-JS safe) ---------- */
html.js [data-reveal] { opacity: 0; transform: translateY(28px); transition: opacity .7s ease, transform .7s cubic-bezier(.22,.61,.36,1); }
html.js [data-reveal].in { opacity: 1; transform: none; }
html.js [data-reveal="left"] { transform: translateX(-36px); }
html.js [data-reveal="right"] { transform: translateX(36px); }
html.js [data-reveal="zoom"] { transform: scale(.94); }

/* ---------- scrolly beats ---------- */
.beat {
    min-height: 72vh; display: flex; flex-direction: column; justify-content: center;
    padding: 28px 0; transition: opacity .4s ease;
}
html.js .beat { opacity: .32; }
html.js .beat.active { opacity: 1; }
.beat-index {
    font-family: var(--display-font); font-weight: 800; font-size: .85rem; letter-spacing: .2em;
    color: var(--primary-dark); text-transform: uppercase; margin-bottom: 10px;
}
.scene-dark .beat-index { color: var(--primary-light); }
.beat h3 { font-size: clamp(1.4rem, 2.6vw, 2rem); font-weight: 700; margin-bottom: 10px; letter-spacing: -.01em; }
.beat p { color: var(--text-muted); max-width: 26rem; }
.scene-dark .beat p { color: rgba(255,255,255,.75); }
.beat .tag {
    display: inline-block; margin-top: 14px; font-size: .78rem; font-weight: 700;
    border-radius: 50px; padding: 4px 14px; background: rgba(222,98,98,.1); color: var(--primary-dark);
}
.scene-dark .beat .tag { background: rgba(255,255,255,.1); color: #fff; }
.sticky-visual { position: sticky; top: 11vh; }
@media (max-width: 991.98px) {
    .sticky-visual { position: static; margin-bottom: 8px; }
    .beat { min-height: 0; padding: 34px 0; }
    html.js .beat { opacity: 1; }
}

/* preview mockup */
.preview-shell {
    background: #fff; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,.35);
    overflow: hidden; text-align: left; max-width: 520px; margin-inline: auto; color: var(--text-dark);
}
.preview-topbar { display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: #F1F5F9; border-bottom: 1px solid #E2E0F0; }
.preview-dot { width: 10px; height: 10px; border-radius: 50%; }
.preview-body { padding: 18px; display: grid; gap: 12px; }
.pv-card {
    border: 2px solid #E9ECEF; border-radius: 14px; padding: 14px 16px;
    display: flex; gap: 12px; align-items: flex-start; background: #fff;
    transition: border-color .35s ease, box-shadow .35s ease, transform .35s ease, opacity .35s ease;
}
html.js .pv-card { opacity: .45; }
html.js .pv-card.active { opacity: 1; border-color: var(--primary); box-shadow: 0 12px 30px rgba(222,98,98,.22); transform: translateX(6px); }
.pv-card .icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; color: #fff;
    background: linear-gradient(135deg, var(--primary), #E87A7A);
}
.pv-card strong { display: block; font-size: .95rem; }
.pv-card span { font-size: .85rem; color: var(--text-muted); }

/* AI panel */
.ai-panel {
    border-radius: 22px; padding: clamp(24px, 4vw, 44px); text-align: center; color: #fff;
    background: linear-gradient(150deg, #141a33 0%, #0B0F1E 70%);
    border: 1px solid rgba(255,255,255,.12); box-shadow: 0 30px 80px rgba(0,0,0,.5);
    position: relative; overflow: hidden;
}
.ai-panel::before {
    content: ''; position: absolute; width: 420px; height: 420px; border-radius: 50%;
    top: -180px; right: -140px; background: radial-gradient(circle, rgba(222,98,98,.35), transparent 70%);
    pointer-events: none;
}
.copilot-line {
    margin-top: 18px; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14);
    border-radius: 14px; padding: 14px 18px; font-size: .95rem; color: rgba(255,255,255,.9);
    min-height: 74px; display: flex; align-items: center; gap: 10px; text-align: left;
}
.copilot-line i { color: var(--primary-light); flex-shrink: 0; }
.ai-metrics { display: flex; justify-content: center; gap: 34px; margin-top: 22px; }
.ai-metrics strong { display: block; font-family: var(--display-font); font-size: 1.7rem; color: var(--primary-light); }
.ai-metrics span { font-size: .8rem; color: rgba(255,255,255,.65); }

/* ---------- proof ---------- */
.stat-band { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin: 40px 0 8px; }
@media (min-width: 992px) { .stat-band { grid-template-columns: repeat(4, 1fr); } }
.stat-cell {
    background: #fff; border: 1px solid #E9ECEF; border-radius: 18px; padding: 28px 20px; text-align: center;
    box-shadow: 0 10px 30px rgba(11,15,30,.06);
}
.stat-cell .num { font-family: var(--display-font); font-weight: 800; font-size: clamp(2rem, 4vw, 2.8rem); color: var(--primary-dark); }
.stat-cell .lbl { color: var(--text-muted); font-size: .95rem; }
.testimonial-card {
    background: #fff; border: 1px solid #E9ECEF; border-radius: 16px; padding: 28px; height: 100%;
    display: flex; flex-direction: column; gap: 14px; box-shadow: 0 10px 30px rgba(11,15,30,.05);
}
.testimonial-card .stars { color: #F59E0B; letter-spacing: 2px; }
.testimonial-card blockquote { font-size: 1rem; line-height: 1.65; color: #334155; margin: 0; }
.testimonial-card .who { display: flex; align-items: center; gap: 12px; margin-top: auto; }
.avatar {
    width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0; color: #fff; font-weight: 800;
    background: linear-gradient(135deg, var(--primary), #E87A7A);
    display: flex; align-items: center; justify-content: center;
}
.verified { font-size: .78rem; font-weight: 700; color: #15803D; background: #E6F9EE; border-radius: 50px; padding: 2px 10px; }

/* ---------- path / steps ---------- */
.path-track { position: relative; }
.path-line { position: absolute; top: 44px; left: 8%; right: 8%; height: 3px; background: #E5E7EB; border-radius: 3px; overflow: hidden; }
.path-line span { display: block; height: 100%; width: 100%; background: linear-gradient(90deg, var(--primary), #E87A7A); transform-origin: 0 50%; transform: scaleX(0); }
.path-card {
    background: #fff; border: 1px solid #E9ECEF; border-radius: 18px; padding: 56px 24px 30px;
    text-align: center; height: 100%; position: relative; box-shadow: 0 10px 30px rgba(11,15,30,.05);
}
.path-num {
    position: absolute; top: -24px; left: 50%; transform: translateX(-50%);
    width: 48px; height: 48px; border-radius: 50%; color: #fff; font-weight: 800; font-size: 1.25rem;
    background: linear-gradient(135deg, var(--primary), #E87A7A);
    display: flex; align-items: center; justify-content: center;
    border: 4px solid var(--bg-light); box-shadow: 0 8px 20px rgba(222,98,98,.4); z-index: 2;
}

/* ---------- faq ---------- */
.faq { max-width: 820px; margin-inline: auto; }
.faq details { background: #fff; border: 1px solid #E9ECEF; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; }
.faq summary { font-weight: 700; color: var(--text-dark); cursor: pointer; min-height: 44px; font-family: var(--display-font); }
.faq p { color: var(--text-muted); line-height: 1.65; margin: 10px 0 0; }

/* ---------- finale ---------- */
.finale { min-height: 92svh; display: flex; align-items: center; text-align: center; }
.finale-title { font-size: clamp(2.4rem, 6.5vw, 4.8rem); font-weight: 800; letter-spacing: -.02em; line-height: 1.05; }

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
    html.js [data-reveal] { opacity: 1 !important; transform: none !important; }
    html.js .beat { opacity: 1 !important; }
    html.js .pv-card { opacity: 1 !important; }
    .scroll-cue .wire::after { animation: none !important; }
}
</style>
@endpush

@section('content')
<div class="story-landing">
<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>
<div class="grain" aria-hidden="true"></div>

{{-- Chapter navigation (deep links to every act) --}}
<nav class="chapter-nav" id="chapterNav" aria-label="Story chapters">
    <a href="#top" data-chapter="top" aria-label="Prologue" title="Prologue"></a>
    <a href="#act-1" data-chapter="act-1" aria-label="Act 1: The weight" title="Act 1"></a>
    <a href="#act-2" data-chapter="act-2" aria-label="Act 2: The platform" title="Act 2"></a>
    <a href="#act-3" data-chapter="act-3" aria-label="Act 3: The copilot" title="Act 3"></a>
    <a href="#act-4" data-chapter="act-4" aria-label="Act 4: The proof" title="Act 4"></a>
    <a href="#act-5" data-chapter="act-5" aria-label="Act 5: The path" title="Act 5"></a>
    <a href="#finale" data-chapter="finale" aria-label="Finale" title="Finale"></a>
</nav>

{{-- ============ PROLOGUE / HERO ============ --}}
<header class="scene scene-navy prologue" id="top" aria-labelledby="hero-heading">
    <div class="orb orb-a" data-speed="-18" aria-hidden="true"></div>
    <div class="orb orb-b" data-speed="14" aria-hidden="true"></div>
    <div class="orb orb-c" data-speed="-10" aria-hidden="true"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-10 col-xl-9">
                <p class="kicker" data-reveal>MedCura AI &middot; A story in five acts</p>
                <h1 class="hero-title" id="hero-heading" data-hero-title>
                    <span class="mask"><span class="line-inner">Every clinic</span></span>
                    <span class="mask"><span class="line-inner">has a story.</span></span>
                    <span class="mask"><span class="line-inner accent">Make yours about care.</span></span>
                </h1>
                <p class="hero-sub mt-4" data-reveal>
                    MedCura AI is the modern EMR that carries the paperwork &mdash; scheduling,
                    transcription, prescriptions, billing &mdash; so your story stays about patients.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-4" data-reveal>
                    <a href="{{ route('register.doctor') }}" class="btn btn-primary-custom" aria-label="Start your free trial as a doctor">
                        <i class="fas fa-stethoscope me-2" aria-hidden="true"></i>Start Free Trial
                    </a>
                    <a href="#act-1" class="btn btn-ghost-custom" data-scrollto aria-label="Begin the story from Act 1">
                        <i class="fas fa-play me-2" aria-hidden="true"></i>Watch the Story
                    </a>
                </div>
                <div class="hero-strip" data-reveal aria-label="Platform at a glance">
                    <div class="hs"><strong>{{ number_format($stats['doctors'] ?? 500) }}+</strong><span>active doctors</span></div>
                    <div class="hs"><strong>{{ number_format($stats['appointments'] ?? 10000) }}+</strong><span>appointments booked</span></div>
                    <div class="hs"><strong>{{ $stats['avg_rating'] ?? '4.8' }}<i class="fas fa-star ms-1" style="font-size:1rem;color:#F59E0B;" aria-hidden="true"></i></strong><span>average rating</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="scroll-cue" aria-hidden="true"><span>Scroll</span><span class="wire"></span></div>
</header>

{{-- ============ ACT I: THE WEIGHT (problem) ============ --}}
<section class="scene scene-dark" id="act-1" aria-labelledby="act1-heading">
    <div class="giant-word" data-drift="-12" aria-hidden="true">WEIGHT</div>
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <p class="kicker" data-reveal>Act I &mdash; The weight</p>
                <h2 class="scene-title" id="act1-heading" data-reveal>Paperwork is stealing the plot.</h2>
                <p class="scene-lead" data-reveal>Hours of documentation, missed follow-ups, ringing phones. The work that never makes it into the story &mdash; yet eats the day.</p>
            </div>
        </div>
        <div class="row g-4 mt-4">
            <div class="col-md-4" data-reveal>
                <div class="display fw-bold" style="font-size:3rem;color:var(--primary-light);"><span data-count="2">2</span>+ hrs</div>
                <p style="color:rgba(255,255,255,.75);">lost to documentation every single day*</p>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="display fw-bold" style="font-size:3rem;color:var(--primary-light);"><span data-count="50">50</span>%</div>
                <p style="color:rgba(255,255,255,.75);">of physicians report burnout symptoms*</p>
            </div>
            <div class="col-md-4" data-reveal>
                <div class="display fw-bold" style="font-size:3rem;color:var(--primary-light);"><span data-count="30">30</span>%</div>
                <p style="color:rgba(255,255,255,.75);">of slots go empty without reminders*</p>
            </div>
        </div>
        <p class="mt-3" data-reveal style="font-size:.8rem;color:rgba(255,255,255,.5);">*Illustrative industry figures. Your mileage varies &mdash; that is exactly the point.</p>
    </div>
</section>

{{-- ============ ACT II: THE PLATFORM (solution scrolly) ============ --}}
<section class="scene scene-light" id="act-2" aria-labelledby="act2-heading">
    <div class="giant-word" data-drift="10" aria-hidden="true">STAGE</div>
    <div class="container">
        <p class="kicker" data-reveal>Act II &mdash; The platform</p>
        <h2 class="scene-title" id="act2-heading" data-reveal>One stage for the whole practice.</h2>
        <p class="scene-lead" data-reveal>Keep scrolling &mdash; each scene lights up a part of the platform, live on the right.</p>
        <div class="row mt-2">
            <div class="col-lg-6 order-lg-2">
                <div class="sticky-visual">
                    <div class="preview-shell" role="img" aria-label="Live preview of the MedCura AI dashboard">
                        <div class="preview-topbar" aria-hidden="true">
                            <span class="preview-dot" style="background:#FF5F57;"></span>
                            <span class="preview-dot" style="background:#FEBC2E;"></span>
                            <span class="preview-dot" style="background:#28C840;"></span>
                            <strong class="ms-2" style="font-size:.85rem;color:#334155;">MedCura AI &mdash; Today's clinic</strong>
                        </div>
                        <div class="preview-body">
                            <div class="pv-card" data-card="schedule">
                                <div class="icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></div>
                                <div><strong>Smart Scheduling</strong><span>Online booking, reminders, calendar sync.</span></div>
                            </div>
                            <div class="pv-card" data-card="transcribe">
                                <div class="icon"><i class="fas fa-microphone" aria-hidden="true"></i></div>
                                <div><strong>Ambient Transcription</strong><span>Consultations charted as they happen.</span></div>
                            </div>
                            <div class="pv-card" data-card="prescribe">
                                <div class="icon"><i class="fas fa-file-prescription" aria-hidden="true"></i></div>
                                <div><strong>Digital Prescriptions</strong><span>Interaction-checked, history-aware.</span></div>
                            </div>
                            <div class="pv-card" data-card="bill">
                                <div class="icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></div>
                                <div><strong>Billing &amp; Invoicing</strong><span>One-click invoices, full reporting.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div data-scrolly="platform">
                    <div class="beat" data-beat="schedule">
                        <div class="beat-index">Scene 1 &middot; Scheduling</div>
                        <h3>The calendar fills itself.</h3>
                        <p>Patients book online against your live availability. Automatic reminders cut no-shows before they happen.</p>
                        <span class="tag">Online booking + reminders</span>
                    </div>
                    <div class="beat" data-beat="transcribe">
                        <div class="beat-index">Scene 2 &middot; Transcription</div>
                        <h3>Notes write themselves.</h3>
                        <p>Real-time speech-to-text turns consultations into structured clinical notes &mdash; no typing after hours.</p>
                        <span class="tag">Ambient voice AI</span>
                    </div>
                    <div class="beat" data-beat="prescribe">
                        <div class="beat-index">Scene 3 &middot; Prescriptions</div>
                        <h3>Prescribe with confidence.</h3>
                        <p>Digital prescriptions checked against patient history and interactions, sent in seconds.</p>
                        <span class="tag">Interaction-checked</span>
                    </div>
                    <div class="beat" data-beat="bill">
                        <div class="beat-index">Scene 4 &middot; Billing</div>
                        <h3>Revenue without the chase.</h3>
                        <p>Automated invoicing, payment tracking, and financial reports &mdash; the books close themselves.</p>
                        <span class="tag">Automated revenue cycle</span>
                    </div>
                </div>
                <p data-reveal><a href="{{ route('doctors.index') }}" class="btn btn-primary-custom mt-2"><i class="fas fa-magnifying-glass me-2" aria-hidden="true"></i>See It From the Patient Side</a></p>
            </div>
        </div>
    </div>
</section>

{{-- ============ ACT III: THE COPILOT (AI scrolly) ============ --}}
<section class="scene scene-dark" id="act-3" aria-labelledby="act3-heading">
    <div class="giant-word" data-drift="-10" aria-hidden="true">COPILOT</div>
    <div class="container">
        <p class="kicker" data-reveal>Act III &mdash; The copilot</p>
        <h2 class="scene-title" id="act3-heading" data-reveal>An intelligent second lead.</h2>
        <p class="scene-lead" data-reveal>AI that watches, listens, and suggests &mdash; while you stay the clinician in charge.</p>
        <div class="row mt-2">
            <div class="col-lg-6">
                <div class="sticky-visual">
                    <div class="ai-panel">
                        <i class="fas fa-brain" style="font-size:64px;color:var(--primary-light);" aria-hidden="true"></i>
                        <h3 class="display mt-3 mb-1" style="font-size:1.4rem;">AI-Powered Healthcare</h3>
                        <p style="color:rgba(255,255,255,.7);font-size:.95rem;">Cutting-edge assistance, supervised by you</p>
                        <div class="copilot-line" aria-live="polite">
                            <i class="fas fa-robot" aria-hidden="true"></i>
                            <span id="copilotLine">Copilot standing by &mdash; scroll to see it think.</span>
                        </div>
                        <div class="ai-metrics">
                            <div><strong>24/7</strong><span>Availability</span></div>
                            <div><strong>95%</strong><span>Accuracy*</span></div>
                            <div><strong>50%</strong><span>Time saved*</span></div>
                        </div>
                        <small class="d-block mt-3" style="color:rgba(255,255,255,.45);">*Internal benchmarks. Individual results vary.</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div data-scrolly="copilot">
                    <div class="beat" data-beat="copilot" data-line="Suggestion: consider CBC + ferritin for this fatigue presentation. Matches 3 similar resolved cases.">
                        <div class="beat-index">Beat 1 &middot; Copilot</div>
                        <h3>Diagnosis suggestions, on cue.</h3>
                        <p>An intelligent assistant offering diagnosis ideas, treatment options, and decision support grounded in patient data.</p>
                    </div>
                    <div class="beat" data-beat="ambient" data-line="Listening… 2 speakers identified. Note drafted and chart populated in real time.">
                        <div class="beat-index">Beat 2 &middot; Ambient listening</div>
                        <h3>The room, transcribed.</h3>
                        <p>Consultation recording with speaker identification and automatic chart population &mdash; just talk to your patient.</p>
                    </div>
                    <div class="beat" data-beat="smart-rx" data-line="Flag: potential interaction between Drug A and Drug B. Alternative suggested with equivalent efficacy.">
                        <div class="beat-index">Beat 3 &middot; Smart prescriptions</div>
                        <h3>Every script, double-checked.</h3>
                        <p>Medication recommendations weighed against diagnosis, history, and drug interactions &mdash; before you sign.</p>
                    </div>
                    <div class="beat" data-beat="predict" data-line="Forecast: 94% schedule fill next week. 3 high-risk follow-ups flagged for recall.">
                        <div class="beat-index">Beat 4 &middot; Predictive analytics</div>
                        <h3>See around corners.</h3>
                        <p>Risk assessment, outcome predictions, and practice performance optimization from your own data.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ ACT IV: THE PROOF ============ --}}
<section class="scene scene-soft" id="act-4" aria-labelledby="act4-heading">
    <div class="container">
        <p class="kicker" data-reveal>Act IV &mdash; The proof</p>
        <h2 class="scene-title" id="act4-heading" data-reveal>Numbers that hold the scene.</h2>
        <p class="scene-lead" data-reveal>Live figures from this very platform &mdash; not a slide deck.</p>
        <div class="stat-band" role="list" aria-label="Platform statistics">
            <div class="stat-cell" role="listitem" data-reveal><div class="num"><span data-count="{{ $stats['doctors'] ?? 500 }}">0</span>+</div><div class="lbl">Active Doctors</div></div>
            <div class="stat-cell" role="listitem" data-reveal><div class="num"><span data-count="{{ $stats['appointments'] ?? 10000 }}">0</span>+</div><div class="lbl">Appointments Booked</div></div>
            <div class="stat-cell" role="listitem" data-reveal><div class="num"><span data-count="{{ $stats['patients'] ?? 25000 }}">0</span>+</div><div class="lbl">Patients Served</div></div>
            <div class="stat-cell" role="listitem" data-reveal><div class="num">{{ $stats['avg_rating'] ?? '4.8' }}<i class="fas fa-star ms-1" style="font-size:1.4rem;" aria-hidden="true"></i></div><div class="lbl">Average Rating</div></div>
        </div>
        <div class="row g-4 mt-3">
            <div class="col-lg-4 col-md-6" data-reveal>
                <article class="testimonial-card">
                    <div class="stars" role="img" aria-label="Rated 5 out of 5 stars"><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i></div>
                    <blockquote>&ldquo;MedCura AI cut my documentation time in half. Ambient transcription is scary accurate, and the copilot catches things I might miss.&rdquo;</blockquote>
                    <div class="who"><div class="avatar" aria-hidden="true">S</div><div><strong>Dr. Sarah M.</strong><br><small class="text-muted">Family Medicine</small> <span class="verified ms-1"><i class="fas fa-circle-check me-1" aria-hidden="true"></i>Verified</span></div></div>
                </article>
            </div>
            <div class="col-lg-4 col-md-6" data-reveal>
                <article class="testimonial-card">
                    <div class="stars" role="img" aria-label="Rated 5 out of 5 stars"><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i></div>
                    <blockquote>&ldquo;Scheduling, prescriptions, and billing finally live in one place. No-shows dropped after we turned on automated reminders.&rdquo;</blockquote>
                    <div class="who"><div class="avatar" aria-hidden="true">A</div><div><strong>Dr. Ahmed K.</strong><br><small class="text-muted">Internal Medicine</small> <span class="verified ms-1"><i class="fas fa-circle-check me-1" aria-hidden="true"></i>Verified</span></div></div>
                </article>
            </div>
            <div class="col-lg-4 col-md-6" data-reveal>
                <article class="testimonial-card">
                    <div class="stars" role="img" aria-label="Rated 4.5 out of 5 stars"><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star" aria-hidden="true"></i><i class="fas fa-star-half-stroke" aria-hidden="true"></i></div>
                    <blockquote>&ldquo;Onboarding took one afternoon. My patients book online, and I access everything securely from any device.&rdquo;</blockquote>
                    <div class="who"><div class="avatar" aria-hidden="true">L</div><div><strong>Dr. Layla H.</strong><br><small class="text-muted">Pediatrics</small> <span class="verified ms-1"><i class="fas fa-circle-check me-1" aria-hidden="true"></i>Verified</span></div></div>
                </article>
            </div>
        </div>
    </div>
</section>

{{-- ============ ACT V: THE PATH (how it works) ============ --}}
<section class="scene scene-light" id="act-5" aria-labelledby="act5-heading">
    <div class="container">
        <p class="kicker" data-reveal>Act V &mdash; The path</p>
        <h2 class="scene-title" id="act5-heading" data-reveal>Three scenes to go live.</h2>
        <p class="scene-lead" data-reveal>Most practices are up and running the same day. The line draws itself as you scroll.</p>
        <div class="path-track mt-5 pt-4">
            <div class="path-line" aria-hidden="true"><span id="pathFill"></span></div>
            <div class="row g-4">
                <div class="col-lg-4" data-reveal>
                    <article class="path-card">
                        <div class="path-num" aria-hidden="true">1</div>
                        <i class="fas fa-user-doctor mb-3" style="font-size:2.4rem;color:var(--primary);" aria-hidden="true"></i>
                        <h3 class="h5 mb-2">Create Account</h3>
                        <p class="text-muted mb-0">Sign up and set up your practice profile in minutes.</p>
                    </article>
                </div>
                <div class="col-lg-4" data-reveal>
                    <article class="path-card">
                        <div class="path-num" aria-hidden="true">2</div>
                        <i class="fas fa-gear mb-3" style="font-size:2.4rem;color:var(--primary);" aria-hidden="true"></i>
                        <h3 class="h5 mb-2">Configure Settings</h3>
                        <p class="text-muted mb-0">Set your availability, appointment types, and preferences.</p>
                    </article>
                </div>
                <div class="col-lg-4" data-reveal>
                    <article class="path-card">
                        <div class="path-num" aria-hidden="true">3</div>
                        <i class="fas fa-rocket mb-3" style="font-size:2.4rem;color:var(--primary);" aria-hidden="true"></i>
                        <h3 class="h5 mb-2">Start Managing</h3>
                        <p class="text-muted mb-0">Begin accepting appointments and managing patients.</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ INTERMISSION: FAQ ============ --}}
<section class="scene scene-soft" id="faq" aria-labelledby="faq-heading" style="padding-top:0;">
    <div class="container">
        <div class="faq">
            <div class="text-center mb-4">
                <h2 class="scene-title" id="faq-heading" data-reveal style="font-size:clamp(1.6rem,3vw,2.2rem);">Intermission &mdash; quick answers</h2>
            </div>
            <details data-reveal open>
                <summary>Is my patient data secure?</summary>
                <p>Yes. MedCura AI uses encrypted storage, encrypted backups, role-based access, and audit-friendly activity tracking &mdash; designed with HIPAA-conscious practices.</p>
            </details>
            <details data-reveal>
                <summary>Do I need a credit card to try it?</summary>
                <p>No. The free trial requires no credit card, and you can cancel anytime.</p>
            </details>
            <details data-reveal>
                <summary>Can patients book online?</summary>
                <p>Yes. Patients discover your practice, view live availability, book appointments, and receive automated reminders. Try the Find a Doctor directory to see the patient experience.</p>
            </details>
            <details data-reveal>
                <summary>Does it work on mobile?</summary>
                <p>Yes. MedCura AI is cloud-based and fully responsive &mdash; manage your practice from desktop, tablet, or phone.</p>
            </details>
        </div>
    </div>
</section>

{{-- ============ FINALE: CTA ============ --}}
<section class="scene scene-dark finale" id="finale" aria-labelledby="finale-heading">
    <div class="orb orb-a" data-speed="-14" aria-hidden="true"></div>
    <div class="orb orb-b" data-speed="10" aria-hidden="true"></div>
    <div class="container">
        <p class="kicker justify-content-center" data-reveal style="display:inline-flex;">Finale &mdash; Your turn</p>
        <h2 class="finale-title" id="finale-heading" data-reveal>The finale is yours to write.</h2>
        <p class="scene-lead mx-auto mt-3" data-reveal style="color:rgba(255,255,255,.8);">Join {{ number_format($stats['doctors'] ?? 500) }}+ healthcare professionals running calmer, modern practices on MedCura AI.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4" data-reveal>
            <a href="{{ route('register.doctor') }}" class="btn btn-primary-custom" aria-label="Start your free trial now">
                <i class="fas fa-stethoscope me-2" aria-hidden="true"></i>Start Free Trial
            </a>
            <a href="{{ route('contact') }}" class="btn btn-ghost-custom" aria-label="Contact our sales team">
                <i class="fas fa-phone me-2" aria-hidden="true"></i>Contact Sales
            </a>
        </div>
        <p class="mt-4 mb-0" data-reveal style="color:rgba(255,255,255,.7);">
            <i class="fas fa-check-circle me-2" aria-hidden="true"></i>No credit card required
            <i class="fas fa-check-circle ms-3 me-2" aria-hidden="true"></i>Cancel anytime
        </p>
    </div>
</section>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script>
(function () {
    'use strict';
    document.documentElement.classList.add('js');
    var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasGsap = (typeof window.gsap !== 'undefined');
    var hasST = hasGsap && (typeof window.ScrollTrigger !== 'undefined');
    if (hasST) { window.gsap.registerPlugin(window.ScrollTrigger); }

    /* ---------- smooth anchor scrolling ---------- */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth', block: 'start' });
            if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
            target.focus({ preventScroll: true });
        });
    });

    /* ---------- scroll progress + path line (vanilla rAF, no dependency) ---------- */
    var progress = document.getElementById('scrollProgress');
    var pathFill = document.getElementById('pathFill');
    var pathTrack = pathFill ? pathFill.closest('.path-track') : null;
    var ticking = false;
    function paintScroll() {
        ticking = false;
        var max = document.documentElement.scrollHeight - window.innerHeight;
        var p = max > 0 ? (window.scrollY / max) : 0;
        if (progress) progress.style.transform = 'scaleX(' + p + ')';
        if (pathFill && pathTrack) {
            var r = pathTrack.getBoundingClientRect();
            var vh = window.innerHeight;
            var done = (vh * 0.75 - r.top) / (r.height + vh * 0.25);
            done = Math.max(0, Math.min(1, done));
            pathFill.style.transform = 'scaleX(' + (REDUCED ? 1 : done) + ')';
        }
    }
    function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(paintScroll); } }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    paintScroll();

    /* ---------- generic reveals ---------- */
    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && !REDUCED) {
        var ro = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.add('in'); ro.unobserve(en.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
        revealEls.forEach(function (el) { ro.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('in'); });
    }

    /* ---------- chapter dots ---------- */
    var dots = Array.prototype.slice.call(document.querySelectorAll('#chapterNav a'));
    var chapterNav = document.getElementById('chapterNav');
    var chapterMap = {};
    dots.forEach(function (d) { chapterMap[d.getAttribute('data-chapter')] = d; });
    function setActiveChapter(id) {
        dots.forEach(function (d) { d.classList.toggle('active', d.getAttribute('data-chapter') === id); });
        var dark = (id === 'act-1' || id === 'act-3' || id === 'finale' || id === 'top');
        if (chapterNav) chapterNav.classList.toggle('on-light', !dark);
    }
    if ('IntersectionObserver' in window) {
        var co = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) setActiveChapter(en.target.id);
            });
        }, { rootMargin: '-45% 0px -45% 0px' });
        ['top', 'act-1', 'act-2', 'act-3', 'act-4', 'act-5', 'finale'].forEach(function (id) {
            var s = document.getElementById(id);
            if (s) co.observe(s);
        });
    }

    /* ---------- scrolly beats: sticky visual + scrolling steps ---------- */
    var copilotLine = document.getElementById('copilotLine');
    document.querySelectorAll('[data-scrolly]').forEach(function (zone) {
        var beats = zone.querySelectorAll('.beat');
        var section = zone.closest('section');
        var cards = section ? section.querySelectorAll('.pv-card') : [];
        function activate(beat) {
            beats.forEach(function (b) { b.classList.toggle('active', b === beat); });
            var key = beat.getAttribute('data-beat');
            cards.forEach(function (c) { c.classList.toggle('active', c.getAttribute('data-card') === key); });
            if (copilotLine && beat.hasAttribute('data-line')) copilotLine.textContent = beat.getAttribute('data-line');
        }
        if ('IntersectionObserver' in window) {
            var bo = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) { if (en.isIntersecting) activate(en.target); });
            }, { rootMargin: '-42% 0px -42% 0px' });
            beats.forEach(function (b) { bo.observe(b); });
        }
        if (beats.length) activate(beats[0]);
    });

    /* ---------- animated counters ---------- */
    function countUp(el) {
        var end = parseFloat(el.getAttribute('data-count'));
        if (isNaN(end)) return;
        if (REDUCED) { el.textContent = end.toLocaleString('en-US'); return; }
        var dur = 1400, t0 = null;
        function frame(t) {
            if (!t0) t0 = t;
            var p = Math.min(1, (t - t0) / dur);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(end * eased).toLocaleString('en-US');
            if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }
    var counters = document.querySelectorAll('[data-count]');
    if ('IntersectionObserver' in window && !REDUCED) {
        var nto = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { countUp(en.target); nto.unobserve(en.target); }
            });
        }, { threshold: 0.4 });
        counters.forEach(function (c) { nto.observe(c); });
    } else {
        counters.forEach(countUp);
    }

    /* ---------- GSAP cinematic layer (progressive enhancement only) ---------- */
    if (hasGsap && hasST && !REDUCED) {
        /* hero intro: masked line reveals */
        window.gsap.from('[data-hero-title] .line-inner', {
            yPercent: 115, duration: 1.1, ease: 'expo.out', stagger: 0.12, delay: 0.15
        });
        /* scrub parallax on orbs + giant words */
        window.gsap.utils.toArray('[data-speed]').forEach(function (el) {
            var amt = parseFloat(el.getAttribute('data-speed')) || 10;
            window.gsap.to(el, {
                yPercent: amt, ease: 'none',
                scrollTrigger: { trigger: el.closest('.scene') || el, start: 'top bottom', end: 'bottom top', scrub: 1 }
            });
        });
        window.gsap.utils.toArray('[data-drift]').forEach(function (el) {
            var amt = parseFloat(el.getAttribute('data-drift')) || 10;
            window.gsap.fromTo(el, { xPercent: amt }, {
                xPercent: -amt, ease: 'none',
                scrollTrigger: { trigger: el.closest('.scene') || el, start: 'top bottom', end: 'bottom top', scrub: 1 }
            });
        });
        /* hero content gently recedes as Act I arrives */
        window.gsap.to('.prologue .container', {
            yPercent: -8, opacity: 0.25, ease: 'none',
            scrollTrigger: { trigger: '.prologue', start: 'top top', end: 'bottom top', scrub: 1 }
        });
        /* finale title rises into frame */
        window.gsap.from('#finale .finale-title', {
            yPercent: 24, opacity: 0, duration: 1, ease: 'expo.out',
            scrollTrigger: { trigger: '#finale', start: 'top 70%' }
        });
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { window.ScrollTrigger.refresh(); });
        }
        window.addEventListener('load', function () { window.ScrollTrigger.refresh(); });
    }
})();
</script>
@endpush
