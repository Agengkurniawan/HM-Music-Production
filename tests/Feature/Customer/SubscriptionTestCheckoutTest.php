<?php

namespace Tests\Feature\Customer;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionTestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.midtrans.server_key', 'SB-Mid-server-test-key');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test-key');
        config()->set('services.midtrans.is_production', false);
    }

    public function test_user_profile_photo_url_falls_back_to_generated_avatar(): void
    {
        $user = User::factory()->make([
            'name' => 'Rina Lestari',
            'email' => 'rina@example.com',
        ]);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $user->profile_photo_url);
    }

    public function test_midtrans_checkout_saves_customer_login_and_waits_for_sandbox_payment_confirmation(): void
    {
        Storage::fake('public');
        $this->fakeMidtransSnap();

        $response = $this->post(route('subcription.payment'), [
            'name' => 'Andi Pratama',
            'email' => 'andi.test@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            'phone' => '+628123456789',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Sandbox',
            'profile_photo' => UploadedFile::fake()->image('andi.jpg', 320, 320),
        ]);

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');
        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'andi.test@example.com')->firstOrFail();

        $this->assertGuest();
        $this->assertTrue(Hash::check('password-rahasia', $user->password));
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        $this->assertStringContainsString('/storage/profile-photos/', $user->profile_photo_url);

        $this->assertDatabaseHas('users', [
            'email' => 'andi.test@example.com',
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package' => 'Premium Monthly',
            'status' => 'Pending',
        ]);

        $payment = Payment::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Pending', $payment->status);
        $this->assertSame('Midtrans Snap Sandbox', $payment->method);
        $this->assertStringStartsWith('HM-SUB-', $payment->reference);

        $this->assertDatabaseMissing('sampling_requests', [
            'user_id' => $user->id,
        ]);
    }

    public function test_registered_customer_can_renew_with_existing_email_and_password(): void
    {
        Carbon::setTestNow('2026-06-07 10:00:00');
        $this->fakeMidtransSnap();

        $customer = User::factory()->create([
            'name' => 'Rina Lestari',
            'email' => 'rina.renew@example.com',
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Premium Monthly',
        ]);

        $subscription = Subscription::create([
            'user_id' => $customer->id,
            'package' => 'Premium Monthly',
            'starts_at' => now()->subDays(20),
            'expires_at' => now()->addDays(10),
            'status' => 'Active',
        ]);

        $response = $this->post(route('subcription.payment'), [
            'name' => 'Rina Lestari',
            'email' => 'rina.renew@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '+628123456700',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Sandbox',
        ]);

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');
        $response->assertSessionHasNoErrors();

        $this->assertGuest();

        $payment = Payment::where('user_id', $customer->id)->firstOrFail();

        $this->postJson(route('payment.midtrans.notification'), $this->midtransPayload($payment))
            ->assertOk();

        $subscription->refresh();
        $this->assertSame('2026-07-17', $subscription->expires_at->toDateString());
        $this->assertDatabaseCount('subscriptions', 2);
        $this->assertDatabaseHas('payments', [
            'user_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'customer_email' => 'rina.renew@example.com',
            'status' => 'Completed',
        ]);

        Carbon::setTestNow();
    }

    public function test_registered_customer_cannot_renew_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'wrong-password@example.com',
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $response = $this->post(route('subcription.payment'), [
            'name' => 'Wrong Password',
            'email' => 'wrong-password@example.com',
            'password' => 'password-salah',
            'password_confirmation' => 'password-salah',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Sandbox',
        ]);

        $response->assertSessionHasErrors('password', null, 'subscriptionCheckout');

        $this->assertGuest();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_midtrans_notification_rejects_amount_mismatch(): void
    {
        $this->fakeMidtransSnap();

        $this->post(route('subcription.payment'), [
            'name' => 'Amount Guard',
            'email' => 'amount-guard@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Sandbox',
        ])->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');

        $payment = Payment::where('customer_email', 'amount-guard@example.com')->firstOrFail();
        $grossAmount = '1.00';
        $statusCode = '200';

        $this->postJson(route('payment.midtrans.notification'), [
            'order_id' => $payment->reference,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', $payment->reference.$statusCode.$grossAmount.config('services.midtrans.server_key')),
        ])->assertStatus(422);

        $this->assertSame('Pending', $payment->refresh()->status);
        $this->assertSame('Pending', $payment->subscription->refresh()->status);
    }

    public function test_sandbox_checkout_accepts_modern_midtrans_key_format(): void
    {
        $this->fakeMidtransSnap();

        config()->set('services.midtrans.server_key', 'Mid-server-sandbox-key');
        config()->set('services.midtrans.client_key', 'Mid-client-sandbox-key');
        config()->set('services.midtrans.is_production', false);

        $this->post(route('subcription.payment'), [
            'name' => 'Modern Key',
            'email' => 'modern-key@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Sandbox',
        ])->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');

        $payment = Payment::where('customer_email', 'modern-key@example.com')->firstOrFail();

        $this->assertSame('Pending', $payment->status);
        $this->assertSame('Pending', $payment->subscription->refresh()->status);
    }

    public function test_production_checkout_rejects_legacy_sandbox_midtrans_keys(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-test-key');
        config()->set('services.midtrans.client_key', 'Mid-client-production-key');
        config()->set('services.midtrans.is_production', true);

        $this->post(route('subcription.payment'), [
            'name' => 'Wrong Key',
            'email' => 'wrong-key@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            'package' => 'Premium Monthly',
            'amount' => 55000,
            'method' => 'Midtrans Snap Production',
        ])->assertSessionHasErrors('method', null, 'subscriptionCheckout');

        $payment = Payment::where('customer_email', 'wrong-key@example.com')->firstOrFail();

        $this->assertSame('Failed', $payment->status);
        $this->assertSame('Cancelled', $payment->subscription->refresh()->status);
    }

    private function fakeMidtransSnap(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'test-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token',
            ]),
        ]);
    }

    private function midtransPayload(Payment $payment): array
    {
        $grossAmount = number_format($payment->amount, 2, '.', '');
        $statusCode = '200';

        return [
            'order_id' => $payment->reference,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', $payment->reference.$statusCode.$grossAmount.config('services.midtrans.server_key')),
        ];
    }
}
