@extends('apps')

@section('content')
<section class="content">
    <div class="content-hall-first">
        @include('components.admin-sidebar')

        <div class="hall-second">
            @include('components.admin-header')

            @php
                $settings = $settings ?? \App\Models\SiteSetting::values();
                $subscriptionPrice = (int) ($settings['subscription_price'] ?? 0);
            @endphp

            <section class="admin-setting">
                @if(session('success'))
                    <div class="setting-alert setting-alert--success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="setting-alert setting-alert--error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <header class="admin-setting__header">
                    <div>
                        <h1>Admin Settings</h1>
                        <p>Update homepage content, subscription pricing, payment gateway, social links, and SMTP mail.</p>
                    </div>
                </header>

                <form class="setting-grid" action="{{ route('admin.setting.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <section class="setting-card setting-card--wide">
                        <div class="setting-card__header">
                            <span class="setting-card__badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                Content
                            </span>
                            <h2>Homepage copy and display assets</h2>
                        </div>

                        <label>
                            Homepage Banner Title
                            <input type="text" name="banner_title" value="{{ old('banner_title', $settings['banner_title']) }}">
                        </label>

                        <label>
                            Homepage Banner
                            <input type="file" name="homepage_banner" accept="image/*">
                            @if(! empty($settings['homepage_banner']))
                                <small>Current: {{ basename($settings['homepage_banner']) }}</small>
                            @endif
                        </label>

                        <label>
                            Upload Logo
                            <input type="file" name="logo" accept="image/*">
                            @if(! empty($settings['logo']))
                                <small>Current: {{ basename($settings['logo']) }}</small>
                            @endif
                        </label>
                    </section>

                    <section class="setting-card">
                        <div class="setting-card__header">
                            <span class="setting-card__badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 1.5l3 6 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.1 19.9l1.1-6.5L2.5 8.4 9 7.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                </svg>
                                Subscription
                            </span>
                            <h2>Price and plan duration</h2>
                        </div>

                        <label>
                            Subscription Price
                            <input type="text" name="subscription_price" value="{{ old('subscription_price', 'Rp '.number_format($subscriptionPrice, 0, ',', '.')) }}">
                        </label>

                        <label>
                            Plan Duration
                            <select name="plan_duration">
                                @foreach(['30 Days', '90 Days', '1 Year'] as $duration)
                                    <option value="{{ $duration }}" @selected(old('plan_duration', $settings['plan_duration']) === $duration)>{{ $duration }}</option>
                                @endforeach
                            </select>
                        </label>
                    </section>

                    <section class="setting-card">
                        <div class="setting-card__header">
                            <span class="setting-card__badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 7h18M3 12h18M3 17h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M7 7v10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                Payment Gateway
                            </span>
                            <h2>Transaction provider setup</h2>
                        </div>

                        <label>
                            Provider
                            <select name="payment_gateway">
                                @foreach(['Midtrans', 'Xendit', 'Manual Bank Transfer'] as $gateway)
                                    <option value="{{ $gateway }}" @selected(old('payment_gateway', $settings['payment_gateway']) === $gateway)>{{ $gateway }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            Merchant Key
                            <input type="password" name="merchant_key" value="{{ old('merchant_key', $settings['merchant_key']) }}">
                        </label>
                    </section>

                    <section class="setting-card">
                        <div class="setting-card__header">
                            <span class="setting-card__badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2.5c5 0 9 4 9 9s-4 9-9 9-9-4-9-9 4-9 9-9z" fill="none" stroke="currentColor" stroke-width="2" />
                                    <path d="M8.5 12.2l2.2 2.2 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Social Media
                            </span>
                            <h2>Public profile links</h2>
                        </div>

                        <label>
                            Instagram
                            <input type="url" name="instagram" value="{{ old('instagram', $settings['instagram']) }}">
                        </label>

                        <label>
                            YouTube
                            <input type="url" name="youtube" value="{{ old('youtube', $settings['youtube']) }}">
                        </label>
                    </section>

                    <section class="setting-card">
                        <div class="setting-card__header">
                            <span class="setting-card__badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="2" />
                                    <path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                </svg>
                                SMTP Mail
                            </span>
                            <h2>Email delivery configuration</h2>
                        </div>

                        <label>
                            SMTP Host
                            <input type="text" name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host']) }}">
                        </label>

                        <div class="setting-card__split">
                            <label>
                                Port
                                <input type="number" name="smtp_port" value="{{ old('smtp_port', $settings['smtp_port']) }}">
                            </label>

                            <label>
                                Encryption
                                <select name="smtp_encryption">
                                    @foreach(['TLS', 'SSL', 'None'] as $encryption)
                                        <option value="{{ $encryption }}" @selected(old('smtp_encryption', $settings['smtp_encryption']) === $encryption)>{{ $encryption }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </section>

                    <div class="setting-actions">
                        <button type="submit" class="btn btn--primary">
                            Save Changes
                        </button>
                        <button type="reset" class="btn btn--ghost">
                            Reset
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</section>

@endsection
