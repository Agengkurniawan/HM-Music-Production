<?php

namespace App\Support;

use Illuminate\Support\Str;

class SocialAuthProviders
{
    public static function all(): array
    {
        return [
            'google' => self::provider('google', 'Google'),
            'facebook' => self::provider('facebook', 'Facebook'),
        ];
    }

    public static function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    private static function provider(string $provider, string $label): array
    {
        $envPrefix = Str::upper($provider);

        return [
            'key' => $provider,
            'label' => $label,
            'configured' => self::isConfigured($provider),
            'setup_message' => "Login {$label} belum aktif. Isi {$envPrefix}_CLIENT_ID dan {$envPrefix}_CLIENT_SECRET di .env.",
        ];
    }
}
