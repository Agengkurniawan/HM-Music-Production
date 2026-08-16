<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StyleSampling extends Model
{
    public const CATEGORIES = ['Dangdut', 'Campursari', 'Gamelan'];

    public const CUSTOMER_STYLE_CATEGORIES = self::CATEGORIES;

    public const PACKS = [
        'HM Dangdut Expansion Packs',
        'HM Campursari Expansion Packs',
        'HM Gamelan Expansion Packs',
    ];

    public const LEGACY_PACK_ALIASES = [
        'HM Dangdut Koplo Expansion Packs' => 'HM Dangdut Expansion Packs',
        'HM Pop Expansion Packs' => 'HM Campursari Expansion Packs',
    ];

    public const PACK_CATEGORY_MAP = [
        'HM Dangdut Expansion Packs' => ['Dangdut'],
        'HM Campursari Expansion Packs' => ['Campursari'],
        'HM Gamelan Expansion Packs' => ['Gamelan'],
    ];

    public const SAMPLING_TOTAL_SIZE_MB = 768;

    public const SAMPLING_REQUEST_PRICE = 800000;

    public const SAMPLING_REQUEST_PACKS = [
        'HM Dangdut Expansion Packs' => [
            'label' => 'Pack 1 - Dangdut',
            'short_label' => 'Pack 1 Dangdut',
            'size_mb' => 400,
            'price' => self::SAMPLING_REQUEST_PRICE,
            'summary' => 'Voice kit utama untuk style Dangdut. Beli pack ini dulu, lalu upload N27 agar admin connect voice ke keyboard.',
            'voice_kits' => [
                'Kendang Dangdut',
                'Ketipung',
                'Tabla Dangdut',
                'Suling Dangdut',
                'Bass Dangdut',
                'Guitar Dangdut',
                'Brass Dangdut',
            ],
        ],
        'HM Campursari Expansion Packs' => [
            'label' => 'Pack 2 - Campursari',
            'short_label' => 'Pack 2 Campursari',
            'size_mb' => 180,
            'price' => self::SAMPLING_REQUEST_PRICE,
            'summary' => 'Voice kit untuk style Campursari. Pack ini dipakai untuk style Campursari yang membutuhkan warna Jawa modern.',
            'voice_kits' => [
                'Kendang Campursari',
                'Suling Campursari',
                'Cak Cuk',
                'Gambang',
                'Siter',
                'Campursari Bass',
                'Campursari Drum Kit',
                'String Campursari',
            ],
        ],
        'HM Gamelan Expansion Packs' => [
            'label' => 'Pack 3 - Gamelan',
            'short_label' => 'Pack 3 Gamelan',
            'size_mb' => 188,
            'price' => self::SAMPLING_REQUEST_PRICE,
            'summary' => 'Voice kit untuk style Gamelan. Pack ini dipakai sebagai sumber sampling gamelan.',
            'voice_kits' => [
                'Saron',
                'Bonang',
                'Demung',
                'Kenong',
                'Gong Kempul',
                'Kendang Jawa',
                'Suling Gamelan',
                'Gender',
            ],
        ],
    ];

    public const ACCESSES = ['Free', 'Premium'];

    public const STATUSES = ['Draft', 'Published'];

    protected $fillable = [
        'name',
        'category',
        'pack',
        'access',
        'status',
        'style_file_path',
        'audio_path',
        'preview_path',
        'cover_image_path',
        'cover_image_url',
        'style_filename',
        'audio_filename',
        'preview_filename',
        'file_size',
        'description',
        'downloads_count',
        'search_embedding',
        'embedding_model',
        'embedding_source_hash',
        'embedding_updated_at',
        'ai_song_title',
        'ai_artist',
        'ai_genre',
        'ai_aliases',
        'ai_search_references',
        'ai_search_profile',
        'ai_enrichment_status',
        'ai_enrichment_source',
        'ai_enrichment_source_hash',
        'ai_enrichment_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'downloads_count' => 'integer',
            'search_embedding' => 'array',
            'embedding_updated_at' => 'datetime',
            'ai_aliases' => 'array',
            'ai_search_references' => 'array',
            'ai_enrichment_updated_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'Published');
    }

    public function hasTrustedAiMetadata(): bool
    {
        return in_array($this->ai_enrichment_status, ['verified', 'probable'], true);
    }

    public function getAudioUrlAttribute(): string
    {
        return $this->preview_url;
    }

    public function getPreviewUrlAttribute(): string
    {
        if ($this->preview_path) {
            return Storage::disk('public')->url($this->preview_path);
        }

        return asset('audio/audio2.mp3');
    }

    public function getCoverSrcAttribute(): string
    {
        if ($this->cover_image_path) {
            return Storage::disk('public')->url($this->cover_image_path);
        }

        return $this->cover_image_url ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=720&h=480&fit=crop';
    }

    public function getDisplayAudioNameAttribute(): string
    {
        return $this->display_style_name;
    }

    public function getDisplayStyleNameAttribute(): string
    {
        return $this->style_filename
            ?: basename((string) $this->style_file_path)
            ?: $this->audio_filename
            ?: "{$this->name}.sty";
    }

    public function getDisplayPreviewNameAttribute(): string
    {
        return $this->preview_filename ?: basename((string) $this->preview_path) ?: "preview-{$this->id}.mp3";
    }

    public function getHasStyleFileAttribute(): bool
    {
        return filled($this->style_file_path);
    }

    public function getHasPreviewFileAttribute(): bool
    {
        return filled($this->preview_path);
    }

    public static function samplingRequestOptions(): array
    {
        return self::SAMPLING_REQUEST_PACKS;
    }

    public static function samplingRequestPackNames(): array
    {
        return array_keys(self::SAMPLING_REQUEST_PACKS);
    }

    public static function samplingRequestOption(string $packName): array
    {
        $packName = self::normalizeSamplingPackName($packName);

        return self::SAMPLING_REQUEST_PACKS[$packName] ?? [
            'label' => $packName,
            'short_label' => $packName,
            'size_mb' => null,
            'price' => self::SAMPLING_REQUEST_PRICE,
            'summary' => 'Sampling pack berisi voice kit untuk dipakai oleh beberapa style. Harga tetap Rp 800.000.',
            'voice_kits' => [],
        ];
    }

    public static function normalizeSamplingPackName(?string $packName): ?string
    {
        if ($packName === null || $packName === '') {
            return $packName;
        }

        return self::LEGACY_PACK_ALIASES[$packName] ?? $packName;
    }

    public static function styleCategoriesForPack(string $packName): array
    {
        $packName = self::normalizeSamplingPackName($packName);

        return self::PACK_CATEGORY_MAP[$packName] ?? [];
    }

    public static function samplingPackForCategory(string $category, ?string $fallbackPack = null): ?string
    {
        foreach (self::PACK_CATEGORY_MAP as $packName => $categories) {
            if (in_array($category, $categories, true)) {
                return $packName;
            }
        }

        return $fallbackPack && array_key_exists($fallbackPack, self::SAMPLING_REQUEST_PACKS)
            ? $fallbackPack
            : null;
    }

    public static function samplingVoiceKits(string $packName): array
    {
        return self::samplingRequestOption($packName)['voice_kits'] ?? [];
    }

    public static function samplingUsageText(string $styleName, string $packName): string
    {
        $option = self::samplingRequestOption($packName);

        return "{$styleName} memakai {$option['label']} sebagai sumber voice kit. Beli/download sampling pack ini dulu, lalu upload N27 supaya admin connect voice kit ke keyboard.";
    }

    public static function samplingRequestAdvice(string $packName, ?int $keyboardStorageMb = null): string
    {
        $option = self::samplingRequestOption($packName);
        $sizeMb = $option['size_mb'] ?? null;

        if ($keyboardStorageMb !== null && $sizeMb !== null && $keyboardStorageMb < $sizeMb) {
            return "{$option['label']} sekitar {$sizeMb} MB, sementara kapasitas keyboard yang diisi {$keyboardStorageMb} MB. Harga sampling tetap Rp 800.000 dan isi voice kit perlu disesuaikan.";
        }

        return "{$option['label']} berisi voice kit untuk banyak style. Harga sampling tetap Rp 800.000; setelah bayar, upload N27 agar admin connect voice kit ke keyboard.";
    }

    public static function formatSamplingSize(?int $sizeMb): string
    {
        return $sizeMb ? "{$sizeMb} MB" : 'By request';
    }

    public static function formatSamplingPrice(?int $price): string
    {
        $price ??= self::SAMPLING_REQUEST_PRICE;

        return $price ? 'Rp '.number_format($price, 0, ',', '.') : 'By request';
    }
}
