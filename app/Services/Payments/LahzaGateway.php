<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lahza (lahza.io) — Palestinian online payment gateway (cards, Apple Pay,
 * local wallets) in ILS, JOD and USD.
 *
 *  - initialize: POST {base}/transaction/initialize, amount in minor units
 *  - verify:     GET  {base}/transaction/verify/{reference}
 *  - webhook:    header x-lahza-signature = HMAC-SHA256(raw body, secret key)
 *
 * Docs: https://docs.lahza.io/payments/accept-payments
 */
class LahzaGateway
{
    public const SUPPORTED_CURRENCIES = ['ILS', 'JOD', 'USD'];

    public function enabled(): bool
    {
        return filled(config('services.lahza.secret_key'));
    }

    public function supports(Invoice $invoice): bool
    {
        return $this->enabled() && in_array(strtoupper($invoice->currency), self::SUPPORTED_CURRENCIES, true);
    }

    /**
     * Starts a checkout for the invoice balance and returns the hosted payment URL.
     */
    public function initialize(Invoice $invoice, string $callbackUrl): string
    {
        $reference = 'INV'.$invoice->id.'-'.now()->format('YmdHis').'-'.random_int(100, 999);

        $response = $this->client()->post('/transaction/initialize', [
            'email' => $invoice->customer->email ?: 'customer'.$invoice->customer_id.'@example.invalid',
            'mobile' => $invoice->customer->phone,
            'amount' => (int) round((float) $invoice->amount_due * 100),
            'currency' => strtoupper($invoice->currency),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'custom_fields' => [['display_name' => 'Invoice', 'variable_name' => 'invoice', 'value' => $invoice->number]],
            ],
        ])->throw();

        $url = $response->json('data.authorization_url') ?? $response->json('authorization_url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Lahza did not return a checkout URL.');
        }

        return $url;
    }

    /**
     * @return array{paid: bool, amount: float, currency: string, reference: string, invoice_id: int|null}
     */
    public function verify(string $reference): array
    {
        $data = $this->client()->get('/transaction/verify/'.rawurlencode($reference))->throw()->json('data') ?? [];

        $invoiceId = $data['metadata']['invoice_id'] ?? null;
        if (! $invoiceId && preg_match('/^INV(\d+)-/', $reference, $m)) {
            $invoiceId = (int) $m[1];
        }

        return [
            'paid' => ($data['status'] ?? null) === 'success',
            'amount' => round(((int) ($data['amount'] ?? 0)) / 100, 2),
            'currency' => strtoupper((string) ($data['currency'] ?? '')),
            'reference' => (string) ($data['reference'] ?? $reference),
            'invoice_id' => $invoiceId ? (int) $invoiceId : null,
        ];
    }

    public function validSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.lahza.secret_key');

        return $secret !== '' && is_string($signature)
            && hash_equals(hash_hmac('sha256', $payload, $secret), strtolower(trim($signature)));
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.lahza.base_url'), '/'))
            ->withToken((string) config('services.lahza.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
