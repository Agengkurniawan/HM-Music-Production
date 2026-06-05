<?php

namespace Tests\Feature\Customer;

use App\Models\StyleSampling;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StyleSamplingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_style_download_requires_active_subscription(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('styles/hm-subscription.sty', 'style-file');

        $style = StyleSampling::create([
            'name' => 'HM Subscription Style',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Koplo Expansion Packs',
            'access' => 'Free',
            'status' => 'Published',
            'style_file_path' => 'styles/hm-subscription.sty',
            'style_filename' => 'hm-subscription.sty',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        $this->actingAs($customer)
            ->get(route('stylesampling', ['type' => 'style']))
            ->assertOk()
            ->assertSee('HM Subscription Style')
            ->assertSee('Unlock STY')
            ->assertSee('Subscription')
            ->assertDontSee('MP3 Preview')
            ->assertDontSee('<audio', false);

        $this->actingAs($customer)
            ->get(route('stylesampling.download.style', $style))
            ->assertRedirect(route('subcription'));

        Subscription::create([
            'user_id' => $customer->id,
            'package' => 'Premium Monthly',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => 'Active',
        ]);

        $this->actingAs($customer)
            ->get(route('stylesampling.download.style', $style))
            ->assertOk()
            ->assertDownload('hm-subscription.sty');
    }
}
