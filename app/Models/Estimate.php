<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Estimate extends Model
{
    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'converted'];

    protected $fillable = [
        'number', 'customer_id', 'status', 'issue_date', 'expiry_date', 'currency', 'discount_type',
        'discount_value', 'subtotal', 'discount_total', 'tax_total', 'total', 'notes', 'terms',
        'public_token', 'sent_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date', 'expiry_date' => 'date', 'discount_value' => 'decimal:2',
            'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2',
            'total' => 'decimal:2', 'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Estimate $estimate): void {
            $estimate->public_token ??= Str::random(48);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class)->orderBy('position');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isExpired(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true) && $this->expiry_date?->lt(today());
    }
}
