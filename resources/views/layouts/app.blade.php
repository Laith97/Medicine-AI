<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description" content="Hospital Admin Panel - MedCura AI">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<!-- Notification meta tags -->
	<meta name="user-id" content="{{ Auth::id() }}">
	<meta name="notification-sound-enabled" content="{{ config('app.env') === 'local' ? 'true' : 'true' }}">
	<meta name="notification-toast-enabled" content="{{ config('app.env') === 'local' ? 'true' : 'true' }}">

    <!-- Font Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('demos/medical/css/medical-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper.css') }}">
    <link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logo-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-modals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-enhancements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-tables.css') }}">
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

        /* Hospital Admin Layout Styles */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
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

        /* Hospital Admin specific styles */
        .hospital-badge {
            background: rgba(222,98,98,0.2);
            color: #DE6262;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Professional Skeleton Loading Animation */
        .skeleton-loader {
            animation: skeleton-loading 1.5s ease-in-out infinite;
            background: linear-gradient(90deg,
                rgba(222,98,98, 0.1) 25%,
                rgba(222,98,98, 0.05) 50%,
                rgba(222,98,98, 0.1) 75%);
            background-size: 200% 100%;
            border: 1px solid rgba(222,98,98, 0.1);
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-header {
            height: 60px;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .skeleton-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .skeleton-stat-card {
            flex: 1;
            height: 120px;
            border-radius: 20px;
        }

        .skeleton-tabs {
            height: 50px;
            border-radius: 20px;
            margin-bottom: 2rem;
        }

        .skeleton-table {
            border-radius: 12px;
            overflow: hidden;
        }

        .skeleton-table-header {
            height: 50px;
            margin-bottom: 1rem;
        }

        .skeleton-table-row {
            height: 60px;
            margin-bottom: 0.5rem;
            border-radius: 8px;
        }

        /* Loading indicator overlay */
        .ajax-loading-overlay {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9998;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(222,98,98, 0.2);
            border-radius: 50px;
            padding: 8px 16px;
            box-shadow: 0 4px 12px rgba(222,98,98, 0.15);
            display: none;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #DE6262;
            font-weight: 500;
        }

        .ajax-loading-overlay.show {
            display: flex;
        }

        .ajax-loading-overlay .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(222,98,98, 0.3);
            border-top: 2px solid #DE6262;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive skeleton adjustments */
        @media (max-width: 768px) {
            .skeleton-stats {
                flex-direction: column;
            }

            .skeleton-stat-card {
                height: 100px;
                margin-bottom: 1rem;
            }
        }
    </style>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Hospital Admin | MedCura AI')</title>
</head>
<body>
    <!-- AJAX Loading Indicator -->
    <div id="ajax-loading-overlay" class="ajax-loading-overlay">
        <div class="loading-spinner"></div>
        <span>Loading...</span>
    </div>

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar" id="adminSidebar">
            <!-- Brand -->
            <div class="sidebar-brand">
                <a href="{{ route('hospital-admin.dashboard') }}">
                    <img src="{{ asset('demos/medical/images/logo-medical.png') }}?v={{ time() }}&cache={{ rand(1000,9999) }}" alt="MedCura AI" class="img-fluid">
                </a>
                <div class="mt-2">
                    <small class="text-white-50">Hospital Admin</small>
                    @if(Auth::user()->hospital)
                        <div class="hospital-badge mt-1">
                            {{ Str::limit(Auth::user()->hospital->name, 20) }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Navigation -->
            <div class="sidebar-nav">
                <!-- Dashboard Section -->
                <div class="nav-section">Dashboard</div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.dashboard') }}" class="nav-link {{ request()->routeIs('hospital-admin.dashboard') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Overview</span>
                    </a>
                </div>

                <!-- Hospital Management Section -->
                <div class="nav-section">Hospital Management</div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.doctors.index') }}" class="nav-link {{ request()->routeIs('hospital-admin.doctors.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-user-md"></i>
                        <span>Doctors</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.departments.index') }}" class="nav-link {{ request()->routeIs('hospital-admin.departments.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-building"></i>
                        <span>Departments</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.hospital.profile') }}" class="nav-link {{ request()->routeIs('hospital-admin.hospital.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-hospital"></i>
                        <span>Hospital Profile</span>
                    </a>
                </div>

                <!-- Analytics & Reports Section -->
                <div class="nav-section">Analytics & Reports</div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.analytics.overview') }}" class="nav-link {{ request()->routeIs('hospital-admin.analytics.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.usage.index') }}" class="nav-link {{ request()->routeIs('hospital-admin.usage.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-chart-bar"></i>
                        <span>Usage Reports</span>
                    </a>
                </div>

                <!-- Billing Section -->
                <div class="nav-section">Billing & Finance</div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.subscription.manage') }}" class="nav-link {{ request()->routeIs('hospital-admin.subscription.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-credit-card"></i>
                        <span>Subscription</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('hospital-admin.invoices.index') }}" class="nav-link {{ request()->routeIs('hospital-admin.invoices.*') ? 'active' : '' }}" data-ajax="true">
                        <i class="fas fa-file-invoice"></i>
                        <span>Invoices</span>
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
                        <div class="fw-semibold">{{ Auth::user()->name }}</div>
                        <small class="text-white-50">Hospital Admin</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('hospital-admin.hospital.profile') }}">
                                    <i class="fas fa-user-cog me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
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
                        <h4 class="mb-0">@yield('page-title', 'Hospital Admin Panel')</h4>
                    </div>
                    <div class="d-flex align-items-center">
                        @if(Auth::user()->hospital)
                            <span class="badge bg-primary me-3">{{ Auth::user()->hospital->name }}</span>
                        @endif
                        <span class="text-muted">{{ now()->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
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
            document.getElementById('adminSidebar').classList.toggle('show');
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

    {{-- AJAX Navigation Script --}}
    <script>
    $(document).ready(function() {
       // Intercept sidebar link clicks
       $(document).on('click', '.sidebar-nav a[data-ajax="true"]', function(e) {
           e.preventDefault();

           const $link = $(this);
           const route = $link.data('route');
           const url = $link.attr('href');

           // Don't navigate if already active
           if ($link.hasClass('active')) {
               return;
           }

           // Update active state
           $('.sidebar-nav .nav-link').removeClass('active');
           $link.addClass('active');

           // Load content via AJAX
           loadPageContent(url, route);

           // Update browser history
           history.pushState({route: route, url: url}, '', url);
       });

       // Handle browser back/forward buttons
       window.addEventListener('popstate', function(e) {
           if (e.state && e.state.url) {
               loadPageContent(e.state.url, e.state.route);
           }
       });
    });

    function loadPageContent(url, route) {
        // Show loading overlay and skeleton
        const $loadingOverlay = $('#ajax-loading-overlay');
        const $mainContent = $('#main-content');
        const originalContent = $mainContent.html();

        // Show loading overlay
        $loadingOverlay.addClass('show');

        $mainContent.html(`
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <!-- Skeleton Header -->
                        <div class="skeleton-loader skeleton-header"></div>

                        <!-- Skeleton Stats Cards -->
                        <div class="skeleton-stats">
                            <div class="skeleton-loader skeleton-stat-card"></div>
                            <div class="skeleton-loader skeleton-stat-card"></div>
                            <div class="skeleton-loader skeleton-stat-card"></div>
                            <div class="skeleton-loader skeleton-stat-card"></div>
                        </div>

                        <!-- Skeleton Table -->
                        <div class="skeleton-loader skeleton-table">
                            <div class="skeleton-loader skeleton-table-header"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                            <div class="skeleton-loader skeleton-table-row"></div>
                        </div>
                    </div>
                </div>
            </div>
        `);

        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            },
            success: function(response) {
                try {
                    // Hide loading overlay
                    $loadingOverlay.removeClass('show');

                    // Extract content from the response (between main-content div)
                    const $temp = $('<div>').html(response);
                    const newContent = $temp.find('#main-content').html();

                    if (newContent) {
                        $mainContent.html(newContent);

                        // Update page title
                        const newTitle = $temp.find('title').text();
                        if (newTitle) {
                            document.title = newTitle;
                        }

                        // Re-initialize any JavaScript components
                        initializePageComponents(route);

                        // Scroll to top smoothly
                        $('html, body').animate({ scrollTop: 0 }, 300);
                    } else {
                        // If no main-content found, assume full page response
                        $mainContent.html(response);
                    }
                } catch (error) {
                    console.error('Error parsing AJAX response:', error);
                    $mainContent.html(originalContent);
                    showAjaxError('Failed to load page content. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                // Hide loading overlay
                $loadingOverlay.removeClass('show');

                console.error('AJAX Error:', error);
                $mainContent.html(originalContent);

                // Fallback to regular navigation for critical errors
                if (xhr.status === 0 || xhr.status >= 500) {
                    showAjaxError('Connection failed. Redirecting...');
                    setTimeout(() => {
                        window.location.href = url;
                    }, 2000);
                } else {
                    showAjaxError('Failed to load page. Please refresh and try again.');
                }
            }
        });
    }

    function initializePageComponents(route) {
        // Re-initialize DataTables if present
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.dataTable').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().destroy();
                }
            });

            // Re-initialize DataTables with new content
            if (typeof initializeDataTable === 'function') {
                initializeDataTable();
            }
        }

        // Re-initialize Bootstrap components (Bootstrap 5 - no jQuery plugins)
        if (typeof bootstrap !== 'undefined') {
            // Re-initialize tooltips
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
                const tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip) {
                    tooltip.dispose();
                }
                new bootstrap.Tooltip(element);
            });

            // Re-initialize popovers
            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(element => {
                const popover = bootstrap.Popover.getInstance(element);
                if (popover) {
                    popover.dispose();
                }
                new bootstrap.Popover(element);
            });

            // Clean up modals (dispose existing instances)
            document.querySelectorAll('.modal').forEach(modalElement => {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.dispose();
                }
            });
        }

        // Trigger custom event for page-specific initializations
        $(document).trigger('pageContentLoaded', [route]);
    }

    function showAjaxError(message) {
        // Create a temporary error notification
        const $error = $(`
            <div class="alert alert-danger alert-dismissible fade show position-fixed"
                 style="top: 80px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        $('body').append($error);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            $error.alert('close');
        }, 5000);
    }
    </script>

    @stack('scripts')
</body>
</html>