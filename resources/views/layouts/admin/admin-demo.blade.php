@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')

        <div class="hall-second">
            @include('components.admin-header')

            @php
                $genres = $genres ?? \App\Models\MusicDemo::GENRES;
                $noneGenre = \App\Models\MusicDemo::GENRE_NONE;
                $demos = $demos ?? collect();
                $summary = $demoSummary ?? [
                    'total' => $demos->count(),
                    'published' => $demos->where('status', 'Published')->count(),
                    'draft' => $demos->where('status', 'Draft')->count(),
                    'trending' => $demos->where('is_trending', true)->count(),
                    'plays' => $demos->sum('plays_count'),
                ];
            @endphp
            <section class="admin-demo">
                <div class="admin-demo__summary">
                    <article>
                        <div class="admin-demo__summary-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </div>
                        <div class="admin-demo__summary-body">
                            <span>Total Demos</span>
                            <strong>{{ number_format($summary['total']) }}</strong>
                            <small>{{ number_format($summary['published']) }} published / {{ number_format($summary['draft']) }} draft</small>
                        </div>
                    </article>

                    <article>
                        <div class="admin-demo__summary-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.47 6.03.88-4.36 4.25 1.03 6-5.4-2.84-5.4 2.84 1.03-6-4.36-4.25 6.03-.88L12 3z"/></svg>
                        </div>
                        <div class="admin-demo__summary-body">
                            <span>Published Videos</span>
                            <strong>{{ number_format($summary['published']) }}</strong>
                            <small>{{ number_format($summary['draft']) }} drafts waiting</small>
                        </div>
                    </article>

                    <article>
                        <div class="admin-demo__summary-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 3 14h8l-1 8 10-12h-8l1-8Z"/></svg>
                        </div>
                        <div class="admin-demo__summary-body">
                            <span>Trending</span>
                            <strong>{{ number_format($summary['trending']) }}</strong>
                            <small>Highlighted in customer demo list</small>
                        </div>
                    </article>

                    <article>
                        <div class="admin-demo__summary-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-7"/></svg>
                        </div>
                        <div class="admin-demo__summary-body">
                            <span>Total Plays</span>
                            <strong>{{ number_format($summary['plays']) }}</strong>
                            <small>Recorded when a customer opens a player</small>
                        </div>
                    </article>
                </div>

                <div class="admin-demo_grid">
                    <div class="demo-manager">
                        <div class="demo-manager__toolbar">
                            <div class="demo-manager__search">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="M21 21l-4.35-4.35" />
                                </svg>
                                <input id="demoSearchInput" type="search" placeholder="Search demo">
                            </div>

                            <select id="demoGenreFilter"
                                aria-label="Filter genre"
                                data-datatable-filter
                                data-table="#adminDemoTable"
                                data-column="1">

                                <option value="">All Genres</option>

                                @foreach($genres as $genre)
                                <option value="{{ $genre }}">{{ $genre }}</option>
                                @endforeach
                            </select>

                            <button
                                class="admin-demo__primary"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#uploadDemoModal">

                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 5v14" />
                                    <path d="M5 12h14" />
                                </svg>
                                Create Demo
                            </button>
                        </div>
                        <div class="demo-table">
                            <div class="admin-datatable demo-table__datatables">
                                <table id="adminDemoTable" class="table align-middle admin-datatable__table admin-demo__table js-admin-datatable" data-search="#demoSearchInput" data-unsortable="6">
                                    <thead>
                                        <tr>
                                            <th>Demo</th>
                                            <th>Genre / BPM</th>
                                            <th>YouTube</th>
                                            <th>MP4</th>
                                            <th>Trending</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($demos as $demo)
                                        <tr>
                                            <td>
                                                <div class="admin-demo-table__demo">
                                                    <img src="{{ $demo->thumbnail_src }}" alt="{{ $demo->title }} thumbnail">
                                                    <div>
                                                        <strong>{{ $demo->title }}</strong>
                                                        <span>{{ $demo->duration }} / {{ number_format($demo->plays_count) }} plays</span>
                                                        @if($demo->youtube_video_id)
                                                            <a href="{{ $demo->youtube_url }}" target="_blank" rel="noopener">Watch on YouTube</a>
                                                        @else
                                                            <small class="admin-demo-table__missing">No YouTube URL</small>
                                                        @endif

                                                    </div>
                                                </div>
                                            </td>
                                            <td data-search="{{ $demo->display_genre }}">
                                                <div class="admin-demo-table__meta">
                                                    <span class="admin-demo-table__genre">{{ $demo->display_genre }}</span>
                                                    <span class="admin-demo-table__bpm">{{ $demo->display_bpm }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="admin-demo-table__youtube {{ $demo->youtube_video_id ? 'admin-demo-table__youtube--connected' : '' }}">
                                                    {{ $demo->youtube_video_id ? 'Connected' : 'Missing URL' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($demo->installation_video_url)
                                                    <a class="admin-demo-table__media admin-demo-table__media--connected" href="{{ $demo->installation_video_url }}" target="_blank" rel="noopener">MP4 Ready</a>
                                                @else
                                                    <span class="admin-demo-table__media">No MP4</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form class="admin-demo-table__trend-form" action="{{ route('admin.demo.trending', $demo) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_trending" value="0">
                                                    <label class="switch-control" aria-label="Trending setting for {{ $demo->title }}">
                                                        <input type="checkbox" name="is_trending" value="1" @checked($demo->is_trending) onchange="this.form.submit()">
                                                        <span></span>
                                                    </label>
                                                </form>
                                            </td>
                                            <td>
                                                <small class="admin-demo-table__status">{{ $demo->status }}</small>
                                            </td>
                                            <td>
                                                <div class="admin-demo-table__actions">
                                                    <button class="admin-demo-table__edit"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#demoEditModal"
                                                        data-demo-action="edit"
                                                        data-demo-title="{{ $demo->title }}"
                                                        data-demo-youtube-url="{{ $demo->youtube_url }}"
                                                        data-demo-has-installation-video="{{ $demo->installation_video_path ? '1' : '0' }}"
                                                        data-demo-installation-video-url="{{ $demo->installation_video_url }}"
                                                        data-demo-genre="{{ $demo->genre === $noneGenre ? '' : $demo->genre }}"
                                                        data-demo-bpm="{{ $demo->bpm > 0 ? $demo->bpm : '' }}"
                                                        data-demo-duration="{{ $demo->duration }}"
                                                        data-demo-key-signature="{{ $demo->key_signature }}"
                                                        data-demo-status="{{ $demo->status }}"
                                                        data-demo-trending="{{ $demo->is_trending ? '1' : '0' }}"
                                                        data-demo-update-url="{{ route('admin.demo.update', $demo) }}">Edit</button>
                                                    <button class="admin-demo-table__delete"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#demoDeleteModal"
                                                        data-demo-action="delete"
                                                        data-demo-title="{{ $demo->title }}"
                                                        data-demo-delete-url="{{ route('admin.demo.destroy', $demo) }}">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</section>

@include('components.modal', [
    'genres' => $genres ?? [],
    'categories' => $categories ?? [],
])

@endsection

@push('script')
<script>
    document.querySelectorAll('[data-demo-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetSelector = button.getAttribute('data-bs-target');
            const modal = document.querySelector(targetSelector);

            if (!modal) return;

            modal.querySelectorAll('[data-demo-modal-title]').forEach((target) => {
                target.textContent = button.dataset.demoTitle || 'Selected demo';
            });

            const form = modal.querySelector('form');
            const title = modal.querySelector('[data-demo-edit-title]');
            const youtubeUrl = modal.querySelector('[data-demo-edit-youtube-url]');
            const installationVideo = modal.querySelector('[data-demo-edit-installation-video]');
            const installationStatus = modal.querySelector('[data-demo-edit-installation-status]');
            const removeInstallation = modal.querySelector('[data-demo-edit-remove-installation]');
            const genre = modal.querySelector('[data-demo-edit-genre]');
            const bpm = modal.querySelector('[data-demo-edit-bpm]');
            const duration = modal.querySelector('[data-demo-edit-duration]');
            const keySignature = modal.querySelector('[data-demo-edit-key-signature]');
            const status = modal.querySelector('[data-demo-edit-status]');
            const trending = modal.querySelector('[data-demo-edit-trending]');

            if (form && button.dataset.demoUpdateUrl) form.action = button.dataset.demoUpdateUrl;
            if (form && button.dataset.demoDeleteUrl) form.action = button.dataset.demoDeleteUrl;
            if (title) title.value = button.dataset.demoTitle || '';
            if (youtubeUrl) youtubeUrl.value = button.dataset.demoYoutubeUrl || '';
            if (installationVideo) installationVideo.value = '';
            if (installationStatus) {
                installationStatus.textContent = button.dataset.demoHasInstallationVideo === '1'
                    ? 'Current MP4 video is ready. Upload a new file to replace it.'
                    : 'No MP4 video yet. Upload one to show the MP4 video button.';
            }
            if (removeInstallation) {
                removeInstallation.checked = false;
                removeInstallation.disabled = button.dataset.demoHasInstallationVideo !== '1';
            }
            if (genre) genre.value = button.dataset.demoGenre || '';
            if (bpm) bpm.value = button.dataset.demoBpm || '';
            if (duration) duration.value = button.dataset.demoDuration || '';
            if (keySignature) keySignature.value = button.dataset.demoKeySignature || '';
            if (status) status.value = button.dataset.demoStatus || 'Published';
            if (trending) trending.checked = button.dataset.demoTrending === '1';
        });
    });
</script>
@endpush
