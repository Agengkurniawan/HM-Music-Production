<?php

namespace App\Services\AI;

use App\Data\HybridStyleSearchResult;
use App\Models\StyleSampling;
use Illuminate\Support\Collection;

class HybridStyleSearchService
{
    public function __construct(
        private readonly QueryUnderstandingService $understanding,
        private readonly SearchMetadataNormalizer $normalizer,
        private readonly SemanticStyleSearchService $semantic,
    ) {}

    public function search(string $query, ?string $category = null, ?string $pack = null): HybridStyleSearchResult
    {
        $eligible = $this->eligibleCatalog($category, $pack);

        $exact = $this->exactCatalogMatches($eligible, $query);

        if ($exact->isNotEmpty()) {
            return new HybridStyleSearchResult(
                $exact->take((int) config('services.ai_search.top_k', 5))->values(),
                [
                    'intent' => 'song', 'artist' => null, 'song_title' => trim($query),
                    'context' => null, 'semantic_query' => trim($query),
                    'fallback' => false, 'deterministic' => true,
                ],
            );
        }

        $embedded = $this->embeddedCatalogMatches($eligible, $query);

        if ($embedded->isNotEmpty()) {
            return new HybridStyleSearchResult(
                $embedded->take((int) config('services.ai_search.top_k', 5))->values(),
                [
                    'intent' => 'song', 'artist' => null,
                    'song_title' => $embedded->first()->name,
                    'context' => null, 'semantic_query' => trim($query),
                    'fallback' => false, 'deterministic' => true,
                ],
            );
        }

        $direct = $this->directCatalogMatches($eligible, $query);

        if ($direct->isNotEmpty()) {
            return new HybridStyleSearchResult($direct, [
                'intent' => 'catalog', 'artist' => null, 'song_title' => null,
                'context' => null, 'semantic_query' => trim($query),
                'fallback' => false, 'deterministic' => true,
            ]);
        }

        $understanding = $this->understanding->understand($query);
        $understanding['original_query'] = trim($query);

        return match ($understanding['intent']) {
            'artist' => $this->artist($eligible, $understanding, false),
            'artist_context' => $this->artist($eligible, $understanding, true),
            'song' => $this->song($eligible, $understanding, false),
            'lyric' => $this->lyric($eligible, $understanding),
            'song_context' => $this->song($eligible, $understanding, true),
            default => $this->semantic($understanding, $eligible),
        };
    }

    /** @param Collection<int, StyleSampling> $eligible @param array<string, mixed> $understanding */
    private function artist(Collection $eligible, array $understanding, bool $withContext): HybridStyleSearchResult
    {
        $needle = $this->normalizer->normalize($understanding['artist']);
        $artistMatches = $eligible->filter(fn (StyleSampling $style): bool => $style->hasTrustedAiMetadata() && $this->normalizer->normalize($style->ai_artist) === $needle
        )->each->setAttribute('ai_match_label', 'Catalog Artist Match');
        $referenceMatches = $this->referenceMatches($eligible, $this->referenceNeedles($understanding, $needle))
            ->each->setAttribute('ai_match_label', 'Reference Match');
        $candidates = $artistMatches->concat($referenceMatches)->unique('id')->values();

        if ($candidates->isEmpty()) {
            return $this->semanticFallback($understanding, $eligible);
        }

        if ($withContext) {
            return new HybridStyleSearchResult(
                $this->semantic->searchWithin($understanding['semantic_query'], $candidates),
                $understanding,
                'Belum ditemukan Style Sampling artis tersebut yang cukup relevan dengan konteks pencarian Anda.',
            );
        }

        return new HybridStyleSearchResult(
            $candidates->take((int) config('services.ai_search.top_k', 5))->values(),
            $understanding,
        );
    }

    /** @param Collection<int, StyleSampling> $eligible @param array<string, mixed> $understanding */
    private function song(Collection $eligible, array $understanding, bool $withContext): HybridStyleSearchResult
    {
        $needle = $this->normalizer->normalize($understanding['song_title']);
        $songMatches = $eligible->filter(function (StyleSampling $style) use ($needle): bool {
            $aliases = $this->normalizer->aliases($style->ai_aliases ?? []);

            return $style->hasTrustedAiMetadata()
                && ($this->normalizer->normalize($style->ai_song_title) === $needle
                    || in_array($needle, $aliases, true));
        })->each->setAttribute('ai_match_label', 'Catalog Match');
        $referenceMatches = $this->referenceMatches($eligible, $this->referenceNeedles($understanding, $needle))
            ->each->setAttribute('ai_match_label', 'Reference Match');
        $candidates = $songMatches->concat($referenceMatches)->unique('id')->values();

        if ($candidates->isEmpty()) {
            return $this->semanticFallback($understanding, $eligible);
        }

        if ($withContext && $candidates->count() > 1) {
            $candidates = $this->semantic->searchWithin($understanding['semantic_query'], $candidates);
        } else {
            // Deterministic labels were assigned while building the candidates.
        }

        return new HybridStyleSearchResult($candidates->take((int) config('services.ai_search.top_k', 5))->values(), $understanding);
    }

    /** @param Collection<int, StyleSampling> $eligible @param array<string, mixed> $understanding */
    private function lyric(Collection $eligible, array $understanding): HybridStyleSearchResult
    {
        $title = $this->normalizer->normalize($understanding['song_title'] ?? null);

        if (($understanding['identification_confidence'] ?? null) !== 'exact' || $title === '') {
            return new HybridStyleSearchResult(
                collect(),
                $understanding,
                'Potongan lirik belum dapat dikenali dengan yakin sebagai lagu tertentu.',
            );
        }

        $matches = $eligible->filter(function (StyleSampling $style) use ($title): bool {
            $name = $this->normalizer->normalize($style->name);
            $aliases = $this->normalizer->aliases($style->ai_aliases ?? []);

            return $name === $title
                || str_starts_with($name, $title.' ')
                || ($style->hasTrustedAiMetadata()
                    && ($this->normalizer->normalize($style->ai_song_title) === $title
                        || in_array($title, $aliases, true)));
        })->each->setAttribute('ai_match_label', 'Catalog Match')->values();

        return new HybridStyleSearchResult(
            $matches,
            $understanding,
            'Lagunya berhasil dikenali, tetapi Style tersebut belum tersedia di katalog HM Music.',
        );
    }

    /** @param array<string, mixed> $understanding */
    private function semantic(array $understanding, ?Collection $eligible = null): HybridStyleSearchResult
    {
        $styles = $eligible === null
            ? $this->semantic->search($understanding['semantic_query'])
            : $this->semantic->searchWithin($understanding['semantic_query'], $eligible);

        return new HybridStyleSearchResult($styles, $understanding,
            'Belum ditemukan Style Sampling yang cukup relevan dengan pencarian Anda.');
    }

    /** @param Collection<int, StyleSampling> $eligible @return Collection<int, StyleSampling> */
    private function directCatalogMatches(Collection $eligible, string $query): Collection
    {
        $needle = $this->normalizer->normalize($query);

        if ($needle === '') {
            return collect();
        }

        $artistMatches = $eligible->filter(fn (StyleSampling $style): bool => $style->hasTrustedAiMetadata()
            && $this->normalizer->normalize($style->ai_artist) === $needle
        )->each->setAttribute('ai_match_label', 'Catalog Artist Match');
        $referenceMatches = $this->referenceMatches($eligible, [$needle])
            ->each->setAttribute('ai_match_label', 'Reference Match');
        $metadataMatchIds = $artistMatches->concat($referenceMatches)->pluck('id')->all();
        $keywordMatches = $eligible->reject(fn (StyleSampling $style): bool => in_array($style->getKey(), $metadataMatchIds, true))
            ->filter(function (StyleSampling $style) use ($needle): bool {
                $searchable = $this->normalizer->normalize(implode(' ', array_filter([
                    $style->name,
                    $style->category,
                    StyleSampling::samplingPackForCategory($style->category, $style->pack),
                    $style->display_style_name,
                ])));

                return str_contains($searchable, $needle);
            })->each->setAttribute('ai_match_label', 'Catalog Match');

        return $artistMatches
            ->concat($referenceMatches)
            ->concat($keywordMatches)
            ->unique('id')
            ->values();
    }

    /** @param Collection<int, StyleSampling> $eligible @return Collection<int, StyleSampling> */
    private function exactCatalogMatches(Collection $eligible, string $query): Collection
    {
        $needle = $this->normalizer->normalize($query);

        if ($needle === '') {
            return collect();
        }

        $nameMatches = $eligible->filter(fn (StyleSampling $style): bool => $this->normalizer->normalize($style->name) === $needle
        );
        $titleMatches = $eligible->filter(fn (StyleSampling $style): bool => $style->hasTrustedAiMetadata() && $this->normalizer->normalize($style->ai_song_title) === $needle
        );
        $aliasMatches = $eligible->filter(fn (StyleSampling $style): bool => $style->hasTrustedAiMetadata()
            && in_array($needle, $this->normalizer->aliases($style->ai_aliases ?? []), true)
        );

        return $nameMatches
            ->concat($titleMatches)
            ->concat($aliasMatches)
            ->unique('id')
            ->each->setAttribute('ai_match_label', 'Catalog Match')
            ->values();
    }

    /** @param Collection<int, StyleSampling> $eligible @return Collection<int, StyleSampling> */
    private function embeddedCatalogMatches(Collection $eligible, string $query): Collection
    {
        $haystack = $this->normalizer->normalize($query);

        if ($haystack === '') {
            return collect();
        }

        return $eligible->filter(function (StyleSampling $style) use ($haystack): bool {
            $candidates = [$this->normalizer->normalize($style->name)];

            if ($style->hasTrustedAiMetadata()) {
                $candidates[] = $this->normalizer->normalize($style->ai_song_title);
                $candidates = array_merge($candidates, $this->normalizer->aliases($style->ai_aliases ?? []));
            }

            foreach (array_unique(array_filter($candidates)) as $candidate) {
                $words = preg_split('/\s+/u', $candidate, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                if (count($words) >= 2 && str_contains(' '.$haystack.' ', ' '.$candidate.' ')) {
                    return true;
                }
            }

            return false;
        })->each->setAttribute('ai_match_label', 'Catalog Match')->values();
    }

    /** @param Collection<int, StyleSampling> $eligible @return Collection<int, StyleSampling> */
    private function referenceMatches(Collection $eligible, array $needles): Collection
    {
        $needles = array_values(array_unique(array_filter($needles)));

        if ($needles === []) {
            return collect();
        }

        return $eligible->filter(fn (StyleSampling $style): bool => $style->hasTrustedAiMetadata()
            && array_intersect($needles, $this->normalizer->aliases($style->ai_search_references ?? [])) !== []
        )->values();
    }

    /** @param array<string, mixed> $understanding @return array<int, string> */
    private function referenceNeedles(array $understanding, string $entityNeedle): array
    {
        return [
            $entityNeedle,
            $this->normalizer->normalize($understanding['original_query'] ?? null),
        ];
    }

    /** @param array<string, mixed> $understanding */
    private function semanticFallback(array $understanding, Collection $eligible): HybridStyleSearchResult
    {
        $understanding['semantic_query'] = filled($understanding['semantic_query'] ?? null)
            ? $understanding['semantic_query']
            : ($understanding['original_query'] ?? '');

        return $this->semantic($understanding, $eligible);
    }

    /** @return Collection<int, StyleSampling> */
    private function eligibleCatalog(?string $category = null, ?string $pack = null): Collection
    {
        $query = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES);

        if ($category !== null && in_array($category, StyleSampling::CUSTOMER_STYLE_CATEGORIES, true)) {
            $query->where('category', $category);
        }

        if ($pack !== null && in_array($pack, StyleSampling::PACKS, true)) {
            $packCategories = StyleSampling::styleCategoriesForPack($pack);

            if ($packCategories !== []) {
                $query->whereIn('category', $packCategories);
            } else {
                $query->where('pack', $pack);
            }
        }

        return $query->get();
    }
}
