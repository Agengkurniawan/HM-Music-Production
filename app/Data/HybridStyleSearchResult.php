<?php

namespace App\Data;

use App\Models\StyleSampling;
use Illuminate\Support\Collection;

class HybridStyleSearchResult
{
    /** @param Collection<int, StyleSampling> $styles @param array<string, mixed> $understanding */
    public function __construct(
        public readonly Collection $styles,
        public readonly array $understanding,
        public readonly ?string $emptyMessage = null,
    ) {}
}
