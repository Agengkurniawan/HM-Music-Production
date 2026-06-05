@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')

        <div class="hall-second">
            @include('components.admin-header')

            @php
                $downloads = $downloads ?? collect();
                $topFile = $downloads->groupBy('file_name')->sortByDesc(fn ($items) => $items->count())->keys()->first() ?? '-';
            @endphp

            <section class="admin-downloadsales">
                <div class="downloadsales-summary">
                    <article>
                        <div class="downloadsales-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                        </div>
                        <div>
                            <span>Total Downloads</span>
                            <strong>{{ number_format($downloads->count()) }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="downloadsales-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </div>
                        <div>
                            <span>Most Downloaded File</span>
                            <strong>{{ $topFile }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="downloadsales-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <span>Most Active Users</span>
                            <strong>{{ number_format($downloads->pluck('user_id')->filter()->unique()->count()) }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="downloadsales-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <span>Download Revenue</span>
                            <strong>Rp {{ number_format($downloads->sum('amount'), 0, ',', '.') }}</strong>
                        </div>
                    </article>
                </div>

                <div class="downloadsales-panel">
                    <div class="downloadsales-panel__toolbar">
                        <div class="downloadsales-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <input id="downloadSalesSearchInput" type="search" placeholder="Search user or file">
                        </div>

                        <select id="downloadSalesType" aria-label="Filter download type" data-datatable-filter data-table="#adminDownloadSalesTable" data-column="1">
                            <option value="">All Types</option>
                            <option value="Style">Style</option>
                            <option value="Demo">Demo</option>
                        </select>

                        <select id="downloadSalesAccess" aria-label="Filter download status" data-datatable-filter data-table="#adminDownloadSalesTable" data-column="4">
                            <option value="">All Status</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                        </select>

                        <select id="downloadmonth" aria-label="Filter date range" data-datatable-date-filter data-table="#adminDownloadSalesTable" data-column="3">
                            <option value="month">This Month</option>
                            <option value="week">This Week</option>
                            <option value="today">Today</option>
                            <option value="all">All Time</option>
                        </select>
                    </div>

                    <div class="admin-datatable">
                        <table id="adminDownloadSalesTable" class="table align-middle admin-datatable__table downloadsales-table js-admin-datatable" data-search="#downloadSalesSearchInput">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>File</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($downloads as $download)
                                    <tr>
                                        @php
                                            $downloadType = ucfirst($download->download_type ?? 'style');
                                        @endphp
                                        <td>
                                            <div class="downloadsales-row__user">
                                                <div>{{ strtoupper(substr($download->user->name ?? 'U', 0, 1)) }}</div>
                                                <span>
                                                    <strong>{{ $download->user->name ?? 'Unknown User' }}</strong>
                                                    <small>{{ $download->user->email ?? '-' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-search="{{ $downloadType }}">
                                            <span class="downloadsales-type downloadsales-type--{{ strtolower($downloadType) }}">
                                                {{ $downloadType }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="downloadsales-row__file">
                                                <strong>{{ $download->file_name }}</strong>
                                                <small>{{ $download->style_name }}</small>
                                            </div>
                                        </td>
                                        <td class="downloadsales-row__date" data-date-column="2" data-date="{{ optional($download->downloaded_at)->format('Y-m-d') }}">{{ optional($download->downloaded_at)->format('d M Y, H:i') ?? '-' }}</td>
                                        <td data-search="{{ $download->status }}">
                                            <span class="downloadsales-status downloadsales-status--{{ strtolower($download->status) }}">
                                                {{ $download->status }}
                                            </span>
                                        </td>
                                        <td class="downloadsales-row__amount">{{ $download->amount > 0 ? 'Rp '.number_format($download->amount, 0, ',', '.') : 'Free' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
