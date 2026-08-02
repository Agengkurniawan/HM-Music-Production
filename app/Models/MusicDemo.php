<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MusicDemo extends Model
{
    public const GENRE_NONE = 'None';

    public const GENRES = ['Dangdut', 'Campursari', 'Gamelan'];

    public const STATUSES = ['Draft', 'Published'];
 
    protected $fillable = [
        'title',
        'genre',
        'bpm',
        'duration',
        'key_signature',
        'status',
        'is_trending',
        'youtube_url',
        'installation_video_path',
        'plays_count',
    ];

    protected function casts(): array
    {
        return [
            'is_trending' => 'boolean',
            'bpm' => 'integer',
            'plays_count' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'Published');
    }

    public function scopeWithYoutubeVideo(Builder $query): Builder
    {
        return $query
            ->whereNotNull('youtube_url')
            ->where('youtube_url', '<>', '');
    }

    public function scopeWithPlayableMedia(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where(function (Builder $query): void {
                    $query->whereNotNull('youtube_url')
                        ->where('youtube_url', '<>', '');
                })
                ->orWhere(function (Builder $query): void {
                    $query->whereNotNull('installation_video_path')
                        ->where('installation_video_path', '<>', '');
                });
        });
    }

    public static function genreOptions(): array
    {
        return [self::GENRE_NONE, ...self::GENRES];
    }

    public static function extractYoutubeVideoId(?string $url): ?string
    {
        $parts = parse_url(trim((string) $url));

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $host = preg_replace('/^www\./', '', $host);
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = explode('/', $path)[0] ?? null;
        } elseif (
            $host === 'youtube.com'
            || str_ends_with($host, '.youtube.com')
            || $host === 'youtube-nocookie.com'
            || str_ends_with($host, '.youtube-nocookie.com')
        ) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $segments = explode('/', $path);

            if (! empty($query['v'])) {
                $videoId = $query['v'];
            } elseif (in_array($segments[0] ?? null, ['embed', 'live', 'shorts'], true)) {
                $videoId = $segments[1] ?? null;
            }
        }

        return is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)
            ? $videoId
            : null;
    }

    public static function normalizeYoutubeUrl(string $url): string
    {
        return 'https://www.youtube.com/watch?v=' . self::extractYoutubeVideoId($url);
    }

    public function getYoutubeVideoIdAttribute(): ?string
    {
        return self::extractYoutubeVideoId($this->youtube_url);
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return $this->youtube_video_id
            ? 'https://www.youtube-nocookie.com/embed/' . $this->youtube_video_id
            : null;
    }

    public function getInstallationVideoUrlAttribute(): ?string
    {
        return $this->installation_video_path
            ? Storage::disk('public')->url($this->installation_video_path)
            : null;
    }

    public function getDisplayGenreAttribute(): string
    {
        return $this->genre ?: self::GENRE_NONE;
    }

    public function getDisplayBpmAttribute(): string
    {
        return $this->bpm > 0 ? $this->bpm.' BPM' : self::GENRE_NONE;
    }

    public function getDisplayDurationAttribute(): string
    {
        return filled($this->duration) && $this->duration !== '0:00'
            ? $this->duration
            : self::GENRE_NONE;
    }

    public function getThumbnailSrcAttribute(): string
    {
        if ($this->youtube_video_id) {
            return 'https://i.ytimg.com/vi/' . $this->youtube_video_id . '/hqdefault.jpg';
        }

        return 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=720&h=480&fit=crop';
    }
}
