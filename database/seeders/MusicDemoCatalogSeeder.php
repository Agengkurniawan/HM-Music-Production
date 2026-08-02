<?php

namespace Database\Seeders;

use App\Models\MusicDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class MusicDemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        self::seed();
    }

    public static function seed(bool $reset = true): Collection
    {
        if ($reset) {
            MusicDemo::query()->delete();
        }

        $demos = collect([
            [
                'title' => 'Dangdut Raya',
                'genre' => 'Dangdut',
                'bpm' => 140,
                'duration' => '3:24',
                'key_signature' => 'C Minor',
                'is_trending' => true,
                'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
                'plays_count' => 3200,
            ],
            [
                'title' => 'Dangdut Koplo Studio',
                'genre' => 'Dangdut',
                'bpm' => 146,
                'duration' => '3:38',
                'key_signature' => 'G Minor',
                'is_trending' => false,
                'youtube_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
                'plays_count' => 1840,
            ],
            [
                'title' => 'Campursari Senja',
                'genre' => 'Campursari',
                'bpm' => 128,
                'duration' => '4:12',
                'key_signature' => 'A Minor',
                'is_trending' => true,
                'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'plays_count' => 2100,
            ],
            [
                'title' => 'Campursari Kendang Klasik',
                'genre' => 'Campursari',
                'bpm' => 122,
                'duration' => '3:56',
                'key_signature' => 'E Minor',
                'is_trending' => false,
                'youtube_url' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                'plays_count' => 1260,
            ],
            [
                'title' => 'Gamelan Modern',
                'genre' => 'Gamelan',
                'bpm' => 104,
                'duration' => '3:52',
                'key_signature' => 'D Minor',
                'is_trending' => true,
                'youtube_url' => 'https://www.youtube.com/watch?v=ysz5S6PUM-U',
                'plays_count' => 1700,
            ],
            [
                'title' => 'Gamelan Keraton Digital',
                'genre' => 'Gamelan',
                'bpm' => 96,
                'duration' => '4:05',
                'key_signature' => 'F Minor',
                'is_trending' => false,
                'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
                'plays_count' => 940,
            ],
        ]);

        return $demos
            ->map(fn (array $demo): MusicDemo => MusicDemo::create([
                ...$demo,
                'status' => 'Published',
            ]))
            ->values();
    }
}
