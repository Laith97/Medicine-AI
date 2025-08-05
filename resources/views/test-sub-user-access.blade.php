@extends('master')

@section('title', 'Sub-User Access Test')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Sub-User Access Test</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Information -->
                        <div class="col-md-6 mb-4">
                            <h5>Current User Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ auth()->user()->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ auth()->user()->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td>{{ auth()->user()->role }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Is Sub-User:</strong></td>
                                    <td>
                                        @if(auth()->user()->isSubUser())
                                            <span class="badge bg-info">Yes</span>
                                        @else
                                            <span class="badge bg-primary">No (Main User)</span>
                                        @endif
                                    </td>
                                </tr>
                                @if(auth()->user()->isSubUser())
                                    <tr>
                                        <td><strong>Sub-User Role:</strong></td>
                                        <td>{{ ucfirst(auth()->user()->sub_user_role) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Parent User:</strong></td>
                                        <td>{{ auth()->user()->parentUser->name ?? 'N/A' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Has Doctor Profile:</strong></td>
                                    <td>
                                        @if(auth()->user()->hasActiveDoctorProfile())
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-warning">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Permissions -->
                        <div class="col-md-6 mb-4">
                            <h5>User Permissions</h5>
                            @if(auth()->user()->isSubUser())
                                @if(auth()->user()->permissions->count() > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach(auth()->user()->permissions as $permission)
                                            <span class="badge bg-light text-dark">{{ $permission->display_name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">No permissions assigned</p>
                                @endif
                            @else
                                <p class="text-info">Main users have all permissions based on their role</p>
                            @endif
                        </div>
                    </div>

                    <!-- Route Access Test -->
                    <div class="mb-4">
                        <h5>Route Access Test</h5>
                        <div class="row">
                            @php
                                $testRoutes = [
                                    'dashboard' => 'Dashboard',
                                    'ask-ai' => 'AI Assistant',
                                    'voice-assistant.index' => 'Voice Assistant',
                                    'diagnosis.index' => 'Diagnoses',
                                    'cases' => 'Patient Cases',
                                    'sub-users.index' => 'Sub-Users Management',
                                    'settings' => 'Settings',
                                    'doctor.appointments.index' => 'Appointments',
                                    'doctor.reviews.index' => 'Reviews',
                                ];
                            @endphp

                            @foreach($testRoutes as $route => $name)
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ $name }}:</span>
                                        @if(auth()->user()->canAccessRoute($route))
                                            <span class="badge bg-success">✓ Allowed</span>
                                        @else
                                            <span class="badge bg-danger">✗ Denied</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Menu Items -->
                    <div class="mb-4">
                        <h5>Available Menu Items</h5>
                        @php
                            $menuItems = \App\Helpers\MenuHelper::getMenuItems(auth()->user());
                        @endphp
                        
                        <div class="row">
                            @foreach($menuItems as $item)
                                <div class="col-md-6 mb-2">
                                    <div class="card card-body py-2">
                                        <div class="d-flex align-items-center">
                                            @if(isset($item['icon']))
                                                <i class="{{ $item['icon'] }} me-2"></i>
                                            @endif
                                            <strong>{{ $item['name'] }}</strong>
                                        </div>
                                        
                                        @if(isset($item['dropdown']) && $item['dropdown'] && isset($item['items']))
                                            <div class="mt-2">
                                                <small class="text-muted">Dropdown items:</small>
                                                <div class="ms-3">
                                                    @foreach($item['items'] as $subItem)
                                                        <div class="small">
                                                            @if(isset($subItem['icon']))
                                                                <i class="{{ $subItem['icon'] }} me-1"></i>
                                                            @endif
                                                            {{ $subItem['name'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Test Links -->
                    <div class="mb-4">
                        <h5>Test Navigation Links</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">Dashboard</a>
                            <a href="{{ route('settings') }}" class="btn btn-sm btn-outline-secondary">Settings</a>
                            <a href="{{ route('cases') }}" class="btn btn-sm btn-outline-info">Cases</a>
                            @if(auth()->user()->canAccessRoute('doctor.appointments.index'))
                                <a href="{{ route('doctor.appointments.index') }}" class="btn btn-sm btn-outline-success">Appointments</a>
                            @endif
                            @if(auth()->user()->canAccessRoute('sub-users.index'))
                                <a href="{{ route('sub-users.index') }}" class="btn btn-sm btn-outline-warning">Sub-Users</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection