<?php

namespace App\Services\AI;

use App\Contracts\CatalogEnrichmentProviderInterface;
use App\Models\StyleSampling;

class CatalogEnrichmentService
{
    public function __construct(private readonly CatalogEnrichmentProviderInterface $provider) {}

    public function enrich(StyleSampling $style, bool $force = false): string
    {
        $sourceHash = $this->sourceHash($style);

        if (! $force && $style->ai_enrichment_source_hash === $sourceHash && $style->ai_enrichment_updated_at) {
            return 'skipped';
        }

        $this->markStaleIfNeeded($style, $sourceHash);

        $result = $this->provider->enrich(
            $style,
            (bool) config('services.ai_enrichment.grounding_enabled', false),
        );
        $verification = in_array($result['verification'] ?? null, ['verified', 'probable', 'uncertain', 'unrecognized'], true)
            ? $result['verification']
            : 'uncertain';
        $trusted = ($result['recognized'] ?? false) === true && in_array($verification, ['verified', 'probable'], true);

        $style->forceFill([
            'ai_song_title' => $trusted ? $this->nullableString($result['song_title'] ?? null) : null,
            'ai_artist' => $trusted ? $this->nullableString($result['artist'] ?? null) : null,
            'ai_genre' => $trusted ? $this->nullableString($result['genre'] ?? null) : null,
            'ai_aliases' => $trusted && is_array($result['aliases'] ?? null) ? array_values($result['aliases']) : [],
            'ai_search_references' => $trusted ? $this->references($result['search_references'] ?? []) : [],
            'ai_search_profile' => $trusted ? $this->nullableString($result['search_profile'] ?? null) : null,
            'ai_enrichment_status' => $trusted ? $verification : ($verification === 'unrecognized' ? 'unrecognized' : 'uncertain'),
            'ai_enrichment_source' => $result['source'] ?? 'gemini_knowledge',
            'ai_enrichment_source_hash' => $sourceHash,
            'ai_enrichment_updated_at' => now(),
        ])->saveQuietly();

        return $trusted ? 'enriched' : 'unrecognized';
    }

    public function sourceHash(StyleSampling $style): string
    {
        return hash('sha256', json_encode([
            'name' => $style->name,
            'category' => $style->category,
            'pack' => StyleSampling::normalizeSamplingPackName($style->pack),
            'description' => $style->description,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function markStaleIfNeeded(StyleSampling $style, ?string $sourceHash = null): bool
    {
        $sourceHash ??= $this->sourceHash($style);

        if (! $style->ai_enrichment_updated_at || $style->ai_enrichment_source_hash === $sourceHash) {
            return false;
        }

        $style->forceFill(['ai_enrichment_status' => 'stale'])->saveQuietly();

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** @return array<int, string> */
    private function references(mixed $references): array
    {
        if (! is_array($references)) {
            return [];
        }

        return collect($references)
            ->filter(fn ($reference): bool => is_string($reference) && trim($reference) !== '')
            ->map(fn (string $reference): string => trim($reference))
            ->unique(fn (string $reference): string => mb_strtolower($reference))
            ->take(8)
            ->values()
            ->all();
    }
}
