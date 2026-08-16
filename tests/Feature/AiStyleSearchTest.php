<?php

namespace Tests\Feature;

use App\Contracts\EmbeddingProviderInterface;
use App\Models\StyleSampling;
use App\Models\User;
use App\Services\AI\SemanticStyleSearchService;
use App\Services\AI\StyleEmbeddingIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeEmbeddingProvider;
use Tests\TestCase;

class AiStyleSearchTest extends TestCase
{
    use RefreshDatabase;

    private FakeEmbeddingProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.ai_search.enabled', true);
        config()->set('services.ai_search.top_k', 2);
        config()->set('hm.admin_email', 'admin@hmmusic.test');
        $this->provider = new FakeEmbeddingProvider;
        $this->app->instance(EmbeddingProviderInterface::class, $this->provider);
    }

    public function test_indexer_saves_embedding_skips_fresh_and_force_reindexes(): void
    {
        $style = $this->style();
        $indexer = app(StyleEmbeddingIndexer::class);

        $this->assertTrue($indexer->index($style));
        $this->assertEquals([1.0, 0.0, 0.0], $style->refresh()->search_embedding);
        $this->assertSame('fake-embedding-model', $style->embedding_model);
        $this->assertNotNull($style->embedding_updated_at);
        $this->assertFalse($indexer->index($style));
        $this->assertSame(1, $this->provider->calls);
        $this->assertTrue($indexer->index($style, true));
        $this->assertSame(2, $this->provider->calls);
    }

    public function test_artisan_command_indexes_skips_and_force_reindexes(): void
    {
        $this->style();

        $this->artisan('styles:semantic-index')->assertSuccessful();
        $this->assertSame(1, $this->provider->calls);

        $this->artisan('styles:semantic-index')->assertSuccessful();
        $this->assertSame(1, $this->provider->calls);

        $this->artisan('styles:semantic-index', ['--force' => true])->assertSuccessful();
        $this->assertSame(2, $this->provider->calls);
    }

    public function test_semantic_search_ranks_descending_limits_top_k_and_excludes_ineligible_styles(): void
    {
        $vectors = [
            'Best Wedding' => [1.0, 0.0],
            'Second Wedding' => [0.8, 0.2],
            'Third Wedding' => [0.5, 0.5],
            'Draft Wedding' => [1.0, 0.0],
        ];
        $this->provider->resolver = function (string $text) use ($vectors): array {
            foreach ($vectors as $name => $vector) {
                if (str_contains($text, $name)) {
                    return $vector;
                }
            }

            return [1.0, 0.0];
        };

        foreach ($vectors as $name => $vector) {
            $style = $this->style(['name' => $name, 'status' => str_starts_with($name, 'Draft') ? 'Draft' : 'Published']);
            app(StyleEmbeddingIndexer::class)->index($style);
        }

        $results = app(SemanticStyleSearchService::class)->search('musik romantis untuk resepsi');

        $this->assertSame(['Best Wedding', 'Second Wedding'], $results->pluck('name')->all());
        $this->assertGreaterThanOrEqual($results[1]->ai_similarity, $results[0]->ai_similarity);
        $this->assertCount(2, $results);
    }

    public function test_stale_and_invalid_embedding_are_not_returned(): void
    {
        $style = $this->style();
        app(StyleEmbeddingIndexer::class)->index($style);
        $style->update(['description' => 'Source changed after indexing']);

        $this->assertTrue(app(SemanticStyleSearchService::class)->search('wedding')->isEmpty());
    }

    public function test_query_validation_and_provider_failure_are_customer_safe(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'Active']);

        $this->actingAs($customer)->post(route('stylesampling.ai-search'), ['query' => ''])
            ->assertSessionHasErrors('query');

        $this->provider->shouldFail = true;
        $this->actingAs($customer)->post(route('stylesampling.ai-search'), ['query' => 'iringan nikahan'])
            ->assertRedirect(route('stylesampling'));

        $this->get(route('stylesampling'))
            ->assertOk()
            ->assertSee('AI Smart Search sedang tidak tersedia. Silakan gunakan pencarian biasa.');

        $this->get(route('stylesampling'))
            ->assertDontSee('AI Smart Search sedang tidak tersedia. Silakan gunakan pencarian biasa.');
    }

    public function test_valid_query_uses_existing_card_mapping_filters_and_hides_raw_embedding(): void
    {
        $style = $this->style(['name' => 'Wedding Campursari', 'category' => 'Campursari', 'pack' => 'HM Campursari Expansion Packs']);
        app(StyleEmbeddingIndexer::class)->index($style);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'Active']);

        $post = $this->actingAs($customer)->post(route('stylesampling.ai-search'), ['query' => 'iringan musik untuk acara nikahan'])
            ->assertRedirect(route('stylesampling'))
            ->assertSessionHas('stylesampling_ai_search_result');

        $state = $post->getSession()->get('stylesampling_ai_search_result');
        $this->assertIsArray($state);
        $this->assertSame([$style->id], array_column($state['results'], 'id'));
        $this->assertFalse($this->containsObject($state));
        $this->assertArrayNotHasKey('search_embedding', $state['results'][0]);

        $this->get(route('stylesampling'))
            ->assertOk()
            ->assertSee('Wedding Campursari')
            ->assertSee('Relevansi AI: 100%')
            ->assertSee('Hasil untuk')
            ->assertSee('iringan musik untuk acara nikahan')
            ->assertSee('Tampilkan Semua Style')
            ->assertSee('href="'.route('stylesampling', ['type' => 'style']).'"', false)
            ->assertSee('HM Campursari Expansion Packs')
            ->assertSee('Unlock STY')
            ->assertSee('data-unified-style-query', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="pack"', false)
            ->assertDontSee('search_embedding')
            ->assertDontSee(json_encode($style->search_embedding));

        $this->get(route('stylesampling'))
            ->assertOk()
            ->assertSee('Wedding Campursari')
            ->assertDontSee('Hasil untuk')
            ->assertDontSee('Tampilkan Semua Style')
            ->assertDontSee('iringan musik untuk acara nikahan');
    }

    public function test_style_library_has_one_unified_primary_search_without_old_ai_section(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'Active']);

        $response = $this->actingAs($customer)->get(route('stylesampling', ['type' => 'style']))
            ->assertOk()
            ->assertSee('Cari style, judul lagu, artis, atau referensi musik...')
            ->assertSee('data-unified-style-query', false)
            ->assertSee('<button type="submit" class="unified-style-search__submit">Cari</button>', false)
            ->assertDontSee('AI Smart Search')
            ->assertDontSee('Cari dengan AI');

        $this->assertSame(1, substr_count($response->getContent(), '<input type="search" name="query"'));
        $this->assertSame(0, substr_count($response->getContent(), '<textarea id="ai-style-query"'));
    }

    public function test_unified_search_honors_category_and_pack_filters(): void
    {
        $wanted = $this->style(['name' => 'Shared Song', 'category' => 'Campursari', 'pack' => 'HM Campursari Expansion Packs']);
        $this->style(['name' => 'Shared Song', 'category' => 'Dangdut', 'pack' => 'HM Dangdut Expansion Packs']);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'Active']);

        $post = $this->actingAs($customer)->post(route('stylesampling.ai-search'), [
            'query' => 'Shared Song',
            'category' => 'Campursari',
            'pack' => 'HM Campursari Expansion Packs',
        ])->assertRedirect(route('stylesampling'));

        $state = $post->getSession()->get('stylesampling_ai_search_result');
        $this->assertSame([$wanted->id], array_column($state['results'], 'id'));

        $this->get(route('stylesampling'))
            ->assertOk()
            ->assertSee('Shared Song')
            ->assertSee('value="Campursari" selected', false)
            ->assertSee('value="HM Campursari Expansion Packs" selected', false);
    }

    public function test_provider_failure_does_not_rollback_admin_style_update(): void
    {
        $style = $this->style(['status' => 'Draft']);
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'), 'email_verified_at' => now(),
            'role' => 'admin', 'status' => 'Active',
        ]);
        $this->provider->shouldFail = true;

        $this->actingAs($admin)->put(route('admin.stylesampling.update', $style), [
            'name' => 'Updated Despite AI Failure',
            'category' => 'Dangdut',
            'description' => 'Saved first',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Updated Despite AI Failure', $style->refresh()->name);
        $this->assertNull($style->search_embedding);
    }

    private function style(array $attributes = []): StyleSampling
    {
        return StyleSampling::create(array_merge([
            'name' => 'Semantic Style',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_file_path' => 'styles/semantic.sty',
            'style_filename' => 'semantic.sty',
            'description' => 'Style untuk acara dan pertunjukan',
        ], $attributes));
    }

    private function containsObject(mixed $value): bool
    {
        if (is_object($value)) {
            return true;
        }

        return is_array($value) && collect($value)->contains(fn (mixed $item): bool => $this->containsObject($item));
    }
}
