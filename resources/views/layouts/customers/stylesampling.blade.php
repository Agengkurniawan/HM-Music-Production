@extends('apps')

@section('content')
<section class="stylesampling">
    <div class="content-hall-first">
        @include('components.sidebar')
        <div class="hall-second">
            @include('components.header')
            <div class="sectionprofil-6">
                @php
                    $styles = $styles ?? collect();
                    $samplingRequests = $samplingRequests ?? collect();
                    $selectedCategory = $selectedCategory ?? request('category');
                    $selectedPack = $selectedPack ?? request('pack');
                    $styleCategories = $styleCategories ?? collect();
                    $stylePacks = $stylePacks ?? collect();
                    $samplingPackOptions = $samplingPackOptions ?? \App\Models\StyleSampling::samplingRequestOptions();
                    $assetType = $assetType ?? request('type', 'style');
                    $assetType = in_array($assetType, ['style', 'sampling'], true) ? $assetType : 'style';
                    $isSamplingView = $assetType === 'sampling';
                    $isSubscribed = $isSubscribed ?? false;
                    $selectedPackLabel = $selectedPack
                        ? (($isSamplingView ? ($samplingPackOptions[$selectedPack]['label'] ?? $selectedPack) : $selectedPack))
                        : null;
                    $pageTitle = $isSamplingView
                        ? ($selectedPackLabel ? "{$selectedPackLabel} N27" : 'Sampling Voice Packs')
                        : ($selectedPackLabel ? "{$selectedPackLabel} Styles" : ($selectedCategory ? "{$selectedCategory} Styles" : 'Style Library'));
                    $downloadErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
                    $autoOpenPaymentRequestId = session('sampling_payment_request_id');
                @endphp

                <div class="style-page">
                    <div class="style-page__header">
                        <div>
                            <span>{{ $isSamplingView ? 'Voice Kit' : 'STY Library' }}</span>
                            <h1>{{ $pageTitle }}</h1>
                            <p>
                                @if($isSamplingView)
                                    Beli salah satu sampling voice pack dulu. Setelah pembayaran Midtrans berhasil, upload file .n27 agar admin connect voice kit ke keyboard.
                                @elseif($selectedPackLabel)
                                    Showing styles from {{ $selectedPackLabel }}. Download file STY dengan subscription aktif, lalu beli sampling voice pack yang sesuai bila style membutuhkan voice kit.
                                @elseif($selectedCategory)
                                    Showing {{ $selectedCategory }} styles only. Customer hanya mengunduh file .sty; playback style tidak dibuka di halaman ini.
                                @else
                                    Pilih style, download STY, dan cek voice pack yang sesuai.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="filters">
                        <label>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" />
                                <path d="M21 21l-4.35-4.35" />
                            </svg>
                            <input type="text" placeholder="{{ $isSamplingView ? 'Search sampling orders...' : 'Search style downloads...' }}" data-style-search-input>
                        </label>
                        <select aria-label="Filter style category" data-style-filter="category">
                            @if($isSamplingView)
                                <option value="" selected>Style Categories</option>
                            @endif
                            <option value="{{ route('stylesampling', array_filter(['type' => 'style', 'pack' => $assetType === 'style' ? $selectedPack : null])) }}" @selected($assetType === 'style' && !$selectedCategory)>All Styles</option>
                            @foreach($styleCategories as $category)
                                <option value="{{ route('stylesampling', array_filter(['type' => 'style', 'category' => $category, 'pack' => $assetType === 'style' ? $selectedPack : null])) }}" @selected($assetType === 'style' && $selectedCategory === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        <select aria-label="Filter expansion pack" data-style-filter="pack">
                            @if($isSamplingView)
                                <option value="{{ route('stylesampling', ['type' => 'sampling']) }}" @selected(!$selectedPack)>All Sampling Packs</option>
                                @foreach($samplingPackOptions as $packName => $pack)
                                    <option value="{{ route('stylesampling', ['type' => 'sampling', 'pack' => $packName]) }}" @selected($selectedPack === $packName)>{{ $pack['label'] }}</option>
                                @endforeach
                            @else
                                <option value="{{ route('stylesampling', array_filter(['type' => 'style', 'category' => $selectedCategory])) }}" @selected(!$selectedPack)>All Style Packs</option>
                                @foreach($stylePacks as $packName)
                                    <option value="{{ route('stylesampling', array_filter(['type' => 'style', 'category' => $selectedCategory, 'pack' => $packName])) }}" @selected($selectedPack === $packName)>{{ $packName }}</option>
                                @endforeach
                                <option value="{{ route('stylesampling', ['type' => 'sampling']) }}">Beli Sampling Pack</option>
                            @endif
                        </select>
                    </div>

                    @if(session('success'))
                        <div class="style-page__notice style-page__notice--success">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            <div>
                                <strong>Saved</strong>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if($downloadErrors->any())
                        <div class="style-page__notice style-page__notice--error">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                            <div>
                                <strong>Action unavailable</strong>
                                <span>{{ $downloadErrors->first() }}</span>
                            </div>
                        </div>
                    @endif

                    @if($isSamplingView)
                        <div class="sampling-pack-strip">
                            @foreach($samplingPackOptions as $packName => $pack)
                                <span>
                                    <strong>{{ $pack['short_label'] }}</strong>
                                    <small>{{ \App\Models\StyleSampling::formatSamplingSize($pack['size_mb']) }} / {{ \App\Models\StyleSampling::formatSamplingPrice($pack['price']) }}</small>
                                    <em>{{ count($pack['voice_kits'] ?? []) }} voice kits</em>
                                </span>
                            @endforeach
                        </div>

                        <form class="sampling-request-form" action="{{ route('sampling-requests.store') }}" method="POST" data-sampling-checkout-form>
                            @csrf
                            <div class="sampling-request-form__header">
                                <div>
                                    <span>Beli pack voice, lalu upload N27</span>
                                    <h2>Beli Sampling Pack</h2>
                                </div>
                                <strong>Harga sampling: Rp {{ number_format(\App\Models\StyleSampling::SAMPLING_REQUEST_PRICE, 0, ',', '.') }}</strong>
                            </div>

                            <div class="sampling-request-form__grid">
                                <label>
                                    Kebutuhan Pack
                                    <select name="pack_name" required>
                                        @foreach($samplingPackOptions as $packName => $pack)
                                            <option value="{{ $packName }}"
                                                data-price="{{ (int) ($pack['price'] ?? 0) }}"
                                                data-size="{{ $pack['size_mb'] ?? '' }}"
                                                data-summary="{{ $pack['summary'] }}"
                                                @selected(old('pack_name', $selectedPack) === $packName)>
                                                {{ $pack['label'] }} - {{ \App\Models\StyleSampling::formatSamplingSize($pack['size_mb']) }} - {{ \App\Models\StyleSampling::formatSamplingPrice($pack['price']) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('pack_name')<small>{{ $message }}</small>@enderror
                                </label>

                                <label>
                                    Kapasitas Keyboard (MB)
                                    <input type="number" name="keyboard_storage_mb" min="1" max="4096" value="{{ old('keyboard_storage_mb') }}" placeholder="Contoh: 768">
                                    @error('keyboard_storage_mb')<small>{{ $message }}</small>@enderror
                                </label>
                            </div>

                            <label>
                                Catatan Kebutuhan
                                <textarea name="customer_notes" rows="4" placeholder="Contoh: style yang dipakai HM Dangdut Raya, keyboard saya tersisa 512 MB, mohon connect voice kit yang sesuai.">{{ old('customer_notes') }}</textarea>
                                @error('customer_notes')<small>{{ $message }}</small>@enderror
                            </label>

                            <div class="sampling-request-form__payment" data-sampling-payment-preview>
                                <div>
                                    <span data-sampling-preview-label>Sampling Payment</span>
                                    <strong data-sampling-preview-price>Rp 0</strong>
                                </div>
                                <p data-sampling-preview-summary>Pilih pack untuk melihat harga dan ukuran sampling.</p>
                            </div>

                            <div class="sampling-request-form__note">
                                Sampling pack berisi voice kit untuk banyak style. Setelah pembayaran, upload file N27 supaya admin connect voice kit pack ini ke keyboard.
                            </div>

                            <button type="submit">Beli Pack & Buka Pembayaran</button>
                        </form>

                        <div class="sampling-orders">
                            <div class="sampling-orders__header">
                                <div>
                                    <span>Customer N27 Workflow</span>
                                    <h2>Your Sampling Packs</h2>
                                </div>
                                <strong>{{ $samplingRequests->count() }} packs</strong>
                            </div>

                            @forelse($samplingRequests as $request)
                                @php
                                    $samplingPaymentAmount = $request->amount > 0
                                        ? $request->amount
                                        : \App\Models\StyleSampling::SAMPLING_REQUEST_PRICE;
                                    $samplingOrderOption = $request->pack_name
                                        ? \App\Models\StyleSampling::samplingRequestOption($request->pack_name)
                                        : null;
                                    $samplingOrderTitle = $samplingOrderOption['label'] ?? $request->product_name;
                                    $samplingOrderPack = $request->pack_name
                                        ? \App\Models\StyleSampling::normalizeSamplingPackName($request->pack_name)
                                        : 'Sampling Voice Pack';
                                @endphp
                                <article
                                    class="sampling-order-card"
                                    data-search-item
                                    data-search-text="{{ $request->order_reference }} {{ $request->product_name }} {{ $request->pack_name }} {{ $request->payment_status }} {{ $request->status }} {{ $request->customer_notes }}">
                                    <div class="sampling-order-card__head">
                                        <div>
                                            <span>{{ $request->order_reference }}</span>
                                            <h3>{{ $samplingOrderTitle }}</h3>
                                            <p>{{ $samplingOrderPack }}</p>
                                        </div>
                                        <strong class="sampling-status sampling-status--{{ $request->status_class }}">
                                            {{ $request->status }}
                                        </strong>
                                    </div>

                                    <div class="sampling-order-card__meta">
                                        <span>Payment: {{ $request->payment_status }}</span>
                                        <span>Rp {{ number_format($samplingPaymentAmount, 0, ',', '.') }}</span>
                                        @if($request->keyboard_storage_mb)
                                            <span>Keyboard: {{ $request->keyboard_storage_mb }} MB</span>
                                        @endif
                                        <span>{{ $request->created_at->format('d M Y') }}</span>
                                    </div>

                                    @if($request->customer_notes || $request->admin_notes)
                                        <div class="sampling-order-card__state">
                                            <div>
                                                <strong>{{ $request->customer_notes ? 'Catatan request' : 'Rekomendasi admin' }}</strong>
                                                <span>{{ $request->customer_notes ?: $request->admin_notes }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="sampling-order-card__steps">
                                        <span class="{{ $request->payment_status === \App\Models\SamplingRequest::PAYMENT_PAID ? 'is-done' : '' }}">Sampling Paid</span>
                                        <span class="{{ $request->has_n27_file ? 'is-done' : '' }}">N27 Uploaded</span>
                                        <span class="{{ $request->is_ready ? 'is-done' : '' }}">Ready Link</span>
                                    </div>

                                    @if($request->payment_status !== \App\Models\SamplingRequest::PAYMENT_PAID)
                                        <div class="sampling-order-card__payment">
                                            <div>
                                                <strong>Bayar via Midtrans untuk membuka upload N27</strong>
                                                <span>
                                                    Checkout sampling Rp {{ number_format($samplingPaymentAmount, 0, ',', '.') }} untuk order {{ $request->order_reference }}.
                                                    @if($request->payment)
                                                        Setelah menyelesaikan pembayaran sandbox, klik Cek Status Pembayaran agar form upload N27 muncul.
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="sampling-order-card__payment-actions">
                                                @if($request->payment)
                                                    <form action="{{ route('sampling-requests.payment.sync', $request) }}" method="POST">
                                                        @csrf
                                                        <button type="submit">Cek Status Pembayaran</button>
                                                    </form>
                                                @endif
                                                <button
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#samplingMidtransModal"
                                                    data-sampling-payment-action="checkout"
                                                    data-sampling-url="{{ route('sampling-requests.payment', $request) }}"
                                                    data-sampling-reference="{{ $request->order_reference }}"
                                                    data-sampling-product="{{ $samplingOrderTitle }}"
                                                    data-sampling-pack="{{ $samplingOrderPack }}"
                                                    data-sampling-amount="{{ $samplingPaymentAmount }}"
                                                    data-sampling-auto-open="{{ (string) $autoOpenPaymentRequestId === (string) $request->id ? '1' : '0' }}">
                                                    Bayar Rp {{ number_format($samplingPaymentAmount, 0, ',', '.') }}
                                                </button>
                                            </div>
                                        </div>
                                    @elseif(! $request->has_n27_file)
                                        <form class="sampling-order-card__upload" action="{{ route('sampling-requests.n27.upload', $request) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <label>
                                                Upload Yamaha N27 File
                                                <input type="file" name="n27_file" accept=".n27" required>
                                            </label>
                                            <button type="submit">Send N27 File</button>
                                        </form>
                                    @else
                                        <div class="sampling-order-card__state">
                                            <strong>{{ $request->n27_original_name }}</strong>
                                            <span>Uploaded {{ optional($request->n27_uploaded_at)->format('d M Y H:i') }}. Admin can now process it in Yamaha Expansion Manager.</span>
                                        </div>
                                    @endif

                                    @if($request->is_ready)
                                        <div class="sampling-order-card__delivery">
                                            <div>
                                                <strong>Completed sampling file is ready</strong>
                                                <span>{{ $request->delivery_notes ?: 'Open the Google Drive link to download your completed sampling file.' }}</span>
                                            </div>
                                            <a href="{{ $request->google_drive_link }}" target="_blank" rel="noopener">Open Drive Link</a>
                                        </div>
                                    @elseif($request->has_n27_file)
                                        <div class="sampling-order-card__delivery is-waiting">
                                            <div>
                                                <strong>Admin processing</strong>
                                                <span>The completed Google Drive link will appear here after export.</span>
                                            </div>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="style-page__empty">
                                    <strong>No sampling packs yet</strong>
                                    <span>Beli salah satu sampling pack dulu. Modal Midtrans terbuka setelah submit, lalu upload N27 aktif setelah pembayaran.</span>
                                </div>
                            @endforelse
                        </div>

                        <div class="modal fade sampling-midtrans-modal" id="samplingMidtransModal" tabindex="-1" aria-labelledby="samplingMidtransModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form class="modal-form" action="#" method="POST" data-sampling-midtrans-form>
                                        @csrf
                                        <div class="modal-header">
                                            <div>
                                                <span class="modal-eyebrow">Midtrans Checkout</span>
                                                <h2 class="modal-title" id="samplingMidtransModalLabel">Bayar Sampling Pack</h2>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <hr>
                                        <div class="modal-body">
                                            <p class="modal-context" data-sampling-midtrans-context>Sampling pack checkout</p>

                                            <div class="modal-summary sampling-midtrans-summary">
                                                <div>
                                                    <span>Order</span>
                                                    <strong data-sampling-midtrans-reference>-</strong>
                                                </div>
                                                <div>
                                                    <span>Total Bayar</span>
                                                    <strong data-sampling-midtrans-amount>Rp {{ number_format(\App\Models\StyleSampling::SAMPLING_REQUEST_PRICE, 0, ',', '.') }}</strong>
                                                </div>
                                            </div>

                                            <div class="sampling-midtrans-product">
                                                <div>
                                                    <span>Pack</span>
                                                    <strong data-sampling-midtrans-product>-</strong>
                                                </div>
                                                <p data-sampling-midtrans-pack>-</p>
                                            </div>

                                            <div class="sampling-midtrans-methods" aria-label="Midtrans payment methods">
                                                <span>QRIS</span>
                                                <span>Virtual Account</span>
                                                <span>E-Wallet</span>
                                                <span>Kartu Debit/Kredit</span>
                                            </div>

                                            <div class="modal-note sampling-midtrans-note">
                                                <div>
                                                    <span>Gateway</span>
                                                    <strong>Midtrans</strong>
                                                </div>
                                                <p>Pembayaran selesai akan membuka upload file .n27 untuk order sampling ini.</p>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="modal-footer">
                                            <button type="button" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit">Bayar via Midtrans</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid">
                            @forelse ($styles as $style)
                                @php
                                    $canDownloadStyle = $isSubscribed;
                                    $stylePackName = \App\Models\StyleSampling::samplingPackForCategory($style->category, $style->pack);
                                    $samplingOption = $stylePackName
                                        ? \App\Models\StyleSampling::samplingRequestOption($stylePackName)
                                        : null;
                                        $styleSearchText = implode(' ', array_filter([
                                            $style->name,
                                            $style->category,
                                            $stylePackName,
                                            $style->display_style_name,
                                            $samplingOption['label'] ?? null,
                                            $samplingOption['summary'] ?? null,
                                        ]));
                                @endphp
                                <article
                                    class="style-card {{ $canDownloadStyle ? '' : 'is-locked' }}"
                                    data-search-item
                                    data-search-text="{{ $styleSearchText }}">
                                    <div class="style-card__image">
                                        <img src="{{ $style->cover_src }}" alt="{{ $style->name }} cover">
                                        <div class="style-card__top">
                                            <span>{{ $style->category }}</span>
                                            @if(! $canDownloadStyle)
                                                <strong>
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                                                        <rect x="5" y="11" width="14" height="10" rx="2" />
                                                    </svg>
                                                    Subscription
                                                </strong>
                                            @endif
                                        </div>
                                        @if(! $canDownloadStyle)
                                            <div class="style-card__lock" aria-hidden="true">
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                                                    <rect x="5" y="11" width="14" height="10" rx="2" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="style-card__content">
                                        <div>
                                            <h2>{{ $style->name }}</h2>
                                            <p>{{ $stylePackName ?? 'Single Style' }}</p>
                                        </div>
                                        <div class="style-card__meta">
                                            <span>Published</span>
                                            <span>STY Download</span>
                                            <span>Subscription</span>
                                            @if($samplingOption)
                                                <span>{{ $samplingOption['short_label'] ?? $samplingOption['label'] }}</span>
                                            @endif
                                        </div>
                                        <div class="style-card__files">
                                            <div>
                                                <strong>STY</strong>
                                                <span>{{ $style->display_style_name }}</span>
                                            </div>
                                        </div>

                                        <div class="style-card__downloads style-card__downloads--single">
                                            @if($style->has_style_file && $canDownloadStyle)
                                                <a href="{{ route('stylesampling.download.style', $style) }}">Download STY</a>
                                            @elseif($style->has_style_file)
                                                <a class="style-card__subscribe" href="{{ route('subcription') }}">Unlock STY</a>
                                            @else
                                                <button type="button" disabled>STY Pending</button>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="style-page__empty">
                                    <strong>No styles found</strong>
                                    <span>Try choosing another style category from the sidebar.</span>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('script')
<script>
    document.querySelectorAll('[data-style-filter]').forEach((filter) => {
        filter.addEventListener('change', () => {
            if (filter.value) {
                window.location.href = filter.value;
            }
        });
    });

    document.querySelectorAll('[data-style-search-input]').forEach((input) => {
        const searchableItems = document.querySelectorAll('[data-search-item]');

        input.addEventListener('input', () => {
            const keyword = input.value.trim().toLowerCase();

            searchableItems.forEach((item) => {
                const haystack = (item.dataset.searchText || item.textContent || '').toLowerCase();
                item.hidden = keyword !== '' && !haystack.includes(keyword);
            });
        });
    });

    document.querySelectorAll('[data-sampling-checkout-form]').forEach((form) => {
        const packSelect = form.querySelector('select[name="pack_name"]');
        const priceTarget = form.querySelector('[data-sampling-preview-price]');
        const labelTarget = form.querySelector('[data-sampling-preview-label]');
        const summaryTarget = form.querySelector('[data-sampling-preview-summary]');
        const defaultSamplingPrice = {{ \App\Models\StyleSampling::SAMPLING_REQUEST_PRICE }};

        if (!packSelect || !priceTarget || !labelTarget || !summaryTarget) {
            return;
        }

        const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value).replace(/\s/g, ' ');

        const updateSamplingPaymentPreview = () => {
            const option = packSelect.options[packSelect.selectedIndex];
            const price = Number.parseInt(option?.dataset.price || `${defaultSamplingPrice}`, 10) || defaultSamplingPrice;
            const size = option?.dataset.size ? `${option.dataset.size} MB` : 'ukuran by request';

            labelTarget.textContent = option?.textContent?.split(' - ')[0]?.trim() || 'Sampling Payment';
            priceTarget.textContent = formatRupiah(price);
            summaryTarget.textContent = `${option.dataset.summary || 'Sampling pack siap dibayar.'} Setelah pembayaran, upload N27 agar voice kit bisa di-connect ke keyboard. Ukuran: ${size}.`;
        };

        packSelect.addEventListener('change', updateSamplingPaymentPreview);
        updateSamplingPaymentPreview();
    });

    (() => {
        const modalElement = document.getElementById('samplingMidtransModal');
        const checkoutButtons = document.querySelectorAll('[data-sampling-payment-action="checkout"]');

        if (!modalElement || !checkoutButtons.length) {
            return;
        }

        const form = modalElement.querySelector('[data-sampling-midtrans-form]');
        const context = modalElement.querySelector('[data-sampling-midtrans-context]');
        const reference = modalElement.querySelector('[data-sampling-midtrans-reference]');
        const amount = modalElement.querySelector('[data-sampling-midtrans-amount]');
        const product = modalElement.querySelector('[data-sampling-midtrans-product]');
        const pack = modalElement.querySelector('[data-sampling-midtrans-pack]');

        const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value).replace(/\s/g, ' ');

        const fillMidtransModal = (button) => {
            const total = Number.parseInt(button.dataset.samplingAmount || '0', 10);
            const orderReference = button.dataset.samplingReference || '-';
            const productName = button.dataset.samplingProduct || 'Sampling Pack';
            const packName = button.dataset.samplingPack || 'Sampling Voice Pack';

            form.action = button.dataset.samplingUrl || '#';
            context.textContent = `${orderReference} / ${productName}`;
            reference.textContent = orderReference;
            amount.textContent = formatRupiah(total);
            product.textContent = productName;
            pack.textContent = packName;
        };

        checkoutButtons.forEach((button) => {
            button.addEventListener('click', () => fillMidtransModal(button));
        });

        const autoOpenButton = Array.from(checkoutButtons).find((button) => button.dataset.samplingAutoOpen === '1');

        if (autoOpenButton && window.bootstrap) {
            window.addEventListener('load', () => {
                fillMidtransModal(autoOpenButton);
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            });
        }
    })();
</script>
@endpush
