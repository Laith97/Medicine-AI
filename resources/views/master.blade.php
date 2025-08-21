<!DOCTYPE html>
<html dir="ltr" lang="en-US">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description"
        content="Create Medical Clinic & Hospital Websites with Canvas Template. Get Canvas to build powerful websites easily with the Highly Customizable & Best Selling Bootstrap Template, today.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@auth
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="notification-sound-enabled" content="{{ env('NOTIFICATION_SOUND_ENABLED', 'true') }}">
    <meta name="notification-toast-enabled" content="{{ env('NOTIFICATION_TOAST_ENABLED', 'true') }}">
    <meta name="notification-badge-enabled" content="{{ env('NOTIFICATION_BADGE_ENABLED', 'true') }}">
    @endauth
    <style>
        .top-link {
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            color: #333;
            /* dark gray */
            position: relative;
            padding: 5px 8px;
            transition: color 0.3s ease;
        }

        .top-link:hover {
            color: #007bff;
            /* blue on hover */
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
/* Header Dropdown Styling - Match EXACTLY the .sub-menu-container style */
.dropdown-menu {
    /* Visuals copied from .primary-menu .sub-menu-container */
    background: #ffffff !important;
    border: 1px solid #dee2e6 !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    border-radius: 0.375rem !important;

    /* Spacing copied from .sub-menu-container */
    padding: 0.5rem 0 !important;
    margin: 0.25rem 0 0 0 !important;

    /* Dimensions copied from .sub-menu-container */
    min-width: 200px !important;
    width: auto !important;
    max-width: 280px !important;

    /* Keep high z-index so it shows above content */
    z-index: 999999 !important;
}

/* Ensure any Bootstrap-added shadow class doesn't override the look */
.dropdown-menu.shadow {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

.dropdown-item {
    /* Match .primary-menu .sub-menu-container .menu-link */
    display: flex !important;
    width: 185px !important;
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0.5rem 1rem !important;
    margin: 0.125rem 0.5rem !important;
    font-size: 0.875rem !important;
    font-weight: 400 !important;
    line-height: 1.5 !important;
    color: #212529 !important;
    text-decoration: none !important;
    background-color: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 0.375rem !important;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out !important;
}

/* Hover: match .primary-menu .sub-menu-container .menu-link:hover */
.dropdown-item:hover {
    color: #DE6262 !important;
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}

/* Icon sizing inside dropdown items to match sub-menu links */
.dropdown-item i {
    font-size: 0.875rem !important;
    width: 1rem !important;
    text-align: center !important;
}

/* Focus/active: mirror .primary-menu .sub-menu-container .menu-item.current .menu-link */
.dropdown-item:focus, .dropdown-item:active,
.dropdown-item:focus-visible,
.dropdown-item:focus-within {
    outline: none !important;
    color: #ffffff !important;
    background-color: #DE6262 !important;
    border-color: #DE6262 !important;
}

        /* Hover Effect - Same as sub-menu menu-link hover */
        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(222, 98, 98, 0.1), rgba(222, 98, 98, 0.05)) !important;
            color: #DE6262 !important;
            font-weight: 600 !important;
            transform: translateX(4px) !important;
            box-shadow: 0 4px 12px rgba(222, 98, 98, 0.15) !important;
        }

        /* Focus states - Same as sub-menu styling */
        .dropdown-item:focus,
        .dropdown-item:active,
        .dropdown-item:focus-visible,
        .dropdown-item:focus-within {
            outline: none !important;
            background: linear-gradient(135deg, rgba(222, 98, 98, 0.1), rgba(222, 98, 98, 0.05)) !important;
            color: #DE6262 !important;
            font-weight: 600 !important;
            transform: translateX(4px) !important;
            box-shadow: 0 4px 12px rgba(222, 98, 98, 0.15) !important;
        }

        /* Danger/Logout button styling */
        .dropdown-item.text-danger {
            color: #dc3545 !important;
        }

        .dropdown-item.text-danger:hover {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05)) !important;
            color: #dc3545 !important;
            font-weight: 600 !important;
        }

        /* Dropdown divider - Same as menu divider */
        .dropdown-divider {
            height: 1px !important;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.08) 20%, rgba(0, 0, 0, 0.08) 80%, transparent) !important;
            margin: 8px 16px !important;
            border: none !important;
            padding: 0 !important;
        }

        /* Bootstrap Dropdown Container Fix */
        .dropdown {
            position: relative !important;
            z-index: 10003 !important;
        }

/* Dropdown: let Bootstrap/Popper position it; only ensure visibility */
body .dropdown.show .dropdown-menu,
body .dropdown .dropdown-menu.show,
.dropdown-menu.show {
    z-index: 999999 !important;
    display: block !important;
    /* Do not override position/transform so Popper can place it correctly */
}

/* Ensure dropdown containers allow dropdowns to escape */
.table-responsive {
    overflow-x: auto !important;
    overflow-y: visible !important;
}

.admin-card,
.admin-table-container,
.main-content,
.content,
.container,
.container-fluid,
.container-xxl,
.row,
.card,
.card-body {
    overflow: visible !important;
}

        /* Header Area Dropdown Fix */
        #header .dropdown {
            position: relative !important;
            z-index: 10005 !important;
        }

        #header .dropdown-menu {
            z-index: 999999 !important;
            position: absolute !important;
        }
    </style>
    <!-- Clean Font Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- FontAwesome CDN - Priority over local -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Temporarily disabled local font-icons -->
    <!-- <link rel="stylesheet" href="{{ asset('css/font-icons.css') }}"> -->
    <link rel="stylesheet" href="{{ asset('demos/medical/css/medical-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper.css') }}">
    <link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logo-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-modals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom-buttons.css') }}">
    @stack('styles')

    <!-- Global Font Styling -->
    <style>
        /* Clean Medical System Font */
        body,
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif !important;
            font-weight: 400;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* Navigation specific fonts - Clean and readable */
        .primary-menu .menu-link {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif !important;
            font-weight: 400;
            letter-spacing: 0.01em;
        }

        /* Headers - Bold but not thick */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .heading {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif !important;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        /* Medical/clinical text */
        .medical-text,
        .diagnosis-text,
        .case-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif !important;
            font-weight: 400;
            line-height: 1.6;
        }

        /* FontAwesome Debug - Force display if not loading */
        .fa,
        .fas,
        .far,
        .fab {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            -webkit-font-smoothing: antialiased;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
        }

        /* Test icon visibility */
        .icon-test {
            font-size: 24px;
            color: red;
            margin: 10px;
        }

        /* Navigation Improvements */
        .primary-menu .menu-container {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            max-width: 100%;
            overflow: visible;
        }

        .primary-menu .menu-item {
            white-space: nowrap;
            margin-right: 1rem;
            position: relative;
        }

        .primary-menu .menu-link {
            padding: 0.5rem 1rem;
            font-size: 15px;
            font-weight: 400;
            color: #333333;
            transition: all 0.3s ease;
        }

        .primary-menu .menu-item.current .menu-link,
        .primary-menu .menu-link:hover {
            color: #DE6262;
            font-weight: 500;
        }

        /* Dropdown arrow styling */
        .primary-menu .fa-chevron-down {
            font-size: 10px;
            margin-left: 5px;
            color: #333333;
            opacity: 0.8;
        }

        .primary-menu .menu-item:hover .fa-chevron-down,
        .primary-menu .menu-item.current .fa-chevron-down {
            color: #DE6262;
            opacity: 1;
        }

        /* Bootstrap Button Style Dropdown Design */
        .primary-menu .sub-menu-container {
            /* Positioning & Layout */
            position: absolute !important;
            top: 100% !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 999999 !important;

            /* Bootstrap Button Dimensions */
            min-width: 200px !important;
            width: auto !important;
            max-width: 280px !important;

            /* Bootstrap Button Design */
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;

            /* Bootstrap Button Rounded Design */
            border-radius: 0.375rem !important;

            /* Bootstrap Button Spacing */
            padding: 0.5rem 0 !important;
            margin: 0.25rem 0 0 0 !important;

            /* Simple Animation */
            opacity: 0 !important;
            visibility: hidden !important;
            transition: all 0.15s ease-in-out !important;

            /* List Reset */
            list-style: none !important;
        }

        /* Add hover bridge to prevent dropdown from closing */
        .primary-menu .sub-menu-container::before {
            content: '' !important;
            position: absolute !important;
            top: -10px !important;
            left: 0 !important;
            right: 0 !important;
            height: 10px !important;
            background: transparent !important;
        }

        /* Remove special-casing last item; keep centered under its parent */
        .primary-menu .menu-item:last-child .sub-menu-container {
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
        }

        /* Show dropdown on hover - keep it open when hovering dropdown itself */
        .primary-menu .menu-item:hover .sub-menu-container,
        .primary-menu .sub-menu-container:hover {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Bootstrap Button Style Dropdown Items */
        .primary-menu .sub-menu-container .menu-item {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Bootstrap Button Style Dropdown Links */
        .primary-menu .sub-menu-container .menu-link {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.375rem 0.75rem !important;
            margin: 0.125rem 0.5rem !important;
            font-size: 0.875rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: #212529 !important;
            text-decoration: none !important;
            background-color: transparent !important;
            border: 1px solid transparent !important;
            border-radius: 0.375rem !important;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out !important;
        }

        /* Bootstrap Button Style Icon */
        .primary-menu .sub-menu-container .menu-link i {
            font-size: 0.875rem !important;
            width: 1rem !important;
            text-align: center !important;
        }

        /* Bootstrap Button Style Hover Effect */
        .primary-menu .sub-menu-container .menu-link:hover {
            color: #DE6262 !important;
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
        }

        /* Bootstrap Button Style Active State */
        .primary-menu .sub-menu-container .menu-item.current .menu-link {
            color: #ffffff !important;
            background-color: #DE6262 !important;
            border-color: #DE6262 !important;
        }

        /* Bootstrap Button Style Active Hover State */
        .primary-menu .sub-menu-container .menu-item.current .menu-link:hover {
            color: #ffffff !important;
            background-color: #c55555 !important;
            border-color: #c55555 !important;
        }

        /* Remove debug styling - production ready */

        /* Modern Dropdown Arrow Animation */
        .primary-menu .fa-chevron-down {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            font-size: 10px !important;
            margin-left: 6px !important;
            opacity: 0.7 !important;
        }

        .primary-menu .menu-item:hover .fa-chevron-down {
            transform: rotate(180deg) !important;
            opacity: 1 !important;
            color: #DE6262 !important;
        }

        /* Responsive Navigation Improvements */
        @media (max-width: 1200px) {
            .primary-menu .menu-container {
                flex-wrap: wrap !important;
                justify-content: center !important;
            }

            .primary-menu .menu-item {
                margin-right: 0.5rem !important;
            }

            .primary-menu .sub-menu-container {
                min-width: 240px !important;
                max-width: 280px !important;
            }
        }

        @media (max-width: 992px) {
            .primary-menu .menu-link {
                padding: 0.4rem 0.8rem !important;
                font-size: 14px !important;
            }

            .primary-menu .sub-menu-container {
                position: fixed !important;
                top: 80px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                width: 90vw !important;
                max-width: 300px !important;
            }
        }

        /* Enhanced Menu Item Spacing */
        .primary-menu .menu-item+.menu-item {
            margin-left: 0.5rem !important;
        }

        /* Modern Badge for Notifications */
        .menu-badge {
            position: absolute !important;
            top: -2px !important;
            right: -2px !important;
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            color: white !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            padding: 2px 6px !important;
            border-radius: 10px !important;
            min-width: 16px !important;
            text-align: center !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
        }

        /* Prevent Horizontal Scroll but allow dropdowns */
        html, body {
            /* Avoid 100vw to prevent scrollbar-induced overflow */
            max-width: 100% !important;
            /* Prevent horizontal scroll without affecting dropdown positioning */
            overflow-x: clip !important;
        }

        .container-fluid {
            max-width: 100% !important;
        }

        .container {
            max-width: 1200px !important;
        }

        /* Ensure all elements stay within viewport */
        * {
            box-sizing: border-box !important;
        }

        /* Header Layout - Fix Z-Index Issues */
        #header {
            overflow: visible !important;
            position: relative !important;
        }

        .header-row {
            overflow: visible !important;
            position: relative !important;
        }

        .primary-menu {
            position: relative !important;
            z-index: 10001 !important;
        }

        /* Ensure dropdown appears above all content */
        .primary-menu .menu-item {
            position: relative !important;
            z-index: 10002 !important;
        }

        /* Force dropdown above everything */
        .primary-menu .sub-menu-container {
            position: absolute !important;
            z-index: 999999 !important;
        }

        /* Fix dropdown positioning to prevent overflow */
        .primary-menu .sub-menu-container {
            /* Ensure dropdowns don't cause horizontal scroll */
            left: 50% !important;
            transform: translateX(-50%) translateY(-8px) !important;
            max-width: min(320px, 90vw) !important;
            width: max-content !important;
        }

        /* Adjust dropdown positioning for edge cases */
        .primary-menu .menu-item:first-child .sub-menu-container {
            left: 0 !important;
            transform: translateX(0) translateY(-8px) !important;
        }

        /* Keep last item centered like others unless it overflows; default center */
        .primary-menu .menu-item:last-child .sub-menu-container {
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) translateY(-8px) !important;
        }

        /* Keep mobile menu functionality intact */
        @media (max-width: 991px) {
            .primary-menu {
                display: none;
            }

            .primary-menu-trigger {
                display: block;
            }
        }

        /* Modern Parent Hover State */
        .primary-menu .menu-item:hover>.menu-link {
            background: linear-gradient(135deg, rgba(222, 98, 98, 0.08), rgba(222, 98, 98, 0.04)) !important;
            border-radius: 8px !important;
            color: #DE6262 !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 8px rgba(222, 98, 98, 0.1) !important;
            transform: translateY(-1px) !important;
        }

        /* Dropdown positioning */
        .primary-menu .menu-item {
            position: relative;
        }

        /* Modern Dropdown Overrides */
        .primary-menu .sub-menu-container,
        .primary-menu .sub-menu-container * {
            box-sizing: border-box !important;
        }

        /* Prevent any theme interference */
        .primary-menu .menu-item .sub-menu-container {
            list-style: none !important;
        }

        /* Better hover persistence */
        .primary-menu .menu-item {
            position: relative !important;
        }

        /* Make dropdown parent more forgiving to hover */
        .primary-menu .menu-item .menu-link {
            position: relative !important;
            z-index: 2 !important;
        }

        /* Keep dropdown open when hovering over parent or dropdown */
        .primary-menu .menu-item:hover .sub-menu-container,
        .primary-menu .menu-item .sub-menu-container:hover {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateX(-50%) translateY(0) !important;
            transition-delay: 0s !important;
        }

        /* Add slight delay when leaving to prevent accidental closing */
        .primary-menu .sub-menu-container {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Immediate show, delayed hide */
        .primary-menu .menu-item:hover .sub-menu-container {
            transition-delay: 0s !important;
        }

        .primary-menu .menu-item:not(:hover) .sub-menu-container {
            transition-delay: 0.2s !important;
        }

        /* Modern Responsive Design */
        @media (max-width: 1200px) {
            .primary-menu .menu-link {
                padding: 0.5rem 0.75rem;
                font-size: 14px;
            }

            .primary-menu .sub-menu-container {
                min-width: 220px !important;
                max-width: 260px !important;
            }
        }

        @media (max-width: 992px) {
            .primary-menu .menu-link {
                padding: 0.5rem 0.5rem;
                font-size: 13px;
            }

            .primary-menu .sub-menu-container {
                min-width: 200px !important;
                max-width: 240px !important;
                backdrop-filter: blur(15px) !important;
            }
        }

        @media (max-width: 768px) {
            .primary-menu .menu-link {
                font-size: 14px;
                padding: 0.75rem 1rem;
            }

            /* Mobile: Convert to accordion style */
            .primary-menu .sub-menu-container {
                position: static !important;
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
                background: rgba(248, 249, 250, 0.95) !important;
                backdrop-filter: none !important;
                box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1) !important;
                border: 1px solid #e9ecef !important;
                border-radius: 12px !important;
                margin: 8px 0 !important;
                padding: 8px 0 !important;
            }

            .primary-menu .sub-menu-container::before {
                display: none !important;
            }

            .primary-menu .sub-menu-container .menu-link {
                margin: 0 4px !important;
                padding: 10px 16px !important;
                border-left: 3px solid transparent !important;
                border-radius: 8px !important;
            }

            .primary-menu .sub-menu-container .menu-item.current .menu-link {
                border-left-color: #DE6262 !important;
                background: linear-gradient(135deg, rgba(222, 98, 98, 0.15), rgba(222, 98, 98, 0.08)) !important;
            }
        }
    </style>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Medical Demo | Canvas')</title>
</head>

<body class="stretched page-transition"
    data-loader-html="<div id='css3-spinner-svg-pulse-wrapper'><svg id='css3-spinner-svg-pulse' version='1.2' height='210' width='550' xmlns='https://www.w3.org/2000/svg' viewport='0 0 60 60' xmlns:xlink='https://www.w3.org/1999/xlink'><path id='css3-spinner-pulse' stroke='#DE6262' fill='none' stroke-width='2' stroke-linejoin='round' d='M0,90L250,90Q257,60 262,87T267,95 270,88 273,92t6,35 7,-60T290,127 297,107s2,-11 10,-10 1,1 8,-10T319,95c6,4 8,-6 10,-17s2,10 9,11h210'></svg></div>">

    <!-- Wrapper -->
    <div id="wrapper">

        <!-- Top Bar Start -->
        <div id="top-bar" class="py-2 border-bottom"
            style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white;">
            <div class="container">
                <div class="row justify-content-between align-items-center">

                    <!-- Left Side: Quick Info & Status -->
                    <div class="col-md-auto d-none d-md-flex align-items-center gap-4 small">
                        <div class="d-flex align-items-center">
                            <div class="status-indicator bg-success rounded-circle me-2"
                                style="width: 8px; height: 8px;"></div>
                            <span><i class="bi bi-shield-check me-1"></i> AI System Online</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @if(Auth::guard('admin')->check() || session()->has('impersonating_admin_id'))
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
                        @if(Auth::user()->isDoctor())
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('settings') }}">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('doctor.profile.edit') }}">
                                    <i class="fas fa-user-edit"></i>Edit Profile
                                </a>
                            </li>
                            @if(Auth::user()->isMainUser())
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('sub-users.index') }}">
                                        <i class="fas fa-users"></i> Manage Sub-Users
                                    </a>
                                </li>
                            @endif
                        @endif
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
               class="btn btn-sm px-4 me-2"
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
<div class="header-row d-flex align-items-center justify-content-center">

                <!-- Logo and Desktop Nav Container -->
                <div class="d-flex align-items-center">
                    <!-- Logo -->
                    <div id="logo" class="me-4">
                        <a href="@auth{{ route('dashboard') }}@else{{ url('/') }}@endauth">
                            <img style="width: 140px" class="logo-default"
                                 srcset="{{ asset('demos/medical/images/logo-medical.jpeg') }}, {{ asset('demos/medical/images/logo-medical.jpeg') }} 2x"
                                 src="{{ asset('demos/medical/images/logo-medical.jpeg') }}"
                                 alt="Canvas Logo">
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <nav class="primary-menu style-3 menu-spacing-margin d-none d-lg-block">
                        <ul class="menu-container">
                            @auth
                                @if (Auth::guard('admin')->check() && !session()->has('impersonating_admin_id') && !session()->has('impersonating_hospital_admin_id'))
                                    <!-- Pure Admin View - Only show when admin is not impersonating -->
                                    <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'current' : '' }}">
                                        <a class="menu-link" href="{{ route('admin.dashboard') }}"><div>Dashboard</div></a>
                                    </li>
                                @else
                                    <!-- User View (Including Admin Impersonation and Hospital Admin Impersonation) -->
                                    @php
                                        $menuItems = \App\Helpers\MenuHelper::getMenuItems(auth()->user());
                                    @endphp

                        @auth
                            <!-- Quick Action Button for Emergency -->
                            <a href="{{ route('ask-ai') }}" class="btn btn-sm px-3 py-1 me-2"
                                style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; font-size: 12px;">
                                <i class="bi bi-lightning-charge me-1"></i> Quick Diagnosis
                            </a>

                            <!-- Notifications Bell -->
                            <div class="dropdown notifications-dropdown">
                                <button class="btn btn-sm position-relative notification-bell" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                                    <i class="bi bi-bell"></i>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count"
                                        id="notification-count" style="font-size: 10px; padding: 2px 6px; display: none;">
                                        0
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow notifications-dropdown-menu"
                                    style="width: 350px; max-height: 400px; overflow-y: auto;">
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                        <h6 class="mb-0">Notifications</h6>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary mark-all-read-btn"
                                                title="Mark all as read">
                                                <i class="bi bi-check-all"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary view-all-btn"
                                                title="View all notifications">
                                                <i class="bi bi-list-ul"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="notification-list" id="notification-list">
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
                                            <small>No notifications</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown">
                                <a class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle" href="#"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                                    <i class="bi bi-person-circle"></i>
                                    <div class="d-flex flex-column align-items-start">
                                        <span>{{ Auth::user()->name }}</span>
                                        @if (Auth::user()->isSubUser())
                                            <small
                                                class="opacity-75">{{ \App\Helpers\MenuHelper::getUserRoleDisplay(Auth::user()) }}</small>
                                        @endif
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    @if (Auth::guard('admin')->check())
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                href="{{ route('admin.dashboard') }}">
                                                <i class="bi bi-shield-check"></i> Admin Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                href="{{ route('admin.users.index') }}">
                                                <i class="bi bi-people"></i> Manage Users
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                    @endif
                                    @if (Auth::user()->isDoctor())
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                href="{{ route('settings') }}">
                                                <i class="bi bi-gear"></i> Settings
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                href="{{ route('doctor.profile.edit') }}">
                                                <i class="fas fa-user-edit"></i>Edit Profile
                                            </a>
                                        </li>
                                        @if (Auth::user()->isMainUser())
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2"
                                                    href="{{ route('sub-users.index') }}">
                                                    <i class="fas fa-users"></i> Manage Sub-Users
                                                </a>
                                            </li>
                                        @endif
                                    @endif
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                            href="{{ route('notifications.index') }}">
                                            <i class="bi bi-gear"></i> Notification Settings
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit"
                                                class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                <i class="bi bi-box-arrow-right"></i> Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @endauth

                        @guest
                            <a href="{{ route('login') }}" class="btn btn-sm px-4"
                                style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                            <!-- <a href="{{ route('register') }}"
                   class="btn btn-sm px-4"
                   style="background: white; color: #DE6262; border: none; font-weight: 500; border-radius: 25px;">
                    <i class="bi bi-person-plus me-1"></i> Register
                </a>-->
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
                    <div class="header-row d-flex align-items-center justify-content-center">

                        <!-- Logo and Desktop Nav Container -->
                        <div class="d-flex align-items-center">
                            <!-- Logo -->
                            <div id="logo" class="me-4">
                                <a href="@auth {{ route('dashboard') }} @else {{ url('/') }} @endauth">
                                    <img style="width: 140px" class="logo-default"
                                        srcset="{{ asset('demos/medical/images/logo-medical.jpeg') }}, {{ asset('demos/medical/images/logo-medical.jpeg') }} 2x"
                                        src="{{ asset('demos/medical/images/logo-medical.jpeg') }}"
                                        alt="Canvas Logo">
                                </a>
                            </div>

                            <!-- Desktop Navigation -->
                            <nav class="primary-menu style-3 menu-spacing-margin d-none d-lg-block">
                                <ul class="menu-container">
                                    @auth
                                        @if (Auth::guard('admin')->check())
                                            <li
                                                class="menu-item {{ request()->routeIs('admin.dashboard') ? 'current' : '' }}">
                                                <a class="menu-link" href="{{ route('admin.dashboard') }}">
                                                    <div>Dashboard</div>
                                                </a>
                                            </li>
                                        @else
                                            @php
                                                $menuItems = \App\Helpers\MenuHelper::getMenuItems(auth()->user());
                                            @endphp

                                            @foreach ($menuItems as $item)
                                                @if (isset($item['dropdown']) && $item['dropdown'])
                                                    <!-- Dropdown Menu Item -->
                                                    <li
                                                        class="menu-item {{ collect($item['items'])->contains(fn($subItem) => request()->routeIs($subItem['route'] ?? '')) ? 'current' : '' }}">
                                                        <a class="menu-link" href="#">
                                                            <div>{{ $item['name'] }} <i class="fas fa-chevron-down"></i>
                                                            </div>
                                                        </a>
                                                        <ul class="sub-menu-container">
                                                            @foreach ($item['items'] as $subItem)
                                                                <li
                                                                    class="menu-item {{ request()->routeIs($subItem['route'] ?? '') ? 'current' : '' }}">
                                                                    <a class="menu-link"
                                                                        href="{{ isset($subItem['route']) ? route($subItem['route']) : '#' }}">
                                                                        <div>
                                                                            @if (isset($subItem['icon']))
                                                                                <i
                                                                                    class="{{ $subItem['icon'] }} me-2"></i>
                                                                            @endif
                                                                            {{ $subItem['name'] }}
                                                                        </div>
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </li>
                                                @else
                                                    <!-- Regular Menu Item -->
                                                    <li
                                                        class="menu-item {{ request()->routeIs($item['route'] ?? '') ? 'current' : '' }}">
                                                        <a class="menu-link"
                                                            href="{{ isset($item['route']) ? route($item['route']) : '#' }}">
                                                            <div>
                                                                @if (isset($item['icon']))
                                                                    <i class="{{ $item['icon'] }} me-2"></i>
                                                                @endif
                                                                {{ $item['name'] }}
                                                            </div>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endauth

                                    @guest
                                        <li class="menu-item {{ request()->is('/') ? 'current' : '' }}">
                                            <a class="menu-link" href="{{ url('/') }}">
                                                <div>Home</div>
                                            </a>
                                        </li>
                                        <li class="menu-item {{ request()->is('about') ? 'current' : '' }}">
                                            <a class="menu-link" href="{{ route('about') }}">
                                                <div>About Us</div>
                                            </a>
                                        </li>
                                        <li class="menu-item {{ request()->is('contact') ? 'current' : '' }}">
                                            <a class="menu-link" href="{{ route('contact') }}">
                                                <div>Contact</div>
                                            </a>
                                        </li>
                                        <li class="menu-item {{ request()->is('doctors') ? 'current' : '' }}">
                                            <a class="menu-link" href="{{ route('doctors.index') }}">
                                                <div>For Patients</div>
                                            </a>
                                        </li>
                                    @endguest
                                </ul>
                            </nav>
                        </div>

                        <!-- Mobile Hamburger Button -->
                        <div class="primary-menu-trigger d-block d-lg-none">
                            <button class="cnvs-hamburger" type="button" title="Open Mobile Menu">
                                <span class="cnvs-hamburger-box"><span class="cnvs-hamburger-inner"></span></span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="header-wrap-clone"></div>
        </header>


        <!-- Flash Messages -->
        @if (session('success') || session('error') || session('warning') || session('info'))
            <div class="container-fluid px-0">
                <div class="row">
                    <div class="col-12">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show m-0 rounded-0 border-0"
                                role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Success!</strong> {{ session('success') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show m-0 rounded-0 border-0"
                                role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Error!</strong> {{ session('error') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show m-0 rounded-0 border-0"
                                role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Warning!</strong> {{ session('warning') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (session('info'))
                            <div class="alert alert-info alert-dismissible fade show m-0 rounded-0 border-0"
                                role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Info!</strong> {{ session('info') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @php
            // DEBUG: Check session variables for banner display
            $debugInfo = [
                'admin_id' => session('impersonating_admin_id'),
                'hospital_admin_id' => session('impersonating_hospital_admin_id'),
                'admin_started' => session('admin_impersonation_started_at'),
                'hospital_started' => session('hospital_admin_impersonation_started_at'),
                'user_id' => session('impersonating_user_id'),
                'user_role' => auth()->user()?->role,
            ];
        @endphp

        <!-- Chain Impersonation: ONLY if hospital admin started time exists AND we have admin session -->
        @if(session('impersonating_admin_id') && session('impersonating_hospital_admin_id') && session('hospital_admin_impersonation_started_at') && !empty(session('hospital_admin_impersonation_started_at')) && auth()->check() && auth()->user()->isDoctor())
            <!-- Chain Impersonation Banner (Sky Blue) -->
            <div class="bg-info py-2">
                <div class="container">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users me-2 text-white"></i>
                            <small class="mb-0 text-white">
                                <strong>Chain Impersonation:</strong>
                                {{ session('impersonating_admin_name', 'Admin') }} → {{ session('impersonating_hospital_admin_name') }} → Dr. {{ auth()->user()->name }}
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('return-to-hospital-admin') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm py-1 px-2">
                                    <i class="fas fa-arrow-left me-1"></i>Return to Hospital Admin
                                </button>
                            </form>
                            <form method="POST" action="{{ route('return-to-admin') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-light btn-sm py-1 px-2">
                                    <i class="fas fa-arrow-up me-1"></i>Return to Admin
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(session('impersonating_hospital_admin_id') && empty(session('impersonating_admin_id')) && auth()->check() && auth()->user()->isDoctor())
            <!-- Direct Hospital Admin Banner (Yellow) - Only when NO admin session -->
            <div class="bg-warning py-2">
                <div class="container">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield me-2"></i>
                            <small class="mb-0">
                                <strong>Hospital Admin Impersonation:</strong>
                                {{ session('impersonating_hospital_admin_name') }} is viewing as Dr. {{ auth()->user()->name }}
                            </small>
                        </div>
                        <form method="POST" action="{{ route('return-to-hospital-admin') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm py-1 px-2">
                                <i class="fas fa-arrow-left me-1"></i>Return to Hospital Admin
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(session('impersonating_admin_id') && session('impersonating_user_id') && session('admin_impersonation_started_at') && empty(session('hospital_admin_impersonation_started_at')))
            <!-- Direct Admin Banner (Red) - Only when NO hospital admin session active -->
            @php
                $impersonatedUser = auth()->user();
                $impersonatedUserId = session('impersonating_user_id');

                // Fallback: if auth()->user() is null, try to get user from session
                if (!$impersonatedUser && $impersonatedUserId) {
                    $impersonatedUser = \App\Models\User::find($impersonatedUserId);
                }

                $userName = $impersonatedUser?->name ?? 'User';
                $userRole = $impersonatedUser?->role ?? 'unknown';
            @endphp
            <div class="bg-danger py-2">
                <div class="container">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-shield me-2 text-white"></i>
                            <small class="mb-0 text-white">
                                <strong>Admin Impersonation:</strong>
                                {{ session('impersonating_admin_name', 'Admin') }} is viewing as {{ $userName }} ({{ ucfirst(str_replace('_', ' ', $userRole)) }})
                            </small>
                        </div>
                        <form method="POST" action="{{ route('return-to-admin') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm py-1 px-2">
                                <i class="fas fa-arrow-left me-1"></i>Return to Admin
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

    </div><!-- #wrapper end -->
			</div>

@if (!auth()->check())
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
                        <li class="mb-2"><a href="{{ route('ask-ai') }}" class="text-white-50 text-decoration-none hover-link">AI Assistant</a></li>
                        <li class="mb-2"><a href="{{ route('cases') }}" class="text-white-50 text-decoration-none hover-link">Case Studies</a></li>
                        <li class="mb-2"><a href="{{ route('settings') }}" class="text-white-50 text-decoration-none hover-link">Settings</a></li>
                    @else
                        <li class="mb-2"><a href="{{ url('/') }}" class="text-white-50 text-decoration-none hover-link">Home</a></li>
                        <li class="mb-2"><a href="{{ route('about') }}" class="text-white-50 text-decoration-none hover-link">About Us</a></li>
                        <li class="mb-2"><a href="{{ route('contact') }}" class="text-white-50 text-decoration-none hover-link">Contact</a></li>
                        <li class="mb-2"><a href="{{ route('login') }}" class="text-white-50 text-decoration-none hover-link">Login</a></li>
                        <li class="mb-2"><a href="{{ route('register') }}" class="text-white-50 text-decoration-none hover-link">Register</a></li>
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
                        <li class="mb-2"><a href="{{ route('settings') }}" class="text-white-50 text-decoration-none hover-link">Profile Settings</a></li>
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
                            <a href="info@medcuraai.com" class="text-white text-decoration-none">info@medcuraai.com</a>
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
                    <a href="{{ route('contact') }}" class="text-white-50 text-decoration-none hover-link me-3">Contact Us</a>
                    <a href="{{ route('admin.login') }}" class="text-white-50 text-decoration-none hover-link" style="font-size: 0.8rem;">Admin</a>
                </div>
            </div>
        </div>
    </div>

    @if (!auth()->check())
        <!-- Footer -->
        <footer id="footer" class="text-white py-5"
            style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
            <div class="container">
                <div class="row g-4">
                    <!-- Company Info -->
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-brand mb-4">
                            <h4 class="text-white mb-3" style="color: #DE6262 !important;">
                                <i class="bi bi-heart-pulse me-2" style="color: #DE6262;"></i>
                                AI Medical Diagnosis
                            </h4>
                            <p class="text-white-50 mb-4">Revolutionizing healthcare with cutting-edge artificial
                                intelligence. Empowering medical professionals with advanced diagnostic tools for
                                superior patient care and outcomes.</p>

                            <!-- Social Links -->
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2"
                                    style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2"
                                    style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2"
                                    style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                                <a href="#" class="btn btn-outline-light btn-sm rounded-circle me-2 p-2"
                                    style="width: 40px; height: 40px; border-color: rgba(222,98,98,0.3);">
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
                                <li class="mb-2"><a href="{{ route('dashboard') }}"
                                        class="text-white-50 text-decoration-none hover-link">Dashboard</a></li>
                                <li class="mb-2"><a href="{{ route('ask-ai') }}"
                                        class="text-white-50 text-decoration-none hover-link">AI Assistant</a></li>
                                <li class="mb-2"><a href="{{ route('cases') }}"
                                        class="text-white-50 text-decoration-none hover-link">Case Studies</a></li>
                                <li class="mb-2"><a href="{{ route('settings') }}"
                                        class="text-white-50 text-decoration-none hover-link">Settings</a></li>
                            @else
                                <li class="mb-2"><a href="{{ url('/') }}"
                                        class="text-white-50 text-decoration-none hover-link">Home</a></li>
                                <li class="mb-2"><a href="{{ route('about') }}"
                                        class="text-white-50 text-decoration-none hover-link">About Us</a></li>
                                <li class="mb-2"><a href="{{ route('contact') }}"
                                        class="text-white-50 text-decoration-none hover-link">Contact</a></li>
                                <li class="mb-2"><a href="{{ route('login') }}"
                                        class="text-white-50 text-decoration-none hover-link">Login</a></li>
                            @endauth
                        </ul>
                    </div>

                    <!-- Resources -->
                    <div class="col-lg-2 col-md-6">
                        <h6 class="text-white mb-3" style="color: #DE6262 !important;">Support</h6>
                        <ul class="list-unstyled footer-links">
                            <li class="mb-2"><a href="{{ route('about') }}"
                                    class="text-white-50 text-decoration-none hover-link">About Platform</a></li>
                            <li class="mb-2"><a href="{{ route('contact') }}"
                                    class="text-white-50 text-decoration-none hover-link">Contact Support</a></li>
                            @auth
                                <li class="mb-2"><a href="{{ route('settings') }}"
                                        class="text-white-50 text-decoration-none hover-link">Profile Settings</a></li>
                            @endauth
                        </ul>
                    </div>

                    <!-- Contact & Support -->
                    <div class="col-lg-4 col-md-6">
                        <h6 class="text-white mb-3" style="color: #DE6262 !important;">Contact & Support</h6>

	<!-- Go To Top
	============================================= -->
	<div id="gotoTop" class="fas fa-chevron-up rounded-circle" style="position: fixed; bottom: 20px; right: 20px; width: 40px; height: 40px; background-color: #DE6262; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; opacity: 0.8; transition: opacity 0.3s;"></div>

                            <div class="d-flex align-items-center mb-3">
                                <div class="contact-icon me-3"
                                    style="width: 40px; height: 40px; background: rgba(222,98,98,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-headset" style="color: #DE6262;"></i>
                                </div>
                                <div>
                                    <small class="text-white-50 d-block">24/7 Support</small>
                                    <span class="text-white">AI-Powered Help Available</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <div class="contact-icon me-3"
                                    style="width: 40px; height: 40px; background: rgba(222,98,98,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
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
                            <a href="{{ route('contact') }}" class="btn btn-sm"
                                style="background: #DE6262; color: white; border: none; border-radius: 25px;">
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
                            <a href="{{ route('contact') }}"
                                class="text-white-50 text-decoration-none hover-link me-3">Contact Us</a>
                            <a href="{{ route('admin.login') }}"
                                class="text-white-50 text-decoration-none hover-link"
                                style="font-size: 0.8rem;">Admin</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    @endif

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
            color: rgba(255, 255, 255, 0.6);
        }

        .newsletter-signup input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #DE6262;
            box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
            color: white;
        }
    </style>

    </div><!-- #wrapper end -->

    <!-- Go To Top
 ============================================= -->
    <div id="gotoTop" class="fas fa-chevron-up rounded-circle"
        style="position: fixed; bottom: 20px; right: 20px; width: 40px; height: 40px; background-color: #007bff; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; opacity: 0.8; transition: opacity 0.3s;">
    </div>

    <!-- JavaScripts
 ============================================= -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/plugins.min.js') }}"></script>
    <script src="{{ asset('js/functions.bundle.js') }}"></script>

    <!-- Vite Assets (Laravel Echo & Pusher) -->
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    <!-- Notification System Styles -->
    @include('notifications._styles')

    <!-- Remove conflicting notification scripts - now handled by Vite -->

{{-- Extra scripts --}}
@stack('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== MENU STRUCTURE DEBUG ===');

        // Log all menu items and their dropdowns
        document.querySelectorAll('.primary-menu .menu-item').forEach((item, index) => {
            const menuLink = item.querySelector(':scope > .menu-link');
            const dropdown = item.querySelector(':scope > .sub-menu-container');

            if (menuLink) {
                const menuName = menuLink.textContent.trim().replace(' ▼', '');
                console.log(`Menu ${index + 1}: "${menuName}"`);

                if (dropdown) {
                    const subItems = Array.from(dropdown.querySelectorAll('.menu-link'))
                        .map(link => link.textContent.trim());
                    console.log(`  Dropdown items:`, subItems);
    <!-- Improved Dropdown Hover Behavior -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Improve dropdown hover behavior
            let hoverTimeout;

            document.querySelectorAll('.primary-menu .menu-item').forEach(menuItem => {
                const dropdown = menuItem.querySelector('.sub-menu-container');

                if (dropdown) {
                    // Show dropdown immediately on hover
                    menuItem.addEventListener('mouseenter', function() {
                        clearTimeout(hoverTimeout);
                        dropdown.style.opacity = '1';
                        dropdown.style.visibility = 'visible';
                    });

                    // Delay hiding dropdown when mouse leaves
                    menuItem.addEventListener('mouseleave', function() {
                        hoverTimeout = setTimeout(() => {
                            dropdown.style.opacity = '0';
                            dropdown.style.visibility = 'hidden';
                        }, 300); // 300ms delay
                    });

                    // Keep dropdown open when hovering over it
                    dropdown.addEventListener('mouseenter', function() {
                        clearTimeout(hoverTimeout);
                    });

                    dropdown.addEventListener('mouseleave', function() {
                        hoverTimeout = setTimeout(() => {
                            dropdown.style.opacity = '0';
                            dropdown.style.visibility = 'hidden';
                        }, 300);
                    });
                }
            });
        });

        // Simple form submission tracking for debugging (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Track form submissions for debugging
            document.querySelectorAll('form[action*="return-to"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const button = form.querySelector('button[type="submit"]');
                    if (button) {
                        // Add simple loading state
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Returning...';
                        button.disabled = true;

                        console.log('Form submitted:', form.action);
                    }
                });
            });
        });

        // Comprehensive Bootstrap dropdown positioning fix
        document.addEventListener('DOMContentLoaded', function() {
            // Override all dropdown positioning issues
            function fixDropdownPositioning() {
                document.querySelectorAll('.dropdown-menu').forEach(function(dropdown) {
                    if (dropdown.classList.contains('show')) {
                        // Remove any problematic styles
                        dropdown.style.transform = '';
                        dropdown.style.left = '';
                        dropdown.style.top = '';
                        dropdown.style.right = '';
                        dropdown.style.bottom = '';
                        dropdown.style.position = 'absolute';
                        dropdown.style.zIndex = '999999';

                        // Force Popper.js to recalculate position
                        const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                        if (dropdownInstance && dropdownInstance._popper) {
                            dropdownInstance._popper.update();
                        }
                    }
                });
            }

            // Fix on dropdown show
            document.addEventListener('shown.bs.dropdown', fixDropdownPositioning);

            // Fix on scroll (in case it moves)
            document.addEventListener('scroll', function() {
                fixDropdownPositioning();
            }, { passive: true });

            // Initial fix for any already open dropdowns
            fixDropdownPositioning();

            // Alternative approach - disable Popper.js positioning entirely for problematic dropdowns
            document.addEventListener('show.bs.dropdown', function(event) {
                const dropdown = event.target.querySelector('.dropdown-menu');
                if (dropdown && dropdown.classList.contains('dropdown-menu-end')) {
                    // Disable Popper.js and handle positioning manually
                    const button = event.target.querySelector('[data-bs-toggle="dropdown"]');
                    const rect = button.getBoundingClientRect();

                    // Position dropdown manually relative to button (fixed positioning)
                    dropdown.style.position = 'fixed';
                    dropdown.style.top = rect.bottom + 'px';
                    dropdown.style.left = (rect.right - dropdown.offsetWidth) + 'px';
                    dropdown.style.transform = 'none';
                    dropdown.style.zIndex = '999999';
                }
            });
        });
    </script>

                    // Add temporary label for visual debugging
                    const debugLabel = document.createElement('div');
                    debugLabel.style.cssText =
                        'background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; font-size: 10px; font-weight: bold;';
                    debugLabel.textContent = `${menuName} Dropdown`;
                    dropdown.insertBefore(debugLabel, dropdown.firstChild);
                } else {
                    console.log(`  No dropdown`);
                }
            }
        });

        console.log('=== END DEBUG ===');

        // Improve dropdown hover behavior
        let hoverTimeout;

        document.querySelectorAll('.primary-menu .menu-item').forEach(menuItem => {
            const dropdown = menuItem.querySelector('.sub-menu-container');

            if (dropdown) {
                menuItem.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    dropdown.style.opacity = '1';
                    dropdown.style.visibility = 'visible';
                });

                menuItem.addEventListener('mouseleave', function() {
                    hoverTimeout = setTimeout(() => {
                        dropdown.style.opacity = '0';
                        dropdown.style.visibility = 'hidden';
                    }, 300);
                });

                dropdown.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                });

                dropdown.addEventListener('mouseleave', function() {
                    hoverTimeout = setTimeout(() => {
                        dropdown.style.opacity = '0';
                        dropdown.style.visibility = 'hidden';
                    }, 300);
                });
            }
        });
    });
</script>

@auth
<!-- Meta tags for notification system -->
<meta name="user-id" content="{{ auth()->id() }}">
<meta name="notification-sound-enabled" content="true">
<meta name="notification-toast-enabled" content="true">

<!-- Notification Scripts -->
<script src="{{ asset('sounds/notification-sound.js') }}"></script>
<!-- Unified notifications now handled by Vite/Enhanced system -->

<!-- Debug Tools (only in development) -->
@if(config('app.debug'))
<meta name="app-debug" content="true">
<script src="{{ asset('js/notification-debug.js') }}"></script>
<script src="{{ asset('js/echo-debug.js') }}"></script>
<script src="{{ asset('js/echo-debug-enhanced.js') }}"></script>
<script src="{{ asset('js/notification-tester.js') }}"></script>
<script src="{{ asset('js/test-public-notifications.js') }}"></script>
<script src="{{ asset('js/notification-test-runner.js') }}"></script>
<script src="{{ asset('js/notification-diagnostics.js') }}"></script>
<script src="{{ asset('js/pusher-raw-debug.js') }}"></script>
<script src="{{ asset('js/laravel-notification-catcher.js') }}"></script>
<script src="{{ asset('js/appointment-notification-debug.js') }}"></script>
<script src="{{ asset('js/backend-diagnosis.js') }}"></script>
@endif

<script>
console.log('🚀 Unified Notification System will auto-initialize');

@if(config('app.debug'))
// Add debug commands to window for testing
window.testNotifications = () => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.testNotification();
        console.log('📤 Test notification sent');
    } else {
        console.error('❌ Unified notification system not available');
    }
};

window.toggleNotificationSound = (enabled) => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.enableSound(enabled);
        console.log('🔊 Notification sound ' + (enabled ? 'enabled' : 'disabled'));
    }
};

window.toggleNotificationToast = (enabled) => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.enableToast(enabled);
        console.log('📋 Notification toast ' + (enabled ? 'enabled' : 'disabled'));
    }
};

// Additional debug info
setTimeout(() => {
    console.log('🧪 System Status:');
    console.log('  • Echo available:', typeof window.Echo !== 'undefined');
    console.log('  • NotificationSound available:', typeof window.notificationSound !== 'undefined');
    console.log('  • UnifiedNotifications available:', typeof window.unifiedNotifications !== 'undefined');
    console.log('  • User ID:', document.querySelector('meta[name="user-id"]')?.getAttribute('content'));

    if (window.unifiedNotifications) {
        console.log('  • Unified system initialized:', window.unifiedNotifications.isInitialized);
        console.log('  • Sound enabled:', window.unifiedNotifications.soundEnabled);
        console.log('  • Toast enabled:', window.unifiedNotifications.toastEnabled);
    }
}, 3000);

console.log('🛠️ Debug commands available:');
console.log('  • testNotifications() - Send a test notification');
console.log('  • toggleNotificationSound(true/false) - Enable/disable sounds');
console.log('  • toggleNotificationToast(true/false) - Enable/disable toasts');
console.log('  • notificationDiagnostics.runQuickTest() - Full system diagnostics');
console.log('  • notificationDiagnostics.testDropdownClick() - Test dropdown click');
console.log('  • pusherRawDebug.start() - Start capturing ALL Pusher events');
console.log('  • pusherRawDebug.getUserEvents() - See captured user events');
console.log('  • laravelNotificationCatcher.getChannels() - List active channels');
console.log('  • appointmentNotificationDebug.start() - Debug appointment notifications');
console.log('  • appointmentNotificationDebug.showSummary() - Show debug results');
console.log('  • diagnoseBackend() - Full backend diagnosis (Laravel/Pusher)');
console.log('  • backendDiagnosis.suggestFixes() - Show fix suggestions');
@endif
</script>
@endauth

    </body>

    </html>
