<?php

namespace Tests\Feature\Admin;

use App\Models\StyleSampling;
use App\Models\User;
use App\Notifications\StyleCatalogUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StyleSamplingUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_admin_can_upload_a_style_sampling(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
        $customer = User::factory()->create([
            'email' => 'customer@gmail.com',
            'role' => 'customer',
            'status' => 'Active',
        ]);
        $suspendedCustomer = User::factory()->create([
            'email' => 'suspended@gmail.com',
            'role' => 'customer',
            'status' => 'Suspended',
        ]);

        Notification::fake();

        $response = $this->actingAs($admin)->post(route('admin.stylesampling.store'), [
            'name' => 'HM Dangdut Raya',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Koplo Expansion Packs',
            'description' => 'Fresh style upload for the catalog.',
            'published' => '1',
            'style_file' => UploadedFile::fake()->create('hm-dangdut-raya.sty', 64),
            'cover_image' => UploadedFile::fake()->image('hm-dangdut-raya.jpg', 640, 420),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('style_samplings', [
            'name' => 'HM Dangdut Raya',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_filename' => 'hm-dangdut-raya.sty',
        ]);

        $styleSampling = StyleSampling::firstOrFail();

        Storage::disk('public')->assertExists($styleSampling->style_file_path);
        Storage::disk('public')->assertExists($styleSampling->cover_image_path);

        Notification::assertSentTo(
            $customer,
            StyleCatalogUpdated::class,
            fn (StyleCatalogUpdated $notification, array $channels): bool => $notification->action === 'added'
                && $notification->styleSampling->is($styleSampling)
                && in_array('mail', $channels, true),
        );
        Notification::assertNotSentTo($suspendedCustomer, StyleCatalogUpdated::class);
        Notification::assertNotSentTo($admin, StyleCatalogUpdated::class);
    }

    public function test_verified_admin_style_update_notifies_customer_emails(): void
    {
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
        $customer = User::factory()->create([
            'email' => 'style-update@gmail.com',
            'role' => 'customer',
            'status' => 'Active',
        ]);
        $styleSampling = StyleSampling::create([
            'name' => 'HM Campursari Lama',
            'category' => 'Campursari',
            'pack' => 'HM Campursari Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_file_path' => 'styles/style-files/hm-campursari-lama.sty',
            'style_filename' => 'hm-campursari-lama.sty',
        ]);

        Notification::fake();

        $response = $this->actingAs($admin)->put(route('admin.stylesampling.update', $styleSampling), [
            'name' => 'HM Campursari Baru',
            'category' => 'Campursari',
            'description' => 'Updated style note.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $customer,
            StyleCatalogUpdated::class,
            fn (StyleCatalogUpdated $notification, array $channels): bool => $notification->action === 'updated'
                && $notification->styleSampling->fresh()->name === 'HM Campursari Baru'
                && in_array('mail', $channels, true),
        );
    }
}
