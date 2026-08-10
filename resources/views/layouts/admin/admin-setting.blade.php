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
                        <h1>Setting Website</h1>
                        <p>Atur harga subscription dan koneksi pembayaran Midtrans.</p>
                    </div>
                </header>

                <form class="setting-grid" action="{{ route('admin.setting.update') }}" method="POST">
                    @csrf

                    <section class="setting-card">
                        <div class="setting-card__header">
                            <span class="setting-card__badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 1.5l3 6 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.1 19.9l1.1-6.5L2.5 8.4 9 7.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                </svg>
                                Subscription
                            </span>
                            <h2>Paket Premium</h2>
                        </div>

                        <label>
                            Harga Subscription
                            <input type="text" name="subscription_price" value="{{ old('subscription_price', 'Rp '.number_format($subscriptionPrice, 0, ',', '.')) }}">
                        </label>

                        <label>
                            Masa Aktif
                            <select name="plan_duration">
                                @foreach(['30 Days', '90 Days', '1 Year'] as $duration)
                                    <option value="{{ $duration }}" @selected(old('plan_duration', $settings['plan_duration']) === $duration)>
                                        {{ match($duration) {
                                            '90 Days' => '90 Hari',
                                            '1 Year' => '1 Tahun',
                                            default => '30 Hari',
                                        } }}
                                    </option>
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
                                Midtrans
                            </span>
                            <h2>Koneksi Pembayaran</h2>
                        </div>

                        <label>
                            Mode
                            <select name="midtrans_is_production">
                                <option value="0" @selected(old('midtrans_is_production', $settings['midtrans_is_production']) === '0')>Sandbox (digunakan sekarang)</option>
                                <option value="1" @selected(old('midtrans_is_production', $settings['midtrans_is_production']) === '1')>Production</option>
                            </select>
                        </label>

                        <label>
                            Midtrans Server Key
                            <input type="password" name="midtrans_server_key" value="{{ old('midtrans_server_key', $settings['midtrans_server_key'] ?: $settings['merchant_key']) }}" placeholder="SB-Mid-server-...">
                            <small>Dipakai untuk membuat transaksi dan cek status pembayaran.</small>
                        </label>

                        <label>
                            Midtrans Client Key
                            <input type="password" name="midtrans_client_key" value="{{ old('midtrans_client_key', $settings['midtrans_client_key']) }}" placeholder="SB-Mid-client-...">
                            <small>Dipakai untuk validasi konfigurasi Midtrans.</small>
                        </label>
                    </section>

                    <div class="setting-actions">
                        <button type="submit" class="btn btn--primary">
                            Simpan Setting
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
