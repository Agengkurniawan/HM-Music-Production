@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')

        <div class="hall-second">
            @include('components.admin-header')

            @php
                $requests = $requests ?? collect();
                $adminErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
            @endphp

            <section class="admin-sampling-requests">
                <div class="sampling-request-hero">
                    <div>
                        <span>Yamaha Style Sampling Backoffice</span>
                        <h1>N27 Sampling Requests</h1>
                        <p>Verify the sampling voice pack size, keep the sampling price at Rp 750.000, then process the customer .n27 file in Yamaha Expansion Manager and send the final Google Drive link.</p>
                    </div>
                    <strong>By Request Workflow</strong>
                </div>

                @if(session('success'))
                    <div class="sampling-admin-alert sampling-admin-alert--success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($adminErrors->any())
                    <div class="sampling-admin-alert sampling-admin-alert--error">
                        {{ $adminErrors->first() }}
                    </div>
                @endif

                <div class="sampling-request-summary">
                    <article>
                        <div class="sampling-request-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <path d="M14 2v6h6" />
                                <path d="M8 13h8" />
                            </svg>
                        </div>
                        <div>
                            <span>Total Requests</span>
                            <strong>{{ $requests->count() }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="sampling-request-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <path d="M17 8l-5-5-5 5" />
                                <path d="M12 3v12" />
                            </svg>
                        </div>
                        <div>
                            <span>N27 Uploaded</span>
                            <strong>{{ $requests->where('status', \App\Models\SamplingRequest::STATUS_N27_UPLOADED)->count() }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="sampling-request-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2v4" />
                                <path d="M12 18v4" />
                                <path d="M2 12h4" />
                                <path d="M18 12h4" />
                                <path d="m4.93 4.93 2.83 2.83" />
                                <path d="m16.24 16.24 2.83 2.83" />
                                <path d="m4.93 19.07 2.83-2.83" />
                                <path d="m16.24 7.76 2.83-2.83" />
                            </svg>
                        </div>
                        <div>
                            <span>Processing</span>
                            <strong>{{ $requests->where('status', \App\Models\SamplingRequest::STATUS_PROCESSING)->count() }}</strong>
                        </div>
                    </article>
                    <article>
                        <div class="sampling-request-summary__icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11.5 4.43" />
                                <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.33-1.33" />
                            </svg>
                        </div>
                        <div>
                            <span>Ready / Completed</span>
                            <strong>{{ $requests->filter(fn ($request) => in_array($request->status, [\App\Models\SamplingRequest::STATUS_READY, \App\Models\SamplingRequest::STATUS_COMPLETED], true))->count() }}</strong>
                        </div>
                    </article>
                </div>

                <div class="sampling-request-panel">
                    <div class="sampling-request-panel__heading">
                        <div>
                            <span>Request Queue</span>
                            <h2>Customer Sampling Orders</h2>
                        </div>
                        <a href="{{ route('admin.stylesampling') }}">Style Catalog</a>
                    </div>

                    <div class="sampling-request-panel__toolbar">
                        <div class="sampling-request-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" />
                                <path d="M21 21l-4.35-4.35" />
                            </svg>
                            <input id="samplingRequestSearchInput" type="search" placeholder="Search customer, order, or product">
                        </div>

                        <select id="samplingRequestStatusFilter" aria-label="Filter sampling request status" data-datatable-filter data-table="#adminSamplingRequestTable" data-column="3">
                            <option value="">All Status</option>
                            @foreach(\App\Models\SamplingRequest::STATUSES as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-datatable">
                        <table id="adminSamplingRequestTable" class="table align-middle admin-datatable__table sampling-request-table js-admin-datatable" data-search="#samplingRequestSearchInput" data-unsortable="5">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Order</th>
                                    <th>N27 File</th>
                                    <th>Status</th>
                                    <th>Delivery</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $request)
                                    @php
                                        $samplingPaymentAmount = $request->amount > 0
                                            ? $request->amount
                                            : \App\Models\StyleSampling::SAMPLING_REQUEST_PRICE;
                                        $samplingPackOption = $request->pack_name
                                            ? \App\Models\StyleSampling::samplingRequestOption($request->pack_name)
                                            : null;
                                        $samplingProductName = $samplingPackOption['label'] ?? $request->product_name;
                                        $samplingPackName = $request->pack_name
                                            ? \App\Models\StyleSampling::normalizeSamplingPackName($request->pack_name)
                                            : 'Sampling Voice Pack';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="sampling-request-user">
                                                <div>{{ strtoupper(substr($request->user?->name ?? 'C', 0, 1)) }}</div>
                                                <span>
                                                    <strong>{{ $request->user?->name ?? 'Customer removed' }}</strong>
                                                    <small>{{ $request->user?->email ?? '-' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="sampling-request-order">
                                                <strong>{{ $request->order_reference }}</strong>
                                                <span>{{ $samplingProductName }}</span>
                                                <small>{{ $samplingPackName }} / Rp {{ number_format($samplingPaymentAmount, 0, ',', '.') }}</small>
                                                @if($request->keyboard_storage_mb)
                                                    <small>Keyboard: {{ $request->keyboard_storage_mb }} MB</small>
                                                @endif
                                                @if($request->customer_notes)
                                                    <small>{{ $request->customer_notes }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($request->has_n27_file)
                                                <a class="sampling-file-link" href="{{ route('admin.sampling-requests.n27.download', $request) }}">
                                                    {{ $request->n27_download_name }}
                                                </a>
                                                <small class="sampling-file-date">{{ optional($request->n27_uploaded_at)->format('d M Y H:i') }}</small>
                                            @else
                                                <span class="sampling-file-empty">Waiting for customer upload</span>
                                            @endif
                                        </td>
                                        <td data-search="{{ $request->status }}">
                                            <span class="sampling-admin-status sampling-admin-status--{{ $request->status_class }}">
                                                {{ $request->status }}
                                            </span>
                                            <small class="sampling-payment-status">Payment: {{ $request->payment_status }}</small>
                                        </td>
                                        <td>
                                            @if($request->google_drive_link)
                                                <a class="sampling-drive-link" href="{{ $request->google_drive_link }}" target="_blank" rel="noopener">Open Drive Link</a>
                                                <small class="sampling-file-date">{{ optional($request->delivered_at)->format('d M Y H:i') }}</small>
                                            @else
                                                <span class="sampling-file-empty">No delivery link yet</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="sampling-request-actions">
                                                @if($request->payment_status !== \App\Models\SamplingRequest::PAYMENT_PAID)
                                                    <button type="button"
                                                        title="Confirm sampling payment"
                                                        aria-label="Confirm payment for {{ $request->order_reference }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#samplingPaymentModal"
                                                        data-sampling-payment-action="confirm"
                                                        data-sampling-reference="{{ $request->order_reference }}"
                                                        data-sampling-customer="{{ $request->user?->name }}"
                                                        data-sampling-product="{{ $samplingProductName }}"
                                                        data-sampling-amount="{{ $samplingPaymentAmount }}"
                                                        data-sampling-notes="{{ $request->admin_notes }}"
                                                        data-sampling-url="{{ route('admin.sampling-requests.payment', $request) }}">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M12 2v20" />
                                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    <button type="button" disabled title="Payment paid" aria-label="Payment paid">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M20 6 9 17l-5-5" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                @if($request->has_n27_file)
                                                    <a href="{{ route('admin.sampling-requests.n27.download', $request) }}" title="Download N27" aria-label="Download N27 for {{ $request->order_reference }}">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M12 3v12" />
                                                            <path d="M7 10l5 5 5-5" />
                                                            <path d="M5 21h14" />
                                                        </svg>
                                                    </a>
                                                @else
                                                    <button type="button" disabled title="N27 not uploaded" aria-label="N27 not uploaded">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M12 3v12" />
                                                            <path d="M7 10l5 5 5-5" />
                                                            <path d="M5 21h14" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                <form action="{{ route('admin.sampling-requests.processing', $request) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" @disabled(! $request->has_n27_file || $request->is_ready) title="Mark processing" aria-label="Mark {{ $request->order_reference }} processing">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M12 2v4" />
                                                            <path d="M12 18v4" />
                                                            <path d="m4.93 4.93 2.83 2.83" />
                                                            <path d="m16.24 16.24 2.83 2.83" />
                                                            <path d="M2 12h4" />
                                                            <path d="M18 12h4" />
                                                            <path d="m4.93 19.07 2.83-2.83" />
                                                            <path d="m16.24 7.76 2.83-2.83" />
                                                        </svg>
                                                    </button>
                                                </form>

                                                <button type="button"
                                                    title="Add Google Drive link"
                                                    aria-label="Add Google Drive link for {{ $request->order_reference }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#samplingDeliveryModal"
                                                    data-sampling-action="delivery"
                                                    data-sampling-reference="{{ $request->order_reference }}"
                                                    data-sampling-customer="{{ $request->user?->name }}"
                                                    data-sampling-product="{{ $request->product_name }}"
                                                    data-sampling-link="{{ $request->google_drive_link }}"
                                                    data-sampling-notes="{{ $request->delivery_notes }}"
                                                    data-sampling-url="{{ route('admin.sampling-requests.delivery', $request) }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11.5 4.43" />
                                                        <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.33-1.33" />
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
            </section>
        </div>
    </div>
</section>

@include('components.modal')
@endsection

@push('script')
<script>
    document.querySelectorAll('[data-sampling-payment-action="confirm"]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.querySelector('#samplingPaymentModal');
            const form = modal.querySelector('form');
            const context = modal.querySelector('[data-sampling-payment-context]');
            const amountInput = modal.querySelector('[data-sampling-payment-amount]');
            const notesInput = modal.querySelector('[data-sampling-payment-notes]');

            form.action = button.dataset.samplingUrl;
            context.textContent = `${button.dataset.samplingReference} / ${button.dataset.samplingCustomer || 'Customer'} / ${button.dataset.samplingProduct}`;
            amountInput.value = button.dataset.samplingAmount || '';
            notesInput.value = button.dataset.samplingNotes || '';
        });
    });

    document.querySelectorAll('[data-sampling-action="delivery"]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.querySelector('#samplingDeliveryModal');
            const form = modal.querySelector('form');
            const context = modal.querySelector('[data-sampling-context]');
            const linkInput = modal.querySelector('[data-sampling-link-input]');
            const notesInput = modal.querySelector('[data-sampling-notes-input]');

            form.action = button.dataset.samplingUrl;
            context.textContent = `${button.dataset.samplingReference} / ${button.dataset.samplingCustomer || 'Customer'} / ${button.dataset.samplingProduct}`;
            linkInput.value = button.dataset.samplingLink || '';
            notesInput.value = button.dataset.samplingNotes || '';
        });
    });
</script>
@endpush
