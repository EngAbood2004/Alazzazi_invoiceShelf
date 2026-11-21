<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'display_status' => $this->displayStatus(),
            'days_overdue' => $this->daysOverdue(),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'discount_total' => (float) $this->discount_total,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'notes' => $this->notes,
            'public_url' => $this->status !== 'draft' ? $this->publicUrl() : null,
            'pdf_url' => $this->status !== 'draft' ? route('public.invoices.pdf', $this->public_token) : null,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => (float) $item->tax_rate,
                'line_total' => (float) $item->line_total,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'paid_on' => $p->paid_on->toDateString(),
                'method' => $p->gateway === 'lahza' ? 'Lahza' : $p->paymentMethod?->name,
                'reference' => $p->reference,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
