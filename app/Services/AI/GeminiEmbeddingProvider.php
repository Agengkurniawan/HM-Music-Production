<?php

namespace App\Services\AI;

use App\Contracts\EmbeddingProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiEmbeddingProvider implements EmbeddingProviderInterface
{
    public function embed(string $text): array
    {
        $apiKey = (string) config('services.ai_search.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = $this->model();

        try {
            $response = Http::asJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->connectTimeout((int) config('services.ai_search.connect_timeout', 3))
                ->timeout((int) config('services.ai_search.timeout', 10))
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent", [
                    'content' => ['parts' => [['text' => $text]]],
                    'output_dimensionality' => (int) config('services.ai_search.dimensions', 768),
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('The embedding provider could not be reached.', previous: $exception);
        }

        if ($response->failed()) {
            $status = $response->status();

            $message = data_get(
                $response->json(),
                'error.message',
                'Unknown Gemini API error'
            );

            throw new RuntimeException(
                "Gemini embedding failed [HTTP {$status}]: {$message}"
            );
        }

        $values = $response->json('embedding.values');

        if (! is_array($values) || $values === []) {
            throw new RuntimeException('The embedding provider returned an invalid vector.');
        }

        return array_map(static fn ($value): float => (float) $value, $values);
    }

    public function model(): string
    {
        return (string) config('services.ai_search.model', 'gemini-embedding-2');
    }
}
