<?php

namespace App\Services\AI;

use App\Contracts\CatalogEnrichmentProviderInterface;
use App\Contracts\QueryUnderstandingProviderInterface;
use App\Models\StyleSampling;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiCatalogEnrichmentProvider implements CatalogEnrichmentProviderInterface, QueryUnderstandingProviderInterface
{
    public function enrich(StyleSampling $style, bool $useGrounding = false): array
    {
        $prompt = <<<'PROMPT'
Analyze this HM Music keyboard Style catalog record. Determine whether the style name confidently refers to a known song. A Style can instead be a genre, rhythm, traditional form, custom name, or internal name. Never invent an artist. If evidence is weak, use recognized=false or verification=uncertain. search_references are search-only names a producer might reasonably use to find this reference; they do not assert an official role. Include only strongly related performer, songwriter, composer, common performer, or reference names, never more than 8, and use an empty array when unsure. Return only the requested structured data.
PROMPT;
        $prompt .= "\nStyle name: {$style->name}\nCategory: {$style->category}\nSampling pack: {$style->pack}\nDescription: ".($style->description ?: 'none');

        $schema = [
            'type' => 'object',
            'properties' => [
                'recognized' => ['type' => 'boolean'],
                'song_title' => ['type' => ['string', 'null']],
                'artist' => ['type' => ['string', 'null']],
                'genre' => ['type' => ['string', 'null']],
                'aliases' => ['type' => 'array', 'items' => ['type' => 'string']],
                'search_references' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 8],
                'search_profile' => ['type' => ['string', 'null']],
                'verification' => ['type' => 'string', 'enum' => ['verified', 'probable', 'uncertain', 'unrecognized']],
            ],
            'required' => ['recognized', 'song_title', 'artist', 'genre', 'aliases', 'search_references', 'search_profile', 'verification'],
        ];

        $response = $this->generate($prompt, $schema, $useGrounding, purpose: 'enrichment');
        $response['source'] = $response['_grounded'] ? 'gemini_grounded' : 'gemini_knowledge';
        unset($response['_grounded']);

        return $response;
    }

    public function understand(string $query): array
    {
        $prompt = <<<'PROMPT'
Classify a customer query for searching an existing HM Music keyboard Style catalog. The query may be a style name, artist, song title, musical context, event need, or a short lyric excerpt. For a lyric excerpt, use Google Search grounding when useful and identify the exact song title and artist from matching evidence, not merely from similar mood, language, genre, or theme. Set intent="lyric" only for an apparent lyric line. Set identification_confidence="exact" only when the excerpt directly matches the identified song; otherwise leave song_title and artist null and set identification_confidence="uncertain". Never reproduce, continue, translate, or return the submitted lyrics. Never suggest a thematically similar song. For non-lyric queries, extract an artist or song only when clearly named. Do not propose catalog results. semantic_query must contain only the identified song/artist or useful musical/event context.
PROMPT;
        $prompt .= "\nCustomer query: {$query}";

        $schema = [
            'type' => 'object',
            'properties' => [
                'intent' => ['type' => 'string', 'enum' => ['artist', 'song', 'lyric', 'artist_context', 'song_context', 'event', 'general']],
                'artist' => ['type' => ['string', 'null']],
                'song_title' => ['type' => ['string', 'null']],
                'context' => ['type' => ['string', 'null']],
                'semantic_query' => ['type' => 'string'],
                'identification_confidence' => ['type' => 'string', 'enum' => ['exact', 'uncertain', 'not_applicable']],
            ],
            'required' => ['intent', 'artist', 'song_title', 'context', 'semantic_query', 'identification_confidence'],
        ];

        $models = (array) config('services.ai_search.query_models', [$this->model()]);
        $queryModels = array_values(array_unique(array_filter($models, 'is_string')));
        $lastException = null;

        foreach ($queryModels as $index => $model) {
            try {
                $result = $this->generate(
                    $prompt,
                    $schema,
                    (bool) config('services.ai_search.lyrics_grounding_enabled', true),
                    $model,
                    'search',
                );

                if (($result['intent'] ?? null) === 'lyric') {
                    if (! ($result['_grounded'] ?? false)) {
                        $result['artist'] = null;
                        $result['song_title'] = null;
                        $result['identification_confidence'] = 'uncertain';
                    }

                    $result['identification_source'] = ($result['_grounded'] ?? false)
                        ? 'google_search_grounding'
                        : 'gemini_ungrounded';
                }

                unset($result['_grounded']);

                return $result;
            } catch (RuntimeException $exception) {
                $lastException = $exception;
                $hasFallback = array_key_exists($index + 1, $queryModels);

                if (! $hasFallback || ! in_array($exception->getCode(), [429, 503], true)) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?? new RuntimeException('No Gemini query model is configured.');
    }

    public function model(): string
    {
        return (string) config('services.ai_enrichment.model', 'gemini-3.6-flash');
    }

    /** @param array<string, mixed> $schema @return array<string, mixed> */
    private function generate(
        string $prompt,
        array $schema,
        bool $grounding,
        ?string $model = null,
        string $purpose = 'enrichment',
    ): array {
        $apiKey = (string) config('services.ai_search.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'responseFormat' => ['text' => ['mimeType' => 'APPLICATION_JSON', 'schema' => $schema]],
                'thinkingConfig' => ['thinkingLevel' => 'minimal'],
            ],
        ];

        if ($grounding) {
            $payload['tools'] = [['google_search' => (object) []]];
        }

        try {
            $configKey = $purpose === 'search' ? 'services.ai_search' : 'services.ai_enrichment';
            $response = Http::asJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->connectTimeout((int) config($configKey.'.connect_timeout', 3))
                ->timeout((int) config($configKey.'.timeout', 20))
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.($model ?: $this->model()).':generateContent', $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('The catalog intelligence provider could not be reached.', previous: $exception);
        }

        if ($response->failed()) {
            $status = $response->status();
            $providerStatus = $this->sanitizeErrorValue($response->json('error.status'));
            $message = $this->sanitizeErrorValue($response->json('error.message'))
                ?: 'Unknown Gemini API error';
            $statusLabel = $providerStatus ? " {$providerStatus}" : '';

            throw new RuntimeException("Gemini catalog enrichment failed [HTTP {$status}{$statusLabel}]: {$message}", $status);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($decoded)) {
            throw new RuntimeException('The catalog intelligence provider returned invalid structured data.');
        }

        $decoded['_grounded'] = filled($response->json('candidates.0.groundingMetadata.groundingChunks'));

        return $decoded;
    }

    private function sanitizeErrorValue(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = preg_replace('/[\r\n\t]+/', ' ', trim($value)) ?? '';

        return mb_substr($value, 0, 500);
    }
}
