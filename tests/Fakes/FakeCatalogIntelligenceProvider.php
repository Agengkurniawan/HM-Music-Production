<?php

namespace Tests\Fakes;

use App\Contracts\CatalogEnrichmentProviderInterface;
use App\Contracts\QueryUnderstandingProviderInterface;
use App\Models\StyleSampling;
use RuntimeException;

class FakeCatalogIntelligenceProvider implements CatalogEnrichmentProviderInterface, QueryUnderstandingProviderInterface
{
    public int $enrichmentCalls = 0;

    public int $understandingCalls = 0;

    public bool $failEnrichment = false;

    public bool $failUnderstanding = false;

    /** @var array<string, mixed> */
    public array $enrichment = [
        'recognized' => true,
        'song_title' => 'Negoro Angin',
        'artist' => 'Denny Caknan',
        'genre' => 'Dangdut Jawa',
        'aliases' => ['Negara Angin'],
        'search_references' => ['Denny Caknan'],
        'search_profile' => 'Lagu pop Jawa modern dengan irama dangdut.',
        'verification' => 'verified',
        'source' => 'gemini_grounded',
    ];

    /** @var array<string, mixed> */
    public array $understanding = [
        'intent' => 'artist',
        'artist' => 'Denny Caknan',
        'song_title' => null,
        'context' => null,
        'semantic_query' => 'Denny Caknan',
    ];

    public function enrich(StyleSampling $style, bool $useGrounding = false): array
    {
        $this->enrichmentCalls++;

        if ($this->failEnrichment) {
            throw new RuntimeException('Fake enrichment failure');
        }

        return $this->enrichment;
    }

    public function understand(string $query): array
    {
        $this->understandingCalls++;

        if ($this->failUnderstanding) {
            throw new RuntimeException('Fake understanding failure');
        }

        return $this->understanding;
    }

    public function model(): string
    {
        return 'fake-generative-model';
    }
}
