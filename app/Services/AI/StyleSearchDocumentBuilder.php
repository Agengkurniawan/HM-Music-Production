<?php

namespace App\Services\AI;

use App\Models\StyleSampling;

class StyleSearchDocumentBuilder
{
    public function build(StyleSampling $style): string
    {
        $pack = StyleSampling::normalizeSamplingPackName($style->pack)
            ?: StyleSampling::samplingPackForCategory($style->category)
            ?: 'Tidak ditentukan';

        $parts = [
            "Nama Style: {$style->name}.",
            "Kategori: {$style->category}.",
            "Sampling Pack: {$pack}.",
        ];

        if (filled($style->description)) {
            $parts[] = 'Deskripsi: '.trim((string) $style->description).'.';
        }

        if ($style->hasTrustedAiMetadata()) {
            $metadata = array_filter([
                'Judul Lagu' => $style->ai_song_title,
                'Artis' => $style->ai_artist,
                'Genre' => $style->ai_genre,
                'Alias' => implode(', ', $style->ai_aliases ?? []),
                'Referensi Pencarian' => implode(', ', $style->ai_search_references ?? []),
                'AI Search Profile' => $style->ai_search_profile,
            ], fn ($value): bool => filled($value));

            foreach ($metadata as $label => $value) {
                $parts[] = "{$label}: {$value}.";
            }
        }

        return "title: {$style->name} | text: ".implode(' ', $parts);
    }

    public function sourceHash(StyleSampling $style): string
    {
        return hash('sha256', $this->build($style));
    }

    public function query(string $query): string
    {
        return 'task: search result | query: '.trim($query);
    }
}
