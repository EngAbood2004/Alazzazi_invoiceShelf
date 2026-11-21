<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'created_by',
        'product_name',
        'sku',
        'quantity',
        'stock_after',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_revenue',
        'profit',
        'sale_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'profit' => 'decimal:2',
        'sale_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function profitPercentage(): ?float
    {
        $cost = (float) $this->total_cost;

        return $cost > 0 ? ((float) $this->profit / $cost) * 100 : null;
    }
}
