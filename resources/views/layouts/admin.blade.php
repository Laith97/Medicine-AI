<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description" content="Create Medical Clinic & Hospital Websites with Canvas Template. Get Canvas to build powerful websites easily with the Highly Customizable & Best Selling Bootstrap Template, today.">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<!-- Notification meta tags -->
	<meta name="user-id" content="{{ Auth::id() }}">
	<meta name="user-role" content="{{ Auth::user()->role ?? 'user' }}">
	<meta name="notification-sound-enabled" content="{{ config('app.env') === 'local' ? 'true' : 'true' }}">
	<meta name="notification-toast-enabled" content="{{ config('app.env') === 'local' ? 'true' : 'true' }}">

    <!-- Font Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Montserrat:wght@400;700&family=Crete+Round:ital@0;1&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- FontAwesome CDN - Priority over local -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('demos/medical/css/medical-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper.css') }}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logo-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-modals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-enhancements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-tables.css') }}">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @stack('styles')

    <!-- Global Font Styling -->
    <style>
        body, * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif !important;
        }

        /* FontAwesome Debug - Force display if not loading */
        .fa, .fas, .far, .fab {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            -webkit-font-smoothing: antialiased;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
        }

        /* Admin Layout Styles */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .admin-content {
            flex: 1;
            margin-left: 280px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .admin-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-brand img {
            max-width: 120px;
            height: auto;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: #DE6262;
        }

        .nav-link.active {
            color: white;
            background: rgba(222,98,98,0.2);
            border-left-color: #DE6262;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        .nav-section {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.5);
            margin-top: 1rem;
        }

        .nav-section:first-child {
            margin-top: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block !important;
            }
        }

        .mobile-toggle {
            display: none;
        }

        .user-info {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
    </style>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel | MedCura AI')</title>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar" id="adminSidebar">
            <!-- Brand -->
            <div class="sidebar-brand">
                <a href="{{ route('admin.dashboard') }}">
                    <img src="{{ asset('demos/medical/images/logo-medical.png') }}?v={{ time() }}&cache={{ rand(1000,9999) }}" alt="MedCura AI" class="img-fluid">
                </a>
                <div class="mt-2">
                    <small class="text-white-50">Admin Panel</small>
                </div>
            </div>

            <!-- Navigation -->
            <div class="sidebar-nav">
                <!-- Dashboard Section -->
                <div class="nav-section">Dashboard</div>
                <div class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Overview</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.usage-analytics') }}" class="nav-link {{ request()->routeIs('admin.usage-analytics') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>Usage Analytics</span>
                    </a>
                </div>

                <!-- User Management Section -->
                <div class="nav-section">User Management</div>
                <div class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.users.create') }}" class="nav-link {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">
                        <i class="fas fa-user-plus"></i>
                        <span>Add New User</span>
                    </a>
                </div>

                <!-- Data Management Section -->
                <div class="nav-section">Data Management</div>
                <div class="nav-item">
                    <a href="{{ route('admin.data-migration.index') }}" class="nav-link {{ request()->routeIs('admin.data-migration.*') ? 'active' : '' }}">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Data Migration</span>
                    </a>
                </div>

                <!-- Billing & Finance Section -->
                <div class="nav-section">Billing & Finance</div>
                <div class="nav-item">
                    <a href="{{ route('admin.billing') }}" class="nav-link {{ request()->routeIs('admin.billing*') ? 'active' : '' }}">
                        <i class="fas fa-credit-card"></i>
                        <span>Billing Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice"></i>
                        <span>Invoice Management</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.invoices.create') }}" class="nav-link {{ request()->routeIs('admin.invoices.create') ? 'active' : '' }}">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create Invoice</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.monthly-invoices.index') }}" class="nav-link {{ request()->routeIs('admin.monthly-invoices.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Monthly Invoices</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.user-pricing.index') }}" class="nav-link {{ request()->routeIs('admin.user-pricing.*') ? 'active' : '' }}">
                        <i class="fas fa-dollar-sign"></i>
                        <span>User Pricing</span>
                    </a>
                </div>

                <!-- Communication Section -->
                <div class="nav-section">Communication</div>
                <div class="nav-item">
                    <a href="{{ route('admin.send-reminders.form') }}" class="nav-link {{ request()->routeIs('admin.send-reminders*') ? 'active' : '' }}">
                        <i class="fas fa-bell"></i>
                        <span>Send Manual Reminders</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.contact-submissions') }}" class="nav-link {{ request()->routeIs('admin.contact-submissions*') ? 'active' : '' }}">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Submissions</span>
                    </a>
                </div>

                <!-- Clearinghouse Integration Section -->
                <div class="nav-section">Clearinghouse</div>
                <div class="nav-item">
                    <a href="{{ route('admin.clearinghouse.accounts') }}" class="nav-link {{ request()->routeIs('admin.clearinghouse.accounts*') ? 'active' : '' }}">
                        <i class="fas fa-building"></i>
                        <span>Account Management</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.clearinghouse.monitoring') }}" class="nav-link {{ request()->routeIs('admin.clearinghouse.monitoring*') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>Submission Monitoring</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.clearinghouse.errors') }}" class="nav-link {{ request()->routeIs('admin.clearinghouse.errors*') ? 'active' : '' }}">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Error Reporting</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.clearinghouse.providers') }}" class="nav-link {{ request()->routeIs('admin.clearinghouse.providers*') ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i>
                        <span>Provider Config</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.clearinghouse.metrics') }}" class="nav-link {{ request()->routeIs('admin.clearinghouse.metrics*') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Performance Metrics</span>
                    </a>
                </div>

                <!-- HEP Management Section -->
                <div class="nav-section">HEP Management</div>
                <div class="nav-item">
                    <a href="{{ route('admin.exercises.index') }}" class="nav-link {{ request()->routeIs('admin.exercises.*') ? 'active' : '' }}">
                        <i class="fas fa-dumbbell"></i>
                        <span>Exercise Library</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.hep-templates.index') }}" class="nav-link {{ request()->routeIs('admin.hep-templates.*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>HEP Templates</span>
                    </a>
                </div>

                <!-- Payer Rules Engine Section -->
                <div class="nav-section">Payer Rules Engine</div>
                <div class="nav-item">
                    <a href="{{ route('admin.payers.index') }}" class="nav-link {{ request()->routeIs('admin.payers.*') ? 'active' : '' }}">
                        <i class="fas fa-building"></i>
                        <span>Payer Management</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.payers.index') }}?tab=rules" class="nav-link {{ request()->routeIs('admin.payers.rules.*') ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i>
                        <span>Rules Configuration</span>
                    </a>
                </div>

                <!-- Waitlist Management Section -->
                <div class="nav-section">Waitlist Management</div>
                <div class="nav-item">
                    <a href="{{ route('admin.waitlist.dashboard') }}" class="nav-link {{ request()->routeIs('admin.waitlist.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-list-ul"></i>
                        <span>Waitlist Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.waitlist.analytics') }}" class="nav-link {{ request()->routeIs('admin.waitlist.analytics') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i>
                        <span>Waitlist Analytics</span>
                    </a>
                </div>

                <!-- System Section -->
                <div class="nav-section">System</div>
                <div class="nav-item">
                    <a href="{{ route('admin.system-settings') }}" class="nav-link {{ request()->routeIs('admin.system-settings*') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h"></i>
                        <span>System Settings</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('admin.sms-settings') }}" class="nav-link {{ request()->routeIs('admin.sms-settings*') ? 'active' : '' }}">
                        <i class="fas fa-mobile-alt"></i>
                        <span>SMS Settings</span>
                    </a>
                </div>

            </div>

            <!-- User Info -->
            <div class="user-info">
                <div class="d-flex align-items-center">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ Auth::guard('admin')->user()->name }}</div>
                        <small class="text-white-50">Administrator</small>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="admin-content" id="main-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary mobile-toggle me-3" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="h4 mb-0">@yield('title', 'Admin Panel')</h1>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-muted small">
                            <i class="fas fa-clock me-1"></i>
                            {{ now()->format('M d, Y - g:i A') }}
                        </div>
                        <div class="badge bg-success">
                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                            Online
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.querySelector('.mobile-toggle');

            if (window.innerWidth <= 768 &&
                !sidebar.contains(event.target) &&
                !toggle.contains(event.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>
