<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const DEFAULT_SUBSCRIPTION_PRICE = 55000;

    public const DEFAULTS = [
        'subscription_price' => '55000',
        'plan_duration' => '30 Days',
        'merchant_key' => '',
        'midtrans_client_key' => '',
        'midtrans_is_production' => '0',
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function values(): array
    {
        $values = array_replace(
            self::DEFAULTS,
            self::query()->pluck('value', 'key')->all(),
        );

        if ((int) ($values['subscription_price'] ?? 0) < self::DEFAULT_SUBSCRIPTION_PRICE) {
            $values['subscription_price'] = (string) self::DEFAULT_SUBSCRIPTION_PRICE;
        }

        return $values;
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
