<?php

namespace App\Contracts;

use App\Models\StyleSampling;

interface CatalogEnrichmentProviderInterface
{
    /** @return array<string, mixed> */
    public function enrich(StyleSampling $style, bool $useGrounding = false): array;

    public function model(): string;
}
