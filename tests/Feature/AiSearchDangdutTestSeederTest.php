<?php

namespace Tests\Feature;

use App\Models\StyleSampling;
use Database\Seeders\AiSearchDangdutTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSearchDangdutTestSeederTest extends TestCase
{
    use RefreshDatabase;

    private StyleSampling $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = StyleSampling::create([
            'name' => 'Original Production Template',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_file_path' => 'styles/original-template.sty',
            'style_filename' => 'original-template.sty',
            'description' => 'Original data that must remain unchanged.',
            'downloads_count' => 77,
        ]);
    }

    public function test_seeder_is_idempotent_preserves_original_and_creates_expected_catalog(): void
    {
        $this->seed(AiSearchDangdutTestSeeder::class);
        $this->seed(AiSearchDangdutTestSeeder::class);

        $testRows = $this->markedRows();
        $this->assertCount(63, $testRows);
        $this->assertSame([
            'Denny Caknan' => 10,
            'Didi Kempot' => 10,
            'Gilga Sahid' => 6,
            'Guyon Waton' => 8,
            'Happy Asmara' => 8,
            'Lavora' => 7,
            'NDX A.K.A.' => 8,
            'Ndarboy Genk' => 6,
        ], $testRows->countBy('ai_artist')->sortKeys()->all());

        $this->assertTrue($testRows->every(fn (StyleSampling $style): bool => filled($style->ai_artist)
            && filled($style->ai_song_title)
            && filled($style->ai_search_profile)
            && is_array($style->ai_search_references)
            && $style->ai_enrichment_status === 'verified'
            && $style->ai_enrichment_source === 'test_seed'
            && $style->status === 'Published'
            && filled($style->style_file_path)
            && in_array($style->category, StyleSampling::CUSTOMER_STYLE_CATEGORIES, true)
            && in_array($style->pack, StyleSampling::PACKS, true)
        ));
        $this->assertTrue($testRows->every(fn (StyleSampling $style): bool => ! str_contains($style->name, '[TEST]')));
        $this->assertTrue($testRows->every(
            fn (StyleSampling $style): bool => $style->style_filename === rtrim($style->name, ' .').'.STY'
                && $style->style_filename !== 'Sinarengan.STY'
        ));
        $this->assertCount(63, $testRows->pluck('style_filename')->unique());
        $this->assertTrue($testRows->contains(
            fn (StyleSampling $style): bool => $style->name === 'Negoro Angin - Denny Caknan'
                && $style->style_filename === 'Negoro Angin - Denny Caknan.STY'
        ));

        $original = $this->template->refresh();
        $this->assertSame('Original Production Template', $original->name);
        $this->assertSame('Original data that must remain unchanged.', $original->description);
        $this->assertSame(77, $original->downloads_count);
    }

    public function test_cleanup_command_deletes_only_marked_test_rows(): void
    {
        $this->seed(AiSearchDangdutTestSeeder::class);

        $this->artisan('styles:test-catalog-clear')
            ->expectsOutput('Deleted: 63')
            ->assertSuccessful();

        $this->assertCount(0, $this->markedRows());
        $this->assertTrue($this->template->refresh()->exists);
        $this->assertDatabaseHas('style_samplings', ['id' => $this->template->id]);
    }

    private function markedRows()
    {
        return StyleSampling::query()
            ->where('ai_enrichment_source', 'test_seed')
            ->where('description', 'like', '%'.AiSearchDangdutTestSeeder::MARKER.'%')
            ->get();
    }
}
