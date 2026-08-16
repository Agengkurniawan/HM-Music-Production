<?php

namespace Tests\Unit;

use App\Models\StyleSampling;
use App\Services\AI\StyleSearchDocumentBuilder;
use PHPUnit\Framework\TestCase;

class StyleSearchDocumentBuilderTest extends TestCase
{
    public function test_it_builds_retrieval_document_and_query_from_existing_fields(): void
    {
        $style = new StyleSampling([
            'name' => 'Wedding Campursari',
            'category' => 'Campursari',
            'pack' => 'HM Campursari Expansion Packs',
            'description' => 'Iringan romantis untuk resepsi',
        ]);
        $builder = new StyleSearchDocumentBuilder;

        $this->assertSame(
            'title: Wedding Campursari | text: Nama Style: Wedding Campursari. Kategori: Campursari. Sampling Pack: HM Campursari Expansion Packs. Deskripsi: Iringan romantis untuk resepsi.',
            $builder->build($style)
        );
        $this->assertSame(
            'task: search result | query: iringan musik untuk acara nikahan',
            $builder->query(' iringan musik untuk acara nikahan ')
        );
    }

    public function test_empty_description_is_safe_and_hash_tracks_only_document_source(): void
    {
        $builder = new StyleSearchDocumentBuilder;
        $style = new StyleSampling(['name' => 'Koplo', 'category' => 'Dangdut', 'pack' => 'HM Dangdut Expansion Packs']);
        $hash = $builder->sourceHash($style);

        $this->assertStringNotContainsString('Deskripsi:', $builder->build($style));
        $this->assertSame($hash, $builder->sourceHash($style));

        $style->description = 'Cepat untuk hajatan';
        $this->assertNotSame($hash, $builder->sourceHash($style));

        $style->description = null;
        $style->pack = 'HM Campursari Expansion Packs';
        $this->assertNotSame($hash, $builder->sourceHash($style));
    }

    public function test_trusted_search_references_are_embedded_and_change_source_hash(): void
    {
        $builder = new StyleSearchDocumentBuilder;
        $style = new StyleSampling([
            'name' => 'Pingal',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'ai_enrichment_status' => 'verified',
            'ai_search_references' => ['Guyon Waton', 'Ngatmombilung'],
        ]);
        $hash = $builder->sourceHash($style);

        $this->assertStringContainsString('Referensi Pencarian: Guyon Waton, Ngatmombilung.', $builder->build($style));

        $style->ai_search_references = ['Andry Priyanta'];
        $this->assertNotSame($hash, $builder->sourceHash($style));
    }
}
