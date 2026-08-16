<?php

namespace Tests\Feature;

use App\Contracts\CatalogEnrichmentProviderInterface;
use App\Contracts\EmbeddingProviderInterface;
use App\Contracts\QueryUnderstandingProviderInterface;
use App\Models\StyleSampling;
use App\Models\User;
use App\Services\AI\CatalogEnrichmentService;
use App\Services\AI\HybridStyleSearchService;
use App\Services\AI\StyleEmbeddingIndexer;
use App\Services\AI\StyleSearchDocumentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeCatalogIntelligenceProvider;
use Tests\Fakes\FakeEmbeddingProvider;
use Tests\TestCase;

class HybridAiStyleSearchTest extends TestCase
{
    use RefreshDatabase;

    private FakeCatalogIntelligenceProvider $intelligence;

    private FakeEmbeddingProvider $embeddings;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.ai_search.enabled', true);
        config()->set('services.ai_search.top_k', 5);
        config()->set('services.ai_search.min_similarity', 0.20);
        config()->set('services.tavily.enabled', false);
        config()->set('services.ai_enrichment.enabled', true);
        config()->set('hm.admin_email', 'admin@hmmusic.test');

        $this->intelligence = new FakeCatalogIntelligenceProvider;
        $this->embeddings = new FakeEmbeddingProvider([1.0, 0.0]);
        $this->app->instance(CatalogEnrichmentProviderInterface::class, $this->intelligence);
        $this->app->instance(QueryUnderstandingProviderInterface::class, $this->intelligence);
        $this->app->instance(EmbeddingProviderInterface::class, $this->embeddings);
    }

    public function test_recognized_catalog_enrichment_is_stored_and_changes_embedding_source_hash(): void
    {
        $style = $this->style(['name' => 'Negoro Angin']);
        $documents = app(StyleSearchDocumentBuilder::class);
        $oldHash = $documents->sourceHash($style);
        app(StyleEmbeddingIndexer::class)->index($style);

        $this->assertSame('enriched', app(CatalogEnrichmentService::class)->enrich($style));
        $style->refresh();

        $this->assertSame('Denny Caknan', $style->ai_artist);
        $this->assertSame('Negoro Angin', $style->ai_song_title);
        $this->assertSame('Lagu pop Jawa modern dengan irama dangdut.', $style->ai_search_profile);
        $this->assertSame(['Denny Caknan'], $style->ai_search_references);
        $this->assertSame('verified', $style->ai_enrichment_status);
        $this->assertNotSame($oldHash, $documents->sourceHash($style));
        $this->assertFalse(app(StyleEmbeddingIndexer::class)->isFresh($style));
    }

    public function test_uncertain_or_unrecognized_style_is_not_forced_to_have_artist(): void
    {
        $this->intelligence->enrichment = [
            'recognized' => true, 'song_title' => 'Guess', 'artist' => 'Guess Artist',
            'genre' => null, 'aliases' => [], 'search_profile' => null,
            'verification' => 'uncertain', 'source' => 'gemini_knowledge',
        ];
        $style = $this->style(['name' => 'Custom Internal Rhythm']);

        $this->assertSame('unrecognized', app(CatalogEnrichmentService::class)->enrich($style));
        $this->assertNull($style->refresh()->ai_artist);
        $this->assertNull($style->ai_song_title);
        $this->assertSame('uncertain', $style->ai_enrichment_status);
    }

    public function test_enrichment_command_skips_fresh_and_force_runs_again(): void
    {
        $this->style(['name' => 'Negoro Angin']);

        $this->artisan('styles:ai-enrich')->assertSuccessful();
        $this->artisan('styles:ai-enrich')->assertSuccessful();
        $this->assertSame(1, $this->intelligence->enrichmentCalls);

        $this->artisan('styles:ai-enrich', ['--force' => true])->assertSuccessful();
        $this->assertSame(2, $this->intelligence->enrichmentCalls);
    }

    public function test_enrichment_failure_does_not_rollback_admin_style_update(): void
    {
        $style = $this->enrichedStyle('Old Recognized Song', 'Old Artist');
        $style->update(['status' => 'Draft']);
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'), 'email_verified_at' => now(),
            'role' => 'admin', 'status' => 'Active',
        ]);
        $this->intelligence->failEnrichment = true;

        $this->actingAs($admin)->put(route('admin.stylesampling.update', $style), [
            'name' => 'Saved Despite Enrichment Failure', 'category' => 'Dangdut', 'description' => 'Safe',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Saved Despite Enrichment Failure', $style->refresh()->name);
        $this->assertSame('stale', $style->ai_enrichment_status);
        $this->assertFalse($style->hasTrustedAiMetadata());
    }

    public function test_artist_query_is_case_insensitive_catalog_only_and_does_not_force_top_k(): void
    {
        $dennyOne = $this->enrichedStyle('Negoro Angin', 'Denny Caknan');
        $dennyTwo = $this->enrichedStyle('Sinarengan', 'DENNY CAKNAN');
        $this->enrichedStyle('Pamer Bojo', 'Didi Kempot');
        $this->intelligence->understanding = $this->understanding('artist', artist: 'denny caknan');

        $result = app(HybridStyleSearchService::class)->search('DENNY CAKNAN');

        $this->assertSame([$dennyOne->id, $dennyTwo->id], $result->styles->pluck('id')->all());
        $this->assertCount(2, $result->styles);
        $this->assertSame('Catalog Artist Match', $result->styles->first()->ai_match_label);
        $this->assertSame(0, $this->intelligence->understandingCalls);
    }

    public function test_lyric_identification_maps_identified_song_back_to_catalog(): void
    {
        $song = $this->enrichedStyle('Pamer Bojo Style', 'Didi Kempot', 'Pamer Bojo');
        $this->intelligence->understanding = $this->understanding('lyric', artist: 'Didi Kempot', song: 'Pamer Bojo');

        $result = app(HybridStyleSearchService::class)->search('potongan lirik yang dikenali provider');

        $this->assertSame([$song->id], $result->styles->pluck('id')->all());
        $this->assertSame('lyric', $result->understanding['intent']);
        $this->assertSame('Pamer Bojo', $result->understanding['song_title']);
    }

    public function test_unresolved_lyric_never_falls_back_to_semantically_similar_mood(): void
    {
        $unrelated = $this->style(['name' => 'Diary Depresiku']);
        app(StyleEmbeddingIndexer::class)->index($unrelated);
        $this->embeddings->calls = 0;
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search('rembulan diam menemani langkah malam yang sepi');

        $this->assertSame('lyric', $result->understanding['intent']);
        $this->assertTrue($result->understanding['fallback']);
        $this->assertTrue($result->styles->isEmpty());
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_verified_local_lyric_signature_works_when_gemini_is_unavailable(): void
    {
        $diary = $this->style(['name' => 'Diary Depresiku']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search('wajar bila saat ini ku iri pada kalian');

        $this->assertSame([$diary->id], $result->styles->pluck('id')->all());
        $this->assertSame('Diary Depresiku', $result->understanding['song_title']);
        $this->assertSame('Last Child', $result->understanding['artist']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_another_verified_cidro_lyric_signature_returns_only_cidro(): void
    {
        $cidro = $this->style(['name' => 'Cidro Didi Kempot']);
        $this->style(['name' => 'Diary Depresiku']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search('Opo ora eling naliko semana');

        $this->assertSame([$cidro->id], $result->styles->pluck('id')->all());
        $this->assertSame('Cidro', $result->understanding['song_title']);
        $this->assertSame('Didi Kempot', $result->understanding['artist']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_verified_later_cidro_verse_returns_only_cidro(): void
    {
        $cidro = $this->style(['name' => 'Cidro Didi Kempot']);
        $this->style(['name' => 'Diary Depresiku']);
        $this->style(['name' => 'Sinarengan']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search(
            'Kepiye maneh iki pancen nasibku Kudu nandang lara kaya mengkene Remok ati iki yen eling janjine Ora ngiro jebulmu lamis wae'
        );

        $this->assertSame([$cidro->id], $result->styles->pluck('id')->all());
        $this->assertSame('Cidro', $result->understanding['song_title']);
        $this->assertSame('Didi Kempot', $result->understanding['artist']);
        $this->assertSame('exact', $result->understanding['identification_confidence']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_verified_sanes_verse_returns_the_sanes_catalog_style(): void
    {
        $sanes = $this->enrichedStyle('[TEST] Sanes', 'Guyon Waton', 'Sanes');
        $this->style(['name' => 'Other Song']);

        $result = app(HybridStyleSearchService::class)->search(
            'Opo koe ra ngerti larane Nalika pas aku kelangan koe Ngancani mongso sepimu Nuruti opo karepmu Nyatane atimu Dudu nggo aku'
        );

        $this->assertSame([$sanes->id], $result->styles->pluck('id')->all());
        $this->assertSame('Sanes', $result->understanding['song_title']);
        $this->assertSame('exact', $result->understanding['identification_confidence']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
    }

    public function test_verified_wirang_verse_returns_the_wirang_catalog_style(): void
    {
        $wirang = $this->enrichedStyle('[TEST] Wirang', 'Guyon Waton', 'Wirang');
        $this->style(['name' => 'Other Song']);

        $result = app(HybridStyleSearchService::class)->search('Adheme angin wengi teka');

        $this->assertSame([$wirang->id], $result->styles->pluck('id')->all());
        $this->assertSame('Wirang', $result->understanding['song_title']);
        $this->assertSame('exact', $result->understanding['identification_confidence']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
    }

    public function test_gemini_is_used_before_tavily_for_an_unknown_lyric(): void
    {
        $wirang = $this->enrichedStyle('[TEST] Wirang', 'Guyon Waton', 'Wirang');
        $this->intelligence->understanding = $this->understanding('lyric', artist: 'Guyon Waton', song: 'Wirang');
        config()->set('services.tavily.enabled', true);
        config()->set('services.tavily.api_key', 'tvly-test-key');
        Http::fake();

        $result = app(HybridStyleSearchService::class)->search('baris lirik baru dikenali gemini dengan tepat');

        $this->assertSame([$wirang->id], $result->styles->pluck('id')->all());
        $this->assertSame(1, $this->intelligence->understandingCalls);
        Http::assertNothingSent();
    }

    public function test_exact_gemini_lyric_identification_is_cached(): void
    {
        $this->enrichedStyle('[TEST] Wirang', 'Guyon Waton', 'Wirang');
        $this->intelligence->understanding = $this->understanding('lyric', artist: 'Guyon Waton', song: 'Wirang');

        $search = app(HybridStyleSearchService::class);
        $search->search('lirik unik untuk memastikan hasil gemini disimpan cache');
        $search->search('lirik unik untuk memastikan hasil gemini disimpan cache');

        $this->assertSame(1, $this->intelligence->understandingCalls);
    }

    public function test_tavily_is_used_after_gemini_failure_for_an_unknown_lyric(): void
    {
        $wirang = $this->enrichedStyle('[TEST] Wirang', 'Guyon Waton', 'Wirang');
        $this->intelligence->failUnderstanding = true;
        config()->set('services.tavily.enabled', true);
        config()->set('services.tavily.api_key', 'tvly-test-key');
        Http::fake(['api.tavily.com/*' => Http::response([
            'answer' => '',
            'results' => [
                ['title' => 'Lirik Lagu Wirang', 'content' => 'Informasi potongan lagu.'],
                ['title' => 'Denny Caknan - Wirang', 'content' => 'Informasi lagu Wirang.'],
            ],
        ])]);

        $result = app(HybridStyleSearchService::class)->search('baris lirik baru saat gemini kehabisan kuota');

        $this->assertSame([$wirang->id], $result->styles->pluck('id')->all());
        $this->assertSame('tavily_catalog_grounding', $result->understanding['identification_source']);
        $this->assertSame(1, $this->intelligence->understandingCalls);
        Http::assertSentCount(1);
    }

    public function test_short_verified_diary_lyric_never_uses_semantic_mood_ranking(): void
    {
        $this->style(['name' => 'Tinggal Kenangan']);
        $diary = $this->style(['name' => 'Diary Depresiku']);
        $this->style(['name' => 'Cidro Didi Kempot']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search('kuingat saat ayah pergi');

        $this->assertSame([$diary->id], $result->styles->pluck('id')->all());
        $this->assertSame('Diary Depresiku', $result->understanding['song_title']);
        $this->assertSame('Last Child', $result->understanding['artist']);
        $this->assertSame('exact', $result->understanding['identification_confidence']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_verified_sinarengan_lyric_returns_only_sinarengan_catalog_results(): void
    {
        $sinarengan = $this->style(['name' => 'Sinarengan (Denny Caknan)']);
        $this->style(['name' => 'Other Romantic Song']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search(
            'Kedaden tenan Nduwe omah sing ra berisik Kebak katresnan Kebak kasih lan sayang'
        );

        $this->assertSame([$sinarengan->id], $result->styles->pluck('id')->all());
        $this->assertSame('Sinarengan', $result->understanding['song_title']);
        $this->assertSame('Denny Caknan feat. Bella Bonita', $result->understanding['artist']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_verified_later_sinarengan_verse_also_returns_sinarengan(): void
    {
        $sinarengan = $this->style(['name' => 'Sinarengan (Denny Caknan)']);
        $this->style(['name' => 'Other Romantic Song']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search(
            'Ngobrol ra ana enteke ning tengah wengi Tetes embun sing ngancani Aku sampeyan kaya lagi kasmaran Tenan mlaku teka tuwa bebarengan'
        );

        $this->assertSame([$sinarengan->id], $result->styles->pluck('id')->all());
        $this->assertSame('Sinarengan', $result->understanding['song_title']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_verified_middle_sinarengan_verse_also_returns_sinarengan(): void
    {
        $sinarengan = $this->style(['name' => 'Sinarengan (Denny Caknan)']);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search(
            'Matur suwun Wis ngancani aku selama iki Wis isa saling nguwat-nguwatke Godha wong liya sing ra seneng Hubungan iki berlanjut'
        );

        $this->assertSame([$sinarengan->id], $result->styles->pluck('id')->all());
        $this->assertSame('Sinarengan', $result->understanding['song_title']);
        $this->assertSame('verified_local_signature', $result->understanding['identification_source']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertSame(0, $this->embeddings->calls);
    }

    public function test_missing_artist_metadata_falls_back_to_thresholded_semantic_search(): void
    {
        $style = $this->style(['name' => 'Semantic Artist Reference']);
        app(StyleEmbeddingIndexer::class)->index($style);
        $this->intelligence->understanding = $this->understanding('artist', artist: 'Artist XYZ');

        $result = app(HybridStyleSearchService::class)->search('Artist XYZ');

        $this->assertSame([$style->id], $result->styles->pluck('id')->all());
        $this->assertGreaterThan(0, $this->embeddings->calls);
    }

    public function test_exact_style_name_is_prioritized_without_query_understanding(): void
    {
        $exactName = $this->style(['name' => 'Pingal']);
        $titleMatch = $this->enrichedStyle('Pingal Variation', 'Guyon Waton', 'Pingal');

        $result = app(HybridStyleSearchService::class)->search('  PINGAL!! ');

        $this->assertSame([$exactName->id, $titleMatch->id], $result->styles->pluck('id')->all());
        $this->assertSame('Catalog Match', $result->styles->first()->ai_match_label);
        $this->assertSame(0, $this->intelligence->understandingCalls);
        $this->assertTrue($result->understanding['deterministic']);
    }

    public function test_song_name_embedded_in_a_lyric_phrase_is_matched_without_external_ai(): void
    {
        $negoro = $this->style(['name' => 'Negoro Angin']);
        $this->style(['name' => 'Diary Depresiku']);

        $result = app(HybridStyleSearchService::class)->search('kepisah benua negoro angin');

        $this->assertSame([$negoro->id], $result->styles->pluck('id')->all());
        $this->assertSame('song', $result->understanding['intent']);
        $this->assertTrue($result->understanding['deterministic']);
        $this->assertSame(0, $this->intelligence->understandingCalls);
    }

    public function test_search_reference_matching_is_case_and_punctuation_insensitive(): void
    {
        $style = $this->enrichedStyle('Pingal', 'Other Performer', references: ['NDX A.K.A.']);
        $this->intelligence->understanding = $this->understanding('artist', artist: 'ndx aka');

        $result = app(HybridStyleSearchService::class)->search('NDX-A.K.A.');

        $this->assertSame([$style->id], $result->styles->pluck('id')->all());
        $this->assertSame('Reference Match', $result->styles->first()->ai_match_label);
        $this->assertSame(0, $this->intelligence->understandingCalls);
    }

    public function test_category_keyword_and_catalog_filters_are_deterministic(): void
    {
        $campursari = $this->style(['name' => 'Campursari Classic', 'category' => 'Campursari']);
        $this->style(['name' => 'Dangdut Classic', 'category' => 'Dangdut']);

        $keyword = app(HybridStyleSearchService::class)->search('campursari');
        $filtered = app(HybridStyleSearchService::class)->search(
            'Campursari Classic',
            'Campursari',
            'HM Campursari Expansion Packs',
        );

        $this->assertSame([$campursari->id], $keyword->styles->pluck('id')->all());
        $this->assertSame([$campursari->id], $filtered->styles->pluck('id')->all());
        $this->assertSame(0, $this->intelligence->understandingCalls);
    }

    public function test_untrusted_metadata_is_not_used_as_deterministic_exact_match(): void
    {
        $this->style([
            'name' => 'Different Internal Name',
            'ai_song_title' => 'Untrusted Title',
            'ai_aliases' => ['Untrusted Alias'],
            'ai_search_references' => ['Untrusted Reference'],
            'ai_enrichment_status' => 'uncertain',
        ]);
        $this->intelligence->understanding = $this->understanding('song', song: 'Untrusted Title');

        app(HybridStyleSearchService::class)->search('Untrusted Title');

        $this->assertSame(1, $this->intelligence->understandingCalls);
    }

    public function test_exact_song_title_and_alias_are_prioritized_from_catalog(): void
    {
        $style = $this->enrichedStyle('Negoro Angin Style', 'Denny Caknan', 'Negoro Angin', ['Negara Angin']);
        $this->enrichedStyle('Other Song', 'Denny Caknan');
        $this->intelligence->understanding = $this->understanding('song', song: 'negara-angin');

        $result = app(HybridStyleSearchService::class)->search('Negara Angin');

        $this->assertSame([$style->id], $result->styles->pluck('id')->all());
        $this->assertSame('Catalog Match', $result->styles->first()->ai_match_label);
    }

    public function test_artist_context_semantically_ranks_only_artist_candidates(): void
    {
        $this->embeddings->resolver = fn (string $text): array => match (true) {
            str_contains($text, 'Romantic Denny') => [1.0, 0.0],
            str_contains($text, 'Fast Denny') => [0.4, 0.6],
            str_contains($text, 'Other Artist') => [1.0, 0.0],
            default => [1.0, 0.0],
        };
        $romantic = $this->enrichedStyle('Romantic Denny', 'Denny Caknan');
        $fast = $this->enrichedStyle('Fast Denny', 'Denny Caknan');
        $this->enrichedStyle('Other Artist', 'Didi Kempot');
        foreach (StyleSampling::all() as $style) {
            app(StyleEmbeddingIndexer::class)->index($style);
        }
        $this->intelligence->understanding = $this->understanding(
            'artist_context', artist: 'Denny Caknan', context: 'acara nikahan', semantic: 'musik romantis untuk nikahan'
        );

        $result = app(HybridStyleSearchService::class)->search('Denny Caknan untuk acara nikahan');

        $this->assertSame([$romantic->id, $fast->id], $result->styles->pluck('id')->all());
        $this->assertNotContains('Other Artist', $result->styles->pluck('name')->all());
    }

    public function test_event_search_uses_threshold_and_does_not_force_below_threshold_results(): void
    {
        $this->embeddings->resolver = fn (string $text): array => str_contains($text, 'Relevant Wedding')
            ? [1.0, 0.0]
            : (str_contains($text, 'Irrelevant Stage') ? [-1.0, 0.0] : [1.0, 0.0]);
        $relevant = $this->style(['name' => 'Relevant Wedding']);
        $this->style(['name' => 'Irrelevant Stage']);
        foreach (StyleSampling::all() as $style) {
            app(StyleEmbeddingIndexer::class)->index($style);
        }
        $this->intelligence->understanding = $this->understanding('event', context: 'nikahan', semantic: 'iringan musik untuk nikahan');

        $result = app(HybridStyleSearchService::class)->search('iringan musik untuk nikahan');

        $this->assertSame([$relevant->id], $result->styles->pluck('id')->all());
    }

    public function test_query_understanding_failure_falls_back_to_semantic_search_safely(): void
    {
        $style = $this->style(['name' => 'Semantic Fallback']);
        app(StyleEmbeddingIndexer::class)->index($style);
        $this->intelligence->failUnderstanding = true;

        $result = app(HybridStyleSearchService::class)->search('iringan keluarga');

        $this->assertTrue($result->understanding['fallback']);
        $this->assertSame([$style->id], $result->styles->pluck('id')->all());
    }

    public function test_customer_artist_search_renders_only_grounded_catalog_cards_without_raw_ai_data(): void
    {
        $this->enrichedStyle('Negoro Angin', 'Denny Caknan');
        $this->enrichedStyle('Kartonyono Medot Janji', 'Denny Caknan');
        $this->enrichedStyle('Pamer Bojo', 'Didi Kempot');
        $this->intelligence->understanding = $this->understanding('artist', artist: 'Denny Caknan');
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'Active']);

        $this->actingAs($customer)->post(route('stylesampling.ai-search'), ['query' => 'Denny Caknan'])
            ->assertRedirect(route('stylesampling'));

        $this->get(route('stylesampling'))
            ->assertOk()
            ->assertSeeInOrder(['Negoro Angin', 'Kartonyono Medot Janji'])
            ->assertSee('Denny Caknan')
            ->assertSee('Catalog Artist Match')
            ->assertDontSee('<h2>Pamer Bojo</h2>', false)
            ->assertDontSee('search_embedding')
            ->assertDontSee('fake-generative-model');
    }

    private function enrichedStyle(string $name, string $artist, ?string $song = null, array $aliases = [], array $references = []): StyleSampling
    {
        return $this->style([
            'name' => $name, 'ai_artist' => $artist, 'ai_song_title' => $song ?: $name,
            'ai_aliases' => $aliases, 'ai_search_profile' => "{$name} oleh {$artist}",
            'ai_search_references' => $references,
            'ai_enrichment_status' => 'verified', 'ai_enrichment_source' => 'gemini_grounded',
            'ai_enrichment_source_hash' => str_repeat('a', 64), 'ai_enrichment_updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function understanding(string $intent, ?string $artist = null, ?string $song = null, ?string $context = null, ?string $semantic = null): array
    {
        return compact('intent', 'artist', 'context') + [
            'song_title' => $song,
            'semantic_query' => $semantic ?: ($artist ?: $song ?: $context ?: 'general music'),
            'identification_confidence' => $intent === 'lyric' ? 'exact' : 'not_applicable',
        ];
    }

    private function style(array $attributes = []): StyleSampling
    {
        return StyleSampling::create(array_merge([
            'name' => 'Hybrid Style', 'category' => 'Dangdut', 'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium', 'status' => 'Published', 'style_file_path' => 'styles/hybrid.sty',
            'style_filename' => 'hybrid.sty', 'description' => 'Style katalog HM Music.',
        ], $attributes));
    }
}
