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
                'period_label' => '/month',
            ];
            $currentSubscription = $currentSubscription ?? null;
            $hasActiveSubscription = $hasActiveSubscription ?? false;
            $premiumActionLabel = auth()->check()
                ? ($hasActiveSubscription ? 'Extend Plan' : 'Renew STY Access')
                : 'Register or Renew';
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
                        <span>Subscription Plan</span>
                        <h2>Unlock Premium STY Styles</h2>
                        <p>Browse the style catalog first, then subscribe to unlock STY downloads. Style playback is not opened for customers; sampling voice packs are bought separately when a style needs the matching voice kit.</p>
                    </div>

                    @if(session('success'))
                        <div class="payment-alert">
                            <strong>{{ session('success') }}</strong>
                            @if(session('payment_reference'))
                                <span>Payment reference: {{ session('payment_reference') }}</span>
                            @endif
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="payment-alert payment-alert--error">
                            <strong>{{ $errors->first() }}</strong>
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
                                    <h3>Free</h3>
                                    <span>forever</span>
                                </div>
                            </div>

                            <div class="price">Rp 0</div>
                            <p class="desc">Perfect for browsing the catalog and listening to regular demo audio.</p>

                            <ul class="features">
                                <li class="active">Play demo audio</li>
                                <li class="active">Browse style catalog</li>
                                <li>Download STY files</li>
                                <li>Sampling voice pack payment</li>
                                <li>Priority support</li>
                            </ul>

                            <button class="current-btn" type="button">Current Plan</button>
                        </div>

                        <div class="plan-card premium">
                            <span class="badge">Most Popular</span>

                            <div class="plan-top">
                                <div class="plan-icon premium-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 2l3 7 7 .5-5.4 4.7 1.7 6.8L12 17.3 5.7 21l1.7-6.8L2 9.5 9 9z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3>Premium</h3>
                                    <span>{{ $premiumPlan['period_label'] }}</span>
                                </div>
                            </div>

                            <div class="price">Rp {{ number_format((int) $premiumPlan['price'], 0, ',', '.') }}<span>{{ $premiumPlan['period_label'] }}</span></div>
                            <p class="desc">Unlock premium style downloads. Sampling voice packs remain separate per pack.</p>

                            @if(auth()->check())
                                <div class="plan-card__status {{ $hasActiveSubscription ? 'is-active' : 'is-expired' }}">
                                    <strong>{{ $hasActiveSubscription ? 'Premium Active' : 'STY Access Locked' }}</strong>
                                    <span>
                                        @if($currentSubscription?->expires_at)
                                            {{ $hasActiveSubscription ? 'Active until' : 'Expired on' }}
                                            {{ $currentSubscription->expires_at->format('d M Y') }}
                                        @else
                                            {{ $hasActiveSubscription ? 'No expiry date' : 'Renew with your registered email' }}
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <ul class="features">
                                <li class="active">Play demo audio</li>
                                <li class="active">Browse style catalog</li>
                                <li class="active">Download STY files</li>
                                <li>Sampling voice pack payment</li>
                                <li class="active">Priority support</li>
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
                            <strong>Download-Only Catalog</strong>
                            <p>Customers can browse styles, then download STY after subscription is active.</p>
                        </article>

                        <article>
                            <strong>STY File</strong>
                            <p>Premium access unlocks the keyboard style file for download.</p>
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
@if($errors->any())
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
