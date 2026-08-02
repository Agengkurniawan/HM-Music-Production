<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'email' => 'customer-reset@example.com',
            'role' => 'customer',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'customer-reset@example.com',
        ]);

        $response->assertSessionHas('success');
        Notification::assertSentTo($customer, CustomerResetPasswordNotification::class);
    }

    public function test_admin_email_is_not_sent_a_customer_password_reset_link(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email' => 'admin-reset@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'admin-reset@example.com',
        ]);

        $response->assertSessionHas('success');
        Notification::assertNotSentTo($admin, CustomerResetPasswordNotification::class);
    }

    public function test_customer_can_reset_password_with_a_verified_token(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'email' => 'verified-reset@example.com',
            'password' => Hash::make('old-password1'),
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $this->post(route('password.email'), [
            'email' => 'verified-reset@example.com',
        ]);

        $token = null;
        Notification::assertSentTo(
            $customer,
            CustomerResetPasswordNotification::class,
            function (CustomerResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return filled($token);
            }
        );

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'verified-reset@example.com',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $customer->refresh();

        $this->assertTrue(Hash::check('new-password1', $customer->password));
        $this->assertNotNull($customer->email_verified_at);
        $this->assertSame('Password reset by customer verification link', $customer->last_activity);
    }
}
