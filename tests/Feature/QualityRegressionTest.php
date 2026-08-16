<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\SiteSetting;
use App\Models\StyleSampling;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MidtransSnapGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.midtrans.server_key', 'SB-Mid-server-test-key');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test-key');
        config()->set('services.midtrans.is_production', false);
    }

    public function test_style_update_rejects_a_category_outside_the_catalog(): void
    {
        $style = $this->style();

        $this->actingAs($this->admin())->put(route('admin.stylesampling.update', $style), [
            'name' => 'Invalid category attempt',
            'category' => 'Arbitrary Category',
        ])->assertSessionHasErrors('category', null, 'editStyle');

        $this->assertSame('Dangdut', $style->refresh()->category);
    }

    public function test_deleting_a_style_removes_all_of_its_managed_files(): void
    {
        Storage::fake('public');
        foreach (['style.sty', 'audio.mp3', 'preview.mp3', 'cover.jpg'] as $file) {
            Storage::disk('public')->put("styles/{$file}", 'content');
        }

        $style = $this->style([
            'style_file_path' => 'styles/style.sty',
            'audio_path' => 'styles/audio.mp3',
            'preview_path' => 'styles/preview.mp3',
            'cover_image_path' => 'styles/cover.jpg',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.stylesampling.destroy', $style))
            ->assertSessionHasNoErrors();

        foreach (['style.sty', 'audio.mp3', 'preview.mp3', 'cover.jpg'] as $file) {
            Storage::disk('public')->assertMissing("styles/{$file}");
        }
    }

    public function test_replacing_an_n27_file_removes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sampling-requests/n27/old.n27', 'old');
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'Active']);
        $request = SamplingRequest::create([
            'user_id' => $customer->id,
            'order_reference' => 'N27-REPLACE-TEST',
            'product_name' => 'Pack 1 - Dangdut',
            'pack_name' => 'HM Dangdut Expansion Packs',
            'amount' => 800000,
            'payment_status' => SamplingRequest::PAYMENT_PAID,
            'status' => SamplingRequest::STATUS_N27_UPLOADED,
            'n27_file_path' => 'sampling-requests/n27/old.n27',
            'n27_original_name' => 'old.n27',
        ]);

        $this->actingAs($customer)->post(route('sampling-requests.n27.upload', $request), [
            'n27_file' => UploadedFile::fake()->create('replacement.n27', 10),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('sampling-requests/n27/old.n27');
        Storage::disk('public')->assertExists($request->refresh()->n27_file_path);
    }

    public function test_admin_password_reset_requires_letters_and_numbers(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.usermanagement.password', $customer), [
            'password' => 'aaaaaaaa',
            'password_confirmation' => 'aaaaaaaa',
        ])->assertSessionHasErrors('password', null, 'adminUserPassword');

        $this->actingAs($admin)->patch(route('admin.usermanagement.password', $customer), [
            'password' => 'secure123',
            'password_confirmation' => 'secure123',
        ])->assertSessionHasNoErrors();
    }

    public function test_guest_cannot_write_notification_read_state(): void
    {
        $this->postJson(route('notifications.read'), ['key' => 'private-key'])
            ->assertUnauthorized();
    }

    public function test_retrying_subscription_checkout_cancels_the_previous_pending_checkout(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'retry-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/retry-token',
            ]),
        ]);
        $payload = [
            'name' => 'Retry Customer',
            'email' => 'retry@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'package' => 'Premium Monthly',
            'amount' => 55000,
        ];

        $this->post(route('subcription.payment'), $payload)->assertRedirect();
        $this->post(route('subcription.payment'), $payload)->assertRedirect();

        $this->assertSame(1, Payment::where('status', 'Pending')->count());
        $this->assertSame(1, Payment::where('status', 'Cancelled')->count());
        $this->assertSame(1, Subscription::where('status', 'Pending')->count());
        $this->assertSame(1, Subscription::where('status', 'Cancelled')->count());
    }

    public function test_midtrans_gateway_reads_the_explicit_server_key_setting(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-from-env');
        SiteSetting::setMany(['midtrans_server_key' => 'SB-Mid-server-from-setting']);

        $this->assertSame('SB-Mid-server-from-setting', app(MidtransSnapGateway::class)->serverKey());
    }

    public function test_admin_cannot_save_a_subscription_price_below_the_business_minimum(): void
    {
        $this->actingAs($this->admin())->post(route('admin.setting.update'), [
            'subscription_price' => '1000',
            'plan_duration' => '30 Days',
            'midtrans_server_key' => 'SB-Mid-server-test-key',
            'midtrans_client_key' => 'SB-Mid-client-test-key',
            'midtrans_is_production' => '0',
        ])->assertSessionHasErrors('subscription_price');

        $this->assertDatabaseMissing('site_settings', [
            'key' => 'subscription_price',
            'value' => '1000',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'status' => 'Active',
        ]);
    }

    private function style(array $attributes = []): StyleSampling
    {
        return StyleSampling::create(array_merge([
            'name' => 'Regression Style',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium',
            'status' => 'Draft',
            'style_file_path' => 'styles/default.sty',
        ], $attributes));
    }
}
