<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Patient Portal | MedCura AI')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    @yield('styles')
    @stack('styles')
    <style>
        body{background:#f1f5f9;font-family:'Inter',system-ui,sans-serif}
        .patient-wrapper{display:flex;min-height:100vh}
        .patient-sidebar{width:260px;background:#0f172a;color:#fff;position:fixed;height:100vh;overflow-y:auto;z-index:1000;padding:1rem 0}
        .patient-sidebar a.nav-link{color:rgba(255,255,255,.7);border-radius:10px;margin:.15rem .6rem;padding:.65rem 1rem;font-size:.9rem}
        .patient-sidebar a.nav-link:hover,.patient-sidebar a.nav-link.active{color:#fff;background:rgba(255,255,255,.1)}
        .patient-content{flex:1;margin-left:260px;min-height:100vh}
        @media(max-width:768px){.patient-sidebar{transform:translateX(-100%)}.patient-sidebar.show{transform:none}.patient-content{margin-left:0}}
    </style>
</head>
<body>
@include('layouts.navigation')
<div class="patient-wrapper">
    <aside class="patient-sidebar">
        @include('layouts.sidebar')
    </aside>
    <main class="patient-content">
        <div class="container-fluid py-3">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
@stack('scripts')
</body>
</html>
