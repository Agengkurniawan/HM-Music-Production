<?php

namespace Tests\Feature\Customer;

use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SamplingRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.midtrans.server_key', 'SB-Mid-server-test-key');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-test-key');
        config()->set('services.midtrans.is_production', false);
    }

    public function test_customer_requests_sampling_pays_then_uploads_n27_to_admin(): void
    {
        Storage::fake('public');
        $this->fakeMidtransSnap();

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $response = $this->actingAs($customer)->post(route('sampling-requests.store'), [
            'pack_name' => 'HM Dangdut Koplo Expansion Packs',
            'keyboard_storage_mb' => 512,
            'customer_notes' => 'Hanya butuh Dangdut untuk keyboard 512 MB.',
        ]);

        $response->assertRedirect(route('stylesampling', ['type' => 'sampling']));
        $response->assertSessionHasNoErrors();

        $samplingRequest = SamplingRequest::where('user_id', $customer->id)->firstOrFail();

        $this->assertSame(SamplingRequest::PAYMENT_PENDING, $samplingRequest->payment_status);
        $this->assertSame(SamplingRequest::STATUS_PENDING_PAYMENT, $samplingRequest->status);
        $this->assertSame(800000, $samplingRequest->amount);
        $this->assertSame(512, $samplingRequest->keyboard_storage_mb);
        $this->assertSame('HM Dangdut Expansion Packs', $samplingRequest->pack_name);

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment', $samplingRequest))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/sampling-token')
            ->assertSessionHasNoErrors();

        $samplingRequest->refresh();

        $this->assertSame(SamplingRequest::PAYMENT_PENDING, $samplingRequest->payment_status);
        $this->assertSame(SamplingRequest::STATUS_PENDING_PAYMENT, $samplingRequest->status);
        $this->assertInstanceOf(Payment::class, $samplingRequest->payment);
        $this->assertSame('Pending', $samplingRequest->payment->status);
        $this->assertSame('Midtrans Sampling Sandbox', $samplingRequest->payment->method);

        $this->postJson(route('payment.midtrans.notification'), $this->midtransPayload($samplingRequest->payment))
            ->assertOk();

        $samplingRequest->refresh();

        $this->assertSame(SamplingRequest::PAYMENT_PAID, $samplingRequest->payment_status);
        $this->assertSame(SamplingRequest::STATUS_PAID, $samplingRequest->status);
        $this->assertSame('Completed', $samplingRequest->payment->refresh()->status);

        $this->actingAs($customer)
            ->post(route('sampling-requests.n27.upload', $samplingRequest), [
                'n27_file' => UploadedFile::fake()->create('after-paid.n27', 16, 'application/octet-stream'),
            ])
            ->assertRedirect(route('stylesampling', ['type' => 'sampling']));

        $samplingRequest->refresh();

        $this->assertSame(SamplingRequest::STATUS_N27_UPLOADED, $samplingRequest->status);
        $this->assertSame('after-paid.n27', $samplingRequest->n27_original_name);
        Storage::disk('public')->assertExists($samplingRequest->n27_file_path);
    }

    public function test_customer_can_sync_sandbox_sampling_payment_before_uploading_n27(): void
    {
        Storage::fake('public');
        $this->fakeMidtransSnap();

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $this->actingAs($customer)->post(route('sampling-requests.store'), [
            'pack_name' => 'HM Campursari Expansion Packs',
        ]);

        $samplingRequest = SamplingRequest::where('user_id', $customer->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment', $samplingRequest))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/sampling-token');

        $payment = $samplingRequest->refresh()->payment;

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => $payment->reference,
                'transaction_status' => 'settlement',
                'status_code' => '200',
                'gross_amount' => number_format($payment->amount, 2, '.', ''),
            ]),
        ]);

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment.sync', $samplingRequest))
            ->assertRedirect(route('stylesampling', ['type' => 'sampling']))
            ->assertSessionHas('success', 'Pembayaran sampling berhasil dikonfirmasi. Silakan upload file N27.');

        $samplingRequest->refresh();

        $this->assertSame(SamplingRequest::PAYMENT_PAID, $samplingRequest->payment_status);
        $this->assertSame(SamplingRequest::STATUS_PAID, $samplingRequest->status);
        $this->assertSame('Completed', $payment->refresh()->status);
        $this->assertTrue($samplingRequest->can_upload_n27);

        $this->actingAs($customer)
            ->post(route('sampling-requests.n27.upload', $samplingRequest), [
                'n27_file' => UploadedFile::fake()->create('sandbox-paid.n27', 16, 'application/octet-stream'),
            ])
            ->assertRedirect(route('stylesampling', ['type' => 'sampling']));

        $this->assertSame('sandbox-paid.n27', $samplingRequest->refresh()->n27_original_name);
        Storage::disk('public')->assertExists($samplingRequest->n27_file_path);
    }

    public function test_retrying_sampling_checkout_cancels_previous_pending_payment(): void
    {
        $this->fakeMidtransSnap();

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $this->actingAs($customer)->post(route('sampling-requests.store'), [
            'pack_name' => 'HM Gamelan Expansion Packs',
        ]);

        $samplingRequest = SamplingRequest::where('user_id', $customer->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment', $samplingRequest))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/sampling-token');

        $firstPayment = $samplingRequest->refresh()->payment;

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment', $samplingRequest))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/sampling-token');

        $secondPayment = $samplingRequest->refresh()->payment;

        $this->assertNotSame($firstPayment->id, $secondPayment->id);
        $this->assertSame('Cancelled', $firstPayment->refresh()->status);
        $this->assertSame('Pending', $secondPayment->status);
        $this->assertSame($secondPayment->id, $samplingRequest->payment_id);
    }

    public function test_sampling_payment_sync_rejects_amount_mismatch(): void
    {
        $this->fakeMidtransSnap();

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $this->actingAs($customer)->post(route('sampling-requests.store'), [
            'pack_name' => 'HM Dangdut Expansion Packs',
        ]);

        $samplingRequest = SamplingRequest::where('user_id', $customer->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment', $samplingRequest))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/sampling-token');

        $payment = $samplingRequest->refresh()->payment;

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => $payment->reference,
                'transaction_status' => 'settlement',
                'status_code' => '200',
                'gross_amount' => '1.00',
            ]),
        ]);

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment.sync', $samplingRequest))
            ->assertRedirect(route('stylesampling', ['type' => 'sampling']))
            ->assertSessionHasErrors('payment', null, 'samplingPayment');

        $this->assertSame(SamplingRequest::PAYMENT_PENDING, $samplingRequest->refresh()->payment_status);
        $this->assertSame('Pending', $payment->refresh()->status);
    }

    public function test_admin_cannot_confirm_sampling_payment_with_wrong_amount(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin HM',
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $samplingRequest = SamplingRequest::create([
            'user_id' => $customer->id,
            'order_reference' => 'N27-REQ-TEST-AMOUNT',
            'product_name' => 'Pack 1 - Dangdut',
            'pack_name' => 'HM Dangdut Expansion Packs',
            'amount' => 800000,
            'payment_status' => SamplingRequest::PAYMENT_PENDING,
            'status' => SamplingRequest::STATUS_PENDING_PAYMENT,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.sampling-requests.payment', $samplingRequest), [
                'amount' => 0,
            ])
            ->assertSessionHasErrors('amount', null, 'adminSamplingPayment');

        $this->assertSame(SamplingRequest::PAYMENT_PENDING, $samplingRequest->refresh()->payment_status);
        $this->assertNull($samplingRequest->payment_id);
    }

    private function fakeMidtransSnap(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'sampling-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/sampling-token',
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
