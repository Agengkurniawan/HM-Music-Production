<?php

namespace Tests\Feature\Customer;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionTestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_photo_url_falls_back_to_generated_avatar(): void
    {
        $user = User::factory()->make([
            'name' => 'Rina Lestari',
            'email' => 'rina@example.com',
        ]);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $user->profile_photo_url);
    }

    public function test_test_checkout_saves_customer_login_and_unlocks_style_subscription_only(): void
    {
        Storage::fake('public');

        $response = $this->post(route('subcription.payment'), [
            'name' => 'Andi Pratama',
            'email' => 'andi.test@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
            'phone' => '+628123456789',
            'package' => 'Premium Monthly',
            'amount' => 29000,
            'method' => 'Test Checkout (Midtrans skipped)',
            'profile_photo' => UploadedFile::fake()->image('andi.jpg', 320, 320),
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $response->assertSessionHas('payment_reference');

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
            'plan' => 'Premium Monthly',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'package' => 'Premium Monthly',
            'status' => 'Active',
        ]);

        $payment = Payment::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Completed', $payment->status);
        $this->assertSame('Test Checkout (Midtrans skipped)', $payment->method);
        $this->assertStringStartsWith('TEST-PAY-', $payment->reference);

        $this->assertDatabaseMissing('sampling_requests', [
            'user_id' => $user->id,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Pembayaran berhasil')
            ->assertSee('andi.test@example.com');
    }
}
