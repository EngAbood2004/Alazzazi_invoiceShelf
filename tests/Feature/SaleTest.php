<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWarehouseManager(): static
    {
        return $this->actingAs(User::factory()->create(['role' => 'warehouse_manager']));
    }

    public function test_sale_records_profit_and_reduces_the_selected_warehouse_stock(): void
    {
        $product = Product::factory()->create([
            'purchase_price' => 100,
            'selling_price' => 125,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
        ]);

        $response = $this->actingAsWarehouseManager()->post(route('sales.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
            'unit_price' => 90,
            'sale_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('sales.index', [
            'period' => 'monthly',
            'year' => now()->year,
        ]));
        $this->assertDatabaseHas('sales', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
            'stock_after' => 5,
            'unit_cost' => 100,
            'unit_price' => 90,
            'total_cost' => 300,
            'total_revenue' => 270,
            'profit' => -30,
        ]);
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'out',
            'quantity' => 3,
            'stock_before' => 8,
            'stock_after' => 5,
        ]);

        $sale = Sale::firstOrFail();
        $this->assertEquals(-10, $sale->profitPercentage());
    }

    public function test_sale_cannot_exceed_available_stock_and_does_not_create_partial_records(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAsWarehouseManager()->post(route('sales.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
            'unit_price' => 100,
            'sale_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
        ]);
    }

    public function test_analytics_calculates_revenue_profit_and_profit_rate(): void
    {
        $product = Product::factory()->create([
            'purchase_price' => 50,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
        ]);
        $user = User::factory()->create(['role' => 'warehouse_manager']);

        $this->actingAs($user)->post(route('sales.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'unit_price' => 75,
            'sale_date' => now()->toDateString(),
        ]);

        $response = $this->get(route('sales.index', [
            'period' => 'monthly',
            'year' => now()->year,
        ]));

        $response->assertOk()
            ->assertSee('150.00')
            ->assertSee('100.00')
            ->assertSee('50.00%');
    }

    public function test_sales_analytics_page_accepts_daily_monthly_and_yearly_periods(): void
    {
        $user = User::factory()->create(['role' => 'warehouse_manager']);

        foreach (['daily', 'monthly', 'yearly'] as $period) {
            $response = $this->actingAs($user)->get(route('sales.index', [
                'period' => $period,
                'year' => now()->year,
            ]));

            $response->assertOk()->assertSee('salesChart');
        }
    }

    public function test_inventory_pages_render_arabic_labels(): void
    {
        $this->app->setLocale('ar');
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('products.index'))->assertOk()->assertSee('المنتجات');
        $this->get(route('categories.index'))->assertOk()->assertSee('التصنيفات');
        $this->get(route('suppliers.index'))->assertOk()->assertSee('الموردون');
        $this->get(route('warehouses.index'))->assertOk()->assertSee('المستودعات');
        $this->get(route('stock-movements.index'))->assertOk()->assertSee('سجل حركات المخزون');
        $this->get(route('sales.index'))->assertOk()->assertSee('تحليل المبيعات');
    }
}
