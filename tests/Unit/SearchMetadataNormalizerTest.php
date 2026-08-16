<?php

namespace Tests\Unit;

use App\Services\AI\SearchMetadataNormalizer;
use PHPUnit\Framework\TestCase;

class SearchMetadataNormalizerTest extends TestCase
{
    public function test_it_normalizes_case_whitespace_and_punctuation(): void
    {
        $normalizer = new SearchMetadataNormalizer;

        $this->assertSame('denny caknan', $normalizer->normalize('  DENNY--Caknan! '));
        $this->assertSame(['negoro angin'], $normalizer->aliases(['Negoro Angin', 'negoro-angin']));
    }
}
