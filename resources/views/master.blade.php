<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description" content="Create Medical Clinic & Hospital Websites with Canvas Template. Get Canvas to build powerful websites easily with the Highly Customizable & Best Selling Bootstrap Template, today.">
	<meta name="csrf-token" content="{{ csrf_token() }}">
<style>.top-link {
    font-size: 16px;
    font-weight: 500;
    text-decoration: none;
    color: #333; /* dark gray */
    position: relative;
    padding: 5px 8px;
    transition: color 0.3s ease;
}

.top-link:hover {
    color: #007bff; /* blue on hover */
}

/* Add nice underline on hover */
.top-link::after {
    content: '';
    display: block;
    width: 0;
    height: 2px;
    background: #007bff;
    transition: width 0.3s;
    position: absolute;
    bottom: 0;
    left: 0;
}

.top-link:hover::after {
    width: 100%;
}
.dropdown-item:focus, .dropdown-item:active, 
.dropdown-item:focus-visible, 
.dropdown-item:focus-within {
    outline: none !important;
    box-shadow: 0 0 0 0.2rem rgba(222,98,98,0.25) !important;
    background-color: rgba(222,98,98,0.08) !important;
    color: #DE6262 !important;
}

</style>
    <!-- Font Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Montserrat:wght@400;700&family=Crete+Round:ital@0;1&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('demos/medical/css/medical-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper.css') }}">
    <link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    @stack('styles')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Medical Demo | Canvas')</title>
</head>
<body class="stretched page-transition" data-loader-html="<div id='css3-spinner-svg-pulse-wrapper'><svg id='css3-spinner-svg-pulse' version='1.2' height='210' width='550' xmlns='https://www.w3.org/2000/svg' viewport='0 0 60 60' xmlns:xlink='https://www.w3.org/1999/xlink'><path id='css3-spinner-pulse' stroke='#DE6262' fill='none' stroke-width='2' stroke-linejoin='round' d='M0,90L250,90Q257,60 262,87T267,95 270,88 273,92t6,35 7,-60T290,127 297,107s2,-11 10,-10 1,1 8,-10T319,95c6,4 8,-6 10,-17s2,10 9,11h210'></svg></div>">

    <!-- Wrapper -->
    <div id="wrapper">

<!-- Top Bar Start -->
<div id="top-bar" class="py-2 border-bottom" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white;">
    <div class="container">
        <div class="row justify-content-between align-items-center">

            <!-- Left Side: Quick Info & Status -->
            <div class="col-md-auto d-none d-md-flex align-items-center gap-4 small">
                <div class="d-flex align-items-center">
                    <div class="status-indicator bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                    <span><i class="bi bi-shield-check me-1"></i> AI System Online</span>
                </div>
                <div><i class="bi bi-cpu me-1"></i> Advanced Diagnostics Available</div>
                <div><i class="bi bi-envelope me-1"></i> <a href="mailto:support@aimedical.com" class="text-decoration-none text-white-50">support@aimedical.com</a></div>
            </div>

            <!-- Right Side: Auth + Quick Actions -->
            <div class="col-md-auto d-flex justify-content-end align-items-center gap-3">

                @auth
                <!-- Quick Action Button for Emergency -->
                <a href="{{ route('ask-openai') }}" class="btn btn-sm px-3 py-1 me-2" 
                   style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; font-size: 12px;">
                    <i class="bi bi-lightning-charge me-1"></i> Quick Diagnosis
                </a>
                
                <div class="dropdown">
                    <a class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @if(Auth::user()->isAdmin())
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-shield-check"></i> Admin Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-people"></i> Manage Users
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('settings') }}">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        
            @guest
            <a href="{{ route('login') }}"
               class="btn btn-sm px-4"
               style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </a>
            <a href="{{ route('register') }}"
               class="btn btn-sm px-4"
               style="background: white; color: #DE6262; border: none; font-weight: 500; border-radius: 25px;">
                <i class="bi bi-person-plus me-1"></i> Register
            </a>
            @endguest

            </div>
        </div>
    </div>
</div>
<!-- Top Bar End -->

		<!-- Header
		============================================= -->
		<header id="header">
			<div id="header-wrap">
				<div class="container">
					<div class="header-row">

						<!-- Logo
						============================================= -->
						<div id="logo">
							<a href="{{ url('/') }}">
								<img class="logo-default" srcset="demos/medical/images/logo-medical.png, demos/medical/images/logo-medical@2x.png 2x" src="demos/medical/images/logo-medical@2x.png" alt="Canvas Logo">
							</a>
						</div><!-- #logo end -->

						<div class="primary-menu-trigger">
							<button class="cnvs-hamburger" type="button" title="Open Mobile Menu">
								<span class="cnvs-hamburger-box"><span class="cnvs-hamburger-inner"></span></span>
							</button>
						</div>

						<!-- Primary Navigation
						============================================= -->
						<nav class="primary-menu style-3 menu-spacing-margin">
                            <ul class="menu-container">
                                @auth
                                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ route('dashboard') }}"><div>Dashboard</div></a>
                                    </li>
                                    <li class="menu-item {{ request()->routeIs('ask-openai') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ route('ask-openai') }}"><div>Patients</div></a>
                                    </li>
                                    <li class="menu-item {{ request()->routeIs('cases') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ route('cases') }}"><div>Cases</div></a>
                                    </li>
                                @endauth
                            
                                @guest
                                    <li class="menu-item {{ request()->is('/') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ url('/') }}"><div>Home</div></a>
                                    </li>
                                    <li class="menu-item {{ request()->is('about') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ route('about') }}"><div>About Us</div></a>
                                    </li>
                                    <li class="menu-item {{ request()->is('contact') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ route('contact') }}"><div>Contact</div></a>
                                    </li>
                                @endguest
                            </ul>
                            

						</nav><!-- #primary-menu end -->

					</div>
				</div>
			</div>
			<div class="header-wrap-clone"></div>
		</header><!-- #header end -->



        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

    </div><!-- #wrapper end -->
			</div>

		<!-- Footer -->
<footer id="footer" class="text-white py-5" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
    <div class="container">
        <div class="row g-4">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand mb-4">
                    <h4 class="text-white mb-3" style="color: #DE6262 !important;">
                        <i class="bi bi-heart-pulse me-2" style="color: #DE6262;"></i>
                        AI Medical Diagnosis
                    </h4>
                    <p class="text-white-50 mb-4">Revolutionizing healthcare with cutting-edge artificial intelligence. Empowering medical professionals with advanced diagnostic tools for superior patient care and outcomes.</p>
                    
                    <!-- Social Links -->
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2" style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2" style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2" style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2" style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                            <i class="bi bi-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white mb-3" style="color: #DE6262 !important;">Platform</h6>
                <ul class="list-unstyled footer-links">
                    @auth
                        <li class="mb-2"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none hover-link">Dashboard</a></li>
                        <li class="mb-2"><a href="{{ route('ask-openai') }}" class="text-white-50 text-decoration-none hover-link">AI Diagnosis</a></li>
                        <li class="mb-2"><a href="{{ route('cases') }}" class="text-white-50 text-decoration-none hover-link">Case Studies</a></li>
                        <li class="mb-2"><a href="{{ route('settings') }}" class="text-white-50 text-decoration-none hover-link">Settings</a></li>
                    @else
                        <li class="mb-2"><a href="{{ url('/') }}" class="text-white-50 text-decoration-none hover-link">Home</a></li>
                        <li class="mb-2"><a href="{{ route('about') }}" class="text-white-50 text-decoration-none hover-link">About Us</a></li>
                        <li class="mb-2"><a href="{{ route('contact') }}" class="text-white-50 text-decoration-none hover-link">Contact</a></li>
                        <li class="mb-2"><a href="{{ route('login') }}" class="text-white-50 text-decoration-none hover-link">Login</a></li>
                    @endauth
                </ul>
            </div>

            <!-- Resources -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white mb-3" style="color: #DE6262 !important;">Support</h6>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2"><a href="{{ route('about') }}" class="text-white-50 text-decoration-none hover-link">About Platform</a></li>
                    <li class="mb-2"><a href="{{ route('contact') }}" class="text-white-50 text-decoration-none hover-link">Contact Support</a></li>
                    @auth
                        <li class="mb-2"><a href="{{ route('profile.edit') }}" class="text-white-50 text-decoration-none hover-link">Profile Settings</a></li>
                    @endauth
                </ul>
            </div>

            <!-- Contact & Support -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white mb-3" style="color: #DE6262 !important;">Contact & Support</h6>
                
                <div class="contact-info mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-icon me-3" style="width: 40px; height: 40px; background: rgba(222,98,98,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-envelope" style="color: #DE6262;"></i>
                        </div>
                        <div>
                            <small class="text-white-50 d-block">Email Support</small>
                            <a href="mailto:support@aimedical.com" class="text-white text-decoration-none">support@aimedical.com</a>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-icon me-3" style="width: 40px; height: 40px; background: rgba(222,98,98,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-headset" style="color: #DE6262;"></i>
                        </div>
                        <div>
                            <small class="text-white-50 d-block">24/7 Support</small>
                            <span class="text-white">AI-Powered Help Available</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="contact-icon me-3" style="width: 40px; height: 40px; background: rgba(222,98,98,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-shield-check" style="color: #DE6262;"></i>
                        </div>
                        <div>
                            <small class="text-white-50 d-block">Security & Privacy</small>
                            <span class="text-white">HIPAA Compliant Platform</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Contact -->
                <div class="quick-contact">
                    <h6 class="text-white mb-2">Need Help?</h6>
                    <p class="text-white-50 small mb-3">Our AI-powered support is here to assist you</p>
                    <a href="{{ route('contact') }}" class="btn btn-sm" style="background: #DE6262; color: white; border: none; border-radius: 25px;">
                        <i class="bi bi-chat-dots me-2"></i>Contact Support
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <hr class="my-4" style="border-color: rgba(222,98,98,0.2);">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-white-50 mb-0">
                    &copy; {{ date('Y') }} AI Medical Diagnosis Platform. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-legal-links">
                    <span class="text-white-50 me-3">Secure & HIPAA Compliant</span>
                    <a href="{{ route('contact') }}" class="text-white-50 text-decoration-none hover-link">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.hover-link:hover {
    color: #DE6262 !important;
    transition: color 0.3s ease;
}

.social-links a:hover {
    background-color: #DE6262 !important;
    border-color: #DE6262 !important;
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.newsletter-signup input::placeholder {
    color: rgba(255,255,255,0.6);
}

.newsletter-signup input:focus {
    background: rgba(255,255,255,0.15);
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222,98,98,0.25);
    color: white;
}
</style>

	</div><!-- #wrapper end -->

	<!-- Go To Top
	============================================= -->
	<div id="gotoTop" class="uil uil-angle-up rounded-circle"></div>

	<!-- JavaScripts
	============================================= -->
    <script src="{{ asset('js/plugins.min.js') }}"></script>
    <script src="{{ asset('js/functions.bundle.js') }}"></script>
    
    @stack('scripts')

</body>
</html>