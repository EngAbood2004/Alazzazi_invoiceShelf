<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Inventory\StockMovementService;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    public function createSale(array $data, ?User $user = null): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $product = Product::query()
                ->whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $data['quantity'];
            $unitCost = round((float) $product->purchase_price, 2);
            $unitPrice = round((float) $data['unit_price'], 2);
            $totalCost = round($unitCost * $quantity, 2);
            $totalRevenue = round($unitPrice * $quantity, 2);

            $sale = Sale::create([
                'product_id' => $product->id,
                'warehouse_id' => $data['warehouse_id'],
                'created_by' => $user?->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'total_revenue' => $totalRevenue,
                'profit' => round($totalRevenue - $totalCost, 2),
                'sale_date' => $data['sale_date'],
            ]);

            $movement = $this->stockMovementService->createMovement([
                'product_id' => $product->id,
                'warehouse_id' => $data['warehouse_id'],
                'type' => 'out',
                'quantity' => $quantity,
                'reference_number' => 'SALE-'.$sale->id,
                'movement_date' => $data['sale_date'],
                'notes' => 'Sale #'.$sale->id,
            ], $user);

            $sale->update(['stock_after' => $movement->stock_after]);

            return $sale;
        });
    }
}
