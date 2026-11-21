<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Header-only invoice with consistent totals (no line items).
 *
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 50, 2000);

        return [
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'status' => Invoice::STATUS_SENT,
            'issue_date' => today()->subDays(10),
            'due_date' => today()->addDays(5),
            'currency' => 'USD',
            'discount_type' => 'fixed',
            'subtotal' => $total,
            'total' => $total,
            'amount_paid' => 0,
            'amount_due' => $total,
            'sent_at' => now(),
        ];
    }

    public function overdue(int $days = 10): static
    {
        return $this->state(fn () => ['issue_date' => today()->subDays($days + 15), 'due_date' => today()->subDays($days)]);
    }
}
