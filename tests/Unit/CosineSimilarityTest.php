<?php

namespace Tests\Unit;

use App\Services\AI\CosineSimilarity;
use PHPUnit\Framework\TestCase;

class CosineSimilarityTest extends TestCase
{
    public function test_identical_vectors_score_one_and_different_vectors_score_lower(): void
    {
        $cosine = new CosineSimilarity;

        $this->assertEqualsWithDelta(1.0, $cosine->calculate([1, 2, 3], [1, 2, 3]), 0.000001);
        $this->assertLessThan(1.0, $cosine->calculate([1, 0], [0.5, 0.5]));
    }

    public function test_invalid_vectors_are_safe(): void
    {
        $cosine = new CosineSimilarity;

        $this->assertNull($cosine->calculate([], []));
        $this->assertNull($cosine->calculate([0, 0], [1, 1]));
        $this->assertNull($cosine->calculate([1], [1, 2]));
        $this->assertNull($cosine->calculate([1, 'invalid'], [1, 2]));
    }
}
