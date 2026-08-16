<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

class SearchMetadataNormalizer
{
    public function normalize(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /** @param array<int, mixed> $aliases */
    public function aliases(array $aliases): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($alias): string => $this->normalize(is_string($alias) ? $alias : null),
            $aliases,
        ))));
    }
}
