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
        opacity: 0.8 !important;
    }
    
    /* Make sure hamburger lines are visible */
    .cnvs-hamburger-inner,
    .cnvs-hamburger-inner::before,
    .cnvs-hamburger-inner::after {
        background-color: #333 !important;
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

/* Ensure dropdown shows above all content */
.dropdown.show .dropdown-menu {
    z-index: 999999 !important;
    display: block !important;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Temporarily disabled local font-icons -->
    <!-- <link rel="stylesheet" href="{{ asset('css/font-icons.css') }}"> -->
    <link rel="stylesheet" href="{{ asset('demos/medical/css/medical-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper.css') }}">
    <!-- Minimal Google Fonts for fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logo-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-modals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom-buttons.css') }}">
    @stack('styles')

    <!-- Global Font Styling -->
    <style>
        /* Clean Medical System Font */
        body, * {
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
        h1, h2, h3, h4, h5, h6, .heading {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif !important;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        /* Medical/clinical text */
        .medical-text, .diagnosis-text, .case-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif !important;
            font-weight: 400;
            line-height: 1.6;
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
        .primary-menu .menu-item + .menu-item {
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
            z-index: 999999 !important;
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
                flex-wrap: nowrap !important;
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
            }
            
            /* Logo and nav container adjustments for mobile */
            .d-flex.align-items-center.flex-grow-1 {
                display: flex !important;
                align-items: center !important;
                flex-grow: 1 !important;
            }
            
            /* Logo adjustments for mobile */
            #logo {
                margin-right: 1rem !important;
                flex-shrink: 0 !important;
            }
            
            #logo img {
                width: 120px !important;
                max-width: 120px !important;
                height: auto !important;
                display: block !important;
            }
            
            /* Hide desktop navigation */
            .primary-menu {
                display: none !important;
            }
            
            /* Show mobile menu trigger - override Bootstrap classes */
            .primary-menu-trigger,
            .primary-menu-trigger.d-block.d-lg-none {
                display: block !important;
                margin-left: auto !important;
                padding: 0.5rem !important;
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
        .primary-menu .menu-item:hover > .menu-link {
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
                <div><i class="bi bi-envelope me-1"></i> <a href="mailto:info@medcuraai.com" class="text-decoration-none text-white-50">info@medcuraai.com</a></div>
            </div>

            <!-- Right Side: Auth + Quick Actions -->
            <div class="col-md-auto d-flex justify-content-end align-items-center gap-3">

                @auth
                <!-- Quick Action Button for Emergency -->
                <a href="{{ route('ask-ai') }}" class="btn btn-sm px-3 py-1 me-2"
                   style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 20px; font-size: 12px;">
                    <i class="bi bi-lightning-charge me-1"></i> Quick Diagnosis
                </a>

                <div class="dropdown">
                    <a class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                        <i class="bi bi-person-circle"></i>
                        <div class="d-flex flex-column align-items-start">
                            <span>{{ Auth::user()->name }}</span>
                            @if(Auth::user()->isSubUser())
                                <small class="opacity-75">{{ \App\Helpers\MenuHelper::getUserRoleDisplay(Auth::user()) }}</small>
                            @endif
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
                <div class="d-flex align-items-center flex-grow-1">
                    <!-- Logo -->
                    <div id="logo" class="me-4 flex-shrink-0">
                        <a href="@auth{{ route('dashboard') }}@else{{ url('/') }}@endauth">
                            <img style="width: 140px; height: auto;" class="logo-default img-fluid"
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

                                    @foreach($menuItems as $item)
                                        @if(isset($item['dropdown']) && $item['dropdown'])
                                            <!-- Dropdown Menu Item -->
                                            <li class="menu-item {{ collect($item['items'])->contains(fn($subItem) => request()->routeIs($subItem['route'] ?? '')) ? 'current' : '' }}">
                                                <a class="menu-link" href="#"><div>{{ $item['name'] }} <i class="fas fa-chevron-down"></i></div></a>
                                                <ul class="sub-menu-container">
                                                    @foreach($item['items'] as $subItem)
                                                        <li class="menu-item {{ request()->routeIs($subItem['route'] ?? '') ? 'current' : '' }}">
                                                            <a class="menu-link" href="{{ isset($subItem['route']) ? route($subItem['route']) : '#' }}">
                                                                <div>
                                                                    @if(isset($subItem['icon']))
                                                                        <i class="{{ $subItem['icon'] }} me-2"></i>
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
                                            <li class="menu-item {{ request()->routeIs($item['route'] ?? '') ? 'current' : '' }}">
                                                <a class="menu-link" href="{{ isset($item['route']) ? route($item['route']) : '#' }}">
                                                    <div>
                                                        @if(isset($item['icon']))
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
                                    <a class="menu-link" href="{{ url('/') }}"><div>Home</div></a>
                                </li>
                                <li class="menu-item {{ request()->is('about') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('about') }}"><div>About Us</div></a>
                                </li>
                                <li class="menu-item {{ request()->is('contact') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('contact') }}"><div>Contact</div></a>
                                </li>
                                <li class="menu-item {{ request()->is('doctors') ? 'current' : '' }}">
                                    <a class="menu-link" href="{{ route('doctors.index') }}"><div>For Patients</div></a>
                                </li>
                            @endguest
                        </ul>
                    </nav>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="primary-menu-trigger d-block d-lg-none flex-shrink-0 ms-auto">
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
        @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="container-fluid px-0">
                <div class="row">
                    <div class="col-12">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show m-0 rounded-0 border-0" role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Success!</strong> {{ session('success') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show m-0 rounded-0 border-0" role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Error!</strong> {{ session('error') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show m-0 rounded-0 border-0" role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Warning!</strong> {{ session('warning') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(session('info'))
                            <div class="alert alert-info alert-dismissible fade show m-0 rounded-0 border-0" role="alert">
                                <div class="container">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Info!</strong> {{ session('info') }}
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
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
	<div id="gotoTop" class="fas fa-chevron-up rounded-circle" style="position: fixed; bottom: 20px; right: 20px; width: 40px; height: 40px; background-color: #DE6262; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 1000; opacity: 0.8; transition: opacity 0.3s;"></div>

	<!-- JavaScripts
	============================================= -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/plugins.min.js') }}"></script>
    <script src="{{ asset('js/functions.bundle.js') }}"></script>

    @stack('scripts')

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

        // NEW SIMPLE MOBILE MENU - Bottom Sheet Style
        function createBottomSheetMenu() {
            const body = document.body;
            
            // Try multiple selectors to find the hamburger button
            let mobileMenuTrigger = document.querySelector('.primary-menu-trigger .cnvs-hamburger');
            if (!mobileMenuTrigger) {
                mobileMenuTrigger = document.querySelector('.cnvs-hamburger');
            }
            if (!mobileMenuTrigger) {
                mobileMenuTrigger = document.querySelector('button[title="Open Mobile Menu"]');
            }
            
            console.log('Looking for mobile trigger...', mobileMenuTrigger);
            
            if (!mobileMenuTrigger) {
                console.log('Mobile trigger not found - available buttons:', document.querySelectorAll('button'));
                return;
            }
            
            console.log('Mobile trigger found:', mobileMenuTrigger);
            
            // Remove existing mobile menu if it exists
            const existingMobileMenu = document.querySelector('.bottom-sheet-menu');
            const existingOverlay = document.querySelector('.bottom-sheet-overlay');
            if (existingMobileMenu) existingMobileMenu.remove();
            if (existingOverlay) existingOverlay.remove();
            
            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'bottom-sheet-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999998;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            `;
            
            // Create bottom sheet menu
            const bottomSheet = document.createElement('div');
            bottomSheet.className = 'bottom-sheet-menu';
            bottomSheet.style.cssText = `
                position: fixed;
                bottom: -100%;
                left: 0;
                width: 100%;
                max-height: 80vh;
                background: white;
                z-index: 999999;
                border-radius: 20px 20px 0 0;
                box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.2);
                transition: bottom 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                overflow-y: auto;
            `;
            
            // Create menu content
            const menuContent = `
                <div style="
                    padding: 20px;
                    border-bottom: 1px solid #eee;
                    background: linear-gradient(135deg, #DE6262 0%, #c54545 100%);
                    color: white;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-bars me-2"></i>Navigation Menu
                    </h3>
                    <button class="close-bottom-sheet" style="
                        background: none;
                        border: none;
                        color: white;
                        font-size: 24px;
                        cursor: pointer;
                        padding: 5px;
                    ">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="menu-items-container" style="padding: 20px;">
                    <!-- Menu items will be added here -->
                </div>
            `;
            
            bottomSheet.innerHTML = menuContent;
            
            // Add menu items
            const menuItemsContainer = bottomSheet.querySelector('.menu-items-container');
            
            // Define menu items manually (since cloning was problematic)
            const menuItems = [
                {
                    title: 'Dashboard',
                    icon: 'fas fa-tachometer-alt',
                    url: '{{ route("dashboard") }}',
                    submenu: null
                },
                {
                    title: 'Ask AI',
                    icon: 'fas fa-robot',
                    url: '{{ route("ask-ai") }}',
                    submenu: null
                },
                {
                    title: 'Voice Assistant',
                    icon: 'fas fa-microphone',
                    url: '{{ route("voice-assistant.index") }}',
                    submenu: null
                },
                {
                    title: 'Medical Tools',
                    icon: 'fas fa-stethoscope',
                    url: '#',
                    submenu: [
                        { title: 'Cases', icon: 'fas fa-folder-medical', url: '/cases' },
                        { title: 'Diagnosis', icon: 'fas fa-diagnoses', url: '/diagnosis' },
                        { title: 'Medical Notes', icon: 'fas fa-notes-medical', url: '/medical-notes' }
                    ]
                },
                {
                    title: 'Appointments',
                    icon: 'fas fa-calendar-check',
                    url: '#',
                    submenu: [
                        { title: 'View Appointments', icon: 'fas fa-calendar', url: '{{ route("appointments.index") }}' },
                        { title: 'Reviews', icon: 'fas fa-star', url: '{{ route("reviews.index") }}' }
                    ]
                },
                {
                    title: 'Sub Users',
                    icon: 'fas fa-users',
                    url: '{{ route("sub-users.index") }}',
                    submenu: null
                },

                {
                    title: 'Profile',
                    icon: 'fas fa-user',
                    url: '{{ route("doctor.profile.edit") }}',
                    submenu: null
                },
                {
                    title: 'Settings',
                    icon: 'fas fa-cog',
                    url: '{{ route("settings") }}',
                    submenu: null
                }
            ];

            // Add admin menu item if user is admin
            const isAdmin = {{ Auth::check() && Auth::user() && Auth::user()->isAdmin() ? 'true' : 'false' }};
            if (isAdmin) {
                menuItems.splice(-2, 0, {
                    title: 'Admin Panel',
                    icon: 'fas fa-cog',
                    url: '#',
                    submenu: [
                        { title: 'Dashboard', icon: 'fas fa-tachometer-alt', url: '{{ route("admin.dashboard") }}' },
                        { title: 'User Management', icon: 'fas fa-users-cog', url: '{{ route("admin.users.index") }}' },
                        { title: 'System Settings', icon: 'fas fa-sliders-h', url: '{{ route("admin.system-settings") }}' },
                        { title: 'Billing', icon: 'fas fa-dollar-sign', url: '{{ route("admin.billing") }}' }
                    ]
                });
            }
            
            // Create menu items HTML
            menuItems.forEach(item => {
                const menuItem = document.createElement('div');
                menuItem.className = 'bottom-sheet-menu-item';
                menuItem.style.cssText = `
                    margin-bottom: 8px;
                    border-radius: 12px;
                    background: #f8f9fa;
                    border: 1px solid #e9ecef;
                    overflow: hidden;
                `;
                
                if (item.submenu) {
                    // Menu item with submenu
                    menuItem.innerHTML = `
                        <div class="menu-item-header" style="
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            padding: 16px 20px;
                            cursor: pointer;
                            background: white;
                            transition: all 0.2s ease;
                        ">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <i class="${item.icon}" style="color: #DE6262; width: 20px; text-align: center;"></i>
                                <span style="font-weight: 500; color: #333;">${item.title}</span>
                            </div>
                            <i class="fas fa-chevron-down" style="color: #666; transition: transform 0.3s ease;"></i>
                        </div>
                        <div class="submenu-items" style="
                            display: none;
                            background: #f8f9fa;
                            border-top: 1px solid #e9ecef;
                        ">
                            ${item.submenu.map(subItem => `
                                <a href="${subItem.url}" style="
                                    display: flex;
                                    align-items: center;
                                    gap: 12px;
                                    padding: 12px 20px 12px 52px;
                                    color: #555;
                                    text-decoration: none;
                                    transition: all 0.2s ease;
                                    border-bottom: 1px solid rgba(0,0,0,0.05);
                                " class="submenu-link">
                                    <i class="${subItem.icon}" style="color: #DE6262; width: 16px; text-align: center; font-size: 14px;"></i>
                                    <span style="font-size: 14px;">${subItem.title}</span>
                                </a>
                            `).join('')}
                        </div>
                    `;
                    
                    // Add click handler for dropdown
                    const header = menuItem.querySelector('.menu-item-header');
                    const submenu = menuItem.querySelector('.submenu-items');
                    const arrow = menuItem.querySelector('.fa-chevron-down');
                    
                    header.addEventListener('click', function() {
                        const isOpen = submenu.style.display === 'block';
                        
                        // Close all other submenus
                        bottomSheet.querySelectorAll('.submenu-items').forEach(sub => {
                            if (sub !== submenu) {
                                sub.style.display = 'none';
                            }
                        });
                        bottomSheet.querySelectorAll('.fa-chevron-down').forEach(arr => {
                            if (arr !== arrow) {
                                arr.style.transform = 'rotate(0deg)';
                            }
                        });
                        
                        // Toggle current submenu
                        if (isOpen) {
                            submenu.style.display = 'none';
                            arrow.style.transform = 'rotate(0deg)';
                        } else {
                            submenu.style.display = 'block';
                            arrow.style.transform = 'rotate(180deg)';
                        }
                    });
                    
                    // Add hover effects for submenu links
                    menuItem.querySelectorAll('.submenu-link').forEach(link => {
                        link.addEventListener('mouseenter', function() {
                            this.style.background = 'rgba(222, 98, 98, 0.1)';
                            this.style.color = '#DE6262';
                        });
                        link.addEventListener('mouseleave', function() {
                            this.style.background = 'transparent';
                            this.style.color = '#555';
                        });
                        link.addEventListener('click', function() {
                            closeBottomSheet();
                        });
                    });
                    
                } else {
                    // Simple menu item
                    menuItem.innerHTML = `
                        <a href="${item.url}" style="
                            display: flex;
                            align-items: center;
                            gap: 12px;
                            padding: 16px 20px;
                            color: #333;
                            text-decoration: none;
                            background: white;
                            transition: all 0.2s ease;
                        " class="simple-menu-link">
                            <i class="${item.icon}" style="color: #DE6262; width: 20px; text-align: center;"></i>
                            <span style="font-weight: 500;">${item.title}</span>
                        </a>
                    `;
                    
                    // Add hover effect and close on click
                    const link = menuItem.querySelector('.simple-menu-link');
                    link.addEventListener('mouseenter', function() {
                        this.style.background = 'rgba(222, 98, 98, 0.05)';
                        this.style.color = '#DE6262';
                    });
                    link.addEventListener('mouseleave', function() {
                        this.style.background = 'white';
                        this.style.color = '#333';
                    });
                    link.addEventListener('click', function() {
                        closeBottomSheet();
                    });
                }
                
                menuItemsContainer.appendChild(menuItem);
            });
            
            // Add to body
            body.appendChild(overlay);
            body.appendChild(bottomSheet);
            
            // Functions to open/close
            function openBottomSheet() {
                overlay.style.opacity = '1';
                overlay.style.visibility = 'visible';
                bottomSheet.style.bottom = '0';
                body.style.overflow = 'hidden';
            }
            
            function closeBottomSheet() {
                overlay.style.opacity = '0';
                overlay.style.visibility = 'hidden';
                bottomSheet.style.bottom = '-100%';
                body.style.overflow = '';
                
                // Close all submenus
                bottomSheet.querySelectorAll('.submenu-items').forEach(sub => {
                    sub.style.display = 'none';
                });
                bottomSheet.querySelectorAll('.fa-chevron-down').forEach(arrow => {
                    arrow.style.transform = 'rotate(0deg)';
                });
            }
            
            // Event listeners
            mobileMenuTrigger.addEventListener('click', openBottomSheet);
            overlay.addEventListener('click', closeBottomSheet);
            bottomSheet.querySelector('.close-bottom-sheet').addEventListener('click', closeBottomSheet);
            
            // Close on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    closeBottomSheet();
                }
            });
            
            console.log('Bottom sheet mobile menu created successfully');
        }
        
        // Initialize mobile menu on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - calling createBottomSheetMenu');
            console.log('Available hamburger buttons:', document.querySelectorAll('.cnvs-hamburger'));
            console.log('Available primary-menu-trigger:', document.querySelectorAll('.primary-menu-trigger'));
            createBottomSheetMenu();
            
            // Fallback: Add click listener directly to hamburger button
            const hamburger = document.querySelector('.cnvs-hamburger');
            if (hamburger) {
                console.log('Adding fallback click listener to hamburger');
                hamburger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Hamburger clicked!', e);
                    
                    // Try to trigger bottom sheet
                    const bottomSheet = document.querySelector('.bottom-sheet-menu');
                    const overlay = document.querySelector('.bottom-sheet-overlay');
                    if (bottomSheet && overlay) {
                        console.log('Bottom sheet found, opening...');
                        bottomSheet.style.bottom = '0';
                        overlay.style.opacity = '1';
                        overlay.style.visibility = 'visible';
                        document.body.style.overflow = 'hidden';
                    } else {
                        console.log('Bottom sheet not found, recreating...');
                        createBottomSheetMenu();
                        // Try again after recreation
                        setTimeout(() => {
                            const newBottomSheet = document.querySelector('.bottom-sheet-menu');
                            const newOverlay = document.querySelector('.bottom-sheet-overlay');
                            if (newBottomSheet && newOverlay) {
                                newBottomSheet.style.bottom = '0';
                                newOverlay.style.opacity = '1';
                                newOverlay.style.visibility = 'visible';
                                document.body.style.overflow = 'hidden';
                            }
                        }, 100);
                    }
                });
            } else {
                console.log('Hamburger button not found!');
            }
        });
        
        // Add manual test trigger (temporary for debugging)
        window.testMobileMenu = function() {
            console.log('Manual test triggered');
            const bottomSheet = document.querySelector('.bottom-sheet-menu');
            const overlay = document.querySelector('.bottom-sheet-overlay');
            if (bottomSheet && overlay) {
                console.log('Opening existing bottom sheet');
                bottomSheet.style.bottom = '0';
                overlay.style.opacity = '1';
                overlay.style.visibility = 'visible';
                document.body.style.overflow = 'hidden';
            } else {
                console.log('Creating new bottom sheet');
                createBottomSheetMenu();
                setTimeout(() => {
                    const newBottomSheet = document.querySelector('.bottom-sheet-menu');
                    const newOverlay = document.querySelector('.bottom-sheet-overlay');
                    if (newBottomSheet && newOverlay) {
                        newBottomSheet.style.bottom = '0';
                        newOverlay.style.opacity = '1';
                        newOverlay.style.visibility = 'visible';
                        document.body.style.overflow = 'hidden';
                    }
                }, 100);
            }
        };

        // Also initialize on page show (for back/forward navigation)
        window.addEventListener('pageshow', function() {
            console.log('Page Show - calling createBottomSheetMenu');
            createBottomSheetMenu();
        });
        
        // For SPA-like navigation, also try to reinitialize periodically
        setInterval(function() {
            const existingMobileMenu = document.querySelector('.bottom-sheet-menu');
            const mobileMenuTrigger = document.querySelector('.primary-menu-trigger .cnvs-hamburger');
            
            // Only reinitialize if elements exist but mobile menu doesn't
            if (mobileMenuTrigger && !existingMobileMenu) {
                console.log('Reinitializing mobile menu due to missing elements');
                createBottomSheetMenu();
            }
        }, 2000); // Check every 2 seconds
    </script>

</body>
</html>
