@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')
        <div class="hall-second">
            @include('components.admin-header')
            @php
                $stats = $stats ?? [
                    [
                        'label' => 'Total Users',
                        'value' => '1,240',
                        'trend' => '+12.4%',
                        'note' => 'vs last month',
                        'theme' => 'violet',
                        'icon' => 'users',
                    ],
                    [
                        'label' => 'Active Subscriptions',
                        'value' => '842',
                        'trend' => '+8.1%',
                        'note' => 'renewal active',
                        'theme' => 'blue',
                        'icon' => 'card',
                    ],
                    [
                        'label' => 'Total Downloads',
                        'value' => '14,521',
                        'trend' => '+18.6%',
                        'note' => 'all files',
                        'theme' => 'green',
                        'icon' => 'download',
                    ],
                    [
                        'label' => 'Total Demos Played',
                        'value' => '8,932',
                        'trend' => '+9.7%',
                        'note' => 'video preview events',
                        'theme' => 'orange',
                        'icon' => 'play',
                    ],
                    [
                        'label' => 'Monthly Revenue',
                        'value' => 'Rp 37.8M',
                        'trend' => '+15.2%',
                        'note' => 'May revenue',
                        'theme' => 'red',
                        'icon' => 'revenue',
                    ],
                    [
                        'label' => "This Week's Trending Song",
                        'value' => $topTrendingSong['title'] ?? 'No trending demo',
                        'trend' => ($topTrendingSong['plays'] ?? '0').' plays',
                        'note' => 'top performer',
                        'theme' => 'dark',
                        'icon' => 'trend',
                    ],
                ];

                $subscriptionMonths = $subscriptionMonths ?? [
                    ['label' => 'Jan', 'value' => 540, 'height' => 52],
                    ['label' => 'Feb', 'value' => 620, 'height' => 60],
                    ['label' => 'Mar', 'value' => 710, 'height' => 68],
                    ['label' => 'Apr', 'value' => 785, 'height' => 76],
                    ['label' => 'May', 'value' => 842, 'height' => 84],
                    ['label' => 'Jun', 'value' => 910, 'height' => 90],
                ];

                $downloadData = $downloadData ?? [
                    ['label' => 'Mon', 'value' => '1.1K', 'height' => 46],
                    ['label' => 'Tue', 'value' => '1.4K', 'height' => 58],
                    ['label' => 'Wed', 'value' => '1.2K', 'height' => 50],
                    ['label' => 'Thu', 'value' => '1.8K', 'height' => 72],
                    ['label' => 'Fri', 'value' => '2.3K', 'height' => 88],
                    ['label' => 'Sat', 'value' => '1.9K', 'height' => 76],
                    ['label' => 'Sun', 'value' => '1.6K', 'height' => 64],
                ];

                $demoPlays = $demoPlays ?? [
                    ['title' => 'Dangdut Raya', 'genre' => 'Dangdut', 'plays' => '3.2K', 'percent' => 92],
                    ['title' => 'Campursari Senja', 'genre' => 'Campursari', 'plays' => '2.8K', 'percent' => 80],
                    ['title' => 'Gamelan Modern', 'genre' => 'Gamelan', 'plays' => '2.1K', 'percent' => 68],
                ];

                $trendingSongs = $trendingSongs ?? collect([
                    ['rank' => 1, 'title' => 'Dangdut Raya', 'artist' => 'HM Studio', 'plays' => '3,240', 'revenue' => 'Rp 7.4M'],
                    ['rank' => 2, 'title' => 'Campursari Senja', 'artist' => 'HM Studio', 'plays' => '2,810', 'revenue' => 'Rp 5.9M'],
                    ['rank' => 3, 'title' => 'Gamelan Modern', 'artist' => 'HM Studio', 'plays' => '2,126', 'revenue' => 'Rp 4.2M'],
                ]);
                $subscriptionGrowthTotal = $subscriptionGrowthTotal ?? '+370 users';
                $weeklyDownloadTotal = $weeklyDownloadTotal ?? '11.3K';
                $demoPlayTotal = $demoPlayTotal ?? '11K plays';
            @endphp

            <section class="admin-dashboard">
                <div class="admin-stats">
                    @foreach($stats as $stat)
                        <article class="admin-stat admin-stat--{{ $stat['theme'] }}">
                            <div class="admin-stat__icon">
                                @if($stat['icon'] === 'users')
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                @elseif($stat['icon'] === 'card')
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>
                                @elseif($stat['icon'] === 'download')
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                @elseif($stat['icon'] === 'play')
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3l14 9-14 9V3z"/></svg>
                                @elseif($stat['icon'] === 'revenue')
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
                                @endif
                            </div>

                            <div class="admin-stat__content">
                                <span>{{ $stat['label'] }}</span>
                                <strong>{{ $stat['value'] }}</strong>
                                <div>
                                    <b>{{ $stat['trend'] }}</b>
                                    <small>{{ $stat['note'] }}</small>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="admin-dashboard__grid">
                    <article class="dashboard-panel dashboard-panel--wide">
                        <div class="dashboard-panel__header">
                            <div>
                                <span>Monthly subscription graph</span>
                                <h2>Subscription Growth</h2>
                            </div>
                            <strong>{{ $subscriptionGrowthTotal }}</strong>
                        </div>

                        <div class="subscription-chart">
                            @foreach($subscriptionMonths as $month)
                                <div class="subscription-chart__item">
                                    <div class="subscription-chart__bar-wrap">
                                        <span style="height: {{ $month['height'] }}%"></span>
                                    </div>
                                    <strong>{{ $month['value'] }}</strong>
                                    <small>{{ $month['label'] }}</small>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="dashboard-panel dashboard-panel--trend">
                        <div class="dashboard-panel__header">
                            <div>
                                <span>This week's trending songs chart</span>
                                <h2>Top Songs</h2>
                            </div>
                        </div>

                        <div class="trending-list">
                            @foreach($trendingSongs as $song)
                                <div class="trending-list__item">
                                    <div class="trending-list__rank">{{ $song['rank'] }}</div>
                                    <div class="trending-list__song">
                                        <strong>{{ $song['title'] }}</strong>
                                        <span>{{ $song['artist'] }}</span>
                                    </div>
                                    <div class="trending-list__meta">
                                        <strong>{{ $song['plays'] }}</strong>
                                        <span>{{ $song['revenue'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <div class="dashboard-panel__header">
                            <div>
                                <span>Download graph</span>
                                <h2>Weekly Downloads</h2>
                            </div>
                            <strong>{{ $weeklyDownloadTotal }}</strong>
                        </div>

                        <div class="download-chart">
                            @foreach($downloadData as $day)
                                <div class="download-chart__item">
                                    <span class="download-chart__value">{{ $day['value'] }}</span>
                                    <div class="download-chart__bar" style="height: {{ $day['height'] }}%"></div>
                                    <small>{{ $day['label'] }}</small>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <div class="dashboard-panel__header">
                            <div>
                                <span>Most played demo graph</span>
                                <h2>Demo Performance</h2>
                            </div>
                            <strong>{{ $demoPlayTotal }}</strong>
                        </div>

                        <div class="demo-performance">
                            @foreach($demoPlays as $demo)
                                <div class="demo-performance__item">
                                    <div class="demo-performance__title">
                                        <strong>{{ $demo['title'] }}</strong>
                                        <span>{{ $demo['genre'] }} / {{ $demo['plays'] }} plays</span>
                                    </div>
                                    <div class="demo-performance__track">
                                        <span style="width: {{ $demo['percent'] }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
