<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', config('app.name'))</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Client resets - keep minimal for email compatibility */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border:0; height:auto; line-height:100%; outline:none; text-decoration:none; }
        body { margin:0 !important; padding:0 !important; width:100% !important; }
        /* System design tokens */
        /* Outlook fallback for gradient */
    </style>
    @stack('email-styles')
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    <!-- Preheader (hidden) -->
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">@yield('preview', config('app.name').' notification')&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;</div>

    <!-- Wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f8fafc;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <!-- Container 600px -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="width:100%;max-width:600px;margin:0 auto;">
                    <!-- Header - matches dashboard-header #2c5aa0 -> #1e3a8a with 3px #10b981 line -->
                    <tr>
                        <td style="background-color:#1e3a8a;background-image:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%);border-radius:12px 12px 0 0;padding:0;overflow:hidden;border:1px solid #1e3a8a;border-bottom:none;">
                            <!-- 3px green line -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="height:3px;line-height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%);background-color:#10b981;font-size:0;">&nbsp;</td></tr></table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding:32px 32px 28px;">
                                        <!-- Logo -->
                                        <div style="font-size:22px;font-weight:800;letter-spacing:-0.02em;color:#ffffff;margin:0 0 14px;">{{ config('app.name', 'MedCura AI') }}</div>
                                        <!-- Title -->
                                        <h1 style="margin:0 0 6px;font-size:22px;font-weight:700;line-height:1.3;color:#ffffff;">@yield('email-title')</h1>
                                        <!-- Subtitle -->
                                        <p style="margin:0;font-size:13px;font-weight:500;line-height:1.5;color:rgba(255,255,255,0.85);">@yield('email-subtitle')</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content Card - matches table-card #fff border #eef2f7 radius12 -->
                    <tr>
                        <td style="background-color:#ffffff;border:1px solid #eef2f7;border-top:none;border-radius:0 0 12px 12px;padding:32px 32px;box-shadow:0 1px 4px rgba(15,23,42,0.04);">
                            <!-- Greeting slot support -->
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:24px 16px 8px;">
                            <p style="margin:0 0 14px;font-size:13px;line-height:1.6;color:#64748b;">This email was sent to you as part of your {{ config('app.name') }} account.</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="font-size:12px;">
                                        <a href="{{ url('/') }}" style="color:#2563eb;text-decoration:none;font-weight:600;margin:0 8px;">Dashboard</a>
                                        <span style="color:#cbd5e1;">·</span>
                                        <a href="{{ url('/contact') }}" style="color:#2563eb;text-decoration:none;font-weight:600;margin:0 8px;">Support</a>
                                        <span style="color:#cbd5e1;">·</span>
                                        <a href="{{ url('/privacy') }}" style="color:#2563eb;text-decoration:none;font-weight:600;margin:0 8px;">Privacy</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:14px 0 0;font-size:11px;color:#94a3b8;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            @yield('footer-content')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Shared component styles - also inlined above for fallback, but kept here for clients that support <style> -->
    <style>
        /* Alerts - match dashboard info-card left-border */
        .alert{padding:14px 16px;border-radius:10px;margin:18px 0;font-size:14px;line-height:1.5;border:1px solid;}
        .alert-success{background:#f0fdf4;border-color:#a7f3d0;color:#065f46;border-left:4px solid #10b981;}
        .alert-warning{background:#fffbeb;border-color:#fde68a;color:#92400e;border-left:4px solid #f59e0b;}
        .alert-danger{background:#fef2f2;border-color:#fecaca;color:#991b1b;border-left:4px solid #ef4444;}
        .alert-info{background:#eff6ff;border-color:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;}
        /* Buttons - bulletproof: use <a> with inline but classes for fallback */
        .btn{display:inline-block;padding:12px 24px;font-size:14px;font-weight:700;text-decoration:none;border-radius:10px;text-align:center;line-height:1;}
        .btn-primary{background-color:#1e293b;color:#ffffff!important;border:1px solid #1e293b;}
        .btn-success{background-color:#0f766e;color:#ffffff!important;}
        .btn-danger{background-color:#dc2626;color:#ffffff!important;}
        .btn-secondary{background-color:#ffffff;color:#1e293b!important;border:1px solid #e2e8f0;}
        /* Info card - matches table-card inner */
        .info-card{background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:18px;margin:18px 0;}
        .info-card-header{font-size:13px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#0f172a;margin:0 0 12px;display:flex;align-items:center;gap:8px;}
        .info-card-icon{width:28px;height:28px;border-radius:8px;background:#1e293b;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:13px;}
        /* Data table - matches doctor-table */
        .data-table{width:100%;border-collapse:collapse;margin:16px 0;background:#ffffff;border:1px solid #eef2f7;border-radius:10px;overflow:hidden;}
        .data-table th{padding:10px 12px;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;text-align:left;}
        .data-table td{padding:12px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;}
        .data-table tr:last-child td{border-bottom:none;}
        /* Badge */
        .badge{display:inline-block;padding:4px 8px;border-radius:99px;font-size:11px;font-weight:700;letter-spacing:0.03em;}
        .badge-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
        .badge-warning{background:#fef3c7;color:#92400e;border:1px solid #fde68a;}
        .badge-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
        .badge-info{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;}
        @media only screen and (max-width:600px){
            .email-container{width:100%!important;}
        }
    </style>
</body>
</html>
