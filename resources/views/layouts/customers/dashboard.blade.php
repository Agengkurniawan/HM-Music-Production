@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.sidebar')

        <div class="hall-second">
            @include('components.header')

            @php
                $stats = $stats ?? [
                    'total_styles' => 0,
                    'demo_played' => 0,
                    'downloads' => 0,
                    'premium_access' => false,
                    'demo_progress' => 0,
                ];
                $demos = collect($demos ?? []);
                $styles = collect($styles ?? []);
                $user = $user ?? ['name' => 'Producer', 'plan' => 'Free Plan', 'demo_limit' => 50, 'is_premium' => false];
                $firstName = explode(' ', trim($user['name'] ?? 'Producer'))[0] ?: 'Producer';
            @endphp

            <div class="dashboard-wrapper">
                <main class="customer-dashboard">
                    <section class="dashboard-hero">
                        <div class="dashboard-hero__content">
                            <span class="dashboard-hero__eyebrow">{{ $user['plan'] ?? 'Free Plan' }}</span>
                            <h1>Start your next arrangement, {{ $firstName }}.</h1>
                            <p>Preview polished demos, browse fresh STY styles, and manage N27 sampling voice packs in one place.</p>
                            <div class="dashboard-hero__actions">
                                <a href="{{ route('stylesampling') }}" class="dashboard-hero__button dashboard-hero__button--primary">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 18V5l12-2v13" />
                                        <circle cx="6" cy="18" r="3" />
                                        <circle cx="18" cy="16" r="3" />
                                    </svg>
                                    Browse Styles
                                </a>
                                <a href="{{ route('demo') }}" class="dashboard-hero__button">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <polygon points="5 3 19 12 5 21 5 3" />
                                    </svg>
                                    Play Demos
                                </a>
                            </div>
                        </div>

                        <div class="dashboard-hero__visual" aria-hidden="true">
                            @forelse($styles->take(3) as $style)
                                <figure class="hero-style hero-style--{{ $loop->iteration }}">
                                    <img src="{{ $style->cover_src }}" alt="">
                                    <figcaption>
                                        <strong>{{ $style->name }}</strong>
                                        <span>{{ $style->category }}</span>
                                    </figcaption>
                                </figure>
                            @empty
                                <figure class="hero-style hero-style--1">
                                    <img src="https://hmmusicproduction.com/storage/styles/covers/RhserqWvSwyjvlFNyk6TJIPI95z5YyALFsBBQDsz.jpg" alt="">
                                    <figcaption>
                                        <strong>HM Music</strong>
                                        <span>Style Pack</span>
                                    </figcaption>
                                </figure>
                            @endforelse
                        </div>
                    </section>

                    <section class="stats-grid" aria-label="Dashboard summary">
                        <article class="stat-card stat-card--styles">
                            <div class="stat-card__top">
                                <div class="stat-card__icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 18V5l12-2v13" />
                                        <circle cx="6" cy="18" r="3" />
                                        <circle cx="18" cy="16" r="3" />
                                    </svg>
                                </div>
                                <span>Catalog</span>
                            </div>
                            <strong>{{ number_format($stats['total_styles']) }}</strong>
                            <p>Published styles</p>
                        </article>

                        <article class="stat-card stat-card--demos">
                            <div class="stat-card__top">
                                <div class="stat-card__icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <polygon points="5 3 19 12 5 21 5 3" />
                                    </svg>
                                </div>
                                <span>Demos</span>
                            </div>
                            <strong>{{ number_format($stats['demo_played']) }}</strong>
                            <p>Total plays</p>
                        </article>

                        <article class="stat-card stat-card--downloads">
                            <div class="stat-card__top">
                                <div class="stat-card__icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <path d="m7 10 5 5 5-5" />
                                        <path d="M12 15V3" />
                                    </svg>
                                </div>
                                <span>Library</span>
                            </div>
                            <strong>{{ number_format($stats['downloads']) }}</strong>
                            <p>My downloads</p>
                        </article>

                        <article class="stat-card stat-card--premium">
                            <div class="stat-card__top">
                                <div class="stat-card__icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 2 15 8.3l7 .9-5.1 4.7 1.3 7L12 17.4 5.8 20.9l1.3-7L2 9.2l7-.9L12 2Z" />
                                    </svg>
                                </div>
                                <span>Access</span>
                            </div>
                            <strong>{{ $stats['premium_access'] ? 'Active' : 'Locked' }}</strong>
                            <p>{{ $stats['premium_access'] ? 'Premium plan' : 'Free preview' }}</p>
                        </article>
                    </section>

                    <section class="dashboard-grid">
                        <article class="dashboard-panel dashboard-panel--wide">
                            <div class="dashboard-panel__header">
                                <div>
                                    <span>Featured Demos</span>
                                    <h2>Ready to preview</h2>
                                </div>
                                <a href="{{ route('demo') }}">View All</a>
                            </div>

                            <div class="demo-list">
                                @forelse($demos as $demo)
                                    <div class="demo-item">
                                        <img src="{{ $demo['img'] }}" alt="{{ $demo['title'] }}">
                                        <div class="demo-item__body">
                                            <strong>{{ $demo['title'] }}</strong>
                                            <span>{{ $demo['genre'] }} / {{ $demo['bpm'] }} BPM / {{ $demo['duration'] ?? 'Preview' }}</span>
                                        </div>
                                        <span class="demo-item__badge">{{ $demo['source'] }}</span>
                                        <a href="{{ route('demo') }}" aria-label="Open {{ $demo['title'] }} demo">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <polygon points="8 5 19 12 8 19 8 5" />
                                            </svg>
                                        </a>
                                    </div>
                                @empty
                                    <div class="dashboard-empty">
                                        <strong>No demos published yet</strong>
                                        <span>New previews will appear here after they are published.</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        @php
                            $isPremium = $user['is_premium'] ?? false;
                            $planExpiresAt = $user['expires_at'] ?? null;
                            $planMeta = $isPremium
                                ? ($planExpiresAt ? 'Active until '.$planExpiresAt->format('d M Y') : 'Active access')
                                : 'Free preview access';
                        @endphp

                        <aside class="dashboard-panel plan-panel {{ $isPremium ? 'is-premium' : 'is-free' }}">
                            <div class="plan-panel__status">
                                <div class="plan-panel__status-top">
                                    <span class="plan-panel__badge">{{ $isPremium ? 'Premium Active' : 'Preview Mode' }}</span>
                                </div>
                                <small>Current Plan</small>
                                <strong>{{ $user['plan'] ?? 'Free Plan' }}</strong>
                                <p>
                                    {{ $isPremium ? 'STY downloads are unlocked. Sampling voice packs are bought separately per pack.' : 'Listen first, then unlock STY style downloads when you are ready.' }}
                                </p>
                                <div class="plan-panel__meta">
                                    <span>{{ $planMeta }}</span>
                                </div>
                            </div>

                            <div class="plan-panel__perks" aria-label="Plan access">
                                <div class="plan-panel__perk">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M20 6 9 17l-5-5" />
                                    </svg>
                                    <span>
                                        <strong>YouTube Demos</strong>
                                        <small>Included</small>
                                    </span>
                                </div>
                                <div class="plan-panel__perk {{ $isPremium ? '' : 'is-muted' }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        @if($isPremium)
                                            <path d="M20 6 9 17l-5-5" />
                                        @else
                                            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                                            <rect x="5" y="11" width="14" height="10" rx="2" />
                                        @endif
                                    </svg>
                                    <span>
                                        <strong>STY Files</strong>
                                        <small>{{ $isPremium ? 'Unlocked' : 'Premium only' }}</small>
                                    </span>
                                </div>
                                <div class="plan-panel__perk">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <path d="M14 2v6h6" />
                                    </svg>
                                    <span>
                                        <strong>Sampling Packs</strong>
                                        <small>Separate order</small>
                                    </span>
                                </div>
                            </div>

                            <a href="{{ route('subcription') }}" class="plan-panel__cta">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2 15 8.3l7 .9-5.1 4.7 1.3 7L12 17.4 5.8 20.9l1.3-7L2 9.2l7-.9L12 2Z" />
                                </svg>
                                {{ $isPremium ? 'Manage Plan' : 'Unlock Downloads' }}
                            </a>
                        </aside>
                    </section>

                    <section class="style-showcase">
                        <div class="dashboard-panel__header">
                            <div>
                                <span>Style Sampling</span>
                                <h2>Latest packs</h2>
                            </div>
                            <a href="{{ route('stylesampling') }}">Explore</a>
                        </div>

                        <div class="style-showcase__grid">
                            @forelse($styles as $style)
                                <article class="style-tile">
                                    <img src="{{ $style->cover_src }}" alt="{{ $style->name }} cover">
                                    <div class="style-tile__content">
                                        <span>{{ $style->category }}</span>
                                        <h3>{{ $style->name }}</h3>
                                        <p>{{ $style->pack ?? 'Single Style' }}</p>
                                        <div>
                                            <strong>{{ $style->access }}</strong>
                                            <small>{{ $style->display_style_name }} / needs matching sampling pack</small>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="dashboard-empty">
                                    <strong>No published styles yet</strong>
                                    <span>Published style packs will appear here automatically.</span>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</section>
@endsection
