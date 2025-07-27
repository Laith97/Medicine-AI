<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}">
                        <img style="width: 140px" class="logo-default" srcset="{{ asset('demos/medical/images/logo-medical.jpeg') }}, {{ asset('demos/medical/images/logo-medical.jpeg') }} 2x" src="{{ asset('demos/medical/images/logo-medical.jpeg') }}" alt="Canvas Logo">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        @if(auth()->user()->role === 'doctor')
                            <!-- Doctor Navigation -->
                            <x-nav-link :href="route('doctor.dashboard')" :active="request()->routeIs('doctor.dashboard')">
                                <i class="fas fa-tachometer-alt mr-2"></i>{{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('ask-ai')" :active="request()->routeIs('ask-ai')">
                                <i class="fas fa-user-plus mr-2"></i>{{ __('Add-patients') }}
                            </x-nav-link>
                            <x-nav-link :href="route('cases')" :active="request()->routeIs('cases')">
                                <i class="fas fa-folder-open mr-2"></i>{{ __('Cases') }}
                            </x-nav-link>
                            <x-nav-link :href="route('doctor.appointments.index')" :active="request()->routeIs('doctor.appointments.*')">
                                <i class="fas fa-calendar mr-2"></i>{{ __('Appointments') }}
                            </x-nav-link>
                            <x-nav-link :href="route('doctor.availability.index')" :active="request()->routeIs('doctor.availability.*')">
                                <i class="fas fa-clock mr-2"></i>{{ __('Availability') }}
                            </x-nav-link>
                            <x-nav-link :href="route('doctor.reviews.index')" :active="request()->routeIs('doctor.reviews.*')">
                                <i class="fas fa-star mr-2"></i>{{ __('Reviews') }}
                            </x-nav-link>
                            <x-nav-link :href="route('doctor.notes.index')" :active="request()->routeIs('doctor.notes.*')">
                                <i class="fas fa-sticky-note mr-2"></i>{{ __('Notes') }}
                            </x-nav-link>
                        @elseif(auth()->user()->role === 'admin')
                            <!-- Admin Navigation -->
                            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                                <i class="fas fa-tachometer-alt mr-2"></i>{{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                                <i class="fas fa-user-md mr-2"></i>{{ __('Doctors') }}
                            </x-nav-link>
                            <x-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                                <i class="fas fa-calendar mr-2"></i>{{ __('All Appointments') }}
                            </x-nav-link>
                        @else
                            <x-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                                <i class="fas fa-search mr-2"></i>{{ __('Find Doctors') }}
                            </x-nav-link>
                            <x-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                                <i class="fas fa-calendar mr-2"></i>{{ __('My Appointments') }}
                            </x-nav-link>
                        @endif
                    @else
                        <x-nav-link :href="url('/')">
                            <i class="fas fa-user-injured mr-2"></i>{{ __('Home') }}
                        </x-nav-link>
                        <!-- Guest Navigation -->
                        <x-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                            <i class="fas fa-user-injured mr-2"></i>{{ __('For Patients') }}
                        </x-nav-link>
                        <x-nav-link :href="route('appointments.guest.lookup')" :active="request()->routeIs('appointments.guest.*')">
                            <i class="fas fa-calendar mr-2"></i>{{ __('My Appointments') }}
                        </x-nav-link>
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if(auth()->user()->role === 'doctor')
                                <x-dropdown-link :href="route('doctor.profile.edit')">
                                    {{ __('Doctor Profile') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Account Settings') }}
                                </x-dropdown-link>
                            @else
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Guest Actions -->
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('patient.register') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                            Create Account
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sign In
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                @if(auth()->user()->role === 'doctor')
                    <!-- Doctor Mobile Navigation -->
                    <x-responsive-nav-link :href="route('doctor.dashboard')" :active="request()->routeIs('doctor.dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i>{{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('ask-ai')" :active="request()->routeIs('ask-ai')">
                        <i class="fas fa-user-plus mr-2"></i>{{ __('Add-patients') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('cases')" :active="request()->routeIs('cases')">
                        <i class="fas fa-folder-open mr-2"></i>{{ __('Cases') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('doctor.appointments.index')" :active="request()->routeIs('doctor.appointments.*')">
                        <i class="fas fa-calendar mr-2"></i>{{ __('Appointments') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('doctor.availability.index')" :active="request()->routeIs('doctor.availability.*')">
                        <i class="fas fa-clock mr-2"></i>{{ __('Availability') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('doctor.reviews.index')" :active="request()->routeIs('doctor.reviews.*')">
                        <i class="fas fa-star mr-2"></i>{{ __('Reviews') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('doctor.notes.index')" :active="request()->routeIs('doctor.notes.*')">
                        <i class="fas fa-sticky-note mr-2"></i>{{ __('Notes') }}
                    </x-responsive-nav-link>
                @elseif(auth()->user()->role === 'admin')
                    <!-- Admin Mobile Navigation -->
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i>{{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                        <i class="fas fa-user-md mr-2"></i>{{ __('Doctors') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                        <i class="fas fa-calendar mr-2"></i>{{ __('All Appointments') }}
                    </x-responsive-nav-link>
                @else
                    <!-- Patient Mobile Navigation -->
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i>{{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                        <i class="fas fa-search mr-2"></i>{{ __('Find Doctors') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                        <i class="fas fa-calendar mr-2"></i>{{ __('My Appointments') }}
                    </x-responsive-nav-link>
                @endif
            @else
                <!-- Guest Mobile Navigation -->
                <x-responsive-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                    <i class="fas fa-user-injured mr-2"></i>{{ __('For Patients') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('appointments.guest.lookup')" :active="request()->routeIs('appointments.guest.*')">
                    <i class="fas fa-calendar mr-2"></i>{{ __('My Appointments') }}
                </x-responsive-nav-link>
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    @if(auth()->user()->role === 'doctor')
                        <x-responsive-nav-link :href="route('doctor.profile.edit')">
                            {{ __('Doctor Profile') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('profile.edit')">
                            {{ __('Account Settings') }}
                        </x-responsive-nav-link>
                    @else
                        <x-responsive-nav-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>
                    @endif

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <!-- Guest Options in Mobile -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('patient.register')">
                        <i class="fas fa-user-plus mr-2"></i>{{ __('Create Account') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('login')">
                        <i class="fas fa-sign-in-alt mr-2"></i>{{ __('Sign In') }}
                    </x-responsive-nav-link>
                </div>
            </div>
        @endauth
    </div>
</nav>
