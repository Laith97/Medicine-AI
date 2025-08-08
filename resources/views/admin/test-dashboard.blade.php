<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Test Admin Dashboard</h3>
                    </div>
                    <div class="card-body">
                        <h4>Admin Authentication Test</h4>
                        <p><strong>Admin logged in:</strong> {{ Auth::guard('admin')->check() ? 'Yes' : 'No' }}</p>
                        @if(Auth::guard('admin')->check())
                            <p><strong>Admin name:</strong> {{ Auth::guard('admin')->user()->name }}</p>
                            <p><strong>Admin email:</strong> {{ Auth::guard('admin')->user()->email }}</p>
                        @endif

                        <hr>

                        <h4>Navigation Links</h4>
                        <ul>
                            <li><a href="{{ route('admin.dashboard') }}">Back to Real Dashboard</a></li>
                            <li><a href="{{ route('admin.users.index') }}">Manage Users</a></li>
                            <li><a href="{{ route('admin.test') }}">Test Route</a></li>
                        </ul>

                        <hr>

                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
