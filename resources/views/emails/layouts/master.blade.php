<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name'))</title>
    <style>
        /* Medical System Email Template - Professional Design */
        
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Main Container */
        .email-wrapper {
            width: 100%;
            padding: 20px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(44, 62, 80, 0.1);
            border: 1px solid rgba(222, 98, 98, 0.1);
        }
        
        /* Header Styles */
        .email-header {
            background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .email-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(0deg); }
            50% { transform: translateX(0%) translateY(0%) rotate(180deg); }
        }
        
        .logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            letter-spacing: -0.5px;
        }
        
        .logo-accent {
            color: #ffffff;
            text-shadow: 0 0 20px rgba(255,255,255,0.5);
        }
        
        .email-title {
            font-size: 24px;
            font-weight: 600;
            margin: 15px 0 10px;
            position: relative;
            z-index: 2;
        }
        
        .email-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        /* Content Styles */
        .email-content {
            padding: 40px 30px;
            background: #ffffff;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .content-text {
            font-size: 16px;
            line-height: 1.7;
            color: #34495e;
            margin-bottom: 20px;
        }
        
        /* Alert Boxes */
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 5px solid;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left-color: #28a745;
            color: #155724;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left-color: #ffc107;
            color: #856404;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 25px;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            margin: 10px 5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(222, 98, 98, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(222, 98, 98, 0.4);
            color: white;
        }
        
        .btn-secondary {
            background: white;
            color: #DE6262;
            border: 2px solid #DE6262;
            box-shadow: 0 4px 15px rgba(222, 98, 98, 0.2);
        }
        
        .btn-secondary:hover {
            background: #DE6262;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(222, 98, 98, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        /* Button Container */
        .btn-container {
            text-align: center;
            margin: 30px 0;
        }
        
        /* Info Cards */
        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid rgba(222, 98, 98, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .info-card-header {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #2c3e50 0%, #DE6262 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        
        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .data-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background: rgba(222, 98, 98, 0.02);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }
        
        .status-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        /* Footer */
        .email-footer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            text-align: center;
            border-top: 1px solid rgba(222, 98, 98, 0.1);
        }
        
        .footer-content {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .footer-links {
            margin: 20px 0;
        }
        
        .footer-link {
            color: #DE6262;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .footer-link:hover {
            color: #2c3e50;
        }
        
        .footer-brand {
            font-weight: 600;
            color: #2c3e50;
            margin-top: 20px;
        }
        
        /* Responsive Design */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            
            .email-container {
                border-radius: 10px;
                margin: 0 10px;
            }
            
            .email-header,
            .email-content,
            .email-footer {
                padding: 25px 20px;
            }
            
            .logo {
                font-size: 28px;
            }
            
            .email-title {
                font-size: 20px;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
            
            .data-table {
                font-size: 14px;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }
        }
        
        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background: #1a1a1a;
                border-color: rgba(222, 98, 98, 0.2);
            }
            
            .email-content {
                background: #1a1a1a;
            }
            
            .content-text {
                color: #e0e0e0;
            }
            
            .greeting {
                color: #ffffff;
            }
            
            .info-card {
                background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
                border-color: rgba(222, 98, 98, 0.2);
            }
            
            .data-table {
                background: #2a2a2a;
            }
            
            .data-table th {
                background: linear-gradient(135deg, #3a3a3a 0%, #2a2a2a 100%);
                color: #ffffff;
            }
            
            .data-table td {
                color: #e0e0e0;
                border-color: #3a3a3a;
            }
        }
        
        /* Print Styles */
        @media print {
            .email-wrapper {
                background: white;
                padding: 0;
            }
            
            .email-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .btn {
                background: #f8f9fa !important;
                color: #2c3e50 !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
    @stack('email-styles')
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <div class="logo">
                    <span class="logo-accent">{{ config('app.name', 'MedCura AI') }}</span>
                </div>
                <h1 class="email-title">@yield('email-title')</h1>
                <p class="email-subtitle">@yield('email-subtitle')</p>
            </div>
            
            <!-- Content -->
            <div class="email-content">
                @yield('content')
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <div class="footer-content">
                    <p>This email was sent to you as part of your {{ config('app.name') }} account.</p>
                    
                    <div class="footer-links">
                        <a href="{{ url('/') }}" class="footer-link">Dashboard</a>
                        <a href="{{ route('subscription.manage') }}" class="footer-link">Manage Subscription</a>
                        <a href="{{ url('/contact') }}" class="footer-link">Contact Support</a>
                        <a href="{{ url('/privacy') }}" class="footer-link">Privacy Policy</a>
                    </div>
                    
                    <div class="footer-brand">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </div>
                    
                    @yield('footer-content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>