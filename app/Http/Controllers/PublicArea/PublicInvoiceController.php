<?php

namespace App\Http\Controllers\PublicArea;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Invoicing\DocumentRenderer;
use App\Models\BusinessSetting;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Services\Invoicing\InvoiceService;
use App\Services\Invoicing\PdfService;
use App\Services\Payments\LahzaGateway;
use App\Services\Payments\OnlinePaymentRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Customer-facing pages reached with the secret link (no login). */
class PublicInvoiceController extends Controller
{
    public function show(string $token, InvoiceService $invoices, LahzaGateway $gateway): View
    {
        $invoice = Invoice::query()->where('public_token', $token)->where('status', '!=', Invoice::STATUS_DRAFT)
            ->with(['customer', 'items', 'payments'])->firstOrFail();

        $invoices->markViewed($invoice);

        return view('invoicing.public.invoice', [
            'invoice' => $invoice,
            'company' => collect(BusinessSetting::DEFAULTS)->keys()->mapWithKeys(fn ($k) => [$k => BusinessSetting::get($k)])->all(),
            'canPayOnline' => $gateway->supports($invoice) && (float) $invoice->amount_due > 0 && $invoice->status !== Invoice::STATUS_CANCELLED,
        ]);
    }

    public function pdf(string $token, PdfService $pdf): Response
    {
        $invoice = Invoice::query()->where('public_token', $token)->where('status', '!=', Invoice::STATUS_DRAFT)->with(['customer', 'items'])->firstOrFail();

        return DocumentRenderer::respond($invoice, 'invoice', $pdf);
    }

    public function estimate(string $token, PdfService $pdf): Response
    {
        $estimate = Estimate::query()->where('public_token', $token)->where('status', '!=', 'draft')->with(['customer', 'items'])->firstOrFail();

        return DocumentRenderer::respond($estimate, 'estimate', $pdf);
    }

    public function pay(string $token, LahzaGateway $gateway): RedirectResponse
    {
        $invoice = Invoice::query()->where('public_token', $token)->with('customer')->firstOrFail();

        abort_unless($gateway->supports($invoice) && (float) $invoice->amount_due > 0 && $invoice->status !== Invoice::STATUS_CANCELLED, 404);

        try {
            return redirect()->away($gateway->initialize($invoice, route('public.invoices.callback', $token)));
        } catch (Throwable $e) {
            Log::error('Lahza initialize failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return back()->with('error', __('Online payment is not available right now. Please try again later.'));
        }
    }

    public function callback(Request $request, string $token, OnlinePaymentRecorder $recorder): RedirectResponse
    {
        $invoice = Invoice::query()->where('public_token', $token)->firstOrFail();
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        try {
            $payment = $reference !== '' ? $recorder->recordReference($reference) : null;
        } catch (Throwable $e) {
            Log::error('Lahza verify failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
            $payment = null;
        }

        return redirect()->route('public.invoices.show', $token)->with(
            $payment && $payment->invoice_id === $invoice->id ? 'success' : 'error',
            $payment && $payment->invoice_id === $invoice->id ? __('Thank you! Your payment was received.') : __('We could not confirm the payment yet. If you were charged, it will appear shortly.'),
        );
    }

    public function webhook(Request $request, LahzaGateway $gateway, OnlinePaymentRecorder $recorder): JsonResponse
    {
        if (! $gateway->validSignature($request->getContent(), $request->header('x-lahza-signature'))) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if ($request->input('event') === 'charge.success' && ($reference = $request->input('data.reference'))) {
            try {
                $recorder->recordReference((string) $reference);
            } catch (Throwable $e) {
                Log::error('Lahza webhook processing failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            }
        }

        return response()->json(['received' => true]);
    }
}
