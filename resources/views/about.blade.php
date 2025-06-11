@extends('master')

@section('title', 'About Us')

@section('content')

<!-- Hero Section with Image Slider -->
<section class="page-title dark page-title-center p-0 position-relative" style="min-height: 350px; overflow: hidden;">
    <div class="fslider" data-arrows="false" data-pagi="false" data-animation="fade" data-hover="false">
        <div class="flexslider">
            <div class="slider-wrap">
                <div class="slide"><img src="demos/medical/images/about-us/page-title/1.jpg" alt="Page Title Image" style="width:100%;height:350px;object-fit:cover;"></div>
                <div class="slide"><img src="demos/medical/images/about-us/page-title/2.jpg" alt="Page Title Image" style="width:100%;height:350px;object-fit:cover;"></div>
                <div class="slide"><img src="demos/medical/images/about-us/page-title/3.jpg" alt="Page Title Image" style="width:100%;height:350px;object-fit:cover;"></div>
                <div class="slide"><img src="demos/medical/images/about-us/page-title/4.jpg" alt="Page Title Image" style="width:100%;height:350px;object-fit:cover;"></div>
            </div>
            <div class="vertical-middle vertical-middle-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(224,234,252,0.7);">
                <div class="container py-5">
                    <div class="page-title-row text-center">
                        <div class="page-title-content">
                            <h1 class="display-4 fw-bold mb-2" style="color: #1b1b18;">{{ $aboutTitle }}</h1>
                            <span class="lead" style="color: #444;">{{ $aboutTagline }}</span>
                        </div>
                        <nav aria-label="breadcrumb" class="mt-3">
                            <ol class="breadcrumb justify-content-center">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">About Us</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5" style="background: #f8f9fa;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Key Features</h2>
            <p class="text-muted">Discover what makes our platform unique for doctors and patients.</p>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach($features as $feature)
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="{{ $feature['icon'] }}" style="font-size: 2.5rem; color: #DE6262;"></i>
                        </div>
                        <h5 class="card-title fw-semibold">{{ $feature['title'] }}</h5>
                        <p class="card-text text-muted">{{ $feature['description'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Counters Section -->
<section class="py-5" style="background: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm bg-white">
                    <i class="i-plain i-xlarge icon-medical-i-surgery mb-2" style="color: #DE6262;"></i>
                    <h2 class="fw-bold mb-0">42,762+</h2>
                    <p class="mb-0 text-muted">Treatments Made</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm bg-white">
                    <i class="i-plain i-xlarge icon-medical-i-respiratory mb-2" style="color: #DE6262;"></i>
                    <h2 class="fw-bold mb-0">21,500+</h2>
                    <p class="mb-0 text-muted">Cured Patients</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm bg-white">
                    <i class="i-plain i-xlarge icon-medical-i-social-services mb-2" style="color: #DE6262;"></i>
                    <h2 class="fw-bold mb-0">408K</h2>
                    <p class="mb-0 text-muted">Satisfied Customers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm bg-white">
                    <i class="i-plain i-xlarge icon-medical-i-ambulance mb-2" style="color: #DE6262;"></i>
                    <h2 class="fw-bold mb-0">140</h2>
                    <p class="mb-0 text-muted">Ambulance Available</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What We Do Section -->
<section class="py-5" style="background: #fff;">
    <div class="container">
        <div class="row align-items-center justify-content-between">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="mb-4">
                    <h2 class="fw-bold mb-3">{{ $whatWeDoTitle }}</h2>
                    <p class="lead text-muted">{{ $whatWeDoDescription }}</p>
                </div>
                <div class="row g-3">
                    @foreach($whatWeDoFeatures as $wwdFeature)
                    <div class="col-12">
                        <div class="d-flex align-items-start">
                            <i class="{{ $wwdFeature['icon'] }} me-3" style="font-size: 1.5rem; color: #DE6262;"></i>
                            <span class="text-muted">{{ $wwdFeature['description'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-5">
                <!-- How It Works Section (Stepper Style) -->
                <div class="how-it-works p-4 rounded shadow-sm bg-white mb-4">
                    <div class="heading-block mb-3 border-bottom-0 text-center">
                        <h4 class="fw-bold mb-2" style="color: #DE6262;"><i class="icon-line-clipboard me-2"></i>How It Works</h4>
                        <span class="text-muted">A simple, guided process for doctors</span>
                    </div>
                    <div class="row text-center align-items-center justify-content-center g-0 mb-3">
                        <div class="col-3 col-md-3">
                            <div class="step-circle mx-auto mb-2" style="background:#DE6262;width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;"><i class="icon-user"></i></div>
                            <div class="fw-semibold">Login</div>
                            <div class="small text-muted">Access your account</div>
                        </div>
                        <div class="col-1 d-none d-md-block"><div style="height:2px;width:100%;background:#DE6262;margin:0 0.5rem;"></div></div>
                        <div class="col-3 col-md-3">
                            <div class="step-circle mx-auto mb-2" style="background:#DE6262;width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;"><i class="icon-edit"></i></div>
                            <div class="fw-semibold">Fill Form</div>
                            <div class="small text-muted">Enter patient data</div>
                        </div>
                        <div class="col-1 d-none d-md-block"><div style="height:2px;width:100%;background:#DE6262;margin:0 0.5rem;"></div></div>
                        <div class="col-3 col-md-3">
                            <div class="step-circle mx-auto mb-2" style="background:#DE6262;width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;"><i class="icon-line-paper-plane"></i></div>
                            <div class="fw-semibold">Submit</div>
                            <div class="small text-muted">Send for AI analysis</div>
                        </div>
                        <div class="col-1 d-none d-md-block"><div style="height:2px;width:100%;background:#DE6262;margin:0 0.5rem;"></div></div>
                        <div class="col-3 col-md-3">
                            <div class="step-circle mx-auto mb-2" style="background:#DE6262;width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;"><i class="icon-medical-i-bar-graph"></i></div>
                            <div class="fw-semibold">Get Results</div>
                            <div class="small text-muted">View instant diagnosis</div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="/login" class="btn btn-lg rounded-pill px-4 shadow" style="background:#DE6262;color:#fff;border:none;">Start Diagnosis</a>
                    </div>
                </div>
                <!-- Core Principles Section -->
                <div class="core-principles p-4 rounded shadow-sm bg-white mt-4">
                    <div class="heading-block mb-3 border-bottom-0">
                        <h4 class="fw-bold mb-2"><i class="icon-line-heart me-2" style="color: #DE6262;"></i>Core Principles</h4>
                        <span class="text-muted">What drives Choose Wisely for Doctors</span>
                    </div>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="icon-line-check text-success me-2"></i> Evidence-based practice</li>
                        <li class="mb-2"><i class="icon-line-check text-success me-2"></i> Patient-centered care</li>
                        <li class="mb-2"><i class="icon-line-check text-success me-2"></i> Reducing unnecessary interventions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
