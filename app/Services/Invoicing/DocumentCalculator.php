<?php

namespace App\Services\Invoicing;

use App\Models\Tax;

/**
 * Turns raw line input into priced lines and document totals.
 *
 * Rules: line subtotal = qty x price; the document discount (fixed or %) is
 * spread over the lines in proportion to their subtotal; each line's tax is
 * charged on its discounted amount.
 */
class DocumentCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, discount_total: float, tax_total: float, total: float}
     */
    public function calculate(array $lines, string $discountType = 'fixed', float $discountValue = 0): array
    {
        $taxes = Tax::query()->whereIn('id', array_filter(array_column($lines, 'tax_id')))->get()->keyBy('id');

        $items = [];
        foreach (array_values($lines) as $position => $line) {
            $quantity = round((float) ($line['quantity'] ?? 0), 3);
            $price = round((float) ($line['unit_price'] ?? 0), 2);
            $tax = isset($line['tax_id']) && $line['tax_id'] ? $taxes->get((int) $line['tax_id']) : null;

            $items[] = [
                'product_id' => ($line['product_id'] ?? null) ?: null,
                'tax_id' => $tax?->id,
                'description' => trim((string) ($line['description'] ?? '')),
                'quantity' => $quantity,
                'unit_price' => $price,
                'tax_rate' => $tax ? (float) $tax->rate : 0.0,
                'line_subtotal' => round($quantity * $price, 2),
                'position' => $position,
            ];
        }

        $subtotal = round(array_sum(array_column($items, 'line_subtotal')), 2);

        $discount = $discountType === 'percent'
            ? round($subtotal * min(max($discountValue, 0), 100) / 100, 2)
            : round(min(max($discountValue, 0), $subtotal), 2);

        $ratio = $subtotal > 0 ? 1 - ($discount / $subtotal) : 1;

        $taxTotal = 0.0;
        foreach ($items as &$item) {
            $item['tax_amount'] = round($item['line_subtotal'] * $ratio * $item['tax_rate'] / 100, 2);
            $item['line_total'] = round($item['line_subtotal'] + $item['tax_amount'], 2);
            $taxTotal += $item['tax_amount'];
        }
        unset($item);

        $taxTotal = round($taxTotal, 2);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $taxTotal,
            'total' => round($subtotal - $discount + $taxTotal, 2),
        ];
    }
}
