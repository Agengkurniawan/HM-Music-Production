<?php

namespace App\Contracts;

interface QueryUnderstandingProviderInterface
{
    /** @return array<string, mixed> */
    public function understand(string $query): array;
}
