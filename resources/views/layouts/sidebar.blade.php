<aside id="appSidebar" class="sidebar">
  <div class="sidebar-header">
    <a href="@auth{{ route('dashboard') }}@else{{ url('/') }}@endauth" class="sidebar-brand">
      <i class="fa-solid fa-hospital"></i>
      <span class="brand-text">MedAssist</span>
    </a>
    <button id="sidebarCollapse" class="btn btn-sm btn-outline-secondary d-lg-none" aria-label="Toggle sidebar" aria-expanded="false">
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <ul class="nav flex-column">
      @auth
        @php($menuItems = \App\Helpers\MenuHelper::getMenuItems(auth()->user()))
        @foreach ($menuItems as $item)
          @if (!empty($item['dropdown']) && !empty($item['items']))
            <li class="nav-item">
              @if(isset($item['href']))
                <a href="{{ route($item['href']) }}" class="nav-link {{ request()->routeIs($item['href']) ? 'active' : '' }}">
                  @if(isset($item['icon']))<i class="{{ $item['icon'] }}"></i>@endif
                  <span>{{ $item['name'] }}</span>
                </a>
              @else
                <div class="nav-link disabled" tabindex="-1">
                  @if(isset($item['icon']))<i class="{{ $item['icon'] }}"></i>@endif
                  <span>{{ $item['name'] }}</span>
                </div>
              @endif
              @foreach ($item['items'] as $subItem)
                <a href="{{ isset($subItem['route']) ? route($subItem['route']) : '#' }}"
                   class="nav-link {{ request()->routeIs($subItem['route'] ?? '') ? 'active' : '' }}">
                  @if(isset($subItem['icon']))<i class="{{ $subItem['icon'] }}"></i>@else<i class="fa-solid fa-angle-right"></i>@endif
                  <span>{{ $subItem['name'] }}</span>
                </a>
              @endforeach
            </li>
          @else
            <li class="nav-item">
              <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                 class="nav-link {{ request()->routeIs($item['route'] ?? '') ? 'active' : '' }}">
                @if(isset($item['icon']))<i class="{{ $item['icon'] }}"></i>@else<i class="fa-solid fa-circle"></i>@endif
                <span>{{ $item['name'] }}</span>
              </a>
            </li>
          @endif
        @endforeach
      @endauth

      @guest
        <li class="nav-item">
          <a href="{{ url('/') }}" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i><span>Home</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('about') }}" class="nav-link {{ request()->is('about') ? 'active' : '' }}">
            <i class="fa-solid fa-circle-info"></i><span>About Us</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('contact') }}" class="nav-link {{ request()->is('contact') ? 'active' : '' }}">
            <i class="fa-solid fa-envelope"></i><span>Contact</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('doctors.index') }}" class="nav-link {{ request()->is('doctors') ? 'active' : '' }}">
            <i class="fa-solid fa-user-doctor"></i><span>For Patients</span>
          </a>
        </li>
      @endguest
    </ul>
  </nav>

  <div class="sidebar-footer">
    @auth
      <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <i class="fa-solid fa-user"></i><span>Profile</span>
      </a>
      <form method="POST" action="{{ route('logout') }}" class="mt-1">
        @csrf
        <button type="submit" class="nav-link btn btn-link text-start p-0">
          <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span>
        </button>
      </form>
    @else
      <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
        <i class="fa-solid fa-right-to-bracket"></i><span>Login</span>
      </a>
      <a href="{{ route('register') }}" class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">
        <i class="fa-solid fa-user-plus"></i><span>Register</span>
      </a>
    @endauth
  </div>
</aside>

<button id="sidebarPin" class="sidebar-pin btn btn-light d-none d-lg-inline-flex" aria-label="Collapse sidebar" title="Collapse sidebar">
  <i class="fa-solid fa-angles-left"></i>
</button>