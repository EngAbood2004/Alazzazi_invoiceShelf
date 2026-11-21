<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Product;
use App\Models\Tax;
use App\Services\Invoicing\EstimateService;
use App\Services\Invoicing\InvoiceService;
use App\Services\Invoicing\PdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class EstimateController extends Controller
{
    public function __construct(private readonly EstimateService $estimates) {}

    public function index(Request $request): View
    {
        $estimates = Estimate::query()->with('customer:id,name,company_name')
            ->when(in_array($request->string('status')->toString(), Estimate::STATUSES, true), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest('issue_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('invoicing.estimates.index', compact('estimates'));
    }

    public function create(Request $request): View
    {
        return view('invoicing.invoices.form', $this->formData(new Estimate([
            'customer_id' => $request->integer('customer_id') ?: null,
            'issue_date' => today(),
            'expiry_date' => today()->addDays((int) BusinessSetting::get('estimate_valid_days')),
            'currency' => BusinessSetting::get('currency'),
            'discount_type' => 'fixed',
            'discount_value' => 0,
        ])));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $estimate = $this->estimates->save($request->validated(), null, $request->user());

        return redirect()->route('estimates.show', $estimate)->with('success', __('Estimate :number saved.', ['number' => $estimate->number]));
    }

    public function show(Estimate $estimate): View
    {
        return view('invoicing.estimates.show', [
            'estimate' => $estimate->load(['customer', 'items', 'invoice']),
            'pdfEnabled' => app(PdfService::class)->enabled(),
        ]);
    }

    public function edit(Estimate $estimate): View|RedirectResponse
    {
        if (in_array($estimate->status, ['converted'], true)) {
            return redirect()->route('estimates.show', $estimate)->with('error', __('A converted estimate cannot be edited.'));
        }

        return view('invoicing.invoices.form', $this->formData($estimate->load('items')));
    }

    public function update(StoreInvoiceRequest $request, Estimate $estimate): RedirectResponse
    {
        abort_if($estimate->status === 'converted', 422);
        $this->estimates->save($request->validated(), $estimate);

        return redirect()->route('estimates.show', $estimate)->with('success', __('Estimate :number saved.', ['number' => $estimate->number]));
    }

    public function status(Request $request, Estimate $estimate): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['sent', 'accepted', 'rejected'])]]);
        abort_if($estimate->status === 'converted', 422);

        $estimate->update(['status' => $data['status'], 'sent_at' => $estimate->sent_at ?? now()]);

        return back()->with('success', __('Estimate updated.'));
    }

    public function convert(Request $request, Estimate $estimate, InvoiceService $invoices): RedirectResponse
    {
        $invoice = $invoices->convertEstimate($estimate->load('items'), $request->user());

        return redirect()->route('invoices.edit', $invoice)->with('success', __('Invoice :number created from the estimate.', ['number' => $invoice->number]));
    }

    public function pdf(Estimate $estimate, PdfService $pdf): Response
    {
        return DocumentRenderer::respond($estimate->load(['customer', 'items']), 'estimate', $pdf);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Estimate $estimate): array
    {
        return [
            'document' => $estimate,
            'type' => 'estimate',
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name', 'currency']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price']),
            'taxes' => Tax::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => collect(),
        ];
    }
}
