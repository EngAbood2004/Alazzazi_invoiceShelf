<?php

namespace App\Services\Invoicing;

use App\Models\BusinessSetting;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Services\Inventory\StockMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business rules for invoices: create/update with priced lines, send,
 * cancel, record payments, convert estimates and run recurring schedules.
 * Stock is deducted when an invoice with a warehouse is first sent and
 * returned if it is cancelled.
 */
class InvoiceService
{
    public function __construct(
        private readonly DocumentCalculator $calculator,
        private readonly StockMovementService $stock,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated header fields + 'items'
     */
    public function create(array $data, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($data, $user) {
            $invoice = new Invoice($this->headerFields($data));
            $invoice->number = $data['number'] ?? DocumentNumber::next(Invoice::class, 'invoice_prefix');
            $invoice->status = Invoice::STATUS_DRAFT;
            $invoice->created_by = $user?->id;
            $this->fillTotals($invoice, $data);
            $invoice->save();
            $invoice->items()->createMany($this->lastItems);

            return $invoice->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        if (! $invoice->isEditable()) {
            throw ValidationException::withMessages(['invoice' => __('This invoice can no longer be edited.')]);
        }

        return DB::transaction(function () use ($invoice, $data) {
            $invoice->fill($this->headerFields($data));
            $this->fillTotals($invoice, $data);
            $invoice->save();
            $invoice->items()->delete();
            $invoice->items()->createMany($this->lastItems);

            return $invoice->load('items');
        });
    }

    /** Marks a draft as sent and takes the products out of stock once. */
    public function markSent(Invoice $invoice, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            if ($invoice->status === Invoice::STATUS_DRAFT) {
                $invoice->status = Invoice::STATUS_SENT;
            }
            $invoice->sent_at ??= now();

            if ($invoice->warehouse_id && ! $invoice->stock_deducted) {
                foreach ($invoice->items()->whereNotNull('product_id')->get() as $item) {
                    $this->stock->createMovement([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'type' => 'out',
                        'quantity' => max(1, (int) round((float) $item->quantity)),
                        'reference_number' => $invoice->number,
                        'movement_date' => $invoice->issue_date->toDateString(),
                        'notes' => 'Invoice '.$invoice->number,
                    ], $user);
                }
                $invoice->stock_deducted = true;
            }

            $invoice->save();

            return $invoice;
        });
    }

    public function markViewed(Invoice $invoice): void
    {
        if ($invoice->viewed_at === null && $invoice->status !== Invoice::STATUS_DRAFT) {
            $invoice->viewed_at = now();
            if ($invoice->status === Invoice::STATUS_SENT) {
                $invoice->status = Invoice::STATUS_VIEWED;
            }
            $invoice->save();
        }
    }

    public function cancel(Invoice $invoice, ?User $user = null): Invoice
    {
        if ((float) $invoice->amount_paid > 0) {
            throw ValidationException::withMessages(['invoice' => __('An invoice with recorded payments cannot be cancelled.')]);
        }

        return DB::transaction(function () use ($invoice, $user) {
            if ($invoice->stock_deducted && $invoice->warehouse_id) {
                foreach ($invoice->items()->whereNotNull('product_id')->get() as $item) {
                    $this->stock->createMovement([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'type' => 'in',
                        'quantity' => max(1, (int) round((float) $item->quantity)),
                        'reference_number' => $invoice->number,
                        'movement_date' => now()->toDateString(),
                        'notes' => 'Cancelled invoice '.$invoice->number,
                    ], $user);
                }
                $invoice->stock_deducted = false;
            }

            $invoice->status = Invoice::STATUS_CANCELLED;
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data  amount, paid_on, payment_method_id?, reference?, notes?, gateway?, gateway_reference?
     */
    public function recordPayment(Invoice $invoice, array $data, ?User $user = null): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data, $user) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages(['amount' => __('Send the invoice before recording a payment.')]);
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > (float) $invoice->amount_due + 0.001) {
                throw ValidationException::withMessages(['amount' => __('The payment must be between 0.01 and the amount due (:due).', ['due' => number_format((float) $invoice->amount_due, 2)])]);
            }

            $payment = $invoice->payments()->create([
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'amount' => $amount,
                'paid_on' => $data['paid_on'] ?? today()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'gateway' => $data['gateway'] ?? null,
                'gateway_reference' => $data['gateway_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user?->id,
            ]);

            $this->refreshBalance($invoice);

            return $payment;
        });
    }

    public function refreshBalance(Invoice $invoice): Invoice
    {
        $paid = round((float) $invoice->payments()->sum('amount'), 2);
        $due = round(max(0, (float) $invoice->total - $paid), 2);

        $invoice->amount_paid = $paid;
        $invoice->amount_due = $due;

        if ($invoice->status !== Invoice::STATUS_CANCELLED && $invoice->status !== Invoice::STATUS_DRAFT) {
            if ($due <= 0) {
                $invoice->status = Invoice::STATUS_PAID;
                $invoice->paid_at ??= now();
            } elseif ($paid > 0) {
                $invoice->status = Invoice::STATUS_PARTIALLY_PAID;
            }
        }

        $invoice->save();

        return $invoice;
    }

    public function convertEstimate(Estimate $estimate, ?User $user = null): Invoice
    {
        if ($estimate->status === 'converted' || $estimate->invoice()->exists()) {
            throw ValidationException::withMessages(['estimate' => __('This estimate was already converted.')]);
        }

        return DB::transaction(function () use ($estimate, $user) {
            $invoice = $this->create([
                'customer_id' => $estimate->customer_id,
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays((int) BusinessSetting::get('invoice_due_days'))->toDateString(),
                'currency' => $estimate->currency,
                'discount_type' => $estimate->discount_type,
                'discount_value' => (float) $estimate->discount_value,
                'notes' => $estimate->notes,
                'terms' => $estimate->terms,
                'items' => $estimate->items->map(fn ($item) => $item->only(['product_id', 'tax_id', 'description', 'quantity', 'unit_price']))->all(),
            ], $user);

            $invoice->update(['estimate_id' => $estimate->id]);
            $estimate->update(['status' => 'converted']);

            return $invoice;
        });
    }

    /** Creates the invoice for a recurring schedule and moves it to its next date. */
    public function runRecurring(RecurringInvoice $schedule): ?Invoice
    {
        if (! $schedule->is_active || $schedule->isFinished()) {
            $schedule->update(['is_active' => false]);

            return null;
        }

        return DB::transaction(function () use ($schedule) {
            $issue = $schedule->next_run_date->copy();

            $invoice = $this->create([
                'customer_id' => $schedule->customer_id,
                'issue_date' => $issue->toDateString(),
                'due_date' => $issue->copy()->addDays($schedule->due_in_days)->toDateString(),
                'currency' => $schedule->currency,
                'notes' => $schedule->notes,
                'items' => $schedule->items,
            ]);
            $invoice->update(['recurring_invoice_id' => $schedule->id]);

            if ($schedule->auto_send) {
                $this->markSent($invoice);
            }

            $schedule->runs++;
            $schedule->next_run_date = $schedule->nextDateAfter($issue);
            if ($schedule->isFinished()) {
                $schedule->is_active = false;
            }
            $schedule->save();

            return $invoice;
        });
    }

    /** @var array<int, array<string, mixed>> */
    private array $lastItems = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function headerFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'customer_id', 'warehouse_id', 'issue_date', 'due_date', 'currency',
            'discount_type', 'discount_value', 'notes', 'terms',
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillTotals(Invoice $invoice, array $data): void
    {
        $result = $this->calculator->calculate(
            $data['items'] ?? [],
            $data['discount_type'] ?? 'fixed',
            (float) ($data['discount_value'] ?? 0),
        );

        $this->lastItems = $result['items'];

        $invoice->subtotal = $result['subtotal'];
        $invoice->discount_total = $result['discount_total'];
        $invoice->tax_total = $result['tax_total'];
        $invoice->total = $result['total'];
        $invoice->amount_paid = $invoice->exists ? $invoice->amount_paid : 0;
        $invoice->amount_due = round($result['total'] - (float) $invoice->amount_paid, 2);
    }
}
