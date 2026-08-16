<?php

namespace App\Services\AI;

use App\Models\StyleSampling;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TavilyLyricsSearchService
{
    /** @return array{song_title: string, artist: string, source: string}|null */
    public function identify(string $lyrics): ?array
    {
        $apiKey = trim((string) config('services.tavily.api_key'));

        if (! config('services.tavily.enabled', true) || $apiKey === '') {
            return null;
        }

        $normalized = $this->normalize($lyrics);

        if ($normalized === '') {
            return null;
        }

        $cacheKey = 'ai-search:lyrics:tavily:'.hash('sha256', $normalized);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $match = $this->search($lyrics);

        if ($match !== null) {
            Cache::put(
                $cacheKey,
                $match,
                now()->addDays(max(1, (int) config('services.tavily.cache_days', 30))),
            );
        }

        return $match;
    }

    /** @return array{song_title: string, artist: string, source: string}|null */
    private function search(string $lyrics): ?array
    {
        $apiKey = trim((string) config('services.tavily.api_key'));
        $searchQuery = trim($lyrics).' lirik lagu judul penyanyi';

        try {
            $response = Http::asJson()
                ->withToken($apiKey)
                ->connectTimeout(3)
                ->timeout((int) config('services.tavily.timeout', 12))
                ->retry(2, 250, throw: false)
                ->post('https://api.tavily.com/search', [
                    'query' => $searchQuery,
                    'topic' => 'general',
                    'search_depth' => 'basic',
                    'include_answer' => 'basic',
                    'include_raw_content' => false,
                    'max_results' => 5,
                    'country' => 'indonesia',
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return null;
        }

        if ($response->failed()) {
            report(new \RuntimeException('Tavily lyric search failed with HTTP '.$response->status().'.'));

            return null;
        }

        $answer = (string) $response->json('answer', '');
        $songTitle = $this->answerField($answer, 'SONG_TITLE');
        $artist = $this->answerField($answer, 'ARTIST');
        $results = $response->json('results', []);

        if ($songTitle !== null && $artist !== null && $this->isSupportedBySources($results, $songTitle, $artist)) {
            return ['song_title' => $songTitle, 'artist' => $artist, 'source' => 'tavily_web_search'];
        }

        return $this->catalogIdentification($results);
    }

    private function answerField(string $answer, string $field): ?string
    {
        if (! preg_match('/^'.preg_quote($field, '/').'\s*:\s*(.+)$/mi', $answer, $matches)) {
            return null;
        }

        $value = trim($matches[1], " \t\n\r\0\x0B*\"'");

        return $value !== '' && mb_strlen($value) <= 150 ? $value : null;
    }

    private function isSupportedBySources(mixed $results, string $songTitle, string $artist): bool
    {
        if (! is_array($results)) {
            return false;
        }

        $titleNeedle = $this->normalize($songTitle);
        $artistNeedle = $this->normalize($artist);
        $titleMatches = 0;

        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            $source = $this->normalize((string) ($result['title'] ?? '').' '.(string) ($result['content'] ?? ''));
            $hasTitle = $titleNeedle !== '' && str_contains($source, $titleNeedle);
            $hasArtist = $artistNeedle !== '' && str_contains($source, $artistNeedle);

            if ($hasTitle && $hasArtist) {
                return true;
            }

            $titleMatches += (int) $hasTitle;
        }

        return $titleMatches >= 2;
    }

    /** @return array{song_title: string, artist: string, source: string}|null */
    private function catalogIdentification(mixed $results): ?array
    {
        if (! is_array($results) || $results === []) {
            return null;
        }

        $sources = collect($results)
            ->filter(fn (mixed $result): bool => is_array($result))
            ->map(fn (array $result): string => $this->normalize(
                (string) ($result['title'] ?? '').' '.(string) ($result['content'] ?? '')
            ));

        $catalog = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->get()
            ->flatMap(function (StyleSampling $style): array {
                $titles = [[
                    'title' => (string) $style->name,
                    'artist' => '',
                ]];

                if ($style->hasTrustedAiMetadata() && filled($style->ai_song_title)) {
                    $titles[] = [
                        'title' => (string) $style->ai_song_title,
                        'artist' => (string) $style->ai_artist,
                    ];
                }

                return $titles;
            })
            ->groupBy(fn (array $candidate): string => $this->normalize($candidate['title']));

        $candidates = $catalog->map(function ($matches, string $normalizedTitle) use ($sources): ?array {
            if ($normalizedTitle === '' || mb_strlen($normalizedTitle) < 4) {
                return null;
            }

            $candidate = $matches->sortByDesc(fn (array $match): int => $match['artist'] !== '' ? 1 : 0)->first();
            $normalizedArtist = $this->normalize($candidate['artist']);
            $titleMatches = $sources->filter(fn (string $source): bool => str_contains($source, $normalizedTitle))->count();
            $artistMatches = $normalizedArtist === '' ? 0 : $sources->filter(
                fn (string $source): bool => str_contains($source, $normalizedTitle)
                    && str_contains($source, $normalizedArtist)
            )->count();

            if ($titleMatches < 2 && $artistMatches < 1) {
                return null;
            }

            return [
                'song_title' => $candidate['title'],
                'artist' => $candidate['artist'],
                'source' => 'tavily_catalog_grounding',
                'score' => ($titleMatches * 2) + $artistMatches,
            ];
        })
            ->filter()
            ->sortByDesc('score');

        $match = $candidates->first();

        if (! is_array($match)) {
            return null;
        }

        unset($match['score']);

        return $match;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}
