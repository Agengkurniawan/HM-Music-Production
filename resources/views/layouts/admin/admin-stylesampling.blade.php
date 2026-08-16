@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')

        <div class="hall-second">
            @include('components.admin-header')

            @php
            $categories = $categories ?? \App\Models\StyleSampling::CATEGORIES;
            $packs = $packs ?? \App\Models\StyleSampling::PACKS;
            $statuses = $statuses ?? \App\Models\StyleSampling::STATUSES;
            $styles = collect($styles ?? [])->filter(fn ($item) => $item->has_style_file);
            @endphp

            <section class="admin-style-sampling">
                @if(session('success'))
                    <div class="usermanagement-alert usermanagement-alert--success">{{ session('success') }}</div>
                @endif
                @if($errors->styleAction->any())
                    <div class="usermanagement-alert usermanagement-alert--error">{{ $errors->styleAction->first() }}</div>
                @endif
                <div class="style-summary">
                    <article>
                        <div class="style-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 18V5l12-2v13" />
                                <circle cx="6" cy="18" r="3" />
                                <circle cx="18" cy="16" r="3" />
                            </svg>
                        </div>
                        <div>
                            <span>Total Styles</span>
                            <strong>{{ $styles->count() }}</strong>
                        </div>
                    </article>

                    <article>
                        <div class="style-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3l2.7 5.47 6.03.88-4.36 4.25 1.03 6-5.4-2.84-5.4 2.84 1.03-6-4.36-4.25 6.03-.88L12 3z" />
                            </svg>
                        </div>
                        <div>
                            <span>Draft</span>
                            <strong>{{ $styles->where('status', 'Draft')->count() }}</strong>
                        </div>
                    </article>

                    <article>
                        <div class="style-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>
                        <div>
                            <span>Published</span>
                            <strong>{{ $styles->where('status', 'Published')->count() }}</strong>
                        </div>
                    </article>

                    <article>
                        <div class="style-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 5h16" />
                                <path d="M4 12h16" />
                                <path d="M4 19h16" />
                                <path d="M8 5v14" />
                            </svg>
                        </div>
                        <div>
                            <span>Categories</span>
                            <strong>{{ count($categories) }}</strong>
                        </div>
                    </article>
                </div>

                <div class="admin-style-sampling__grid">
                    <div class="style-manager">
                        <div class="style-manager__toolbar">
                            <div class="style-manager__search">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="M21 21l-4.35-4.35" />
                                </svg>
                                <input id="styleSamplingSearchInput" type="search" placeholder="Search style catalog">
                            </div>

                            <select id="styleSamplingGenreFilter"
                                aria-label="Filter category"
                                data-datatable-filter
                                data-table="#adminStyleSamplingTable"
                                data-column="1">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>

                            <select id="styleSamplingPackFilter"
                                aria-label="Filter expansion pack"
                                data-datatable-filter
                                data-table="#adminStyleSamplingTable"
                                data-column="2">
                                <option value="">All Packs</option>
                                @foreach($packs as $pack)
                                <option value="{{ $pack }}">{{ $pack }}</option>
                                @endforeach
                            </select>

                            <select id="styleSamplingStatusFilter"
                                aria-label="Filter publication status"
                                data-datatable-filter
                                data-table="#adminStyleSamplingTable"
                                data-column="3">
                                <option value="">All Status</option>
                                @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>

                            <a class="style-manager__n27-link" href="{{ route('admin.sampling-requests') }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 2v6h6" />
                                    <path d="M8 13h8" />
                                    <path d="M8 17h6" />
                                </svg>
                                N27 Requests
                            </a>
                            <button class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#uploadStyleModal">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <path d="M13.5 3H12H8C6.34315 3 5 4.34315 5 6V18C5 19.6569 6.34315 21 8 21H12M13.5 3L19 8.625M13.5 3V7.625C13.5 8.17728 13.9477 8.625 14.5 8.625H19M19 8.625V11.8125" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M17.5 21L17.5 15M17.5 15L20 17.5M17.5 15L15 17.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </g>
                                </svg> Upload Style
                            </button>
                        </div>

                        <div class="admin-datatable">
                            <table id="adminStyleSamplingTable" class="table align-middle admin-datatable__table style-table js-admin-datatable" data-search="#styleSamplingSearchInput" data-unsortable="4">
                                <thead>
                                    <tr>
                                        <th>Style</th>
                                        <th>Category</th>
                                        <th>Pack</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($styles as $style)
                                    @php
                                        $stylePackName = \App\Models\StyleSampling::samplingPackForCategory($style->category, $style->pack);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="style-row__title">
                                                <img class="style-row__image" src="{{ $style->cover_src }}" alt="{{ $style->name }} cover">
                                                <div>
                                                    <strong>{{ $style->name }}</strong>
                                                    <span><b class="style-status style-status--{{ strtolower($style->status) }}">{{ $style->status }}</b> updated {{ $style->updated_at->format('d M Y') }}</span>
                                                    <span class="style-ai-metadata">
                                                        AI: {{ ucfirst($style->ai_enrichment_status ?: 'pending') }}
                                                        @if($style->ai_artist) · {{ $style->ai_artist }} @endif
                                                        @if($style->ai_song_title) · {{ $style->ai_song_title }} @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-search="{{ $style->category }}">
                                            <span class="style-row__category">{{ $style->category }}</span>
                                        </td>
                                        <td data-search="{{ $stylePackName ?? $style->pack }}">
                                            <div class="style-row__pack">
                                                <strong>{{ $stylePackName ?? $style->pack ?? 'Unassigned Pack' }}</strong>
                                                <small>{{ $style->display_style_name }} / {{ $style->file_size ?? '0 MB' }}</small>
                                            </div>
                                        </td>
                                        <td data-search="{{ $style->status }}">
                                            <div class="style-row__status">
                                                <span class="style-status style-status--{{ strtolower($style->status) }}">{{ $style->status }}</span>
                                                <small>{{ number_format($style->downloads_count) }} downloads</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="style-row__actions">
                                                <form action="{{ route('admin.stylesampling.ai-metadata', $style) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" aria-label="Refresh AI metadata {{ $style->name }}" title="Refresh AI Metadata">AI</button>
                                                </form>
                                                @if($style->status === 'Draft')
                                                <form action="{{ route('admin.stylesampling.activate', $style) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="style-row__activate" type="submit" aria-label="Activate {{ $style->name }}" title="Activate">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M20 6 9 17l-5-5" />
                                                        </svg>
                                                    </button>
                                                </form>
                                                @else
                                                <form action="{{ route('admin.stylesampling.deactivate', $style) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="style-row__deactivate" type="submit" aria-label="Deactivate {{ $style->name }}" title="Deactivate">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M18 6 6 18" />
                                                            <path d="m6 6 12 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                                @endif

                                                <button type="button"
                                                    aria-label="Edit {{ $style->name }}"
                                                    title="Edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#styleSamplingEditModal"
                                                    data-style-action="edit"
                                                    data-style-id="{{ $style->id }}"
                                                    data-style-name="{{ $style->name }}"
                                                    data-style-category="{{ $style->category }}"
                                                    data-style-update-url="{{ route('admin.stylesampling.update', $style) }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                </button>

                                                <button class="is-danger"
                                                    type="button"
                                                    aria-label="Delete {{ $style->name }}"
                                                    title="Delete"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#styleSamplingDeleteModal"
                                                    data-style-action="delete"
                                                    data-style-name="{{ $style->name }}"
                                                    data-style-delete-url="{{ route('admin.stylesampling.destroy', $style) }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M3 6h18" />
                                                        <path d="M8 6V4h8v2" />
                                                        <path d="M19 6l-1 14H6L5 6" />
                                                        <path d="M10 11v6" />
                                                        <path d="M14 11v6" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>

@include('components.modal', [
    'genres' => \App\Models\MusicDemo::GENRES,
    'categories' => $categories,
    'packs' => $packs,
])
@endsection

@push('script')
<script>
    document.querySelectorAll('[data-file-label]').forEach((input) => {
        input.addEventListener('change', () => {
            const label = document.querySelector(input.dataset.fileLabel);
            const file = input.files && input.files[0];

            if (label && file) label.textContent = file.name;
        });
    });

    @if(isset($errors) && $errors->uploadStyle->any())
    document.addEventListener('DOMContentLoaded', () => {
        const uploadStyleModal = document.getElementById('uploadStyleModal');

        if (uploadStyleModal && window.bootstrap) {
            new bootstrap.Modal(uploadStyleModal).show();
        }
    });
    @endif

    document.querySelectorAll('[data-style-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetSelector = button.getAttribute('data-bs-target');
            const modal = document.querySelector(targetSelector);

            if (!modal) return;

            modal.querySelectorAll('[data-style-modal-name]').forEach((nameTarget) => {
                nameTarget.textContent = button.dataset.styleName || 'Selected style';
            });

            const editName = modal.querySelector('[data-style-edit-name]');
            const editCategory = modal.querySelector('[data-style-edit-category]');
            const form = modal.querySelector('form');

            if (form && button.dataset.styleUpdateUrl) form.action = button.dataset.styleUpdateUrl;
            if (form && button.dataset.styleDeleteUrl) form.action = button.dataset.styleDeleteUrl;
            if (editName) editName.value = button.dataset.styleName || '';
            if (editCategory) editCategory.value = button.dataset.styleCategory || '';
        });
    });

    @if($errors->editStyle->any() && session('admin_style_edit_id'))
    window.addEventListener('load', () => {
        const button = document.querySelector('[data-style-action="edit"][data-style-id="{{ session('admin_style_edit_id') }}"]');
        if (button) button.click();
    });
    @endif
</script>
@endpush
