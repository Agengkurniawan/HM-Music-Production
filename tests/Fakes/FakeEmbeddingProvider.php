<?php

namespace Tests\Fakes;

use App\Contracts\EmbeddingProviderInterface;
use RuntimeException;

class FakeEmbeddingProvider implements EmbeddingProviderInterface
{
    public int $calls = 0;

    public bool $shouldFail = false;

    /** @var callable(string): array<int, float>|null */
    public $resolver = null;

    /** @param array<int, float> $vector */
    public function __construct(public array $vector = [1.0, 0.0, 0.0]) {}

    public function embed(string $text): array
    {
        $this->calls++;

        if ($this->shouldFail) {
            throw new RuntimeException('Fake provider failure');
        }

        return $this->resolver ? ($this->resolver)($text) : $this->vector;
    }

    public function model(): string
    {
        return 'fake-embedding-model';
    }
}
