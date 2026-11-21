<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\RecurringInvoice;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Invoicing\DocumentCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoicingTest extends TestCase
{
    use RefreshDatabase;

    private function accountant(): User
    {
        return User::factory()->create(['role' => 'accountant']);
    }

    public function test_calculator_applies_discount_before_tax(): void
    {
        $tax = Tax::create(['name' => 'VAT', 'rate' => 16, 'is_active' => true]);

        $result = app(DocumentCalculator::class)->calculate([
            ['description' => 'A', 'quantity' => 2, 'unit_price' => 100, 'tax_id' => $tax->id],
            ['description' => 'B', 'quantity' => 1, 'unit_price' => 50],
        ], 'percent', 10);

        $this->assertSame(250.0, $result['subtotal']);
        $this->assertSame(25.0, $result['discount_total']);
        $this->assertSame(28.8, $result['tax_total']); // 200 * 0.9 * 16%
        $this->assertSame(253.8, $result['total']);
    }

    public function test_accountant_creates_and_sends_an_invoice_that_deducts_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['selling_price' => 40, 'is_active' => true]);
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        ProductStock::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);

        $response = $this->actingAs($this->accountant())->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(15)->toDateString(),
            'currency' => 'USD',
            'warehouse_id' => $warehouse->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'send' => 1,
            'items' => [
                ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 3, 'unit_price' => 40],
                ['description' => '', 'quantity' => 1, 'unit_price' => 0], // empty row is ignored
            ],
        ]);

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('INV-000001', $invoice->number);
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertEquals(120, $invoice->total);
        $this->assertCount(1, $invoice->items);
        $this->assertTrue($invoice->stock_deducted);
        $this->assertSame(7, (int) ProductStock::first()->quantity);
    }

    public function test_cancelling_a_sent_invoice_returns_the_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        ProductStock::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);
        $user = $this->accountant();

        $this->actingAs($user)->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'issue_date' => today()->toDateString(), 'currency' => 'USD',
            'warehouse_id' => $warehouse->id, 'discount_type' => 'fixed', 'send' => 1,
            'items' => [['product_id' => $product->id, 'description' => 'X', 'quantity' => 2, 'unit_price' => 10]],
        ]);
        $invoice = Invoice::firstOrFail();
        $this->assertSame(3, (int) ProductStock::first()->quantity);

        $this->actingAs($user)->post(route('invoices.cancel', $invoice))->assertRedirect();

        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
        $this->assertSame(5, (int) ProductStock::first()->quantity);
    }

    public function test_payments_update_the_balance_and_status(): void
    {
        $invoice = Invoice::factory()->create(['total' => 300, 'subtotal' => 300, 'amount_due' => 300]);
        $user = $this->accountant();

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), ['amount' => 100, 'paid_on' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(Invoice::STATUS_PARTIALLY_PAID, $invoice->fresh()->status);
        $this->assertEquals(200, $invoice->fresh()->amount_due);

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), ['amount' => 500, 'paid_on' => today()->toDateString()])->assertSessionHasErrors('amount');

        $this->actingAs($user)->post(route('invoices.payments.store', $invoice), ['amount' => 200, 'paid_on' => today()->toDateString()]);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_estimate_converts_to_an_invoice_once(): void
    {
        $customer = Customer::factory()->create();
        $user = $this->accountant();

        $this->actingAs($user)->post(route('estimates.store'), [
            'customer_id' => $customer->id, 'issue_date' => today()->toDateString(), 'currency' => 'USD', 'discount_type' => 'fixed',
            'items' => [['description' => 'Consulting', 'quantity' => 4, 'unit_price' => 25]],
        ])->assertRedirect();

        $estimate = Estimate::firstOrFail();
        $this->assertSame('EST-000001', $estimate->number);

        $this->actingAs($user)->post(route('estimates.convert', $estimate))->assertRedirect();
        $this->assertSame('converted', $estimate->fresh()->status);
        $this->assertEquals(100, Invoice::firstOrFail()->total);

        $this->actingAs($user)->post(route('estimates.convert', $estimate))->assertSessionHasErrors('estimate');
        $this->assertSame(1, Invoice::count());
    }

    public function test_recurring_schedule_creates_invoices_and_advances(): void
    {
        $schedule = RecurringInvoice::create([
            'customer_id' => Customer::factory()->create()->id, 'frequency' => 'monthly',
            'next_run_date' => today()->subMonthNoOverflow(), 'due_in_days' => 10, 'auto_send' => true,
            'is_active' => true, 'currency' => 'USD', 'max_runs' => 5,
            'items' => [['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 30]],
        ]);

        $this->artisan('invoices:run-recurring')->assertSuccessful();

        $this->assertSame(2, Invoice::count()); // last month + this month (catch-up)
        $this->assertSame(Invoice::STATUS_SENT, Invoice::first()->status);
        $this->assertTrue($schedule->fresh()->next_run_date->isFuture());
        $this->assertSame(2, $schedule->fresh()->runs);
    }

    public function test_public_link_shows_invoice_and_marks_it_viewed(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('public.invoices.show', $invoice->public_token))->assertOk()->assertSee($invoice->number);
        $this->assertSame(Invoice::STATUS_VIEWED, $invoice->fresh()->status);

        $draft = Invoice::factory()->create(['status' => Invoice::STATUS_DRAFT]);
        $this->get(route('public.invoices.show', $draft->public_token))->assertNotFound();
    }

    public function test_pdf_uses_gotenberg_when_configured_and_falls_back_to_html(): void
    {
        $invoice = Invoice::factory()->create();
        $user = $this->accountant();

        $this->actingAs($user)->get(route('invoices.pdf', $invoice))->assertOk()->assertSee($invoice->number);

        config(['services.gotenberg.url' => 'http://gotenberg:3000']);
        Http::fake(['gotenberg:3000/*' => Http::response('%PDF-1.7 fake', 200, ['Content-Type' => 'application/pdf'])]);

        $this->actingAs($user)->get(route('invoices.pdf', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/forms/chromium/convert/html'));
    }

    public function test_invoicing_pages_render_for_accountants_and_are_forbidden_for_workers(): void
    {
        $invoice = Invoice::factory()->create();
        $user = $this->accountant();

        foreach (['invoices.index', 'invoices.create', 'estimates.index', 'estimates.create', 'customers.index', 'customers.create', 'expenses.index', 'expenses.create', 'recurring-invoices.index', 'recurring-invoices.create', 'insights'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
        $this->actingAs($user)->get(route('invoices.show', $invoice))->assertOk();
        $this->actingAs($user)->get(route('customers.show', $invoice->customer))->assertOk();
        $this->actingAs($user)->get(route('invoicing-settings.edit'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'worker']))->get(route('invoices.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('invoicing-settings.edit'))->assertOk();
    }
}
