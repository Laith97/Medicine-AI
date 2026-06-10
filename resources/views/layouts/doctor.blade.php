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
    @if(Auth::user()->doctor)
    <meta name="doctor-id" content="{{ Auth::user()->doctor->id }}">
    @endif
    <meta name="notification-sound-enabled" content="true">
    <meta name="notification-toast-enabled" content="true">

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
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .doctor-content {
            flex: 1;
            margin-left: 280px;
            background: #f1f5f9;
            min-height: 100vh;
        }
        .sidebar-brand {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-align: center;
            background: rgba(0,0,0,0.2);
        }
        .sidebar-brand img { max-width: 90px; }
        .sidebar-nav { padding: 0.75rem 0; }
        .nav-item { margin: 0.2rem 0.6rem; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.7rem 1rem;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.25s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.08);
            transform: translateX(4px);
        }
        .nav-link.active {
            color: white;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        .nav-link i { width: 22px; margin-right: 0.85rem; text-align: center; font-size: 1rem; }
        .nav-section {
            padding: 1rem 1.25rem 0.5rem;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.35);
        }
        .nav-badge {
            margin-left: auto;
            background: rgba(255,255,255,0.15);
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .nav-link.active .nav-badge {
            background: rgba(255,255,255,0.25);
        }

        /* Quick Action Button */
        .quick-action {
            margin: 0.75rem 0.6rem;
        }
        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }
        .quick-action-btn i { margin-right: 0.5rem; }

        /* Divider */
        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 0.75rem 0.6rem;
        }

        .user-info {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.15);
        }
        .doctor-page { padding: 1.5rem 2rem; }
        .doctor-container { max-width: 1400px; margin: 0 auto; }

        /* Workflow indicator */
        .workflow-hint {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.4);
            padding: 0.5rem 1rem;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .doctor-sidebar { transform: translateX(-100%); }
            .doctor-sidebar.show { transform: translateX(0); }
            .doctor-content { margin-left: 0; }
        }

        /* Active state for AI pages */
        .nav-link.active-ai {
            color: white;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
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
                <small class="text-white-50 d-block mt-2" style="font-size: 0.75rem;">Doctor Panel</small>
            </div>

            <div class="sidebar-nav">
                <!-- Quick Action - Start Consultation -->
                <div class="quick-action">
                    <a href="{{ route('ai.ambient-listening.index') }}" class="quick-action-btn">
                        <i class="fas fa-microphone"></i>
                        Start Consultation
                    </a>
                </div>

                <div class="nav-section">Overview</div>
                <div class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-divider"></div>
                <div class="nav-section">Today's Work</div>
                <div class="nav-item">
                    <a href="{{ route('doctor.on-deck') }}" class="nav-link {{ request()->routeIs('doctor.on-deck') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Today's Queue</span>
                        @php
                            $todayAppointments = \App\Models\Appointment::where('doctor_id', auth()->id())
                                ->whereDate('appointment_date', now()->toDateString())
                                ->whereIn('status', ['confirmed', 'pending'])
                                ->count();
                        @endphp
                        @if($todayAppointments > 0)
                            <span class="nav-badge">{{ $todayAppointments }}</span>
                        @endif
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('ai.ambient-listening.index') }}" class="nav-link {{ request()->routeIs('ai.ambient-listening.index') ? 'active' : '' }}">
                        <i class="fas fa-microphone-alt"></i>
                        <span>Start Consultation</span>
                    </a>
                </div>

                <div class="nav-divider"></div>
                <div class="nav-section">Patient Management</div>
                <div class="nav-item">
                    <a href="{{ route('doctor.appointments.index') }}" class="nav-link {{ request()->routeIs('doctor.appointments.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-check"></i>
                        <span>All Appointments</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.patients.index') }}" class="nav-link {{ request()->routeIs('doctor.patients.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>My Patients</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.cases.overview') }}" class="nav-link {{ request()->routeIs('doctor.cases.*') ? 'active' : '' }}">
                        <i class="fas fa-folder-open"></i>
                        <span>Medical Records</span>
                        @php
                            $casesCount = \App\Models\Diagnosis::where('doctor_id', auth()->id())->count();
                        @endphp
                        @if($casesCount > 0)
                            <span class="nav-badge">{{ $casesCount }}</span>
                        @endif
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.appointments.create') }}" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Appointment</span>
                    </a>
                </div>

                <div class="nav-divider"></div>
                <div class="nav-section">Tools</div>
                <div class="nav-item">
                    <a href="{{ route('ai.ambient-listening.index') }}" class="nav-link {{ request()->routeIs('ai.ambient-listening.index') ? 'active' : '' }}">
                        <i class="fas fa-ear-listen"></i>
                        <span>Ambient Listening</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('ai.ambient-listening.recorded-voices') }}" class="nav-link {{ request()->routeIs('ai.ambient-listening.recorded-voices') ? 'active' : '' }}">
                        <i class="fas fa-history"></i>
                        <span>Consultation History</span>
                    </a>
                </div>

                <div class="nav-divider"></div>
                <div class="nav-section">Practice</div>
                <div class="nav-item">
                    <a href="{{ route('reviews.index') }}" class="nav-link {{ request()->routeIs('reviews.index') ? 'active' : '' }}">
                        <i class="fas fa-star-half-alt"></i>
                        <span>Reviews</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('doctor.blog.index') }}" class="nav-link {{ request()->routeIs('doctor.blog.*') ? 'active' : '' }}">
                        <i class="fas fa-newspaper"></i>
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
                    <div class="avatar-circle me-2">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div>
                        <div class="text-white" style="font-size: 0.9rem; font-weight: 500;">{{ Auth::user()->name ?? 'Doctor' }}</div>
                        <small class="text-white-50">{{ Auth::user()->doctor->specialty ?? 'Physician' }}</small>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light w-100">
                        <i class="fas fa-sign-out-alt me-1"></i> Sign Out
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
