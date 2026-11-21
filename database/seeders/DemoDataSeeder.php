<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    private const MARKER = 'demo_data_seeded_at';

    public function run(): void
    {
        if (DB::table('business_settings')->where('key', self::MARKER)->exists()) {
            $this->command?->info('Demo data already exists; no records were changed.');

            return;
        }

        DB::transaction(function (): void {
            $this->seedDemoData();
            DB::table('business_settings')->insert([
                'key' => self::MARKER,
                'value' => now()->toDateTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->command?->info('Created one month of demo products, stock movements, sales, invoices, estimates, and expenses.');
    }

    private function seedDemoData(): void
    {
        $startDate = today()->subDays(29);
        $categories = [];

        foreach ([
            ['demo-electronics', 'تجريبي - إلكترونيات'],
            ['demo-accessories', 'تجريبي - إكسسوارات'],
            ['demo-office', 'تجريبي - مستلزمات مكتبية'],
        ] as [$slug, $name]) {
            $categories[$slug] = Category::create([
                'name' => $name,
                'slug' => $slug,
                'description' => 'بيانات تجريبية للعرض فقط',
            ]);
        }

        $supplier = Supplier::create([
            'name' => 'مورد تجريبي - شركة النور',
            'contact_name' => 'موظف تجريبي',
            'phone' => '+962790000001',
            'email' => 'supplier@example.test',
            'address' => 'عمّان',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'مستودع العرض التجريبي',
            'code' => 'DEMO-WH-01',
            'address' => 'عمّان',
        ]);

        $tax = Tax::create(['name' => 'ضريبة تجريبية', 'rate' => 16]);
        $paymentMethods = collect([
            'نقدًا - تجريبي',
            'بطاقة - تجريبي',
            'تحويل بنكي - تجريبي',
        ])->mapWithKeys(fn (string $name) => [
            $name => PaymentMethod::create([
                'name' => $name,
                'scope' => 'local',
                'currency' => 'JOD',
                'is_active' => true,
            ]),
        ]);

        $productRows = [
            ['demo-electronics', 'DEMO-EL-001', 'سماعة لاسلكية', 18, 32, 8],
            ['demo-electronics', 'DEMO-EL-002', 'شاحن سريع', 7, 14, 12],
            ['demo-electronics', 'DEMO-EL-003', 'مصباح مكتبي ذكي', 15, 27, 6],
            ['demo-accessories', 'DEMO-AC-001', 'حافظة هاتف', 3, 8, 20],
            ['demo-accessories', 'DEMO-AC-002', 'حامل هاتف', 4, 10, 15],
            ['demo-accessories', 'DEMO-AC-003', 'سماعة سلكية', 5, 12, 12],
            ['demo-office', 'DEMO-OF-001', 'دفتر ملاحظات', 1.5, 4, 25],
            ['demo-office', 'DEMO-OF-002', 'طقم أقلام', 2, 6, 18],
        ];
        $products = [];
        $remainingStock = [];

        foreach ($productRows as [$categorySlug, $sku, $name, $cost, $price, $minimum]) {
            $product = Product::create([
                'category_id' => $categories[$categorySlug]->id,
                'supplier_id' => $supplier->id,
                'name' => $name.' (تجريبي)',
                'sku' => $sku,
                'description' => 'منتج تجريبي للعرض فقط',
                'unit' => 'قطعة',
                'purchase_price' => $cost,
                'selling_price' => $price,
                'minimum_stock' => $minimum,
            ]);
            $products[] = $product;
            $remainingStock[$product->id] = 100;

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'quantity' => 100,
                'stock_before' => 0,
                'stock_after' => 100,
                'reference_number' => 'DEMO-OPEN-'.$sku,
                'movement_date' => $startDate,
                'notes' => 'رصيد افتتاحي تجريبي',
            ]);
        }

        $customers = [];
        for ($index = 1; $index <= 8; $index++) {
            $customers[] = Customer::create([
                'name' => sprintf('عميل تجريبي %02d', $index),
                'company_name' => sprintf('متجر تجريبي %02d', $index),
                'email' => sprintf('customer%02d@example.test', $index),
                'phone' => sprintf('+962790000%03d', $index + 10),
                'whatsapp' => sprintf('+962790000%03d', $index + 10),
                'tax_number' => sprintf('DEMO-TAX-%03d', $index),
                'address' => 'عنوان تجريبي',
                'city' => 'عمّان',
                'country' => 'الأردن',
                'currency' => 'JOD',
                'notes' => 'حساب تجريبي للعرض فقط',
            ]);
        }

        for ($day = 0; $day < 30; $day++) {
            for ($slot = 0; $slot < 2; $slot++) {
                $product = $products[($day * 2 + $slot) % count($products)];
                $quantity = 1 + (($day + $slot) % 3);
                $before = $remainingStock[$product->id];
                $after = $before - $quantity;
                $saleNumber = sprintf('DEMO-SALE-%03d', $day * 2 + $slot + 1);
                $saleDate = $startDate->copy()->addDays($day);
                $unitCost = (float) $product->purchase_price;
                $unitPrice = (float) $product->selling_price;

                Sale::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'stock_after' => $after,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'total_cost' => round($unitCost * $quantity, 2),
                    'total_revenue' => round($unitPrice * $quantity, 2),
                    'profit' => round(($unitPrice - $unitCost) * $quantity, 2),
                    'sale_date' => $saleDate,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_number' => $saleNumber,
                    'movement_date' => $saleDate,
                    'notes' => 'بيع مباشر تجريبي',
                ]);

                $remainingStock[$product->id] = $after;
            }
        }

        foreach ($products as $product) {
            ProductStock::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $remainingStock[$product->id],
            ]);
        }

        $invoiceStatuses = [
            'paid', 'paid', 'partially_paid', 'sent', 'paid', 'viewed',
            'sent', 'partially_paid', 'paid', 'sent', 'cancelled', 'draft',
        ];

        foreach ($invoiceStatuses as $index => $status) {
            $issueDate = $startDate->copy()->addDays(1 + ($index * 2));
            $customer = $customers[$index % count($customers)];
            $lineProducts = [
                $products[$index % count($products)],
                $products[($index + 3) % count($products)],
            ];
            $subtotal = 0.0;
            $taxTotal = 0.0;
            $itemRows = [];

            foreach ($lineProducts as $position => $product) {
                $quantity = 1 + (($index + $position) % 2);
                $unitPrice = (float) $product->selling_price;
                $lineSubtotal = round($quantity * $unitPrice, 2);
                $taxAmount = round($lineSubtotal * 0.16, 2);
                $subtotal += $lineSubtotal;
                $taxTotal += $taxAmount;
                $itemRows[] = compact('product', 'quantity', 'unitPrice', 'lineSubtotal', 'taxAmount', 'position');
            }

            $total = round($subtotal + $taxTotal, 2);
            $paidAmount = match ($status) {
                'paid' => $total,
                'partially_paid' => round($total * 0.4, 2),
                default => 0.0,
            };
            $dueAmount = $status === 'cancelled' ? 0 : round($total - $paidAmount, 2);
            $invoice = Invoice::create([
                'number' => sprintf('DEMO-INV-%03d', $index + 1),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'status' => $status,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->copy()->addDays(14),
                'currency' => 'JOD',
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'tax_total' => $taxTotal,
                'total' => $total,
                'amount_paid' => $paidAmount,
                'amount_due' => $dueAmount,
                'stock_deducted' => false,
                'sent_at' => in_array($status, ['paid', 'partially_paid', 'sent', 'viewed'], true)
                    ? $issueDate->copy()->addDay()
                    : null,
                'viewed_at' => $status === 'viewed' ? $issueDate->copy()->addDays(2) : null,
                'paid_at' => $status === 'paid' ? $issueDate->copy()->addDays(3) : null,
            ]);

            foreach ($itemRows as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product']->id,
                    'tax_id' => $tax->id,
                    'description' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unitPrice'],
                    'tax_rate' => 16,
                    'line_subtotal' => $item['lineSubtotal'],
                    'tax_amount' => $item['taxAmount'],
                    'line_total' => $item['lineSubtotal'] + $item['taxAmount'],
                    'position' => $item['position'],
                ]);
            }

            if ($paidAmount > 0) {
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_method_id' => $paymentMethods->values()[$index % $paymentMethods->count()]->id,
                    'amount' => $paidAmount,
                    'paid_on' => $issueDate->copy()->addDays(3),
                    'reference' => sprintf('DEMO-PAY-%03d', $index + 1),
                    'recorded_by' => null,
                ]);
            }
        }

        foreach (['مسودة', 'مرسل', 'مقبول', 'مرفوض'] as $index => $status) {
            $customer = $customers[$index];
            $product = $products[($index + 1) % count($products)];
            $quantity = $index + 1;
            $lineSubtotal = round($quantity * (float) $product->selling_price, 2);
            $taxAmount = round($lineSubtotal * 0.16, 2);
            $issueDate = $startDate->copy()->addDays(4 + ($index * 5));
            $estimate = Estimate::create([
                'number' => sprintf('DEMO-EST-%03d', $index + 1),
                'customer_id' => $customer->id,
                'status' => ['draft', 'sent', 'accepted', 'rejected'][$index],
                'issue_date' => $issueDate,
                'expiry_date' => $issueDate->copy()->addDays(14),
                'currency' => 'JOD',
                'subtotal' => $lineSubtotal,
                'tax_total' => $taxAmount,
                'total' => $lineSubtotal + $taxAmount,
                'sent_at' => $index > 0 ? $issueDate->copy()->addDay() : null,
            ]);
            $estimate->items()->create([
                'product_id' => $product->id,
                'tax_id' => $tax->id,
                'description' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->selling_price,
                'tax_rate' => 16,
                'line_subtotal' => $lineSubtotal,
                'tax_amount' => $taxAmount,
                'line_total' => $lineSubtotal + $taxAmount,
                'position' => 0,
            ]);
        }

        $expenseCategories = collect(['مشتريات تجريبية', 'نقل تجريبي', 'تشغيل تجريبي'])
            ->mapWithKeys(fn (string $name) => [
                $name => ExpenseCategory::create(['name' => $name]),
            ]);
        $expenseNames = array_keys($expenseCategories->all());

        for ($index = 0; $index < 10; $index++) {
            Expense::create([
                'expense_category_id' => $expenseCategories[$expenseNames[$index % count($expenseNames)]]->id,
                'supplier_id' => $supplier->id,
                'payment_method_id' => $paymentMethods->values()[$index % $paymentMethods->count()]->id,
                'amount' => 12 + ($index * 7.5),
                'currency' => 'JOD',
                'spent_on' => $startDate->copy()->addDays(2 + ($index * 3)),
                'description' => sprintf('مصروف تجريبي رقم %02d', $index + 1),
            ]);
        }
    }
}
