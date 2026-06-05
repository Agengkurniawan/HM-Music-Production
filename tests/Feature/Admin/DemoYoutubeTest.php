<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoYoutubeTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_admin_can_add_a_youtube_demo(): void
    {
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo.store'), [
            'title' => 'Dangdut YouTube Demo',
            'youtube_url' => 'https://youtu.be/M7lc1UVf-VE',
            'installation_youtube_url' => 'https://www.youtube.com/shorts/M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'key_signature' => 'C Minor',
            'status' => 'Published',
            'trending' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('music_demos', [
            'title' => 'Dangdut YouTube Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'installation_youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'status' => 'Published',
            'is_trending' => true,
        ]);
    }

    public function test_verified_admin_cannot_add_a_non_youtube_video_url(): void
    {
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo.store'), [
            'title' => 'Invalid Demo',
            'youtube_url' => 'https://example.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
        ]);

        $response->assertSessionHasErrors('youtube_url');
        $this->assertDatabaseCount('music_demos', 0);
    }

    public function test_verified_admin_cannot_add_a_non_youtube_installation_url(): void
    {
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo.store'), [
            'title' => 'Invalid Installation Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'installation_youtube_url' => 'https://example.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
        ]);

        $response->assertSessionHasErrors('installation_youtube_url');
        $this->assertDatabaseCount('music_demos', 0);
    }
}
