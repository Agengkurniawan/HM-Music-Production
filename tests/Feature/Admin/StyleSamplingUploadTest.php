<?php

namespace Tests\Feature\Admin;

use App\Models\StyleSampling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
    }
}
