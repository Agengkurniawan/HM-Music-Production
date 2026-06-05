<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'package',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->status === 'Pending' || $this->latestPayment?->status === 'Pending') {
            return 'Pending Payment';
        }

        if ($this->status === 'Cancelled') {
            return 'Cancelled';
        }

        if ($this->status === 'Active' && $this->expires_at?->isPast()) {
            return 'Expired';
        }

        if ($this->status === 'Active' && $this->expires_at?->between(now(), now()->addDays(7))) {
            return 'Expiring Soon';
        }

        return $this->status ?: 'Unknown';
    }

    public function getLifecycleClassAttribute(): string
    {
        return str($this->lifecycle_status)->lower()->replace(' ', '-')->toString();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
