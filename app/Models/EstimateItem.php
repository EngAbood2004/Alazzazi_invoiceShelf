<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id', 'product_id', 'tax_id', 'description', 'quantity', 'unit_price',
        'tax_rate', 'line_subtotal', 'tax_amount', 'line_total', 'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'tax_rate' => 'decimal:3',
            'line_subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'line_total' => 'decimal:2',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
