@extends('master')

@section('title', 'AI Medical Diagnosis - Smart Healthcare Solutions')

@push('styles')
<style>
.theme-primary { background-color: #DE6262 !important; }
.theme-primary-light { background-color: #E87777 !important; }
.theme-primary-dark { background-color: #C55555 !important; }
.theme-primary-darker { background-color: #B85555 !important; }
.text-theme-primary { color: #DE6262 !important; }
.border-theme-primary { border-color: #DE6262 !important; }
.btn-theme-primary { 
    background-color: #DE6262 !important; 
    border-color: #DE6262 !important; 
    color: white !important; 
}
.btn-theme-primary:hover { 
    background-color: #C55555 !important; 
    border-color: #C55555 !important; 
}
.btn-theme-outline { 
    background-color: transparent !important; 
    border-color: #DE6262 !important; 
    color: #DE6262 !important; 
}
.btn-theme-outline:hover { 
    background-color: #DE6262 !important; 
    color: white !important; 
}
.hero-gradient {
    background: linear-gradient(135deg, #DE6262 0%, #B85555 100%) !important;
}
.accent-color { color: #FFD700 !important; }
</style>
@endpush

@section('content')
		<!-- Hero Section -->
		<section id="hero" class="slider-element min-vh-75 d-flex align-items-center hero-gradient">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-lg-6">
						<div class="text-white">
							<h1 class="display-4 fw-bold mb-4" data-animate="fadeInUp">
								AI-Powered Medical <span class="accent-color">Diagnosis</span>
							</h1>
							<p class="lead mb-4" data-animate="fadeInUp" data-delay="200">
								Advanced artificial intelligence that assists doctors in diagnosing patient conditions with precision and speed. Submit patient information and receive comprehensive AI-generated medical insights.
							</p>
							<div data-animate="fadeInUp" data-delay="400">
								<a href="#diagnosis-form" class="btn btn-light btn-lg rounded-pill me-3 text-theme-primary fw-semibold shadow">
									Start Diagnosis
								</a>
								<a href="#how-it-works" class="btn btn-outline-light btn-lg rounded-pill fw-semibold shadow-sm">
									Learn More
								</a>
								
							</div>
						</div>
					</div>
					<div class="col-lg-6 text-center">
						<div class="position-relative" data-animate="fadeInRight">
							<i class="icon-medical-i-cardiology" style="font-size: 200px; color: rgba(255,255,255,0.1);"></i>
							<div class="position-absolute top-50 start-50 translate-middle">
								<i class="icon-medical-i-social-services text-white" style="font-size: 80px;"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Features Section -->
		<section id="features" class="py-5 bg-light">
			<div class="container">
				<div class="text-center mb-5">
					<h2 class="display-5 fw-bold">Why Choose Our AI Diagnosis System?</h2>
					<p class="lead text-muted">Cutting-edge technology meets medical expertise</p>
				</div>
				
				<div class="row g-4">
					<div class="col-md-4">
						<div class="feature-box text-center p-4 h-100 bg-white rounded shadow-sm">
							<div class="fbox-icon mb-3">
								<i class="icon-medical-i-neurology text-theme-primary" style="font-size: 3rem;"></i>
							</div>
							<h4>Advanced Analysis</h4>
							<p class="text-muted">Machine learning algorithms trained on vast medical datasets provide accurate diagnostic suggestions.</p>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="feature-box text-center p-4 h-100 bg-white rounded shadow-sm">
							<div class="fbox-icon mb-3">
								<i class="icon-medical-i-emergency text-theme-primary" style="font-size: 3rem;"></i>
							</div>
							<h4>Instant Results</h4>
							<p class="text-muted">Get comprehensive diagnostic insights within seconds of submitting patient information.</p>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="feature-box text-center p-4 h-100 bg-white rounded shadow-sm">
							<div class="fbox-icon mb-3">
								<i class="icon-medical-i-imaging-root-category text-theme-primary" style="font-size: 3rem;"></i>
							</div>
							<h4>HIPAA Compliant</h4>
							<p class="text-muted">All patient data is encrypted and handled according to medical privacy standards.</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- How It Works Section -->
		<section id="how-it-works" class="py-5">
			<div class="container">
				<div class="text-center mb-5">
					<h2 class="display-5 fw-bold">How It Works</h2>
					<p class="lead text-muted">Simple 3-step process for AI-powered diagnosis</p>
				</div>
				
				<div class="row g-4">
					<div class="col-md-4 text-center">
						<div class="step-number text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 theme-primary" style="width: 60px; height: 60px;">
							<span class="fw-bold">1</span>
						</div>
						<h4>Submit Patient Data</h4>
						<p class="text-muted">Enter patient symptoms, medical history, and relevant information through our secure form.</p>
					</div>
					
					<div class="col-md-4 text-center">
						<div class="step-number text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 theme-primary-light" style="width: 60px; height: 60px;">
							<span class="fw-bold">2</span>
						</div>
						<h4>AI Analysis</h4>
						<p class="text-muted">Our advanced AI processes the information and analyzes it against medical knowledge databases.</p>
					</div>
					
					<div class="col-md-4 text-center">
						<div class="step-number text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 theme-primary-dark" style="width: 60px; height: 60px;">
							<span class="fw-bold">3</span>
						</div>
						<h4>Get Results</h4>
						<p class="text-muted">Receive detailed diagnostic suggestions, potential conditions, and recommended next steps.</p>
					</div>
				</div>
			</div>
		</section>



		<!-- Statistics Section -->
	<!-- Statistics Section -->
<!-- Statistics Section -->
<section id="stats" class="py-5 text-white bg-dark">
	<div class="container">
		<div class="row text-center">
			<div class="col-md-3 mb-4">
				<div class="stat-item">
					<i class="icon-medical-i-cardiology mb-3 text-white" style="font-size: 3rem;"></i>
					<div class="counter h2 mb-2"><span data-from="0" data-to="15000" data-speed="2000">0</span></div>
					<h5 class="text-white">Diagnoses Generated</h5>
				</div>
			</div>
			<div class="col-md-3 mb-4">
				<div class="stat-item">
					<i class="icon-medical-i-imaging-root-category mb-3 text-white" style="font-size: 3rem;"></i>
					<div class="counter h2 mb-2"><span data-from="0" data-to="98" data-speed="2000">0</span>%</div>
					<h5 class="text-white">Accuracy Rate</h5>
				</div>
			</div>
			<div class="col-md-3 mb-4">
				<div class="stat-item">
					<i class="icon-medical-i-social-services mb-3 text-white" style="font-size: 3rem;"></i>
					<div class="counter h2 mb-2"><span data-from="0" data-to="500" data-speed="2000">0</span>+</div>
					<h5 class="text-white">Doctors Using</h5>
				</div>
			</div>
			<div class="col-md-3 mb-4">
				<div class="stat-item">
					<i class="icon-medical-i-emergency mb-3 text-white" style="font-size: 3rem;"></i>
					<div class="counter h2 mb-2"><span data-from="0" data-to="24" data-speed="1000">0</span>/7</div>
					<h5 class="text-white">Available</h5>
				</div>
			</div>
		</div>
	</div>
</section>



		<!-- CTA Section -->
		<section id="cta" class="py-5">
			<div class="container text-center">
				<h2 class="display-5 fw-bold mb-4">Ready to Experience AI-Powered Diagnosis?</h2>
				<p class="lead text-muted mb-4">Join hundreds of medical professionals who trust our AI system for accurate diagnostic assistance.</p>
				<a href="#diagnosis-form" class="button button-large button-rounded btn-theme-primary me-3">
					Start Free Trial
				</a>
				<a href="{{ route('contact') }}" class="button button-border button-large button-rounded btn-theme-outline">
					Contact Us
				</a>
			</div>
		</section>


		<script>
			// Smooth scrolling for anchor links
			document.querySelectorAll('a[href^="#"]').forEach(anchor => {
				anchor.addEventListener('click', function (e) {
					e.preventDefault();
					const target = document.querySelector(this.getAttribute('href'));
					if (target) {
						target.scrollIntoView({
							behavior: 'smooth',
							block: 'start'
						});
					}
				});
			});

			// Form submission handling
			document.getElementById('ai-diagnosis-form').addEventListener('submit', function(e) {
				e.preventDefault();
				
				// Show loading state
				const submitBtn = this.querySelector('button[type="submit"]');
				const originalText = submitBtn.innerHTML;
				submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Analyzing...';
				submitBtn.disabled = true;
				
				// Simulate API call (replace with actual form submission)
				setTimeout(() => {
					// Reset button
					submitBtn.innerHTML = originalText;
					submitBtn.disabled = false;
					
					// Show success message or redirect
					alert('Diagnosis request submitted! You will receive results shortly.');
				}, 3000);
			});
		</script>
@endsection