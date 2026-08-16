<?php

namespace App\Services\AI;

use App\Contracts\QueryUnderstandingProviderInterface;
use Illuminate\Support\Facades\Cache;

class QueryUnderstandingService
{
    public function __construct(
        private readonly QueryUnderstandingProviderInterface $provider,
        private readonly TavilyLyricsSearchService $lyricsSearch,
    ) {}

    /** @return array<string, mixed> */
    public function understand(string $query): array
    {
        if ($knownLyric = $this->knownLyric($query)) {
            return [
                'intent' => 'lyric',
                'artist' => $knownLyric['artist'],
                'song_title' => $knownLyric['song_title'],
                'context' => null,
                'semantic_query' => $knownLyric['song_title'].' '.$knownLyric['artist'],
                'identification_confidence' => 'exact',
                'fallback' => false,
                'identification_source' => 'verified_local_signature',
            ];
        }

        $lyricCacheKey = $this->looksLikeLyricExcerpt($query)
            ? 'ai-search:lyrics:understanding:'.hash('sha256', $this->normalize($query))
            : null;
        $cached = $lyricCacheKey !== null ? Cache::get($lyricCacheKey) : null;

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $result = $this->provider->understand($query);
            $intent = in_array($result['intent'] ?? null, ['artist', 'song', 'lyric', 'artist_context', 'song_context', 'event', 'general'], true)
                ? $result['intent']
                : 'general';

            $understanding = [
                'intent' => $intent,
                'artist' => $this->nullableString($result['artist'] ?? null),
                'song_title' => $this->nullableString($result['song_title'] ?? null),
                'context' => $this->nullableString($result['context'] ?? null),
                'semantic_query' => $this->nullableString($result['semantic_query'] ?? null) ?: trim($query),
                'identification_confidence' => in_array($result['identification_confidence'] ?? null, ['exact', 'uncertain', 'not_applicable'], true)
                    ? $result['identification_confidence']
                    : 'not_applicable',
                'identification_source' => $this->nullableString($result['identification_source'] ?? null),
                'fallback' => false,
            ];

            if ($this->looksLikeLyricExcerpt($query)
                && (($understanding['identification_confidence'] ?? null) !== 'exact'
                    || blank($understanding['song_title'] ?? null))) {
                $understanding = $this->webUnderstanding($query) ?? $understanding;
            }

            $this->cacheExactLyric($lyricCacheKey, $understanding);

            return $understanding;
        } catch (\Throwable $exception) {
            report($exception);

            if ($this->looksLikeLyricExcerpt($query) && $webUnderstanding = $this->webUnderstanding($query)) {
                $this->cacheExactLyric($lyricCacheKey, $webUnderstanding);

                return $webUnderstanding;
            }

            return [
                'intent' => $this->looksLikeLyricExcerpt($query) ? 'lyric' : 'general',
                'artist' => null, 'song_title' => null,
                'context' => null, 'semantic_query' => trim($query), 'fallback' => true,
                'identification_confidence' => 'uncertain',
            ];
        }
    }

    /** @return array<string, mixed>|null */
    private function webUnderstanding(string $query): ?array
    {
        $webMatch = $this->lyricsSearch->identify($query);

        if ($webMatch === null) {
            return null;
        }

        return [
            'intent' => 'lyric',
            'artist' => $webMatch['artist'],
            'song_title' => $webMatch['song_title'],
            'context' => null,
            'semantic_query' => trim($webMatch['song_title'].' '.$webMatch['artist']),
            'identification_confidence' => 'exact',
            'fallback' => false,
            'identification_source' => $webMatch['source'],
        ];
    }

    /** @param array<string, mixed> $understanding */
    private function cacheExactLyric(?string $cacheKey, array $understanding): void
    {
        if ($cacheKey === null
            || ($understanding['intent'] ?? null) !== 'lyric'
            || ($understanding['identification_confidence'] ?? null) !== 'exact'
            || blank($understanding['song_title'] ?? null)) {
            return;
        }

        Cache::put(
            $cacheKey,
            $understanding,
            now()->addDays(max(1, (int) config('services.ai_search.lyrics_cache_days', 30))),
        );
    }

    /** @return array{song_title: string, artist: string}|null */
    private function knownLyric(string $query): ?array
    {
        $normalizedQuery = $this->normalize($query);

        foreach ((array) config('services.ai_search.known_lyric_signatures', []) as $signature => $song) {
            if (! is_array($song) || ! is_string($song['song_title'] ?? null) || ! is_string($song['artist'] ?? null)) {
                continue;
            }

            $normalizedSignature = $this->normalize((string) $signature);

            if ($normalizedSignature !== '' && str_contains($normalizedQuery, $normalizedSignature)) {
                return ['song_title' => $song['song_title'], 'artist' => $song['artist']];
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function looksLikeLyricExcerpt(string $query): bool
    {
        $words = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $searchInstructions = '/\b(style|musik|genre|iringan|hajatan|nikahan|resepsi|tempo|bpm)\b/ui';

        return count($words) >= 4 && ! preg_match($searchInstructions, $query);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
