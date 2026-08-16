<?php

namespace App\Services\AI;

use App\Contracts\EmbeddingProviderInterface;
use App\Models\StyleSampling;
use Illuminate\Support\Collection;

class SemanticStyleSearchService
{
    public function __construct(
        private readonly EmbeddingProviderInterface $provider,
        private readonly StyleSearchDocumentBuilder $documents,
        private readonly CosineSimilarity $cosine,
    ) {}

    /** @return Collection<int, StyleSampling> */
    public function search(string $query, ?int $limit = null): Collection
    {
        return $this->searchWithin($query, null, $limit);
    }

    /** @param Collection<int, StyleSampling>|null $candidates @return Collection<int, StyleSampling> */
    public function searchWithin(string $query, ?Collection $candidates = null, ?int $limit = null): Collection
    {
        $queryEmbedding = $this->provider->embed($this->documents->query($query));
        $limit ??= (int) config('services.ai_search.top_k', 5);
        $minimum = (float) config('services.ai_search.min_similarity', 0.20);

        $styles = $candidates ?? StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->get();

        return $styles
            ->filter(fn (StyleSampling $style): bool => $style->embedding_model === $this->provider->model() && is_array($style->search_embedding)
            )
            ->filter(function (StyleSampling $style): bool {
                return hash_equals(
                    $style->embedding_source_hash ?: '',
                    $this->documents->sourceHash($style)
                );
            })
            ->map(function (StyleSampling $style) use ($queryEmbedding, $minimum): ?StyleSampling {
                $score = $this->cosine->calculate($queryEmbedding, $style->search_embedding ?? []);

                if ($score === null || $score < $minimum) {
                    return null;
                }

                return $style->setAttribute('ai_similarity', $score);
            })
            ->filter()
            ->sortByDesc('ai_similarity')
            ->take(max(1, $limit))
            ->values();
    }
}
