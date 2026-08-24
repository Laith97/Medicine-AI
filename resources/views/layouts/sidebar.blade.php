<aside id="appSidebar" class="sidebar">
  <!-- Sidebar Brand - Logo 100x75 exact, moved from top-bar to keep slim bar -->
  <div class="sidebar-brand" style="padding:0.5rem 1rem;border-bottom:1px solid #f1f5f9;text-align:center;background:#ffffff;justify-content:center;">
    <a href="{{ url('/') }}" class="d-flex align-items-center justify-content-center text-decoration-none">
      <img style="width:100px;height:75px;" src="{{ asset('demos/medical/images/logo-medical.png') }}?v={{ time() }}&cache={{ rand(1000,9999) }}" alt="Medcura Logo">
    </a>
  </div>
  <nav class="sidebar-nav">
    <ul class="nav flex-column">
      @auth
        @php($menuItems = \App\Helpers\MenuHelper::getMenuItems(auth()->user()))
        @foreach ($menuItems as $item)
          @if (!empty($item['dropdown']) && !empty($item['items']))
            <li class="nav-item">
              @if(isset($item['dropdown']) && isset($item['header_class']))
                <div class="{{ $item['header_class'] }}" @if(isset($item['header_style'])) style="{{ $item['header_style'] }}" @endif>
                  @if(isset($item['icon']))<i class="{{ $item['icon'] }}"></i>@endif
                  <span>{{ $item['name'] }}</span>
                </div>
              @elseif(isset($item['href']))
                <a href="{{ route($item['href']) }}" class="nav-link {{ request()->routeIs($item['href']) ? 'active' : '' }}"
                   data-route="{{ $item['href'] }}">
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
                   class="nav-link {{ request()->routeIs($subItem['route'] ?? '') ? 'active' : '' }}"
                   style="font-weight: 400; color: #64748b; padding-left: 36px; font-size:0.84rem;"
                   @if(isset($subItem['route'])) data-route="{{ $subItem['route'] }}" @endif>
                  @if(isset($subItem['icon']))<i class="{{ $subItem['icon'] }}"></i>@else<i class="fa-solid fa-angle-right"></i>@endif
                  <span>{{ $subItem['name'] }}</span>
                </a>
              @endforeach
            </li>
          @else
            <li class="nav-item">
              <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                 class="nav-link {{ request()->routeIs($item['route'] ?? '') ? 'active' : '' }}"
                 @if(isset($item['route'])) data-route="{{ $item['route'] }}" @endif>
                @if(isset($item['icon']))<i class="{{ $item['icon'] }}"></i>@else<i class="fa-solid fa-circle"></i>@endif
                <span>{{ $item['name'] }}</span>
              </a>
            </li>
          @endif
        @endforeach
        <li class="nav-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
          <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') || request()->routeIs('profile.update') ? 'active' : '' }}" title="{{ auth()->user()->isDoctor() ? 'Email, password & account security' : 'Profile' }}" data-route="profile.edit">
            <i class="fa-solid {{ auth()->user()->isDoctor() ? 'fa-lock' : 'fa-user' }}"></i><span>{{ auth()->user()->isDoctor() ? 'Account Settings' : 'Profile' }}</span>
          </a>
        </li>
        <li class="nav-item">
          <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="nav-link" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer;">
              <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span>
            </button>
          </form>
        </li>
      @else
        <li class="nav-item">
          <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
            <i class="fa-solid fa-right-to-bracket"></i><span>Login</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('register') }}" class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">
            <i class="fa-solid fa-user-plus"></i><span>Register</span>
          </a>
        </li>
      @endauth
    </ul>
  </nav>
</aside>
