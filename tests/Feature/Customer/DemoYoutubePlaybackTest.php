<?php

namespace Tests\Feature\Customer;

use App\Models\MusicDemo;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoYoutubePlaybackTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_demo_page_lists_published_demos_with_youtube_or_mp4_video(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('demos/videos/mp4-only.mp4', 'mp4-video');

        $published = MusicDemo::create([
            'title' => 'Playable Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
        ]);

        $mp4Only = MusicDemo::create([
            'title' => 'MP4 Only Demo',
            'genre' => MusicDemo::GENRE_NONE,
            'bpm' => 0,
            'duration' => '3:10',
            'status' => 'Published',
            'installation_video_path' => 'demos/videos/mp4-only.mp4',
        ]);

        MusicDemo::create([
            'title' => 'Legacy Audio Demo',
            'genre' => 'Dangdut',
            'bpm' => 128,
            'duration' => '3:10',
            'status' => 'Published',
        ]);

        MusicDemo::create([
            'title' => 'Outside Genre Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'genre' => 'Koplo',
            'bpm' => 128,
            'duration' => '3:10',
            'status' => 'Published',
        ]);

        $response = $this->get(route('demo'));

        $response->assertOk();
        $response->assertSee('Playable Demo');
        $response->assertSee('MP4 Only Demo');
        $response->assertDontSee('Legacy Audio Demo');
        $response->assertDontSee('Outside Genre Demo');
        $response->assertSee($published->youtube_embed_url);
        $response->assertSee('class="demo-card__thumbnail-video"', false);
        $response->assertSee('src="'.$mp4Only->installation_video_url.'#t=0.1"', false);
        $response->assertSee('data-mp4-video-url="'.$mp4Only->installation_video_url.'"', false);
        $response->assertDontSee('class="demo-card__install"', false);
        $response->assertDontSee('demo-card__download', false);
    }

    public function test_customer_demo_page_shows_mp4_button_when_available_with_youtube(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('demos/videos/demo.mp4', 'mp4-video');

        $demo = MusicDemo::create([
            'title' => 'Playable Demo With MP4',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
            'installation_video_path' => 'demos/videos/demo.mp4',
        ]);

        $response = $this->get(route('demo'));

        $response->assertOk();
        $response->assertSee('Playable Demo With MP4');
        $response->assertSee('class="demo-card__install"', false);
        $response->assertSee('MP4 Video');
        $response->assertSee('data-mp4-video-url="'.$demo->installation_video_url.'"', false);
        $response->assertSee('<video title="Playable Demo With MP4 MP4 video"', false);
    }

    public function test_customer_demo_page_renders_genre_filter_hooks(): void
    {
        foreach (MusicDemo::GENRES as $genre) {
            MusicDemo::create([
                'title' => $genre.' Demo',
                'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
                'genre' => $genre,
                'bpm' => 120,
                'duration' => '3:24',
                'status' => 'Published',
            ]);
        }

        $response = $this->get(route('demo'));

        $response->assertOk();
        $response->assertSee('data-demo-filter="All"', false);

        foreach (MusicDemo::GENRES as $genre) {
            $response->assertSee('data-demo-filter="'.$genre.'"', false);
            $response->assertSee('data-demo-category="'.$genre.'"', false);
            $response->assertSee($genre.' Demo');
        }

        $response->assertSee('function filterDemos', false);
    }

    public function test_customer_demo_header_hides_subscribe_offer_for_active_subscriber(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Premium Monthly',
        ]);

        Subscription::create([
            'user_id' => $customer->id,
            'package' => 'Premium Monthly',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => 'Active',
        ]);

        $response = $this->actingAs($customer)->get(route('demo'));

        $response->assertOk();
        $response->assertDontSee('Subscribe');
        $response->assertDontSee('Continue to subscription');
        $response->assertDontSee('Login');
        $response->assertSee('Dashboard');
        $response->assertSee('active STY access');
    }

    public function test_customer_demo_header_keeps_subscribe_offer_for_logged_in_customer_without_active_subscription(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
            'plan' => 'Free',
        ]);

        Subscription::create([
            'user_id' => $customer->id,
            'package' => 'Premium Monthly',
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
            'status' => 'Active',
        ]);

        $response = $this->actingAs($customer)->get(route('demo'));

        $response->assertOk();
        $response->assertSee('Subscribe');
        $response->assertSee('Continue to subscription');
        $response->assertDontSee('Login');
        $response->assertSee('Dashboard');
    }

    public function test_opening_a_youtube_player_records_a_play(): void
    {
        $demo = MusicDemo::create([
            'title' => 'Playable Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
        ]);

        $this->postJson(route('demo.play', $demo))
            ->assertOk()
            ->assertJson(['plays_count' => 1]);

        $this->assertSame(1, $demo->fresh()->plays_count);
    }

    public function test_opening_an_mp4_player_records_a_play(): void
    {
        $demo = MusicDemo::create([
            'title' => 'MP4 Playable Demo',
            'genre' => MusicDemo::GENRE_NONE,
            'bpm' => 0,
            'duration' => '3:24',
            'status' => 'Published',
            'installation_video_path' => 'demos/videos/demo.mp4',
        ]);

        $this->postJson(route('demo.play', $demo))
            ->assertOk()
            ->assertJson(['plays_count' => 1]);

        $this->assertSame(1, $demo->fresh()->plays_count);
    }
}
