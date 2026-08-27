<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — MedCura')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')
    <style>
        :root{--sidebar-w:280px}
        *{font-family:'Inter',system-ui,sans-serif!important}
        .fa,.fas,.far,.fab,[class^="fa-"]{font-family:"Font Awesome 6 Free","Font Awesome 6 Brands"!important;font-style:normal!important;-webkit-font-smoothing:antialiased!important}
        .fas,.fa-solid{font-weight:900!important}
        body{background:#f8fafc;color:#0f172a}
        .admin-shell{display:flex;min-height:100vh}
        .admin-sidebar{width:280px;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);color:#cbd5e1;position:fixed;inset:0 auto 0 0;z-index:1040;display:flex;flex-direction:column;border-right:1px solid rgba(255,255,255,.06)}
        .sidebar-brand{padding:16px;border-bottom:1px solid rgba(255,255,255,.07)}
        .brand-card{background:#fff;border-radius:14px;padding:10px 12px;display:flex;align-items:center;gap:10px}
        .brand-card img{width:36px;height:36px;object-fit:contain}
        .brand-title{font-weight:800;color:#0f172a;font-size:.92rem}
        .brand-sub{font-size:.65rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
        .sidebar-search{padding:10px 12px}
        .sidebar-search input{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:10px;height:38px;padding-left:34px;font-size:.84rem;width:100%}
        .sidebar-search input::placeholder{color:rgba(255,255,255,.45)}
        .sidebar-nav{flex:1;overflow-y:auto;padding:8px 10px}
        .nav-group{margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.04)}
        .nav-group:last-of-type{border-bottom:none}
        .nav-group-title{font-size:.62rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;padding:6px 8px}
        .nav-link-pro{display:flex;align-items:center;gap:10px;padding:7px 10px 7px 32px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:.82rem;font-weight:500;border:1px solid transparent;position:relative}
        .nav-link-pro::before{content:"";position:absolute;left:14px;top:50%;transform:translateY(-50%);width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.12)}
        .nav-link-pro:hover{background:rgba(255,255,255,.07);color:#e2e8f0}
        .nav-link-pro:hover::before{background:rgba(255,255,255,.32)}
        .nav-link-pro.active{background:rgba(255,255,255,.11);color:#fff;border-color:rgba(222,98,98,.22);font-weight:700}
        .nav-link-pro.active::before{background:#DE6262;border-color:#fecaca;box-shadow:0 0 0 3px rgba(222,98,98,.18)}
        .nav-group-header{width:100%;display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:10px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);border-left:3px solid #475569;color:#e2e8f0;font-size:.7rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;box-shadow:0 1px 6px rgba(0,0,0,.12)}
        .nav-group-header:hover{background:rgba(255,255,255,.12);border-left-color:#94a3b8}
        .nav-group-header.active{background:rgba(222,98,98,.16);border-color:rgba(222,98,98,.3);border-left-color:#DE6262;color:#fecaca;box-shadow:0 2px 10px rgba(220,38,38,.15)}
        .nav-group-header .hg-icon{width:22px;height:22px;border-radius:7px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:.7rem}
        .nav-group-header .hg-chevron{transition:.2s}
        .nav-group-header[aria-expanded="true"] .hg-chevron{transform:rotate(180deg)}
        .sidebar-collapse{display:block}
        .sidebar-collapse:not(.show){display:none!important}
        .user-card{margin:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:10px;display:flex;align-items:center;gap:10px}
        .user-card .avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#DE6262,#991b1b);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800}
        .admin-main{flex:1;margin-left:280px;display:flex;flex-direction:column;min-height:100vh}
        .admin-content{padding:24px;flex:1}
        .mobile-toggle{display:none}
        .overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1039}
        .overlay.show{display:block}
        @media(max-width:991px){.admin-sidebar{transform:translateX(-100%);transition:.25s}.admin-sidebar.show{transform:none}.admin-main{margin-left:0}.mobile-toggle{display:inline-flex;position:fixed;top:10px;left:10px;z-index:1035}}
        #toastStack{position:fixed;top:14px;right:14px;z-index:1080;display:flex;flex-direction:column;gap:10px;max-width:440px;pointer-events:none}
        .toast-pro{pointer-events:auto;background:#fff;border:1px solid #eef2f7;border-left:4px solid #e2e8f0;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.12);padding:12px 14px;display:flex;gap:12px}
        .toast-pro.success{border-left-color:#10b981}
        .toast-pro.error{border-left-color:#ef4444}
        .toast-pro.impersonation{border-left-color:#dc2626;background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;border-color:rgba(255,255,255,.08)}
        .toast-pro .t-icon{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .toast-pro.success .t-icon{background:#ecfdf5;color:#059669}
        .toast-pro.impersonation .t-icon{background:rgba(255,255,255,.14);color:#fff}
        .toast-pro .t-body{flex:1}
        .toast-pro .t-title{font-weight:800;font-size:.84rem}
        .toast-pro.impersonation .t-title{color:#fff}
        .toast-pro .t-msg{font-size:.82rem;color:#475569}
        .toast-pro.impersonation .t-msg{color:rgba(255,255,255,.82)}
        .toast-pro .t-close{width:28px;height:28px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;display:flex;align-items:center;justify-content:center}
    </style>
</head>
<body>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="admin-shell">
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none">
                <div class="brand-card">
                    <img src="{{ asset('demos/medical/images/logo-medical.png') }}" alt="MedCura">
                    <div><div class="brand-title">MedCura</div><div class="brand-sub">Admin · Control Center</div></div>
                    <span class="ms-auto badge" style="background:#0f172a;color:#fff;border-radius:20px;font-size:.62rem;padding:4px 8px">ADMIN</span>
                </div>
            </a>
            <div class="sidebar-search mt-2">
                <div class="position-relative">
                    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.45);font-size:.78rem"></i>
                    <input id="sidebarSearch" type="search" placeholder="Search menu…">
                </div>
            </div>
        </div>
        <nav class="sidebar-nav" id="sidebarNav">
            @php $is = fn($p)=>request()->routeIs($p); @endphp
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.dashboard') || $is('admin.usage-analytics') ? 'active' : '' }}" data-target="grp-dashboard" aria-expanded="{{ $is('admin.dashboard') || $is('admin.usage-analytics') ? 'true' : 'false' }}"><span><i class="fas fa-compass me-2"></i>Dashboard</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-dashboard" class="sidebar-collapse {{ $is('admin.dashboard') || $is('admin.usage-analytics') ? 'show' : '' }}">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link-pro mt-2 {{ $is('admin.dashboard')?'active':'' }}"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="{{ route('admin.usage-analytics') }}" class="nav-link-pro {{ $is('admin.usage-analytics')?'active':'' }}"><i class="fas fa-chart-line"></i> Usage Analytics</a>
                </div>
            </div>
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.users.*') ? 'active' : '' }}" data-target="grp-users" aria-expanded="{{ $is('admin.users.*') ? 'true' : 'false' }}"><span><i class="fas fa-users me-2"></i>User Management</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-users" class="sidebar-collapse {{ $is('admin.users.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.users.index') }}" class="nav-link-pro mt-2 {{ $is('admin.users.*') && !$is('admin.users.create') ? 'active' : '' }}"><i class="fas fa-users"></i> Manage Users</a>
                    <a href="{{ route('admin.users.create') }}" class="nav-link-pro {{ $is('admin.users.create')?'active':'' }}"><i class="fas fa-user-plus"></i> Add New User</a>
                </div>
            </div>
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.appointments.*') || $is('admin.diagnoses.*') ? 'active' : '' }}" data-target="grp-appointments" aria-expanded="{{ $is('admin.appointments.*') || $is('admin.diagnoses.*') ? 'true' : 'false' }}"><span><i class="fas fa-calendar-check me-2"></i>Appointments</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-appointments" class="sidebar-collapse {{ $is('admin.appointments.*') || $is('admin.diagnoses.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.appointments.index') }}" class="nav-link-pro mt-2 {{ $is('admin.appointments.*')?'active':'' }}"><i class="fas fa-calendar-check"></i> All Appointments</a>
                    <a href="{{ route('admin.diagnoses.index') }}" class="nav-link-pro {{ $is('admin.diagnoses.*')?'active':'' }}"><i class="fas fa-file-medical"></i> All Diagnoses</a>
                </div>
            </div>
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.kiosks.*') ? 'active' : '' }}" data-target="grp-kiosks" aria-expanded="{{ $is('admin.kiosks.*') ? 'true' : 'false' }}"><span><i class="fas fa-desktop me-2"></i>Kiosks</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-kiosks" class="sidebar-collapse {{ $is('admin.kiosks.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.kiosks.index') }}" class="nav-link-pro mt-2 {{ $is('admin.kiosks.*') && !$is('admin.kiosks.create') ? 'active' : '' }}"><i class="fas fa-desktop"></i> Kiosks</a>
                    <a href="{{ route('admin.kiosks.create') }}" class="nav-link-pro {{ $is('admin.kiosks.create')?'active':'' }}"><i class="fas fa-plus"></i> Add Kiosk</a>
                </div>
            </div>
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.exercises.*') || $is('admin.hep-templates.*') ? 'active' : '' }}" data-target="grp-exercises" aria-expanded="{{ $is('admin.exercises.*') || $is('admin.hep-templates.*') ? 'true' : 'false' }}"><span><i class="fas fa-dumbbell me-2"></i>Clinical Library</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-exercises" class="sidebar-collapse {{ $is('admin.exercises.*') || $is('admin.hep-templates.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.exercises.index') }}" class="nav-link-pro mt-2 {{ $is('admin.exercises.*')?'active':'' }}"><i class="fas fa-dumbbell"></i> Exercises</a>
                    <a href="{{ route('admin.hep-templates.index') }}" class="nav-link-pro {{ $is('admin.hep-templates.*')?'active':'' }}"><i class="fas fa-clipboard-list"></i> HEP Templates</a>
                </div>
            </div>
            {{-- Payers & Rules - DISABLED (billing not used) --}}
            {{-- <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.payers.*') || $is('admin.compliance.*') || $is('admin.alerts.*') ? 'active' : '' }}" data-target="grp-payers" aria-expanded="{{ $is('admin.payers.*') || $is('admin.compliance.*') || $is('admin.alerts.*') ? 'true' : 'false' }}"><span><i class="fas fa-hand-holding-usd me-2"></i>Payers & Rules</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-payers" class="sidebar-collapse {{ $is('admin.payers.*') || $is('admin.compliance.*') || $is('admin.alerts.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.payers.index') }}" class="nav-link-pro mt-2 {{ $is('admin.payers.index') || $is('admin.payers.show') || $is('admin.payers.create') || $is('admin.payers.edit') ? 'active' : '' }}"><i class="fas fa-building"></i> Payers</a>
                    <a href="{{ route('admin.compliance.dashboard') }}" class="nav-link-pro {{ $is('admin.compliance.*')?'active':'' }}"><i class="fas fa-balance-scale"></i> Compliance</a>
                    <a href="{{ route('admin.alerts.index') }}" class="nav-link-pro {{ $is('admin.alerts.*')?'active':'' }}"><i class="fas fa-bell"></i> Alerts</a>
                </div>
            </div> --}}
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.contact-submissions*') || $is('admin.data-migration.*') || $is('security.*') ? 'active' : '' }}" data-target="grp-ops-tools" aria-expanded="{{ $is('admin.contact-submissions*') || $is('admin.data-migration.*') || $is('security.*') ? 'true' : 'false' }}"><span><i class="fas fa-cogs me-2"></i>Operations</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-ops-tools" class="sidebar-collapse {{ $is('admin.contact-submissions*') || $is('admin.data-migration.*') || $is('security.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.contact-submissions') }}" class="nav-link-pro mt-2 {{ $is('admin.contact-submissions*')?'active':'' }}"><i class="fas fa-inbox"></i> Contact Submissions</a>
                    <a href="{{ route('admin.data-migration.index') }}" class="nav-link-pro {{ $is('admin.data-migration.*')?'active':'' }}"><i class="fas fa-database"></i> Data Migration</a>
                    <a href="{{ route('security.dashboard') }}" class="nav-link-pro {{ $is('security.dashboard')?'active':'' }}"><i class="fas fa-shield-alt"></i> Security</a>
                </div>
            </div>
            <div class="nav-group">
                <button class="nav-group-header {{ $is('admin.sms-settings*') || $is('admin.settings.*') ? 'active' : '' }}" data-target="grp-system" aria-expanded="{{ $is('admin.sms-settings*') || $is('admin.settings.*') ? 'true' : 'false' }}"><span><i class="fas fa-cogs me-2"></i>System</span><i class="fas fa-chevron-down hg-chevron"></i></button>
                <div id="grp-system" class="sidebar-collapse {{ $is('admin.sms-settings*') || $is('admin.settings.*') ? 'show' : '' }}">
                    <a href="{{ route('admin.sms-settings') }}" class="nav-link-pro mt-2 {{ $is('admin.sms-settings*')?'active':'' }}"><i class="fas fa-sms"></i> SMS Settings</a>
                    <a href="{{ route('admin.settings.index') }}" class="nav-link-pro {{ $is('admin.settings.*')?'active':'' }}"><i class="fas fa-sliders-h"></i> Transcription</a>
                </div>
            </div>
            <div id="noResults" class="text-center py-3 d-none" style="color:rgba(255,255,255,.45);font-size:.78rem">No results</div>
        </nav>
        <div class="user-card">
            <div class="avatar">{{ strtoupper(substr(Auth::guard('admin')->user()->name ?? 'A',0,1)) }}</div>
            <div class="flex-grow-1 min-w-0"><div style="font-weight:700;color:#fff;font-size:.84rem" class="text-truncate">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</div><div style="font-size:.72rem;color:#94a3b8" class="text-truncate">{{ Auth::guard('admin')->user()->email ?? '' }}</div></div>
            <form method="POST" action="{{ route('admin.logout') }}" class="m-0">@csrf<button class="btn btn-sm" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:10px"><i class="fas fa-sign-out-alt"></i></button></form>
        </div>
    </aside>
    <div class="admin-main">
        <button class="btn btn-light border mobile-toggle" onclick="toggleSidebar()" style="border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08)"><i class="fas fa-bars"></i></button>
        <main class="admin-content">
            @if(session('success')||session('error'))
                <div id="toastStack">
                    @if(session('success'))<div class="toast-pro success"><div class="t-icon"><i class="fas fa-check"></i></div><div class="t-body"><div class="t-title">Success</div><div class="t-msg">{{ session('success') }}</div></div><button class="t-close" onclick="this.closest('.toast-pro').remove()"><i class="fas fa-times" style="font-size:.7rem"></i></button></div>@endif
                    @if(session('error'))<div class="toast-pro error"><div class="t-icon"><i class="fas fa-exclamation-circle"></i></div><div class="t-body"><div class="t-title">Error</div><div class="t-msg">{{ session('error') }}</div></div><button class="t-close" onclick="this.closest('.toast-pro').remove()"><i class="fas fa-times"></i></button></div>@endif
                </div>
            @endif
            @php $isAdminImp = session('impersonating_admin_id') && session('impersonating_user_id'); @endphp
            @if($isAdminImp && auth()->check())
                <div id="toastStack" style="top:14px"><div class="toast-pro impersonation"><div class="t-icon"><i class="fas fa-user-shield"></i></div><div class="t-body"><div class="t-title">Admin View</div><div class="t-msg">{{ session('impersonating_admin_name','Admin') }} viewing as {{ auth()->user()->name }}</div><form method="POST" action="{{ route('return-to-admin') }}" class="mt-2">@csrf<button class="btn btn-sm" style="background:#fff;color:#dc2626;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i>Return to Admin</button></form></div></div></div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('show');document.getElementById('overlay').classList.toggle('show')}
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('[data-target]').forEach(b=>b.addEventListener('click',()=>{
        const t=document.getElementById(b.dataset.target); if(!t) return;
        const willOpen=!t.classList.contains('show');
        if(willOpen){
            document.querySelectorAll('.sidebar-collapse').forEach(el=>{ if(el!==t) el.classList.remove('show'); });
            document.querySelectorAll('[data-target]').forEach(h=>{
                if(h!==b){
                    const target=document.getElementById(h.dataset.target);
                    const hasActiveChild=target && target.querySelector('.nav-link-pro.active');
                    if(!hasActiveChild){ h.classList.remove('active'); }
                    h.setAttribute('aria-expanded','false');
                }
            });
        }
        t.classList.toggle('show', willOpen);
        b.setAttribute('aria-expanded', willOpen);
        const hasActiveInClicked = t.querySelector('.nav-link-pro.active');
        b.classList.toggle('active', willOpen || !!hasActiveInClicked);
    }));
    const inp=document.getElementById('sidebarSearch');
    if(inp) inp.addEventListener('input',()=>{
        const q=inp.value.toLowerCase().trim(); let vis=0;
        document.querySelectorAll('.nav-group').forEach(g=>{
            const links=[...g.querySelectorAll('.nav-link-pro')]; let show=g.querySelector('.nav-group-title, .nav-group-header')?.textContent.toLowerCase().includes(q);
            links.forEach(a=>{ const m=!q||a.textContent.toLowerCase().includes(q); a.style.display=m?'':'none'; if(m) show=true; });
            g.style.display=show?'':'none'; if(show) vis++;
        });
        document.getElementById('noResults').classList.toggle('d-none', vis>0||!q);
        if(!q) document.querySelectorAll('.nav-group').forEach(g=>{g.style.display=''; g.querySelectorAll('.nav-link-pro').forEach(a=>a.style.display='')});
    });
});
</script>
@vite(['resources/js/app.js','resources/css/app.css'])
@stack('scripts')
</body>
</html>
