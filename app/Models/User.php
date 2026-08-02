<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomerResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'google_id', 'facebook_id', 'email_verified_at', 'password', 'role', 'status', 'plan', 'last_activity', 'profile_photo_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasSocialLogin(): bool
    {
        return filled($this->google_id) || filled($this->facebook_id);
    }

    public function socialLoginProviderLabel(): ?string
    {
        return match (true) {
            filled($this->google_id) && filled($this->facebook_id) => 'Google / Facebook',
            filled($this->google_id) => 'Google',
            filled($this->facebook_id) => 'Facebook',
            default => null,
        };
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            if (Str::startsWith($this->profile_photo_path, ['http://', 'https://', 'data:'])) {
                return $this->profile_photo_path;
            }

            return Storage::disk('public')->url($this->profile_photo_path);
        }

        return self::defaultAvatarUrlFor($this->name, $this->email);
    }

    public static function defaultAvatarUrlFor(?string $name, ?string $email = null): string
    {
        $initials = self::avatarInitials($name, $email);
        $escapedInitials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        $seed = crc32(Str::lower($email ?: $name ?: $initials));
        $palettes = [
            ['#2563eb', '#0f766e'],
            ['#7c3aed', '#db2777'],
            ['#0891b2', '#4f46e5'],
            ['#16a34a', '#0e7490'],
            ['#ea580c', '#be123c'],
        ];
        [$start, $end] = $palettes[$seed % count($palettes)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160" role="img" aria-label="Avatar">'
            .'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="'.$start.'"/><stop offset="100%" stop-color="'.$end.'"/></linearGradient></defs>'
            .'<rect width="160" height="160" rx="80" fill="url(#g)"/>'
            .'<text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="58" font-weight="700">'.$escapedInitials.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private static function avatarInitials(?string $name, ?string $email = null): string
    {
        $source = trim(Str::ascii($name ?: Str::before((string) $email, '@') ?: 'HM'));
        $parts = preg_split('/[\s._-]+/', $source, -1, PREG_SPLIT_NO_EMPTY) ?: ['HM'];

        $initials = collect($parts)
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'HM';
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'Active')
            ->orderByDesc('expires_at')
            ->orderByDesc('id');
    }

    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function samplingRequests()
    {
        return $this->hasMany(SamplingRequest::class);
    }

    public function latestSamplingRequest()
    {
        return $this->hasOne(SamplingRequest::class)->latestOfMany();
    }

    public function downloadSales()
    {
        return $this->hasMany(DownloadSale::class);
    }

    public function latestDownloadSale()
    {
        return $this->hasOne(DownloadSale::class)->latestOfMany();
    }
}
