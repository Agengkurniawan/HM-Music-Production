@extends('apps')

@section('content')
<section class="subcription">
    <div class="content-hall-first">
        @php
        $styleProducts = collect($styleProducts ?? []);
        $plans = $plans ?? [];
        $premiumPlan = $plans['premium_monthly'] ?? [
        'name' => 'Premium Monthly',
        'price' => \App\Models\SiteSetting::DEFAULT_SUBSCRIPTION_PRICE,
        'period_label' => '/bulan',
        ];
        $currentSubscription = $currentSubscription ?? null;
        $hasActiveSubscription = $hasActiveSubscription ?? false;
        $periodLabelIndonesia = match($premiumPlan['period_label'] ?? null) {
        '/90 days' => '/90 hari',
        '/year' => '/tahun',
        default => '/bulan',
        };
        $premiumActionLabel = auth()->check()
        ? ($hasActiveSubscription ? 'Perpanjang Paket' : 'Perbarui Akses STY')
        : 'Daftar atau Perbarui';
        @endphp

        @include('components.sidebar', [
        'hideLogout' => ! auth()->check(),
        'styleProducts' => $styleProducts,
        ])

        <div class="hall-second">
            @include('components.header')

            <div class="sectionprofil-6">
                <div class="pricing">
                    <div class="pricing-header">
                        <div>
                            <span>Paket Langganan</span>
                            <h2>Buka Akses Style Premium</h2>
                            <p>Lihat katalog style terlebih dahulu, lalu aktifkan subscription untuk mengunduh file STY. Sampling voice pack dibeli terpisah jika style membutuhkan voice kit yang sesuai.</p>
                        </div>
                        <div class="pricing-header__mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="M3 10h18" />
                                <path d="M7 15h4" />
                            </svg>
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="payment-alert">
                        <strong>{{ session('success') }}</strong>
                        @if(session('payment_reference'))
                        <span>Referensi pembayaran: {{ session('payment_reference') }}</span>
                        @endif
                    </div>
                    @endif

                    @if($errors->getBag('default')->any() || $errors->subscriptionAccess->any())
                    <div class="payment-alert payment-alert--error">
                        <strong>{{ $errors->subscriptionAccess->first() ?: $errors->getBag('default')->first() }}</strong>
                    </div>
                    @endif

                    <div class="pricing-wrapper">
                        <div class="plan-card free">
                            <div class="plan-top">
                                <div class="plan-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 18V5l12-2v13" />
                                        <circle cx="6" cy="18" r="3" />
                                        <circle cx="18" cy="16" r="3" />
                                    </svg>
                                </div>
                                <div>
                                    <h3>Gratis</h3>
                                    <span>selamanya</span>
                                </div>
                            </div>

                            <div class="price">Rp 0</div>
                            <p class="desc">Cocok untuk melihat katalog dan mendengarkan audio demo.</p>

                            <ul class="features">
                                <li class="active">Putar audio demo</li>
                                <li class="active">Lihat katalog style</li>
                                <li>Unduh file STY</li>
                                <li>Pembelian sampling voice pack</li>
                                <li>Dukungan prioritas</li>
                            </ul>

                            <button class="current-btn" type="button">Paket Saat Ini</button>
                        </div>

                        <div class="plan-card premium">
                            <span class="badge">Paling Populer</span>

                            <div class="plan-top">
                                <div class="plan-icon premium-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 2l3 7 7 .5-5.4 4.7 1.7 6.8L12 17.3 5.7 21l1.7-6.8L2 9.5 9 9z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3>Premium</h3>
                                    <span>{{ $periodLabelIndonesia }}</span>
                                </div>
                            </div>

                            <div class="price">Rp {{ number_format((int) $premiumPlan['price'], 0, ',', '.') }}<span>{{ $periodLabelIndonesia }}</span></div>
                            <p class="desc">Buka akses download style premium. Sampling voice pack tetap dibeli terpisah untuk setiap pack.</p>

                            @if(auth()->check())
                            <div class="plan-card__status {{ $hasActiveSubscription ? 'is-active' : 'is-expired' }}">
                                <strong>{{ $hasActiveSubscription ? 'Premium Aktif' : 'Akses STY Terkunci' }}</strong>
                                <span>
                                    @if($currentSubscription?->expires_at)
                                    {{ $hasActiveSubscription ? 'Aktif sampai' : 'Berakhir pada' }}
                                    {{ $currentSubscription->expires_at->format('d/m/Y') }}
                                    @else
                                    {{ $hasActiveSubscription ? 'Tanpa tanggal berakhir' : 'Perbarui dengan email yang terdaftar' }}
                                    @endif
                                </span>
                            </div>
                            @endif

                            <ul class="features">
                                <li class="active">Putar audio demo</li>
                                <li class="active">Lihat katalog style</li>
                                <li class="active">Unduh file STY</li>
                                <li>Pembelian sampling voice pack</li>
                                <li class="active">Dukungan prioritas</li>
                            </ul>

                            <button class="subscribe-btn"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#subscriptionPaymentModal">
                                {{ $premiumActionLabel }}
                            </button>
                        </div>
                    </div>

                    <section class="subscription-benefits">
                        <article>
                            <strong>Katalog Unduhan STY</strong>
                            <p>Customer dapat melihat katalog, kemudian mengunduh STY setelah subscription aktif.</p>
                        </article>

                        <article>
                            <strong>File STY</strong>
                            <p>Akses Premium membuka file style keyboard untuk diunduh.</p>
                        </article>

                        <article>
                            <strong>N27 Sampling</strong>
                            <p>Beli sampling voice pack yang dipakai style. Harga Rp {{ number_format(\App\Models\StyleSampling::SAMPLING_REQUEST_PRICE, 0, ',', '.') }} per pack, lalu upload N27 setelah pembayaran berhasil.</p>
                        </article>
                    </section>
                </div>
            </div>
        </div>
    </div>

</section>
@endsection

@push('script')
@if($errors->subscriptionCheckout->any())
<script>
    window.addEventListener('load', () => {
        const modalElement = document.getElementById('subscriptionPaymentModal');

        if (modalElement && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    });
</script>
@endif
@endpush