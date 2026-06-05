<?php

namespace Tests\Feature\Customer;

use App\Models\MusicDemo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoYoutubePlaybackTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_demo_page_only_lists_published_demos_with_youtube_videos(): void
    {
        $published = MusicDemo::create([
            'title' => 'Playable Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'installation_youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
        ]);

        MusicDemo::create([
            'title' => 'Legacy Audio Demo',
            'genre' => 'Dangdut',
            'bpm' => 128,
            'duration' => '3:10',
            'status' => 'Published',
        ]);

        $response = $this->get(route('demo'));

        $response->assertOk();
        $response->assertSee('Playable Demo');
        $response->assertDontSee('Legacy Audio Demo');
        $response->assertSee($published->youtube_embed_url);
        $response->assertSee($published->installation_youtube_embed_url);
        $response->assertSee('Instalasi');
        $response->assertDontSee('demo-card__download', false);
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
}
