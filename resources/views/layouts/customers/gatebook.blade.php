@extends('apps')

@section('content')
<section class="content gatebook-layout">
    <div class="content-hall-first">
        @include('components.sidebar', ['hideLogout' => ! auth()->check()])

        <div class="hall-second">
            @include('components.header')

            <main class="gatebook">
                @php
                    $gatebookSettings = \App\Models\SiteSetting::values();
                    $subscriptionPrice = (int) ($gatebookSettings['subscription_price'] ?? \App\Models\SiteSetting::DEFAULT_SUBSCRIPTION_PRICE);
                    $planDuration = $gatebookSettings['plan_duration'] ?? '30 Days';
                @endphp
                <section class="gatebook-hero">
                    <div>
                        <span class="gatebook-kicker">Panduan Customer</span>
                        <h1>Kenali alur HM Music dengan mudah.</h1>
                        <p>Ikuti panduan singkat ini untuk mendengarkan demo, mengunduh style, berlangganan, dan memesan sampling voice.</p>
                    </div>
                    <div class="gatebook-hero__mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2zM8 7h8M8 11h6"/></svg>
                    </div>
                </section>

                <nav class="gatebook-shortcuts" aria-label="Pintasan panduan">
                    <a href="#mulai">Mulai dari sini</a>
                    <a href="#alur-utama">Alur lengkap</a>
                    <a href="#style">Download style</a>
                    <a href="#sampling">Pesan sampling</a>
                    <a href="#status">Arti status</a>
                    <a href="#akses">Hak akses</a>
                </nav>

                <section class="gatebook-section start-guide" id="mulai">
                    <div class="gatebook-heading">
                        <span>00</span>
                        <div><h2>Mulai dari sini</h2><p>Pilih kondisi Anda sekarang, lalu ikuti langkah berikutnya.</p></div>
                    </div>
                    <div class="start-guide__choices">
                        <article>
                            <span>Belum punya akun</span>
                            <h3>Jelajahi dahulu tanpa login</h3>
                            <p>Dengarkan Demo, baca Gatebook, lalu buka Subscription ketika sudah siap membuat akun dan mengaktifkan akses.</p>
                            <a href="{{ route('demo') }}">Dengarkan demo</a>
                        </article>
                        <article>
                            <span>Sudah punya akun Free</span>
                            <h3>Aktifkan Premium</h3>
                            <p>Masuk memakai email yang sama, pilih paket Premium, lalu selesaikan pembayaran untuk membuka download STY.</p>
                            <a href="{{ route('subcription', ['plan' => 'premium_monthly']) }}">Lihat subscription</a>
                        </article>
                        <article>
                            <span>Sudah Premium</span>
                            <h3>Gunakan akses penuh</h3>
                            <p>Download style selama masa aktif. Sampling N27 tersedia sebagai pembelian terpisah untuk setiap pack.</p>
                            <a href="{{ auth()->check() ? route('stylesampling') : route('login') }}">{{ auth()->check() ? 'Buka katalog' : 'Login ke akun' }}</a>
                        </article>
                    </div>
                    <div class="gatebook-note">
                        <strong>Yang perlu disiapkan:</strong>
                        <span>Email aktif, password minimal 8 karakter, nomor WhatsApp, metode pembayaran Midtrans, dan file .n27 khusus jika memesan sampling.</span>
                    </div>
                </section>

                <section class="gatebook-section" id="alur-utama">
                    <div class="gatebook-heading">
                        <span>01</span>
                        <div><h2>Alur lengkap customer baru</h2><p>Dari pengunjung sampai seluruh layanan dapat digunakan.</p></div>
                    </div>
                    <div class="journey-list">
                        <article><b>1</b><div><span>Tanpa login</span><h3>Kenali produk melalui Demo</h3><p>Buka menu Demo dan putar contoh musik. Pada tahap ini Anda belum perlu membuat akun atau membayar.</p></div><a href="{{ route('demo') }}">Buka Demo</a></article>
                        <article><b>2</b><div><span>Pendaftaran</span><h3>Buka halaman Subscription</h3><p>Pilih paket Premium seharga <strong>Rp {{ number_format($subscriptionPrice, 0, ',', '.') }}</strong> untuk masa aktif <strong>{{ $planDuration }}</strong>, kemudian tekan tombol berlangganan.</p></div><a href="{{ route('subcription') }}">Pilih paket</a></article>
                        <article><b>3</b><div><span>Data akun</span><h3>Lengkapi formulir checkout</h3><p>Isi nama, email aktif, nomor telepon, password dan konfirmasi password. Email dan password ini akan digunakan untuk login berikutnya.</p></div></article>
                        <article><b>4</b><div><span>Midtrans</span><h3>Selesaikan pembayaran</h3><p>Pilih metode pembayaran yang tersedia di halaman Midtrans. Jangan menutup proses sebelum status transaksi ditampilkan.</p></div></article>
                        <article><b>5</b><div><span>Aktivasi otomatis</span><h3>Tunggu pembayaran terkonfirmasi</h3><p>Jika berhasil, akun otomatis login dan subscription menjadi aktif. Jika masih Pending, tunggu konfirmasi Midtrans sebelum mencoba kembali.</p></div></article>
                        <article><b>6</b><div><span>Akun customer</span><h3>Masuk dan periksa Dashboard</h3><p>Gunakan email serta password yang didaftarkan. Dashboard menampilkan paket, masa akses, katalog, dan aktivitas akun.</p></div><a href="{{ route('login') }}">Halaman Login</a></article>
                        <article><b>7</b><div><span>Premium STY</span><h3>Pilih dan download style</h3><p>Buka Style Sampling → Style, cari berdasarkan kategori atau pack, lalu download file STY selama subscription aktif.</p></div></article>
                        <article><b>8</b><div><span>Layanan terpisah</span><h3>Request sampling bila dibutuhkan</h3><p>Pilih pack sampling, bayar per request, upload file N27, lalu tunggu admin mengirimkan hasil melalui Google Drive.</p></div></article>
                    </div>
                </section>

                <div class="gatebook-paths">
                    <section class="gatebook-section path-card" id="style">
                        <div class="path-card__icon path-card__icon--blue"><svg viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></div>
                        <span class="path-card__label">Jalur A</span>
                        <h2>Download Style STY</h2>
                        <ol>
                            <li>Buka <strong>Style Sampling</strong>, lalu pilih bagian Style.</li>
                            <li>Gunakan kategori atau pack untuk menemukan style.</li>
                            <li>Pastikan subscription Anda aktif.</li>
                            <li>Tekan tombol download dan simpan file STY.</li>
                        </ol>
                        <a href="{{ route('stylesampling', ['type' => 'style']) }}">Lihat katalog style <span>→</span></a>
                    </section>

                    <section class="gatebook-section path-card" id="sampling">
                        <div class="path-card__icon path-card__icon--violet"><svg viewBox="0 0 24 24"><path d="M3 14v-2a9 9 0 0 1 18 0v2M5 14h4v7H5zM15 14h4v7h-4z"/></svg></div>
                        <span class="path-card__label">Jalur B</span>
                        <h2>Request Sampling N27</h2>
                        <ol>
                            <li>Pilih paket sampling dan isi catatan kebutuhan.</li>
                            <li>Kirim request, lalu selesaikan pembayaran.</li>
                            <li>Upload file <strong>.n27</strong> keyboard Anda.</li>
                            <li>Admin memproses file melalui Yamaha Expansion Manager.</li>
                            <li>Saat selesai, buka link Google Drive yang diberikan admin.</li>
                        </ol>
                        <a href="{{ route('stylesampling', ['type' => 'sampling']) }}">Mulai request sampling <span>→</span></a>
                    </section>
                </div>

                <section class="gatebook-section" id="status">
                    <div class="gatebook-heading">
                        <span>02</span>
                        <div><h2>Pahami status request</h2><p>Cek status ini pada riwayat sampling Anda.</p></div>
                    </div>
                    <div class="status-guide">
                        <article><i class="status-dot status-dot--amber"></i><div><h3>Menunggu Pembayaran</h3><p>Request tercatat, tetapi pembayaran belum berhasil dikonfirmasi.</p></div></article>
                        <article><i class="status-dot status-dot--blue"></i><div><h3>Menunggu File N27</h3><p>Pembayaran selesai. Upload file N27 agar admin dapat mulai bekerja.</p></div></article>
                        <article><i class="status-dot status-dot--violet"></i><div><h3>Diproses</h3><p>File sedang dikerjakan oleh admin. Pantau notifikasi untuk pembaruan.</p></div></article>
                        <article><i class="status-dot status-dot--green"></i><div><h3>Selesai</h3><p>Hasil sudah tersedia dan dapat diunduh melalui link Google Drive.</p></div></article>
                    </div>
                </section>

                <section class="gatebook-section" id="akses">
                    <div class="gatebook-heading">
                        <span>03</span>
                        <div><h2>Apa yang dapat diakses?</h2><p>Perbedaan akses sebelum login, akun Free, dan Premium.</p></div>
                    </div>
                    <div class="access-table" role="table" aria-label="Perbandingan hak akses customer">
                        <div class="access-table__row access-table__head" role="row"><span>Fitur</span><span>Belum login</span><span>Akun Free</span><span>Premium aktif</span></div>
                        <div class="access-table__row" role="row"><strong>Demo musik</strong><span class="yes">Bisa</span><span class="yes">Bisa</span><span class="yes">Bisa</span></div>
                        <div class="access-table__row" role="row"><strong>Gatebook & Subscription</strong><span class="yes">Bisa</span><span class="yes">Bisa</span><span class="yes">Bisa</span></div>
                        <div class="access-table__row" role="row"><strong>Dashboard customer</strong><span class="no">Belum</span><span class="yes">Bisa</span><span class="yes">Bisa</span></div>
                        <div class="access-table__row" role="row"><strong>Download Style STY</strong><span class="no">Belum</span><span class="no">Terkunci</span><span class="yes">Terbuka</span></div>
                        <div class="access-table__row" role="row"><strong>Request Sampling N27</strong><span class="no">Harus login</span><span class="yes">Bayar per pack</span><span class="yes">Bayar per pack</span></div>
                    </div>
                    <p class="access-caption">Subscription Premium membuka download Style STY. Biaya request Sampling N27 tidak termasuk subscription dan dibayar terpisah untuk setiap pack.</p>
                </section>

                <section class="gatebook-section after-access">
                    <div class="gatebook-heading">
                        <span>04</span>
                        <div><h2>Setelah akses penuh aktif</h2><p>Hal yang perlu dilakukan agar akun tetap lancar digunakan.</p></div>
                    </div>
                    <div class="after-access__grid">
                        <article><h3>Pantau notifikasi</h3><p>Cek ikon notifikasi untuk status pembayaran, upload N27, proses admin, dan hasil sampling.</p></article>
                        <article><h3>Perhatikan masa aktif</h3><p>Perpanjang subscription sebelum berakhir agar tombol download style tidak kembali terkunci.</p></article>
                        <article><h3>Simpan referensi order</h3><p>Nomor HM-SUB atau HM-SMP membantu admin menemukan transaksi Anda dengan cepat.</p></article>
                        <article><h3>Gunakan akun yang sama</h3><p>Saat memperpanjang paket, gunakan email dan password akun customer yang sudah terdaftar.</p></article>
                    </div>
                </section>

                <aside class="gatebook-help">
                    <div><strong>Masih butuh bantuan?</strong><p>Siapkan nomor referensi order agar tim HM Music dapat membantu lebih cepat.</p></div>
                    <a href="https://wa.me/6282359511922?text={{ urlencode('Halo HM Music Production, saya membutuhkan bantuan mengenai alur layanan HM Music.') }}" target="_blank" rel="noopener noreferrer">Hubungi WhatsApp</a>
                </aside>
            </main>
        </div>
    </div>
</section>
@endsection
