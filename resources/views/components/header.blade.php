<div class="sectionprofil">
    <button class="customer-nav-toggle" type="button" data-customer-nav-toggle aria-controls="customerSidebar" aria-expanded="false" aria-label="Buka menu navigasi">
        <span></span><span></span><span></span>
    </button>
    @php
        $headerUser = auth()->user();
        $headerName = $headerUser?->name ?: 'Customer';
        $headerEmail = $headerUser?->email ?: 'loginemailcustomer@gmail.com';
        $headerFirstName = explode(' ', trim($headerName))[0] ?: $headerName;
        $headerAvatarUrl = $headerUser?->profile_photo_url
            ?: \App\Models\User::defaultAvatarUrlFor($headerName, $headerEmail);
        $headerNotifications = collect($headerNotifications ?? []);
        $headerNotificationCount = $headerNotificationCount ?? $headerNotifications->count();
        $headerNotificationBadge = $headerNotificationCount > 9 ? '9+' : $headerNotificationCount;
    @endphp

    @switch(Route::currentRouteName())
        @case('dashboard')
            <header class="page-header">
                <div class="page-header__text">
                    <h1 class="page-header__title">Welcome back, {{ $headerFirstName }}!</h1>
                    <p class="page-header__subtitle">Here's what's happening with your music production today.</p>
                </div>
            </header>
            @break

        @case('demo')
            <header class="page-header">
                <div class="page-header__text">
                    <h1 class="page-header__title">Demo Section</h1>
                    <p class="page-header__subtitle">Preview style sampling. Play demo before subscribing.</p>
                </div>
            </header>
            @break

        @case('stylesampling')
            <header class="page-header">
                <div class="page-header__text">
                    <h1 class="page-header__title">Style Sampling</h1>
                    <p class="page-header__subtitle">Browse our complete collection of music styles.</p>
                </div>
            </header>
            @break

        @case('subcription')
            <header class="page-header">
                <div class="page-header__text">
                    <h1 class="page-header__title">Subscription</h1>
                    <p class="page-header__subtitle">Register, subscribe, and complete sandbox payments through Midtrans.</p>
                </div>
            </header>
            @break

        @case('gatebook')
            <header class="page-header">
                <div class="page-header__text">
                    <h1 class="page-header__title">Gatebook</h1>
                    <p class="page-header__subtitle">Panduan lengkap menggunakan layanan HM Music dari pendaftaran hingga akses penuh.</p>
                </div>
            </header>
            @break

        @default
            <header class="page-header">
                <div class="page-header__text">
                    <h1 class="page-header__title">Dashboard</h1>
                    <p class="page-header__subtitle">Manage your HM Music Production workspace.</p>
                </div>
            </header>
    @endswitch

    <div class="profil">
        <div class="profilview">
            <img src="{{ $headerAvatarUrl }}" alt="{{ $headerName }} avatar">
        </div>

        <div class="profilname">
            <h1>{{ $headerName }}</h1>
            <p>{{ $headerEmail }}</p>
        </div>

        <div class="profilsvg">
            <a href="javascript:void(0);"
                id="openNotification"
                data-notification-toggle
                data-notification-count="{{ $headerNotificationCount }}"
                aria-controls="notificationPanel"
                aria-expanded="false"
                aria-label="Open notifications">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M19.3399 14.49L18.3399 12.83C18.1299 12.46 17.9399 11.76 17.9399 11.35V8.82C17.9399 6.47 16.5599 4.44 14.5699 3.49C14.0499 2.57 13.0899 2 11.9899 2C10.8999 2 9.91994 2.59 9.39994 3.52C7.44994 4.49 6.09994 6.5 6.09994 8.82V11.35C6.09994 11.76 5.90994 12.46 5.69994 12.82L4.68994 14.49C4.28994 15.16 4.19994 15.9 4.44994 16.58C4.68994 17.25 5.25994 17.77 5.99994 18.02C7.93994 18.68 9.97994 19 12.0199 19C14.0599 19 16.0999 18.68 18.0399 18.03C18.7399 17.8 19.2799 17.27 19.5399 16.58C19.7999 15.89 19.7299 15.13 19.3399 14.49Z" fill="#292D32" />
                    <path d="M14.8299 20.01C14.4099 21.17 13.2999 22 11.9999 22C11.2099 22 10.4299 21.68 9.87993 21.11C9.55993 20.81 9.31993 20.41 9.17993 20C9.30993 20.02 9.43993 20.03 9.57993 20.05C9.80993 20.08 10.0499 20.11 10.2899 20.13C10.8599 20.18 11.4399 20.21 12.0199 20.21C12.5899 20.21 13.1599 20.18 13.7199 20.13C13.9299 20.11 14.1399 20.1 14.3399 20.07C14.4999 20.05 14.6599 20.03 14.8299 20.01Z" fill="#292D32" />
                </svg>

                @if($headerNotificationCount > 0)
                    <p data-notification-badge data-count="{{ $headerNotificationCount }}">{{ $headerNotificationBadge }}</p>
                @endif
            </a>
        </div>

        @include('components.notifications-panel', [
            'notifications' => $headerNotifications,
            'emptyTitle' => 'No customer notifications',
            'emptyMessage' => 'Latest style updates, subscription reminders, and N27 request progress will appear here.',
        ])
    </div>
</div>
