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
<div id="top-bar" class="bg-light py-2 border-bottom" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row justify-content-between align-items-center">

            <!-- Left Side: Contact Info -->
            <div class="col-md-auto d-none d-md-flex align-items-center gap-4 text-muted small">
                <div><i class="bi bi-clock me-1"></i> Mon–Fri: 9am–6pm</div>
                <div><i class="bi bi-telephone me-1"></i> +1-800-9876-221</div>
                <div><i class="bi bi-envelope me-1"></i> <a href="mailto:medical@canvas.com" class="text-decoration-none text-muted">medical@canvas.com</a></div>
            </div>

            <!-- Right Side: Auth + Appointment -->
            <div class="col-md-auto d-flex justify-content-end align-items-center gap-3">

                @auth
                <div class="dropdown">
                    <a class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="background: linear-gradient(90deg, #DE6262 0%, #FFB88C 100%); color: #fff; border: none; font-weight: 500; box-shadow: 0 2px 6px rgba(222,98,98,0.15);">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">

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
            style="background: #DE6262; color: #fff; border: none; font-weight: 500; border-radius: 30px; height: 38px; line-height: 24px;">
             <i class="bi bi-box-arrow-in-right me-1"></i> Login
         </a>
         <a href="{{ route('register') }}"
            class="btn btn-sm px-4"
            style="background: #fff; color: #DE6262; border: 2px solid #DE6262; font-weight: 500; border-radius: 30px; height: 38px; line-height: 24px;">
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
 <!-- Footer -->
<footer id="footer" class="bg-dark text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="text-white">AI Medical Diagnosis</h5>
                <p class="text-white">Revolutionizing healthcare with artificial intelligence. Providing doctors with powerful diagnostic tools for better patient care.</p>
                <div class="social-links">
                    <a href="#" class="text-white me-3" style="opacity: 0.9;"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="text-white me-3" style="opacity: 0.9;"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="text-white me-3" style="opacity: 0.9;"><i class="fa-brands fa-linkedin"></i></a>
                </div>
            </div>
            <div class="col-md-3">
                <h6 class="text-white">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white text-decoration-none">Home</a></li>
                    <li><a href="#how-it-works" class="text-white text-decoration-none">How It Works</a></li>
                    <li><a href="#diagnosis-form" class="text-white text-decoration-none">Start Diagnosis</a></li>
                    <li><a href="{{ route('about') }}" class="text-white text-decoration-none">About</a></li>
                    <li><a href="{{ route('contact') }}" class="text-white text-decoration-none">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-white">Contact Info</h6>
                <p class="text-white mb-2">
                    <i class="icon-mail me-2"></i>
                    support@aimedicaldiagnosis.com
                </p>
                <p class="text-white mb-2">
                    <i class="icon-phone me-2"></i>
                    +1 (555) 123-4567
                </p>
                <p class="text-white">
                    <i class="icon-clock me-2"></i>
                    24/7 AI Support Available
                </p>
            </div>
        </div>
        <hr class="border-light my-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-white mb-0">&copy; {{ date('Y') }} AI Medical Diagnosis. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="#" class="text-white text-decoration-none me-3">Privacy Policy</a>
                <a href="#" class="text-white text-decoration-none">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

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