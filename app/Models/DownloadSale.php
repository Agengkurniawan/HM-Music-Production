<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadSale extends Model
{
    protected $fillable = [
        'user_id',
        'style_sampling_id',
        'download_type',
        'file_name',
        'style_name',
        'status',
        'amount',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'downloaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function styleSampling(): BelongsTo
    {
        return $this->belongsTo(StyleSampling::class);
    }
}
