<?php

namespace Tests\Feature\Admin;

use App\Models\MusicDemo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'status' => 'Published',
            'is_trending' => true,
        ]);
    }

    public function test_verified_admin_can_upload_an_mp4_for_a_demo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo.store'), [
            'title' => 'Dangdut MP4 Demo',
            'youtube_url' => 'https://youtu.be/M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'key_signature' => 'C Minor',
            'status' => 'Published',
            'installation_video' => UploadedFile::fake()->create('demo.mp4', 512, 'video/mp4'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $demo = MusicDemo::where('title', 'Dangdut MP4 Demo')->firstOrFail();

        $this->assertNotNull($demo->installation_video_path);
        Storage::disk('public')->assertExists($demo->installation_video_path);
    }

    public function test_verified_admin_can_add_an_mp4_only_demo_with_optional_metadata_empty(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo.store'), [
            'title' => 'MP4 Only Demo',
            'duration' => '3:24',
            'status' => 'Published',
            'installation_video' => UploadedFile::fake()->create('demo.mp4', 512, 'video/mp4'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $demo = MusicDemo::where('title', 'MP4 Only Demo')->firstOrFail();

        $this->assertNull($demo->youtube_url);
        $this->assertSame(MusicDemo::GENRE_NONE, $demo->genre);
        $this->assertSame(0, $demo->bpm);
        $this->assertNull($demo->key_signature);
        $this->assertNotNull($demo->installation_video_path);
        Storage::disk('public')->assertExists($demo->installation_video_path);
    }

    public function test_verified_admin_must_add_youtube_or_mp4_video(): void
    {
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo.store'), [
            'title' => 'Missing Video Demo',
            'duration' => '3:24',
            'status' => 'Published',
        ]);

        $response->assertSessionHasErrors('youtube_url', null, 'demoCreate');
        $this->assertDatabaseCount('music_demos', 0);
    }

    public function test_verified_admin_can_replace_and_remove_an_mp4(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Storage::disk('public')->put('demos/installations/old.mp4', 'old-video');

        $demo = MusicDemo::create([
            'title' => 'Replaceable Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
            'installation_video_path' => 'demos/installations/old.mp4',
        ]);

        $replaceResponse = $this->actingAs($admin)->put(route('admin.demo.update', $demo), [
            'title' => 'Replaceable Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
            'installation_video' => UploadedFile::fake()->create('new-installation.mp4', 512, 'video/mp4'),
        ]);

        $replaceResponse->assertRedirect();
        $replaceResponse->assertSessionHasNoErrors();

        $demo->refresh();
        $this->assertNotSame('demos/installations/old.mp4', $demo->installation_video_path);
        Storage::disk('public')->assertMissing('demos/installations/old.mp4');
        Storage::disk('public')->assertExists($demo->installation_video_path);

        $newPath = $demo->installation_video_path;

        $removeResponse = $this->actingAs($admin)->put(route('admin.demo.update', $demo), [
            'title' => 'Replaceable Demo',
            'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'genre' => 'Dangdut',
            'bpm' => 140,
            'duration' => '3:24',
            'status' => 'Published',
            'remove_installation_video' => '1',
        ]);

        $removeResponse->assertRedirect();
        $removeResponse->assertSessionHasNoErrors();

        $this->assertNull($demo->fresh()->installation_video_path);
        Storage::disk('public')->assertMissing($newPath);
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

        $response->assertSessionHasErrors('youtube_url', null, 'demoCreate');
        $this->assertDatabaseCount('music_demos', 0);
    }
}
