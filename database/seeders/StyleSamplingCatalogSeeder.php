<?php

namespace Database\Seeders;

use App\Models\DownloadSale;
use App\Models\StyleSampling;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StyleSamplingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        self::seed();
    }

    public static function seed(bool $reset = true): Collection
    {
        if ($reset) {
            DownloadSale::query()->delete();
            StyleSampling::query()->delete();
        }

        $packs = [
            'gamelan' => 'HM Gamelan Expansion Packs',
            'campursari' => 'HM Campursari Expansion Packs',
            'dangdut' => 'HM Dangdut Expansion Packs',
        ];

        $catalog = collect([
            [
                'category' => 'Dangdut',
                'pack' => $packs['dangdut'],
                'base' => 'Dangdut Raya',
                'cover' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=720&h=480&fit=crop',
                'base_size' => 70,
                'bpm' => 140,
            ],
            [
                'category' => 'Campursari',
                'pack' => $packs['campursari'],
                'base' => 'Campursari Senja',
                'cover' => 'https://images.unsplash.com/photo-1518972559570-7cc1309f3229?w=720&h=480&fit=crop',
                'base_size' => 76,
                'bpm' => 128,
            ],
            [
                'category' => 'Gamelan',
                'pack' => $packs['gamelan'],
                'base' => 'Gamelan Modern',
                'cover' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=720&h=480&fit=crop',
                'base_size' => 82,
                'bpm' => 104,
            ],
        ]);

        $previewSource = public_path('audio/audio2.mp3');
        $previewBytes = file_exists($previewSource) ? file_get_contents($previewSource) : '';

        return $catalog
            ->flatMap(function (array $group) use ($previewBytes): Collection {
                return collect(range(1, 10))->map(function (int $number) use ($group, $previewBytes): StyleSampling {
                    $name = sprintf('HM %s %02d', $group['base'], $number);
                    $slug = str($name)->slug('-')->toString();
                    $stylePath = "styles/style-files/{$slug}.sty";
                    $previewPath = "styles/previews/preview-{$slug}.mp3";

                    Storage::disk('public')->put($stylePath, "Temporary STY placeholder for {$name}.\n");

                    if ($previewBytes !== '') {
                        Storage::disk('public')->put($previewPath, $previewBytes);
                    }

                    return StyleSampling::create([
                        'name' => $name,
                        'category' => $group['category'],
                        'pack' => $group['pack'],
                        'access' => $number % 4 === 0 ? 'Free' : 'Premium',
                        'status' => 'Published',
                        'style_file_path' => $stylePath,
                        'preview_path' => $previewBytes !== '' ? $previewPath : null,
                        'cover_image_url' => $group['cover'],
                        'style_filename' => "{$slug}.sty",
                        'preview_filename' => "preview-{$slug}.mp3",
                        'file_size' => ($group['base_size'] + ($number * 2)).' MB',
                        'description' => "{$group['category']} style for {$group['pack']} at {$group['bpm']} BPM. The matching sampling pack provides the voice kit connected through the N27 workflow.",
                        'downloads_count' => 120 + ($number * 17),
                    ]);
                });
            })
            ->values();
    }
}
