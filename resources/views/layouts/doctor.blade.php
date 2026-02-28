<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description" content="MedCura AI - Medical Clinic Management System">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="user-role" content="{{ Auth::user()->role ?? 'user' }}">

    <!-- Font Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Montserrat:wght@400;700&family=Crete+Round:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('demos/medical/css/medical-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logo-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('favicon.ico') }}">

    <style>
        body, * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        }
        .fa, .fas, .far, .fab {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }
        .doctor-wrapper { display: flex; min-height: 100vh; }
        .doctor-sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .doctor-content {
            flex: 1;
            margin-left: 260px;
            background: #f5f6fa;
            min-height: 100vh;
        }
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-brand img { max-width: 100px; }
        .sidebar-nav { padding: 1rem 0; }
        .nav-item { margin: 0.15rem 0.5rem; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            color: white;
            background: rgba(59, 146, 246, 0.3);
        }
        .nav-link i { width: 24px; margin-right: 0.75rem; text-align: center; }
        .nav-section {
            padding: 0.5rem 1.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.4);
            margin-top: 1rem;
        }
        .user-info {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }
        .doctor-page { padding: 1.5rem 2rem; }
        .doctor-container { max-width: 1400px; margin: 0 auto; }
        @media (max-width: 768px) {
            .doctor-sidebar { transform: translateX(-100%); }
            .doctor-sidebar.show { transform: translateX(0); }
            .doctor-content { margin-left: 0; }
        }
    </style>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Doctor Dashboard | MedCura AI')</title>
</head>
<body>
    <div class="doctor-wrapper">
        <nav class="doctor-sidebar">
            <div class="sidebar-brand">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('demos/medical/images/logo-medical.png') }}" alt="MedCura AI">
                </a>
                <small class="text-white-50 d-block mt-2">Doctor Panel</small>
            </div>
            <div class="sidebar-nav">
                <div class="nav-section">Main</div>
                <div class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-section">Management</div>
                <div class="nav-item">
                    <a href="{{ route('doctor.appointments.index') }}" class="nav-link {{ request()->routeIs('doctor.appointments.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.patients.index') }}" class="nav-link {{ request()->routeIs('doctor.patients.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Patients</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.on-deck') }}" class="nav-link {{ request()->routeIs('doctor.on-deck') ? 'active' : '' }}">
                        <i class="fas fa-list-ol"></i>
                        <span>On-Deck</span>
                    </a>
                </div>
                <div class="nav-section">Practice</div>
                <div class="nav-item">
                    <a href="{{ route('reviews.index') }}" class="nav-link {{ request()->routeIs('reviews.index') ? 'active' : '' }}">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.blog.index') }}" class="nav-link {{ request()->routeIs('doctor.blog.*') ? 'active' : '' }}">
                        <i class="fas fa-blog"></i>
                        <span>Blog</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.chat.index') }}" class="nav-link {{ request()->routeIs('doctor.chat.*') ? 'active' : '' }}">
                        <i class="fas fa-comments"></i>
                        <span>Messages</span>
                    </a>
                </div>
            </div>
            <div class="user-info">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user-md me-2"></i>
                    <span>{{ Auth::user()->name ?? 'Doctor' }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light w-100">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </button>
                </form>
            </div>
        </nav>
        <main class="doctor-content">
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
