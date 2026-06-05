<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const DEFAULTS = [
        'banner_title' => 'HM Music Production Style Sampling',
        'homepage_banner' => null,
        'logo' => null,
        'subscription_price' => '29000',
        'plan_duration' => '30 Days',
        'payment_gateway' => 'Manual Bank Transfer',
        'merchant_key' => 'hm-production-key',
        'instagram' => 'https://instagram.com/hmmusicproduction',
        'youtube' => 'https://youtube.com/@hmmusicproduction',
        'smtp_host' => 'smtp.mailtrap.io',
        'smtp_port' => '587',
        'smtp_encryption' => 'TLS',
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function values(): array
    {
        return array_replace(
            self::DEFAULTS,
            self::query()->pluck('value', 'key')->all(),
        );
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return self::values()[$key] ?? $default;
    }

    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
