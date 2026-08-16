<?php

namespace Tests\Unit;

use App\Models\StyleSampling;
use App\Services\AI\TavilyLyricsSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TavilyLyricsSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('services.tavily.enabled', true);
        config()->set('services.tavily.api_key', 'tvly-test-key');
    }

    public function test_it_returns_a_source_verified_song_identification(): void
    {
        Http::fake(['api.tavily.com/*' => Http::response([
            'answer' => "SONG_TITLE: Cidro\nARTIST: Didi Kempot",
            'results' => [[
                'title' => 'Lirik Lagu Cidro - Didi Kempot',
                'content' => 'Informasi lagu Cidro yang dinyanyikan Didi Kempot.',
            ]],
        ])]);

        $result = app(TavilyLyricsSearchService::class)->identify('potongan lirik baru untuk pengujian');

        $this->assertSame('Cidro', $result['song_title']);
        $this->assertSame('Didi Kempot', $result['artist']);
        $this->assertSame('tavily_web_search', $result['source']);
        Http::assertSentCount(1);
    }

    public function test_it_rejects_an_answer_not_supported_by_search_sources(): void
    {
        Http::fake(['api.tavily.com/*' => Http::response([
            'answer' => "SONG_TITLE: Guessed Song\nARTIST: Guessed Artist",
            'results' => [[
                'title' => 'Unrelated search result',
                'content' => 'No matching title or artist here.',
            ]],
        ])]);

        $this->assertNull(app(TavilyLyricsSearchService::class)->identify('potongan lirik berbeda untuk pengujian'));
    }

    public function test_it_caches_identification_to_save_search_credits(): void
    {
        Http::fake(['api.tavily.com/*' => Http::response([
            'answer' => "SONG_TITLE: Cidro\nARTIST: Didi Kempot",
            'results' => [[
                'title' => 'Cidro oleh Didi Kempot',
                'content' => 'Lagu Cidro Didi Kempot.',
            ]],
        ])]);

        $service = app(TavilyLyricsSearchService::class);
        $service->identify('lirik sama yang akan disimpan cache');
        $service->identify('lirik sama yang akan disimpan cache');

        Http::assertSentCount(1);
    }

    public function test_it_does_not_cache_failed_identification(): void
    {
        Http::fakeSequence()
            ->push(['answer' => '', 'results' => []])
            ->push(['answer' => '', 'results' => []]);

        $service = app(TavilyLyricsSearchService::class);
        $service->identify('lirik gagal tidak boleh disimpan');
        $service->identify('lirik gagal tidak boleh disimpan');

        Http::assertSentCount(2);
    }

    public function test_it_identifies_a_verified_catalog_song_from_sources_when_answer_format_is_unstructured(): void
    {
        $this->trustedStyle('Wirang', 'Guyon Waton');
        $this->trustedStyle('Other Song', 'Other Artist');
        Http::fake(['api.tavily.com/*' => Http::response([
            'answer' => 'The excerpt is associated with a song commonly known as Wirang.',
            'results' => [
                ['title' => 'Lirik dan Akor Wirang', 'content' => 'Adheme angin wengi teka.'],
                ['title' => 'Denny Caknan - Wirang', 'content' => 'Official music video and song information.'],
            ],
        ])]);

        $result = app(TavilyLyricsSearchService::class)->identify('Adheme angin wengi teka');

        $this->assertSame('Wirang', $result['song_title']);
        $this->assertSame('Guyon Waton', $result['artist']);
        $this->assertSame('tavily_catalog_grounding', $result['source']);
    }

    public function test_it_can_ground_a_published_style_name_without_ai_metadata_from_two_sources(): void
    {
        StyleSampling::create([
            'name' => 'Sewu Kuto',
            'category' => 'Campursari',
            'pack' => 'HM Campursari Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_file_path' => 'styles/sewu-kuto.sty',
            'style_filename' => 'sewu-kuto.sty',
            'description' => 'Test style without enrichment.',
        ]);
        Http::fake(['api.tavily.com/*' => Http::response([
            'answer' => '',
            'results' => [
                ['title' => 'Lirik Lagu Sewu Kuto', 'content' => 'Informasi lagu dan potongan lirik.'],
                ['title' => 'Sewu Kuto - lagu', 'content' => 'Judul lagu campursari Sewu Kuto.'],
            ],
        ])]);

        $result = app(TavilyLyricsSearchService::class)->identify('potongan lirik sewu kuto yang dicari');

        $this->assertSame('Sewu Kuto', $result['song_title']);
        $this->assertSame('', $result['artist']);
        $this->assertSame('tavily_catalog_grounding', $result['source']);
    }

    private function trustedStyle(string $title, string $artist): StyleSampling
    {
        return StyleSampling::create([
            'name' => '[TEST] '.$title,
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_file_path' => 'styles/'.$title.'.sty',
            'style_filename' => $title.'.sty',
            'description' => 'Test style.',
            'ai_song_title' => $title,
            'ai_artist' => $artist,
            'ai_enrichment_status' => 'verified',
            'ai_enrichment_source' => 'gemini_grounded',
            'ai_enrichment_source_hash' => str_repeat('a', 64),
            'ai_enrichment_updated_at' => now(),
        ]);
    }
}
