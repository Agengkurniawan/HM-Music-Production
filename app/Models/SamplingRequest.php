<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SamplingRequest extends Model
{
    public const PAYMENT_PENDING = 'Pending';
    public const PAYMENT_PAID = 'Paid';

    public const STATUS_PENDING_PAYMENT = 'Pending Payment';
    public const STATUS_PAID = 'Paid';
    public const STATUS_N27_UPLOADED = 'N27 Uploaded';
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_READY = 'Ready';
    public const STATUS_COMPLETED = 'Completed';

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_N27_UPLOADED,
        self::STATUS_PROCESSING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'user_id',
        'style_sampling_id',
        'payment_id',
        'order_reference',
        'product_name',
        'pack_name',
        'keyboard_storage_mb',
        'customer_notes',
        'amount',
        'payment_status',
        'status',
        'n27_file_path',
        'n27_original_name',
        'n27_uploaded_at',
        'google_drive_link',
        'delivery_notes',
        'delivered_at',
        'completed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'keyboard_storage_mb' => 'integer',
            'n27_uploaded_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    public function getHasN27FileAttribute(): bool
    {
        return filled($this->n27_file_path);
    }

    public function getCanUploadN27Attribute(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID
            && ! in_array($this->status, [self::STATUS_READY, self::STATUS_COMPLETED], true);
    }

    public function getIsReadyAttribute(): bool
    {
        return filled($this->google_drive_link)
            && in_array($this->status, [self::STATUS_READY, self::STATUS_COMPLETED], true);
    }

    public function getN27DownloadNameAttribute(): string
    {
        return $this->n27_original_name ?: "{$this->order_reference}.n27";
    }

    public function getStatusClassAttribute(): string
    {
        return str($this->status)->lower()->replace(' ', '-')->toString();
    }

    public function n27Exists(): bool
    {
        return $this->n27_file_path
            && Storage::disk('public')->exists($this->n27_file_path);
    }
}
