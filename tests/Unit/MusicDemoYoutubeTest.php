<?php

namespace Tests\Unit;

use App\Models\MusicDemo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MusicDemoYoutubeTest extends TestCase
{
    #[DataProvider('youtubeUrls')]
    public function test_it_extracts_video_ids_from_supported_youtube_urls(string $url): void
    {
        $this->assertSame('M7lc1UVf-VE', MusicDemo::extractYoutubeVideoId($url));
    }

    public static function youtubeUrls(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=M7lc1UVf-VE'],
            'short link' => ['https://youtu.be/M7lc1UVf-VE'],
            'shorts' => ['https://www.youtube.com/shorts/M7lc1UVf-VE'],
            'live' => ['https://www.youtube.com/live/M7lc1UVf-VE'],
            'embed' => ['https://www.youtube.com/embed/M7lc1UVf-VE'],
        ];
    }

    public function test_it_rejects_non_youtube_urls(): void
    {
        $this->assertNull(MusicDemo::extractYoutubeVideoId('https://example.com/watch?v=M7lc1UVf-VE'));
    }
}
