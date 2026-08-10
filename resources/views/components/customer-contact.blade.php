<footer class="customer-contact {{ request()->routeIs('login', 'password.*') ? 'customer-contact--auth' : 'customer-contact--customer' }}" aria-labelledby="customer-contact-title">
    <div class="customer-contact__frame">
        <div class="customer-contact__inner">
            <div class="customer-contact__copy">
                <div class="customer-contact__heading">
                    <h2 id="customer-contact-title">Butuh bantuan?</h2>
                    <span><i aria-hidden="true"></i> Customer support</span>
                </div>
                <p>Bingung soal pembelian, pembayaran, download style, atau sampling N27? Hubungi kami.</p>
            </div>

            <div class="customer-contact__actions">
                <a
                    class="customer-contact__link customer-contact__link--whatsapp"
                    href="https://wa.me/6282359511922?text={{ urlencode('Halo HM Music Production, saya ingin bertanya mengenai produk atau layanan HM Music.') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Hubungi HM Music Production melalui WhatsApp di 0823-5951-1922">
                    <span class="customer-contact__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M20.52 3.48A11.82 11.82 0 0 0 12.07 0C5.5 0 .16 5.35.16 11.92c0 2.1.55 4.15 1.6 5.95L.06 24l6.28-1.65a11.9 11.9 0 0 0 5.72 1.46h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.23-6.17-3.47-8.41Zm-8.45 18.32h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.73.98 1-3.63-.24-.37a9.84 9.84 0 0 1-1.52-5.27c0-5.46 4.44-9.9 9.91-9.9a9.83 9.83 0 0 1 7 2.9 9.84 9.84 0 0 1 2.9 7c0 5.45-4.45 9.88-9.9 9.88Zm5.43-7.42c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47a8.94 8.94 0 0 1-1.65-2.05c-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.59-.49-.51-.67-.52h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2-1.41.25-.7.25-1.3.18-1.42-.08-.12-.28-.2-.58-.35Z" />
                        </svg>
                    </span>
                    <span><small>WhatsApp</small><strong>0823-5951-1922</strong></span>
                    <svg class="customer-contact__arrow" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M11.25 3.75a.83.83 0 0 0 0 1.18l3.4 3.4H3.33a.83.83 0 1 0 0 1.67h11.32l-3.4 3.4a.83.83 0 1 0 1.18 1.18l4.82-4.82a.83.83 0 0 0 0-1.18l-4.82-4.83a.83.83 0 0 0-1.18 0Z" />
                    </svg>
                </a>

                <a
                    class="customer-contact__link customer-contact__link--instagram"
                    href="https://instagram.com/hmmusicpro"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Lihat Instagram HM Music Production di hmmusicpro">
                    <span class="customer-contact__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M7.75 2h8.5A5.76 5.76 0 0 1 22 7.75v8.5A5.76 5.76 0 0 1 16.25 22h-8.5A5.76 5.76 0 0 1 2 16.25v-8.5A5.76 5.76 0 0 1 7.75 2Zm0 2A3.75 3.75 0 0 0 4 7.75v8.5A3.75 3.75 0 0 0 7.75 20h8.5A3.75 3.75 0 0 0 20 16.25v-8.5A3.75 3.75 0 0 0 16.25 4h-8.5Zm8.75 1.5a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z" />
                        </svg>
                    </span>
                    <span><small>Instagram</small><strong>@hmmusicpro</strong></span>
                    <svg class="customer-contact__arrow" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M11.25 3.75a.83.83 0 0 0 0 1.18l3.4 3.4H3.33a.83.83 0 1 0 0 1.67h11.32l-3.4 3.4a.83.83 0 1 0 1.18 1.18l4.82-4.82a.83.83 0 0 0 0-1.18l-4.82-4.83a.83.83 0 0 0-1.18 0Z" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="customer-contact__meta">
            <span>&copy; {{ now()->year }} HM Music Production | Style Library <i></i> Demo Audio <i></i> Sampling Extension</span>
        </div>
    </div>
</footer>
