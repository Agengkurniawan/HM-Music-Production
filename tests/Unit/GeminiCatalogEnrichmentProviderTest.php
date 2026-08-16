<?php

namespace Tests\Unit;

use App\Models\StyleSampling;
use App\Services\AI\GeminiCatalogEnrichmentProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GeminiCatalogEnrichmentProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.ai_search.api_key', 'fake-test-key');
        config()->set('services.ai_enrichment.model', 'gemini-3.6-flash');
        config()->set('services.ai_search.query_models', ['gemini-3.6-flash', 'gemini-3.5-flash-lite']);
    }

    public function test_it_sends_gemini_36_compatible_structured_output_payload(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'recognized' => true,
                        'song_title' => 'Negoro Angin',
                        'artist' => 'Denny Caknan',
                        'genre' => 'Dangdut Jawa',
                        'aliases' => [],
                        'search_references' => ['Denny Caknan'],
                        'search_profile' => 'Lagu pop Jawa.',
                        'verification' => 'verified',
                    ])]]],
                ]],
            ]),
        ]);

        $result = app(GeminiCatalogEnrichmentProvider::class)->enrich($this->style());

        $this->assertSame('Denny Caknan', $result['artist']);
        Http::assertSent(function (Request $request): bool {
            $config = $request->data()['generationConfig'] ?? [];

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent'
                && data_get($config, 'responseFormat.text.mimeType') === 'APPLICATION_JSON'
                && data_get($config, 'thinkingConfig.thinkingLevel') === 'minimal'
                && data_get($config, 'responseFormat.text.schema.properties.search_references.maxItems') === 8
                && ! array_key_exists('temperature', $config)
                && ! array_key_exists('topP', $config)
                && ! array_key_exists('topK', $config)
                && ! array_key_exists('candidateCount', $config)
                && ! array_key_exists('thinkingBudget', $config);
        });
    }

    public function test_it_reports_sanitized_gemini_http_error_details(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => "Invalid structured output value.\nTry again.",
                    'status' => 'INVALID_ARGUMENT',
                ],
            ], 400),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Gemini catalog enrichment failed [HTTP 400 INVALID_ARGUMENT]: Invalid structured output value. Try again.'
        );

        app(GeminiCatalogEnrichmentProvider::class)->enrich($this->style());
    }

    public function test_lyric_grounding_falls_back_to_another_model_after_rate_limit(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded']], 429)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'intent' => 'lyric',
                        'artist' => 'Didi Kempot',
                        'song_title' => 'Cidro',
                        'context' => null,
                        'semantic_query' => 'Cidro Didi Kempot',
                        'identification_confidence' => 'exact',
                    ])]]],
                    'groundingMetadata' => ['groundingChunks' => [['web' => ['uri' => 'https://example.test']]]],
                ]],
            ]);

        $result = app(GeminiCatalogEnrichmentProvider::class)->understand('potongan lirik baru di sini');

        $this->assertSame('Cidro', $result['song_title']);
        $this->assertSame('exact', $result['identification_confidence']);
        $this->assertSame('google_search_grounding', $result['identification_source']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'gemini-3.5-flash-lite:generateContent'));
    }

    public function test_lyric_identification_is_rejected_without_google_grounding_evidence(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'intent' => 'lyric',
                        'artist' => 'Guessed Artist',
                        'song_title' => 'Guessed Song',
                        'context' => null,
                        'semantic_query' => 'Guessed Song',
                        'identification_confidence' => 'exact',
                    ])]]],
                ]],
            ]),
        ]);

        $result = app(GeminiCatalogEnrichmentProvider::class)->understand('potongan lirik yang tidak grounded');

        $this->assertNull($result['song_title']);
        $this->assertNull($result['artist']);
        $this->assertSame('uncertain', $result['identification_confidence']);
        $this->assertSame('gemini_ungrounded', $result['identification_source']);
    }

    private function style(): StyleSampling
    {
        return new StyleSampling([
            'name' => 'Negoro Angin',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
        ]);
    }
}
