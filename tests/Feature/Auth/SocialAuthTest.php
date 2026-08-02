<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
        config()->set('services.facebook.client_id', 'facebook-client-id');
        config()->set('services.facebook.client_secret', 'facebook-client-secret');
        config()->set('services.facebook.redirect', 'http://localhost/auth/facebook/callback');
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test-key');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test-key');
        config()->set('services.midtrans.is_production', false);
    }

    public function test_google_redirect_opens_the_account_chooser(): void
    {
        $response = $this->get(route('auth.google.redirect', ['intent' => 'subscription']));

        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        $this->assertSame('select_account', $query['prompt']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertSame('http://localhost/auth/google/callback', $query['redirect_uri']);
        $this->assertNotEmpty($query['state']);
        $this->assertSame($query['state'], session('social_oauth_state'));
        $this->assertSame('google', session('social_oauth_provider'));
        $this->assertSame('subscription', session('social_oauth_intent'));
    }

    public function test_facebook_redirect_opens_the_facebook_login_dialog(): void
    {
        $response = $this->get(route('auth.facebook.redirect'));

        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://www.facebook.com/dialog/oauth?', $location);
        $this->assertSame('email,public_profile', $query['scope']);
        $this->assertSame('http://localhost/auth/facebook/callback', $query['redirect_uri']);
        $this->assertSame('facebook', session('social_oauth_provider'));
    }

    public function test_verified_google_profile_creates_and_logs_in_a_customer(): void
    {
        $this->fakeGoogleProfile([
            'sub' => 'google-user-123',
            'name' => 'Ayu Musik',
            'email' => 'ayu@gmail.com',
            'email_verified' => true,
            'picture' => 'https://lh3.googleusercontent.com/ayu-photo',
        ]);

        $response = $this
            ->withSession($this->socialSession('google', 'subscription'))
            ->get(route('auth.google.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]));

        $user = User::where('email', 'ayu@gmail.com')->firstOrFail();

        $response->assertRedirect(route('subcription', ['checkout' => 'payment']));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('Free', $user->plan);
        $this->assertSame('https://lh3.googleusercontent.com/ayu-photo', $user->profile_photo_path);
    }

    public function test_facebook_profile_creates_and_logs_in_a_customer(): void
    {
        $this->fakeFacebookProfile([
            'id' => 'facebook-user-456',
            'name' => 'Bima Musik',
            'email' => 'bima@example.com',
            'picture' => [
                'data' => ['url' => 'https://platform-lookaside.fbsbx.com/bima-photo'],
            ],
        ]);

        $response = $this
            ->withSession($this->socialSession('facebook'))
            ->get(route('auth.facebook.callback', [
                'code' => 'facebook-code',
                'state' => 'valid-state',
            ]));

        $user = User::where('email', 'bima@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('facebook-user-456', $user->facebook_id);
        $this->assertSame('https://platform-lookaside.fbsbx.com/bima-photo', $user->profile_photo_path);
    }

    public function test_social_profile_links_an_existing_customer_with_the_same_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@gmail.com',
            'google_id' => null,
        ]);

        $this->fakeGoogleProfile([
            'sub' => 'google-existing-789',
            'name' => 'Existing Customer',
            'email' => 'existing@gmail.com',
            'email_verified' => true,
        ]);

        $response = $this
            ->withSession($this->socialSession('google'))
            ->get(route('auth.google.callback', [
                'code' => 'authorization-code',
                'state' => 'valid-state',
            ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-existing-789', $user->refresh()->google_id);
    }

    public function test_social_customer_can_complete_subscription_checkout_without_a_password(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'test-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token',
            ]),
        ]);

        $user = User::factory()->create([
            'email' => 'facebook-checkout@example.com',
            'facebook_id' => 'facebook-checkout-101',
            'password' => Hash::make('unknown-random-password'),
        ]);

        $response = $this->actingAs($user)->post(route('subcription.payment'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+628123456789',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Sandbox',
        ]);

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package' => 'Premium Monthly',
            'status' => 'Pending',
        ]);
    }

    public function test_callback_rejects_an_invalid_oauth_state(): void
    {
        Http::preventStrayRequests();

        $response = $this
            ->withSession($this->socialSession('google'))
            ->get(route('auth.google.callback', [
                'code' => 'authorization-code',
                'state' => 'different-state',
            ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('social');
        $this->assertGuest();
    }

    private function socialSession(string $provider, string $intent = 'login'): array
    {
        return [
            'social_oauth_state' => 'valid-state',
            'social_oauth_provider' => $provider,
            'social_oauth_intent' => $intent,
        ];
    }

    private function fakeGoogleProfile(array $profile): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response($profile),
        ]);
    }

    private function fakeFacebookProfile(array $profile): void
    {
        Http::fake([
            'https://graph.facebook.com/oauth/access_token*' => Http::response([
                'access_token' => 'facebook-access-token',
            ]),
            'https://graph.facebook.com/me*' => Http::response($profile),
        ]);
    }
}
