<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\Invoicing\InvoiceService;

/**
 * Records a verified gateway payment exactly once (the callback and the
 * webhook can both arrive for the same transaction).
 */
class OnlinePaymentRecorder
{
    public function __construct(private readonly LahzaGateway $gateway, private readonly InvoiceService $invoices) {}

    public function recordReference(string $reference): ?InvoicePayment
    {
        if ($existing = InvoicePayment::query()->where('gateway_reference', $reference)->first()) {
            return $existing;
        }

        $result = $this->gateway->verify($reference);

        if (! $result['paid'] || ! $result['invoice_id']) {
            return null;
        }

        $invoice = Invoice::query()->find($result['invoice_id']);

        if (! $invoice || strtoupper($invoice->currency) !== $result['currency'] || (float) $invoice->amount_due <= 0) {
            return null;
        }

        return $this->invoices->recordPayment($invoice, [
            'amount' => min($result['amount'], (float) $invoice->amount_due),
            'paid_on' => today()->toDateString(),
            'gateway' => 'lahza',
            'gateway_reference' => $result['reference'],
            'reference' => $result['reference'],
            'notes' => 'Online payment (Lahza)',
        ]);
    }
}
