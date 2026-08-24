@extends('master')

@section('title', 'Contact Us - MedCura Clinical Platform')

@push('styles')
<style>
.hero-contact{background:linear-gradient(180deg, rgba(255,255,255,0.82) 0%, rgba(248,250,252,0.88) 100%), url('{{ asset('demos/medical/images/contact/page-title.jpg') }}') center/cover no-repeat;border-bottom:1px solid #e2e8f0;padding:3rem 0 2.5rem;box-shadow:0 1px 3px rgba(15,23,42,0.04)}
.hero-contact h1{font-size:1.9rem;font-weight:800;color:#0f172a;letter-spacing:-0.02em;margin:0 0 0.5rem}
.hero-contact p{font-size:0.9rem;color:#475569;line-height:1.6;max-width:600px}
.contact-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.contact-card h3{font-size:1.1rem;font-weight:600;color:#0f172a;margin:0 0 0.35rem}
.contact-card .sub{font-size:0.84rem;color:#64748b;margin:0 0 1.25rem}
.form-label{font-size:0.84rem;font-weight:500;color:#334155;margin-bottom:0.35rem}
.form-control,.form-select{border:1px solid #e2e8f0 !important;border-radius:8px !important;padding:0.6rem 0.85rem !important;font-size:0.875rem !important}
.form-control:focus,.form-select:focus{border-color:#DE6262 !important;box-shadow:0 0 0 3px rgba(222,98,98,0.12) !important}
.btn-send{background:#0f172a;border:1px solid #0f172a;color:#ffffff;border-radius:8px;padding:0.65rem 1.25rem;font-weight:600;font-size:0.875rem}
.btn-send:hover{background:#1e293b;border-color:#1e293b;color:#ffffff}
.info-highlight{background:#0f172a;border-radius:12px;padding:1.5rem;color:#ffffff;position:relative;overflow:hidden}
.info-highlight::after{content:'';position:absolute;top:-30px;right:-30px;width:160px;height:160px;background:radial-gradient(circle, rgba(222,98,98,0.15) 0%, transparent 70%)}
.info-item{display:flex;gap:0.75rem;margin-bottom:1rem}
.info-icon{width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;color:#ffffff;flex-shrink:0}
</style>
@endpush

@section('content')
<section class="hero-contact">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div style="display:inline-flex;align-items:center;gap:0.4rem;background:#ffffff;border:1px solid #e2e8f0;border-radius:99px;padding:0.3rem 0.75rem;font-size:0.72rem;font-weight:600;color:#475569"><span style="width:6px;height:6px;border-radius:50%;background:#10b981"></span> Response in 2 hours</div>
                <h1>Talk to our clinical team</h1>
                <p>Questions on AI diagnosis, voice assistance or patient growth? We answer with clinicians, not bots.</p>
                <div class="d-flex gap-4 mt-3" style="font-size:0.82rem;color:#475569">
                    <span class="d-flex align-items-center gap-1"><i class="fas fa-shield-halved" style="color:#10b981"></i> HIPAA</span>
                    <span class="d-flex align-items-center gap-1"><i class="fas fa-lock" style="color:#10b981"></i> Encrypted</span>
                    <span class="d-flex align-items-center gap-1"><i class="fas fa-clock" style="color:#94a3b8"></i> 24/7</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="info-highlight">
                    <h5 style="font-size:1rem;font-weight:600;margin:0 0 1rem;position:relative;color:#ffffff">Direct contacts</h5>
                    <div class="info-item"><div class="info-icon"><i class="fas fa-envelope"></i></div><div><div style="font-size:0.82rem;color:#94a3b8">Email</div><div style="font-size:0.9rem;font-weight:600">info@medcuraai.com</div></div></div>
                    <div class="info-item"><div class="info-icon"><i class="fas fa-headset"></i></div><div><div style="font-size:0.82rem;color:#94a3b8">Support</div><div style="font-size:0.9rem;font-weight:600">AI assistance · Clinical help</div></div></div>
                    <div class="info-item mb-0"><div class="info-icon"><i class="fas fa-brain"></i></div><div><div style="font-size:0.82rem;color:#94a3b8">Expertise</div><div style="font-size:0.9rem;font-weight:600">Medical + AI specialists</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section style="background:#f8fafc;padding:2rem 0 3rem">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="contact-card">
                    <h3>Send a message</h3>
                    <p class="sub">We typically reply within 2 hours during business hours.</p>
                    @if(session('success'))<div class="alert" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:8px">{{ session('success') }}</div>@endif
                    <form method="post" action="{{ route('contact.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                                @error('name')<div style="font-size:0.78rem;color:#ef4444;margin-top:0.25rem">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                                @error('email')<div style="font-size:0.78rem;color:#ef4444;margin-top:0.25rem">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Topic</label>
                                <select name="service" class="form-select">
                                    <option value="">Select one</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="AI Diagnosis Support">AI Diagnosis</option>
                                    <option value="Voice Assistant Help">Voice Assistant</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Demo Request">Demo Request</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" value="{{ old('subject') }}" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message *</label>
                                <textarea name="message" rows="5" class="form-control" required placeholder="How can we help?">{{ old('message') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-send">Send message</button>
                                <span style="font-size:0.78rem;color:#94a3b8;margin-left:0.75rem">Encrypted & HIPAA compliant</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-column gap-3">
                    <div class="contact-card" style="text-align:center;padding:2rem 1.5rem">
                        <span class="d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;border-radius:10px;background:rgba(222,98,98,0.08);color:#DE6262;border:1px solid rgba(222,98,98,0.15)"><i class="fas fa-clock"></i></span>
                        <h5 class="mt-3" style="font-size:0.95rem;font-weight:600;color:#0f172a">Fast response</h5>
                        <p style="font-size:0.84rem;color:#64748b;margin:0">Average first reply under 2 hours on weekdays.</p>
                    </div>
                    <div class="contact-card" style="display:flex;gap:0.75rem;align-items:center">
                        <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569"><i class="fas fa-shield-alt"></i></span>
                        <div><div style="font-size:0.84rem;font-weight:600;color:#0f172a">Privacy first</div><div style="font-size:0.78rem;color:#64748b">All data encrypted, HIPAA compliant.</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
