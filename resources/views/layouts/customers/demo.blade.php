@extends('apps')

@section('content')
<section class="demo">
    <div class="content-hall-first">
        @include('components.sidebar', ['hideLogout' => ! auth()->check()])

        <div class="hall-second">
            @include('components.header')

            <div class="sectionprofil-6">
                @php
                    $categories = $categories ?? ['All', ...\App\Models\MusicDemo::GENRES];
                    $demos = collect($demos ?? []);
                    $hasActiveSubscription = $hasActiveSubscription ?? false;
                @endphp

                <div class="demo-section">
                    <div class="demo-section__header">
                        <div>
                            <span>Customer Demo Library</span>
                            <h1>Play demos published by admin</h1>
                            <p>
                                @if($hasActiveSubscription)
                                    Watch each demo published by admin and compare fresh arrangements with your active STY access.
                                @else
                                    Watch each demo published by admin. Continue to subscription when you are ready to unlock premium STY downloads.
                                @endif
                            </p>
                        </div>

                        <div class="demo-section__actions">
                            @unless($hasActiveSubscription)
                                <a class="demo-section__primary" href="{{ route('subcription') }}">Subscribe</a>
                            @endunless

                            @guest
                                <a class="demo-section__secondary" href="{{ route('login') }}">Login</a>
                            @else
                                <a class="demo-section__secondary" href="{{ route('dashboard') }}">Dashboard</a>
                            @endguest
                        </div>
                    </div>

                    <div class="categories" aria-label="Demo categories">
                        @foreach($categories as $category)
                            <button
                                class="{{ $loop->first ? 'active' : '' }}"
                                type="button"
                                data-demo-filter="{{ $category }}"
                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $category }}</button>
                        @endforeach
                    </div>

                    <div class="grid" data-demo-grid>
                        @foreach ($demos as $demo)
                            <article class="demo-card"
                                data-demo-category="{{ $demo->display_genre }}"
                                data-play-url="{{ route('demo.play', $demo) }}"
                                data-youtube-embed-url="{{ $demo->youtube_embed_url }}"
                                data-mp4-video-url="{{ $demo->installation_video_url }}">
                                <div class="demo-card__image">
                                    @if($demo->youtube_video_id)
                                        <img src="{{ $demo->thumbnail_src }}" alt="{{ $demo->title }} demo cover">
                                    @elseif($demo->installation_video_url)
                                        <video class="demo-card__thumbnail-video" src="{{ $demo->installation_video_url }}#t=0.1" muted playsinline preload="metadata" aria-label="{{ $demo->title }} MP4 preview"></video>
                                    @else
                                        <img src="{{ $demo->thumbnail_src }}" alt="{{ $demo->title }} demo cover">
                                    @endif

                                    <span class="demo-card__source">{{ $demo->youtube_video_id ? 'YouTube' : 'MP4' }}</span>

                                    @if($demo->is_trending)
                                        <span class="demo-card__trend">Trending</span>
                                    @endif

                                    <button class="demo-card__play-icon" type="button" data-video-kind="{{ $demo->youtube_video_id ? 'youtube' : 'mp4' }}" onclick="toggleDemoVideo(this)" aria-label="Play {{ $demo->title }} demo">
                                        <svg class="icon-play" viewBox="0 0 24 24" aria-hidden="true">
                                            <polygon points="8 5 19 12 8 19 8 5" />
                                        </svg>
                                    </button>

                                    <div class="demo-card__player" hidden>
                                        <iframe
                                            title="{{ $demo->title }} YouTube demo"
                                            src=""
                                            hidden
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin"
                                            allowfullscreen></iframe>
                                        <video title="{{ $demo->title }} MP4 video" controls playsinline preload="metadata" hidden></video>
                                    </div>
                                </div>

                                <div class="demo-card__content">
                                    <div>
                                        <h2>{{ $demo->title }}</h2>
                                        <span class="demo-card__category">{{ $demo->display_genre }}</span>
                                    </div>

                                    <div class="demo-card__meta">
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" /></svg>
                                            {{ $demo->display_bpm }}
                                        </span>
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17V7" /><path d="M8 19V5" /><path d="M12 16V8" /><path d="M16 21V3" /><path d="M20 18V6" /></svg>
                                            {{ $demo->key_signature ?? 'Live Key' }}
                                        </span>
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                                            {{ $demo->display_duration }}
                                        </span>
                                    </div>

                                    <div class="demo-card__actions">
                                        @if($demo->youtube_video_id)
                                            <button class="demo-card__play" type="button" data-video-kind="youtube" data-video-label="Play Video" onclick="toggleDemoVideo(this)">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <polygon points="8 5 19 12 8 19 8 5" />
                                                </svg>
                                                <span>Play Video</span>
                                            </button>
                                        @else
                                            <button class="demo-card__play" type="button" data-video-kind="mp4" data-video-label="Play Video" onclick="toggleDemoVideo(this)">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <polygon points="8 5 19 12 8 19 8 5" />
                                                </svg>
                                                <span>Play Video</span>
                                            </button>
                                        @endif
                                        @if($demo->installation_video_url)
                                            @if($demo->youtube_video_id)
                                                <button class="demo-card__install" type="button" data-video-kind="mp4" data-video-label="MP4 Video" onclick="toggleDemoVideo(this)">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <polygon points="8 5 19 12 8 19 8 5" />
                                                    </svg>
                                                    <span>MP4 Video</span>
                                                </button>
                                            @endif
                                        @endif
                                        @if($demo->youtube_video_id)
                                            <a class="demo-card__youtube" href="{{ $demo->youtube_url }}" target="_blank" rel="noopener">Open YouTube</a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <p class="demo-section__empty" data-demo-empty @if($demos->isNotEmpty()) hidden @endif>
                        No demos available in this category yet.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('script')
<script>
    function closeDemoVideo(card) {
        const player = card.querySelector('.demo-card__player');
        const iframe = player?.querySelector('iframe');
        const video = player?.querySelector('video');

        if (iframe) {
            iframe.src = '';
            iframe.hidden = true;
        }
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.hidden = true;
            video.load();
        }
        if (player) player.hidden = true;

        card.classList.remove('is-playing');
        card.dataset.activeVideoKind = '';
        card.dataset.playRecorded = '0';

        card.querySelectorAll('[data-video-kind]').forEach((button) => {
            if (button.dataset.videoLabel) {
                setDemoButtonLabel(button, button.dataset.videoLabel);
            }
        });
    }

    function setDemoButtonLabel(button, label) {
        const text = button.querySelector('span');

        if (text) {
            text.textContent = label;
            return;
        }

        button.textContent = label;
    }

    function toggleDemoVideo(button) {
        const card = button.closest('.demo-card');
        const player = card.querySelector('.demo-card__player');
        const iframe = player?.querySelector('iframe');
        const video = player?.querySelector('video');
        const videoKind = button.dataset.videoKind || 'demo';
        const embedUrl = card.dataset.youtubeEmbedUrl;
        const mp4VideoUrl = card.dataset.mp4VideoUrl;

        if (!player) return;

        if (card.classList.contains('is-playing') && card.dataset.activeVideoKind === videoKind) {
            closeDemoVideo(card);
            return;
        }

        document.querySelectorAll('.demo-card.is-playing').forEach(closeDemoVideo);

        if (videoKind === 'mp4') {
            if (!video || !mp4VideoUrl) return;

            if (iframe) iframe.hidden = true;
            video.src = mp4VideoUrl;
            video.hidden = false;
            video.play().catch(() => {});
        } else {
            if (!iframe || !embedUrl) return;

            if (video) video.hidden = true;
            iframe.src = `${embedUrl}?autoplay=1&rel=0`;
            iframe.hidden = false;
        }

        player.hidden = false;
        card.classList.add('is-playing');
        card.dataset.activeVideoKind = videoKind;

        card.querySelectorAll('[data-video-kind]').forEach((videoButton) => {
            if (!videoButton.dataset.videoLabel) return;

            setDemoButtonLabel(
                videoButton,
                videoButton.dataset.videoKind === videoKind
                    ? 'Close Video'
                    : videoButton.dataset.videoLabel
            );
        });

        recordDemoPlay(card);
    }

    function recordDemoPlay(card) {
        if (card.dataset.playRecorded === '1') return;

        const playUrl = card.dataset.playUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!playUrl || !csrfToken) return;

        card.dataset.playRecorded = '1';

        fetch(playUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Play count failed');
            }
        }).catch(() => {
            card.dataset.playRecorded = '0';
        });
    }

    function filterDemos(button) {
        const selectedCategory = button.dataset.demoFilter || 'All';
        const emptyState = document.querySelector('[data-demo-empty]');
        let visibleCount = 0;

        document.querySelectorAll('[data-demo-filter]').forEach((filterButton) => {
            const isActive = filterButton === button;
            filterButton.classList.toggle('active', isActive);
            filterButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('.demo-card').forEach((card) => {
            const shouldShow = selectedCategory === 'All' || card.dataset.demoCategory === selectedCategory;

            if (!shouldShow && card.classList.contains('is-playing')) {
                closeDemoVideo(card);
            }

            card.hidden = !shouldShow;
            if (shouldShow) visibleCount += 1;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
            emptyState.textContent = selectedCategory === 'All'
                ? 'No demos available yet.'
                : `No ${selectedCategory} demos available yet.`;
        }
    }

    document.querySelectorAll('[data-demo-filter]').forEach((button) => {
        button.addEventListener('click', () => filterDemos(button));
    });
</script>
@endpush
