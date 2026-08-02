<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'package',
        'amount',
        'method',
        'status',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function samplingRequest(): HasOne
    {
        return $this->hasOne(SamplingRequest::class);
    }
}
