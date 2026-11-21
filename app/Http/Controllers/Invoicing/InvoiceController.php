<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordInvoicePaymentRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Services\Invoicing\InvoiceService;
use App\Services\Invoicing\PdfService;
use App\Services\Reminders\InvoiceReminderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $invoices = Invoice::query()
            ->with('customer:id,name,company_name')
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when($status === 'unpaid', fn ($q) => $q->open())
            ->when(in_array($status, Invoice::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('number', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%")->orWhere('company_name', 'like', "%{$s}%"))))
            ->latest('issue_date')->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'outstanding' => (float) Invoice::query()->open()->sum('amount_due'),
            'overdue' => (float) Invoice::query()->overdue()->sum('amount_due'),
            'overdue_count' => Invoice::query()->overdue()->count(),
            'paid_this_month' => (float) \App\Models\InvoicePayment::query()->whereDate('paid_on', '>=', now()->startOfMonth()->toDateString())->whereDate('paid_on', '<=', today()->toDateString())->sum('amount'),
        ];

        return view('invoicing.invoices.index', compact('invoices', 'summary', 'status'));
    }

    public function create(Request $request): View
    {
        $invoice = new Invoice([
            'customer_id' => $request->integer('customer_id') ?: null,
            'issue_date' => today(),
            'due_date' => today()->addDays((int) BusinessSetting::get('invoice_due_days')),
            'currency' => BusinessSetting::get('currency'),
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'terms' => BusinessSetting::get('invoice_terms'),
        ]);

        return view('invoicing.invoices.form', $this->formData($invoice));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create($request->validated(), $request->user());

        if ($request->boolean('send')) {
            $this->invoices->markSent($invoice, $request->user());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', __('Invoice :number saved.', ['number' => $invoice->number]));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'payments.paymentMethod', 'reminders', 'warehouse']);

        return view('invoicing.invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'pdfEnabled' => app(PdfService::class)->enabled(),
        ]);
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if (! $invoice->isEditable()) {
            return redirect()->route('invoices.show', $invoice)->with('error', __('This invoice can no longer be edited.'));
        }

        return view('invoicing.invoices.form', $this->formData($invoice->load('items')));
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->update($invoice, $request->validated());

        return redirect()->route('invoices.show', $invoice)->with('success', __('Invoice :number saved.', ['number' => $invoice->number]));
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->markSent($invoice, $request->user());

        return back()->with('success', __('Invoice marked as sent. Share the customer link to let them view and pay it.'));
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->cancel($invoice, $request->user());

        return back()->with('success', __('Invoice cancelled.'));
    }

    public function recordPayment(RecordInvoicePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->recordPayment($invoice, $request->validated(), $request->user());

        return back()->with('success', __('Payment recorded.'));
    }

    public function remind(Request $request, Invoice $invoice, InvoiceReminderService $reminders): RedirectResponse
    {
        $results = $reminders->sendNow($invoice, $request->user());
        $sent = collect($results)->where('status', 'sent')->count();

        return back()->with($sent > 0 ? 'success' : 'error', $sent > 0
            ? __('Reminder sent on :count channel(s).', ['count' => $sent])
            : __('No reminder could be sent: :reason', ['reason' => collect($results)->pluck('error_message')->filter()->first() ?? __('the customer has no email or WhatsApp number.')]));
    }

    public function pdf(Invoice $invoice, PdfService $pdf): Response
    {
        return DocumentRenderer::respond($invoice->load(['customer', 'items']), 'invoice', $pdf);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Invoice $invoice): array
    {
        return [
            'document' => $invoice,
            'type' => 'invoice',
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name', 'currency']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price']),
            'taxes' => Tax::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
