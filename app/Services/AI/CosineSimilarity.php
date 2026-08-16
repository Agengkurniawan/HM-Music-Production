<?php

namespace App\Services\AI;

class CosineSimilarity
{
    /** @param array<int, mixed> $left @param array<int, mixed> $right */
    public function calculate(array $left, array $right): ?float
    {
        if ($left === [] || count($left) !== count($right)) {
            return null;
        }

        $dot = $leftMagnitude = $rightMagnitude = 0.0;

        foreach ($left as $index => $leftValue) {
            $rightValue = $right[$index] ?? null;

            if (! is_numeric($leftValue) || ! is_numeric($rightValue)) {
                return null;
            }

            $a = (float) $leftValue;
            $b = (float) $rightValue;

            if (! is_finite($a) || ! is_finite($b)) {
                return null;
            }

            $dot += $a * $b;
            $leftMagnitude += $a * $a;
            $rightMagnitude += $b * $b;
        }

        if ($leftMagnitude <= 0.0 || $rightMagnitude <= 0.0) {
            return null;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
