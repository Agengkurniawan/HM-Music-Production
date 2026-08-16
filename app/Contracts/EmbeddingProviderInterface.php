<?php

namespace App\Contracts;

interface EmbeddingProviderInterface
{
    /** @return array<int, float> */
    public function embed(string $text): array;

    public function model(): string;
}
