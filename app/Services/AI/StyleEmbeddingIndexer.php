<?php

namespace App\Services\AI;

use App\Contracts\EmbeddingProviderInterface;
use App\Models\StyleSampling;

class StyleEmbeddingIndexer
{
    public function __construct(
        private readonly EmbeddingProviderInterface $provider,
        private readonly StyleSearchDocumentBuilder $documents,
    ) {}

    public function index(StyleSampling $style, bool $force = false): bool
    {
        $hash = $this->documents->sourceHash($style);

        if (! $force && $this->isFresh($style, $hash)) {
            return false;
        }

        $embedding = $this->provider->embed($this->documents->build($style));

        $style->forceFill([
            'search_embedding' => $embedding,
            'embedding_model' => $this->provider->model(),
            'embedding_source_hash' => $hash,
            'embedding_updated_at' => now(),
        ])->saveQuietly();

        return true;
    }

    public function isFresh(StyleSampling $style, ?string $hash = null): bool
    {
        return is_array($style->search_embedding)
            && $style->search_embedding !== []
            && hash_equals($style->embedding_source_hash ?: '', $hash ?: $this->documents->sourceHash($style))
            && $style->embedding_model === $this->provider->model();
    }
}
