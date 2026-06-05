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
                @endphp

                <div class="demo-section">
                    <div class="demo-section__header">
                        <div>
                            <span>Customer Demo Library</span>
                            <h1>Play YouTube demos published by admin</h1>
                            <p>Watch each demo and open installation videos when admin adds them. Continue to subscription when you are ready to unlock premium STY downloads.</p>
                        </div>

                        <div class="demo-section__actions">
                            <a class="demo-section__primary" href="{{ route('subcription') }}">Subscribe</a>

                            @guest
                                <a class="demo-section__secondary" href="{{ route('login') }}">Login</a>
                            @else
                                <a class="demo-section__secondary" href="{{ route('dashboard') }}">Dashboard</a>
                            @endguest
                        </div>
                    </div>

                    <div class="categories" aria-label="Demo categories">
                        @foreach($categories as $category)
                            <button class="{{ $loop->first ? 'active' : '' }}" type="button">{{ $category }}</button>
                        @endforeach
                    </div>

                    <div class="grid">
                        @foreach ($demos as $demo)
                            <article class="demo-card"
                                data-play-url="{{ route('demo.play', $demo) }}"
                                data-youtube-embed-url="{{ $demo->youtube_embed_url }}"
                                data-installation-embed-url="{{ $demo->installation_youtube_embed_url }}">
                                <div class="demo-card__image">
                                    <img src="{{ $demo->thumbnail_src }}" alt="{{ $demo->title }} demo cover">

                                    <span class="demo-card__source">YouTube</span>

                                    @if($demo->is_trending)
                                        <span class="demo-card__trend">Trending</span>
                                    @endif

                                    <button class="demo-card__play-icon" type="button" data-video-kind="demo" onclick="toggleDemoVideo(this)" aria-label="Play {{ $demo->title }} demo">
                                        <svg class="icon-play" viewBox="0 0 24 24" aria-hidden="true">
                                            <polygon points="8 5 19 12 8 19 8 5" />
                                        </svg>
                                    </button>

                                    <div class="demo-card__player" hidden>
                                        <iframe
                                            title="{{ $demo->title }} YouTube demo"
                                            src=""
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin"
                                            allowfullscreen></iframe>
                                    </div>
                                </div>

                                <div class="demo-card__content">
                                    <div>
                                        <h2>{{ $demo->title }}</h2>
                                        <span class="demo-card__category">{{ $demo->genre }}</span>
                                    </div>

                                    <div class="demo-card__meta">
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l12-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="18" cy="16" r="3" /></svg>
                                            {{ $demo->bpm }} BPM
                                        </span>
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17V7" /><path d="M8 19V5" /><path d="M12 16V8" /><path d="M16 21V3" /><path d="M20 18V6" /></svg>
                                            {{ $demo->key_signature ?? 'Live Key' }}
                                        </span>
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                                            {{ $demo->duration }}
                                        </span>
                                    </div>

                                    <div class="demo-card__actions">
                                        <button class="demo-card__play" type="button" data-video-kind="demo" onclick="toggleDemoVideo(this)">Play Video</button>
                                        @if($demo->installation_youtube_video_id)
                                            <button class="demo-card__install" type="button" data-video-kind="installation" onclick="toggleDemoVideo(this)">Instalasi</button>
                                        @endif
                                        <a class="demo-card__youtube" href="{{ $demo->youtube_url }}" target="_blank" rel="noopener">Open YouTube</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
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

        if (iframe) iframe.src = '';
        if (player) player.hidden = true;

        card.classList.remove('is-playing');
        card.dataset.playRecorded = '0';
        card.dataset.currentVideoKind = '';

        const playButton = card.querySelector('[data-video-kind="demo"].demo-card__play');
        const installButton = card.querySelector('[data-video-kind="installation"]');
        if (playButton) playButton.textContent = 'Play Video';
        if (installButton) installButton.textContent = 'Instalasi';
    }

    function toggleDemoVideo(button) {
        const card = button.closest('.demo-card');
        const player = card.querySelector('.demo-card__player');
        const iframe = player?.querySelector('iframe');
        const videoKind = button.dataset.videoKind || 'demo';
        const embedUrl = videoKind === 'installation'
            ? card.dataset.installationEmbedUrl
            : card.dataset.youtubeEmbedUrl;

        if (!player || !iframe || !embedUrl) return;

        if (card.classList.contains('is-playing') && card.dataset.currentVideoKind === videoKind) {
            closeDemoVideo(card);
            return;
        }

        document.querySelectorAll('.demo-card.is-playing').forEach(closeDemoVideo);

        iframe.src = `${embedUrl}?autoplay=1&rel=0`;
        player.hidden = false;
        card.classList.add('is-playing');
        card.dataset.currentVideoKind = videoKind;

        const playButton = card.querySelector('.demo-card__play');
        const installButton = card.querySelector('[data-video-kind="installation"]');
        if (playButton) playButton.textContent = videoKind === 'demo' ? 'Close Video' : 'Play Video';
        if (installButton) installButton.textContent = videoKind === 'installation' ? 'Tutup Instalasi' : 'Instalasi';

        if (videoKind === 'demo') {
            recordDemoPlay(card);
        }
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
</script>
@endpush
