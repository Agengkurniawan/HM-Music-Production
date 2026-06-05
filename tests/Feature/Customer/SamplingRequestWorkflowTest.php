<?php

namespace Tests\Feature\Customer;

use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SamplingRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_requests_sampling_pays_then_uploads_n27_to_admin(): void
    {
        Storage::fake('public');

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
        $this->assertSame(750000, $samplingRequest->amount);
        $this->assertSame(512, $samplingRequest->keyboard_storage_mb);

        $this->actingAs($customer)
            ->post(route('sampling-requests.payment', $samplingRequest))
            ->assertRedirect(route('stylesampling', ['type' => 'sampling']))
            ->assertSessionHasNoErrors();

        $samplingRequest->refresh();

        $this->assertSame(SamplingRequest::PAYMENT_PAID, $samplingRequest->payment_status);
        $this->assertSame(SamplingRequest::STATUS_PAID, $samplingRequest->status);
        $this->assertInstanceOf(Payment::class, $samplingRequest->payment);
        $this->assertSame('Completed', $samplingRequest->payment->status);
        $this->assertSame('Test Sampling Checkout (Midtrans skipped)', $samplingRequest->payment->method);

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
}
