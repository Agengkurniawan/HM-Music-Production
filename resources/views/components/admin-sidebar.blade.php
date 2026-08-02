<section class="admin-sidebar">
    @php
    $adminMenus = [
    [
    'title' => 'Dashboard Admin',
    'route' => 'admin.dashboard',
    'icon' => 'dashboard',
    ],
    [
    'title' => 'Demo Management',
    'route' => 'admin.demo',
    'icon' => 'music',
    ],
    [
    'title' => 'Style Catalog',
    'route' => 'admin.stylesampling',
    'icon' => 'headphones',
    ],
    [
    'title' => 'N27 Requests',
    'route' => 'admin.sampling-requests',
    'icon' => 'file',
    ],
    [
    'title' => 'User Management',
    'route' => 'admin.usermanagement',
    'icon' => 'users',
    ],
    [
    'title' => 'Subscription',
    'route' => 'admin.subcription',
    'icon' => 'card',
    ],
    [
    'title' => 'Download & Sales',
    'route' => 'admin.downloadsales',
    'icon' => 'download',
    ],
    [
    'title' => 'Settings',
    'route' => 'admin.setting',
    'icon' => 'settings',
    ],
    ];
    @endphp

    <div class="hall-first">
        <div class="sidebar-header">
            <div class="logo">
                <img src="{{ asset('img/logo-hm-transparent.png') }}" alt="HM Music">
            </div>

            <div>
                <h2>HM Music</h2>
                <p>Admin Backoffice</p>
            </div>
        </div>

        <div class="atasbawah">
            <div class="sidebar-menu">
                @foreach($adminMenus as $menu)
                <a href="{{ $menu['route'] ? route($menu['route']) : '#' }}"
                    class="menu-item {{ $menu['route'] && request()->routeIs($menu['route']) ? 'active' : '' }} {{ $menu['route'] ? '' : 'disabled' }}"
                    @if(!$menu['route']) aria-disabled="true" @endif>
                    <span class="icon">
                        @switch($menu['icon'])
                        @case('dashboard')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 13h8V3H3z" />
                            <path d="M13 21h8V11h-8z" />
                            <path d="M13 3v6h8V3z" />
                            <path d="M3 21h8v-6H3z" />
                        </svg>
                        @break
                        @case('music')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 18V5l12-2v13" />
                            <circle cx="6" cy="18" r="3" />
                            <circle cx="18" cy="16" r="3" />
                        </svg>
                        @break
                        @case('headphones')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 14v-2a9 9 0 0 1 18 0v2" />
                            <path d="M5 14h4v7H5z" />
                            <path d="M15 14h4v7h-4z" />
                        </svg>
                        @break
                        @case('file')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6" />
                            <path d="M8 13h8" />
                            <path d="M8 17h6" />
                        </svg>
                        @break
                        @case('card')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M3 10h18" />
                            <path d="M7 15h4" />
                        </svg>
                        @break
                        @case('download')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3v12" />
                            <path d="M7 10l5 5 5-5" />
                            <path d="M5 21h14" />
                        </svg>
                        @break
                        @case('users')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        @break
                        @case('settings')
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 0 1-4 0v-.1A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 0 1 0-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 0 1 4 0v.1A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.1a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.7.6z" />
                        </svg>
                        @break
                        @endswitch
                    </span>

                    <span>{{ $menu['title'] }}</span>
                </a>
                @endforeach
            </div>

            <div class="sidebar-user">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="logout-btn" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </div>
</section>
