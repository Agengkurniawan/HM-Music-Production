<?php

namespace App\Console\Commands;

use App\Contracts\CatalogEnrichmentProviderInterface;
use App\Models\StyleSampling;
use Illuminate\Console\Command;

class ProbeCatalogEnrichment extends Command
{
    protected $signature = 'styles:ai-enrich-probe
        {--name=Negoro Angin : In-memory Style name}
        {--category=Dangdut : In-memory Style category}
        {--pack=HM Dangdut Expansion Packs : In-memory Sampling Pack}';

    protected $description = 'Test one catalog enrichment request without writing to the database';

    public function handle(CatalogEnrichmentProviderInterface $provider): int
    {
        $style = new StyleSampling([
            'name' => (string) $this->option('name'),
            'category' => (string) $this->option('category'),
            'pack' => (string) $this->option('pack'),
        ]);

        try {
            $result = $provider->enrich($style, false);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], collect([
            'recognized' => ($result['recognized'] ?? false) ? 'true' : 'false',
            'song_title' => $result['song_title'] ?? '-',
            'artist' => $result['artist'] ?? '-',
            'genre' => $result['genre'] ?? '-',
            'aliases' => implode(', ', $result['aliases'] ?? []),
            'search_profile' => $result['search_profile'] ?? '-',
            'verification' => $result['verification'] ?? '-',
            'source' => $result['source'] ?? '-',
        ])->map(fn ($value, $field): array => [$field, $value])->values()->all());

        return self::SUCCESS;
    }
}
