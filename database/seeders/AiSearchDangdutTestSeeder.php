<?php

namespace Database\Seeders;

use App\Models\StyleSampling;
use App\Services\AI\CatalogEnrichmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiSearchDangdutTestSeeder extends Seeder
{
    public const MARKER = '[AI-TEST-2026]';

    public function run(): void
    {
        $template = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->where(fn ($query) => $query->whereNull('description')->orWhere('description', 'not like', '%'.self::MARKER.'%'))
            ->orderBy('id')
            ->first();

        if (! $template) {
            throw new RuntimeException('AI test catalog needs one original Published Style with a STY file as its template.');
        }

        $rows = DB::transaction(fn (): Collection => $this->seedFromTemplate($template));

        $this->command?->info("AI test catalog ready: {$rows->count()} rows. Template: {$template->name} (#{$template->id}).");
    }

    /** @return Collection<int, StyleSampling> */
    public function seedFromTemplate(StyleSampling $template): Collection
    {
        $enrichment = app(CatalogEnrichmentService::class);

        return collect($this->catalog())->map(function (array $item) use ($template, $enrichment): StyleSampling {
            $category = $item['artist'] === 'Didi Kempot' ? 'Campursari' : 'Dangdut';
            $pack = StyleSampling::samplingPackForCategory($category);
            $displayName = $item['title'].' - '.$item['artist'];
            $description = self::MARKER.' Data dummy katalog untuk pengujian Hybrid Intelligent Search.';
            $nameIsOwnedByOriginalData = StyleSampling::where('name', $displayName)
                ->where(fn ($query) => $query->whereNull('description')->orWhere('description', 'not like', '%'.self::MARKER.'%'))
                ->exists();
            $catalogName = $nameIsOwnedByOriginalData ? $displayName.' (AI Catalog)' : $displayName;

            $style = StyleSampling::query()
                ->where('description', 'like', '%'.self::MARKER.'%')
                ->where('ai_artist', $item['artist'])
                ->where('ai_song_title', $item['title'])
                ->first();

            if (! $style) {
                $style = new StyleSampling([
                    'name' => $catalogName,
                    'access' => $template->access,
                    'status' => 'Published',
                    'style_file_path' => $template->style_file_path,
                    'audio_path' => $template->audio_path,
                    'preview_path' => $template->preview_path,
                    'audio_filename' => $template->audio_filename,
                    'preview_filename' => $template->preview_filename,
                    'file_size' => $template->file_size,
                    'downloads_count' => 0,
                    'ai_enrichment_updated_at' => now(),
                ]);
            }

            $profile = $this->searchProfile($item['title'], $item['artist'], $item['genre']);
            $style->fill([
                'name' => $catalogName,
                'category' => $category,
                'pack' => $pack,
                'style_filename' => $this->styleFilename($catalogName),
                'description' => $description,
                'cover_image_path' => null,
                'cover_image_url' => null,
                'ai_song_title' => $item['title'],
                'ai_artist' => $item['artist'],
                'ai_genre' => $item['genre'],
                'ai_aliases' => $item['aliases'],
                'ai_search_references' => $this->referencesFor($item['title'], $item['artist']),
                'ai_search_profile' => $profile,
                'ai_enrichment_status' => 'verified',
                'ai_enrichment_source' => 'test_seed',
            ]);
            $style->ai_enrichment_source_hash = $enrichment->sourceHash($style);
            $style->saveQuietly();

            return $style;
        })->values();
    }

    /** @return array<int, array{title: string, artist: string, genre: string, aliases: array<int, string>}> */
    private function catalog(): array
    {
        return collect([
            'Denny Caknan' => [
                'genre' => 'Dangdut Jawa',
                'songs' => ['Negoro Angin', 'Sinarengan', 'Ropang', 'Tunggal Eka', 'Sigar', 'Laut Kidul', 'Cundamani', 'LDR (Langgeng Dayaning Rasa)', 'Satru 2', 'Kartonyono Medot Janji'],
            ],
            'Didi Kempot' => [
                'genre' => 'Campursari / Dangdut Jawa',
                'songs' => ['Pamer Bojo', 'Banyu Langit', 'Sewu Kutho', 'Stasiun Balapan', 'Cidro', 'Suket Teki', 'Layang Kangen', 'Tanjung Mas Ninggal Janji', 'Parangtritis', 'Kalung Emas'],
            ],
            'Happy Asmara' => [
                'genre' => 'Dangdut Koplo / Pop Jawa',
                'songs' => ['Rungkad', 'Kalah', 'Sadar Posisi', 'Kembang Wangi', 'Tak Ikhlasno', 'Dalan Liyane', 'Kari Cerito', 'Jangan Tunggu Lama Lama'],
            ],
            'Gilga Sahid' => [
                'genre' => 'Dangdut Jawa / Pop Jawa',
                'songs' => ['Nemu', 'Nemen', 'Demi Kowe', 'Rucah', 'ASMORO', 'Jajalen Aku'],
            ],
            'Guyon Waton' => [
                'genre' => 'Pop Jawa / Dangdut Jawa',
                'songs' => ['Sanes', 'Pelanggaran', 'Klebus', 'Wirang', 'Korban Janji', 'Gampil', 'Pingal', 'Silul'],
            ],
            'Ndarboy Genk' => [
                'genre' => 'Dangdut Jawa',
                'songs' => ['Mendung Tanpo Udan', 'Ojo Nangis', 'Rungokno Aku', 'Kicau Mania', 'Sikep (Siap Kelangan Pengarep Arep)', 'Lanang Tenan'],
            ],
            'NDX A.K.A.' => [
                'genre' => 'Hip Hop Dangdut',
                'songs' => ['Kelingan Mantan', 'Tresno Tekan Mati', 'Nemen (Hiphop Dangdut Version)', 'Ngertenono Ati', 'Ditinggal Rabi', 'Pamit Kerjo', 'Apa Kabar Mantan', 'Demi Kowe'],
            ],
            'Lavora' => [
                'genre' => 'Pop Dangdut Jawa',
                'songs' => ['Rasah Bali', 'Sewates Konco', 'Nresnani', 'HTS', 'Durung Ikhlas', 'Pamit Rabi', 'Tamu Undangan'],
            ],
        ])->flatMap(function (array $group, string $artist): array {
            return collect($group['songs'])->map(fn (string $title): array => [
                'title' => $title,
                'artist' => $artist,
                'genre' => $group['genre'],
                'aliases' => $this->aliasesFor($title),
            ])->all();
        })->values()->all();
    }

    /** @return array<int, string> */
    private function aliasesFor(string $title): array
    {
        return match ($title) {
            'LDR (Langgeng Dayaning Rasa)' => ['LDR', 'Langgeng Dayaning Rasa'],
            'Sikep (Siap Kelangan Pengarep Arep)' => ['Sikep', 'Siap Kelangan Pengarep Arep'],
            'Nemen (Hiphop Dangdut Version)' => ['Nemen Hiphop Dangdut Version'],
            default => [],
        };
    }

    private function searchProfile(string $title, string $artist, string $genre): string
    {
        return "{$title} oleh {$artist}. Katalog pengujian {$genre}. Digunakan untuk pengujian pencarian artis {$artist}, judul {$title}, musik Jawa, dangdut Jawa, dan Style Sampling.";
    }

    private function styleFilename(string $displayName): string
    {
        $filename = str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '-', $displayName);
        $filename = trim(preg_replace('/\s+/u', ' ', $filename) ?? '', " .");

        return ($filename !== '' ? $filename : 'HM Music Style').'.STY';
    }

    /** @return array<int, string> */
    private function referencesFor(string $title, string $artist): array
    {
        if ($title === 'Pingal') {
            return ['Guyon Waton', 'Ngatmombilung', 'Andry Priyanta'];
        }

        return [$artist];
    }
}
