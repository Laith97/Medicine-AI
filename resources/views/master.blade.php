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

                <!-- Auth Links -->
                <div class="dropdown">
                    @auth
                        <a class="btn btn-sm btn-outline-danger text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #DE6262;">
                            👋 {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('settings') }}">Settings</a></li> <!-- Settings link added -->
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-danger text-white" style="border-color: #DE6262; background-color: #DE6262;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-sm btn-outline-danger text-white" style="border-color: #DE6262; background-color: #DE6262;">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    @endguest
                </div>

                <!-- Book Appointment -->
                <a href="{{ route('contact') }}" class="btn btn-sm" style="background-color: #DE6262; color: white; border-radius: 30px;">
                    <i class="bi bi-calendar-check me-1"></i> Book Appointment
                </a>

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
                                <li class="menu-item {{ request()->is('/') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ url('/') }}"><div>Home</div></a>
                                </li>
                                <li class="menu-item {{ request()->is('about') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('about') }}"><div>About Us</div></a>
                                </li>
                                @auth
                                <li class="menu-item {{ request()->routeIs('ask-openai') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('ask-openai') }}"><div>Patients</div></a>
                                </li>
                            @endauth
                            
                                <li class="menu-item {{ request()->routeIs('cases') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('cases') }}"><div>Cases</div></a>
                                </li>
                                <li class="menu-item {{ request()->is('contact') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('contact') }}"><div>Contact</div></a>
                                </li>
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

			<!-- Copyrights
			============================================= -->
			<div id="copyrights" class="bg-transparent">
				<div class="container">

					<div class="row col-mb-30">
						<div class="col-md-6 text-center text-md-start">
							Copyrights &copy; 2023 All Rights Reserved by Canvas Inc.<br>
							<div class="copyright-links"><a href="#">Terms of Use</a> / <a href="#">Privacy Policy</a></div>
						</div>

						<div class="col-md-6 text-center text-md-end">
							<div class="copyrights-menu copyright-links">
								<a href="#">Home</a>/<a href="#">About Us</a>/<a href="#">Team</a>/<a href="#">Clients</a>/<a href="#">FAQs</a>/<a href="#">Contact</a>
							</div>
						</div>
					</div>

				</div>
			</div><!-- #copyrights end -->
		</footer><!-- #footer end -->

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