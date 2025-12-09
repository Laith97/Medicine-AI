<!DOCTYPE html>
<html dir="ltr" lang="en-US">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description"
        content="Create Medical Clinic & Hospital Websites with Canvas Template. Get Canvas to build powerful websites easily with the Highly Customizable & Best Selling Bootstrap Template, today.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Force fresh logo loading - prevent JPEG caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
 @auth
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="notification-sound-enabled" content="{{ env('NOTIFICATION_SOUND_ENABLED', 'true') }}">
    <meta name="notification-toast-enabled" content="{{ env('NOTIFICATION_TOAST_ENABLED', 'true') }}">
    <meta name="notification-badge-enabled" content="{{ env('NOTIFICATION_BADGE_ENABLED', 'true') }}">
    <script>
        window.userRole = '{{ Auth::user()->role ?? 'user' }}';
        window.userId = {{ Auth::id() ?? 'null' }};
    </script>
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
    z-index: 9999999 !important;
    position: absolute !important;
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
/* Ensure hamburger button is visible and clickable on mobile */
@media (max-width: 991px) {
    .primary-menu-trigger {
        display: block !important;
    }

    .cnvs-hamburger {
        display: block !important;
        background: none !important;
        border: none !important;
        cursor: pointer !important;
        padding: 10px !important;
        z-index: 10005 !important;
        position: relative !important;
        /* Add visual debugging */
        min-width: 44px !important;
        min-height: 44px !important;
    }

    .cnvs-hamburger:hover {
        background: rgba(222, 98, 98, 0.2) !important;
        border-color: rgba(222, 98, 98, 0.5) !important;
        transform: scale(1.05) !important;
    }

    /* Make sure hamburger lines are visible */
    .cnvs-hamburger .cnvs-hamburger-inner,
    .cnvs-hamburger .cnvs-hamburger-inner::before,
    .cnvs-hamburger .cnvs-hamburger-inner::after {
        background-color: white !important;
    }

    /* Fix logo positioning on mobile - keep it on the left */
    .d-flex.align-items-center.flex-grow-1 {
        display: flex !important;
        align-items: center !important;
        flex-grow: 1 !important;
        justify-content: flex-start !important; /* Keep logo on the left */
    }

    /* Ensure logo container doesn't center itself */
    .header .container,
    .header .container-fluid {
        justify-content: space-between !important;
    }

    /* Logo specific positioning */
    #logo {
        margin-right: auto !important;
        margin-left: 0 !important;
    }
}

/* Bootstrap Dropdown Toggle Button */
.dropdown-toggle {
    position: relative !important;
    z-index: 10004 !important;
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
    z-index: 9999999 !important;
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
            z-index: 9999999 !important;
            position: absolute !important;
        }
    </style>

    <!-- Dropdown Styles - Simplified for Bootstrap Compatibility -->
    <style>
        /* Ensure dropdown toggles are clickable */
        .dropdown-toggle,
        .notification-bell,
        .btn[data-bs-toggle="dropdown"],
        a[data-bs-toggle="dropdown"],
        button[data-bs-toggle="dropdown"] {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        /* Dropdown container positioning */
        .dropdown,
        .notifications-dropdown,
        .user-dropdown {
            position: relative !important;
            display: inline-block !important; /* prevent reflow shifting */
            vertical-align: middle !important;
        }

        /* Custom dropdown styling */
        .dropdown-menu {
            /* Glass morphism design */
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.08) !important;
            padding: 12px 0 !important;
            min-width: 240px !important;
            z-index: 9999999 !important;
        }

        /* Dropdown items styling */
        .dropdown-item {
            display: flex !important;
            align-items: center !important;
            padding: 12px 20px !important;
            margin: 0 8px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            color: #374151 !important;
            text-decoration: none !important;
            border-radius: 10px !important;
            background: transparent !important;
            border: none !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(222, 98, 98, 0.1), rgba(222, 98, 98, 0.05)) !important;
            color: #DE6262 !important;
            font-weight: 600 !important;
            transform: translateX(4px) !important;
            box-shadow: 0 4px 12px rgba(222, 98, 98, 0.15) !important;
        }

        /* Dropdown divider */
        .dropdown-divider {
            height: 1px !important;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.08) 20%, rgba(0, 0, 0, 0.08) 80%, transparent) !important;
            margin: 8px 16px !important;
            border: none !important;
            padding: 0 !important;
        }

        /* Specific dropdown fixes */
        .notifications-dropdown .dropdown-menu {
            width: 350px !important;
            max-height: 400px !important;
            overflow-y: auto !important;
            z-index: 9999999 !important;
            position: absolute !important;
        }

        .notifications-dropdown {
            position: relative !important;
            z-index: 10004 !important;
        }

        .user-dropdown .dropdown-menu {
            min-width: 200px !important;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .dropdown-menu {
                position: fixed !important;
                top: auto !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-height: 50vh !important;
                border-radius: 10px 10px 0 0 !important;
            }
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
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
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

        /* Fix: Do not override Font Awesome icon fonts */
        .fa, .fas, .far, .fal, .fat, .fad,
        .fa-solid, .fa-regular, .fa-light, .fa-thin, .fa-duotone, .fa-brands,
        i.fa, i.fas, i.far, i.fal, i.fat, i.fad,
        i.fa-solid, i.fa-regular, i.fa-light, i.fa-thin, i.fa-duotone, i.fa-brands {
            font-family: inherit; /* reset first */
        }
        /* Re-assert correct FA font families with !important to beat global * rule */
        .fa, .fas, .fa-solid, i.fa, i.fas, i.fa-solid {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important; /* solid */
        }
        .far, .fa-regular, i.far, i.fa-regular {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 400 !important; /* regular */
        }
        .fab, .fa-brands, i.fab, i.fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important; /* brands */
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
            margin: 0 !important; /* prevent shifting during load */
            padding: 0 !important; /* prevent shifting during load */
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
            z-index: 9999999 !important;

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
            /* keep element measurable to avoid reflow jumps */
            opacity: 0.001 !important;
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

        /* Last item: do not force centering here. Allow general/invert rules to apply */
        /* (Intentionally no overrides for .primary-menu .menu-item:last-child .sub-menu-container) */

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
            /* Large tablets and small desktops */
            .header-row {
                padding: 0.5rem 0 !important;
            }

            #logo img {
                width: 130px !important;
            }

            .primary-menu .menu-container {
                flex-wrap: nowrap !important;
                justify-content: flex-start !important;
            }

            .primary-menu .menu-item {
                margin-right: 0.5rem !important;
            }

            .primary-menu .menu-link {
                padding: 0.4rem 0.7rem !important;
                font-size: 14px !important;
            }

            .primary-menu .sub-menu-container {
                min-width: 240px !important;
                max-width: 280px !important;
            }
        }

        @media (max-width: 992px) {
            /* Tablets - still show desktop navigation but smaller */
            .header-row {
                padding: 0.4rem 0 !important;
            }

            #logo img {
                width: 125px !important;
            }

            .primary-menu .menu-link {
                padding: 0.3rem 0.6rem !important;
                font-size: 13px !important;
            }

            .primary-menu .menu-item {
                margin-right: 0.3rem !important;
            }

            .primary-menu .sub-menu-container {
                position: fixed !important;
                top: 80px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                width: 90vw !important;
                max-width: 300px !important;
                backdrop-filter: blur(10px) !important;
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

        /* Ensure top-bar appears above sidebar */
        #top-bar {
            z-index: 1050 !important;
        }

        /* Professional Skeleton Loading Animation */
        .skeleton-loader {
            animation: skeleton-loading 1.5s ease-in-out infinite;
            background: linear-gradient(90deg,
                rgba(222, 98, 98, 0.1) 25%,
                rgba(222, 98, 98, 0.05) 50%,
                rgba(222, 98, 98, 0.1) 75%);
            background-size: 200% 100%;
            border: 1px solid rgba(222, 98, 98, 0.1);
        }

        /* Loading indicator overlay */
        .ajax-loading-overlay {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9998;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(222, 98, 98, 0.2);
            border-radius: 50px;
            padding: 8px 16px;
            box-shadow: 0 4px 12px rgba(222, 98, 98, 0.15);
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
            border: 2px solid rgba(222, 98, 98, 0.3);
            border-top: 2px solid #DE6262;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        /* Professional Mobile Responsiveness for Top Bar */
        @media (max-width: 991px) {
            #top-bar {
                padding: 0.75rem 0 !important;
                min-height: 60px !important;
            }

            #top-bar .container {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
                max-width: 100% !important;
            }

            /* Improve logo sizing for mobile */
            #top-bar .top-bar-logo img {
                width: 110px !important;
                height: auto !important;
            }

            /* Better spacing for status indicators */
            #top-bar .status-indicator {
                width: 6px !important;
                height: 6px !important;
            }

            /* Improve button touch targets */
            #top-bar .btn {
                min-height: 44px !important;
                min-width: 44px !important;
                padding: 0.5rem !important;
                border-radius: 8px !important;
            }

            /* Better text readability */
            #top-bar .small {
                font-size: 0.75rem !important;
                line-height: 1.2 !important;
            }

            /* User dropdown improvements */
            #top-bar .user-dropdown-toggle {
                padding: 0.5rem 0.75rem !important;
                min-height: 44px !important;
            }

            #top-bar .user-dropdown-toggle .small {
                font-size: 0.7rem !important;
                opacity: 0.8 !important;
            }

            /* Notification bell improvements */
            #top-bar .notification-bell {
                position: relative !important;
            }

            /* Improve spacing between elements */
            #top-bar .d-flex.align-items-center.gap-3 {
                gap: 0.5rem !important;
            }

            /* Status text improvements */
            #top-bar .d-flex.align-items-center .me-1 {
                margin-right: 0.25rem !important;
            }
        }

        @media (max-width: 768px) {
            #top-bar {
                padding: 0.5rem 0 !important;
                min-height: 56px !important;
            }

            /* Stack elements better on very small screens */
            #top-bar .row {
                align-items: stretch !important;
            }

            #top-bar .col-md-auto {
                padding: 0.25rem 0 !important;
            }

            /* Reduce logo size further */
            #top-bar .top-bar-logo img {
                width: 100px !important;
            }

            /* Hide some less critical elements on very small screens */
            #top-bar .text-muted {
                display: none !important;
            }

            /* Make buttons more compact but still touchable */
            #top-bar .btn {
                padding: 0.375rem !important;
                font-size: 0.875rem !important;
            }

            /* Improve notification badge positioning */
            #top-bar .notification-count {
                font-size: 9px !important;
                padding: 1px 5px !important;
                min-width: 14px !important;
                height: 14px !important;
            }
        }

        @media (max-width: 480px) {
            #top-bar {
                padding: 0.375rem 0 !important;
                min-height: 52px !important;
            }

            /* Minimal logo for very small screens */
            #top-bar .top-bar-logo img {
                width: 90px !important;
            }

            /* Simplify user dropdown for small screens */
            #top-bar .user-dropdown-toggle .d-flex {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0 !important;
            }

            #top-bar .user-dropdown-toggle .small {
                font-size: 0.65rem !important;
                line-height: 1 !important;
            }

            /* Hide status indicators on very small screens */
            #top-bar .status-indicator {
                display: none !important;
            }

            /* Make login/register buttons full width on very small screens */
            #top-bar .flex.items-center.space-x-4 {
                flex-direction: column !important;
                gap: 0.25rem !important;
                width: 100% !important;
            }

            #top-bar .flex.items-center.space-x-4 .btn {
                width: 100% !important;
                justify-content: center !important;
            }

            /* Prevent top-bar row from wrapping on mobile */
            #top-bar .row {
                flex-wrap: nowrap !important;
            }
        }

        /* Header Layout - Fix Z-Index Issues */
        #header {
            overflow: visible !important;
            position: relative !important;
            width: 100% !important;
        }

        .header-row {
            overflow: visible !important;
            position: relative !important;
            width: 100% !important;
            min-height: 70px !important;
            align-items: center !important;
        }

        /* Header container responsive fixes */
        #header-wrap {
            width: 100% !important;
        }

        #header .container {
            width: 100% !important;
            max-width: 1400px !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            margin: 0 auto !important;
        }

        /* Logo responsive styling */
        #logo {
            flex-shrink: 0 !important;
        }

        #logo img {
            max-width: 100% !important;
            height: auto !important;
            display: block !important;
        }

        /* Mobile menu trigger styling - hidden by default, shown on mobile */
        .primary-menu-trigger {
            display: none !important;
            flex-shrink: 0 !important;
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
            z-index: 9999999 !important;
        }

        /* Fix dropdown positioning and sizing */
        .primary-menu .sub-menu-container {
            max-width: min(320px, 90vw) !important;
            width: max-content !important;
        }

        /* Top-level dropdowns: center under parent */
        .primary-menu > .menu-container > .menu-item > .sub-menu-container {
            left: 50% !important;
            transform: translateX(-50%) translateY(-8px) !important;
        }

        /* Inverted sub-menus: generic fallback */
        .primary-menu .sub-menu-container.menu-pos-invert {
            left: auto !important;
            right: 0 !important;
            transform: translateX(0) translateY(-8px) !important;
        }

        /* Inverted top-level: align right of parent (more specific) */
        .primary-menu > .menu-container > .menu-item > .sub-menu-container.menu-pos-invert {
            left: auto !important;
            right: 0 !important;
            transform: translateX(0) translateY(-8px) !important;
        }

        /* First item edge-case: align left */
        .primary-menu > .menu-container > .menu-item:first-child > .sub-menu-container {
            left: 0 !important;
            transform: translateX(0) translateY(-8px) !important;
        }

        /* Last item non-invert: keep centered */
        .primary-menu > .menu-container > .menu-item:last-child > .sub-menu-container:not(.menu-pos-invert) {
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) translateY(-8px) !important;
        }

        /* Nested dropdowns: open to the right of their parent menu */
        .primary-menu .sub-menu-container .sub-menu-container {
            top: 0 !important;
            left: 100% !important;
            right: auto !important;
            transform: translateX(0) translateY(0) !important;
        }

        /* Nested inverted: open to the left */
        .primary-menu .sub-menu-container .sub-menu-container.menu-pos-invert {
            left: auto !important;
            right: 100% !important;
            transform: translateX(0) translateY(0) !important;
        }

        /* Mobile Header Responsive Fixes */
        @media (max-width: 991px) {
            /* Header container adjustments */
            #header {
                display: block !important;
                position: relative !important;
                width: 100% !important;
                background: #fff !important;
                border-bottom: 1px solid #eee !important;
                min-height: 60px !important;
            }

            #header-wrap {
                display: block !important;
                width: 100% !important;
            }

            #header .container {
                width: 100% !important;
                max-width: 100% !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
                margin: 0 !important;
            }

            .header-row {
                display: flex !important;
                padding: 0.5rem 0 !important;
                flex-wrap: wrap !important; /* Allow wrapping on mobile */
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
                min-height: 60px !important;
            }

            /* Left side: Logo only */
            .col-md-auto.d-flex.align-items-center.gap-3:first-child {
                order: 1 !important;
                flex: 0 0 auto !important;
                justify-content: flex-start !important;
                width: auto !important;
            }

            /* Right side: Login/Register buttons and hamburger */
            .col-md-auto.d-flex.align-items-center.gap-3:last-child {
                order: 2 !important;
                flex: 0 0 auto !important;
                justify-content: space-between !important;
                align-items: center !important;
                margin-top: 0 !important;
                margin-left: auto !important;
                width: auto !important;
            }

            /* Logo adjustments for mobile */
            #logo {
                margin-right: 0 !important;
                flex-shrink: 0 !important;
            }

            #logo img {
                width: 120px !important;
                max-width: 120px !important;
                height: auto !important;
                display: block !important;
            }

            /* Mobile navigation dropdown - positioned below top bar */
            .show-mobile-nav {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 0.5rem !important;
                position: absolute !important;
                top: 100% !important; /* Below the top bar */
                left: 0 !important;
                right: 0 !important;
                background: white !important;
                border: 1px solid #dee2e6 !important;
                border-top: none !important;
                padding: 1rem !important;
                z-index: 10000 !important;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
                max-height: 60vh !important;
                overflow-y: auto !important;
                width: 100% !important;
            }

            .show-mobile-nav .top-link {
                color: #333 !important;
                background: #f8f9fa !important;
                border: 1px solid #dee2e6 !important;
                font-size: 14px !important;
                padding: 12px 16px !important;
                width: 100% !important;
                text-align: center !important;
                border-radius: 6px !important;
                margin: 4px 0 !important;
                text-decoration: none !important;
                display: block !important;
                transition: all 0.2s ease !important;
            }

            .show-mobile-nav .top-link:hover {
                background: #DE6262 !important;
                color: white !important;
                border-color: #DE6262 !important;
            }

            /* Hide desktop navigation */
            .primary-menu {
                display: none !important;
            }

            /* Show mobile menu trigger - override Bootstrap classes */
            .primary-menu-trigger,
            .primary-menu-trigger.d-block.d-lg-none {
                display: block !important;
                padding: 0.5rem !important;
                order: 2 !important;
            }

            /* Mobile hamburger styling */
            .cnvs-hamburger {
                background: none !important;
                border: none !important;
                padding: 0.5rem !important;
                cursor: pointer !important;
                display: block !important;
                outline: none !important;
            }

            .cnvs-hamburger-box {
                width: 24px !important;
                height: 24px !important;
                position: relative !important;
                display: inline-block !important;
            }

            .cnvs-hamburger-inner {
                display: block !important;
                top: 50% !important;
                margin-top: -2px !important;
                width: 24px !important;
                height: 3px !important;
                background-color: #333 !important;
                border-radius: 4px !important;
                position: absolute !important;
                transition: all 0.3s ease !important;
            }

            .cnvs-hamburger-inner::before,
            .cnvs-hamburger-inner::after {
                content: '' !important;
                display: block !important;
                width: 24px !important;
                height: 3px !important;
                background-color: #333 !important;
                border-radius: 4px !important;
                position: absolute !important;
                transition: all 0.3s ease !important;
            }

            .cnvs-hamburger-inner::before {
                top: -8px !important;
            }

            .cnvs-hamburger-inner::after {
                bottom: -8px !important;
            }

            /* Hamburger animation when active */
            .cnvs-hamburger.active .cnvs-hamburger-inner {
                transform: rotate(45deg) !important;
            }

            .cnvs-hamburger.active .cnvs-hamburger-inner::before {
                top: 0 !important;
                transform: rotate(90deg) !important;
            }

            .cnvs-hamburger.active .cnvs-hamburger-inner::after {
                bottom: 0 !important;
                transform: rotate(90deg) !important;
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
            /* Extra small screens - phones */
            .header-row {
                padding: 0.25rem 0 !important;
            }

            #logo {
                margin-right: 0.5rem !important;
            }

            #logo img {
                width: 100px !important;
                max-width: 100px !important;
            }

            .cnvs-hamburger {
                padding: 0.25rem !important;
            }

            .cnvs-hamburger-box {
                width: 20px !important;
                height: 20px !important;
            }

            .cnvs-hamburger-inner {
                width: 20px !important;
                height: 2px !important;
            }

            .cnvs-hamburger-inner::before,
            .cnvs-hamburger-inner::after {
                width: 20px !important;
                height: 2px !important;
            }

            .cnvs-hamburger-inner::before {
                top: -6px !important;
            }

            .cnvs-hamburger-inner::after {
                bottom: -6px !important;
            }

            /* Hamburger animation when active - smaller screens */
            .cnvs-hamburger.active .cnvs-hamburger-inner {
                transform: rotate(45deg) !important;
            }

            .cnvs-hamburger.active .cnvs-hamburger-inner::before {
                top: 0 !important;
                transform: rotate(90deg) !important;
            }

            .cnvs-hamburger.active .cnvs-hamburger-inner::after {
                bottom: 0 !important;
                transform: rotate(90deg) !important;
            }
        }

        @media (max-width: 480px) {
            /* Very small screens */
            .container {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .header-row {
                padding: 0.25rem !important;
            }

            #logo img {
                width: 90px !important;
                max-width: 90px !important;
            }
        }

            .primary-menu .sub-menu-container .menu-link {
                margin: 0 4px !important;
                padding: 10px 16px !important;
                border-left: 3px solid transparent !important;
                border-radius: 8px !important;
            }

            .primary-menu .sub-menu-container .menu-item.current .menu-link {
                border-left-color: #DE6262 !important;
            }
        }
    </style>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Medical Demo | Canvas')</title>
</head>

<body class="stretched page-transition"
    data-loader-html="<div id='css3-spinner-svg-pulse-wrapper'><svg id='css3-spinner-svg-pulse' version='1.2' height='210' width='550' xmlns='https://www.w3.org/2000/svg' viewport='0 0 60 60' xmlns:xlink='https://www.w3.org/1999/xlink'><path id='css3-spinner-pulse' stroke='#DE6262' fill='none' stroke-width='2' stroke-linejoin='round' d='M0,90L250,90Q257,60 262,87T267,95 270,88 273,92t6,35 7,-60T290,127 297,107s2,-11 10,-10 1,1 8,-10T319,95c6,4 8,-6 10,-17s2,10 9,11h210'></svg></div>">

    {{-- Sidebar CSS/JS --}}
    @auth
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    @include('layouts.sidebar')
    <style>#header{display:none !important;} #top-bar .top-bar-logo{display:none !important;}</style>
    @endauth

<!-- Prevent notification redirects after login -->
<script>
(function() {
    // Check if we have a login parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const loginParam = urlParams.get('login');

    // If we have a login parameter, intercept fetch requests to notification endpoints
    if (loginParam === 'success') {
        const originalFetch = window.fetch;

        window.fetch = function(url, options) {
            // If this is a request to a notification endpoint, cancel it
            if (url.includes('/api/notifications')) {
                return Promise.resolve(new Response(JSON.stringify({}), { status: 200 }));
            }

            // For all other requests, use the original fetch
            return originalFetch.apply(this, arguments);
        };

        // Also intercept any direct navigation to notification endpoints
        const originalPushState = history.pushState;
        history.pushState = function() {
            const url = arguments[2];
            if (url && url.includes('/api/notifications')) {
                return;
            }
            return originalPushState.apply(this, arguments);
        };

        const originalReplaceState = history.replaceState;
        history.replaceState = function() {
            const url = arguments[2];
            if (url && url.includes('/api/notifications')) {
                console.log('Blocking navigation to notification endpoint after login:', url);
                return;
            }
            return originalReplaceState.apply(this, arguments);
        };

        // Restore original functions after a delay
        setTimeout(() => {
            window.fetch = originalFetch;
            history.pushState = originalPushState;
            history.replaceState = originalReplaceState;
            }, 5000); // Wait 5 seconds before restoring
    }
})();
</script>

    <!-- Wrapper -->
    <div id="wrapper">

        <!-- Top Bar Start -->
        <div id="top-bar" class="py-2 border-bottom"
            style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; width: 100%;">
            <div class="container">
                <div class="row justify-content-between align-items-center">

                    <!-- Left Side: Logo & Navigation -->
                    <div class="col-md-auto d-flex align-items-center gap-3 flex-nowrap">
                        <!-- Logo in Top Bar -->
                        <div class="top-bar-logo">
                            <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none">
                                <img style="width: 120px; height: auto;" class="logo-default img-fluid"
                                      src="{{ asset('demos/medical/images/logo-medical.png') }}?v={{ time() }}&cache={{ rand(1000,9999) }}"
                                      alt="Medcura Logo">
                            </a>
                        </div>

                        <!-- Quick Info & Status / Navigation Links -->
                        <div class="d-none d-md-flex align-items-center gap-3 small">
                            @auth
                            <div class="d-flex align-items-center">
                                <div class="status-indicator bg-success rounded-circle me-2"
                                    style="width: 8px; height: 8px;"></div>
                                <span><i class="bi bi-shield-check me-1"></i> AI System Online</span>
                            </div>
                            <div><i class="bi bi-cpu me-1"></i> Advanced Diagnostics Available</div>
                            <div><i class="bi bi-envelope me-1"></i> <a href="mailto:info@medcuraai.com"
                                    class="text-decoration-none text-white-50">info@medcuraai.com</a></div>
                            @else
                            <a href="{{ url('/') }}" class="top-link" style="color: white; text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">Home</a>
                            <a href="{{ route('about') }}" class="top-link" style="color: white; text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">About Us</a>
                            <a href="{{ route('contact') }}" class="top-link" style="color: white; text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">Contact</a>
                            <a href="{{ route('doctors.index') }}" class="top-link" style="color: white; text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">For Patients</a>
                            @endauth
                        </div>
                    </div>

                    <!-- Right Side: Notifications & User Menu -->
                    <div class="col-md-auto d-flex align-items-center gap-3">
                        @auth
                            <!-- Mobile Sidebar Toggle Button -->
                            <button id="sidebarCollapse" class="btn btn-sm btn-outline-secondary d-lg-none d-flex align-items-center justify-content-center"
                                    type="button" aria-label="Toggle navigation sidebar" aria-expanded="false"
                                    title="Open sidebar menu"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; min-width: 44px; min-height: 44px; width: 44px; height: 44px; backdrop-filter: blur(10px);">
                                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                            </button>

                            {{-- AI Ask temporarily disabled --}}
                            {{-- AI Ask temporarily disabled --}}
                            {{-- <!-- Quick Action Button for Emergency -->
                            <a href="{{ route('ai.ask-ai') }}" class="btn btn-sm px-3 py-1"
                                style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; font-size: 12px;"> --}}
                                <i class="bi bi-lightning-charge me-1"></i> Quick Diagnosis
                            </a>

                            <!-- Notifications Bell -->
                            <div class="dropdown notifications-dropdown">
                                <button class="btn btn-sm position-relative notification-bell dropdown-toggle d-flex align-items-center justify-content-center" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true"
                                    aria-label="Notifications menu" title="Notifications"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; min-width: 44px; min-height: 44px; width: 44px; height: 44px; backdrop-filter: blur(10px);">
                                    <i class="bi bi-bell" aria-hidden="true"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count"
                                        id="notification-count" aria-label="unread notifications count"
                                        style="font-size: 10px; padding: 2px 6px; display: none; font-weight: 600;">
                                        0
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow notifications-dropdown-menu"
                                    role="menu"
                                    aria-labelledby="notification-bell"
                                    style="width: 320px; max-height: 350px; overflow-y: auto;">
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" role="presentation">
                                        <h6 class="mb-0" id="notification-header">Notifications</h6>
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Notification actions">
                                            <button type="button" class="btn btn-outline-secondary mark-all-read-btn"
                                                title="Mark all as read"
                                                aria-label="Mark all notifications as read">
                                                <i class="bi bi-check-all" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary view-all-btn"
                                                title="View all notifications"
                                                aria-label="View all notifications">
                                                <i class="bi bi-list-ul" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="notification-list" id="notification-list" role="list" aria-label="Notification list">
                                        <div class="text-center py-4 text-muted" role="status" aria-live="polite">
                                            <i class="bi bi-bell-slash display-6 d-block mb-2" aria-hidden="true"></i>
                                            <small>Loading notifications...</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle user-dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                                    <div class="d-flex flex-column align-items-start d-none d-sm-flex">
                                        <span class="small fw-semibold">{{ Auth::user()->name }}</span>
                                        @if (Auth::user()->isSubUser())
                                            <small class="opacity-75 small text-truncate" style="max-width: 120px;">{{ \App\Helpers\MenuHelper::getUserRoleDisplay(Auth::user()) }}</small>
                                        @endif
                                    </div>
                                    <span class="d-sm-none">
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow user-dropdown-menu">
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
                                            href="{{ route('notifications.settings') }}">
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
                            <div class="d-flex gap-2 align-items-center">
                                <a href="{{ route('login') }}"
                                    class="btn btn-sm px-3 d-flex align-items-center justify-content-center"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px); min-height: 40px;">
                                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Login</span>
                                    <span class="d-sm-none">Sign In</span>
                                </a>
                                <a href="{{ route('register') }}"
                                    class="btn btn-sm px-3 d-flex align-items-center justify-content-center"
                                    style="background: white; color: #DE6262; border: none; font-weight: 500; border-radius: 25px; min-height: 40px;">
                                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Register</span>
                                    <span class="d-sm-none">Sign Up</span>
                                </a>
                                <!-- Hamburger Button for Mobile Navigation -->
                                <button class="cnvs-hamburger d-lg-none d-flex align-items-center justify-content-center"
                                    type="button" aria-label="Toggle navigation menu" aria-expanded="false"
                                    title="Open navigation menu"
                                    style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 50%; min-width: 44px; min-height: 44px; width: 44px; height: 44px; backdrop-filter: blur(10px);">
                                    <div class="cnvs-hamburger-box">
                                        <div class="cnvs-hamburger-inner"></div>
                                    </div>
                                </button>
                            </div>
                        @endguest
                    </div>
                </div>



            </div>
        </div>
    </div>
</div>
<!-- Top Bar End -->
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

        <!-- AJAX Loading Indicator -->
        <div id="ajax-loading-overlay" class="ajax-loading-overlay">
            <div class="loading-spinner"></div>
            <span>Loading...</span>
        </div>

        <!-- Main Content -->
        <div id="main-content" class="dashboard-container" style="padding-top: 0px; margin-top: 70px; border-top: 5px solid #DE6262; border-radius: 15px 15px 0 0; box-shadow: 0 -4px 20px rgba(222, 98, 98, 0.1); position: relative; z-index: 1;">
            <!-- Seamless connection gradient -->
            <div style="position: absolute; top: -5px; left: 0; right: 0; height: 15px; background: linear-gradient(to bottom, rgba(222, 98, 98, 0.2), transparent); pointer-events: none;"></div>
            <main class="app-main" style="padding-top: 25px;">
                @yield('content')
            </main>
        </div>

    </div><!-- #wrapper end -->
		<!-- Footer -->


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
                    <p class="text-white-50 mb-4">
                        Revolutionizing healthcare with cutting-edge artificial intelligence.
                        Empowering medical professionals with advanced diagnostic tools for
                        superior patient care and outcomes.
                    </p>

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
                        <li class="mb-2"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none hover-link">Dashboard</a></li>
                        {{-- AI Ask temporarily disabled --}}
                        {{-- <li class="mb-2"><a href="{{ route('ai.ask-ai') }}" class="text-white-50 text-decoration-none hover-link">AI Assistant</a></li> --}}
                        <li class="mb-2"><a href="{{ route('doctor.patient-management.index') }}" class="text-white-50 text-decoration-none hover-link">Patient Management</a></li>
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
                        <li class="mb-2"><a href="{{ route('settings') }}" class="text-white-50 text-decoration-none hover-link">Profile Settings</a></li>
                    @endauth
                </ul>
            </div>

            <!-- Contact & Support -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white mb-3" style="color: #DE6262 !important;">Contact & Support</h6>

                <!-- Go To Top Button -->
                <div id="gotoTop" class="fas fa-chevron-up rounded-circle"
                    style="position: fixed; bottom: 20px; right: 20px; width: 40px; height: 40px; background-color: #DE6262; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; opacity: 0.8; transition: opacity 0.3s;">
                </div>

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

                <div class="d-flex align-items-center mb-4">
                    <div class="contact-icon me-3"
                        style="width: 40px; height: 40px; background: rgba(222,98,98,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-shield-check" style="color: #DE6262;"></i>
                    </div>
                    <div>
                        <small class="text-white-50 d-block">Security & Privacy</small>
                        <span class="text-white">HIPAA Compliant Platform</span>
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
                    <a href="{{ route('contact') }}" class="text-white-50 text-decoration-none hover-link me-3">Contact Us</a>
                    <a href="{{ route('admin.login') }}" class="text-white-50 text-decoration-none hover-link"
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
    <!-- Ensure Bootstrap JS (with Popper) is available for dropdowns/modals -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- Sidebar JS -->
    <script src="{{ asset('js/sidebar.js') }}" defer></script>

    <!-- Vite Assets (Laravel Echo & Pusher) -->
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    <!-- Notification System Styles -->
    @include('notifications._styles')

    <!-- Notification Manager Script -->
    <script src="{{ asset('js/notification-manager.js?v=' . time()) }}"></script>

    <!-- Notification Sound System -->
    <script src="{{ asset('sounds/notification-sound.js?v=' . time()) }}"></script>

    <!-- Notification Accessibility Test Script -->
    <script src="{{ asset('js/notification-accessibility-test.js?v=' . time()) }}"></script>

    <!-- Remove conflicting notification scripts - now handled by Vite -->

{{-- Extra scripts --}}
@stack('scripts')

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
                <div class="col-12 col-lg-10">
                    <!-- Skeleton Header -->
                    <div class="skeleton-loader skeleton-header"></div>

                    <!-- Skeleton Stats Cards -->
                    <div class="skeleton-stats">
                        <div class="skeleton-loader skeleton-stat-card"></div>
                        <div class="skeleton-loader skeleton-stat-card"></div>
                        <div class="skeleton-loader skeleton-stat-card"></div>
                        <div class="skeleton-loader skeleton-stat-card"></div>
                    </div>

                    <!-- Skeleton Tabs -->
                    <div class="skeleton-loader skeleton-tabs"></div>

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

                   // Mark content as AJAX loaded for components that need to know
                   $mainContent.attr('data-ajax-loaded', 'true');

                   // Re-initialize any JavaScript components (including executing scripts)
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
    // Execute any script tags in the newly loaded content
    executeScriptsInContent();

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

// Function to execute script tags in dynamically loaded content
function executeScriptsInContent() {
    // Find all script tags in the main content area that haven't been executed yet
    const scripts = document.querySelectorAll('#main-content script:not([data-executed])');

    // Separate external and inline scripts
    const externalScripts = [];
    const inlineScripts = [];

    scripts.forEach(script => {
        if (script.src) {
            externalScripts.push(script);
        } else if (script.textContent) {
            inlineScripts.push(script);
        }
    });

    // Load external scripts first, then execute inline scripts
    if (externalScripts.length > 0) {
        loadExternalScriptsSequentially(externalScripts, () => {
            executeInlineScripts(inlineScripts);
        });
    } else {
        executeInlineScripts(inlineScripts);
    }
}

function loadExternalScriptsSequentially(scripts, callback) {
    let index = 0;

    function loadNext() {
        if (index >= scripts.length) {
            callback();
            return;
        }

        const script = scripts[index];
        const newScript = document.createElement('script');
        newScript.src = script.src;
        newScript.async = false; // Load synchronously to ensure proper order
        newScript.defer = false;

        // Copy any data attributes
        Array.from(script.attributes).forEach(attr => {
            if (attr.name.startsWith('data-')) {
                newScript.setAttribute(attr.name, attr.value);
            }
        });

        newScript.onload = () => {
            console.log('Loaded external script:', script.src);
            script.setAttribute('data-executed', 'true');
            index++;
            loadNext();
        };

        newScript.onerror = () => {
            console.error('Failed to load external script:', script.src);
            script.setAttribute('data-executed', 'true');
            index++;
            loadNext(); // Continue with next script even if one fails
        };

        // Replace the old script with the new one
        script.parentNode.replaceChild(newScript, script);
    }

    loadNext();
}

function executeInlineScripts(scripts) {
    scripts.forEach(script => {
        try {
            // Execute the script in global scope
            (function() {
                eval(script.textContent);
            })();
            script.setAttribute('data-executed', 'true');
        } catch (error) {
            console.error('Error executing inline script:', error);
        }
    });
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

<script>
    // Dropdown initialization
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap not loaded, dropdowns will not work');
            return;
        }

        setTimeout(function() {
            // Initialize all dropdown toggles
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                try {
                    if (!bootstrap.Dropdown.getInstance(toggle)) {
                        new bootstrap.Dropdown(toggle, {
                            autoClose: true,
                            offset: [0, 2]
                        });
                    }
                } catch (e) {
                    console.error('Error initializing dropdown:', e);
                }
            });

            // Check notifications dropdown specifically
            const notificationsDropdown = document.querySelector('.notifications-dropdown');
            if (notificationsDropdown) {
                const toggle = notificationsDropdown.querySelector('.dropdown-toggle');

                if (toggle) {
                    // Ensure Bootstrap dropdown is properly initialized for notifications
                    try {
                        if (!bootstrap.Dropdown.getInstance(toggle)) {
                            const dropdownInstance = new bootstrap.Dropdown(toggle, {
                                autoClose: true,
                                offset: [0, 2]
                            });
                        }
                    } catch (e) {
                        console.error('Error initializing notifications dropdown:', e);
                    }
                }
            }

            // Add event listener for notifications dropdown to load notifications when opened
            if (notificationsDropdown) {
                notificationsDropdown.addEventListener('shown.bs.dropdown', function() {
                    loadNotifications();
                });
            }

            // Fix dropdown click handling - Bootstrap's data-bs-toggle auto-handling not working
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default Bootstrap behavior
                    e.stopPropagation(); // Stop event bubbling

                    const instance = bootstrap.Dropdown.getInstance(this);
                    if (instance) {
                        instance.toggle();
                    }
                });
            });
        }, 100);
    });

    // Fallback initialization
    window.addEventListener('load', function() {
        setTimeout(() => {
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                try {
                    if (!bootstrap.Dropdown.getInstance(toggle)) {
                        new bootstrap.Dropdown(toggle);
                    }
                } catch (e) {
                    console.error('Fallback dropdown initialization error:', e);
                }
            });
        }, 500);
    });

        // MOBILE NAVIGATION TOGGLE - Show/Hide navigation links
        function initializeMobileNavigation() {
            // Find the hamburger button
            let mobileMenuTrigger = document.querySelector('.primary-menu-trigger .cnvs-hamburger');
            if (!mobileMenuTrigger) {
                mobileMenuTrigger = document.querySelector('.cnvs-hamburger');
            }
            if (!mobileMenuTrigger) {
                mobileMenuTrigger = document.querySelector('button[title="Open Mobile Menu"]');
            }

            if (!mobileMenuTrigger) {
                console.log('Hamburger button not found - checking all possible selectors');
                console.log('Available hamburger buttons:', document.querySelectorAll('.cnvs-hamburger'));
                console.log('Available primary-menu-trigger:', document.querySelectorAll('.primary-menu-trigger'));
                return;
            }

            console.log('Hamburger button found:', mobileMenuTrigger);

            // Find the navigation links container
            const navLinks = document.querySelector('.d-none.d-md-flex.align-items-center.gap-3.small');

            if (!navLinks) {
                console.log('Navigation links container not found - checking all possible selectors');
                console.log('Available navigation containers:', document.querySelectorAll('[class*="d-none"][class*="d-md-flex"]'));
                console.log('All div elements:', document.querySelectorAll('div').length);
                return;
            }

            console.log('Navigation links container found:', navLinks);
            console.log('Navigation links HTML:', navLinks.innerHTML);

            // Function to toggle navigation
            function toggleMobileNav() {
                console.log('Toggling mobile navigation');

                if (navLinks.classList.contains('show-mobile-nav')) {
                    // Hide navigation - remove the dropdown and event listeners
                    navLinks.classList.remove('show-mobile-nav');
                    if (navLinks._dropdown) {
                        navLinks._dropdown.remove();
                        navLinks._dropdown = null;
                        console.log('Navigation dropdown removed');
                    }
                    // Remove any click outside listeners
                    document.removeEventListener('click', navLinks._closeHandler);
                    navLinks._closeHandler = null;
                    console.log('Navigation hidden');
                } else {
                    // Create a professional dropdown matching site style
                    const dropdown = document.createElement('div');
                    dropdown.id = 'mobile-nav-dropdown';
                    dropdown.style.cssText = `
                        position: fixed;
                        top: 80px;
                        left: 0;
                        right: 0;
                        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
                        border-bottom: 1px solid rgba(255,255,255,0.1);
                        padding: 20px;
                        z-index: 99999;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    `;
                    dropdown.innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                            <a href="/" style="color: white; text-decoration: none; font-weight: 500; padding: 12px 20px; border-radius: 8px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); display: block; width: 100%; text-align: center; font-size: 16px;">Home</a>
                            <a href="/about" style="color: white; text-decoration: none; font-weight: 500; padding: 12px 20px; border-radius: 8px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); display: block; width: 100%; text-align: center; font-size: 16px;">About Us</a>
                            <a href="/contact" style="color: white; text-decoration: none; font-weight: 500; padding: 12px 20px; border-radius: 8px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); display: block; width: 100%; text-align: center; font-size: 16px;">Contact</a>
                            <a href="/doctors" style="color: white; text-decoration: none; font-weight: 500; padding: 12px 20px; border-radius: 8px; transition: all 0.3s ease; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); display: block; width: 100%; text-align: center; font-size: 16px;">For Patients</a>
                        </div>
                    `;

                    document.body.appendChild(dropdown);
                    console.log('Created new visible dropdown element');

                    // Store reference to remove it later
                    navLinks._dropdown = dropdown;

                    // Add click outside to close
                    const closeDropdown = (e) => {
                        if (!dropdown.contains(e.target) && e.target !== mobileMenuTrigger && !mobileMenuTrigger.contains(e.target)) {
                            dropdown.remove();
                            navLinks.classList.remove('show-mobile-nav');
                            navLinks._dropdown = null;
                            document.removeEventListener('click', closeDropdown);
                            navLinks._closeHandler = null;
                            console.log('Dropdown closed by clicking outside');
                        }
                    };

                    // Store the handler for cleanup
                    navLinks._closeHandler = closeDropdown;

                    // Add the event listener after a short delay to avoid immediate closing
                    setTimeout(() => {
                        document.addEventListener('click', closeDropdown);
                    }, 100);
                }

                console.log('Navigation links classes:', navLinks.className);
                console.log('Navigation links style display:', navLinks.style.display);

                // Update hamburger animation
                const hamburger = mobileMenuTrigger;
                if (hamburger) {
                    hamburger.classList.toggle('active');
                    console.log('Hamburger active class toggled');
                }
            }

            // Function to close navigation
            function closeMobileNav() {
                console.log('Closing mobile navigation');
                navLinks.classList.remove('show-mobile-nav');

                // Reset hamburger animation
                const hamburger = mobileMenuTrigger;
                if (hamburger) {
                    hamburger.classList.remove('active');
                }
            }

            // Add click event to hamburger
            mobileMenuTrigger.addEventListener('click', function(e) {
                console.log('Hamburger clicked');
                e.preventDefault();
                e.stopPropagation();
                toggleMobileNav();
            });

            // Close navigation when clicking outside
            document.addEventListener('click', function(e) {
                if (!mobileMenuTrigger.contains(e.target) && !navLinks.contains(e.target)) {
                    closeMobileNav();
                }
            });

            // Close navigation on window resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    closeMobileNav();
                }
            });

            // Close navigation when a link is clicked
            navLinks.addEventListener('click', function(e) {
                if (e.target.closest('.top-link')) {
                    closeMobileNav();
                }
            });
        }

        // Force fresh logo loading on mobile devices
        function forceFreshLogoLoading() {
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                const logos = document.querySelectorAll('img[src*="logo-medical"]');
                logos.forEach(img => {
                    const currentSrc = img.src;
                    // Add timestamp to force fresh load
                    const separator = currentSrc.includes('?') ? '&' : '?';
                    img.src = currentSrc + separator + 'force=' + Date.now();
                });
            }
        }

        // Initialize mobile navigation on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Force fresh logo loading
            forceFreshLogoLoading();

            // Initialize mobile navigation toggle
            initializeMobileNavigation();

            // Only log in debug mode
            if (window.location.search.includes('debug=true')) {
            }
        });

        // Add manual test trigger (temporary for debugging)
        window.testMobileNav = function() {
            const navLinks = document.querySelector('.d-none.d-md-flex.align-items-center.gap-4.small');
            if (navLinks) {
                navLinks.classList.toggle('show-mobile-nav');
            }
        };

        // Also initialize on page show (for back/forward navigation)
        window.addEventListener('pageshow', function() {
            // Re-initialize mobile navigation if needed
            initializeMobileNavigation();
        });

        // For SPA-like navigation, reinitialize mobile navigation when needed
        let mobileNavInitialized = false;

        function initializeMobileNavIfNeeded() {
            const mobileMenuTrigger = document.querySelector('.primary-menu-trigger .cnvs-hamburger') ||
                                     document.querySelector('.cnvs-hamburger');

            // Only reinitialize if trigger exists but we haven't initialized recently
            if (mobileMenuTrigger && !mobileNavInitialized) {
                initializeMobileNavigation();
                mobileNavInitialized = true;

                // Reset flag after a delay to allow re-initialization if needed
                setTimeout(() => {
                    mobileNavInitialized = false;
                }, 5000);
            }
        }

        // Check on navigation events instead of continuous polling
        window.addEventListener('popstate', initializeMobileNavIfNeeded);
        window.addEventListener('hashchange', initializeMobileNavIfNeeded);

    // Function to load notifications into dropdown
    function loadNotifications() {
        const notificationList = document.getElementById('notification-list');
        if (!notificationList) return;

        // Only log in debug mode
        if (window.location.search.includes('debug=true')) {
        }

        // Show loading state
        notificationList.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-hourglass-split display-6 d-block mb-2"></i>
                <small>Loading notifications...</small>
            </div>
        `;

        // Fetch notifications from the server
        fetch('/api/notifications')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
 
                if (data.notifications && data.notifications.length > 0) {
                    // Render notifications
                    let html = '';
                    data.notifications.forEach(notification => {
                        const date = new Date(notification.created_at).toLocaleDateString();
                        const time = new Date(notification.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                        html += `
                            <div class="notification-item ${notification.read_at ? 'read' : 'unread'}" data-id="${notification.id}" data-link="${notification.data?.link || ''}" data-message="${notification.data?.message || ''}">
                                <div class="d-flex align-items-start gap-3 p-3 border-bottom">
                                    <div class="notification-icon">
                                        <i class="bi ${notification.data?.icon || 'bi-bell'} text-${notification.data?.color || 'primary'}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0 small">${notification.data?.title || 'Notification'}</h6>
                                            <small class="text-muted">${time}</small>
                                        </div>
                                        <p class="mb-0 small text-muted">${notification.data?.message || 'You have a new notification'}</p>
                                        ${notification.data?.link ? `
                                            <div class="mt-2">
                                                <a href="${notification.data.link}" class="btn btn-sm btn-outline-primary">
                                                    ${notification.data?.link_text || 'View Details'}
                                                </a>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    notificationList.innerHTML = html;

                    // Add click handlers to mark as read and redirect
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            const notificationId = this.dataset.id;
                            const link = this.dataset.link;
                            const message = this.dataset.message;

                            markAsRead(notificationId);

                            // Close the dropdown
                            const dropdown = bootstrap.Dropdown.getInstance(document.querySelector('.notifications-dropdown .dropdown-toggle'));
                            if (dropdown) dropdown.hide();

                            // Small delay to ensure dropdown closes before redirect
                            setTimeout(() => {
                                // Redirect based on notification type
                                if (link) {
                                    window.location.href = link;
                                } else if (message && message.toLowerCase().includes('appointment')) {
                                    window.location.href = '/appointments';
                                } else {
                                    // Default fallback to dashboard
                                    window.location.href = '/dashboard';
                                }
                            }, 100);
                        });
                    });

                } else {
                    // No notifications
                    notificationList.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
                            <small>No notifications</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                notificationList.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-exclamation-triangle display-6 d-block mb-2"></i>
                        <small>Error loading notifications</small>
                    </div>
                `;
            });
    }

    // Function to mark notification as read
    function markAsRead(notificationId) {
        fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                }

                // Update badge count
                updateNotificationBadge();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    // Function to update notification badge
    function updateNotificationBadge() {
        // Use a timeout to prevent hanging requests
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        fetch('/api/notifications/unread-count', {
            signal: controller.signal,
            credentials: 'same-origin'  // Include cookies for authentication
        })
        .then(response => {
            clearTimeout(timeoutId);

            // Check if the response is a redirect
            if (response.redirected) {
                throw new Error('Redirect detected. User may not be authenticated.');
            }

            // Check if response is JSON or HTML (error page)
            const contentType = response.headers.get('content-type');
            if (!response.ok) {
                if (contentType && contentType.includes('text/html')) {
                    // If we get HTML back, it's likely an authentication error
                    return response.text().then(html => {
                        throw new Error('Authentication required. Please log in again.');
                    });
                }
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            // Ensure we have JSON
            if (!contentType || !contentType.includes('json')) {
                throw new Error('Invalid response format. Expected JSON.');
            }

            return response.json();
        })
        .then(data => {
            const badge = document.getElementById('notification-count');
            if (badge) {
                // Handle both old format (data.count) and new format (data.count)
                const count = data.count || 0;

                // Check if user is authenticated
                if (data.authenticated === false) {
                    // User is not authenticated, hide badge
                    badge.style.display = 'none';
                    return;
                }

                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Error updating notification badge:', error);

            // Ensure badge is hidden on error
            const badge = document.getElementById('notification-count');
            if (badge) {
                badge.style.display = 'none';
            }

            // If it's an authentication error, we might want to redirect to login
            if (error.message.includes('Authentication required') || error.message.includes('Redirect detected')) {
                // Optionally: show a message or redirect to login
                // window.location.href = '/login';
            }
        });
    }

    // Load notifications on page load, but skip if we're in the middle of login redirect
    document.addEventListener('DOMContentLoaded', function() {
        // Check if this is a login redirect by looking for the login parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const loginParam = urlParams.get('login');

        // If we have a login parameter in URL, skip notifications for 5 seconds
        if (loginParam === 'success') {
            setTimeout(() => {
                updateNotificationBadge();
            }, 5000); // Wait 5 seconds before enabling notifications
        } else {
            // Otherwise, load notifications normally with a small delay
            setTimeout(() => {
                updateNotificationBadge();
            }, 1000);
        }
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
<script src="{{ asset('js/notification-diagnostics.js') }}"></script>
<script src="{{ asset('js/pusher-raw-debug.js') }}"></script>
<script src="{{ asset('js/laravel-notification-catcher.js') }}"></script>
<script src="{{ asset('js/appointment-notification-debug.js') }}"></script>
<script src="{{ asset('js/backend-diagnosis.js') }}"></script>
@endif

@endauth

<!-- WebSocket Test Scripts (only in development) -->
@if(config('app.debug'))
<script src="{{ asset('js/websocket-test.js') }}"></script>
<script src="{{ asset('js/pusher-connection-test.js') }}"></script>
@endif

<script>
// Prevent notification API calls immediately after login
(function() {
    // Check if we have a login parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const loginParam = urlParams.get('login');

    // If we have a login parameter, override the updateNotificationBadge function
    if (loginParam === 'success') {
        // Temporarily replace updateNotificationBadge with a no-op
        const originalUpdateNotificationBadge = window.updateNotificationBadge;
        window.updateNotificationBadge = function() {
            // Restore the original function after a delay
            setTimeout(() => {
                window.updateNotificationBadge = originalUpdateNotificationBadge;
                // Call it once more after the delay
                if (originalUpdateNotificationBadge) {
                    originalUpdateNotificationBadge();
                }
            }, 3000); // Wait 3 seconds before enabling notifications
        };
    }
})();
</script>

    </body>

    </html>
