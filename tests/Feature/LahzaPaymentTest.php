<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LahzaPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.lahza.secret_key' => 'sk_test_lahza', 'services.lahza.base_url' => 'https://api.lahza.io']);
    }

    private function invoice(): Invoice
    {
        return Invoice::factory()->create([
            'customer_id' => Customer::factory()->create(['email' => 'payer@example.test'])->id,
            'currency' => 'ILS', 'total' => 250, 'subtotal' => 250, 'amount_due' => 250,
        ]);
    }

    public function test_pay_button_redirects_to_lahza_checkout_with_minor_units(): void
    {
        $invoice = $this->invoice();
        Http::fake(['api.lahza.io/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.lahza.io/abc']])]);

        $this->get(route('public.invoices.show', $invoice->public_token))->assertSee(route('public.invoices.pay', $invoice->public_token));

        $this->post(route('public.invoices.pay', $invoice->public_token))->assertRedirect('https://checkout.lahza.io/abc');

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sk_test_lahza')
            && $r['amount'] === 25000 && $r['currency'] === 'ILS' && $r['metadata']['invoice_id'] === $invoice->id
            && str_contains($r['callback_url'], $invoice->public_token));
    }

    public function test_callback_verifies_and_records_the_payment_once(): void
    {
        $invoice = $this->invoice();
        $reference = 'INV'.$invoice->id.'-20261116-123';
        Http::fake(["api.lahza.io/transaction/verify/{$reference}" => Http::response(['status' => true, 'data' => [
            'status' => 'success', 'amount' => 25000, 'currency' => 'ILS', 'reference' => $reference, 'metadata' => ['invoice_id' => $invoice->id],
        ]])]);

        $this->get(route('public.invoices.callback', [$invoice->public_token, 'reference' => $reference]))->assertSessionHas('success');
        $this->get(route('public.invoices.callback', [$invoice->public_token, 'reference' => $reference]));

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame('lahza', $invoice->payments()->first()->gateway);
    }

    public function test_failed_verification_records_nothing(): void
    {
        $invoice = $this->invoice();
        Http::fake(['api.lahza.io/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'failed', 'amount' => 25000, 'currency' => 'ILS']])]);

        $this->get(route('public.invoices.callback', [$invoice->public_token, 'reference' => 'INV'.$invoice->id.'-x']))->assertSessionHas('error');

        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_webhook_requires_a_valid_signature(): void
    {
        $invoice = $this->invoice();
        $reference = 'INV'.$invoice->id.'-hook';
        Http::fake(['api.lahza.io/transaction/verify/*' => Http::response(['data' => ['status' => 'success', 'amount' => 25000, 'currency' => 'ILS', 'reference' => $reference, 'metadata' => ['invoice_id' => $invoice->id]]])]);
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $reference]]);

        $this->call('POST', '/api/webhooks/lahza', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_LAHZA_SIGNATURE' => 'bad'], $payload)->assertUnauthorized();
        $this->assertSame(0, $invoice->payments()->count());

        $this->call('POST', '/api/webhooks/lahza', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_LAHZA_SIGNATURE' => hash_hmac('sha256', $payload, 'sk_test_lahza')], $payload)->assertOk();
        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_unsupported_currency_hides_online_payment(): void
    {
        $invoice = $this->invoice();
        $invoice->update(['currency' => 'EUR']);

        $this->post(route('public.invoices.pay', $invoice->public_token))->assertNotFound();
    }
}
