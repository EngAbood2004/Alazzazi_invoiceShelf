<?php

namespace Tests\Feature;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_a_coherent_month_of_demo_activity_and_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseCount('products', 8);
        $this->assertDatabaseCount('customers', 8);
        $this->assertDatabaseCount('sales', 60);
        $this->assertDatabaseCount('invoices', 12);
        $this->assertDatabaseCount('invoice_items', 24);
        $this->assertDatabaseCount('estimates', 4);
        $this->assertDatabaseCount('expenses', 10);
        $this->assertSame(
            30,
            DB::table('sales')->whereBetween('sale_date', [today()->subDays(29), today()])->distinct()->count('sale_date'),
        );
        $this->assertSame(
            (int) DB::table('product_stocks')->sum('quantity'),
            (int) DB::table('stock_movements')->where('type', 'in')->sum('quantity')
                - (int) DB::table('stock_movements')->where('type', 'out')->sum('quantity'),
        );

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('sales', 60);
        $this->assertDatabaseCount('invoices', 12);
    }
}
