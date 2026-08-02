<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SocialAuthProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = [
        'google' => [
            'label' => 'Google',
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'profile_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scope' => 'openid email profile',
            'id_key' => 'sub',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'authorize_url' => 'https://www.facebook.com/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/oauth/access_token',
            'profile_url' => 'https://graph.facebook.com/me',
            'scope' => 'email,public_profile',
            'id_key' => 'id',
        ],
    ];

    public function redirect(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        $settings = self::PROVIDERS[$provider];

        if ($request->user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $intent = $request->query('intent') === 'subscription' ? 'subscription' : 'login';

        if (! $this->isConfigured($provider)) {
            return $this->redirectWithError(
                $intent,
                'Login '.$settings['label'].' belum aktif karena identitas aplikasi OAuth belum dihubungkan. Isi '.Str::upper($provider).'_CLIENT_ID dan '.Str::upper($provider).'_CLIENT_SECRET di .env.',
            );
        }

        $state = Str::random(64);

        $request->session()->put([
            'social_oauth_state' => $state,
            'social_oauth_provider' => $provider,
            'social_oauth_intent' => $intent,
            'social_oauth_user_id' => $request->user()?->id,
        ]);

        $query = [
            'client_id' => config("services.{$provider}.client_id"),
            'redirect_uri' => $this->redirectUri($provider),
            'response_type' => 'code',
            'scope' => $settings['scope'],
            'state' => $state,
        ];

        if ($provider === 'google') {
            $query['prompt'] = 'select_account';
            $query['include_granted_scopes'] = 'true';
        }

        return redirect()->away($settings['authorize_url'].'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        $settings = self::PROVIDERS[$provider];
        $intent = $request->session()->pull('social_oauth_intent', 'login');
        $expectedState = (string) $request->session()->pull('social_oauth_state', '');
        $expectedProvider = (string) $request->session()->pull('social_oauth_provider', '');
        $linkingUserId = $request->session()->pull('social_oauth_user_id');

        if ($request->filled('error')) {
            return $this->redirectWithError(
                $intent,
                $request->query('error') === 'access_denied'
                    ? 'Pemilihan akun '.$settings['label'].' dibatalkan.'
                    : $settings['label'].' tidak dapat menyelesaikan proses login. Silakan coba lagi.',
            );
        }

        $returnedState = (string) $request->query('state', '');

        if ($expectedProvider !== $provider || $expectedState === '' || $returnedState === '' || ! hash_equals($expectedState, $returnedState)) {
            return $this->redirectWithError($intent, 'Sesi login '.$settings['label'].' tidak valid atau sudah kedaluwarsa. Silakan coba lagi.');
        }

        if (! $this->isConfigured($provider) || ! $request->filled('code')) {
            return $this->redirectWithError($intent, 'Konfigurasi atau kode otorisasi '.$settings['label'].' tidak valid.');
        }

        try {
            $accessToken = $this->exchangeCodeForToken($provider, $request->string('code')->toString());
            $profile = $this->fetchProfile($provider, $accessToken);
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectWithError($intent, 'Tidak dapat menghubungi '.$settings['label'].' saat ini. Silakan coba lagi.');
        }

        $providerId = trim((string) ($profile[$settings['id_key']] ?? ''));
        $email = Str::lower(trim((string) ($profile['email'] ?? '')));
        $emailVerified = $provider === 'google'
            ? filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOL)
            : filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        if ($providerId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $emailVerified) {
            return $this->redirectWithError($intent, $settings['label'].' tidak memberikan alamat email yang dapat digunakan. Pastikan izin email disetujui.');
        }

        $providerColumn = $provider.'_id';
        $userByProvider = User::where($providerColumn, $providerId)->first();
        $userByEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $linkingUser = $linkingUserId ? User::find($linkingUserId) : null;

        if ($linkingUser && Str::lower($linkingUser->email) !== $email) {
            return $this->redirectWithError($intent, 'Pilih akun '.$settings['label'].' dengan email yang sama seperti akun yang sedang login.');
        }

        if ($userByProvider && $userByEmail && ! $userByProvider->is($userByEmail)) {
            return $this->redirectWithError($intent, 'Identitas '.$settings['label'].' tersebut sudah terhubung ke akun lain.');
        }

        $user = $linkingUser ?: $userByProvider ?: $userByEmail;

        if ($user?->role === 'admin') {
            return $this->redirectWithError($intent, 'Login sosial hanya tersedia untuk akun customer.');
        }

        if ($user?->status === 'Suspended') {
            return $this->redirectWithError($intent, 'Akun ini sedang suspended. Silakan hubungi admin.');
        }

        if ($user?->{$providerColumn} && $user->{$providerColumn} !== $providerId) {
            return $this->redirectWithError($intent, 'Email tersebut sudah tertaut ke identitas '.$settings['label'].' yang berbeda.');
        }

        $picture = $this->profilePicture($provider, $profile);

        $user = DB::transaction(function () use ($user, $profile, $providerId, $providerColumn, $provider, $settings, $email, $picture): User {
            if (! $user) {
                return User::create([
                    'name' => trim((string) ($profile['name'] ?? '')) ?: Str::before($email, '@'),
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Str::random(64),
                    $providerColumn => $providerId,
                    'role' => 'customer',
                    'status' => 'Active',
                    'plan' => 'Free',
                    'last_activity' => 'Registered with '.$settings['label'],
                    'profile_photo_path' => $picture,
                ]);
            }

            $updates = [
                $providerColumn => $providerId,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'last_activity' => 'Signed in with '.$settings['label'],
            ];

            if (! $user->profile_photo_path && $picture) {
                $updates['profile_photo_path'] = $picture;
            }

            $user->update($updates);

            return $user->refresh();
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($intent === 'subscription') {
            return redirect()
                ->route('subcription', ['checkout' => 'payment'])
                ->with('success', 'Akun '.$settings['label'].' berhasil ditautkan. Lengkapi checkout untuk mengaktifkan subscription STY.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Berhasil login menggunakan akun '.$settings['label'].'.');
    }

    private function exchangeCodeForToken(string $provider, string $code): string
    {
        $settings = self::PROVIDERS[$provider];
        $payload = [
            'code' => $code,
            'client_id' => config("services.{$provider}.client_id"),
            'client_secret' => config("services.{$provider}.client_secret"),
            'redirect_uri' => $this->redirectUri($provider),
        ];

        $response = $provider === 'google'
            ? Http::asForm()->acceptJson()->post($settings['token_url'], [...$payload, 'grant_type' => 'authorization_code'])
            : Http::acceptJson()->get($settings['token_url'], $payload);

        $accessToken = (string) $response->throw()->json('access_token');

        if ($accessToken === '') {
            throw new \RuntimeException($settings['label'].' access token is missing.');
        }

        return $accessToken;
    }

    private function fetchProfile(string $provider, string $accessToken): array
    {
        $request = Http::withToken($accessToken)->acceptJson();
        $response = $provider === 'facebook'
            ? $request->get(self::PROVIDERS[$provider]['profile_url'], ['fields' => 'id,name,email,picture.type(large)'])
            : $request->get(self::PROVIDERS[$provider]['profile_url']);
        $profile = $response->throw()->json();

        if (! is_array($profile)) {
            throw new \RuntimeException(self::PROVIDERS[$provider]['label'].' profile response is invalid.');
        }

        return $profile;
    }

    private function profilePicture(string $provider, array $profile): ?string
    {
        $picture = $provider === 'facebook'
            ? data_get($profile, 'picture.data.url')
            : ($profile['picture'] ?? null);

        return is_string($picture) && $picture !== '' ? $picture : null;
    }

    private function provider(Request $request): string
    {
        $provider = (string) $request->route('provider');

        abort_unless(array_key_exists($provider, self::PROVIDERS), 404);

        return $provider;
    }

    private function isConfigured(string $provider): bool
    {
        return SocialAuthProviders::isConfigured($provider);
    }

    private function redirectUri(string $provider): string
    {
        return (string) (config("services.{$provider}.redirect") ?: route("auth.{$provider}.callback"));
    }

    private function redirectWithError(string $intent, string $message): RedirectResponse
    {
        $redirect = $intent === 'subscription'
            ? redirect()->route('subcription', ['checkout' => 'payment'])
            : redirect()->route('login');

        return $redirect->withErrors(['social' => $message]);
    }
}
