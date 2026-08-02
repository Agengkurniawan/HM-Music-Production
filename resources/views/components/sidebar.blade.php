<section class="sidebar">
    @php
        $isStyleActive = request()->routeIs('stylesampling');
        $activeStyleType = request('type', 'style');
        $activeStyleCategory = $activeStyleType === 'style' ? request('category') : null;
        $activeStylePack = $activeStyleType === 'style' ? request('pack') : null;
        $activeSamplingPack = $activeStyleType === 'sampling' ? request('pack') : null;

        $customerMenus = [
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'dashboard',
            ],
            [
                'title' => 'Demo',
                'route' => 'demo',
                'icon' => 'music',
            ],
        ];

        $styleMenus = [
            'styles' => collect(\App\Models\StyleSampling::CUSTOMER_STYLE_CATEGORIES),
            'sampling' => collect(\App\Models\StyleSampling::samplingRequestOptions()),
        ];
    @endphp

    <div class="hall-first">
        <div class="sidebar-header">
            <div class="logo">
                <img src="{{ asset('img/logo-hm-transparent.png') }}" alt="HM Music">
            </div>

            <div>
                <h2>HM Music</h2>
                <p>Production Studio</p>
            </div>
        </div>

        <div class="atasbawah">
            <div class="sidebar-menu">
                @foreach($customerMenus as $menu)
                    <a href="{{ route($menu['route']) }}"
                        class="menu-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
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
                            @endswitch
                        </span>
                        <span>{{ $menu['title'] }}</span>
                    </a>
                @endforeach

                <div class="dropdown-wrapper">
                    <button class="dropdown-btn {{ $isStyleActive ? 'active' : '' }}"
                        type="button"
                        onclick="toggleDropdown()">
                        <div class="left">
                            <span class="icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 14v-2a9 9 0 0 1 18 0v2" />
                                    <path d="M5 14h4v7H5z" />
                                    <path d="M15 14h4v7h-4z" />
                                </svg>
                            </span>
                            <span>Style Sampling</span>
                        </div>

                        <span id="arrow" class="{{ $isStyleActive ? 'rotate' : '' }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </span>
                    </button>

                    <div class="dropdown-content {{ $isStyleActive ? 'show' : '' }}"
                        id="dropdownContent">
                        <div class="dropdown-section">
                            <p class="dropdown-title">Style</p>

                            <a href="{{ route('stylesampling', ['type' => 'style']) }}" class="submenu-item {{ $activeStyleType === 'style' && !$activeStyleCategory && !$activeStylePack && $isStyleActive ? 'active' : '' }}">
                                All Styles
                            </a>

                            @foreach($styleMenus['styles'] as $style)
                                <a href="{{ route('stylesampling', ['type' => 'style', 'category' => $style]) }}"
                                    class="submenu-item {{ $activeStyleType === 'style' && $activeStyleCategory === $style ? 'active' : '' }}">
                                    {{ $style }}
                                </a>
                            @endforeach
                        </div>

                        <div class="dropdown-section">
                            <p class="dropdown-title">Sampling</p>

                            <a href="{{ route('stylesampling', ['type' => 'sampling']) }}" class="submenu-item {{ $activeStyleType === 'sampling' && !$activeSamplingPack && $isStyleActive ? 'active' : '' }}">
                                All Sampling Packs
                            </a>

                            @foreach($styleMenus['sampling'] as $samplingName => $sampling)
                                <a href="{{ route('stylesampling', ['type' => 'sampling', 'pack' => $samplingName]) }}"
                                    class="submenu-item {{ $activeStyleType === 'sampling' && $activeSamplingPack === $samplingName ? 'active' : '' }}">
                                    {{ $sampling['short_label'] ?? $samplingName }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <a href="{{ route('subcription') }}"
                    class="menu-item {{ request()->routeIs('subcription') ? 'active' : '' }}">
                    <span class="icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M3 10h18" />
                            <path d="M7 15h4" />
                        </svg>
                    </span>
                    <span>Subscription</span>
                </a>
            </div>

            @unless($hideLogout ?? false)
                <div class="sidebar-user">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="logout-btn" type="submit">Logout</button>
                    </form>
                </div>
            @endunless
        </div>

    </div>
</section>

@include('components.modal', [
    'genres' => $genres ?? [],
    'categories' => $categories ?? [],
    'styleProducts' => $styleProducts ?? [],
])

<script>
    document.addEventListener("DOMContentLoaded", function() {
        window.toggleDropdown = function() {
            const dropdown = document.getElementById('dropdownContent');
            const arrow = document.getElementById('arrow');

            dropdown.classList.toggle('show');
            arrow.classList.toggle('rotate');
        }
    });
</script>
