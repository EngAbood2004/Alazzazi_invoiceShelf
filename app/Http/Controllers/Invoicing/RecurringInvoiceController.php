<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringInvoiceRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\Tax;
use App\Services\Invoicing\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoicing.recurring.index', [
            'schedules' => RecurringInvoice::query()->with('customer:id,name,company_name')->withCount('invoices')->orderByDesc('is_active')->orderBy('next_run_date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('invoicing.recurring.form', $this->formData(new RecurringInvoice([
            'frequency' => 'monthly', 'next_run_date' => today()->addMonthNoOverflow()->startOfMonth(),
            'due_in_days' => (int) BusinessSetting::get('invoice_due_days'), 'currency' => BusinessSetting::get('currency'),
            'is_active' => true, 'auto_send' => true, 'items' => [],
        ])));
    }

    public function store(StoreRecurringInvoiceRequest $request): RedirectResponse
    {
        RecurringInvoice::create($this->payload($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('recurring-invoices.index')->with('success', __('Recurring invoice saved.'));
    }

    public function edit(RecurringInvoice $recurringInvoice): View
    {
        return view('invoicing.recurring.form', $this->formData($recurringInvoice));
    }

    public function update(StoreRecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice): RedirectResponse
    {
        $recurringInvoice->update($this->payload($request));

        return redirect()->route('recurring-invoices.index')->with('success', __('Recurring invoice saved.'));
    }

    public function runNow(Request $request, RecurringInvoice $recurringInvoice, InvoiceService $invoices): RedirectResponse
    {
        $recurringInvoice->update(['next_run_date' => today()]);
        $invoice = $invoices->runRecurring($recurringInvoice);

        return $invoice
            ? redirect()->route('invoices.show', $invoice)->with('success', __('Invoice :number created.', ['number' => $invoice->number]))
            : back()->with('error', __('This schedule has finished.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreRecurringInvoiceRequest $request): array
    {
        $data = $request->validated();
        $data['auto_send'] = $request->boolean('auto_send');
        $data['is_active'] = $request->boolean('is_active');
        $data['items'] = collect($data['items'])->map(fn ($i) => [
            'description' => $i['description'], 'product_id' => $i['product_id'] ?? null, 'tax_id' => $i['tax_id'] ?? null,
            'quantity' => (float) $i['quantity'], 'unit_price' => (float) $i['unit_price'],
        ])->all();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(RecurringInvoice $schedule): array
    {
        return [
            'schedule' => $schedule,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price']),
            'taxes' => Tax::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
