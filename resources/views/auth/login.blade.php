@extends('apps')

@section('content')
<main class="login-page">
    <section class="login-page__shell" aria-labelledby="login-title">
        <aside class="login-showcase" aria-label="HM Music Production studio preview">
            <div class="login-showcase__brand">
                <div class="login-page__mark" aria-hidden="true">
                    <svg viewBox="0 0 44 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 41.6821L14.4214 34.1035C9.06096 28.7431 0.00366211 32.5324 0.00366211 40.1109V50H43.9963V19.6858L22 41.6821Z" fill="currentColor" />
                        <path d="M22 8.31793L29.5785 15.8965C34.939 21.2569 43.9963 17.4677 43.9963 9.88909V0H0.00366211V30.3142L22 8.31793Z" fill="currentColor" />
                    </svg>
                </div>
                <div>
                    <span>HM Music Production</span>
                    <strong>Studio Access</strong>
                </div>
            </div>

            <div class="login-showcase__headline">
                <p>Producer Workspace</p>
                <h2>Keep your STY library, demos, and sampling orders in rhythm.</h2>
            </div>

            <div class="login-showcase__board" aria-hidden="true">
                <div class="login-showcase__session">
                    <div class="login-showcase__session-top">
                        <span>Session Deck</span>
                        <strong>STY-27</strong>
                    </div>
                    <div class="login-showcase__wave">
                        <span style="--height: 42%"></span>
                        <span style="--height: 72%"></span>
                        <span style="--height: 55%"></span>
                        <span style="--height: 88%"></span>
                        <span style="--height: 48%"></span>
                        <span style="--height: 68%"></span>
                        <span style="--height: 36%"></span>
                        <span style="--height: 78%"></span>
                        <span style="--height: 52%"></span>
                        <span style="--height: 64%"></span>
                    </div>
                    <div class="login-showcase__timeline">
                        <span></span>
                    </div>
                </div>

                <div class="login-showcase__mixer">
                    <div>
                        <span></span>
                        <strong></strong>
                    </div>
                    <div>
                        <span></span>
                        <strong></strong>
                    </div>
                    <div>
                        <span></span>
                        <strong></strong>
                    </div>
                </div>
            </div>

            <div class="login-showcase__stats">
                <div>
                    <span>STY</span>
                    <strong>Library</strong>
                </div>
                <div>
                    <span>Demo</span>
                    <strong>Preview</strong>
                </div>
                <div>
                    <span>N27</span>
                    <strong>Sampling</strong>
                </div>
            </div>
        </aside>

        <section class="login-page__auth">
            <div class="login-page__brand">
                <div class="login-page__mark" aria-hidden="true">
                    <svg viewBox="0 0 44 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 41.6821L14.4214 34.1035C9.06096 28.7431 0.00366211 32.5324 0.00366211 40.1109V50H43.9963V19.6858L22 41.6821Z" fill="currentColor" />
                        <path d="M22 8.31793L29.5785 15.8965C34.939 21.2569 43.9963 17.4677 43.9963 9.88909V0H0.00366211V30.3142L22 8.31793Z" fill="currentColor" />
                    </svg>
                </div>
                <div>
                    <span>HM Music Production</span>
                    <strong>Studio Access</strong>
                </div>
            </div>

            <div class="login-page__intro">
                <p class="login-page__eyebrow">Secure studio login</p>
                <h1 id="login-title">Welcome Back</h1>
                <p>Continue managing STY downloads, demo playlists, and paid sampling requests.</p>
            </div>

            <form class="login-form" action="{{ route('login.attempt') }}" method="POST">
                @csrf
                @if (session('success'))
                    <p class="login-form__success">{{ session('success') }}</p>
                @endif

                <label class="login-form__field" for="email">
                    <span>Email</span>
                    <span class="login-form__control">
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.82018 17.9143C2.44885 17.7711 1.34417 16.6906 1.17405 15.3223C0.960224 13.6026 0.746338 11.8227 0.746338 10C0.746338 8.65884 0.862151 7.3408 1.00853 6.05282C1.04001 5.77584 1.36334 5.64232 1.58224 5.81492L7.67824 10.6214C9.03995 11.6951 10.9602 11.6951 12.3219 10.6214L18.4178 5.81504C18.6367 5.64244 18.96 5.77595 18.9915 6.05294C19.1378 7.34087 19.2537 8.65887 19.2537 10C19.2537 11.8227 19.0398 13.6026 18.826 15.3223C18.6558 16.6906 17.5511 17.7711 16.1798 17.9143C16.1163 17.921 16.0527 17.9276 15.9891 17.9343C14.0548 18.1366 12.0517 18.346 10 18.346C7.94828 18.346 5.94525 18.1366 4.01098 17.9343C3.94731 17.9276 3.88371 17.921 3.82018 17.9143ZM18.2575 3.26967C18.3648 3.41325 18.3264 3.61304 18.1857 3.72405L11.2163 9.21915C10.503 9.78155 9.49715 9.78155 8.78388 9.21915L1.81444 3.72398C1.67362 3.61295 1.63518 3.41317 1.74259 3.26958C2.22879 2.61967 2.97368 2.17412 3.82018 2.08571C3.88369 2.07907 3.94728 2.07242 4.01095 2.06577C5.94524 1.8635 7.94827 1.65405 10 1.65405C12.0517 1.65405 14.0548 1.8635 15.9891 2.06577C16.0527 2.07242 16.1163 2.07907 16.1798 2.08571C17.0264 2.17412 17.7713 2.61971 18.2575 3.26967Z" />
                        </svg>
                        <input id="email" name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" autocomplete="email" required>
                    </span>
                </label>

                <label class="login-form__field" for="password">
                    <span>Password</span>
                    <span class="login-form__control">
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M10 1.667C7.242 1.667 5 3.908 5 6.667V8H4.167A2.5 2.5 0 0 0 1.667 10.5v5A2.5 2.5 0 0 0 4.167 18h11.666a2.5 2.5 0 0 0 2.5-2.5v-5a2.5 2.5 0 0 0-2.5-2.5H15V6.667c0-2.759-2.242-5-5-5Zm-3.333 5a3.333 3.333 0 1 1 6.666 0V8H6.667V6.667ZM10.833 13.9V15a.833.833 0 1 1-1.666 0v-1.1a1.667 1.667 0 1 1 1.666 0Z" />
                        </svg>
                        <input id="password" name="password" type="password" placeholder="Enter password" autocomplete="current-password" required>
                        <button class="login-form__toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                            <svg class="login-form__eye login-form__eye--show" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M17.7084 7.625C15.7834 4.6 12.9667 2.85834 10.0001 2.85834C8.51675 2.85834 7.07508 3.29167 5.75841 4.1C4.44175 4.91667 3.25841 6.10834 2.29175 7.625C1.45841 8.93334 1.45841 11.0583 2.29175 12.3667C4.21675 15.4 7.03341 17.1333 10.0001 17.1333C11.4834 17.1333 12.9251 16.7 14.2417 15.8917C15.5584 15.075 16.7417 13.8833 17.7084 12.3667C18.5417 11.0667 18.5417 8.93334 17.7084 7.625ZM10.0001 13.3667C8.13341 13.3667 6.63341 11.8583 6.63341 10C6.63341 8.14167 8.13341 6.63334 10.0001 6.63334C11.8667 6.63334 13.3667 8.14167 13.3667 10C13.3667 11.8583 11.8667 13.3667 10.0001 13.3667Z" />
                                <path d="M10 7.61664C8.69167 7.61664 7.625 8.6833 7.625 9.99997C7.625 11.3083 8.69167 12.375 10 12.375C11.3083 12.375 12.3833 11.3083 12.3833 9.99997C12.3833 8.69164 11.3083 7.61664 10 7.61664Z" />
                            </svg>
                            <svg class="login-form__eye login-form__eye--hide" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M17.725 7.65004C17.4834 7.26671 17.225 6.90838 16.9584 6.57504C16.65 6.18338 16.0667 6.15004 15.7167 6.50004L13.2167 9.00004C13.4 9.55004 13.4334 10.1834 13.2667 10.8417C12.975 12.0167 12.025 12.9667 10.85 13.2584C10.1917 13.425 9.55837 13.3917 9.00837 13.2084L6.95837 15.2584C6.54171 15.675 6.67504 16.4084 7.23337 16.625C8.12504 16.9667 9.05004 17.1417 10 17.1417C11.4834 17.1417 12.925 16.7084 14.2417 15.9C15.5834 15.0667 16.7917 13.8417 17.7667 12.2834C18.5584 11.025 18.5167 8.90838 17.725 7.65004Z" />
                                <path d="M15.2084 4.79161L12.3834 7.61661C11.7751 6.99994 10.9334 6.63328 10.0001 6.63328C8.13342 6.63328 6.63341 8.14161 6.63341 9.99994C6.63341 10.9333 7.00842 11.7749 7.61675 12.3833L4.80008 15.2083H4.79175C3.86675 14.4583 3.01675 13.4999 2.29175 12.3666C1.45841 11.0583 1.45841 8.93328 2.29175 7.62494C3.25841 6.10828 4.44175 4.91661 5.75841 4.09994C7.07508 3.29994 8.51675 2.85828 10.0001 2.85828C11.8584 2.85828 13.6584 3.54161 15.2084 4.79161Z" />
                                <path d="M18.1417 1.85828C17.8917 1.60828 17.4834 1.60828 17.2334 1.85828L1.8584 17.2416C1.6084 17.4916 1.6084 17.8999 1.8584 18.1499C1.9834 18.2666 2.14173 18.3333 2.3084 18.3333C2.47507 18.3333 2.6334 18.2666 2.7584 18.1416L18.1417 2.75828C18.4001 2.50828 18.4001 2.10828 18.1417 1.85828Z" />
                            </svg>
                        </button>
                    </span>
                </label>

                @if ($errors->any())
                    <p class="login-form__error">{{ $errors->first() }}</p>
                @endif

                <div class="login-form__meta">
                    <label class="login-form__remember">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <span>Need access? Contact admin.</span>
                </div>

                <button class="login-form__submit" type="submit">
                    <span>Sign In</span>
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M11.25 3.75a.83.83 0 0 0 0 1.18l3.4 3.4H3.33a.83.83 0 1 0 0 1.67h11.32l-3.4 3.4a.83.83 0 1 0 1.18 1.18l4.82-4.82a.83.83 0 0 0 0-1.18l-4.82-4.83a.83.83 0 0 0-1.18 0Z" />
                    </svg>
                </button>

                <div class="login-form__register">
                    <span>New customer?</span>
                    <a href="{{ route('subcription') }}">Register & unlock STY</a>
                    <p>Sampling voice pack requests are paid separately after request.</p>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection

@push('script')
<script>
    document.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
        toggleButton.addEventListener('click', () => {
            const passwordInput = toggleButton.closest('.login-form__control').querySelector('input');
            const isVisible = passwordInput.type === 'text';

            passwordInput.type = isVisible ? 'password' : 'text';
            toggleButton.setAttribute('aria-pressed', String(!isVisible));
            toggleButton.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
    });
</script>
@endpush
